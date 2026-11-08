@props([
    'variant' => 'panel',
    'title' => null,
    'titleId' => null,
])

<div
    @if ($variant === 'embedded')
        x-data="aiChat({ variant: 'embedded', title: @js($title), titleId: @js($titleId) })"
    @endif
    class="flex flex-col flex-1 {{ $variant === 'embedded' ? 'h-[32rem] max-h-[70vh]' : 'h-full' }} min-h-0 bg-card text-text overflow-hidden"
>
    @if (! app(\App\Services\Settings::class)->get('features.ai_enabled', true))
        <div class="p-6">
            <x-under-maintenance feature="AI search" />
        </div>
    @else
    {{-- Demo driver banner while AI_DRIVER=fake --}}
    @if (config('ai.driver') === 'fake')
        <div class="px-4 pt-3 pb-1">
            <x-ai-demo-banner />
        </div>
    @endif

    {{-- ai-status line shown BEFORE the user types if quota/availability triggers apply --}}
    <div x-cloak x-show="statusMessage" class="px-4 pt-3 pb-1">
        <x-ai-status>
            <span x-text="statusMessage"></span>
        </x-ai-status>
    </div>

    {{-- Upgrade card shown for Free and Plus in those states --}}
    <div x-cloak x-show="showUpgradeCard" class="px-4 pt-2 pb-1">
        <x-upgrade-card />
    </div>

    {{-- Scrollable messages container --}}
    <div
        x-ref="messagesContainer"
        class="flex-1 min-h-0 overflow-y-auto px-4 py-4 space-y-4 scroll-smooth"
        :class="variant === 'embedded' && messages.length === 0 ? 'flex items-start' : ''"
    >
        {{-- Empty state: Data notice and suggested prompts --}}
        <template x-if="messages.length === 0 && variant !== 'embedded'">
            <div class="flex flex-col gap-4 py-2">
                {{-- Data notice --}}
                <div class="rounded-xl border border-border bg-surface/50 p-3.5 text-xs text-muted leading-relaxed">
                    <span x-text="dataNotice">
                        Your messages are processed by Google's Gemini to find titles and clips. Please do not share personal information.
                    </span>
                </div>

                {{-- Suggested prompts --}}
                <div class="flex flex-col gap-2">
                    <p class="text-xs font-medium text-muted">
                        {{ __('Suggested prompts:') }}
                    </p>
                    <div class="flex flex-col gap-1.5">
                        <template x-for="(prompt, idx) in suggestedPrompts" :key="idx">
                            <button
                                type="button"
                                x-on:click="usePrompt(prompt)"
                                class="w-full text-left rounded-xl border border-border bg-surface px-3 py-2.5 text-xs sm:text-sm text-text hover:border-accent hover:bg-elevated transition-colors"
                            >
                                <span x-text="prompt"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="variant === 'embedded' && messages.length === 0">
            <p class="w-full rounded-xl bg-surface border border-border p-3 text-sm text-muted" x-text="`What would you like to know about ${title}?`">
                What would you like to know about {{ $title }}?
            </p>
        </template>

        {{-- Conversation messages --}}
        <template x-for="(msg, index) in messages" :key="index">
            <div class="flex flex-col gap-2">
                {{-- User Message --}}
                <template x-if="msg.role === 'user'">
                    <div class="flex justify-end">
                        <div class="max-w-[85%] rounded-2xl rounded-br-sm bg-accent px-4 py-2.5 text-sm text-white leading-relaxed">
                            <span x-text="msg.content"></span>
                        </div>
                    </div>
                </template>

                {{-- Assistant Message --}}
                <template x-if="msg.role === 'assistant'">
                    <div class="flex flex-col gap-3">
                        <div class="max-w-[90%] rounded-2xl rounded-bl-sm bg-elevated border border-border px-4 py-2.5 text-sm text-text leading-relaxed">
                            <span x-text="msg.content"></span>
                        </div>

                        {{-- AI Title Cards --}}
                        <template x-if="msg.titles && msg.titles.length > 0">
                            <div class="grid grid-cols-1 gap-2.5 mt-1">
                                <template x-for="item in msg.titles" :key="item.id">
                                    <article class="scene-frame bg-surface border border-border rounded-xl p-3 sm:p-4 flex flex-col gap-2 hover:border-accent/40 transition-colors">
                                        <div class="scene-frame-bracket scene-frame-tl"></div>
                                        <div class="scene-frame-bracket scene-frame-tr"></div>
                                        <div class="scene-frame-bracket scene-frame-bl"></div>
                                        <div class="scene-frame-bracket scene-frame-br"></div>

                                        <div class="flex items-start justify-between gap-2">
                                            <h5 class="text-sm font-semibold text-text leading-snug">
                                                <a :href="item.url" class="hover:text-accent transition-colors">
                                                    <span x-text="item.title"></span>
                                                    <template x-if="item.year">
                                                        <span class="text-muted font-normal text-xs ml-1" x-text="'(' + item.year + ')'"></span>
                                                    </template>
                                                </a>
                                            </h5>
                                            <a :href="item.url" class="shrink-0 text-xs text-accent hover:underline flex items-center gap-1 font-medium">
                                                <span>{{ __('View') }}</span>
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M5 12h14" />
                                                    <path d="m12 5 7 7-7 7" />
                                                </svg>
                                            </a>
                                        </div>

                                        <template x-if="item.directors && item.directors.length > 0">
                                            <p class="text-xs text-muted" x-text="'Directed by ' + item.directors.join(', ')"></p>
                                        </template>

                                        <template x-if="item.reason">
                                            <div class="rounded-lg bg-card/80 border border-border/80 px-2.5 py-1.5 text-xs text-text/90">
                                                <span class="text-muted font-medium">{{ __('Why it matches:') }}</span>
                                                <span x-text="' ' + item.reason"></span>
                                            </div>
                                        </template>

                                        <template x-if="item.overview">
                                            <p class="text-xs text-muted line-clamp-2 leading-relaxed" x-text="item.overview"></p>
                                        </template>
                                    </article>
                                </template>
                            </div>
                        </template>

                        {{-- Clip teaser for free users --}}
                        <template x-if="msg.showClipTeaser">
                            <x-upgrade-card variant="clip_teaser" />
                        </template>

                        {{-- Clip Cards --}}
                        <template x-if="msg.clips && msg.clips.length > 0">
                            <div class="grid grid-cols-1 gap-3 mt-1">
                                <template x-for="clip in msg.clips" :key="clip.id || clip.url">
                                    <div
                                        x-data="{
                                            reported: clip.status === 'reported',
                                            embeddable: Boolean(clip.is_embeddable ?? (clip.status === 'active' && clip.video_platform === 'youtube' && clip.video_id && (clip.trust === 'official' || clip.trust === 'trusted'))),
                                            reportOpen: false,
                                            submitting: false,
                                            reportError: '',
                                            reportSuccess: false,
                                            reason: 'dead',
                                            details: '',
                                            get sourceLabel() {
                                                if (clip.source_label) return clip.source_label;
                                                if (clip.video_platform === 'other' || clip.video_platform === 'external' || clip.platform === 'external') return 'External link';
                                                if (clip.trust === 'official') return 'Official';
                                                if (clip.trust === 'trusted') return 'Trusted';
                                                return 'Unverified';
                                            },
                                            get videoId() {
                                                return clip.video_id || '';
                                            },
                                            get thumbnailUrl() {
                                                return this.videoId ? 'https://img.youtube.com/vi/' + this.videoId + '/hqdefault.jpg' : '';
                                            },
                                            get clipLabel() {
                                                return clip.label || clip.title || title || 'Clip';
                                            },
                                            async submitReport() {
                                                if (!clip.id || this.submitting) return;
                                                this.submitting = true;
                                                this.reportError = '';
                                                try {
                                                    const csrfToken = document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content') || '';
                                                    const response = await fetch('/clips/' + clip.id + '/report', {
                                                        method: 'POST',
                                                        headers: {
                                                            'Content-Type': 'application/json',
                                                            'Accept': 'application/json',
                                                            'X-CSRF-TOKEN': csrfToken,
                                                        },
                                                        body: JSON.stringify({ reason: this.reason, details: this.details }),
                                                    });
                                                    if (response.status === 429) {
                                                        this.reportError = 'Daily report limit reached (10/day).';
                                                        this.submitting = false;
                                                        return;
                                                    }
                                                    if (!response.ok) {
                                                        const data = await response.json().catch(() => ({}));
                                                        this.reportError = data.message || 'Unable to submit report.';
                                                        this.submitting = false;
                                                        return;
                                                    }
                                                    this.reported = true;
                                                    this.embeddable = false;
                                                    this.reportOpen = false;
                                                } catch (e) {
                                                    this.reportError = 'Network error. Please try again.';
                                                } finally {
                                                    this.submitting = false;
                                                }
                                            }
                                        }"
                                        class="scene-frame relative bg-surface border border-border rounded-xl p-3 sm:p-4 flex flex-col gap-3 hover:border-accent/40 transition-colors"
                                    >
                                        <div class="scene-frame-bracket scene-frame-tl"></div>
                                        <div class="scene-frame-bracket scene-frame-tr"></div>
                                        <div class="scene-frame-bracket scene-frame-bl"></div>
                                        <div class="scene-frame-bracket scene-frame-br"></div>

                                        {{-- Header with Source Label Badge & Report Button --}}
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold"
                                                    :class="{
                                                        'bg-accent/15 text-accent border border-accent/30': sourceLabel === 'Official',
                                                        'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30': sourceLabel === 'Trusted',
                                                        'bg-amber-500/15 text-amber-400 border border-amber-500/30': sourceLabel === 'Unverified',
                                                        'bg-surface text-muted border border-border': sourceLabel === 'External link'
                                                    }"
                                                    x-text="sourceLabel"
                                                ></span>
                                                <span
                                                    x-cloak
                                                    x-show="reported"
                                                    class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-rose-500/15 text-rose-400 border border-rose-500/30"
                                                >
                                                    Reported
                                                </span>
                                            </div>

                                            <template x-if="clip.id && !reported">
                                                <button
                                                    type="button"
                                                    x-on:click="reportOpen = !reportOpen"
                                                    class="inline-flex items-center gap-1 text-xs text-muted hover:text-text px-2 py-1 rounded hover:bg-elevated transition-colors"
                                                    aria-label="Report clip link"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                                                    </svg>
                                                    <span>Report</span>
                                                </button>
                                            </template>
                                        </div>

                                        {{-- Report dialog form --}}
                                        <div
                                            x-cloak
                                            x-show="reportOpen"
                                            x-on:click.outside="reportOpen = false"
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
                                                        class="w-full text-xs rounded-lg bg-surface border-border text-text py-1.5 px-2"
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
                                                        class="w-full text-xs rounded-lg bg-surface border-border text-text placeholder-muted p-2 resize-none"
                                                    ></textarea>
                                                </div>
                                                <div x-show="reportError" class="text-xs text-rose-400" x-text="reportError"></div>
                                                <div class="flex items-center justify-end gap-2 pt-1">
                                                    <button type="button" x-on:click="reportOpen = false" class="text-xs px-2.5 py-1.5 rounded-lg text-muted hover:text-text">Cancel</button>
                                                    <button
                                                        type="submit"
                                                        :disabled="submitting"
                                                        class="text-xs px-3 py-1.5 rounded-lg bg-rose-600 text-white font-medium hover:bg-rose-500 disabled:opacity-50"
                                                    >
                                                        <span x-text="submitting ? 'Submitting…' : 'Submit report'"></span>
                                                    </button>
                                                </div>
                                            </form>
                                        </div>

                                        {{-- Clip content: thumbnail + info --}}
                                        <div class="flex flex-col sm:flex-row gap-3">
                                            <div class="relative aspect-video sm:w-40 shrink-0 rounded-lg overflow-hidden bg-black/60 border border-border group">
                                                <template x-if="thumbnailUrl">
                                                    <img :src="thumbnailUrl" :alt="clipLabel" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                                </template>
                                                <template x-if="!thumbnailUrl">
                                                    <div class="w-full h-full flex items-center justify-center text-muted">
                                                        <svg class="w-7 h-7 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                        </svg>
                                                    </div>
                                                </template>
                                                <template x-if="embeddable && !reported">
                                                    <button
                                                        type="button"
                                                        x-on:click="$dispatch('open-preview', {
                                                            videoId: videoId,
                                                            url: clip.url,
                                                            title: clipLabel,
                                                            channelName: clip.channel_name || ''
                                                        })"
                                                        class="absolute inset-0 flex items-center justify-center bg-black/30 group-hover:bg-black/10 transition-colors focus:outline-none focus:ring-2 focus:ring-accent"
                                                        :aria-label="'Play preview for ' + clipLabel"
                                                    >
                                                        <div class="w-9 h-9 rounded-full bg-accent/90 text-white flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                                                            <svg class="w-4 h-4 ml-0.5 fill-current" viewBox="0 0 24 24">
                                                                <path d="M8 5v14l11-7z" />
                                                            </svg>
                                                        </div>
                                                    </button>
                                                </template>
                                            </div>

                                            <div class="flex-1 min-w-0 flex flex-col justify-between gap-2">
                                                <div>
                                                    <h4 class="text-sm font-semibold text-text leading-snug line-clamp-2" :title="clipLabel" x-text="clipLabel"></h4>
                                                    <template x-if="clip.channel_name">
                                                        <p class="text-xs text-muted mt-1 truncate" x-text="clip.channel_name"></p>
                                                    </template>
                                                </div>

                                                <div class="flex flex-wrap items-center gap-2 pt-1">
                                                    <template x-if="embeddable && !reported">
                                                        <button
                                                            type="button"
                                                            x-on:click="$dispatch('open-preview', {
                                                                videoId: videoId,
                                                                url: clip.url,
                                                                title: clipLabel,
                                                                channelName: clip.channel_name || ''
                                                            })"
                                                            class="inline-flex items-center justify-center min-h-[44px] px-3 py-1.5 rounded-xl text-xs sm:text-sm font-semibold bg-accent text-white hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-accent transition-all"
                                                        >
                                                            <svg class="w-3.5 h-3.5 mr-1.5 fill-current" viewBox="0 0 24 24">
                                                                <path d="M8 5v14l11-7z" />
                                                            </svg>
                                                            <span>Preview (first 1:00)</span>
                                                        </button>
                                                    </template>
                                                    <a
                                                        :href="clip.url"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="inline-flex items-center justify-center min-h-[44px] px-3 py-1.5 rounded-xl text-xs sm:text-sm font-medium border border-border bg-surface text-text hover:bg-elevated hover:border-accent/40 focus:outline-none focus:ring-2 focus:ring-accent transition-all"
                                                    >
                                                        <span>Watch full video</span>
                                                        <svg class="w-3.5 h-3.5 ml-1.5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                        </svg>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        {{-- No clip found state --}}
                        <template x-if="msg.noClipFound">
                            <div class="scene-frame relative bg-surface border border-border rounded-xl p-4 flex flex-col gap-3 mt-1">
                                <div class="scene-frame-bracket scene-frame-tl"></div>
                                <div class="scene-frame-bracket scene-frame-tr"></div>
                                <div class="scene-frame-bracket scene-frame-bl"></div>
                                <div class="scene-frame-bracket scene-frame-br"></div>

                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-card border border-border flex items-center justify-center shrink-0 text-muted">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-semibold text-text">No clip found</h4>
                                        <p class="text-xs text-muted">We could not find an official or trusted preview clip for this scene.</p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-border/50">
                                    <a
                                        :href="'https://www.youtube.com/results?search_query=' + encodeURIComponent((title || '') + ' ' + (msg.searchQuery || 'scene'))"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center justify-center min-h-[44px] px-3.5 py-2 rounded-xl text-xs sm:text-sm font-medium bg-surface border border-border text-text hover:border-accent hover:text-accent transition-colors"
                                    >
                                        <span>Search on YouTube</span>
                                        <svg class="w-3.5 h-3.5 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>

                                    <a
                                        href="#where-to-watch"
                                        class="inline-flex items-center justify-center min-h-[44px] px-3.5 py-2 rounded-xl text-xs sm:text-sm font-medium text-accent hover:underline"
                                    >
                                        <span>Where to watch</span>
                                        <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </template>

                        {{-- Failed request retry button --}}
                        <template x-if="msg.retryable">
                            <div class="pt-1">
                                <button
                                    type="button"
                                    x-on:click="retryLastMessage()"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-border bg-card text-xs font-medium text-text hover:bg-surface focus:outline-none focus:ring-2 focus:ring-accent"
                                >
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M3 12a9 9 0 0 1 15-6.7L21 8" />
                                        <path d="M21 3v5h-5" />
                                        <path d="M21 12a9 9 0 0 1-15 6.7L3 16" />
                                        <path d="M8 16H3v5" />
                                    </svg>
                                    <span>{{ __('Retry') }}</span>
                                </button>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </template>

        {{-- Progress steps & typing indicator & cancel button --}}
        <div x-cloak x-show="loading" class="flex flex-col gap-2 pt-1">
            <div class="inline-flex items-center gap-3 self-start rounded-2xl rounded-bl-sm bg-elevated border border-border px-4 py-2.5 text-xs text-muted">
                {{-- Typing indicator dots --}}
                <span class="flex items-center gap-1" aria-hidden="true">
                    <span class="h-1.5 w-1.5 rounded-full bg-accent animate-pulse"></span>
                    <span class="h-1.5 w-1.5 rounded-full bg-accent animate-pulse [animation-delay:200ms]"></span>
                    <span class="h-1.5 w-1.5 rounded-full bg-accent animate-pulse [animation-delay:400ms]"></span>
                </span>
                <span x-text="progressStep" class="text-text font-medium"></span>
                <button
                    type="button"
                    x-on:click="cancelRequest()"
                    class="text-xs text-muted hover:text-text underline-offset-2 hover:underline ml-1"
                >
                    {{ __('Cancel') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Quota meter at the bottom --}}
    <x-quota-meter />

    {{-- Chat input area --}}
    <form
        x-on:submit.prevent="sendMessage()"
        class="p-3 border-t border-border bg-card flex items-center gap-2"
    >
        <label for="ai-chat-input-field" class="sr-only">{{ __('Message AI Chat') }}</label>
        <input
            id="ai-chat-input-field"
            type="text"
            x-ref="chatInput"
            x-model="input"
            autocomplete="off"
            :disabled="loading || isProviderUnavailable"
            placeholder="{{ __('Describe a movie or scene...') }}"
            class="flex-1 rounded-xl border border-border bg-surface px-3.5 py-2.5 text-base sm:text-sm text-text placeholder-muted focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent disabled:opacity-50"
        />
        <button
            type="submit"
            :disabled="loading || !input.trim() || isProviderUnavailable"
            class="inline-flex items-center justify-center h-10 w-10 shrink-0 rounded-xl bg-accent text-white hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-accent disabled:opacity-40 transition-opacity"
            aria-label="{{ __('Send message') }}"
        >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14" />
                <path d="m12 5 7 7-7 7" />
            </svg>
        </button>
    </form>

    @if ($variant === 'embedded')
    {{-- Exit Warning Modal (shown only when hasMessages is true) --}}
    <div
        x-cloak
        x-show="exitModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-bg/80 backdrop-blur-sm"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="embedded-exit-modal-title"
        aria-describedby="embedded-exit-modal-description"
    >
        <div
            x-show="exitModalOpen"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-sm rounded-2xl border border-border bg-elevated p-6 shadow-2xl flex flex-col gap-4"
        >
            <div class="flex flex-col gap-1">
                <h4 id="embedded-exit-modal-title" class="text-base font-semibold text-text">
                    {{ __('Leave this chat?') }}
                </h4>
                <p id="embedded-exit-modal-description" class="text-sm text-muted leading-relaxed">
                    {{ __('Your conversation with the AI will be lost.') }}
                </p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button
                    type="button"
                    x-on:click="cancelLeave()"
                    class="px-4 py-2 rounded-xl border border-border bg-card text-sm font-medium text-text hover:bg-surface focus:outline-none focus:ring-2 focus:ring-accent min-h-[44px]"
                >
                    {{ __('Stay') }}
                </button>
                <button
                    type="button"
                    x-on:click="confirmLeave()"
                    class="px-4 py-2 rounded-xl bg-accent text-sm font-medium text-white hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-accent min-h-[44px]"
                >
                    {{ __('Leave') }}
                </button>
            </div>
        </div>
    </div>
    @endif
    @endif
</div>
