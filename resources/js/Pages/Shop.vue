<template>
  <div class="min-h-screen" :class="{ 'rtl': store.isRTL }">
    <Header
      :title="title"
      :facebook="facebook"
      :instagram="instagram"
      :tiktok="tiktok"
      @menu-toggle="handleMenuToggle"
    />

    <main class="container mx-auto px-4 py-8">
      <!-- Header -->
      <div class="mb-8 rtl:text-right">
        <h1 class="text-3xl font-bold mb-2">{{ store.t('products') }}</h1>
        <p class="text-muted-foreground">
            {{ store.t('discover') }}
        </p>
      </div>

      <!-- Search and Filters -->
      <div class="mb-8 space-y-4">
        <!-- Search bar -->
        <div class="relative max-w-md">
          <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground rtl:left-auto rtl:right-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            type="text"
            :placeholder="store.t('searchPlaceholder')"
            v-model="queryParams.search"
            @input="handleSearch"
            class="w-full pl-10 pr-4 rtl:pl-4 rtl:pr-10 border border-input bg-background rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
          />
        </div>

        <!-- Filter controls -->
        <div class="flex flex-wrap items-center gap-4">
          <button
            class="md:hidden flex items-center px-3 py-2 border border-border rounded-md hover:bg-accent hover:text-accent-foreground"
            @click="showFilters = !showFilters"
          >
            <svg class="h-4 w-4 mr-2 rtl:ml-2 rtl:mr-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            {{ store.locale === 'ar' ? 'فلاتر' : 'Filters' }} {{ activeFiltersCount > 0 ? `(${activeFiltersCount})` : '' }}
          </button>

          <div class="flex flex-wrap gap-4 rtl:space-x-reverse" :class="{ 'flex w-full mt-2': showFilters, 'hidden md:flex': !showFilters }">
            <!-- Category filter -->
            <select
              v-model="queryParams.category"
              @change="applyFilters"
              class="w-48 border border-input bg-background rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
            >
              <option :value="null">{{ store.t('allCategories') }}</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ store.locale === 'ar' ? (cat.name || cat.name) : (cat.name_en || cat.name) }}
              </option>
            </select>

            <!-- Sort -->
            <select
              v-model="queryParams.sort"
              @change="applyFilters"
              class="w-48 border border-input bg-background rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
            >
              <option value="id-desc">{{ store.t('newestFirst') }}</option>
              <option value="retail_price-asc">{{ store.t('priceLowToHigh') }}</option>
              <option value="retail_price-desc">{{ store.t('priceHighToLow') }}</option>
              <option value="name-asc">{{ store.locale === 'ar' ? 'الاسم: أ-ي' : 'Name: A-Z' }}</option>
            </select>

            <!-- Clear filters -->
            <button
              v-if="activeFiltersCount > 0"
              class="flex items-center px-3 py-2 border border-border rounded-md hover:bg-accent hover:text-accent-foreground"
              @click="clearFilters"
            >
              <svg class="h-4 w-4 mr-2 rtl:ml-2 rtl:mr-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              {{ store.t('clearFilters') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Products grid -->
      <div v-if="localProducts.length > 0">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
          <ProductCard
            v-for="product in transformedProducts"
            :key="product.id"
            :product="product"
          />
        </div>

        <div v-if="nextPageUrl" class="mt-12 flex justify-center">
          <button
            @click="loadMore"
            :disabled="loadingMore"
            class="px-8 py-3 bg-primary text-white hover:text-white rounded-full font-bold shadow-lg hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="loadingMore">{{ store.locale === 'ar' ? 'جاري التحميل...' : 'Loading...' }}</span>
            <span v-else>{{ store.locale === 'ar' ? 'مشاهدة المزيد' : 'Show More' }}</span>
          </button>
        </div>
      </div>

      <div v-else class="text-center py-20 bg-secondary/90 rounded-lg">
        <p class="text-white mb-4">{{ store.t('noProductsFound') }}</p>
        <button
          class="px-6 py-2 bg-primary text-white hover:text-white rounded-md hover:bg-primary/70"
          @click="clearFilters"
        >
          {{ store.t('clearFilters') }}
        </button>
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
    <FloatingButtons :whatsapp-number="whatsapp || phone" :is-r-t-l="store.isRTL" />
  </div>
</template>

<script>
import Header from '../Components/Website/Header.vue'
import ProductCard from '../Components/Website/ProductCard.vue'
import Sitemap from '../Components/Website/Sitemap.vue'
import Footer from '../Components/Website/Footer.vue'
import FloatingButtons from '../Components/Website/FloatingButtons.vue'
import { ref, computed } from 'vue'
import { useStore } from '@/stores/store'

export default {
  components: {
    Header,
    ProductCard,
    Sitemap,
    Footer,
    FloatingButtons
  },
  props: {
    products: Object,
    categories: Array,
    filters: Object,
    category: Object,
    title: String,
    phone: String,
    email: String,
    facebook: String,
    instagram: String,
    tiktok: String,
    whatsapp: String,
    address: String
  },
  setup(props) {
    const store = useStore()
    const showFilters = ref(false)
    const localProducts = ref([...(props.products.data || [])])
    const nextPageUrl = ref(props.products.next_page_url)
    const loadingMore = ref(false)

    const queryParams = ref({
      search: props.filters.search || '',
      category: props.category?.id || props.filters.category || null,
      sale: props.filters.sale === 'true' || props.filters.sale === true,
      field: props.filters.field || 'id',
      direction: props.filters.direction || 'desc',
      sort: props.filters.field ? `${props.filters.field}-${props.filters.direction}` : 'id-desc'
    })

    const transformedProducts = computed(() => {
      return localProducts.value.map(product => {

        return {
          id: product.id,
          name: store.locale === 'ar' ? (product.name || product.name) : (product.name_en || product.name),
          image: product.photo_url || product.image,
          retail_price: product.retail_price,
          price_before_discount: product.price_before_discount,
          price_before_discount_value: product.price_before_discount_value,
          sale_price: product.sale_price,
          final_price_value: product.final_price_value,
          wholesale_price_value: product.wholesale_price_value,
          isNew: false,
          colors: product.colors || [],
          sizes: product.sizes || [],
          rate: 4 + Math.random(),
          reviews_count: Math.floor(Math.random() * 50) + 10,
          formatted_wholesale_price: product.formatted_wholesale_price || ''
        }
      })
    })

    const loadMore = async () => {
      if (!nextPageUrl.value || loadingMore.value) return

      loadingMore.value = true
      try {
        const response = await axios.get(nextPageUrl.value)
        localProducts.value.push(...(response.data.data || []))
        nextPageUrl.value = response.data.next_page_url
      } catch (error) {
        console.error('Error loading more products:', error)
      } finally {
        loadingMore.value = false
      }
    }

    const activeFiltersCount = computed(() => {
      let count = 0
      if (queryParams.value.search) count++
      if (queryParams.value.category) count++
      return count
    })

    const handleSearch = () => {
      debounceSearch()
    }

    let searchTimeout
    const debounceSearch = () => {
      clearTimeout(searchTimeout)
      searchTimeout = setTimeout(() => {
        applyFilters()
      }, 500)
    }

    const applyFilters = () => {
      const parts = queryParams.value.sort.split('-')
      const field = parts[0]
      const direction = parts[1]

      const params = {
        search: queryParams.value.search,
        category: queryParams.value.category,
        sale: queryParams.value.sale || null,
        field: field,
        direction: direction
      }

      Object.keys(params).forEach(key => (params[key] == null || params[key] === '' || params[key] === false) && delete params[key])

      const url = new URL(window.location.origin + '/shop')
      Object.entries(params).forEach(([key, value]) => url.searchParams.set(key, value))
      window.location.href = url.toString()
    }

    const clearFilters = () => {
      window.location.href = route('shop')
    }

    const handleMenuToggle = () => {

    }

    return {
      store,
      showFilters,
      queryParams,
      localProducts,
      transformedProducts,
      activeFiltersCount,
      handleSearch,
      applyFilters,
      clearFilters,
      handleMenuToggle,
      loadMore,
      nextPageUrl,
      loadingMore
    }
  }
}
</script>
