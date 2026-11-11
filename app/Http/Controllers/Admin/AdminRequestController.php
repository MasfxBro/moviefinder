<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Contracts\PlanResolver;
use App\Enums\MediaType;
use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\TitleRequest;
use App\Notifications\RequestResolved;
use App\Services\AuditLogger;
use App\Services\Catalog\CatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminRequestController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalogService,
        private readonly AuditLogger $auditLogger
    ) {}

    public function index(Request $request): View
    {
        $status = $request->input('status', 'pending');

        $query = TitleRequest::query()->with(['user', 'resolvedTitle']);

        if ($status !== 'all' && RequestStatus::tryFrom($status)) {
            $query->where('status', $status);
        }

        $requests = $query->latest()->paginate(20)->withQueryString();

        $items = $requests->getCollection();
        $sorted = $items->sort(function ($a, $b) {
            $planA = app(PlanResolver::class)->resolve($a->user)->value;
            $planB = app(PlanResolver::class)->resolve($b->user)->value;
            $rank = fn ($p) => match ($p) {
                'pro' => 3, 'plus' => 2, default => 1
            };

            return $rank($planB) <=> $rank($planA);
        });
        $requests->setCollection($sorted->values());

        $pendingCount = TitleRequest::where('status', RequestStatus::Pending)->count();

        return view('admin.requests.index', [
            'requests' => $requests,
            'currentStatus' => $status,
            'pendingCount' => $pendingCount,
        ]);
    }

    public function approve(Request $request, TitleRequest $titleRequest): RedirectResponse
    {
        $tmdbId = (int) $request->input('tmdb_id');
        $mediaTypeValue = $request->input('media_type', $titleRequest->media_type->value);

        $title = null;
        if ($tmdbId > 0) {
            try {
                $title = $this->catalogService->import($tmdbId, MediaType::from((string) $mediaTypeValue), bypassThrottle: true);
            } catch (\Throwable $e) {
                return redirect()->back()->with('error', "TMDB Import failed: {$e->getMessage()}");
            }
        }

        $titleRequest->update([
            'status' => $title ? RequestStatus::Added : RequestStatus::Approved,
            'resolved_title_id' => $title?->id,
            'admin_note' => $request->input('admin_note'),
        ]);

        if ($titleRequest->user) {
            $titleRequest->user->notify(new RequestResolved($titleRequest));
        }

        $this->auditLogger->log($request, 'request.approve', $titleRequest, [
            'title_text' => $titleRequest->title_text,
            'resolved_title_id' => $title?->id,
        ]);

        return redirect()->back()->with('success', "Request for \"{$titleRequest->title_text}\" approved.");
    }

    public function createManual(Request $request, TitleRequest $titleRequest): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'media_type' => 'required|in:movie,tv',
            'release_year' => 'nullable|integer|min:1880|max:2100',
            'overview' => 'nullable|string',
            'poster' => 'nullable|image|max:2048',
        ]);

        $title = $this->catalogService->createManual(
            [
                'title' => $validated['title'],
                'media_type' => $validated['media_type'],
                'release_year' => $validated['release_year'] ?? $titleRequest->release_year,
                'overview' => $validated['overview'] ?? $titleRequest->note,
            ],
            $request->file('poster'),
            $request->user('admin') ?? $request->user()
        );

        $titleRequest->update([
            'status' => RequestStatus::Added,
            'resolved_title_id' => $title->id,
        ]);

        if ($titleRequest->user) {
            $titleRequest->user->notify(new RequestResolved($titleRequest));
        }

        $this->auditLogger->log($request, 'request.create_manual', $titleRequest, [
            'title_text' => $titleRequest->title_text,
            'created_title_id' => $title->id,
        ]);

        return redirect()->back()->with('success', 'Title created and request marked as added.');
    }

    public function reject(Request $request, TitleRequest $titleRequest): RedirectResponse
    {
        $adminNote = (string) $request->input('admin_note', 'Request rejected by admin.');

        $titleRequest->update([
            'status' => RequestStatus::Rejected,
            'admin_note' => $adminNote,
        ]);

        if ($titleRequest->user) {
            $titleRequest->user->notify(new RequestResolved($titleRequest));
        }

        $this->auditLogger->log($request, 'request.reject', $titleRequest, [
            'title_text' => $titleRequest->title_text,
            'admin_note' => $adminNote,
        ]);

        return redirect()->back()->with('success', "Request for \"{$titleRequest->title_text}\" rejected.");
    }

    public function merge(Request $request): RedirectResponse
    {
        $primaryId = (int) $request->input('primary_id');
        $duplicateIds = $request->input('duplicate_ids', []);

        if (! $primaryId || empty($duplicateIds)) {
            return redirect()->back()->with('error', 'Please select a primary request and at least one duplicate request.');
        }

        $primary = TitleRequest::findOrFail($primaryId);
        $duplicates = TitleRequest::whereIn('id', $duplicateIds)->get();

        foreach ($duplicates as $dup) {
            if ($dup->id === $primary->id) {
                continue;
            }
            $dup->update([
                'status' => RequestStatus::Rejected,
                'admin_note' => "Merged into request #{$primary->id}.",
            ]);
            if ($dup->user) {
                $dup->user->notify(new RequestResolved($dup));
            }
        }

        $this->auditLogger->log($request, 'request.merge', $primary, [
            'primary_id' => $primary->id,
            'duplicate_ids' => $duplicateIds,
        ]);

        return redirect()->back()->with('success', 'Requests merged successfully.');
    }
}
