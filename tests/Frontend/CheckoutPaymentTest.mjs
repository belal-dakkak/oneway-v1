import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import test from 'node:test'

const sourceUrl = new URL('../../resources/js/Utils/CheckoutPayment.js', import.meta.url)
const source = await readFile(sourceUrl, 'utf8')
const moduleUrl = `data:text/javascript;base64,${Buffer.from(source).toString('base64')}`
const { defaultPaymentMethod, reconcilePaymentMethod } = await import(moduleUrl)

test('UAE checkout defaults to card when the gateway is ready', () => {
    assert.equal(defaultPaymentMethod('AE', { card_available: true }), 'card')
    assert.equal(defaultPaymentMethod('AE', { card_available: false }), 'cod')
})

test('late commerce sync selects card without overriding a manual COD choice', () => {
    assert.equal(reconcilePaymentMethod('cod', 'AE', true, false), 'card')
    assert.equal(reconcilePaymentMethod('cod', 'AE', true, true), 'cod')
})

test('Syria and unavailable gateways always use COD', () => {
    assert.equal(reconcilePaymentMethod('card', 'SY', true, false), 'cod')
    assert.equal(reconcilePaymentMethod('card', 'AE', false, true), 'cod')
})
