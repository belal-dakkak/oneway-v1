<template>
  <div class="min-h-screen" :class="{ 'rtl': store.isRTL }">
    <Header :title="title" />

    <main class="container mx-auto px-4 py-12">
      <div class="flex flex-col lg:flex-row gap-12">
        <!-- Checkout Form -->
        <div class="lg:w-2/3">
          <section class="mb-10">
            <h2 class="text-3xl font-bold mb-8 rtl:text-right">{{ store.t('shippingInfo') }}</h2>
            <div class="grid md:grid-cols-2 gap-6 rtl:text-right">
              <div class="space-y-2">
                <label class="text-sm font-medium">{{ store.t('firstName') }}</label>
                <input v-model="form.first_name" type="text" class="w-full border-primary rounded-lg px-4 py-3 focus:ring-1 focus:ring-red-500" placeholder="Ahmad">
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium">{{ store.t('lastName') }}</label>
                <input v-model="form.last_name" type="text" class="w-full border-primary rounded-lg px-4 py-3 focus:ring-1 focus:ring-red-500" placeholder="Dakkak">
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium">{{ store.t('emailAddress') }}</label>
                <input v-model="form.email" type="email" class="w-full border-primary rounded-lg px-4 py-3 focus:ring-2 focus:ring-red-500" placeholder="ahmad@example.com">
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium">{{ store.t('phone') }}</label>
                <div class="flex gap-2" dir="ltr">
                  <select v-model="form.country_code" disabled class="w-32 border-primary rounded-lg px-2 py-3 focus:ring-2 focus:ring-red-500 bg-gray-100">
                    <option v-for="option in countryOptions" :key="option.code" :value="option.code">
                      {{ option.flag }} {{ option.code }}
                    </option>
                  </select>
                  <input dir="ltr" v-model="form.phone" type="text" class="flex-1 border-primary rounded-lg px-4 py-3 focus:ring-2 focus:ring-red-500" :placeholder="form.country_code === '+971' ? '50 123 4567' : '70 123 456'">
                </div>
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium">{{ store.t('buildingName') }}</label>
                <input v-model="form.building_name" type="text" class="w-full border-primary rounded-lg px-4 py-3 focus:ring-1 focus:ring-red-500" placeholder="One Way Tower">
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium">{{ store.t('flatNumber') }}</label>
                <input v-model="form.flat_number" type="text" class="w-full border-primary rounded-lg px-4 py-3 focus:ring-1 focus:ring-red-500" placeholder="Apt 402">
              </div>
              <div class="md:col-span-2 space-y-2">
                <label class="text-sm font-medium">{{ store.t('address') }}</label>
                <textarea v-model="form.address" rows="3" class="w-full border-primary rounded-lg px-4 py-3 focus:ring-2 focus:ring-red-500 resize-none" placeholder="Street name, landmark, etc."></textarea>
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium">{{ store.t('city') }}</label>
                <input v-model="form.city" type="text" class="w-full border-primary rounded-lg px-4 py-3 focus:ring-2 focus:ring-red-500" placeholder="Dubai">
              </div>
            </div>
          </section>

          <section>
            <h2 class="text-3xl font-bold mb-8 rtl:text-right">{{ store.t('paymentMethod') }}</h2>
            <div class="grid sm:grid-cols-2 gap-4 rtl:text-right">
              <label class="relative flex items-center p-6 bg-secondary rounded-xl border-2 cursor-pointer transition-all" :class="form.payment_method === 'cod' ? 'border-primary ring-2 ring-primary/20' : 'border-transparent'">
                <input v-model="form.payment_method" type="radio" value="cod" class="sr-only">
                <div class="w-6 h-6 rounded-full text-white border-2 flex items-center justify-center rtl:ml-4 ltr:mr-4" :class="form.payment_method === 'cod' ? 'border-white' : 'border-muted-foreground'">
                  <div v-if="form.payment_method === 'cod'" class="w-3 h-3 bg-white rounded-full"></div>
                </div>
                <div>
                  <p class="font-bold text-white">{{ store.t('cashOnDelivery') }}</p>
                  <p class="text-xs text-white">{{ store.locale === 'ar' ? 'ادفع نقداً عند استلام طلبك' : 'Pay in cash when your order arrives' }}</p>
                </div>
              </label>

              <label v-if="store.country !== 'SY'" class="relative flex items-center p-6 bg-secondary rounded-xl border-2 cursor-pointer transition-all" :class="form.payment_method === 'card' ? 'border-primary ring-2 ring-primary/20' : 'border-transparent'">
                <input v-model="form.payment_method" type="radio" value="card" class="sr-only">
                <div class="w-6 h-6 rounded-full text-white border-2 flex items-center justify-center rtl:ml-4 ltr:mr-4" :class="form.payment_method === 'card' ? 'border-white' : 'border-muted-foreground'">
                  <div v-if="form.payment_method === 'card'" class="w-3 h-3 bg-white rounded-full"></div>
                </div>
                <div>
                  <p class="font-bold text-white">{{ store.t('creditCard') }}</p>
                  <p class="text-xs text-white">{{ store.locale === 'ar' ? 'ادفع بالبطاقة ووفر الرسوم' : 'Pay by card and save fees' }}</p>
                </div>
              </label>
            </div>

            <!-- Protection Info Sections -->
            <div class="mt-12 space-y-10 border-t pt-10">
              <!-- Card Protection -->
              <div class="flex gap-4 items-start">
                <div class="flex-shrink-0 w-12 h-12 bg-muted rounded-xl p-2.5">
                  <img src="/assets/protect.png.slim.avif" alt="Protect" class="w-full h-full object-contain">
                </div>
                <div class="space-y-2">
                  <h4 class="font-bold text-lg md:text-xl">{{ store.t('cardProtectionTitle') }}</h4>
                  <ul class="text-sm text-muted-foreground space-y-1.5 list-disc ltr:pl-5 rtl:pr-5">
                    <li>{{ store.t('cardProtectionDesc1') }}</li>
                    <li>{{ store.t('cardProtectionDesc2') }}</li>
                    <li>{{ store.t('cardProtectionDesc3') }}</li>
                    <li>{{ store.t('cardProtectionDesc4') }}</li>
                  </ul>
                </div>
              </div>

              <!-- Secure Privacy -->
              <div class="flex gap-4 items-start">
                <div class="flex-shrink-0 w-12 h-12 bg-muted rounded-xl p-2.5">
                  <img src="/assets/secure.png.slim.avif" alt="Secure" class="w-full h-full object-contain">
                </div>
                <div class="space-y-3">
                  <h4 class="font-bold text-lg md:text-xl">{{ store.t('privacyTitle') }}</h4>
                  <p class="text-sm text-muted-foreground leading-relaxed">
                    {{ store.t('privacyDesc') }}
                  </p>
                  <a href="#" class="inline-flex items-center text-sm font-bold text-primary hover:text-primary/80 transition-colors group">
                    {{ store.t('learnMore') }}
                    <svg class="w-4 h-4 ml-1 rtl:mr-1 rtl:rotate-180 transform transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                  </a>
                </div>
              </div>

              <!-- Purchase Protection -->
              <div class="flex gap-4 items-start">
                <div class="flex-shrink-0 w-12 h-12 bg-muted rounded-xl p-2.5">
                  <img src="/assets/purchase.avif" alt="Purchase" class="w-full h-full object-contain">
                </div>
                <div class="space-y-3">
                  <h4 class="font-bold text-lg md:text-xl">{{ store.t('purchaseProtectionTitle') }}</h4>
                  <p class="text-sm text-muted-foreground leading-relaxed">
                    {{ store.t('purchaseProtectionDesc') }}
                  </p>
                  <a href="#" class="inline-flex items-center text-sm font-bold text-primary hover:text-primary/80 transition-colors group">
                    {{ store.t('learnMore') }}
                    <svg class="w-4 h-4 ml-1 rtl:mr-1 rtl:rotate-180 transform transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                  </a>
                </div>
              </div>
            </div>
          </section>
        </div>

        <!-- Order Summary Stick -->
        <div class="lg:w-1/3">
          <div class="bg-card border border-border p-8 rounded-2xl shadow-xl h-fit sticky top-24 rtl:text-right">
            <h2 class="text-2xl font-bold mb-6 border-b pb-4">{{ store.t('summary') }}</h2>

            <div class="space-y-4 max-h-[40vh] overflow-y-auto mb-6 scrollbar-hide">
              <div v-for="item in store.cart" :key="item.id" class="flex justify-between items-center text-sm">
                <div class="flex items-center space-x-3 rtl:space-x-reverse">
                  <span class="w-10 h-10 bg-muted rounded flex-shrink-0">
                    <img :src="item.product.image" class="w-full h-full object-cover rounded">
                  </span>
                  <span class="line-clamp-1 font-medium">{{ item.product.name }} x {{ item.quantity }}</span>
                </div>
                <span class="font-bold">{{ store.formatPrice(store.getItemPrice(item) * item.quantity) }}</span>
              </div>
            </div>

            <div class="space-y-4 mb-8 pt-4 border-t">
              <div class="flex justify-between text-muted-foreground">
                <span>{{ store.t('subtotal') }}</span>
                <span>{{ store.formatPrice(store.getCartTotal) }}</span>
              </div>
              <div class="flex justify-between text-muted-foreground">
                <span>{{ store.t('shipping') }}</span>
                <span v-if="shippingFee === 0" class="text-green-600 font-medium">{{ store.t('free') }}</span>
                <span v-else class="font-medium">{{ store.formatPrice(shippingFee) }}</span>
              </div>
              <div v-if="codFeeValue > 0" class="flex justify-between text-muted-foreground">
                <span>{{ store.t('codFee') }}</span>
                <span class="font-medium">{{ store.formatPrice(codFeeValue) }}</span>
              </div>
              <div v-if="shippingFee > 0 && form.payment_method === 'cod'" class="text-[10px] text-muted-foreground rtl:text-left ltr:text-right mt-[-8px]">
                {{ shippingMessage }}
              </div>
              <div class="flex justify-between text-xl font-extrabold pt-4 border-t border-dashed">
                <span>{{ store.t('total') }}</span>
                <span class="text-primary">{{ store.formatPrice(totalWithShipping) }}</span>
              </div>
            </div>

            <!-- COD Promotion Message -->
            <div class="mt-6 p-4 bg-primary/5 rounded-xl border border-primary/10">
              <p class="text-sm font-medium text-primary leading-relaxed">
                {{ store.t('codPromoMessage') }}
              </p>
            </div>

            <button
              @click="submitOrder"
              :disabled="loading"
              class="w-full bg-primary text-primary-foreground font-bold py-4 rounded-xl hover:bg-primary/90 transition-all shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="loading" class="flex text-white items-center justify-center">
                <svg class="animate-spin h-5 w-5 mr-3" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
              </span>
              <span class="text-white" v-else>{{ store.t('placeOrder') }}</span>
            </button>
          </div>
        </div>
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
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useStore } from '@/stores/store'
import { Inertia } from '@inertiajs/inertia'

export default {
  components: {
    Header,
    Sitemap,
    Footer,
    FloatingButtons
  },
  props: {
    categories: Array, title: String, phone: String, email: String, facebook: String, instagram: String, tiktok: String, address: String
  },
  setup() {
    const store = useStore()
    const loading = ref(false)
    const countryOptions = [
      { code: '+971', name: 'UAE', flag: '🇦🇪' },
      { code: '+961', name: 'Lebanon', flag: '🇱🇧' },
      { code: '+963', name: 'Syria', flag: '🇸🇾' }
    ]

    const form = reactive({
      first_name: '',
      last_name: '',
      email: '',
      phone: '',
      country_code: store.country === 'SY' ? '+963' : (store.country === 'LB' ? '+961' : '+971'),
      address: '',
      city: '',
      building_name: '',
      flat_number: '',
      payment_method: store.country === 'SY' ? 'cod' : 'card',
      items: store.cart
    })

    onMounted(() => {
      const savedInfo = localStorage.getItem('shipping_info')
      if (savedInfo) {
        try {
          const data = JSON.parse(savedInfo)
          // Pre-fill form from localStorage
          const fields = ['first_name', 'last_name', 'email', 'phone', 'country_code', 'address', 'city', 'building_name', 'flat_number']
          fields.forEach(field => {
            if (data[field] !== undefined) {
              form[field] = data[field]
            }
          })
        } catch (e) {
          console.error('Failed to parse shipping_info from localStorage', e)
        }
      }
      form.country_code = store.country === 'SY' ? '+963' : (store.country === 'LB' ? '+961' : '+971')
      if (store.country === 'SY') form.payment_method = 'cod'
    })

    watch(() => store.country, (country) => {
      form.country_code = country === 'SY' ? '+963' : (country === 'LB' ? '+961' : '+971')
      if (country === 'SY') form.payment_method = 'cod'
    })

    watch(() => {
      const { first_name, last_name, email, phone, country_code, address, city, building_name, flat_number } = form
      return { first_name, last_name, email, phone, country_code, address, city, building_name, flat_number }
    }, (newVal) => {
      localStorage.setItem('shipping_info', JSON.stringify(newVal))
    }, { deep: true })

    const shippingFee = computed(() => {
      let cartTotal = store.getCartTotal
      
      if (isNaN(cartTotal) || cartTotal === null || cartTotal === undefined) {
        cartTotal = 0
      }
      
      const threshold = store.commerce.free_shipping_threshold_usd
      if (threshold !== null && threshold !== '' && cartTotal >= store.convertFromUsd(threshold)) return 0
      return store.convertFromUsd(store.commerce.shipping_fee_usd || 0)
    })

    const codFeeValue = computed(() => {
      if (form.payment_method === 'cod') {
        let cartTotal = store.getCartTotal
        if (isNaN(cartTotal) || cartTotal === null || cartTotal === undefined) {
          cartTotal = 0
        }
        const percentage = Number(store.commerce.cod_fee_percent || 0)
        return cartTotal * (percentage / 100)
      }
      return 0
    })

    const totalWithShipping = computed(() => {
      let cartTotal = store.getCartTotal
      if (isNaN(cartTotal) || cartTotal === null || cartTotal === undefined) {
        cartTotal = 0
      }
      return cartTotal + shippingFee.value + codFeeValue.value
    })

    const shippingMessage = computed(() => {
      const threshold = store.convertFromUsd(store.commerce.free_shipping_threshold_usd || 0)
      return store.isRTL
        ? `تُطبّق رسوم الشحن للطلبات الأقل من ${store.formatPrice(threshold)}.`
        : `Shipping applies to orders below ${store.formatPrice(threshold)}.`
    })

    const submitOrder = () => {
      if (!form.first_name || !form.last_name || !form.phone || !form.email || !form.address || !form.city || !form.building_name || !form.flat_number) {
        alert(store.locale === 'ar' ? 'يرجى ملء جميع الحقول المطلوبة' : 'Please fill in all required fields')
        return
      }

      // Merchant Minimum Quantity Validation (12 items)
      if (store.isMerchant) {
        const hasLowQty = store.cart.some(item => item.quantity < 12)
        if (hasLowQty) {
          alert(store.t('merchantMinQuantityError'))
          return
        }
      }

      // Phone Validation: Use selected country code + cleaned number
      const phoneClean = String(form.phone || '').replace(/[\s\-\(\)]/g, '')
      const fullPhone = form.country_code + phoneClean

      const uaeRegex = /^(?:\+971)?5[02456]\d{7}$/
      const lebanonRegex = /^(?:\+961)?(?:3|70|71|76|78|79|81)\d{6}$/
      const syriaRegex = /^(?:\+963)?9[345689]\d{7}$/

      let isValid = false
      if (form.country_code === '+971') isValid = uaeRegex.test(fullPhone)
      else if (form.country_code === '+961') isValid = lebanonRegex.test(fullPhone)
      else if (form.country_code === '+963') isValid = syriaRegex.test(fullPhone)

      if (!isValid) {
        alert(store.t('phoneValidationError'))
        return
      }

      loading.value = true
      // Send the full phone number to the backend
      const submissionForm = { ...form, items: store.cart, phone: fullPhone, currency: store.currency }

      Inertia.post('/checkout', submissionForm, {
        onSuccess: () => {
          // Cart will be cleared on OrderSuccess page
        },
        onError: (errors) => {
          loading.value = false
          console.error(errors)
          alert('Failed to place order. Please try again.')
        }
      })
    }

    return { store, form, loading, submitOrder, shippingFee, codFeeValue, totalWithShipping, shippingMessage, countryOptions }
  }
}
</script>
