<template>
  <div class="min-h-screen" :class="{ 'rtl': store.isRTL, 'ltr': !store.isRTL }">
    <Header @menu-toggle="handleMenuToggle" />

    <main class="container mx-auto px-4 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold mb-4">Products</h1>
        <p class="text-muted-foreground">
          Discover our curated collection of premium fashion items
        </p>
      </div>

      <!-- Search and Filters -->
      <div class="mb-8 space-y-4">
        <!-- Search bar -->
        <div class="relative max-w-md">
          <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <input
            type="text"
            placeholder="Search products..."
            v-model="searchTerm"
            class="w-full pl-10 pr-4 border border-input bg-background rounded-md px-3 py-2 text-sm ring-offset-background file:border-0 placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
          />
        </div>

        <!-- Filter controls -->
        <div class="flex flex-wrap items-center gap-4">
          <button
            class="md:hidden px-3 py-2 border border-border rounded-md hover:bg-accent hover:text-accent-foreground"
            @click="showFilters = !showFilters"
          >
            <Filter class="h-4 w-4 mr-2" />
            Filters {{ activeFiltersCount > 0 ? `(${activeFiltersCount})` : '' }}
          </button>

          <div class="flex flex-wrap gap-4" :class="{ 'block': showFilters, 'hidden md:flex': !showFilters }">
            <!-- Category filter -->
            <select
              v-model="selectedCategory"
              class="w-48 border border-input bg-background rounded-md px-3 py-2 text-sm ring-offset-background file:border-0 placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            >
              <option value="all">All Categories</option>
              <option v-for="category in mockCategories" :key="category.id" :value="category.name.toLowerCase()">
                {{ category.name }}
              </option>
            </select>

            <!-- Sort -->
            <select
              v-model="sortBy"
              class="w-48 border border-input bg-background rounded-md px-3 py-2 text-sm ring-offset-background file:border-0 placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            >
              <option value="featured">Featured</option>
              <option value="price-low">Price: Low to High</option>
              <option value="price-high">Price: High to Low</option>
              <option value="rating">Highest Rated</option>
              <option value="newest">Newest First</option>
            </select>

            <!-- Clear filters -->
            <button
              v-if="activeFiltersCount > 0"
              class="px-3 py-2 border border-border rounded-md hover:bg-accent hover:text-accent-foreground"
              @click="clearFilters"
            >
              <X class="h-4 w-4 mr-2" />
              Clear Filters
            </button>
          </div>
        </div>

        <!-- Active filters display -->
        <div v-if="activeFiltersCount > 0" class="flex flex-wrap gap-2">
          <span v-if="selectedCategory !== 'all'" class="bg-secondary text-secondary-foreground px-2 py-1 rounded text-sm">
            Category: {{ selectedCategory }}
            <X class="h-3 w-3 ml-1 cursor-pointer inline" @click="selectedCategory = 'all'" />
          </span>
          <span v-if="searchTerm" class="bg-secondary text-secondary-foreground px-2 py-1 rounded text-sm">
            Search: {{ searchTerm }}
            <X class="h-3 w-3 ml-1 cursor-pointer inline" @click="searchTerm = ''" />
          </span>
        </div>
      </div>

      <!-- Results count -->
      <div class="mb-6">
        <p class="text-muted-foreground">
          Showing {{ filteredProducts.length }} of {{ mockProducts.length }} products
        </p>
      </div>

      <!-- Products grid -->
      <div v-if="filteredProducts.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <ProductCard
          v-for="product in filteredProducts"
          :key="product.id"
          :product="product"
          @quick-add="handleQuickAdd"
        />
      </div>

      <div v-else class="text-center py-12">
        <p class="text-muted-foreground mb-4">No products found matching your criteria.</p>
        <button class="px-4 py-2 bg-accent hover:bg-accent/90 text-accent-foreground rounded-md" @click="clearFilters">
          Clear Filters
        </button>
      </div>
    </main>

    <Footer />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Search, Filter, X } from 'lucide-vue-next'
import Header from '@/components/Header.vue'
import ProductCard from '@/components/ProductCard.vue'
import Footer from '@/components/Footer.vue'
import { useStore } from '@/stores/store'
import { mockProducts, mockCategories } from '@/data/mockData'
import type { Product } from '@/types/store'

const store = useStore()

const searchTerm = ref('')
const selectedCategory = ref('all')
const sortBy = ref('featured')
const showFilters = ref(false)

const filteredProducts = computed(() => {
  let filtered = [...mockProducts]

  // Filter by category
  if (selectedCategory.value !== 'all') {
    filtered = filtered.filter(product =>
      product.category.toLowerCase() === selectedCategory.value.toLowerCase()
    )
  }

  // Filter by search term
  if (searchTerm.value) {
    filtered = filtered.filter(product =>
      product.name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
      product.description.toLowerCase().includes(searchTerm.value.toLowerCase())
    )
  }

  // Sort products
  switch (sortBy.value) {
    case 'price-low':
      filtered.sort((a, b) => (a.discountedPrice || a.originalPrice) - (b.discountedPrice || b.originalPrice))
      break
    case 'price-high':
      filtered.sort((a, b) => (b.discountedPrice || b.originalPrice) - (a.discountedPrice || a.originalPrice))
      break
    case 'rating':
      filtered.sort((a, b) => b.rating - a.rating)
      break
    case 'newest':
      filtered.sort((a, b) => (b.isNew ? 1 : 0) - (a.isNew ? 1 : 0))
      break
    default:
      // featured - no sorting
      break
  }

  return filtered
})

const activeFiltersCount = computed(() => {
  return [
    selectedCategory.value !== 'all',
    searchTerm.value !== ''
  ].filter(Boolean).length
})

const handleMenuToggle = () => {
  console.log('Menu toggle')
}

const handleQuickAdd = (product: Product) => {
  if (product.colors.length > 0 && product.colors[0].sizes.length > 0) {
    const firstColor = product.colors[0]
    const firstSize = firstColor.sizes[0]
    store.addToCart(product, firstColor, firstSize, 1)
  }
}

const clearFilters = () => {
  searchTerm.value = ''
  selectedCategory.value = 'all'
  sortBy.value = 'featured'
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
