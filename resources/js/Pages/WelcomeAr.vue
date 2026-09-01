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

      <!-- Featured Products -->
      <FeaturedProducts :products="transformedProducts" />

      <!-- Additional sections -->
      <section class="py-12 bg-secondary">
        <div class="container mx-auto px-4 text-center">
          <h2 class="text-3xl font-bold mb-4">{{ store.t('whyChooseOneway') || 'Why Choose Oneway?' }}</h2>
          <p class="text-muted-foreground max-w-2xl mx-auto mb-8">
            {{ store.t('whyChooseOnewayDesc') || 'Discover premium quality fashion with our carefully curated collection. From timeless classics to modern trends, we bring you the best in contemporary style.' }}
          </p>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
              <div class="w-16 h-16 bg-accent rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-2xl">🚚</span>
              </div>
              <h3 class="font-semibold mb-2">{{ store.t('freeShipping') }}</h3>
              <p class="text-sm text-muted-foreground">{{ store.t('freeShippingDesc') || 'On orders over $100' }}</p>
            </div>
            <div class="text-center">
              <div class="w-16 h-16 bg-accent rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-2xl">🔄</span>
              </div>
              <h3 class="font-semibold mb-2">{{ store.t('easyReturns') }}</h3>
              <p class="text-sm text-muted-foreground">{{ store.t('easyReturnsDesc') || '30-day return policy' }}</p>
            </div>
            <div class="text-center">
              <div class="w-16 h-16 bg-accent rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-2xl">🎁</span>
              </div>
              <h3 class="font-semibold mb-2">{{ store.t('memberBenefits') }}</h3>
              <p class="text-sm text-muted-foreground">{{ store.t('memberBenefitsDesc') || 'Exclusive offers and rewards' }}</p>
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
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue'
import Header from '../Components/Website/Header.vue'
import HeroBanner from '../Components/Website/HeroBanner.vue'
import CategoriesSlider from '../Components/Website/CategoriesSlider.vue'
import FeaturedProducts from '../Components/Website/FeaturedProducts.vue'
import Sitemap from '../Components/Website/Sitemap.vue'
import Footer from '../Components/Website/Footer.vue'
import { useStore } from '@/stores/store'

export default {
  components: {
    Header,
    HeroBanner,
    CategoriesSlider,
    FeaturedProducts,
    Sitemap,
    Footer
  },
  props: {
    products: {
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
          subtitle: this.store.t('newCollection') || 'New Collection',
          title: this.title || 'One Way',
          description: this.store.t('newCollectionDesc') || 'Discover our latest collection with exclusive designs and premium quality',
          image: '/api/placeholder/1200/600',
          ctaText: this.store.t('shopNow') || 'Shop Now',
          ctaLink: '/shop'
        }]
      }

      return activeSliders.map((slider, index) => ({
        id: slider.id,
        subtitle: this.store.t('newCollection') || 'New Collection',
        title: this.title || 'One Way',
        description: this.store.t('newCollectionDesc') || 'Discover our latest collection with exclusive designs and premium quality',
        image: slider.image_url || slider.image,
        ctaText: this.store.t('shopNow') || 'Shop Now',
        ctaLink: '/shop'
      }))
    },

    categoriesWithIcons() {
      const defaultIcons = ['👗', '👔', '👖', '🧥', '🧶', '👜', '👠', '🏃']
      
      return this.categories.map((cat, index) => ({
        id: cat.id,
        name: this.store.locale === 'ar' ? (cat.name_ar || cat.name) : (cat.name_en || cat.name),
        icon: defaultIcons[index % defaultIcons.length],
        image: cat.image_url || cat.image
      }))
    },

    transformedProducts() {
      return this.products.map(item => {
        const product = item;
        
        const originalPrice = product.formatted_price_before_discount || '$0';
        const currentPrice = product.final_price || '$0';
        
        const rawOriginal = parseFloat(product.price_before_discount || 0);
        const rawCurrent = parseFloat(product.retail_price || 0);
        const discountPercentage = rawOriginal > rawCurrent 
          ? Math.round(((rawOriginal - rawCurrent) / rawOriginal) * 100)
          : 0;

        return {
          id: product.id,
          name: this.store.locale === 'ar' ? (product.name_ar || product.name) : (product.name_en || product.name),
          image: product.photo_url || product.image_url || product.image || '/api/placeholder/300/400',
          originalPriceValue: rawOriginal,
          discountedPriceValue: rawCurrent,
          final_price_value: product.final_price_value || rawCurrent,
          discountPercentage: discountPercentage,
          isNew: false,
          colors: product.colors || [],
          rate: parseFloat(product.rate || 0),
          reviews_count: parseInt(product.reviews_count || 0),
          wholesale_price_value: product.wholesale_price_value || 0,
          formatted_wholesale_price: product.formatted_wholesale_price || ''
        }
      })
    }
  },
  methods: {
    handleMenuToggle() {

    },
    handleCategoryClick(category) {
      window.location.href = route('shop', { category: category.id })
    }
  }
}
</script>
<style scoped>

.rounded-custom{
    border-radius: 3rem;
}

.bg-custom{
    background-color: rgba(35,32,42,var(--tw-bg-opacity));
}

.text-header{
    color: rgba(194,198,221,var(--tw-text-opacity));
}

.bg-simplify{
    background-image: url("/assets/bg-simplify-section.svg");
    background-repeat: no-repeat;
    background-size: auto;
}

#about{
    background-image: url("/assets/heroimage.svg");
    background-repeat: no-repeat;
    background-size: auto;
    background-color: #000000;
}

#foundation, #featured{
    background-color: #000000;
}

#mission, #vision, #objective, #story, #choose, #partner{
    background-color: #201824;
}

#mission-area, #vision-area, #objective-area{
    background-color: #251d2a;
}

.contact-icon
{
    position: fixed;
    bottom: 20px;
    left: 25px;
}
</style>
