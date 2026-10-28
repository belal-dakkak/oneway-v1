import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import test from 'node:test'

const sourceUrl = new URL('../../resources/js/Utils/StorefrontPricing.js', import.meta.url)
const source = await readFile(sourceUrl, 'utf8')
const productCardSource = await readFile(
  new URL('../../resources/js/Components/Website/ProductCard.vue', import.meta.url),
  'utf8',
)
const moduleUrl = `data:text/javascript;base64,${Buffer.from(source).toString('base64')}`
const { storefrontProductImage } = await import(moduleUrl)

test('the selected cover image wins over the legacy product photo', () => {
  assert.equal(storefrontProductImage({
    image_url: '/storage/products/colors/selected.webp',
    photo_url: '/storage/products/old.webp',
    image: 'products/raw.webp',
  }), '/storage/products/colors/selected.webp')
})

test('product images fall back in a stable order', () => {
  assert.equal(storefrontProductImage({ photo_url: '/storage/products/photo.webp' }), '/storage/products/photo.webp')
  assert.equal(storefrontProductImage({ image: 'products/raw.webp' }), 'products/raw.webp')
  assert.equal(storefrontProductImage({}), '/api/placeholder/300/400')
})

test('available colors do not override the selected cover on the product card', () => {
  const product = {
    image_url: '/storage/products/colors/selected-cover.webp',
    colors: [
      { stock: 5, photo_url: '/storage/products/colors/first-in-stock.webp' },
      { stock: 3, photo_url: '/storage/products/colors/second-in-stock.webp' },
    ],
  }

  assert.equal(
    storefrontProductImage(product),
    '/storage/products/colors/selected-cover.webp',
  )
  assert.match(productCardSource, /:src="cardImage"/)
  assert.match(
    productCardSource,
    /const cardImage = computed\(\(\) => storefrontProductImage\(props\.product\)\)/,
  )
})
