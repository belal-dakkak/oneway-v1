<template>
  <div class="min-h-screen bg-background" :dir="store.isRTL ? 'rtl' : 'ltr'">
    <Header 
      :title="store.t('favorites')" 
      :facebook="facebook"
      :instagram="instagram"
      :tiktok="tiktok"
      @menu-toggle="isMobileMenuOpen = !isMobileMenuOpen" 
    />

    <main class="container mx-auto px-4 py-8">
      <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
        <div>
          <h2 class="text-3xl font-bold tracking-tight">{{ store.t('favorites') }}</h2>
          <p class="text-muted-foreground mt-1">
            {{ favorites.length }} {{ favorites.length === 1 ? store.t('item') : store.t('items') }}
          </p>
        </div>
        
        <button 
          v-if="favorites.length > 0"
          @click="addAllToCart"
          class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
        >
          {{ store.t('addAllToCart') }}
        </button>
      </div>

      <!-- Empty State -->
      <div v-if="favorites.length === 0" class="flex flex-col items-center justify-center py-20 text-center">
        <div class="w-20 h-20 bg-muted rounded-full flex items-center justify-center mb-6">
          <svg class="h-10 w-10 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
          </svg>
        </div>
        <h3 class="text-xl font-semibold mb-2">{{ store.t('noFavoritesYet') }}</h3>
        <p class="text-muted-foreground mb-8 max-w-sm">
          {{ store.t('noFavoritesMessage') }}
        </p>
        <a 
          :href="route('shop')" 
          class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-11 px-8"
        >
          {{ store.t('startShopping') }}
        </a>
      </div>

      <!-- Favorites Grid -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <ProductCard
          v-for="product in favorites"
          :key="product.id"
          :product="product"
        />
      </div>
    </main>

      :address="address"
    />
    <FloatingButtons :whatsapp-number="phone" :is-r-t-l="store.isRTL" />
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useStore } from '@/stores/store'
import Header from '@/Components/Website/Header.vue'
import Footer from '@/Components/Website/Footer.vue'
import ProductCard from '@/Components/Website/ProductCard.vue'
import FloatingButtons from '@/Components/Website/FloatingButtons.vue'

export default {
  components: {
    Header,
    Footer,
    ProductCard,
    FloatingButtons
  },
  props: {
    favorites: {
      type: Array,
      default: () => []
    },
    categories: Array,
    phone: String,
    email: String,
    facebook: String,
    instagram: String,
    tiktok: String,
    address: String
  },
  setup(props) {
    const store = useStore()
    const isMobileMenuOpen = ref(false)

    onMounted(() => {
      // Initialize favorites in store if we have them from backend
      if (props.favorites && props.favorites.length > 0) {
        store.setFavorites(props.favorites.map(f => f.id))
      }
    })

    const addAllToCart = () => {
      props.favorites.forEach(product => {
        store.addToCart(product, product.colors?.[0], product.colors?.[0]?.sizes?.[0] || 'Default', 1)
      })
      // Optional: redirect to cart or show notification
      window.location.href = route('cart')
    }

    return {
      store,
      isMobileMenuOpen,
      addAllToCart
    }
  }
}
</script>
