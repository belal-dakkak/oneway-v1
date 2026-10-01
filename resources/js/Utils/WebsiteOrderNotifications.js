// Shared across Inertia layout mounts so navigation cannot replay a ringtone.
const sessions = new Map();

export class WebsiteOrderNotifications {
    constructor({ userId, initialIds = [], load, update, ring, document, setInterval, clearInterval }) {
        Object.assign(this, { load, update, ring, document, setInterval, clearInterval });
        if (!sessions.has(userId)) sessions.set(userId, { seen: new Set(), audioEnabled: false });
        this.session = sessions.get(userId);
        initialIds.forEach(id => this.session.seen.add(id));
        this.busy = false;
        this.stopped = false;
        this.visibility = () => { if (!document.hidden) this.refresh(); };
        this.unlock = () => { this.session.audioEnabled = true; };
    }

    start() {
        this.document.addEventListener('visibilitychange', this.visibility);
        this.document.addEventListener('pointerdown', this.unlock);
        this.document.addEventListener('keydown', this.unlock);
        this.timer = this.setInterval(() => this.refresh(), 15000);
        this.refresh();
    }

    async refresh() {
        if (this.stopped || this.busy || this.document.hidden) return;
        this.busy = true;
        try {
            const snapshot = await this.load();
            if (this.stopped) return;
            const fresh = snapshot.website_order_ids.filter(id => !this.session.seen.has(id));
            snapshot.website_order_ids.forEach(id => this.session.seen.add(id));
            this.update(snapshot);
            if (fresh.length && this.session.audioEnabled && !this.document.hidden) {
                await this.ring().catch(() => {});
            }
        } catch (_) {
            // The next poll retries; do not clear the existing badge on a network error.
        } finally {
            this.busy = false;
        }
    }

    stop() {
        this.stopped = true;
        this.clearInterval(this.timer);
        this.document.removeEventListener('visibilitychange', this.visibility);
        this.document.removeEventListener('pointerdown', this.unlock);
        this.document.removeEventListener('keydown', this.unlock);
    }
}
