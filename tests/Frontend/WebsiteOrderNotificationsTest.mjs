import { readFile } from 'node:fs/promises';
import test from 'node:test';
import assert from 'node:assert/strict';
const source = await readFile(new URL('../../resources/js/Utils/WebsiteOrderNotifications.js', import.meta.url), 'utf8');
const { WebsiteOrderNotifications } = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);

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
