import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Product, ProductColor, ProductSize, CartItem } from '@/types/store'

export const useStore = defineStore('main', () => {
  // State
  const cart = ref<CartItem[]>([])
  const favorites = ref<string[]>([])
  const isRTL = ref(false)
  const selectedProduct = ref<Product | null>(null)
  const isProductModalOpen = ref(false)

  // Getters
  const getCartTotal = computed(() => {
    return cart.value.reduce((total, item) => {
      const price = item.product.discountedPrice || item.product.originalPrice
      return total + (price * item.quantity)
    }, 0)
  })

  const getCartCount = computed(() => {
    return cart.value.reduce((count, item) => count + item.quantity, 0)
  })

  // Actions
  const addToCart = (product: Product, color: ProductColor, size: ProductSize, quantity: number = 1) => {
    const existingItem = cart.value.find(
      item => 
        item.product.id === product.id && 
        item.color.id === color.id && 
        item.size.id === size.id
    )

    if (existingItem) {
      existingItem.quantity += quantity
    } else {
      const newItem: CartItem = {
        id: `${product.id}-${color.id}-${size.id}`,
        product,
        color,
        size,
        quantity
      }
      cart.value.push(newItem)
    }
  }

  const removeFromCart = (itemId: string) => {
    const index = cart.value.findIndex(item => item.id === itemId)
    if (index > -1) {
      cart.value.splice(index, 1)
    }
  }

  const updateQuantity = (itemId: string, quantity: number) => {
    if (quantity <= 0) {
      removeFromCart(itemId)
    } else {
      const item = cart.value.find(item => item.id === itemId)
      if (item) {
        item.quantity = quantity
      }
    }
  }

  const clearCart = () => {
    cart.value = []
  }

  const toggleRTL = () => {
    isRTL.value = !isRTL.value
  }

  const toggleFavorite = (productId: string) => {
    const index = favorites.value.indexOf(productId)
    if (index > -1) {
      favorites.value.splice(index, 1)
    } else {
      favorites.value.push(productId)
    }
  }

  const isFavorite = (productId: string) => {
    return favorites.value.includes(productId)
  }

  const openProductModal = (product: Product) => {
    selectedProduct.value = product
    isProductModalOpen.value = true
  }

  const closeProductModal = () => {
    selectedProduct.value = null
    isProductModalOpen.value = false
  }

  return {
    // State
    cart,
    favorites,
    isRTL,
    selectedProduct,
    isProductModalOpen,
    
    // Getters
    getCartTotal,
    getCartCount,
    
    // Actions
    addToCart,
    removeFromCart,
    updateQuantity,
    clearCart,
    toggleRTL,
    toggleFavorite,
    isFavorite,
    openProductModal,
    closeProductModal
  }
})