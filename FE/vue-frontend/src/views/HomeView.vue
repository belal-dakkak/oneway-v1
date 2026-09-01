<template>
  <div class="min-h-screen" :class="{ 'rtl': store.isRTL, 'ltr': !store.isRTL }">
    <Header @menu-toggle="handleMenuToggle" />
    
    <main>
      <!-- Hero Banner -->
      <HeroBanner :slides="mockHeroSlides" />

      <!-- Categories Slider -->
      <CategoriesSlider 
        :categories="mockCategories"
        @category-click="handleCategoryClick"
      />

      <!-- Featured Products -->
      <FeaturedProducts :products="mockProducts" />

      <!-- Additional sections can be added here -->
      <section class="py-12 bg-secondary">
        <div class="container mx-auto px-4 text-center">
          <h2 class="text-3xl font-bold mb-4">Why Choose Oneway?</h2>
          <p class="text-muted-foreground max-w-2xl mx-auto mb-8">
            Discover premium quality fashion with our carefully curated collection. 
            From timeless classics to modern trends, we bring you the best in contemporary style.
          </p>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
              <div class="w-16 h-16 bg-accent rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-2xl">🚚</span>
              </div>
              <h3 class="font-semibold mb-2">Free Shipping</h3>
              <p class="text-sm text-muted-foreground">On orders over $100</p>
            </div>
            <div class="text-center">
              <div class="w-16 h-16 bg-accent rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-2xl">🔄</span>
              </div>
              <h3 class="font-semibold mb-2">Easy Returns</h3>
              <p class="text-sm text-muted-foreground">30-day return policy</p>
            </div>
            <div class="text-center">
              <div class="w-16 h-16 bg-accent rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-2xl">🎁</span>
              </div>
              <h3 class="font-semibold mb-2">Member Benefits</h3>
              <p class="text-sm text-muted-foreground">Exclusive offers and rewards</p>
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- Sitemap -->
    <Sitemap />

    <!-- Footer -->
    <Footer />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import Header from '@/components/Header.vue'
import HeroBanner from '@/components/HeroBanner.vue'
import CategoriesSlider from '@/components/CategoriesSlider.vue'
import FeaturedProducts from '@/components/FeaturedProducts.vue'
import Sitemap from '@/components/Sitemap.vue'
import Footer from '@/components/Footer.vue'
import { useStore } from '@/stores/store'
import { mockProducts, mockCategories, mockHeroSlides } from '@/data/mockData'
import type { Category } from '@/types/store'

const store = useStore()

const handleMenuToggle = () => {
  console.log('Menu toggle')
}

const handleCategoryClick = (category: Category) => {
  console.log('Navigate to category:', category.name)
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