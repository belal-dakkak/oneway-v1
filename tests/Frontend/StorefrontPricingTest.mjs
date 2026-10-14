import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import test from 'node:test'

const sourceUrl = new URL('../../resources/js/Utils/StorefrontPricing.js', import.meta.url)
const source = await readFile(sourceUrl, 'utf8')
const moduleUrl = `data:text/javascript;base64,${Buffer.from(source).toString('base64')}`
const { convertCurrencyAmount, storefrontProductPrice } = await import(moduleUrl)

const product = {
  final_price_currency: 'AED',
  final_price_value: 89,
  retail_price: 24.2177,
  sale_price: 12.2449,
  prices_by_currency: {
    AED: { retail: 89, wholesale: 45, before_discount: 100 },
    USD: { retail: 24.22, wholesale: 12.24, before_discount: 27.21 },
  },
}

test('storefront product prices switch values and currencies together', () => {
  assert.equal(storefrontProductPrice(product, 'retail', 'AED', 3.675), 89)
  assert.equal(storefrontProductPrice(product, 'retail', 'USD', 1), 24.22)
  assert.equal(storefrontProductPrice(product, 'wholesale', 'USD', 1), 12.24)
})

test('official AED checkout totals can be shown as approximate USD', () => {
  assert.equal(convertCurrencyAmount(89, 3.675, 1, 'USD'), 24.22)
  assert.equal(convertCurrencyAmount(24.22, 1, 3.675, 'AED'), 89.01)
})

test('legacy cart rows fall back to their base USD price', () => {
  assert.equal(storefrontProductPrice({ retail_price: 10 }, 'retail', 'USD', 1), 10)
  assert.equal(storefrontProductPrice({ retail_price: 10 }, 'retail', 'AED', 3.675), 36.75)
})
