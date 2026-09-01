import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'
import { dictionary } from '../data/dictionary'
import axios from 'axios'
import { Inertia } from '@inertiajs/inertia'

export const useStore = defineStore('main', () => {
  // State
  const cart = ref(JSON.parse(localStorage.getItem('cart') || '[]'))
  const favorites = ref(JSON.parse(localStorage.getItem('favorites') || '[]'))
  const isRTL = ref(localStorage.getItem('isRTL') === 'true')
  const locale = ref(localStorage.getItem('locale') || 'en')
  const currency = ref(localStorage.getItem('currency') || 'AED')
  const exchangeRate = 3.67 // 1 USD = 3.67 AED
  const selectedProduct = ref(null)
  const isProductModalOpen = ref(false)
  const isMerchant = ref(false)
  const country = ref(localStorage.getItem('country') || 'AE')

  // Watchers for persistence
  watch(currency, (val) => {
    localStorage.setItem('currency', val)
  })

  watch(cart, (newCart) => {
    localStorage.setItem('cart', JSON.stringify(newCart))
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
    if (currency.value === 'USD') {
      return item.product.price || item.product.retail_price || 0
    } else {
      return item.product.final_price_value || (item.product.price || item.product.retail_price || 0) * exchangeRate
    }
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
          final_price: product.final_price
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
    if (country.value === 'LB' || country.value === 'SY') return
    currency.value = currency.value === 'AED' ? 'USD' : 'AED'
  }

  const formatPrice = (value) => {
    if (value === undefined || value === null || isNaN(Number(value))) {
      return '0.00' + (currency.value === 'USD' ? ' USD' : ' AED')
    }
    return Number(value).toFixed(2) + (currency.value === 'USD' ? ' USD' : ' AED')
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
        if (countryCode === 'LB' || countryCode === 'SY') {
          currency.value = 'USD'
          localStorage.setItem('currency', 'USD')
        }
        // Reload the page to fetch new data based on the new country
        window.location.reload()
      },
      onError: (errors) => {
        console.error('Failed to switch country:', errors)
      }
    })
  }

  // Initialize RTL/Locale on load if necessary
  if (typeof document !== 'undefined') {
    document.documentElement.dir = isRTL.value ? 'rtl' : 'ltr'
    document.documentElement.lang = locale.value
    // Fix: Force USD if country is LB or SY on load
    if (country.value === 'LB' || country.value === 'SY') {
      currency.value = 'USD'
      localStorage.setItem('currency', 'USD')
    }
  }

  return {
    // State
    cart,
    favorites,
    isRTL,
    locale,
    currency,
    exchangeRate,
    selectedProduct,
    isProductModalOpen,
    isMerchant,

    // Getters
    getCartTotal,
    getCartCount,
    getItemPrice,

    // Actions
    addToCart,
    removeFromCart,
    updateQuantity,
    clearCart,
    toggleRTL,
    toggleCurrency,
    formatPrice,
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
    switchCountry
  }
})