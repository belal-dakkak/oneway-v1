export function storefrontProductPrice(product, kind, currency, exchangeRate) {
  const mapped = Number(product?.prices_by_currency?.[currency]?.[kind])
  if (Number.isFinite(mapped)) return mapped

  const exactField = kind === 'wholesale'
    ? 'wholesale_price_value'
    : (kind === 'before_discount' ? 'price_before_discount_value' : 'final_price_value')
  const exactPrice = Number(product?.[exactField])
  if (Number.isFinite(exactPrice) && product?.final_price_currency === currency) {
    return exactPrice
  }

  const baseField = kind === 'wholesale'
    ? 'sale_price'
    : (kind === 'before_discount' ? 'price_before_discount' : 'retail_price')
  const basePrice = Number(product?.[baseField] || (kind === 'retail' ? product?.price : 0) || 0)
  return currency === 'USD'
    ? Number(basePrice.toFixed(2))
    : Number((basePrice * Number(exchangeRate || 1)).toFixed(currency === 'SYP' ? 0 : 2))
}

export function storefrontProductImage(product, fallback = '/api/placeholder/300/400') {
  return product?.image_url || product?.photo_url || product?.image || fallback
}

export function convertCurrencyAmount(value, fromRate, toRate, toCode) {
  const amount = Number(value || 0)
  const sourceRate = Number(fromRate || 1)
  const targetRate = Number(toRate || 1)
  const converted = (amount / sourceRate) * targetRate
  return toCode === 'SYP' ? Math.round(converted) : Number(converted.toFixed(2))
}
