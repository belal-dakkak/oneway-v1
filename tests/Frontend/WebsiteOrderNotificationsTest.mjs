import { readFile } from 'node:fs/promises';
import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
const source = await readFile(new URL('../../resources/js/Utils/WebsiteOrderNotifications.js', import.meta.url), 'utf8');
const { WebsiteOrderNotifications, refreshWebsiteOrderSummary } = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);

test('polling counts new IDs once and suppresses initial, repeated and pre-interaction sounds', async () => {
    let ids = ['old'], rings = 0, snapshot;
    const listeners = {};
    const document = { hidden: false, addEventListener: (key, cb) => listeners[key] = cb, removeEventListener: key => delete listeners[key] };
    const sync = new WebsiteOrderNotifications({ userId: 1, initialIds: ids, document,
        load: async () => ({ website_order_ids: ids, website_order_count: ids.length }),
        update: value => snapshot = value, ring: async () => rings++, setInterval: () => 1, clearInterval: () => {} });
    sync.start();
    await new Promise(resolve => setImmediate(resolve));
    ids = ['old', 'before-gesture'];
    await sync.refresh();
    assert.equal(rings, 0);
    listeners.pointerdown();
    ids = ['old', 'before-gesture', 'new'];
    await sync.refresh();
    await sync.refresh();
    assert.equal(rings, 1);
    assert.equal(snapshot.website_order_count, 3);
    sync.stop();
    assert.equal(Object.keys(listeners).length, 0);
});

test('hidden tabs and overlapping refreshes do not send requests; unmount ignores pending responses', async () => {
    let requests = 0, updates = 0, finish;
    const document = { hidden: true, addEventListener() {}, removeEventListener() {} };
    const sync = new WebsiteOrderNotifications({ userId: 2, document,
        load: () => { requests++; return new Promise(resolve => finish = resolve); },
        update: () => updates++, ring: async () => {}, setInterval: () => 1, clearInterval: () => {} });
    await sync.refresh();
    assert.equal(requests, 0);
    document.hidden = false;
    const pending = sync.refresh();
    await sync.refresh();
    assert.equal(requests, 1);
    sync.stop();
    finish({ website_order_ids: ['new'] });
    await pending;
    assert.equal(updates, 0);
});

test('status save during a poll queues a fresh request and discards the stale count without ringing', async () => {
    let finish, requests = 0, rings = 0;
    const counts = [];
    const sync = new WebsiteOrderNotifications({ userId: 3, initialIds: ['known'],
        document: { hidden: false },
        load: () => {
            requests++;
            return requests === 1 ? new Promise(resolve => finish = resolve)
                : Promise.resolve({ website_order_ids: ['known'], website_order_count: 0 });
        },
        update: value => counts.push(value.website_order_count), ring: async () => rings++ });
    sync.unlock();
    const pending = sync.refresh();
    await sync.statusChanged();
    await sync.statusChanged();
    assert.equal(requests, 1);
    finish({ website_order_ids: ['known'], website_order_count: 3 });
    await pending;
    assert.equal(requests, 2);
    assert.deepEqual(counts, [0]);
    assert.equal(rings, 0);
});

test('badge can fall to zero and return while read notification IDs stay independent of the ringtone', async () => {
    let count = 3, ids = ['known'], rings = 0, snapshot, fail = false;
    const sync = new WebsiteOrderNotifications({ userId: 4, initialIds: ids,
        document: { hidden: false },
        load: async () => {
            if (fail) throw new Error('Offline');
            return { website_order_ids: ids, website_order_count: count };
        }, update: value => snapshot = value, ring: async () => rings++ });
    sync.unlock();
    await sync.refresh();
    ids = []; // Reading notifications does not remove pending orders.
    await sync.refresh();
    assert.equal(snapshot.website_order_count, 3);
    count = 0;
    await sync.statusChanged();
    assert.equal(snapshot.website_order_count, 0);
    count = 1;
    await sync.statusChanged();
    assert.equal(snapshot.website_order_count, 1);
    fail = true;
    await sync.refresh();
    assert.equal(snapshot.website_order_count, 1);
    assert.equal(rings, 0);
});

test('list and detail status actions refresh the live badge only after successful saves', async () => {
    const oldDocument = globalThis.document;
    const document = new EventTarget();
    document.hidden = false;
    globalThis.document = document;
    let count = 3, requests = 0, snapshot, rejectSave = false, rings = 0;
    const sync = new WebsiteOrderNotifications({ userId: 5, initialIds: ['known'], document,
        load: async () => { requests++; return { website_order_ids: ['known'], website_order_count: count }; },
        update: value => snapshot = value, ring: async () => rings++,
        setInterval: () => 1, clearInterval: () => {} });
    try {
        sync.start();
        sync.unlock();
        await new Promise(resolve => setImmediate(resolve));
        for (const [file, method] of [['WebsiteOrders.vue', 'changeStatus'], ['WebsiteOrders.vue', 'changeStatusTo'], ['View.vue', 'changeStatusTo']]) {
            const sfc = await readFile(new URL(`../../resources/js/Pages/Admin/Orders/${file}`, import.meta.url), 'utf8');
            const script = sfc.match(/<script>([\s\S]*?)<\/script>/)[1]
                .replace(/^import .+$/gm, '').replace('export default', 'module.exports =');
            const context = { module: { exports: {} }, defineComponent: value => value,
                AppLayout: {}, MeeTable: {}, Pagination: {}, JetButton: {}, Datepicker: {}, Multiselect: {}, JetSectionBorder: {},
                throttle: fn => fn, console: { error() {} }, refreshWebsiteOrderSummary,
                axios: { post: async () => {
                    if (rejectSave) throw new Error('Save rejected');
                    count--;
                    return { data: { status: 2, status_label: 'Ongoing' } };
                } } };
            vm.runInNewContext(script, context);
            const order = { id: 7, status: 1 };
            const instance = { route: () => '/status', order };
            const args = file === 'View.vue' ? [2] : [order, 2];
            const action = context.module.exports.methods[method];
            const before = requests;
            await action.apply(instance, args);
            await new Promise(resolve => setImmediate(resolve));
            assert.equal(requests, before + 1);
            assert.equal(snapshot.website_order_count, count);
            assert.equal(order.status, 2);

            rejectSave = true;
            await action.apply(instance, args);
            assert.equal(requests, before + 1);
            assert.equal(snapshot.website_order_count, count);
            rejectSave = false;
        }
        assert.equal(snapshot.website_order_count, 0);
        assert.equal(rings, 0);
        sync.stop();
        refreshWebsiteOrderSummary();
        await new Promise(resolve => setImmediate(resolve));
        assert.equal(requests, 4);
    } finally {
        sync.stop();
        if (oldDocument === undefined) delete globalThis.document;
        else globalThis.document = oldDocument;
    }
});
