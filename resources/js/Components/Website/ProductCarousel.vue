<template>
  <section class="py-12 bg-background border-b border-border/50">
    <div class="container mx-auto px-4">
      <div class="flex items-center justify-between mb-8">
        <h2 class="text-2xl md:text-3xl font-bold">{{ title }}</h2>
        <a
          v-if="showAllLink"
          :href="showAllLink"
          class="text-[#c20000] font-semibold hover:underline flex items-center gap-1"
        >
          {{ store.t('showAll') || 'Show All' }}
          <svg class="h-4 w-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
          </svg>
        </a>
      </div>

      <div class="relative group/carousel">
        <Carousel
          v-bind="settings"
          :breakpoints="breakpoints"
          :wrap-around="true"
          :autoplay="15000"
          :transition="600"
          :dir="store.isRTL ? 'rtl' : 'ltr'"
        >
          <Slide v-for="product in products" :key="product.id">
            <div class="px-2 w-full h-full">
              <ProductCard :product="product" :label="label" />
            </div>
          </Slide>

          <template #addons>
            <Navigation>
              <template #next>
                <div class="carousel-nav-btn next">
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </div>
              </template>
              <template #prev>
                <div class="carousel-nav-btn prev">
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                  </svg>
                </div>
              </template>
            </Navigation>
          </template>
        </Carousel>
      </div>
    </div>
  </section>
</template>

<script>
import { defineComponent } from 'vue'
import { Carousel, Slide, Navigation } from 'vue3-carousel'
import ProductCard from './ProductCard.vue'
import { useStore } from '@/stores/store'
import 'vue3-carousel/dist/carousel.css'

export default defineComponent({
  name: 'ProductCarousel',
  components: {
    Carousel,
    Slide,
    Navigation,
    ProductCard
  },
    props: {
    products: {
      type: Array,
      required: true
    },
    title: {
      type: String,
      required: true
    },
    label: {
      type: String,
      default: ''
    },
    showAllLink: {
      type: String,
      default: ''
    }
  },
  setup() {
    const store = useStore()

    const settings = {
      itemsToShow: 2,
      snapAlign: 'start',
    }

    const breakpoints = {
      // 700px and up
      700: {
        itemsToShow: 2.5,
        snapAlign: 'center',
      },
      // 1024 and up
      1024: {
        itemsToShow: 6,
        snapAlign: 'start',
      },
    }

    return {
      store,
      settings,
      breakpoints
    }
  }
})
</script>

<style scoped>
.carousel-nav-btn {
  background-color: #c20000 !important;
  color: white !important;
  @apply p-2.5 rounded-full shadow-lg border border-[#c20000] transition-all duration-300;
}

.carousel-nav-btn:hover {
  background-color: #e8e3e3 !important;
  color: #c20000 !important;
}

:deep(.carousel__prev), :deep(.carousel__next) {
  @apply opacity-0 group-hover/carousel:opacity-100 transition-all duration-300 bg-transparent border-none w-auto h-auto;
}

:deep(.carousel__prev) {
  @apply left-[-20px];
}

:deep(.carousel__next) {
  @apply right-[-20px];
}

@media (max-width: 768px) {
  :deep(.carousel__prev), :deep(.carousel__next) {
    @apply opacity-100;
  }
  :deep(.carousel__prev) {
    @apply left-2;
  }
  :deep(.carousel__next) {
    @apply right-2;
  }
}
</style>
