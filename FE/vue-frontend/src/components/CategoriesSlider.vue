<template>
  <div class="relative py-8">
    <div class="container mx-auto px-4">
      <h2 class="text-2xl font-bold mb-6 text-center">Shop by Category</h2>
      
      <div class="relative">
        <!-- Left scroll button -->
        <button
          class="absolute left-0 top-1/2 transform -translate-y-1/2 z-10 bg-background shadow-lg p-2 rounded-md border border-border"
          @click="scrollLeft"
        >
          <ChevronLeft class="h-4 w-4" />
        </button>

        <!-- Categories container -->
        <div 
          ref="categoriesContainer"
          class="flex space-x-6 overflow-x-auto scrollbar-hide px-12 py-4 scroll-smooth"
        >
          <div
            v-for="category in categories"
            :key="category.id"
            class="flex flex-col items-center min-w-[120px] cursor-pointer group"
            @click="$emit('category-click', category)"
          >
            <div class="w-20 h-20 rounded-full bg-secondary flex items-center justify-center mb-3 group-hover:bg-accent transition-all-300 group-hover:scale-110">
              <span class="text-3xl">{{ category.icon }}</span>
            </div>
            <p class="text-sm font-medium text-center group-hover:text-accent-foreground transition-colors">
              {{ category.name }}
            </p>
          </div>
        </div>

        <!-- Right scroll button -->
        <button
          class="absolute right-0 top-1/2 transform -translate-y-1/2 z-10 bg-background shadow-lg p-2 rounded-md border border-border"
          @click="scrollRight"
        >
          <ChevronRight class="h-4 w-4" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import type { Category } from '@/types/store'

interface Props {
  categories: Category[]
}

const props = defineProps<Props>()
const categoriesContainer = ref<HTMLElement | null>(null)

const emit = defineEmits<{
  'category-click': [category: Category]
}>()

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
</script>