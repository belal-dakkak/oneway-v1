<template>
  <div class="min-h-screen" :class="{ 'rtl': store.isRTL, 'ltr': !store.isRTL }">
    <Header @menu-toggle="handleMenuToggle" />

    <main class="container mx-auto px-4 py-8">
      <div v-if="product" class="grid lg:grid-cols-2 gap-12 mb-16">
        <!-- Product Images -->
        <div class="space-y-4">
          <div class="relative aspect-[3/4] overflow-hidden bg-muted rounded-lg">
            <img
              :src="images[currentImageIndex] || '/api/placeholder/600/800'"
              :alt="product.name"
              class="w-full h-full object-cover"
            />
            
            <!-- Image navigation -->
            <button
              v-if="images.length > 1"
              class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-background/80 p-2 rounded-md"
              @click="prevImage"
            >
              <ChevronLeft class="h-4 w-4" />
            </button>
            <button
              v-if="images.length > 1"
              class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-background/80 p-2 rounded-md"
              @click="nextImage"
            >
              <ChevronRight class="h-4 w-4" />
            </button>

            <!-- Badges -->
            <div class="absolute top-4 left-4 flex flex-col space-y-2">
              <span
                v-if="product.isNew"
                class="bg-accent text-accent-foreground px-2 py-1 rounded text-sm font-medium"
              >
                New
              </span>
              <span
                v-if="hasDiscount"
                class="bg-destructive text-destructive-foreground px-2 py-1 rounded text-sm font-medium"
              >
                -{{ product.discountPercentage }}%
              </span>
            </div>
          </div>

          <!-- Image thumbnails -->
          <div v-if="images.length > 1" class="flex space-x-2">
            <button
              v-for="(_, index) in images"
              :key="index"
              class="w-20 h-20 border-2 rounded overflow-hidden"
              :class="index === currentImageIndex ? 'border-accent' : 'border-border'"
              @click="currentImageIndex = index"
            >
              <img
                :src="images[index]"
                :alt="`${product.name} ${index + 1}`"
                class="w-full h-full object-cover"
              />
            </button>
          </div>
        </div>

        <!-- Product Details -->
        <div class="space-y-6">
          <!-- Title and Price -->
          <div>
            <span
              v-if="product.isNew"
              class="bg-accent text-accent-foreground px-2 py-1 rounded text-sm font-medium"
            >
              New
            </span>
            <h1 class="text-3xl font-bold mb-4">{{ product.name }}</h1>
            
            <div class="flex items-center space-x-4 mb-4">
              <div class="flex items-center">
                <Star
                  v-for="i in 5"
                  :key="i"
                  class="h-5 w-5"
                  :class="i <= Math.floor(product.rating) ? 'fill-yellow-400 text-yellow-400' : 'fill-gray-200 text-gray-200'"
                />
              </div>
              <span class="text-sm text-muted-foreground">
                {{ product.rating }} ({{ product.reviews }} reviews)
              </span>
            </div>

            <div class="flex items-center space-x-4">
              <span class="text-3xl font-bold text-primary">
                ${{ currentPrice }}
              </span>
              <span
                v-if="hasDiscount"
                class="text-xl text-muted-foreground line-through"
              >
                ${{ product.originalPrice }}
              </span>
              <span
                v-if="hasDiscount"
                class="bg-destructive text-destructive-foreground px-2 py-1 rounded text-sm font-medium"
              >
                -{{ product.discountPercentage }}%
              </span>
            </div>
          </div>

          <div class="border-t border-border pt-6">
            <!-- Description -->
            <div class="mb-6">
              <h3 class="font-semibold mb-2">Description</h3>
              <p class="text-muted-foreground">{{ product.description }}</p>
            </div>

            <!-- Color Selection -->
            <div v-if="colors.length > 0" class="mb-6">
              <h3 class="font-semibold mb-3">Color: {{ selectedColor?.name }}</h3>
              <div class="flex space-x-3">
                <button
                  v-for="color in colors"
                  :key="color.id"
                  class="w-12 h-12 rounded-full border-2 transition-all"
                  :class="selectedColor?.id === color.id ? 'border-accent ring-2 ring-accent/50' : 'border-border hover:border-accent/50'"
                  :style="{ backgroundColor: color.hexCode }"
                  @click="handleColorSelect(color)"
                  :title="color.name"
                />
              </div>
            </div>

            <!-- Size Selection -->
            <div v-if="sizes.length > 0" class="mb-6">
              <h3 class="font-semibold mb-3">Size</h3>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="size in sizes"
                  :key="size.id"
                  class="px-4 py-2 border rounded-md transition-all"
                  :class="selectedSize?.id === size.id ? 'border-accent bg-accent text-accent-foreground' : 'border-border hover:border-accent/50'"
                  :class="{ 'opacity-50 cursor-not-allowed': size.stock === 0 }"
                  @click="size.stock > 0 && handleSizeSelect(size)"
                  :disabled="size.stock === 0"
                >
                  {{ size.size }}
                  {{ size.stock === 0 ? ' (Out of stock)' : '' }}
                </button>
              </div>
            </div>

            <!-- Quantity -->
            <div class="mb-6">
              <h3 class="font-semibold mb-3">Quantity</h3>
              <div class="flex items-center space-x-3">
                <button
                  class="p-2 border border-border rounded-md hover:bg-accent hover:text-accent-foreground"
                  @click="quantity = Math.max(1, quantity - 1)"
                >
                  <Minus class="h-4 w-4" />
                </button>
                <span class="w-12 text-center font-medium">{{ quantity }}</span>
                <button
                  class="p-2 border border-border rounded-md hover:bg-accent hover:text-accent-foreground"
                  @click="quantity = quantity + 1"
                >
                  <Plus class="h-4 w-4" />
                </button>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex space-x-4 mb-6">
              <button
                class="flex-1 bg-accent hover:bg-accent/90 text-accent-foreground px-4 py-3 rounded-md text-lg font-medium"
                @click="handleAddToCart"
                :disabled="!selectedColor || !selectedSize || selectedSize.stock === 0"
              >
                <ShoppingCart class="h-4 w-4 mr-2" />
                Add to Cart
              </button>
              <button
                class="px-4 py-3 border border-border rounded-md text-lg hover:bg-accent hover:text-accent-foreground"
                @click="store.toggleFavorite(product.id)"
              >
                <Heart class="h-4 w-4" :class="{ 'fill-red-500 text-red-500': favorite }" />
              </button>
            </div>

            <!-- Share -->
            <div class="mb-6">
              <h3 class="font-semibold mb-3">Share this product</h3>
              <div class="flex space-x-2">
                <button
                  class="px-3 py-2 border border-border rounded-md hover:bg-accent hover:text-accent-foreground text-sm"
                  @click="handleShare('whatsapp')"
                >
                  WhatsApp
                </button>
                <button
                  class="px-3 py-2 border border-border rounded-md hover:bg-accent hover:text-accent-foreground text-sm"
                  @click="handleShare('facebook')"
                >
                  Facebook
                </button>
                <button
                  class="px-3 py-2 border border-border rounded-md hover:bg-accent hover:text-accent-foreground text-sm"
                  @click="handleShare('twitter')"
                >
                  Twitter
                </button>
              </div>
            </div>
          </div>

          <!-- Features -->
          <div class="border-t border-border pt-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="flex items-center space-x-3">
                <Truck class="h-5 w-5 text-accent" />
                <div>
                  <p class="font-medium text-sm">Free Shipping</p>
                  <p class="text-xs text-muted-foreground">On orders over $100</p>
                </div>
              </div>
              <div class="flex items-center space-x-3">
                <Shield class="h-5 w-5 text-accent" />
                <div>
                  <p class="font-medium text-sm">Secure Payment</p>
                  <p class="text-xs text-muted-foreground">100% secure transactions</p>
                </div>
              </div>
              <div class="flex items-center space-x-3">
                <RefreshCw class="h-5 w-5 text-accent" />
                <div>
                  <p class="font-medium text-sm">Easy Returns</p>
                  <p class="text-xs text-muted-foreground">30-day return policy</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Related Products -->
      <section v-if="relatedProducts.length > 0">
        <h2 class="text-2xl font-bold mb-8">Related Products</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
          <ProductCard
            v-for="relatedProduct in relatedProducts"
            :key="relatedProduct.id"
            :product="relatedProduct"
            @quick-add="handleRelatedQuickAdd"
          />
        </div>
      </section>
    </main>

    <Footer />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { Star, Heart, ShoppingCart, ChevronLeft, ChevronRight, Minus, Plus, Truck, Shield, RefreshCw } from 'lucide-vue-next'
import Header from '@/components/Header.vue'
import ProductCard from '@/components/ProductCard.vue'
import Footer from '@/components/Footer.vue'
import { useStore } from '@/stores/store'
import { mockProducts } from '@/data/mockData'
import type { Product, ProductColor, ProductSize } from '@/types/store'

const route = useRoute()
const store = useStore()

const productId = route.params.id as string
const selectedColor = ref<ProductColor | null>(null)
const selectedSize = ref<ProductSize | null>(null)
const currentImageIndex = ref(0)
const quantity = ref(1)

const product = computed(() => mockProducts.find(p => p.id === productId))
const colors = computed(() => product.value?.colors || [])
const sizes = computed(() => selectedColor.value?.sizes || [])
const images = computed(() => selectedColor.value?.image ? [selectedColor.value.image] : product.value?.images || [])
const hasDiscount = computed(() => product.value?.discountPercentage && product.value.discountPercentage > 0)
const currentPrice = computed(() => product.value?.discountedPrice || product.value?.originalPrice || 0)
const favorite = computed(() => product.value ? store.isFavorite(product.value.id) : false)

const relatedProducts = computed(() => {
  if (!product.value) return []
  return mockProducts
    .filter(p => p.id !== product.value.id && p.category === product.value.category)
    .slice(0, 4)
})

// Auto-select first color and size when product changes
watch([colors, selectedColor, selectedSize], () => {
  if (colors.value.length > 0 && !selectedColor.value) {
    selectedColor.value = colors.value[0]
    if (colors.value[0].sizes.length > 0 && !selectedSize.value) {
      selectedSize.value = colors.value[0].sizes[0]
    }
  }
}, { immediate: true })

const handleColorSelect = (color: ProductColor) => {
  selectedColor.value = color
  selectedSize.value = color.sizes[0] || null
  currentImageIndex.value = 0
}

const handleSizeSelect = (size: ProductSize) => {
  selectedSize.value = size
}

const handleAddToCart = () => {
  if (selectedColor.value && selectedSize.value && product.value) {
    store.addToCart(product.value, selectedColor.value, selectedSize.value, quantity.value)
  }
}

const handleShare = (platform: string) => {
  const url = window.location.href
  const text = `Check out ${product.value?.name} on Oneway!`
  
  let shareUrl = ''
  switch (platform) {
    case 'whatsapp':
      shareUrl = `https://wa.me/?text=${encodeURIComponent(text + ' ' + url)}`
      break
    case 'facebook':
      shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`
      break
    case 'twitter':
      shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}`
      break
  }
  
  if (shareUrl) {
    window.open(shareUrl, '_blank')
  }
}

const nextImage = () => {
  if (images.value.length > 0) {
    currentImageIndex.value = (currentImageIndex.value + 1) % images.value.length
  }
}

const prevImage = () => {
  if (images.value.length > 0) {
    currentImageIndex.value = (currentImageIndex.value - 1 + images.value.length) % images.value.length
  }
}

const handleRelatedQuickAdd = (relatedProduct: Product) => {
  if (relatedProduct.colors.length > 0 && relatedProduct.colors[0].sizes.length > 0) {
    const firstColor = relatedProduct.colors[0]
    const firstSize = firstColor.sizes[0]
    store.addToCart(relatedProduct, firstColor, firstSize, 1)
  }
}

const handleMenuToggle = () => {
  console.log('Menu toggle')
}

onMounted(() => {
  // Apply RTL class to body when RTL is enabled
  if (store.isRTL) {
    document.body.classList.add('rtl')
    document.body.classList.remove('ltr')
  } else {
    document.body.classList.add('ltr')
    document.body.classList.remove('rtl')
  }
})
</script>