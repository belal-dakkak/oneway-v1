<template>
  <div class="min-h-screen bg-gray-50" :class="{ 'rtl': store.isRTL }">
    <Header
      :title="title"
      :facebook="facebook"
      :instagram="instagram"
      :tiktok="tiktok"
    />

    <main class="py-12 md:py-20">
      <div class="container mx-auto px-4 max-w-4xl">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 md:p-12">
          <h1 class="text-3xl md:text-4xl font-extrabold mb-8 text-black uppercase tracking-tight">
            {{ store.t('refundPolicyTitle') }}
          </h1>
          <div class="prose prose-sm md:prose-base max-w-none text-gray-700 leading-relaxed policy-content" v-html="store.t('refundPolicyContent')">
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
    return { store }
  }
}
</script>

<style scoped>
.policy-content :deep(h3) {
  font-weight: 700;
  font-size: 1.25rem;
  margin-top: 2rem;
  margin-bottom: 1rem;
  color: black;
}
.policy-content :deep(p) {
  margin-bottom: 1rem;
}
.policy-content :deep(strong) {
  color: #c20000;
}
</style>
