<template>
  <div class="min-h-screen" :class="{ 'rtl': store.isRTL }">
    <Header
      :title="title"
      :facebook="facebook"
      :instagram="instagram"
      :tiktok="tiktok"
    />

    <main class="container mx-auto px-4 py-12">
      <h1 class="text-4xl font-bold mb-10 rtl:text-right">{{ store.t('shoppingCart') }}</h1>

      <div v-if="store.cart.length > 0" class="grid lg:grid-cols-3 gap-10">
        <!-- Cart Items -->
        <div class="lg:col-span-2 space-y-6">
          <div v-for="item in store.cart" :key="item.id" class="flex flex-col sm:flex-row items-center bg-card border border-border p-4 rounded-xl shadow-sm hover:shadow-md transition-shadow">
            <div class="w-32 h-32 flex-shrink-0 bg-muted rounded-lg overflow-hidden mb-4 sm:mb-0 rtl:sm:ml-6 ltr:sm:mr-6">
              <img :src="item.product.image || '/api/placeholder/150/150'" :alt="item.product.name" class="w-full h-full object-cover">
            </div>

            <div class="flex-1 flex flex-col justify-between rtl:text-right">
              <div class="flex justify-between items-start">
                <div>
                  <h3 class="text-xl font-bold mb-1">{{ item.product.name }}</h3>
                  <p class="text-sm text-muted-foreground">{{ store.t('color') }}: {{ item.color.name }} | {{ store.t('size') }}: {{ item.size }}</p>
                </div>
                <button @click="store.removeFromCart(item.id)" class="text-muted-foreground hover:text-destructive transition-colors p-1">
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>

              <div class="flex flex-wrap items-center justify-between mt-4">
                <div class="flex flex-col gap-2">
                  <div class="flex items-center border border-border rounded-md bg-secondary/50 self-start">
                    <button @click="store.updateQuantity(item.id, item.quantity - 1)" class="p-2 hover:bg-accent transition-colors">
                      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                      </svg>
                    </button>
                    <span class="w-10 text-center font-bold">{{ item.quantity }}</span>
                    <button
                      @click="store.updateQuantity(item.id, item.quantity + 1)"
                      class="p-2 hover:bg-accent transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                      :disabled="item.stock !== undefined && item.quantity >= item.stock"
                    >
                      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                      </svg>
                    </button>
                  </div>
                  <!-- Individual item warnings are removed as per new "total 20" requirement -->
                </div>

                <div class="flex items-center gap-4">
                  <!-- Max stock reached indicator -->
                  <span v-if="item.stock !== undefined && item.quantity >= item.stock" class="text-xs font-semibold text-amber-600">
                    {{ store.isRTL ? 'الحد الأقصى' : 'Max' }}
                  </span>
                  <div class="text-lg font-bold text-primary">
                    {{ store.formatPrice(store.getItemPrice(item) * item.quantity) }}
                    <syp-equivalent :usd="store.getItemPrice(item) * item.quantity" />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="flex justify-end pt-4">
             <a :href="route('shop')" class="text-primary font-medium hover:underline flex items-center">
               <svg class="h-4 w-4 rtl:ml-2 ltr:mr-2 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
               </svg>
               {{ store.t('continueShopping') }}
             </a>
          </div>
        </div>

        <!-- Summary -->
        <div class="bg-card border border-border p-8 rounded-2xl shadow-lg h-fit sticky top-24 rtl:text-right">
          <h2 class="text-2xl font-bold mb-6 border-b pb-4">{{ store.t('summary') }}</h2>
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
                <div v-if="shippingFee > 0" class="text-muted-foreground rtl:text-left ltr:text-right mt-[-8px]">
                    {{ shippingMessage }}
                </div>
                <div class="flex justify-between text-xl font-extrabold pt-4 border-t border-dashed">
                    <span>{{ store.t('total') }}</span>
                    <span class="text-primary">
                      {{ store.formatPrice(totalWithShipping) }}
                      <syp-equivalent :usd="totalWithShipping" />
                    </span>
                </div>
            </div>
          <div v-if="store.isMerchant && store.getCartCount < 20" class="mb-6 p-4 bg-amber-500/10 border border-amber-500/20 rounded-xl text-amber-600 text-sm font-bold flex items-center gap-2">
             <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
             </svg>
             <span>{{ store.isRTL ? 'مطلوب إضافة 20 قطعة على الأقل كتاجر (الإجمالي حالياً: ' + store.getCartCount + ')' : 'Merchant requirement: 20 items total (Currently: ' + store.getCartCount + ')' }}</span>
          </div>

          <button
            @click="proceedToCheckout"
            class="w-full bg-primary text-white hover:text-white font-bold py-4 rounded-xl hover:bg-primary/90 transition-all shadow-xl hover:-translate-y-1 active:translate-y-0"
          >
            {{ store.t('checkout') }}
          </button>
        </div>
      </div>

      <!-- Empty Cart -->
      <div v-else class="text-center py-24 bg-secondary/30 rounded-3xl border border-dashed border-border mb-12">
        <div class="w-24 h-24 bg-muted rounded-full flex items-center justify-center mx-auto mb-6">
          <svg class="h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
          </svg>
        </div>
        <h2 class="text-2xl font-bold mb-4">{{ store.t('emptyCart') }}</h2>
        <a :href="route('shop')" class="inline-block bg-[#c20000] text-white font-bold py-3 px-8 rounded-xl ring-2 ring-red-100 hover:bg-[#750000] transition-all">{{ store.t('continueShopping') }}</a>
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
import { computed } from 'vue'

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

    const canProceed = computed(() => {
      if (!store.isMerchant) return true
      return store.cart.length > 0 && store.getCartCount >= 20
    })

      const shippingFee = computed(() => {
          let cartTotal = store.getCartTotal
          
          if (isNaN(cartTotal) || cartTotal === null || cartTotal === undefined) {
              cartTotal = 0
          }
          
          const threshold = store.commerce.free_shipping_threshold_usd
          if (threshold !== null && threshold !== '' && cartTotal >= store.convertFromUsd(threshold)) return 0
          return store.convertFromUsd(store.commerce.shipping_fee_usd || 0)
      })

      const shippingMessage = computed(() => {
          const threshold = store.convertFromUsd(store.commerce.free_shipping_threshold_usd || 0)
          return store.isRTL
              ? `تُطبّق رسوم الشحن للطلبات الأقل من ${store.formatPrice(threshold)}.`
              : `Shipping applies to orders below ${store.formatPrice(threshold)}.`
      })

      const totalWithShipping = computed(() => {
          let cartTotal = store.getCartTotal
          
          if (isNaN(cartTotal) || cartTotal === null || cartTotal === undefined) {
              cartTotal = 0
          }
          
          return cartTotal + shippingFee.value
      })


      const proceedToCheckout = () => {
      if (!canProceed.value) {
        if (window.Swal) {
          window.Swal.fire({
            icon: 'warning',
            title: store.isRTL ? 'تنبيه' : 'Attention',
            text: store.isRTL
              ? 'يجب إضافة 20 قطعة على الأقل لإتمام الطلب كتاجر'
              : 'As a merchant, you must add at least 20 items in total to proceed. Currently you have ' + store.getCartCount + ' items.',
            confirmButtonText: store.isRTL ? 'حسناً' : 'OK',
            confirmButtonColor: '#c20000',
          })
        } else {
          alert(store.isRTL ? 'يجب إضافة 20 قطعة على الأقل كتاجر' : 'Min 20 items total required for merchants.')
        }
        return
      }
      window.location.href = route('checkout')
    }

    return {
      store,
      canProceed,
      proceedToCheckout, shippingFee, totalWithShipping, shippingMessage
    }
  }
}
</script>
