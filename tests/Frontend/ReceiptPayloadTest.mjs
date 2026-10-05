import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import test from 'node:test'

const sourceUrl = new URL('../../resources/js/Utils/Receipt.js', import.meta.url)
const source = await readFile(sourceUrl, 'utf8')
const moduleUrl = `data:text/javascript;base64,${Buffer.from(source).toString('base64')}`
const { default: Receipt } = await import(moduleUrl)

test('receipt payload selects compact layout from the real product count', () => {
    for (const count of [1, 2, 3]) {
        const products = Array.from({ length: count }, (_, index) => ({
            name: `Product ${index + 1}`,
            qty: 1,
            item_price: 100,
        }))
        const payload = Receipt.orderPayload({ id: count, products })

        assert.equal(payload.products_count, count)
        assert.equal(payload.compact_layout, count <= 2)
        assert.deepEqual(payload.products, products)
    }
})

test('receipt payload normalization does not mutate its input', () => {
    const order = {
        id: 10,
        product_name: 'Original',
        products_count: 99,
        compact_layout: false,
        products: [{ name: 'Only product', qty: 1 }],
    }
    const snapshot = structuredClone(order)
    const payload = Receipt.orderPayload(order)

    assert.deepEqual(order, snapshot)
    assert.notStrictEqual(payload, order)
    assert.notStrictEqual(payload.products, order.products)
    assert.equal(payload.products_count, 1)
    assert.equal(payload.compact_layout, true)
})

test('receipt payload rejects malformed product collections', () => {
    assert.throws(() => Receipt.orderPayload(null), /must be an object/)
    assert.throws(() => Receipt.orderPayload({ products: null }), /must be an array/)
    assert.throws(() => Receipt.orderPayload({ products: {} }), /must be an array/)
})
