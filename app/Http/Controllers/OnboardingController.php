<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\OnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OnboardingController extends Controller
{
    public function outcome(Request $request, OnboardingService $service): JsonResponse
    {
        $validated = $request->validate([
            'outcome' => ['required', 'string', Rule::in(['skipped', 'completed'])],
        ]);

        $service->recordOutcome($request->user(), $validated['outcome']);

        return response()->json(['status' => 'success']);
    }
}
