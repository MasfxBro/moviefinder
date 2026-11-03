export default function offlineNotice() {
    return {
        offline: ! navigator.onLine,
        toast: false,
        timer: null,

        init() {
            const sync = () => { this.offline = ! navigator.onLine; };

            window.addEventListener('online', () => {
                this.offline = false;
                this.showToast();
            });

            window.addEventListener('offline', sync);

            window.addEventListener('app:offline', () => {
                this.offline = true;
            });

            window.addEventListener('app:online', sync);

            sync();
        },

        showToast() {
            this.toast = true;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => { this.toast = false; }, 4000);
        },
    };
}
