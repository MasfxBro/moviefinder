<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeedbackRequest;
use App\Models\Feedback;
use App\Services\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function create(Request $request, Settings $settings): View
    {
        if (! $settings->get('features.feedback_open', true)) {
            return view('feedback.create', ['maintenance' => true]);
        }

        $pagePath = $this->resolvePagePath($request);

        return view('feedback.create', [
            'maintenance' => false,
            'pagePath' => $pagePath,
        ]);
    }

    public function store(StoreFeedbackRequest $request, Settings $settings): RedirectResponse|JsonResponse
    {
        if (! $settings->get('features.feedback_open', true)) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Feedback is under maintenance.'], 503);
            }

            return redirect()->route('feedback.create');
        }

        $validated = $request->validated();
        $pagePath = $validated['page_path'] ?? null;
        if ($pagePath !== null && (str_starts_with($pagePath, 'http://') || str_starts_with($pagePath, 'https://'))) {
            $pagePath = parse_url($pagePath, PHP_URL_PATH);
        }

        $feedback = Feedback::create([
            'user_id' => $request->user()?->id,
            'type' => $validated['type'],
            'message' => strip_tags($validated['message']),
            'rating' => isset($validated['rating']) ? (int) $validated['rating'] : null,
            'page_path' => $pagePath !== null ? substr((string) $pagePath, 0, 200) : null,
            'status' => 'new',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Thank you for your feedback.',
                'feedback' => $feedback,
            ], 201);
        }

        return redirect()->route('feedback.create')
            ->with('status', 'thank-you')
            ->with('submitted_message', $feedback->message);
    }

    private function resolvePagePath(Request $request): ?string
    {
        $raw = $request->query('from') ?? $request->query('page_path') ?? $request->header('referer');

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
            $parsed = parse_url($raw);
            if (($parsed['host'] ?? null) === $request->getHost()) {
                return substr($parsed['path'] ?? '/', 0, 200);
            }

            return null;
        }

        if (str_starts_with($raw, '/') && ! str_starts_with($raw, '//')) {
            return substr($raw, 0, 200);
        }

        return null;
    }
}
