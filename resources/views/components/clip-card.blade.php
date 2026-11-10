@props([
    'clip' => [],
    'title' => '',
])

@php
    $isModel = $clip instanceof \App\Models\ClipLink;
    $id = $isModel ? $clip->id : ($clip['id'] ?? null);
    $url = $isModel ? $clip->url : ($clip['url'] ?? '#');
    $platform = $isModel ? $clip->video_platform : ($clip['video_platform'] ?? $clip['platform'] ?? 'youtube');
    $videoId = $isModel ? $clip->video_id : ($clip['video_id'] ?? null);
    $channelName = $isModel ? $clip->channel_name : ($clip['channel_name'] ?? '');
    $label = $isModel ? ($clip->label ?: ($clip->title?->title ?? $title)) : ($clip['label'] ?? $clip['title'] ?? $title);
    $trust = $isModel ? $clip->trust : ($clip['trust'] ?? 'unverified');
    $status = $isModel ? $clip->status : ($clip['status'] ?? 'active');

    $isEmbeddable = $isModel
        ? $clip->isEmbeddable()
        : ($status === 'active' && $platform === 'youtube' && filled($videoId) && in_array($trust, ['official', 'trusted'], true));

    $sourceLabel = match (true) {
        $platform === 'other' || $platform === 'external' => 'External link',
        $trust === 'official' => 'Official',
        $trust === 'trusted' => 'Trusted',
        default => 'Unverified',
    };

    $thumbnailUrl = $videoId
        ? "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg"
        : ($clip['thumbnail'] ?? null);

    $reportUrl = $id ? route('clips.report', $id) : null;
@endphp

<article
    x-data="{
        reported: @js($status === 'reported'),
        embeddable: @js($isEmbeddable),
        reportOpen: false,
        submitting: false,
        reportError: '',
        reportSuccess: false,
        reason: 'dead',
        details: '',

        async submitReport() {
            if (! @js($reportUrl) || this.submitting) return;
            this.submitting = true;
            this.reportError = '';

            try {
                const csrfToken = document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content') || '';
                const response = await fetch(@js($reportUrl), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        reason: this.reason,
                        details: this.details,
                    }),
                });

                if (response.status === 429) {
                    this.reportError = 'Daily report limit reached (10/day).';
                    this.submitting = false;
                    return;
                }

                if (! response.ok) {
                    const data = await response.json().catch(() => ({}));
                    this.reportError = data.message || 'Unable to submit report.';
                    this.submitting = false;
                    return;
                }

                this.reported = true;
                this.embeddable = false;
                this.reportSuccess = true;
                this.reportOpen = false;
            } catch (e) {
                this.reportError = 'Network error. Please try again.';
            } finally {
                this.submitting = false;
            }
        }
    }"
    {{ $attributes->merge(['class' => 'scene-frame relative bg-surface border border-border rounded-xl p-3 sm:p-4 flex flex-col gap-3 hover:border-accent/40 transition-colors']) }}
>
    {{-- Signature scene-frame corner brackets --}}
    <div class="scene-frame-bracket scene-frame-tl"></div>
    <div class="scene-frame-bracket scene-frame-tr"></div>
    <div class="scene-frame-bracket scene-frame-bl"></div>
    <div class="scene-frame-bracket scene-frame-br"></div>

    {{-- Top row: Source Label & Channel / Report Button --}}
    <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            {{-- Source Label Badge (Official / Trusted / Unverified / External link) --}}
            <span
                class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold
                @if ($sourceLabel === 'Official')
                    bg-accent/15 text-accent border border-accent/30
                @elseif ($sourceLabel === 'Trusted')
                    bg-emerald-500/15 text-emerald-400 border border-emerald-500/30
                @elseif ($sourceLabel === 'Unverified')
                    bg-amber-500/15 text-amber-400 border border-amber-500/30
                @else
                    bg-surface text-muted border border-border
                @endif"
                data-testid="clip-source-label"
            >
                {{ $sourceLabel }}
            </span>

            {{-- Reported Badge --}}
            <span
                x-cloak
                x-show="reported"
                class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-rose-500/15 text-rose-400 border border-rose-500/30"
                data-testid="clip-reported-badge"
            >
                Reported
            </span>
        </div>

        {{-- Report button --}}
        @if ($reportUrl)
            <div class="relative">
                <button
                    type="button"
                    x-show="!reported"
                    x-on:click="reportOpen = !reportOpen"
                    class="inline-flex items-center gap-1 text-xs text-muted hover:text-text px-2 py-1 rounded hover:bg-elevated transition-colors focus:outline-none focus:ring-1 focus:ring-accent"
                    aria-label="Report clip link"
                    data-testid="report-link-button"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                    </svg>
                    <span>Report</span>
                </button>
            </div>
        @endif
    </div>

    {{-- Report Dialog / Dropdown Form --}}
    <div
        x-cloak
        x-show="reportOpen"
        x-on:click.outside="reportOpen = false"
        x-transition
        class="bg-elevated border border-border rounded-xl p-3 space-y-3 z-10 shadow-lg"
    >
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-text">Report this link</span>
            <button type="button" x-on:click="reportOpen = false" class="text-muted hover:text-text text-xs p-1">✕</button>
        </div>

        <form x-on:submit.prevent="submitReport()" class="space-y-2.5">
            <div>
                <label class="block text-xs text-muted mb-1 font-medium">Reason</label>
                <select
                    x-model="reason"
                    class="w-full text-xs rounded-lg bg-surface border-border text-text focus:ring-accent focus:border-accent py-1.5 px-2"
                >
                    <option value="dead">Dead link</option>
                    <option value="wrong_scene">Wrong scene</option>
                    <option value="copyright">Copyright concern</option>
                    <option value="inappropriate">Inappropriate</option>
                </select>
            </div>

            <div>
                <label class="block text-xs text-muted mb-1 font-medium">Optional details</label>
                <textarea
                    x-model="details"
                    maxlength="500"
                    rows="2"
                    placeholder="Describe the issue..."
                    class="w-full text-xs rounded-lg bg-surface border-border text-text placeholder-muted focus:ring-accent focus:border-accent p-2 resize-none"
                ></textarea>
            </div>

            <div x-show="reportError" class="text-xs text-rose-400" x-text="reportError"></div>

            <div class="flex items-center justify-end gap-2 pt-1">
                <button
                    type="button"
                    x-on:click="reportOpen = false"
                    class="text-xs px-2.5 py-1.5 rounded-lg text-muted hover:text-text"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    :disabled="submitting"
                    class="text-xs px-3 py-1.5 rounded-lg bg-rose-600 text-white font-medium hover:bg-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500 disabled:opacity-50"
                >
                    <span x-text="submitting ? 'Submitting…' : 'Submit report'">Submit report</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Main Clip Content: Thumbnail + Details --}}
    <div class="flex flex-col sm:flex-row gap-3">
        {{-- Thumbnail --}}
        <div class="relative aspect-video sm:w-44 shrink-0 rounded-lg overflow-hidden bg-black/60 border border-border group">
            @if ($thumbnailUrl)
                <img
                    src="{{ $thumbnailUrl }}"
                    alt="{{ $label }}"
                    loading="lazy"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                />
            @else
                <div class="w-full h-full flex items-center justify-center text-muted">
                    <svg class="w-8 h-8 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </div>
            @endif

            {{-- Play button overlay (clicking thumbnail opens preview modal if embeddable) --}}
            <template x-if="embeddable && !reported">
                <button
                    type="button"
                    x-on:click="$dispatch('open-preview', {
                        videoId: @js($videoId),
                        url: @js($url),
                        title: @js($label),
                        channelName: @js($channelName)
                    })"
                    class="absolute inset-0 flex items-center justify-center bg-black/30 group-hover:bg-black/10 transition-colors focus:outline-none focus:ring-2 focus:ring-accent"
                    aria-label="Play preview for {{ $label }}"
                    data-testid="clip-thumbnail-preview"
                >
                    <div class="w-10 h-10 rounded-full bg-accent/90 text-white flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 ml-0.5 fill-current" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z" />
                        </svg>
                    </div>
                </button>
            </template>
        </div>

        {{-- Clip Information --}}
        <div class="flex-1 min-w-0 flex flex-col justify-between gap-2">
            <div>
                <h4 class="text-sm font-semibold text-text leading-snug line-clamp-2" title="{{ $label }}">
                    {{ $label }}
                </h4>
                @if ($channelName)
                    <p class="text-xs text-muted mt-1 truncate">
                        {{ $channelName }}
                    </p>
                @endif
            </div>

            {{-- Buttons Row: Preview and Watch full video --}}
            <div class="flex flex-wrap items-center gap-2 pt-1">
                {{-- Preview Button (Embeddable YouTube only) --}}
                <template x-if="embeddable && !reported">
                    <button
                        type="button"
                        x-on:click="$dispatch('open-preview', {
                            videoId: @js($videoId),
                            url: @js($url),
                            title: @js($label),
                            channelName: @js($channelName)
                        })"
                        class="inline-flex items-center justify-center min-h-[44px] px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-accent text-white hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-accent transition-all"
                        data-testid="clip-preview-button"
                    >
                        <svg class="w-4 h-4 mr-1.5 fill-current" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z" />
                        </svg>
                        <span>Preview (first 1:00)</span>
                    </button>
                </template>

                {{-- Watch Full Video Button (Always available) --}}
                <a
                    href="{{ $url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center justify-center min-h-[44px] px-3.5 py-2 rounded-xl text-xs sm:text-sm font-medium border border-border bg-surface text-text hover:bg-elevated hover:border-accent/40 focus:outline-none focus:ring-2 focus:ring-accent transition-all"
                    data-testid="clip-watch-full-button"
                >
                    <span>Watch full video</span>
                    <svg class="w-3.5 h-3.5 ml-1.5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
</article>
