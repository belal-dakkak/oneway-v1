<template>
  <section class="py-12">
    <div class="container mx-auto px-4">
      <h2 class="text-3xl font-bold text-center mb-8">{{ title }}</h2>
      
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <ProductCard
          v-for="product in products"
          :key="product.id"
          :product="product"
          @quick-add="handleQuickAdd"
        />
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import type { Product } from '@/types/store'
import { useStore } from '@/stores/store'
import ProductCard from './ProductCard.vue'

interface Props {
  products: Product[]
  title?: string
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Featured Products'
})

const store = useStore()

const handleQuickAdd = (product: Product) => {
  if (product.colors.length > 0 && product.colors[0].sizes.length > 0) {
    const firstColor = product.colors[0]
    const firstSize = firstColor.sizes[0]
    store.addToCart(product, firstColor, firstSize, 1)
  }
}
</script>