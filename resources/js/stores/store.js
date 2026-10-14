import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'
import { dictionary } from '../data/dictionary'
import axios from 'axios'
import { Inertia } from '@inertiajs/inertia'
import { convertCurrencyAmount, storefrontProductPrice } from '../Utils/StorefrontPricing'

export const useStore = defineStore('main', () => {
  // State
  const initialCountry = localStorage.getItem('country') || 'AE'
  const cartKey = (countryCode, merchant) => `cart_${countryCode}_${merchant ? 'wholesale' : 'retail'}`
  const currencyKey = (countryCode, merchant) => `currency_${countryCode}_${merchant ? 'wholesale' : 'retail'}`
  const initialCart = localStorage.getItem(cartKey(initialCountry, false))
    || localStorage.getItem(`cart_${initialCountry}`)
    || '[]'
  const cart = ref(JSON.parse(initialCart))
  const favorites = ref(JSON.parse(localStorage.getItem('favorites') || '[]'))
  const isRTL = ref(localStorage.getItem('isRTL') === 'true')
  const locale = ref(localStorage.getItem('locale') || 'en')
  const currency = ref(localStorage.getItem(`currency_${initialCountry}`) || 'AED')
  const currencyOptions = ref([])
  const commerce = ref({ shipping_fee_usd: 0, free_shipping_threshold_usd: null, cod_fee_percent: 0, card_available: false })
  const transactionCurrency = ref('USD')
  const selectedProduct = ref(null)
  const isProductModalOpen = ref(false)
  const isMerchant = ref(false)
  const country = ref(initialCountry)

  const exchangeRate = computed(() => {
    const option = currencyOptions.value.find(item => item.code === currency.value)
    return Number(option?.rate || 1)
  })
  const currencyDisplayOnly = computed(() => Boolean(
    currencyOptions.value.find(item => item.code === currency.value)?.display_only
  ))

  // Watchers for persistence
  watch(currency, (val) => {
    localStorage.setItem(currencyKey(country.value, isMerchant.value), val)
  })

  watch(cart, (newCart) => {
    localStorage.setItem(cartKey(country.value, isMerchant.value), JSON.stringify(newCart))
  }, { deep: true })

  watch(favorites, (newFavorites) => {
    localStorage.setItem('favorites', JSON.stringify(newFavorites))
  }, { deep: true })

  watch(isRTL, (val) => {
    localStorage.setItem('isRTL', val.toString())
  })

  watch(locale, (val) => {
    localStorage.setItem('locale', val)
  })

  watch(country, (val) => {
    localStorage.setItem('country', val)
  })

  // Getters
  const getItemPrice = (item) => {
    return productPrice(item.product, isMerchant.value ? 'wholesale' : 'retail')
  }

  const productPrice = (product, kind = 'retail') => {
    return storefrontProductPrice(product, kind, currency.value, exchangeRate.value)
  }

  const getCartTotal = computed(() => {
    return cart.value.reduce((total, item) => {
      return total + (Number(getItemPrice(item)) * item.quantity)
    }, 0)
  })

  const getCartCount = computed(() => {
    return cart.value.reduce((count, item) => count + item.quantity, 0)
  })

  // Actions
  const addToCart = (product, color, size, quantity = 1, stock = null) => {
    const colorId = color?.id || 'default'
    const sizeVal = size?.size || size || 'default'
    const maxStock = stock !== null ? stock : Infinity

    const existingItem = cart.value.find(
      item =>
        item.product.id === product.id &&
        item.color.id === colorId &&
        item.size === sizeVal
    )

    if (existingItem) {
      const newQty = existingItem.quantity + quantity
      const itemStock = existingItem.stock !== undefined ? existingItem.stock : maxStock
      if (newQty > itemStock) {
        return { success: false, maxStock: itemStock }
      }
      existingItem.quantity = newQty
    } else {
      if (quantity > maxStock) {
        return { success: false, maxStock }
      }
      const newItem = {
        id: `${product.id}-${colorId}-${sizeVal}`,
        product: {
          id: product.id,
          name: product.name,
          image: color?.photo_url || product.photo_url || product.image,
          retail_price: product.retail_price,
          sale_price: product.sale_price,
          price: product.price,
          final_price_value: product.final_price_value,
          final_price_currency: product.final_price_currency,
          final_price: product.final_price,
          price_before_discount: product.price_before_discount,
          price_before_discount_value: product.price_before_discount_value,
          wholesale_price_value: product.wholesale_price_value,
          prices_by_currency: product.prices_by_currency || {}
        },
        color: {
          id: colorId,
          name: color?.color_name || 'Generic'
        },
        size: sizeVal,
        quantity,
        stock: maxStock
      }
      cart.value.push(newItem)
    }
    return { success: true }
  }

  const removeFromCart = (itemId) => {
    const index = cart.value.findIndex(item => item.id === itemId)
    if (index > -1) {
      cart.value.splice(index, 1)
    }
  }

  const updateQuantity = (itemId, quantity) => {
    if (quantity <= 0) {
      removeFromCart(itemId)
    } else {
      const item = cart.value.find(item => item.id === itemId)
      if (item) {
        // Respect stock limit if stored
        const maxStock = item.stock !== undefined ? item.stock : Infinity
        item.quantity = Math.min(quantity, maxStock)
        return { success: item.quantity === quantity, maxStock }
      }
    }
    return { success: true }
  }

  const clearCart = () => {
    cart.value = []
  }

  const toggleRTL = () => {
    isRTL.value = !isRTL.value
    locale.value = isRTL.value ? 'ar' : 'en'
    document.documentElement.dir = isRTL.value ? 'rtl' : 'ltr'
    document.documentElement.lang = locale.value
  }

  const toggleCurrency = () => {
    if (currencyOptions.value.length < 2) return
    const index = currencyOptions.value.findIndex(item => item.code === currency.value)
    currency.value = currencyOptions.value[(index + 1) % currencyOptions.value.length].code
  }

  const formatPrice = (value) => {
    const decimals = currency.value === 'SYP' ? 0 : 2
    const amount = value === undefined || value === null || isNaN(Number(value)) ? 0 : Number(value)
    const minimumDecimals = decimals > 0 && !Number.isInteger(amount) ? decimals : 0
    return amount.toLocaleString(undefined, { minimumFractionDigits: minimumDecimals, maximumFractionDigits: decimals }) + ` ${currency.value}`
  }

  const convertFromUsd = (value) => {
    const converted = Number(value || 0) * exchangeRate.value
    return currency.value === 'SYP' ? Math.round(converted) : Number(converted.toFixed(2))
  }

  const rateFor = (code) => Number(currencyOptions.value.find(item => item.code === code)?.rate || 1)

  const convertAmount = (value, fromCode, toCode = currency.value) => {
    const from = String(fromCode || 'USD').toUpperCase()
    const to = String(toCode || currency.value).toUpperCase()
    if (from === to) return Number(value || 0)
    return convertCurrencyAmount(value, rateFor(from), rateFor(to), to)
  }

  const t = (key) => {
    return dictionary[locale.value][key] || key
  }

  const toggleFavorite = async (productId) => {
    const index = favorites.value.indexOf(productId)
    const isAdding = index === -1

    // Optimistic update
    if (!isAdding) {
      favorites.value.splice(index, 1)
    } else {
      favorites.value.push(productId)
    }

    try {
      const response = await axios.post('/favorites/toggle', { product_id: productId })
      // Sync with server response if needed
      const serverAdded = response.data.added
      const localIndex = favorites.value.indexOf(productId)

      if (serverAdded && localIndex === -1) {
        favorites.value.push(productId)
      } else if (!serverAdded && localIndex > -1) {
        favorites.value.splice(localIndex, 1)
      }
    } catch (error) {
      console.error('Failed to toggle favorite:', error)
      // Revert optimistic update on error if it's 401
      if (error.response && error.response.status === 401) {
        // Redirect to login or just show error
        window.location.href = route('login')
      }
    }
  }

  const setFavorites = (favoriteIds) => {
    favorites.value = favoriteIds
  }

  const getFavoritesCount = computed(() => favorites.value.length)

  const isFavorite = (productId) => {
    return favorites.value.includes(productId)
  }

  const openProductModal = (product) => {
    selectedProduct.value = product
    isProductModalOpen.value = true
  }

  const closeProductModal = () => {
    selectedProduct.value = null
    isProductModalOpen.value = false
  }

  const verifyMerchantCode = async (code) => {
    try {
      const response = await axios.post('/merchant/verify', { code })
      if (response.data.success) {
        isMerchant.value = true
        // Refresh page to apply session changes to backend results
        window.location.reload()
        return { success: true, message: response.data.message }
      }
    } catch (error) {
      console.error('Failed to verify merchant code:', error)
      return {
        success: false,
        message: error.response?.data?.message || 'Invalid code'
      }
    }
  }

  const disableMerchantMode = async () => {
    try {
      await axios.post('/merchant/disable')
      isMerchant.value = false
      window.location.reload()
    } catch (error) {
      console.error('Failed to disable merchant mode:', error)
    }
  }

  const switchCountry = (countryCode) => {
    Inertia.post(route('country.set'), { country: countryCode }, {
      onSuccess: () => {
        country.value = countryCode
        localStorage.setItem('country', countryCode)
        window.location.reload()
      },
      onError: (errors) => {
        console.error('Failed to switch country:', errors)
      }
    })
  }

  const syncContext = (countryCode, options = [], defaultCurrency = 'USD', commerceSettings = null, officialCurrency = null) => {
    country.value = countryCode
    currencyOptions.value = Array.isArray(options) ? options : []
    if (commerceSettings) commerce.value = commerceSettings
    transactionCurrency.value = officialCurrency || defaultCurrency
    const scopedCartKey = cartKey(countryCode, isMerchant.value)
    const legacyCart = !isMerchant.value ? localStorage.getItem(`cart_${countryCode}`) : null
    cart.value = JSON.parse(localStorage.getItem(scopedCartKey) || legacyCart || '[]')
    localStorage.setItem(scopedCartKey, JSON.stringify(cart.value))
    const savedCurrency = localStorage.getItem(currencyKey(countryCode, isMerchant.value))
    const allowedCodes = currencyOptions.value.map(item => item.code)
    currency.value = allowedCodes.includes(savedCurrency)
      ? savedCurrency
      : (allowedCodes.includes(defaultCurrency) ? defaultCurrency : (allowedCodes[0] || 'USD'))
  }

  // Initialize RTL/Locale on load if necessary
  if (typeof document !== 'undefined') {
    document.documentElement.dir = isRTL.value ? 'rtl' : 'ltr'
    document.documentElement.lang = locale.value
  }

  return {
    // State
    cart,
    favorites,
    isRTL,
    locale,
    currency,
    exchangeRate,
    currencyOptions,
    currencyDisplayOnly,
    commerce,
    transactionCurrency,
    selectedProduct,
    isProductModalOpen,
    isMerchant,

    // Getters
    getCartTotal,
    getCartCount,
    getItemPrice,
    productPrice,

    // Actions
    addToCart,
    removeFromCart,
    updateQuantity,
    clearCart,
    toggleRTL,
    toggleCurrency,
    formatPrice,
    convertFromUsd,
    convertAmount,
    rateFor,
    t,
    toggleFavorite,
    isFavorite,
    setFavorites,
    getFavoritesCount,
    openProductModal,
    closeProductModal,
    verifyMerchantCode,
    disableMerchantMode,
    country,
    switchCountry,
    syncContext
  }
})
