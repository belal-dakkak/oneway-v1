<template>
  <header class="sticky top-0 z-50 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 border-b">
    <div class="container mx-auto px-4">
      <!-- Top bar -->
      <div class="flex items-center justify-between h-16">
        <!-- Menu button (mobile) -->
        <button
          variant="ghost"
          size="icon"
          class="md:hidden"
          @click="$emit('menu-toggle')"
        >
          <Menu class="h-5 w-5" />
        </button>

        <!-- Logo -->
        <div class="flex items-center">
          <h1 class="text-2xl font-bold text-primary">Oneway</h1>
        </div>

        <!-- Search bar (desktop) -->
        <div class="hidden md:flex flex-1 max-w-md mx-8">
          <div class="relative w-full">
            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <input
              type="text"
              placeholder="Search products..."
              class="w-full pl-10 pr-4 border border-input bg-background rounded-md px-3 py-2 text-sm ring-offset-background file:border-0 placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            />
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center space-x-2">
          <!-- Search button (mobile) -->
          <button
            variant="ghost"
            size="icon"
            class="md:hidden"
            @click="isSearchOpen = !isSearchOpen"
          >
            <Search class="h-5 w-5" />
          </button>

          <!-- RTL Toggle -->
          <button
            variant="ghost"
            size="icon"
            @click="store.toggleRTL"
            :title="store.isRTL ? 'Switch to LTR' : 'Switch to RTL'"
          >
            <Globe class="h-5 w-5" />
          </button>

          <!-- Favorites -->
          <button variant="ghost" size="icon">
            <Heart class="h-5 w-5" />
          </button>

          <!-- Cart -->
          <button
            variant="ghost"
            size="icon"
            class="relative"
            @click="$router.push('/cart')"
          >
            <ShoppingCart class="h-5 w-5" />
            <span
              v-if="store.getCartCount > 0"
              class="absolute -top-2 -right-2 h-5 w-5 rounded-full p-0 flex items-center justify-center text-xs bg-destructive text-destructive-foreground"
            >
              {{ store.getCartCount }}
            </span>
          </button>

          <!-- User -->
          <button variant="ghost" size="icon">
            <User class="h-5 w-5" />
          </button>
        </div>
      </div>

      <!-- Mobile search -->
      <div v-if="isSearchOpen" class="md:hidden py-3 border-t">
        <div class="relative">
          <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <input
            type="text"
            placeholder="Search products..."
            class="w-full pl-10 pr-4 border border-input bg-background rounded-md px-3 py-2 text-sm ring-offset-background file:border-0 placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            autoFocus
          />
        </div>
      </div>

      <!-- Navigation -->
      <nav class="hidden md:flex items-center space-x-8 py-4 border-t">
        <a href="/" class="text-sm font-medium hover:text-primary transition-colors">
          Home
        </a>
        <a href="/products" class="text-sm font-medium hover:text-primary transition-colors">
          Products
        </a>
        <a href="#categories" class="text-sm font-medium hover:text-primary transition-colors">
          Categories
        </a>
        <a href="#about" class="text-sm font-medium hover:text-primary transition-colors">
          About Us
        </a>
        <a href="#contact" class="text-sm font-medium hover:text-primary transition-colors">
          Contact
        </a>
        <a href="/products?sale=true" class="text-sm font-medium text-red-600 hover:text-red-700 transition-colors">
          Sale
        </a>
      </nav>
    </div>
  </header>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Search, ShoppingCart, User, Menu, Heart, Globe } from 'lucide-vue-next'
import { useStore } from '@/stores/store'
import { useRouter } from 'vue-router'

const store = useStore()
const router = useRouter()
const isSearchOpen = ref(false)

defineEmits<{
  'menu-toggle': []
}>()
</script>
