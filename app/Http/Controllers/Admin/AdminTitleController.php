<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\MediaType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkImportTitlesRequest;
use App\Http\Requests\Admin\ImportTitleRequest;
use App\Http\Requests\Admin\StoreManualTitleRequest;
use App\Http\Requests\Admin\UpdateTitleRequest;
use App\Models\Genre;
use App\Models\Title;
use App\Services\AuditLogger;
use App\Services\Catalog\CatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTitleController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalogService,
        private readonly AuditLogger $auditLogger
    ) {}

    public function index(Request $request): View
    {
        $query = Title::query()->with(['genres']);

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('original_title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
                if (ctype_digit($search)) {
                    $q->orWhere('tmdb_id', (int) $search);
                }
            });
        }

        if ($request->filled('source') && in_array($request->input('source'), ['tmdb', 'manual'], true)) {
            $query->where('source', $request->input('source'));
        }

        if ($request->filled('hidden')) {
            if ($request->input('hidden') === '1') {
                $query->where('is_hidden', true);
            } elseif ($request->input('hidden') === '0') {
                $query->where('is_hidden', false);
            }
        }

        if ($request->input('stale') === '1') {
            $ageDays = (int) config('catalog.max_refresh_age_days', 150);
            $query->where('source', 'tmdb')
                ->where(function ($q) use ($ageDays): void {
                    $q->whereNull('synced_at')
                        ->orWhere('synced_at', '<=', now()->subDays($ageDays));
                });
        }

        if ($request->filled('media_type') && in_array($request->input('media_type'), ['movie', 'tv'], true)) {
            $query->where('media_type', $request->input('media_type'));
        }

        $titles = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.titles.index', [
            'titles' => $titles,
            'filters' => $request->only(['q', 'source', 'hidden', 'stale', 'media_type']),
        ]);
    }

    public function create(): View
    {
        $genres = Genre::orderBy('name')->get();

        return view('admin.titles.create', [
            'genres' => $genres,
        ]);
    }

    public function store(StoreManualTitleRequest $request): RedirectResponse
    {
        $title = $this->catalogService->createManual(
            $request->validated(),
            $request->file('poster'),
            $request->user('admin') ?? $request->user()
        );

        $this->auditLogger->log($request, 'title.create', $title, [
            'title' => $title->title,
            'media_type' => $title->media_type,
            'source' => 'manual',
        ]);

        return redirect()->route('admin.titles.index')
            ->with('success', "Title '{$title->title}' created successfully.");
    }

    public function import(ImportTitleRequest $request): RedirectResponse
    {
        try {
            $title = $this->catalogService->import(
                $request->validated('tmdb_id'),
                $request->validated('media_type'),
                bypassThrottle: true
            );

            $this->auditLogger->log($request, 'title.import', $title, [
                'tmdb_id' => $title->tmdb_id,
                'media_type' => $title->media_type,
            ]);

            return redirect()->route('admin.titles.index')
                ->with('success', "Title '{$title->title}' imported from TMDB successfully.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', "Import failed: {$e->getMessage()}");
        }
    }

    public function bulkImport(BulkImportTitlesRequest $request): RedirectResponse
    {
        $rawIds = preg_split('/[\r\n,]+/', (string) $request->input('ids'));
        $defaultType = MediaType::from($request->input('media_type', 'movie'));
        $items = [];

        foreach ($rawIds as $raw) {
            $raw = trim($raw);
            if ($raw === '') {
                continue;
            }
            if (str_contains($raw, ':')) {
                [$idPart, $typePart] = explode(':', $raw, 2);
                $type = MediaType::tryFrom(trim($typePart)) ?? $defaultType;
                $id = trim($idPart);
            } else {
                $id = $raw;
                $type = $defaultType;
            }
            if (ctype_digit($id) && (int) $id > 0) {
                $items[] = ['id' => (int) $id, 'type' => $type];
            }
        }

        if (empty($items)) {
            return redirect()->back()->with('error', 'No valid TMDB IDs provided.');
        }

        $result = $this->catalogService->bulkImport($items, bypassThrottle: true);

        $this->auditLogger->log($request, 'title.bulk_import', null, [
            'requested_count' => count($items),
            'imported_count' => count($result['imported']),
            'failed_count' => count($result['failed']),
            'imported_ids' => array_map(fn (Title $t) => $t->tmdb_id, $result['imported']),
        ]);

        $message = sprintf(
            'Bulk import completed: %d imported, %d failed/skipped.',
            count($result['imported']),
            count($result['failed'])
        );

        return redirect()->route('admin.titles.index')->with('success', $message);
    }

    public function edit(Title $title): View
    {
        $genres = Genre::orderBy('name')->get();

        return view('admin.titles.edit', [
            'title' => $title->load(['genres', 'people']),
            'genres' => $genres,
        ]);
    }

    public function update(UpdateTitleRequest $request, Title $title): RedirectResponse
    {
        $updated = $this->catalogService->update(
            $title,
            $request->validated(),
            $request->file('poster')
        );

        $this->auditLogger->log($request, 'title.update', $updated, [
            'title' => $updated->title,
            'locked_fields' => $updated->locked_fields,
        ]);

        return redirect()->route('admin.titles.edit', $updated)
            ->with('success', "Title '{$updated->title}' updated successfully.");
    }

    public function toggleHide(Request $request, Title $title): RedirectResponse
    {
        $updated = $this->catalogService->toggleHide($title);
        $action = $updated->is_hidden ? 'title.hide' : 'title.unhide';

        $this->auditLogger->log($request, $action, $updated, [
            'is_hidden' => $updated->is_hidden,
        ]);

        $statusText = $updated->is_hidden ? 'hidden' : 'visible';

        return redirect()->back()->with('success', "Title '{$updated->title}' is now {$statusText}.");
    }

    public function refresh(Request $request, Title $title): RedirectResponse
    {
        if ($title->source !== 'tmdb') {
            return redirect()->back()->with('error', 'Manual titles cannot be refreshed from TMDB.');
        }

        try {
            $refreshed = $this->catalogService->refreshTitle($title);

            $this->auditLogger->log($request, 'title.refresh', $refreshed, [
                'tmdb_id' => $refreshed->tmdb_id,
                'synced_at' => (string) $refreshed->synced_at,
            ]);

            return redirect()->back()->with('success', "Title '{$refreshed->title}' refreshed from TMDB.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', "Refresh failed: {$e->getMessage()}");
        }
    }

    public function confirmDelete(Title $title): View
    {
        $dependencies = $this->catalogService->dependencySummary($title);

        return view('admin.titles.delete', [
            'title' => $title,
            'dependencies' => $dependencies,
        ]);
    }

    public function destroy(Request $request, Title $title): RedirectResponse
    {
        $titleName = $title->title;
        $slug = $title->slug;
        $tmdbId = $title->tmdb_id;

        $summary = $this->catalogService->delete($title);

        $this->auditLogger->log($request, 'title.delete', $title, [
            'title' => $titleName,
            'slug' => $slug,
            'tmdb_id' => $tmdbId,
            'dependencies' => $summary,
        ]);

        return redirect()->route('admin.titles.index')
            ->with('success', "Title '{$titleName}' deleted successfully.");
    }
}
