export default function watchlistToggle({ url, saved = false }) {
    return {
        saved,
        busy: false,
        toast: '',
        previous: undefined,
        timer: null,
        async toggle() {
            if (this.busy) return;
            this.previous = this.saved;
            this.saved = !this.saved;
            this.toast = this.saved ? 'Added to watchlist.' : 'Removed from watchlist.';
            this.busy = true;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => { this.toast = ''; this.previous = undefined; }, 5000);
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'The request failed.');
            } catch (error) {
                this.saved = this.previous;
                this.toast = error.message === 'Failed to fetch'
                    ? 'The server could not be reached. Please check your connection and try again.'
                    : error.message;
                this.previous = undefined;
                clearTimeout(this.timer);
                this.timer = setTimeout(() => { this.toast = ''; }, 5000);
            } finally {
                this.busy = false;
            }
        },
        async undo() {
            if (this.previous === null || this.busy) return;
            const restore = this.previous;
            this.saved = restore;
            this.previous = undefined;
            this.busy = true;
            clearTimeout(this.timer);
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'The request failed.');
                this.toast = restore ? 'Added to watchlist.' : 'Removed from watchlist.';
            } catch (error) {
                this.saved = !restore;
                this.toast = error.message === 'Failed to fetch'
                    ? 'The server could not be reached. Please check your connection and try again.'
                    : error.message;
            } finally {
                this.busy = false;
                this.previous = undefined;
                this.timer = setTimeout(() => { this.toast = ''; }, 5000);
            }
        },
    };
}
