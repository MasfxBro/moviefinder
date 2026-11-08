export default function updateNotice(pollMinutes = 5) {
    return {
        show: false,
        currentBuild: '',
        latestBuild: '',
        dismissedBuild: null,
        dismissedUntil: 0,
        pollTimer: null,
        _visibilityHandler: null,

        init() {
            const meta = document.querySelector('meta[name="app-build"]');
            this.currentBuild = meta ? (meta.getAttribute('content') || '') : '';
            if (!this.currentBuild) {
                return;
            }

            this._visibilityHandler = () => {
                if (document.visibilityState === 'visible') {
                    this.check();
                }
            };
            document.addEventListener('visibilitychange', this._visibilityHandler);

            const intervalMs = Math.max(1, pollMinutes) * 60 * 1000;
            this.pollTimer = setInterval(() => {
                this.check();
            }, intervalMs);
        },

        async check() {
            if (!this.currentBuild) return;

            try {
                const response = await fetch('/version', {
                    headers: {
                        'Accept': 'application/json',
                    },
                    cache: 'no-store',
                });
                if (!response.ok) return;

                const data = await response.json();
                const newBuild = data.build;

                if (!newBuild || newBuild === this.currentBuild) {
                    this.show = false;
                    return;
                }

                this.latestBuild = newBuild;

                const isDismissed = this.dismissedBuild === newBuild && Date.now() < this.dismissedUntil;
                if (!isDismissed) {
                    this.show = true;
                }
            } catch {
                // Ignore network errors
            }
        },

        later() {
            this.show = false;
            this.dismissedBuild = this.latestBuild;
            this.dismissedUntil = Date.now() + 60 * 60 * 1000;
        },

        update() {
            let intercepted = false;
            window.dispatchEvent(new CustomEvent('ai-chat:confirm-exit', {
                detail: {
                    action: () => window.location.reload(),
                    onIntercept: () => {
                        intercepted = true;
                    },
                },
            }));

            if (!intercepted) {
                window.location.reload();
            }
        },

        destroy() {
            if (this._visibilityHandler) {
                document.removeEventListener('visibilitychange', this._visibilityHandler);
            }
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        },
    };
}
