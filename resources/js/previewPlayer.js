export function parseStartTime(url) {
    if (!url) return 0;
    try {
        const u = new URL(url, 'https://www.youtube.com');
        const t = u.searchParams.get('t');
        if (!t) return 0;
        if (/^\d+$/.test(t)) return parseInt(t, 10);
        let seconds = 0;
        const h = t.match(/(\d+)h/);
        const m = t.match(/(\d+)m/);
        const s = t.match(/(\d+)s/);
        if (h) seconds += parseInt(h[1], 10) * 3600;
        if (m) seconds += parseInt(m[1], 10) * 60;
        if (s) seconds += parseInt(s[1], 10);
        return seconds;
    } catch {
        return 0;
    }
}

function ensureYouTubeApi() {
    if (window.YT && window.YT.Player) {
        return Promise.resolve(window.YT);
    }

    return new Promise((resolve) => {
        const checkInterval = setInterval(() => {
            if (window.YT && window.YT.Player) {
                clearInterval(checkInterval);
                resolve(window.YT);
            }
        }, 50);

        if (!document.getElementById('youtube-iframe-api')) {
            const tag = document.createElement('script');
            tag.id = 'youtube-iframe-api';
            tag.src = 'https://www.youtube.com/iframe_api';
            document.head.appendChild(tag);
        }

        const prev = window.onYouTubeIframeAPIReady;
        window.onYouTubeIframeAPIReady = () => {
            if (typeof prev === 'function') prev();
            clearInterval(checkInterval);
            resolve(window.YT);
        };
    });
}

export default function previewPlayer() {
    return {
        isOpen: false,
        ended: false,
        isLonger: false,
        videoId: '',
        url: '',
        title: '',
        channelName: '',
        start: 0,
        end: 60,
        duration: 0,
        triggerEl: null,
        player: null,
        pollTimer: null,

        init() {
            window.addEventListener('open-preview', (event) => {
                const trigger = event.target || document.activeElement;
                this.open(event.detail || {}, trigger);
            });
        },

        open(detail, trigger = null) {
            if (this.isOpen) {
                this.close();
            }

            this.triggerEl = trigger || document.activeElement;
            this.videoId = detail.videoId || '';
            this.url = detail.url || '';
            this.title = detail.title || '';
            this.channelName = detail.channelName || '';

            const s = typeof detail.start === 'number' ? detail.start : parseStartTime(this.url);
            this.start = s;
            this.end = s + 60;
            this.ended = false;
            this.isLonger = false;
            this.duration = 0;
            this.isOpen = true;

            this.$nextTick(() => {
                this.mountPlayer();
            });
        },

        mountPlayer() {
            const host = this.$refs.playerHost;
            if (!host || !this.videoId) return;

            host.innerHTML = '';

            const origin = window.location.origin;
            const iframe = document.createElement('iframe');
            iframe.id = 'yt-preview-iframe';
            // youtube-nocookie, start/end = start+60, rel=0, playsinline=1, no autoplay
            iframe.src = `https://www.youtube-nocookie.com/embed/${this.videoId}?enablejsapi=1&origin=${encodeURIComponent(origin)}&start=${this.start}&end=${this.end}&rel=0&playsinline=1`;
            iframe.className = 'w-full h-full border-0';
            iframe.setAttribute('allow', 'accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
            iframe.setAttribute('allowfullscreen', 'true');
            iframe.title = this.title || 'Clip Preview';
            host.appendChild(iframe);

            ensureYouTubeApi().then((YT) => {
                if (!this.isOpen || this.ended) return;

                this.player = new YT.Player(iframe, {
                    events: {
                        onReady: (event) => {
                            try {
                                const dur = event.target.getDuration();
                                this.duration = dur || 0;
                                this.isLonger = this.duration > 60;
                            } catch {
                                this.duration = 0;
                                this.isLonger = false;
                            }
                            this.startPolling();
                        },
                        onStateChange: (event) => {
                            // YT.PlayerState.ENDED is 0
                            if (event.data === 0) {
                                this.endPreview();
                            }
                        },
                    },
                });
            });
        },

        startPolling() {
            this.stopPolling();
            this.pollTimer = setInterval(() => {
                if (!this.player || typeof this.player.getCurrentTime !== 'function') return;
                try {
                    const current = this.player.getCurrentTime();
                    if (current >= this.end) {
                        this.endPreview();
                    }
                } catch {
                    // Ignore transient errors
                }
            }, 250);
        },

        stopPolling() {
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        },

        endPreview() {
            this.stopPolling();
            if (this.player) {
                try {
                    this.player.destroy();
                } catch {
                    // Ignore destroy error
                }
                this.player = null;
            }
            if (this.$refs.playerHost) {
                this.$refs.playerHost.innerHTML = '';
            }
            this.ended = true;
        },

        close() {
            this.stopPolling();
            if (this.player) {
                try {
                    this.player.destroy();
                } catch {
                    // Ignore destroy error
                }
                this.player = null;
            }
            if (this.$refs.playerHost) {
                this.$refs.playerHost.innerHTML = '';
            }
            this.isOpen = false;
            this.ended = false;

            if (this.triggerEl && typeof this.triggerEl.focus === 'function') {
                const el = this.triggerEl;
                this.triggerEl = null;
                setTimeout(() => el.focus(), 50);
            }
        },
    };
}
