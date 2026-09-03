<template>
  <div class="min-h-screen" :class="{ rtl: store.isRTL }">
    <Header :title="title" />

    <main class="container mx-auto px-4 py-24 text-center">
      <div class="max-w-2xl mx-auto bg-card border border-border p-12 rounded-3xl shadow-2xl">
        <div class="w-20 h-20 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-8 shadow-inner">
          <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>

        <h1 class="text-4xl font-extrabold mb-4 text-amber-600">
          {{ store.locale === 'ar' ? 'جاري التحقق من الدفع' : 'Payment verification in progress' }}
        </h1>
        <p class="text-lg text-muted-foreground mb-10">
          {{ store.locale === 'ar' ? 'تم استلام نتيجة الدفع وسنتحقق منها تلقائيًا. لا تحاول الدفع مرة ثانية الآن.' : 'We received your payment result and will verify it automatically. Please do not pay again now.' }}
        </p>

        <div class="bg-amber-50/50 border border-amber-100 rounded-2xl p-6 mb-10 grid grid-cols-2 gap-4 rtl:text-right">
          <div>
            <p class="text-sm text-muted-foreground mb-1">{{ store.t('orderNumber') }}</p>
            <p class="font-bold text-lg">#{{ order.barcode || order.id }}</p>
          </div>
          <div class="text-right rtl:text-left">
            <p class="text-sm text-muted-foreground mb-1">{{ store.t('total') }}</p>
            <p class="font-bold text-lg text-amber-600">{{ formatPrice(order.total_price) }}</p>
          </div>
        </div>

        <a :href="route('homepage')" class="inline-block bg-primary text-white px-10 py-4 rounded-xl font-bold hover:shadow-xl transition-all-300">
          {{ store.t('backToHome') }}
        </a>
      </div>
    </main>

    <Sitemap :categories="categories" :phone="phone" :email="email" :facebook="facebook" :instagram="instagram" :tiktok="tiktok" :address="address" />
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
  name: 'PaymentPending',
  components: { Header, Sitemap, Footer, FloatingButtons },
  props: {
    order: Object,
    categories: Array,
    title: String,
    phone: String,
    email: String,
    facebook: String,
    instagram: String,
    tiktok: String,
    address: String
  },
  setup(props) {
    const store = useStore()
    const formatPrice = (value) => {
      const currency = props.order.curr_type || (props.order.country_id === 2 ? 'AED' : 'USD')
      const decimals = currency === 'SYP' ? 0 : 2
      return Number(value).toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals }) + ' ' + currency
    }

    return { store, formatPrice }
  }
}
</script>
