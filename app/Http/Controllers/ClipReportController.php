<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ReportClipLinkRequest;
use App\Models\ClipLink;
use App\Models\LinkReport;
use Illuminate\Http\JsonResponse;

class ClipReportController extends Controller
{
    public function store(ReportClipLinkRequest $request, ClipLink $clipLink): JsonResponse
    {
        $clipLink->update([
            'status' => 'reported',
        ]);

        $report = LinkReport::query()->create([
            'clip_link_id' => $clipLink->id,
            'user_id' => $request->user()->id,
            'reason' => $request->validated('reason'),
            'details' => $request->validated('details'),
            'status' => 'open',
        ]);

        return response()->json([
            'message' => 'Report submitted. Thank you for helping keep links accurate.',
            'status' => 'reported',
            'report_id' => $report->id,
        ], 200);
    }
}
