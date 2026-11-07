export default function aiChat(options = {}) {
    return {
        variant: options.variant || 'panel',
        titleId: options.titleId || null,
        title: options.title || '',
        isOpen: false,
        conversationId: null,
        hasMessages: false,
        messages: [],
        input: '',
        loading: false,
        progressStep: 'Understanding your request…',
        progressTimer: null,
        timeoutTimer: null,
        abortController: null,
        lastSubmittedMessage: '',
        quota: {
            plan: 'free',
            units_left: 5,
            units_limit: 5,
            smart_left: 0,
            smart_limit: 0,
            pro_limit: 12,
            place_held: false,
            places_full: false,
            next_wave_at: null,
            daily_limit_used: false,
            daily_reset_at: null,
            allowance_used: 0,
            allowance_limit: 0,
            renews_at: null,
            budget: null,
        },
        statusMessage: null,
        showUpgradeCard: false,
        upgradeVariant: 'default',
        isProviderUnavailable: false,
        exitModalOpen: false,
        pendingAction: null,
        _linkClickHandler: null,
        _beforeUnloadHandler: null,

        suggestedPrompts: [
            'Action movie with a bus jump in the 1990s',
            'Sci-fi series about time travel in Germany',
            'Indonesian horror movie set in an old house',
        ],

        dataNotice: "Your messages are processed by Google's Gemini to find titles and clips. Please do not share personal information.",

        init() {
            if (this.variant === 'embedded') {
                this.isOpen = true;
                this.fetchStatus();
            }

            this._linkClickHandler = (e) => this.handleLinkClick(e);
            document.addEventListener('click', this._linkClickHandler);

            this._beforeUnloadHandler = (e) => {
                if (this.hasMessages) {
                    e.preventDefault();
                    e.returnValue = '';
                    return '';
                }
            };
            window.addEventListener('beforeunload', this._beforeUnloadHandler);
        },

        destroy() {
            if (this._linkClickHandler) {
                document.removeEventListener('click', this._linkClickHandler);
            }
            if (this._beforeUnloadHandler) {
                window.removeEventListener('beforeunload', this._beforeUnloadHandler);
            }
            this.clearTimers();
        },

        open() {
            this.isOpen = true;
            this.fetchStatus();
            this.$nextTick(() => this.focusInput());
        },

        close() {
            if (this.hasMessages) {
                this.pendingAction = () => {
                    this.resetChat();
                    this.isOpen = false;
                };
                this.exitModalOpen = true;
                return;
            }
            this.isOpen = false;
        },

        toggle() {
            if (this.isOpen) {
                this.close();
            } else {
                this.open();
            }
        },

        requestClose() {
            this.close();
        },

        confirmLeave() {
            this.exitModalOpen = false;
            this.hasMessages = false;
            const action = this.pendingAction;
            this.pendingAction = null;
            if (typeof action === 'function') {
                action();
            } else if (typeof action === 'string') {
                window.location.href = action;
            } else {
                this.resetChat();
                this.isOpen = false;
            }
        },

        cancelLeave() {
            this.exitModalOpen = false;
            this.pendingAction = null;
        },

        handleLinkClick(e) {
            if (!this.hasMessages) return;
            const link = e.target.closest('a');
            if (!link || !link.href) return;
            if (link.target === '_blank' || link.hasAttribute('download')) return;

            try {
                const url = new URL(link.href, window.location.origin);
                if (url.origin !== window.location.origin) return;
                if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) return;
            } catch {
                return;
            }

            e.preventDefault();
            this.pendingAction = link.href;
            this.exitModalOpen = true;
        },

        resetChat() {
            this.conversationId = null;
            this.hasMessages = false;
            this.messages = [];
            this.input = '';
            this.loading = false;
            this.clearTimers();
        },

        clearTimers() {
            if (this.progressTimer) {
                clearInterval(this.progressTimer);
                this.progressTimer = null;
            }
            if (this.timeoutTimer) {
                clearTimeout(this.timeoutTimer);
                this.timeoutTimer = null;
            }
        },

        get isPro() {
            return this.quota.plan === 'pro';
        },

        get quotaCounterText() {
            const left = this.quota.units_left ?? 0;
            const limit = this.quota.units_limit ?? 5;
            return `${left} of ${limit} AI searches left today`;
        },

        get smartCounterText() {
            const smartLeft = this.quota.smart_left ?? 0;
            return `${smartLeft} Smart searches left today`;
        },

        formatWaveTime(iso) {
            if (!iso) return '00:00';
            try {
                const date = new Date(iso);
                return date.toLocaleTimeString('en-GB', {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false,
                    timeZone: 'Asia/Jakarta',
                });
            } catch {
                return '00:00';
            }
        },

        formatRenewalDate(iso) {
            if (!iso) return 'the next period';
            try {
                const date = new Date(iso);
                return date.toLocaleDateString('en-GB', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                    timeZone: 'Asia/Jakarta',
                });
            } catch {
                return 'the next period';
            }
        },

        calculateHoursUntilReset(resetIso) {
            if (!resetIso) return 24;
            try {
                const resetTime = new Date(resetIso).getTime();
                const now = Date.now();
                const diffMs = resetTime - now;
                return Math.max(1, Math.ceil(diffMs / (1000 * 60 * 60)));
            } catch {
                return 24;
            }
        },

        async fetchStatus() {
            try {
                const response = await fetch('/ai/status', {
                    headers: { Accept: 'application/json' },
                });
                if (!response.ok) {
                    throw new Error('Status request failed');
                }
                const data = await response.json();
                if (data.quota) {
                    this.quota = { ...this.quota, ...data.quota };
                }
                this.isProviderUnavailable = false;
                this.evaluateStatusLine();
            } catch {
                this.isProviderUnavailable = true;
                this.statusMessage = 'AI search is temporarily unavailable. Normal search still works. Please try again in a few minutes.';
                this.showUpgradeCard = false;
            }
        },

        evaluateStatusLine() {
            this.statusMessage = null;
            this.showUpgradeCard = false;

            const q = this.quota;
            const plan = q.plan || 'free';

            if (plan === 'free' && q.places_full && !q.place_held) {
                const waveTime = this.formatWaveTime(q.next_wave_at);
                this.statusMessage = `Today's free AI places are full. The next free places open at ${waveTime} WIB.`;
                this.showUpgradeCard = true;
                this.upgradeVariant = 'default';
                return;
            }

            const dailyUsed = q.daily_limit_used || (q.units_left !== undefined && q.units_left <= 0);
            if (dailyUsed) {
                const hours = this.calculateHoursUntilReset(q.daily_reset_at);
                if (plan === 'free') {
                    this.statusMessage = `You have used today's AI searches. They reset at 00:00 WIB (in about ${hours} hours).`;
                    this.showUpgradeCard = true;
                    this.upgradeVariant = 'default';
                } else if (plan === 'plus') {
                    const proLimit = q.pro_limit || 12;
                    this.statusMessage = `You have used today's AI searches. They reset at 00:00 WIB (in about ${hours} hours). Pro offers ${proLimit} a day, including Smart searches.`;
                    this.showUpgradeCard = true;
                    this.upgradeVariant = 'default';
                } else if (plan === 'pro') {
                    this.statusMessage = `You have reached today's limit. It resets at 00:00 WIB (in about ${hours} hours).`;
                    this.showUpgradeCard = false;
                }
                return;
            }

            const allowanceUsed = plan !== 'free' && (q.budget?.allowed === false || (q.allowance_limit > 0 && q.allowance_used >= q.allowance_limit));
            if (allowanceUsed) {
                const renewalDate = this.formatRenewalDate(q.renews_at);
                this.statusMessage = `You have used this period's AI allowance. It renews on ${renewalDate}.`;
                if (plan === 'plus') {
                    this.showUpgradeCard = true;
                    this.upgradeVariant = 'default';
                } else {
                    this.showUpgradeCard = false;
                }
                return;
            }
        },

        usePrompt(promptText) {
            this.input = promptText;
            this.sendMessage();
        },

        focusInput() {
            this.$refs.chatInput?.focus();
        },

        scrollToBottom() {
            const container = this.$refs.messagesContainer;
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        },

        startProgressSteps() {
            const steps = [
                'Understanding your request…',
                'Checking titles…',
                'Finding clips…',
            ];
            let index = 0;
            this.progressStep = steps[0];
            this.progressTimer = setInterval(() => {
                index = (index + 1) % steps.length;
                this.progressStep = steps[index];
            }, 2500);
        },

        cancelRequest() {
            if (this.abortController) {
                this.abortController.abort();
            }
            this.clearTimers();
            this.loading = false;
            this.focusInput();
        },

        handleTimeout() {
            if (this.abortController) {
                this.abortController.abort();
            }
            this.clearTimers();
            this.loading = false;
            this.messages.push({
                role: 'assistant',
                content: 'The request timed out. Please try again.',
                retryable: true,
                failedMessage: this.lastSubmittedMessage,
            });
            this.$nextTick(() => {
                this.scrollToBottom();
                this.focusInput();
            });
        },

        retryLastMessage() {
            if (this.lastSubmittedMessage) {
                this.input = this.lastSubmittedMessage;
                this.sendMessage();
            }
        },

        async sendMessage() {
            const text = this.input.trim();
            if (!text || this.loading) return;

            this.messages.push({ role: 'user', content: text });
            this.lastSubmittedMessage = text;
            this.hasMessages = true;
            this.input = '';
            this.loading = true;

            this.$nextTick(() => this.scrollToBottom());

            this.startProgressSteps();

            this.abortController = new AbortController();
            this.timeoutTimer = setTimeout(() => this.handleTimeout(), 30000);

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const response = await fetch('/ai/messages', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        message: text,
                        conversation_id: this.conversationId,
                        mode: this.variant === 'embedded' ? 'title' : 'global',
                        ...(this.variant === 'embedded' ? { title_id: this.titleId } : {}),
                    }),
                    signal: this.abortController.signal,
                });

                this.clearTimers();
                this.loading = false;

                if (!response.ok) {
                    throw new Error('Server returned error status');
                }

                const data = await response.json();
                const payload = data.data ?? data;

                if (payload.conversation_id) {
                    this.conversationId = payload.conversation_id;
                }

                if (payload.quota) {
                    this.quota = { ...this.quota, ...payload.quota };
                    this.evaluateStatusLine();
                }

                this.messages.push({
                    role: 'assistant',
                    content: payload.reply || '',
                    titles: payload.titles || [],
                    clips: payload.clips || [],
                    fallback_links: payload.fallback_links || [],
                    showClipTeaser: payload.clips && payload.clips.length === 0 && this.quota.plan === 'free',
                });

                this.$nextTick(() => {
                    this.scrollToBottom();
                    this.focusInput();
                });
            } catch (err) {
                if (err.name === 'AbortError') {
                    return;
                }
                this.clearTimers();
                this.loading = false;

                this.messages.push({
                    role: 'assistant',
                    content: 'The server could not be reached. Please check your connection and try again.',
                    retryable: true,
                    failedMessage: text,
                });

                this.$nextTick(() => {
                    this.scrollToBottom();
                    this.focusInput();
                });
            }
        },
    };
}
