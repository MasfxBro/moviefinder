<div
    x-data="previewPlayer()"
    x-cloak
    x-show="isOpen"
    x-on:keydown.escape.window="if (isOpen) close()"
    class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center"
    style="display: none;"
    role="dialog"
    aria-modal="true"
    aria-label="Video Preview"
>
    {{-- Backdrop --}}
    <div
        x-show="isOpen"
        x-on:click="close()"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-bg/80 backdrop-blur-sm"
        aria-hidden="true"
    ></div>

    {{-- Modal Box with signature scene-frame corner brackets --}}
    <div
        x-show="isOpen"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="scene-frame relative w-full max-w-3xl bg-elevated border border-border rounded-2xl shadow-2xl overflow-hidden z-10 mx-auto"
    >
        <div class="scene-frame-bracket scene-frame-tl"></div>
        <div class="scene-frame-bracket scene-frame-tr"></div>
        <div class="scene-frame-bracket scene-frame-bl"></div>
        <div class="scene-frame-bracket scene-frame-br"></div>

        {{-- Modal Header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-border bg-surface/50">
            <div class="min-w-0 pr-2">
                <h3 class="text-sm font-semibold text-text truncate" x-text="title || 'Clip Preview'">
                    Clip Preview
                </h3>
                <p class="text-xs text-muted truncate" x-text="channelName"></p>
            </div>
            <button
                type="button"
                x-on:click="close()"
                class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] rounded-xl text-muted hover:text-text hover:bg-surface focus:outline-none focus:ring-2 focus:ring-accent transition-colors"
                aria-label="Close preview"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Player Area / Overlay Area --}}
        <div class="relative aspect-video w-full bg-black">
            {{-- Host for dynamically mounted YouTube nocookie iframe (iframe only after click) --}}
            <div
                x-ref="playerHost"
                x-show="!ended"
                class="w-full h-full"
            ></div>

            {{-- "Preview ended - continue on YouTube" Overlay --}}
            <div
                x-cloak
                x-show="ended"
                class="absolute inset-0 flex flex-col items-center justify-center p-6 text-center bg-elevated/95"
            >
                <div class="max-w-md space-y-4">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-accent/20 text-accent">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    <h4 class="text-base sm:text-lg font-bold text-text">
                        Preview ended — continue on YouTube
                    </h4>

                    <div class="space-y-1">
                        <p class="text-xs sm:text-sm text-muted">
                            Channel: <span class="text-text font-medium" x-text="channelName || 'YouTube'"></span>
                        </p>
                        <p x-show="isLonger" class="text-xs text-muted">
                            This video is longer than the preview.
                        </p>
                    </div>

                    <div class="pt-2 flex flex-wrap items-center justify-center gap-3">
                        <a
                            :href="url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center justify-center min-h-[44px] px-5 py-2.5 rounded-xl text-sm font-semibold bg-accent text-white hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-accent transition-all"
                        >
                            <span>Watch full video</span>
                            <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                        <button
                            type="button"
                            x-on:click="close()"
                            class="inline-flex items-center justify-center min-h-[44px] px-4 py-2.5 rounded-xl text-sm font-medium text-muted hover:text-text hover:bg-surface border border-border focus:outline-none focus:ring-2 focus:ring-accent transition-colors"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
