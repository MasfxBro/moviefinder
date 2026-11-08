export default function guidedTour(config = {}) {
    return {
        isActive: false,
        allSteps: [],
        visibleSteps: [],
        currentIndex: 0,
        prefersReducedMotion: false,
        spotlightStyle: {
            top: 0,
            left: 0,
            width: 0,
            height: 0,
        },
        desktopCardStyle: '',
        _resizeHandler: null,
        _scrollHandler: null,

        init() {
            this.prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            const block = document.getElementById('guided-tour-steps');
            if (block && block.textContent) {
                try {
                    this.allSteps = JSON.parse(block.textContent);
                } catch {
                    this.allSteps = [];
                }
            }

            this._resizeHandler = () => {
                if (this.isActive) {
                    this.updatePosition();
                }
            };
            this._scrollHandler = () => {
                if (this.isActive) {
                    this.updatePosition();
                }
            };
            window.addEventListener('resize', this._resizeHandler);
            window.addEventListener('scroll', this._scrollHandler, { passive: true });

            if (config.startImmediately) {
                this.$nextTick(() => {
                    this.start();
                });
            }
        },

        destroy() {
            if (this._resizeHandler) {
                window.removeEventListener('resize', this._resizeHandler);
            }
            if (this._scrollHandler) {
                window.removeEventListener('scroll', this._scrollHandler);
            }
        },

        getTargetElement(target) {
            if (!target) return null;
            const elements = Array.from(document.querySelectorAll(`[data-tour="${target}"]`));
            return elements.find(el => this.isElementVisible(el)) || null;
        },

        isElementVisible(el) {
            if (!el) return false;
            const style = window.getComputedStyle(el);
            if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') {
                return false;
            }
            const rect = el.getBoundingClientRect();
            return rect.width > 0 && rect.height > 0;
        },

        start() {
            this.visibleSteps = this.allSteps.filter(step => this.getTargetElement(step.target) !== null);

            if (this.visibleSteps.length === 0) {
                return;
            }

            this.currentIndex = 0;
            this.isActive = true;

            this.$nextTick(() => {
                this.updatePosition();
                this.focusFirst();
            });
        },

        get currentStep() {
            return this.visibleSteps[this.currentIndex] || null;
        },

        get stepMarker() {
            if (this.visibleSteps.length === 0) return '';
            return `${this.currentIndex + 1} of ${this.visibleSteps.length}`;
        },

        get hasPrev() {
            return this.currentIndex > 0;
        },

        get isLast() {
            return this.currentIndex >= this.visibleSteps.length - 1;
        },

        next() {
            if (this.currentIndex < this.visibleSteps.length - 1) {
                this.currentIndex++;
                this.$nextTick(() => {
                    this.updatePosition();
                    this.focusFirst();
                });
            } else {
                this.finish();
            }
        },

        prev() {
            if (this.currentIndex > 0) {
                this.currentIndex--;
                this.$nextTick(() => {
                    this.updatePosition();
                    this.focusFirst();
                });
            }
        },

        skip() {
            this.isActive = false;
            this.recordOutcome('skipped');
        },

        finish() {
            this.isActive = false;
            this.recordOutcome('completed');
        },

        async recordOutcome(outcome) {
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                await fetch('/onboarding/outcome', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token || '',
                    },
                    body: JSON.stringify({ outcome }),
                });
            } catch {
                // Ignore network errors
            }
        },

        updatePosition() {
            const step = this.currentStep;
            if (!step) return;

            const el = this.getTargetElement(step.target);
            if (!el) return;

            el.scrollIntoView({
                behavior: this.prefersReducedMotion ? 'auto' : 'smooth',
                block: 'nearest',
                inline: 'nearest',
            });

            const rect = el.getBoundingClientRect();
            const padding = 6;

            this.spotlightStyle = {
                top: Math.max(0, rect.top - padding),
                left: Math.max(0, rect.left - padding),
                width: rect.width + (padding * 2),
                height: rect.height + (padding * 2),
            };

            if (window.innerWidth >= 640) {
                const cardWidth = 384;
                const cardHeight = 220;
                let top = rect.bottom + 12;
                let left = rect.left;

                if (left + cardWidth > window.innerWidth - 16) {
                    left = window.innerWidth - cardWidth - 16;
                }
                if (left < 16) {
                    left = 16;
                }

                if (top + cardHeight > window.innerHeight - 16) {
                    top = Math.max(16, rect.top - cardHeight - 12);
                }

                this.desktopCardStyle = `top: ${Math.round(top)}px; left: ${Math.round(left)}px;`;
            } else {
                this.desktopCardStyle = '';
            }
        },

        focusFirst() {
            const card = this.$refs.stepCard;
            if (!card) return;
            const nextBtn = this.$refs.nextButton;
            if (nextBtn) {
                nextBtn.focus();
            } else {
                card.focus();
            }
        },

        handleTab(event) {
            const card = this.$refs.stepCard;
            if (!card) return;

            const focusables = card.querySelectorAll(
                'button:not([disabled]), [href]:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            );
            if (focusables.length === 0) return;

            const first = focusables[0];
            const last = focusables[focusables.length - 1];

            if (event.shiftKey) {
                if (document.activeElement === first || !card.contains(document.activeElement)) {
                    last.focus();
                    event.preventDefault();
                }
            } else {
                if (document.activeElement === last || !card.contains(document.activeElement)) {
                    first.focus();
                    event.preventDefault();
                }
            }
        },
    };
}
