// Shared across Inertia layout mounts so navigation cannot replay a ringtone.
const sessions = new Map();
const statusChangedEvent = 'website-order-status-changed';

export function refreshWebsiteOrderSummary() {
    document.dispatchEvent(new Event(statusChangedEvent));
}

export class WebsiteOrderNotifications {
    constructor({ userId, initialIds = [], load, update, ring, document, setInterval, clearInterval }) {
        Object.assign(this, { load, update, ring, document, setInterval, clearInterval });
        if (!sessions.has(userId)) sessions.set(userId, { seen: new Set(), audioEnabled: false });
        this.session = sessions.get(userId);
        initialIds.forEach(id => this.session.seen.add(id));
        this.busy = false;
        this.refreshQueued = false;
        this.stopped = false;
        this.visibility = () => { if (!document.hidden) this.refresh(); };
        this.statusChanged = () => this.refresh({ ensureFresh: true });
        this.unlock = () => { this.session.audioEnabled = true; };
    }

    start() {
        this.document.addEventListener('visibilitychange', this.visibility);
        this.document.addEventListener('pointerdown', this.unlock);
        this.document.addEventListener('keydown', this.unlock);
        this.document.addEventListener(statusChangedEvent, this.statusChanged);
        this.timer = this.setInterval(() => this.refresh(), 15000);
        this.refresh();
    }

    async refresh({ ensureFresh = false } = {}) {
        if (this.stopped || this.document.hidden) return;
        if (this.busy) {
            // A response started before the status save may contain the old count.
            if (ensureFresh) this.refreshQueued = true;
            return;
        }
        this.busy = true;
        try {
            const snapshot = await this.load();
            if (this.stopped || this.refreshQueued) return;
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
            if (this.refreshQueued) {
                this.refreshQueued = false;
                await this.refresh();
            }
        }
    }

    stop() {
        this.stopped = true;
        this.clearInterval(this.timer);
        this.document.removeEventListener('visibilitychange', this.visibility);
        this.document.removeEventListener('pointerdown', this.unlock);
        this.document.removeEventListener('keydown', this.unlock);
        this.document.removeEventListener(statusChangedEvent, this.statusChanged);
    }
}
