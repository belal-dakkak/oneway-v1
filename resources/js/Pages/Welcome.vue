<template>
  <div class="min-h-screen" :class="{ 'rtl': store.isRTL }">
    <Header
      :title="title"
      :facebook="facebook"
      :instagram="instagram"
      :tiktok="tiktok"
      @menu-toggle="handleMenuToggle"
    />

    <main>
      <!-- Hero Banner -->
      <HeroBanner :slides="heroSlides" />

      <!-- Categories Slider -->
      <CategoriesSlider
        :categories="categoriesWithIcons"
        @category-click="handleCategoryClick"
      />

      <!-- Product Carousels -->

      <ProductCarousel
        v-if="newProducts.length"
        :products="transformList(newProducts)"
        :title="store.t('newProducts')"
        :label="store.t('newLabel')"
      />

      <div v-if="offerProducts.length" class="pb-12 text-center">
        <ProductCarousel
          :products="transformList(offerProducts)"
          :title="store.t('offers')"
          :label="store.t('offerLabel')"
        />
        <div class="mt-8">
          <a
            :href="route('shop', { sale: 'true' })"
            class="inline-block bg-[#c20000] hover:bg-white text-white hover:text-[#c20000] font-bold py-3 px-10 rounded-md transition-all border-2 border-[#c20000] shadow-lg hover:shadow-xl"
          >
            {{ store.t('showAllOffers') }}
          </a>
        </div>
      </div>

      <div v-if="randomProducts.length" class="pb-12 text-center">
        <ProductCarousel
          :products="transformList(randomProducts)"
          :title="store.t('allProducts')"
        />
        <div class="mt-8">
          <a
            :href="route('shop')"
            class="inline-block bg-[#c20000] hover:bg-white text-white hover:text-[#c20000] font-bold py-3 px-10 rounded-md transition-all border-2 border-[#c20000] shadow-lg hover:shadow-xl"
          >
            {{ store.t('showAllProducts') }}
          </a>
        </div>
      </div>

      <!-- Additional sections -->
      <section class="py-12 bg-background border-t border-border/50">
        <div class="container mx-auto px-4 text-center">
          <h2 class="text-3xl font-bold mb-4">{{ store.t('whyChooseOneway') }}</h2>
          <p class="text-muted-foreground max-w-2xl mx-auto mb-8">
            {{ store.t('whyChooseOnewayDesc') }}
          </p>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
              <div class="w-16 h-16 bg-accent/50 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-2xl">🚚</span>
              </div>
              <h3 class="font-semibold mb-2">{{ store.t('freeShipping') }}</h3>
              <p class="text-sm text-muted-foreground">{{ store.t('freeShippingDesc') }}</p>
            </div>
            <div class="text-center">
              <div class="w-16 h-16 bg-accent/50 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-2xl">🔄</span>
              </div>
              <h3 class="font-semibold mb-2">{{ store.t('easyReturns') }}</h3>
              <p class="text-sm text-muted-foreground">{{ store.t('easyReturnsDesc') }}</p>
            </div>
            <div class="text-center">
              <div class="w-16 h-16 bg-accent/50 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-2xl">🎁</span>
              </div>
              <h3 class="font-semibold mb-2">{{ store.t('memberBenefits') }}</h3>
              <p class="text-sm text-muted-foreground">{{ store.t('memberBenefitsDesc') }}</p>
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- Sitemap -->
    <Sitemap
      :categories="categories"
      :phone="phone"
      :email="email"
      :facebook="facebook"
      :instagram="instagram"
      :tiktok="tiktok"
      :address="address"
    />

    <!-- Footer -->
    <Footer />
    <FloatingButtons :whatsapp-number="whatsapp" :is-r-t-l="store.isRTL" />
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue'
import Header from '../Components/Website/Header.vue'
import HeroBanner from '../Components/Website/HeroBanner.vue'
import CategoriesSlider from '../Components/Website/CategoriesSlider.vue'
import ProductCarousel from '../Components/Website/ProductCarousel.vue'
import Sitemap from '../Components/Website/Sitemap.vue'
import Footer from '../Components/Website/Footer.vue'
import FloatingButtons from '../Components/Website/FloatingButtons.vue'
import { useStore } from '@/stores/store'

export default {
  components: {
    Header,
    HeroBanner,
    CategoriesSlider,
    ProductCarousel,
    Sitemap,
    Footer,
    FloatingButtons
  },
  props: {
    featuredProducts: {
      type: Array,
      default: () => []
    },
    newProducts: {
      type: Array,
      default: () => []
    },
    offerProducts: {
      type: Array,
      default: () => []
    },
    randomProducts: {
      type: Array,
      default: () => []
    },
    categories: {
      type: Array,
      default: () => []
    },
    sliders: {
      type: Array,
      default: () => []
    },
    mobileSliders: {
      type: Array,
      default: () => []
    },
    title: {
      type: String,
      default: 'One Way'
    },
    phone: {
      type: String,
      default: ''
    },
    tiktok: {
      type: String,
      default: ''
    },
    facebook: {
      type: String,
      default: ''
    },
    instagram: {
      type: String,
      default: ''
    },
    whatsapp: {
      type: String,
      default: ''
    },
    address: {
      type: String,
      default: ''
    },
    email: {
      type: String,
      default: ''
    }
  },
  setup() {
    const store = useStore()
    const isMobile = ref(false)

    const checkMobile = () => {
      isMobile.value = window.innerWidth < 768
    }

    onMounted(() => {
      checkMobile()
      window.addEventListener('resize', checkMobile)
    })

    onUnmounted(() => {
      window.removeEventListener('resize', checkMobile)
    })

    return { store, isMobile }
  },
  computed: {
    heroSlides() {
      const activeSliders = (this.isMobile && this.mobileSliders.length > 0) 
        ? this.mobileSliders 
        : this.sliders;

      if (activeSliders.length === 0) {
        return [{
          id: 1,
          subtitle: this.store.t('newCollection'),
          title: this.title || 'One Way',
          description: this.store.t('newCollectionDesc'),
          image: '/api/placeholder/1200/600',
          ctaText: this.store.t('shopNow'),
          ctaLink: route('shop')
        }]
      }

      return activeSliders.map((slider, index) => ({
        id: slider.id,
        subtitle: this.store.t('newCollection'),
        title: this.title || 'One Way',
        description: this.store.t('newCollectionDesc'),
        image: slider.image_url || slider.image,
        ctaText: this.store.t('shopNow'),
        ctaLink: route('shop')
      }))
    },

    categoriesWithIcons() {
      const defaultIcons = ['👗', '👔', '👖', '🧥', '🧶', '👜', '👠', '🏃']

      return this.categories.map((cat, index) => ({
        id: cat.id,
        name: this.store.locale === 'ar' ? (cat.name || cat.name) : (cat.name_en || cat.name),
        icon: defaultIcons[index % defaultIcons.length],
        image: cat.image_url || cat.image
      }))
    }
  },
  methods: {
    handleMenuToggle() {

    },
    handleCategoryClick(category) {
      window.location.href = route('shop', { category: category.id })
    },
    transformProduct(item) {
      const product = item;
      return {
        id: product.id,
        name: this.store.locale === 'ar' ? (product.name || product.name) : (product.name_en || product.name),
        image: product.photo_url || product.image_url || product.image || '/api/placeholder/300/400',
        retail_price: product.retail_price,
        price_before_discount: product.price_before_discount,
        price_before_discount_value: product.price_before_discount_value,
        sale_price: product.sale_price,
        final_price_value: product.final_price_value,
        wholesale_price_value: product.wholesale_price_value,
        isNew: false,
        colors: product.colors || [],
        rate: 4 + Math.random(),
        reviews_count: Math.floor(Math.random() * 50) + 10,
        formatted_wholesale_price: product.formatted_wholesale_price || ''
      }
    },
    transformList(list) {
      return list.map(item => this.transformProduct(item))
    }
  }
}
</script>
