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
</div>
