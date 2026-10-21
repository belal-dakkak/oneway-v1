import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import test from 'node:test'

const sourceUrl = new URL('../../resources/js/Utils/WholesalePrice.js', import.meta.url)
const source = await readFile(sourceUrl, 'utf8')
const moduleUrl = `data:text/javascript;base64,${Buffer.from(source).toString('base64')}`
const { default: WholesalePrice } = await import(moduleUrl)

test('changing one size synchronizes only sizes from the same model', () => {
    const products = [
        { model_id: 10, size: 'S', price: '90.00' },
        { model_id: 10, size: 'M', price: '90.00' },
        { model_id: 20, size: 'S', price: '120.00' },
    ]

    products[0].price = '100.00'
    WholesalePrice.synchronize(products, products[0])

    assert.equal(products[1].price, '100.00')
    assert.equal(products[2].price, '120.00')
})

test('blank scanner rows are ignored', () => {
    const products = [{ model_id: '', price: '10' }, { model_id: '', price: '' }]
    WholesalePrice.synchronize(products, products[0])
    assert.equal(products[1].price, '')
})
