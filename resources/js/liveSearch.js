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
            this.$watch('query', this.debouncedSearch);
            this.$nextTick(() => {
                this.$refs.input?.focus();
            });
        },

        focusInput() {
            this.$refs.input?.focus();
        },

        debouncedSearch: (() => {
            let timer;
            return function (value) {
                clearTimeout(timer);
                if (value.length < 2) {
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
                timer = setTimeout(() => this.search(value), 300);
            };
        })(),

        async search(value) {
            try {
                const res = await fetch(`${this.endpoint}?q=${encodeURIComponent(value)}`);
                if (!res.ok) throw new Error('Request failed');
                this.results = await res.json();
                this.loading = false;
                this.error = false;
                this.open = this.results.length > 0;
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
                }
            } else if (e.key === 'Escape') {
                this.open = false;
                this.results = [];
                this.focused = -1;
            }
        },
    };
}
