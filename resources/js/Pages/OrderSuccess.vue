<template>
  <div class="min-h-screen" :class="{ 'rtl': store.isRTL }">
    <Header :title="title" />

    <main class="container mx-auto px-4 py-24 text-center">
      <div class="max-w-2xl mx-auto bg-card border border-border p-12 rounded-3xl shadow-2xl">
        <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-8 shadow-inner">
          <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
          </svg>
        </div>

        <h1 class="text-5xl font-extrabold mb-4">{{ store.t('thankYou') }}</h1>
        <p class="text-xl text-muted-foreground mb-10">{{ store.t('orderSuccess') }}</p>

        <div class="bg-green-100/50 rounded-2xl p-6 mb-10 grid grid-cols-2 gap-4 rtl:text-right">
          <div>
            <p class="text-sm text-muted-foreground mb-1">{{ store.t('orderNumber') }}</p>
            <p class="font-bold text-lg">#{{ order.barcode || order.id }}</p>
          </div>
          <div class="text-right rtl:text-left">
            <p class="text-sm text-muted-foreground mb-1">{{ store.t('total') }}</p>
            <p class="font-bold text-lg text-green-600">{{ formatPrice(order.total_price) }}</p>
            <syp-equivalent v-if="orderDisplayCurrency" :usd="order.total_price" :display-currency="orderDisplayCurrency" />
          </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <a :href="route('homepage')" class="bg-primary text-white px-10 py-4 rounded-xl font-bold hover:text-gray-400 hover:shadow-xl transition-all-300 hover:-translate-y-1">
            {{ store.t('backToHome') }}
          </a>
          <a :href="route('shop')" class="bg-secondary text-white px-10 py-4 rounded-xl font-bold hover:bg-muted hover:text-gray-400 transition-all-300 hover:-translate-y-1">
            {{ store.t('continueShopping') }}
          </a>
        </div>
      </div>
    </main>

    <Sitemap :categories="categories" :phone="phone" :email="email" :facebook="facebook" :instagram="instagram" :tiktok="tiktok"      :address="address"
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
import { onMounted } from 'vue'
import { useStore } from '@/stores/store'

export default {
  components: {
    Header,
    Sitemap,
    Footer,
    FloatingButtons
  },
  props: {
    order: Object, categories: Array, title: String, phone: String, email: String, facebook: String, instagram: String, tiktok: String, address: String
  },
  setup(props) {
    const store = useStore()

    onMounted(() => {
      const countryCode = { 1: 'LB', 2: 'AE', 4: 'SY' }[props.order.country_id] || store.country
      localStorage.setItem(`cart_${countryCode}`, '[]')
      if (store.country === countryCode) store.clearCart()
    })

    const formatPrice = (value) => {
      const currency = props.order.curr_type || (props.order.country_id === 2 ? 'AED' : 'USD')
      const decimals = currency === 'SYP' ? 0 : 2
      return Number(value).toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals }) + ' ' + currency
    }

    const orderDisplayCurrency = {
      code: props.order.display_currency,
      rate: props.order.display_rate,
      decimals: 0,
      approximate: true,
    }

    return { store, formatPrice, orderDisplayCurrency }
  }
}
</script>
