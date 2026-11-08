<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\PlanResolver;
use App\Enums\RequestStatus;
use App\Http\Requests\StoreTitleRequest;
use App\Models\TitleRequest;
use App\Services\Ai\PlanCatalog;
use App\Services\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TitleRequestController extends Controller
{
    public function index(Request $request, PlanResolver $planResolver, PlanCatalog $planCatalog): View|JsonResponse
    {
        Gate::authorize('viewAny', TitleRequest::class);

        $user = $request->user();
        $requests = $user->titleRequests()
            ->with('resolvedTitle')
            ->latest()
            ->paginate(15);

        $plan = $planResolver->resolve($user);
        $dailyLimit = (int) ($planCatalog->get($plan)['requests_daily'] ?? 2);
        $usedToday = $user->titleRequests()
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
        $remainingToday = max(0, $dailyLimit - $usedToday);

        if ($request->wantsJson()) {
            return response()->json($requests);
        }

        return view('requests.index', compact('requests', 'dailyLimit', 'usedToday', 'remainingToday', 'plan'));
    }

    public function create(Request $request, PlanResolver $planResolver, PlanCatalog $planCatalog): View
    {
        Gate::authorize('create', TitleRequest::class);

        $user = $request->user();
        $plan = $planResolver->resolve($user);

        if (! app(Settings::class)->get('features.requests_open', true)) {
            return view('requests.create', [
                'maintenance' => true,
                'plan' => $plan,
                'usedToday' => 0,
                'dailyLimit' => 0,
                'remainingToday' => 0,
                'prefilledTitle' => '',
                'prefilledYear' => '',
                'prefilledType' => 'movie',
            ]);
        }

        $prefilledTitle = (string) $request->query('title', $request->query('q', ''));
        $prefilledYear = (string) $request->query('year', $request->query('release_year', ''));
        $prefilledType = (string) $request->query('type', $request->query('media_type', 'movie'));
        $dailyLimit = (int) ($planCatalog->get($plan)['requests_daily'] ?? 2);
        $usedToday = $user->titleRequests()
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
        $remainingToday = max(0, $dailyLimit - $usedToday);

        return view('requests.create', compact(
            'prefilledTitle',
            'prefilledYear',
            'prefilledType',
            'dailyLimit',
            'usedToday',
            'remainingToday',
            'plan'
        ));
    }

    public function store(StoreTitleRequest $request): RedirectResponse|JsonResponse
    {
        Gate::authorize('create', TitleRequest::class);

        if (! app(Settings::class)->get('features.requests_open', true)) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Title requests are under maintenance.'], 503);
            }

            return redirect()->route('requests.create');
        }

        $user = $request->user();
        $titleText = trim((string) ($request->input('title_text') ?? $request->input('title')));
        $mediaType = $request->input('media_type') ?? $request->input('type') ?? 'movie';
        $rawYear = $request->input('release_year') ?? $request->input('year');
        $releaseYear = filled($rawYear) ? (int) $rawYear : null;
        $note = $request->input('note');

        $titleRequest = $user->titleRequests()->create([
            'media_type' => $mediaType,
            'title_text' => $titleText,
            'release_year' => $releaseYear,
            'note' => $note,
            'status' => RequestStatus::Pending,
        ]);

        if ($request->wantsJson()) {
            return response()->json($titleRequest, 201);
        }

        return redirect()->route('requests.index')->with('status', 'Your title request has been submitted.');
    }

    public function show(Request $request, TitleRequest $titleRequest): View|JsonResponse
    {
        Gate::authorize('view', $titleRequest);

        if ($request->wantsJson()) {
            return response()->json($titleRequest);
        }

        return view('requests.show', compact('titleRequest'));
    }
}
