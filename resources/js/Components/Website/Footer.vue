<template>
  <footer class="bg-black text-white py-8 border-t border-white/10">
    <div class="container mx-auto px-4">
      <div class="flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="text-sm text-gray-400 font-medium">
          © {{ currentYear }} <span class="text-white hover:text-[#c20000] transition-colors cursor-pointer font-bold">Oneway</span>. All rights reserved.
        </div>

        <div class="flex flex-col md:flex-row items-center gap-4">
          <span class="text-sm text-gray-400 uppercase tracking-widest font-bold">{{ store.t('paymentMethods') || 'Payment Methods' }}:</span>
          <div class="flex items-center gap-4">
            <!-- Visa -->
            <div class="bg-white p-1 rounded-sm shadow-sm" title="Visa">
              <svg class="h-6 w-10" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M18.665 31.977l2.843-17.653h4.632l-2.843 17.653h-4.632z" fill="#003580"/>
                <path d="M37.382 14.786c-1.055-.411-2.733-.853-4.81-.853-5.286 0-9.013 2.809-9.043 6.845-.031 2.977 2.663 4.635 4.697 5.626 2.083 1.015 2.783 1.666 2.774 2.574-.016 1.391-1.668 2.022-3.21 2.022-2.14 0-3.284-.325-5.045-1.097l-.696-.334-.739 4.595c1.239.57 3.529 1.066 5.908 1.088 5.621 0 9.273-2.782 9.324-7.07.038-2.352-1.401-4.135-4.479-5.61-1.865-.953-3.009-1.589-3.009-2.557.009-.858.966-1.742 3.064-1.742 1.741-.03 2.997.37 3.961.782l.471.218.731-4.526z" fill="#003580"/>
                <path d="M47.288 14.324h-3.565c-1.104 0-1.928.32-2.41 1.468L34.615 31.977h4.868s.794-2.195.973-2.678h5.938c.139.638.56 2.678.56 2.678h4.3l-3.966-17.653zM41.69 25.539c.394-1.071 1.9-5.161 1.9-5.161-.027.051.393-1.077.633-1.758l.325 1.571s1.171 5.485 1.411 6.5l-4.269-1.152z" fill="#003580"/>
                <path d="M11.64 14.324L7.106 26.39l-.478-2.433c-.822-2.793-3.398-5.816-6.273-7.33L0 16.4l.036-.18c.026-.135.056-.271.092-.405.008-.033.016-.067.025-.1.081-.302.204-.593.366-.86.012-.02.025-.039.037-.059.32-.544.821-.99 1.413-1.272.046-.022.093-.042.141-.061 1.062-.435 3.078-1.139 5.253-1.139h4.281z" fill="#F79E1B"/>
              </svg>
            </div>
            <!-- Mastercard -->
            <div class="bg-white p-1 rounded-sm shadow-sm" title="MasterCard">
              <svg class="h-6 w-10" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="48" height="48" rx="4" fill="white"/>
                <circle cx="18" cy="24" r="12" fill="#EB001B"/>
                <circle cx="30" cy="24" r="12" fill="#F79E1B" fill-opacity="0.8"/>
              </svg>
            </div>
            <!-- Cash/COD -->
            <div class="bg-white p-1 rounded-sm shadow-sm" title="Cash on Delivery">
              <div class="flex items-center gap-1 px-1">
                <span class="text-green-600 font-bold text-[10px]">CASH</span>
                <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>

<script>
import { computed } from 'vue'
import { useStore } from '@/stores/store'

export default {
  name: 'Footer',
  setup() {
    const store = useStore()
    const currentYear = computed(() => new Date().getFullYear())

    return {
      store,
      currentYear
    }
  }
}
</script>
