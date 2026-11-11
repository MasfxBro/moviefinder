<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChannelAllowlist;
use App\Models\ClipLink;
use App\Models\LinkReport;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminReportController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger
    ) {}

    public function index(Request $request): View
    {
        $status = $request->input('status', 'open');
        $query = LinkReport::query()->with(['clipLink.title', 'user', 'resolver']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $reports = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.reports.index', [
            'reports' => $reports,
            'currentStatus' => $status,
        ]);
    }

    public function restore(Request $request, LinkReport $linkReport): RedirectResponse
    {
        $clipLink = $linkReport->clipLink;
        if ($clipLink) {
            $clipLink->update(['status' => 'active']);
        }

        $linkReport->update([
            'status' => 'resolved',
            'resolved_by' => ($request->user('admin') ?? $request->user())?->id,
            'resolved_at' => now(),
            'details' => trim(($linkReport->details ? $linkReport->details."\n" : '').'Resolved: Restored clip link.'),
        ]);

        $this->auditLogger->log($request, 'link_report.restore', $linkReport, [
            'report_id' => $linkReport->id,
            'clip_link_id' => $clipLink !== null ? $clipLink->id : null,
        ]);

        return redirect()->route('admin.reports.index')->with('success', 'Clip link restored and report resolved.');
    }

    public function removeLink(Request $request, LinkReport $linkReport): RedirectResponse
    {
        $clipLink = $linkReport->clipLink;
        if ($clipLink) {
            $clipLink->update(['status' => 'removed']);
        }

        $linkReport->update([
            'status' => 'resolved',
            'resolved_by' => ($request->user('admin') ?? $request->user())?->id,
            'resolved_at' => now(),
            'details' => trim(($linkReport->details ? $linkReport->details."\n" : '').'Resolved: Removed clip link.'),
        ]);

        $this->auditLogger->log($request, 'link_report.remove_link', $linkReport, [
            'report_id' => $linkReport->id,
            'clip_link_id' => $clipLink !== null ? $clipLink->id : null,
        ]);

        return redirect()->route('admin.reports.index')->with('success', 'Clip link removed and report resolved.');
    }

    public function blockChannel(Request $request, LinkReport $linkReport): RedirectResponse
    {
        $clipLink = $linkReport->clipLink;
        if ($clipLink && filled($clipLink->channel_name)) {
            $channelName = $clipLink->channel_name;
            ChannelAllowlist::updateOrCreate(
                ['platform' => $clipLink->video_platform === 'youtube' ? 'youtube' : 'youtube', 'channel_id' => $clipLink->channel_id],
                ['channel_name' => $channelName, 'trust' => 'blocked', 'notes' => 'Blocked via report #'.$linkReport->id]
            );

            ClipLink::whereRaw('LOWER(channel_name) = ?', [mb_strtolower($channelName)])
                ->update(['status' => 'removed']);
        }

        $linkReport->update([
            'status' => 'resolved',
            'resolved_by' => ($request->user('admin') ?? $request->user())?->id,
            'resolved_at' => now(),
            'details' => trim(($linkReport->details ? $linkReport->details."\n" : '').'Resolved: Blocked channel and removed links.'),
        ]);

        $this->auditLogger->log($request, 'link_report.block_channel', $linkReport, [
            'report_id' => $linkReport->id,
            'channel_name' => $clipLink !== null ? $clipLink->channel_name : null,
        ]);

        return redirect()->route('admin.reports.index')->with('success', 'Channel blocked, all its links removed, and report resolved.');
    }

    public function resolve(Request $request, LinkReport $linkReport): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $note = trim((string) ($validated['note'] ?? 'Resolved with note.'));

        $linkReport->update([
            'status' => 'resolved',
            'resolved_by' => ($request->user('admin') ?? $request->user())?->id,
            'resolved_at' => now(),
            'details' => trim(($linkReport->details ? $linkReport->details."\n" : '').'Note: '.$note),
        ]);

        $this->auditLogger->log($request, 'link_report.resolve', $linkReport, [
            'report_id' => $linkReport->id,
            'note' => $note,
        ]);

        return redirect()->route('admin.reports.index')->with('success', 'Report resolved with note.');
    }
}
