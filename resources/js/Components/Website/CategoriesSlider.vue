<template>
  <div class="relative py-8 bg-[#fdf3f3]">
    <div class="container mx-auto px-4">
      <h2 class="text-2xl font-bold mb-6 text-center">{{ store.t('shopByCategory') }}</h2>

      <div class="relative">
        <button
          class="absolute left-0 top-1/2 transform -translate-y-1/2 z-10 bg-[#c20000] text-white hover:bg-white hover:text-[#c20000] shadow-lg p-2 rounded-md border border-[#c20000] transition-colors duration-300"
          @click="scrollLeft"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>

        <div
          ref="categoriesContainer"
          class="flex space-x-4 md:space-x-6 overflow-x-auto scrollbar-hide px-4 md:px-12 py-4 scroll-smooth"
        >
          <div
            v-for="category in categories"
            :key="category.id"
            class="flex flex-col items-center flex-none w-[100px] md:w-[calc((100%-144px)/7)] cursor-pointer group"
            @click="$emit('category-click', category)"
          >
            <div class="w-24 h-24 md:w-24 md:h-24 rounded-full bg-[#c20000] flex items-center justify-center mb-3 group-hover:bg-[#750000] transition-all group-hover:scale-110 overflow-hidden">
              <img v-if="category.image" :src="category.image" :alt="category.name" class="w-full h-full object-cover" />
              <span v-else class="text-2xl md:text-3xl text-white">{{ category.icon }}</span>
            </div>
            <p class="text-xs md:text-sm font-medium text-center group-hover:text-[#c20000] transition-colors line-clamp-1">
              {{ category.name }}
            </p>
          </div>
        </div>

        <button
          class="absolute right-0 top-1/2 transform -translate-y-1/2 z-10 bg-[#c20000] text-white hover:bg-white hover:text-[#c20000] shadow-lg p-2 rounded-md border border-[#c20000] transition-colors duration-300"
          @click="scrollRight"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'
import { useStore } from '@/stores/store'

export default {
  props: {
    categories: {
      type: Array,
      default: () => []
    }
  },
  setup() {
    const store = useStore()
    const categoriesContainer = ref(null)

    const scrollLeft = () => {
      if (categoriesContainer.value) {
        categoriesContainer.value.scrollBy({ left: -200, behavior: 'smooth' })
      }
    }

    const scrollRight = () => {
      if (categoriesContainer.value) {
        categoriesContainer.value.scrollBy({ left: 200, behavior: 'smooth' })
      }
    }

    return {
      store,
      categoriesContainer,
      scrollLeft,
      scrollRight
    }
  }
}
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
