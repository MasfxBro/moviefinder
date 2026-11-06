export default function liveSearch() {
    return {
        query: '',
        results: [],
        open: false,
        loading: false,
        error: false,
        focused: -1,
        endpoint: '/search',

        init() {
            this.$watch('query', (value) => this.debouncedSearch(value));
        },

        shortcut(event) {
            const target = event.target;
            const isTyping = target instanceof HTMLElement
                && (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName));

            if (event.key === '/' && ! isTyping && this.$el.offsetParent !== null) {
                event.preventDefault();
                this.$refs.input?.focus();
            }
        },

        focusInput() {
            this.$refs.input?.focus();
        },

        debounceTimer: null,

        debouncedSearch(value) {
            clearTimeout(this.debounceTimer);
            if (value.trim().length < 2) {
                this.results = [];
                this.open = false;
                this.loading = false;
                this.error = false;
                return;
            }
            this.loading = true;
            this.error = false;
            this.open = true;
            this.focused = -1;
            this.debounceTimer = setTimeout(() => this.search(value.trim()), 300);
        },

        async search(value) {
            try {
                const res = await fetch(`${this.endpoint}?q=${encodeURIComponent(value)}`, {
                    headers: { Accept: 'application/json' },
                });
                if (!res.ok) throw new Error('Request failed');
                const payload = await res.json();
                this.results = Array.isArray(payload)
                    ? payload
                    : (Array.isArray(payload.data) ? payload.data : (payload.data?.data ?? []));
                this.loading = false;
                this.error = false;
                this.open = true;
            } catch {
                this.loading = false;
                this.error = true;
                this.open = true;
            }
        },

        select(result) {
            this.query = result.title;
            this.results = [];
            this.open = false;
            this.loading = false;
            this.error = false;
            this.focused = -1;
            this.$nextTick(() => {
                window.location.href = `/titles/${result.slug}`;
            });
        },

        highlight(term, text) {
            if (!term) return text;
            const re = new RegExp(`(${this.escapeRegExp(term)})`, 'gi');
            return text.replace(re, '<mark>$1</mark>');
        },

        escapeRegExp(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        },

        keydown(e) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.focused = Math.min(this.focused + 1, this.results.length - 1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.focused = Math.max(this.focused - 1, -1);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (this.focused >= 0 && this.results[this.focused]) {
                    this.select(this.results[this.focused]);
                } else if (this.query.trim().length > 0) {
                    window.location.href = `/search?q=${encodeURIComponent(this.query.trim())}`;
                }
            } else if (e.key === 'Escape') {
                this.open = false;
                this.results = [];
                this.focused = -1;
            }
        },
    };
}
