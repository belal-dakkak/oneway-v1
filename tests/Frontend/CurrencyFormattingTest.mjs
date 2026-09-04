import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import test from 'node:test'

const sourceUrl = new URL('../../resources/js/Utils/Currency.js', import.meta.url)
const source = await readFile(sourceUrl, 'utf8')
const moduleUrl = `data:text/javascript;base64,${Buffer.from(source).toString('base64')}`
const { default: Currency } = await import(moduleUrl)

test('USD and AED inputs always use two decimal places', () => {
    assert.equal(Currency.normalizeInput(12.2, 'USD'), '12.20')
    assert.equal(Currency.normalizeInput('12.2000', 'AED'), '12.20')
    assert.equal(Currency.normalizeInput(12.206, 'USD'), '12.21')
})

test('SYP is rounded to an integer and displayed with thousands separators', () => {
    assert.equal(Currency.normalizeInput(130000.4, 'SYP'), '130000')
    assert.equal(Currency.formatAmount(130000, 'SYP'), '130,000')
    assert.equal(Currency.formatFromUsd(10, 13000, 'SYP'), '130,000')
})

test('price inputs use the correct step for each currency', () => {
    assert.equal(Currency.inputStep('USD'), '0.01')
    assert.equal(Currency.inputStep('AED'), '0.01')
    assert.equal(Currency.inputStep('SYP'), '1')
})
