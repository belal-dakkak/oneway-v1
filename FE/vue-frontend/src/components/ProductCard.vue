<template>
  <div 
    class="group cursor-pointer overflow-hidden transition-all-300 hover:shadow-lg bg-card rounded-lg border border-border"
    @click="handleProductClick"
  >
    <div class="relative">
      <!-- Product image -->
      <div class="aspect-[3/4] overflow-hidden bg-muted">
        <img
          :src="product.colors[0]?.image || '/api/placeholder/300/400'"
          :alt="product.name"
          class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
        />
      </div>

      <!-- Badges -->
      <div class="absolute top-2 left-2 flex flex-col space-y-2">
        <span
          v-if="product.isNew"
          class="bg-accent text-accent-foreground px-2 py-1 rounded text-xs font-medium"
        >
          New
        </span>
        <span
          v-if="hasDiscount"
          class="bg-destructive text-destructive-foreground px-2 py-1 rounded text-xs font-medium"
        >
          -{{ product.discountPercentage }}%
        </span>
      </div>

      <!-- Favorite button -->
      <button
        class="absolute top-2 right-2 bg-background/80 hover:bg-background text-foreground p-2 rounded-md"
        @click="handleFavoriteToggle"
      >
        <Heart 
          class="h-4 w-4"
          :class="{ 'fill-red-500 text-red-500': favorite }"
        />
      </button>

      <!-- Quick add button -->
      <button
        class="absolute bottom-2 left-2 right-2 bg-accent hover:bg-accent/90 text-accent-foreground opacity-0 group-hover:opacity-100 transition-opacity px-3 py-2 rounded-md text-sm font-medium"
        @click="handleQuickAdd"
      >
        <ShoppingCart class="h-4 w-4 mr-2 inline" />
        Quick Add
      </button>
    </div>

    <div class="p-4">
      <!-- Product name -->
      <h3 class="font-medium text-sm mb-2 line-clamp-2 group-hover:text-accent-foreground transition-colors">
        {{ product.name }}
      </h3>

      <!-- Rating -->
      <div class="flex items-center space-x-1 mb-2">
        <div class="flex items-center">
          <Star
            v-for="i in 5"
            :key="i"
            class="h-3 w-3"
            :class="i <= Math.floor(product.rating) ? 'fill-yellow-400 text-yellow-400' : 'fill-gray-200 text-gray-200'"
          />
        </div>
        <span class="text-xs text-muted-foreground">
          ({{ product.reviews }})
        </span>
      </div>

      <!-- Price -->
      <div class="flex items-center space-x-2">
        <span class="font-bold text-primary">
          ${{ currentPrice }}
        </span>
        <span
          v-if="hasDiscount"
          class="text-sm text-muted-foreground line-through"
        >
          ${{ product.originalPrice }}
        </span>
      </div>

      <!-- Colors -->
      <div
        v-if="product.colors.length > 1"
        class="flex items-center space-x-1 mt-2"
      >
        <div
          v-for="(color, index) in product.colors.slice(0, 3)"
          :key="color.id"
          class="w-4 h-4 rounded-full border border-border"
          :style="{ backgroundColor: color.hexCode }"
          :title="color.name"
        />
        <span
          v-if="product.colors.length > 3"
          class="text-xs text-muted-foreground"
        >
          +{{ product.colors.length - 3 }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Star, Heart, ShoppingCart } from 'lucide-vue-next'
import type { Product } from '@/types/store'
import { useStore } from '@/stores/store'

interface Props {
  product: Product
}

const props = defineProps<Props>()
const store = useStore()

const hasDiscount = computed(() => props.product.discountPercentage && props.product.discountPercentage > 0)
const currentPrice = computed(() => props.product.discountedPrice || props.product.originalPrice)
const favorite = computed(() => store.isFavorite(props.product.id))

const handleProductClick = () => {
  // Navigate to product details page
  window.location.href = `/product/${props.product.id}`
}

const handleQuickAdd = (event: Event) => {
  event.stopPropagation()
  if (props.product.colors.length > 0 && props.product.colors[0].sizes.length > 0) {
    const firstColor = props.product.colors[0]
    const firstSize = firstColor.sizes[0]
    store.addToCart(props.product, firstColor, firstSize, 1)
  }
}

const handleFavoriteToggle = (event: Event) => {
  event.stopPropagation()
  store.toggleFavorite(props.product.id)
}
</script>