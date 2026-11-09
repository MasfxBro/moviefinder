<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChannelAllowlist;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminChannelAllowlistController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger
    ) {}

    public function index(Request $request): View
    {
        $query = ChannelAllowlist::query();

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($q) use ($search): void {
                $q->where('channel_name', 'like', "%{$search}%")
                    ->orWhere('channel_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('trust') && in_array($request->input('trust'), ['official', 'trusted', 'blocked'], true)) {
            $query->where('trust', $request->input('trust'));
        }

        $channels = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.channels.index', [
            'channels' => $channels,
            'filters' => $request->only(['q', 'trust']),
        ]);
    }

    public function create(): View
    {
        return view('admin.channels.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $channel = ChannelAllowlist::create($validated);

        $this->auditLogger->log($request, 'channel_allowlist.create', $channel, [
            'channel_name' => $channel->channel_name,
            'trust' => $channel->trust,
        ]);

        return redirect()->route('admin.channels.index')->with('success', "Channel '{$channel->channel_name}' added to allow-list.");
    }

    public function edit(ChannelAllowlist $channel): View
    {
        return view('admin.channels.edit', [
            'channel' => $channel,
        ]);
    }

    public function update(Request $request, ChannelAllowlist $channel): RedirectResponse
    {
        $validated = $this->validated($request);

        $channel->update($validated);

        $this->auditLogger->log($request, 'channel_allowlist.update', $channel, [
            'channel_name' => $channel->channel_name,
            'trust' => $channel->trust,
        ]);

        return redirect()->route('admin.channels.index')->with('success', "Channel '{$channel->channel_name}' updated.");
    }

    public function destroy(Request $request, ChannelAllowlist $channel): RedirectResponse
    {
        $channel->delete();

        $this->auditLogger->log($request, 'channel_allowlist.delete', null, [
            'channel_name' => $channel->channel_name,
        ]);

        return redirect()->route('admin.channels.index')->with('success', "Channel '{$channel->channel_name}' removed from allow-list.");
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'platform' => ['required', 'in:youtube'],
            'channel_id' => ['nullable', 'string', 'max:255'],
            'channel_name' => ['required', 'string', 'max:255'],
            'trust' => ['required', 'in:official,trusted,blocked'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
