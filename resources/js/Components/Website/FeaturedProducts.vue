<template>
  <section class="py-12">
    <div class="container mx-auto px-4">
      <h2 class="text-3xl font-bold text-center mb-8">{{ displayTitle }}</h2>
      
      <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 md:gap-6">
        <ProductCard
          v-for="product in products"
          :key="product.id"
          :product="product"
        />
      </div>
    </div>
  </section>
</template>

<script>
import ProductCard from './ProductCard.vue'
import { useStore } from '@/stores/store'

export default {
  components: {
    ProductCard
  },
  props: {
    products: {
      type: Array,
      default: () => []
    },
    title: {
      type: String,
      default: ''
    }
  },
  setup(props) {
    const store = useStore()
    return {
      store,
      displayTitle: props.title || store.t('featuredProducts')
    }
  }
}
</script>
