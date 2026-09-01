<template>
  <div class="hero-banner-wrapper py-6 md:py-0" :class="{ 'rtl': store.isRTL }">
    <Carousel
      v-bind="settings"
      :breakpoints="breakpoints"
      :wrap-around="true"
      :autoplay="8000"
      :transition="800"
      :dir="store.isRTL ? 'rtl' : 'ltr'"
      class="hero-carousel"
    >
      <Slide v-for="slide in slides" :key="slide.id">
        <div class="carousel__item w-full px-2 md:px-0">
          <div class="relative overflow-hidden rounded-[24px] md:rounded-none h-[40vh] min-h-[300px] max-h-[700px] md:h-[400px] lg:h-[500px] group cursor-pointer shadow-xl md:shadow-none" @click="navigateToSlide(slide.ctaLink)">
            <!-- Background Image -->
            <div
              class="absolute inset-0 bg-cover bg-center bg-no-repeat transition-transform duration-700 group-hover:scale-105"
              :style="{ backgroundImage: `url(${slide.image})` }"
            />

            <!-- Gradient Overlay -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent md:bg-black/30" />

            <!-- Content -->
            <div class="absolute bottom-0 inset-0 flex flex-col justify-end pt-8 md:pt-16 lg:pt-24 p-4 md:pr-8 lg:pr-16 pb-1 md:pb-3 lg:pb-6" :class="store.isRTL ? 'text-right' : 'text-left'">
              <div class="max-w-3xl animate-fade-in-up pl-1 md:pl-8 lg:pl-16">
                <p class="text-sm md:text-xl text-white/90 font-bold uppercase tracking-widest mb-2 drop-shadow-sm">
                  {{ slide.subtitle }}
                </p>
                <h2 class="text-2xl md:text-6xl text-white font-extrabold mb-4 leading-tight drop-shadow-md">
                  {{ slide.title }}
                </h2>
                <p class="hidden md:block text-lg text-white/80 mb-8 max-w-xl leading-relaxed">
                  {{ slide.description }}
                </p>
                <button
                  class="bg-white text-[#c20000] md:bg-[#c20000] md:text-white font-bold px-4 md:px-8 py-1 md:py-3.5 rounded-full md:rounded-lg transition-all duration-300 shadow-lg hover:shadow-xl active:scale-95 whitespace-nowrap"
                  @click.stop="navigateToSlide(slide.ctaLink)"
                >
                  {{ slide.ctaText }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </Slide>

      <template #addons>
        <div class="hidden md:block">
          <Navigation />
        </div>
        <Pagination />
      </template>
    </Carousel>
  </div>
</template>

<script>
import { defineComponent } from 'vue'
import { Carousel, Slide, Pagination, Navigation } from 'vue3-carousel'
import { useStore } from '@/stores/store'
import 'vue3-carousel/dist/carousel.css'

export default defineComponent({
  name: 'HeroBanner',
  components: {
    Carousel,
    Slide,
    Pagination,
    Navigation
  },
  props: {
    slides: {
      type: Array,
      default: () => []
    }
  },
  setup() {
    const store = useStore()

    const settings = {
      itemsToShow: 1.15,
      snapAlign: 'center',
    }

    const breakpoints = {
      // 768px and up
      768: {
        itemsToShow: 1,
        snapAlign: 'start',
      }
    }

    const navigateToSlide = (link) => {
      if (link) {
        window.location.href = link
      }
    }

    return {
      store,
      settings,
      breakpoints,
      navigateToSlide
    }
  }
})
</script>

<style scoped>
.hero-carousel :deep(.carousel__track) {
  @apply py-4 md:py-0;
}

/* Scaling effect for peeking slides */
.hero-carousel :deep(.carousel__slide) {
  @apply transition-all duration-500 opacity-60 scale-[0.92];
}

.hero-carousel :deep(.carousel__slide--active) {
  @apply opacity-100 scale-100;
}

/* Pagination dots styling */
.hero-carousel :deep(.carousel__pagination) {
  @apply absolute bottom-12 left-1/2 -translate-x-1/2 gap-2;
}

.hero-carousel :deep(.carousel__pagination-button) {
  @apply w-2.5 h-2.5 rounded-full bg-white/40 hover:bg-white/60 transition-all p-0 overflow-hidden after:hidden;
}

.hero-carousel :deep(.carousel__pagination-button--active) {
  @apply bg-white w-8;
}

/* Navigation Arrows (Desktop) */
.hero-carousel :deep(.carousel__prev),
.hero-carousel :deep(.carousel__next) {
  @apply bg-white/20 hover:bg-white/40 text-white rounded-full w-12 h-12 backdrop-blur-sm border border-white/30 mx-4 transition-all;
}

@keyframes fade-in-up {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in-up {
  animation: fade-in-up 0.8s ease-out forwards;
}

/* RTL Adjustment */
.rtl .ltr\:text-left { text-align: right; }
.rtl .rtl\:text-right { text-align: left; }
</style>
