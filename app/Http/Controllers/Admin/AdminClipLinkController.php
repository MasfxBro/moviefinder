<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClipLink;
use App\Models\Title;
use App\Services\AuditLogger;
use App\Services\Clips\PlatformUrl;
use App\Services\Clips\YoutubeOembed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminClipLinkController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly YoutubeOembed $youtube
    ) {}

    public function index(Request $request): View
    {
        $query = ClipLink::query()->with(['title']);

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($q) use ($search): void {
                $q->where('url', 'like', "%{$search}%")
                    ->orWhere('channel_name', 'like', "%{$search}%")
                    ->orWhere('label', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && in_array($request->input('status'), ['active', 'reported', 'removed'], true)) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('trust') && in_array($request->input('trust'), ['official', 'trusted', 'unverified'], true)) {
            $query->where('trust', $request->input('trust'));
        }

        if ($request->filled('source') && in_array($request->input('source'), ['admin', 'resolver'], true)) {
            $query->where('source', $request->input('source'));
        }

        $clipLinks = $query->orderByDesc('id')->paginate(20)->withQueryString();
        $titles = Title::orderBy('title')->get(['id', 'title']);

        return view('admin.clip-links.index', [
            'clipLinks' => $clipLinks,
            'titles' => $titles,
            'filters' => $request->only(['q', 'status', 'trust', 'source']),
        ]);
    }

    public function create(): View
    {
        $titles = Title::orderBy('title')->get(['id', 'title']);

        return view('admin.clip-links.create', [
            'titles' => $titles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title_id' => ['required', 'exists:titles,id'],
            'url' => ['required', 'url', 'max:500'],
            'label' => ['nullable', 'string', 'max:255'],
            'trust' => ['required', 'in:official,trusted,unverified'],
        ]);

        $parsed = PlatformUrl::parse($validated['url']);
        $platform = $parsed !== null ? $parsed->platform : 'other';
        $videoId = $parsed !== null ? $parsed->videoId : null;
        $channelName = null;
        $channelId = null;

        if ($platform === 'youtube' && $videoId !== null) {
            $oembed = $this->youtube->check($parsed);
            if ($oembed !== null) {
                $channelName = $oembed['author_name'] ?? null;
            }
        }

        $clipLink = ClipLink::create([
            'title_id' => $validated['title_id'],
            'video_platform' => $platform === 'youtube' ? 'youtube' : 'other',
            'video_id' => $videoId,
            'url' => $parsed !== null ? $parsed->canonicalUrl : $validated['url'],
            'channel_name' => $channelName,
            'channel_id' => $channelId,
            'label' => $validated['label'] ?: 'Clip',
            'trust' => $validated['trust'],
            'source' => 'admin',
            'status' => 'active',
            'verified_at' => now(),
            'created_by' => ($request->user('admin') ?? $request->user())?->id,
        ]);

        $this->auditLogger->log($request, 'clip_link.create', $clipLink, [
            'url' => $clipLink->url,
            'title_id' => $clipLink->title_id,
        ]);

        return redirect()->route('admin.clip_links.index')->with('success', 'Clip link created successfully.');
    }

    public function edit(ClipLink $clipLink): View
    {
        $titles = Title::orderBy('title')->get(['id', 'title']);

        return view('admin.clip-links.edit', [
            'clipLink' => $clipLink,
            'titles' => $titles,
        ]);
    }

    public function update(Request $request, ClipLink $clipLink): RedirectResponse
    {
        $validated = $request->validate([
            'title_id' => ['required', 'exists:titles,id'],
            'url' => ['required', 'url', 'max:500'],
            'label' => ['nullable', 'string', 'max:255'],
            'trust' => ['required', 'in:official,trusted,unverified'],
            'status' => ['required', 'in:active,reported,removed'],
        ]);

        $parsed = PlatformUrl::parse($validated['url']);
        $platform = $parsed !== null ? $parsed->platform : 'other';
        $videoId = $parsed !== null ? $parsed->videoId : null;

        $clipLink->update([
            'title_id' => $validated['title_id'],
            'video_platform' => $platform === 'youtube' ? 'youtube' : 'other',
            'video_id' => $videoId,
            'url' => $parsed !== null ? $parsed->canonicalUrl : $validated['url'],
            'label' => $validated['label'],
            'trust' => $validated['trust'],
            'status' => $validated['status'],
        ]);

        $this->auditLogger->log($request, 'clip_link.update', $clipLink, [
            'id' => $clipLink->id,
            'status' => $clipLink->status,
        ]);

        return redirect()->route('admin.clip_links.index')->with('success', 'Clip link updated successfully.');
    }

    public function destroy(Request $request, ClipLink $clipLink): RedirectResponse
    {
        $clipLink->delete();

        $this->auditLogger->log($request, 'clip_link.delete', null, [
            'id' => $clipLink->id,
            'url' => $clipLink->url,
        ]);

        return redirect()->route('admin.clip_links.index')->with('success', 'Clip link deleted successfully.');
    }

    public function verify(Request $request, ClipLink $clipLink): RedirectResponse
    {
        $parsed = PlatformUrl::parse($clipLink->url);
        if ($parsed === null || $parsed->platform !== 'youtube') {
            return redirect()->back()->with('error', 'Only YouTube URLs can be verified via oEmbed.');
        }

        $oembed = $this->youtube->check($parsed);
        if ($oembed === null) {
            return redirect()->back()->with('error', 'YouTube oEmbed verification failed or video is unavailable.');
        }

        $clipLink->update([
            'verified_at' => now(),
            'status' => 'active',
            'video_id' => $parsed->videoId,
            'channel_name' => $oembed['author_name'] ?? $clipLink->channel_name,
        ]);

        $this->auditLogger->log($request, 'clip_link.verify', $clipLink, [
            'id' => $clipLink->id,
            'url' => $clipLink->url,
        ]);

        return redirect()->back()->with('success', 'Clip link verified successfully.');
    }

    public function bulkRemove(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'channel_name' => ['required', 'string'],
        ]);

        $channelName = trim($validated['channel_name']);
        $count = ClipLink::whereRaw('LOWER(channel_name) = ?', [mb_strtolower($channelName)])
            ->update(['status' => 'removed']);

        $this->auditLogger->log($request, 'clip_link.bulk_remove', null, [
            'channel_name' => $channelName,
            'removed_count' => $count,
        ]);

        return redirect()->route('admin.clip_links.index')->with('success', "Bulk removed {$count} clip links for channel '{$channelName}'.");
    }
}
