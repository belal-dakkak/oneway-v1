<template>
  <div class="product-gallery">
    <!-- Desktop Layout (Main Image + Thumbnails) -->
    <div class="hidden md:flex flex-row gap-6 items-start h-auto">
      <!-- Vertical Thumbnails -->
      <div class="w-24 space-y-4 overflow-y-auto no-scrollbar py-1 max-h-[80vh]">
        <button
          v-for="(image, index) in images"
          :key="index"
          @click="activeIndex = index"
          class="w-full aspect-square rounded-lg overflow-hidden border-2 transition-all duration-200 bg-muted flex-shrink-0"
          :class="activeIndex === index ? 'border-primary ring-2 ring-primary/10 scale-[1.02]' : 'border-transparent hover:border-primary/40 opacity-70 hover:opacity-100'"
        >
          <img :src="image.src" :alt="image.alt" class="w-full h-full object-cover" />
        </button>
      </div>

      <!-- Main Image Display -->
      <div class="flex-1 relative bg-white rounded-2xl border border-border group cursor-zoom-in flex flex-col items-center justify-start overflow-hidden h-auto max-h-[80vh]">
        <transition name="fade" mode="out-in">
          <img
            :key="activeIndex"
            :src="images[activeIndex]?.src"
            :alt="images[activeIndex]?.alt"
            class="block object-contain transition-transform duration-700 group-hover:scale-[1.03]"
            style="width: auto; height: auto; max-width: 100%; max-height: 70vh;"
            @click="openLightbox(activeIndex)"
          />
        </transition>

        <!-- Navigation Arrows -->
        <button
          v-if="images.length > 1"
          @click.stop="prev"
          class="absolute left-4 top-1/2 -translate-y-1/2 p-3 bg-white/80 backdrop-blur-sm rounded-full shadow-lg opacity-0 group-hover:opacity-100 transition-all hover:bg-white"
        >
          <svg class="w-5 h-5 text-zinc-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <button
          v-if="images.length > 1"
          @click.stop="next"
          class="absolute right-4 top-1/2 -translate-y-1/2 p-3 bg-white/80 backdrop-blur-sm rounded-full shadow-lg opacity-0 group-hover:opacity-100 transition-all hover:bg-white"
        >
          <svg class="w-5 h-5 text-zinc-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>

        <slot name="badges"></slot>
      </div>
    </div>

    <!-- Mobile Layout (Swipeable Carousel) -->
    <div class="md:hidden relative -mx-4">
      <Carousel
        ref="carouselRef"
        v-model="activeIndex"
        :items-to-show="1"
        :wrap-around="images.length > 1"
        class="w-full"
      >
        <Slide v-for="(image, index) in images" :key="index">
          <div
            class="w-full aspect-1 md:aspect-[4/5] bg-white flex items-center justify-center overflow-hidden"
            @click="openLightbox(index)"
          >
            <img :src="image.src" :alt="image.alt" class="max-w-[65%] md:max-w-full max-h-full object-contain" />
          </div>
        </Slide>

        <template #addons v-if="images.length > 1">
          <Pagination class="absolute bottom-4 left-1/2 -translate-x-1/2" />
        </template>
      </Carousel>

      <slot name="badges-mobile"></slot>
    </div>

    <!-- Lightbox (Full Screen Modal) -->
    <Teleport to="body">
      <transition enter-active-class="duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="isLightboxOpen" class="fixed inset-0 z-[200] bg-black flex flex-col pt-4">
          <!-- Header -->
          <div class="absolute top-0 inset-x-0 h-8 flex items-center justify-between px-6 bg-black/50 backdrop-blur-sm z-[210]">
            <span class="text-white font-medium">{{ activeIndex + 1 }} / {{ images.length }}</span>
            <button @click="closeLightbox" class="p-2 text-white hover:bg-white/10 rounded-full transition-colors">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Main Carousel for Lightbox -->
          <div class="flex-1 flex items-center justify-center relative overflow-hidden">
            <Carousel
              v-model="activeIndex"
              :items-to-show="1"
              :wrap-around="true"
              class="w-full h-full"
            >
              <Slide v-for="(image, index) in images" :key="index" class="h-full">
                <div class="w-full h-full flex items-center justify-center p-4">
                  <img :src="image.src" :alt="image.alt" class="max-w-full lg:max-w-[25%] max-h-full object-contain select-none shadow-2xl" />
                </div>
              </Slide>

              <template #addons>
                <div class="hidden md:block">
                  <Navigation />
                </div>
              </template>
            </Carousel>
          </div>

          <!-- Thumbnail Strip (Bottom) -->
          <div class="h-24 md:h-32 bg-zinc-900/80 backdrop-blur-lg p-3 flex justify-center gap-3 overflow-x-auto border-t border-white/10 no-scrollbar">
            <button
              v-for="(image, index) in images"
              :key="index"
              @click="activeIndex = index"
              class="relative w-16 md:w-20 flex-shrink-0 aspect-square rounded-md overflow-hidden border-2 transition-all outline-none"
              :class="activeIndex === index ? 'border-primary ring-2 ring-primary/20 scale-105' : 'border-transparent opacity-40 hover:opacity-100'"
            >
              <img :src="image.src" class="w-full h-full object-cover" />
            </button>
          </div>
        </div>
      </transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Carousel, Slide, Pagination, Navigation } from 'vue3-carousel'
import 'vue3-carousel/dist/carousel.css'

const props = defineProps({
  images: {
    type: Array,
    required: true,
    default: () => []
  },
  initialIndex: {
    type: Number,
    default: 0
  }
})

const emit = defineEmits(['change'])

const activeIndex = ref(props.initialIndex)
const isLightboxOpen = ref(false)

// Notify parent when index changes
watch(activeIndex, (newVal) => {
  emit('change', newVal)
})

// Watch for prop changes (e.g. color change in parent)
watch(() => props.initialIndex, (newVal) => {
  activeIndex.value = newVal
})

const next = () => {
  if (activeIndex.value < props.images.length - 1) {
    activeIndex.value++
  } else {
    activeIndex.value = 0
  }
}

const prev = () => {
  if (activeIndex.value > 0) {
    activeIndex.value--
  } else {
    activeIndex.value = props.images.length - 1
  }
}

const openLightbox = (index) => {
  activeIndex.value = index
  isLightboxOpen.value = true
  document.body.style.overflow = 'hidden'
}

const closeLightbox = () => {
  isLightboxOpen.value = false
  document.body.style.overflow = ''
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.4s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

:deep(.carousel__pagination-button) {
  padding: 0;
  margin: 0 4px;
}

:deep(.carousel__pagination-button::after) {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: rgba(0, 0, 0, 0.2);
  transition: all 0.3s ease;
}

:deep(.carousel__pagination-button--active::after) {
  background-color: #c20000;
  transform: scale(1.2);
}

:deep(.carousel__prev),
:deep(.carousel__next) {
  background-color: white;
  color: #c20000;
  border-radius: 50%;
  width: 44px;
  height: 44px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  transition: all 0.2s ease;
}

:deep(.carousel__prev:hover),
:deep(.carousel__next:hover) {
  background-color: #c20000;
  color: white;
  transform: scale(1.1);
}

.carousel__prev {
  left: 5% !important;
}
.carousel__next {
  right: 5% !important;
}

.aspect-1 {
    aspect-ratio: 1;
}

@media (max-width: 768px) {
  :deep(.carousel__pagination-button::after) {
    background-color: rgba(255, 255, 255, 0.4);
  }
  :deep(.carousel__pagination-button--active::after) {
    background-color: white;
  }
}
</style>
