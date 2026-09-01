<template>
  <div class="relative h-[600px] overflow-hidden">
    <!-- Slides -->
    <div class="relative h-full">
      <div
        v-for="(slide, index) in slides"
        :key="slide.id"
        class="absolute inset-0 transition-opacity duration-1000"
        :class="index === currentSlide ? 'opacity-100' : 'opacity-0'"
      >
        <div class="relative h-full">
          <!-- Background image -->
          <div 
            class="absolute inset-0 bg-cover bg-center bg-no-repeat"
            :style="{ backgroundImage: `url(${slide.image})` }"
          />
          
          <!-- Overlay -->
          <div class="absolute inset-0 bg-black/40" />
          
          <!-- Content -->
          <div class="relative h-full flex items-center justify-center text-center text-white px-4">
            <div class="max-w-3xl mx-auto">
              <p class="text-lg md:text-xl font-medium mb-4 opacity-90">
                {{ slide.subtitle }}
              </p>
              <h2 class="text-4xl md:text-6xl font-bold mb-6">
                {{ slide.title }}
              </h2>
              <p class="text-lg md:text-xl mb-8 opacity-90 max-w-2xl mx-auto">
                {{ slide.description }}
              </p>
              <button 
                class="bg-accent hover:bg-accent/90 text-accent-foreground font-medium px-8 py-3 rounded-md"
                @click="navigateToSlide(slide.ctaLink)"
              >
                {{ slide.ctaText }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Navigation arrows -->
    <button
      class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-white/20 hover:bg-white/30 border border-white/30 text-white p-2 rounded-md"
      @click="prevSlide"
    >
      <ChevronLeft class="h-4 w-4" />
    </button>
    <button
      class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-white/20 hover:bg-white/30 border border-white/30 text-white p-2 rounded-md"
      @click="nextSlide"
    >
      <ChevronRight class="h-4 w-4" />
    </button>

    <!-- Dots indicator -->
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 flex space-x-2">
      <button
        v-for="(_, index) in slides"
        :key="index"
        class="w-3 h-3 rounded-full transition-all"
        :class="index === currentSlide ? 'bg-white w-8' : 'bg-white/50 hover:bg-white/70'"
        @click="goToSlide(index)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import type { HeroSlide } from '@/types/store'

interface Props {
  slides: HeroSlide[]
}

const props = defineProps<Props>()
const currentSlide = ref(0)
let intervalId: NodeJS.Timeout | null = null

const nextSlide = () => {
  currentSlide.value = (currentSlide.value + 1) % props.slides.length
}

const prevSlide = () => {
  currentSlide.value = (currentSlide.value - 1 + props.slides.length) % props.slides.length
}

const goToSlide = (index: number) => {
  currentSlide.value = index
}

const navigateToSlide = (link: string) => {
  window.location.href = link
}

onMounted(() => {
  intervalId = setInterval(() => {
    nextSlide()
  }, 5000)
})

onUnmounted(() => {
  if (intervalId) {
    clearInterval(intervalId)
  }
})
</script>