<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\MediaType;
use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\SearchMiss;
use App\Models\TitleRequest;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Catalog\CatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminMissedSearchController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalogService,
        private readonly AuditLogger $auditLogger
    ) {}

    public function index(Request $request): View
    {
        $status = $request->input('status', 'open');
        $query = SearchMiss::query();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $sort = $request->input('sort', 'hits');
        if ($sort === 'last_seen') {
            $query->orderByDesc('last_seen_at');
        } else {
            $query->orderByDesc('hits');
        }

        $searchMisses = $query->paginate(20)->withQueryString();

        return view('admin.missed-searches.index', [
            'misses' => $searchMisses,
            'currentStatus' => $status,
            'currentSort' => $sort,
        ]);
    }

    public function convert(Request $request, SearchMiss $searchMiss): RedirectResponse
    {
        $actionType = $request->input('action_type', 'request');

        if ($actionType === 'import') {
            $tmdbId = (int) $request->input('tmdb_id');
            $mediaType = $request->input('media_type', 'movie');
            try {
                $title = $this->catalogService->import($tmdbId, MediaType::from($mediaType), bypassThrottle: true);
                $searchMiss->update(['status' => 'added']);

                $this->auditLogger->log($request, 'missed_search.convert', $searchMiss, [
                    'query' => $searchMiss->query_norm,
                    'action' => 'import',
                    'title_id' => $title->id,
                ]);

                return redirect()->back()->with('success', "Imported \"{$title->title}\" from missed search.");
            } catch (\Throwable $e) {
                return redirect()->back()->with('error', "Import failed: {$e->getMessage()}");
            }
        } else {
            $user = $request->user('admin') ?? User::first();
            if ($user) {
                TitleRequest::create([
                    'user_id' => $user->id,
                    'media_type' => 'movie',
                    'title_text' => $searchMiss->query_norm,
                    'status' => RequestStatus::Pending,
                ]);
            }

            $searchMiss->update(['status' => 'requested']);

            $this->auditLogger->log($request, 'missed_search.convert', $searchMiss, [
                'query' => $searchMiss->query_norm,
                'action' => 'request',
            ]);

            return redirect()->back()->with('success', 'Created title request from missed search.');
        }
    }

    public function ignore(Request $request, SearchMiss $searchMiss): RedirectResponse
    {
        $searchMiss->update(['status' => 'ignored']);

        $this->auditLogger->log($request, 'missed_search.ignore', $searchMiss, [
            'query' => $searchMiss->query_norm,
        ]);

        return redirect()->back()->with('success', 'Missed search marked as ignored.');
    }
}
