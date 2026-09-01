<template>
  <div class="min-h-screen" :class="{ 'rtl': store.isRTL }">
    <Header 
      :title="title" 
      :facebook="facebook"
      :instagram="instagram"
      :tiktok="tiktok"
      @menu-toggle="handleMenuToggle" 
    />

    <main class="container mx-auto px-4 py-12">
      <div class="max-w-4xl mx-auto text-center mb-16 rtl:text-right">
        <h1 class="text-4xl font-bold mb-4">{{ store.t('categories') }}</h1>
        <p class="text-muted-foreground text-lg">
          {{ store.t('categoriesDescription') }}
        </p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
        <div 
          v-for="category in categories" 
          :key="category.id"
          class="group relative overflow-hidden rounded-xl bg-card border border-border cursor-pointer transition-all hover:shadow-2xl hover:-translate-y-1"
          @click="navigate(category.id)"
        >
          <div class="aspect-[16/9] overflow-hidden">
            <img 
              :src="category.image_url || '/api/placeholder/800/450'" 
              :alt="store.locale === 'ar' ? (category.name_ar || category.name) : (category.name_en || category.name)"
              class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
            />
          </div>
          <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent flex flex-col justify-end p-6 rtl:text-right">
            <h2 class="text-2xl font-bold text-white mb-2">{{ store.locale === 'ar' ? (category.name_ar || category.name) : (category.name_en || category.name) }}</h2>
            <div class="flex items-center text-white/80 group-hover:text-white transition-colors">
              <span class="text-sm font-medium rtl:ml-2 ltr:mr-2">{{ store.t('exploreCollection') }}</span>
              <svg class="h-4 w-4 transition-transform group-hover:translate-x-1 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </main>

    <Sitemap 
      :categories="categories"
      :phone="phone"
      :email="email"
      :facebook="facebook"
      :instagram="instagram"
      :tiktok="tiktok"
      :address="address"
    />
    <Footer />
    <FloatingButtons :whatsapp-number="phone" :is-r-t-l="store.isRTL" />
  </div>
</template>

<script>
import Header from '../Components/Website/Header.vue'
import Sitemap from '../Components/Website/Sitemap.vue'
import Footer from '../Components/Website/Footer.vue'
import FloatingButtons from '../Components/Website/FloatingButtons.vue'
import { useStore } from '@/stores/store'

export default {
  components: {
    Header,
    Sitemap,
    Footer,
    FloatingButtons
  },
  props: {
    categories: Array,
    title: String,
    phone: String,
    email: String,
    facebook: String,
    instagram: String,
    tiktok: String,
    address: String
  },
  setup() {
    const store = useStore()
    const navigate = (id) => {
      window.location.href = route('shop', { category: id })
    }

    const handleMenuToggle = () => {

    }

    return {
      store,
      navigate,
      handleMenuToggle
    }
  }
}
</script>
