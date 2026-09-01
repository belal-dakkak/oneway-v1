<template>
  <div class="bg-black text-white py-16 border-t border-white/10">
    <div class="container mx-auto px-4">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 rtl:text-right">
        <!-- Section 1: Logo and Socials -->
        <div class="space-y-6">
          <div>
            <a :href="route('homepage')" class="inline-block mb-4">
              <img
                src="/custom/logo-icon-black.png"
                alt="Oneway Logo"
                class="h-16 md:h-20 w-auto brightness-0 invert"
              />
            </a>
            <p class="text-sm text-gray-400 leading-relaxed">
              {{ store.t('sitemapDesc') }}
            </p>
          </div>

          <div class="space-y-4 pt-4 border-t border-white/10">
            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest">{{ store.locale === 'ar' ? 'تابعنا على مواقع التواصل' : 'Follow us on Social Media' }}</h4>
            <div class="flex items-center gap-4">
              <!-- Facebook -->
              <button
                @click="showFacebookModal = true"
                class="w-10 h-10 rounded-full flex items-center justify-center bg-[#1877F2] text-white hover:scale-110 transition-transform shadow-lg"
                title="Facebook"
              >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
              </button>

              <!-- Instagram -->
              <button
                @click="showInstagramModal = true"
                class="w-10 h-10 rounded-full flex items-center justify-center bg-gradient-to-tr from-[#f09433] via-[#dc2743] to-[#cc2397] text-white hover:scale-110 transition-transform shadow-lg"
                title="Instagram"
              >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/>
                </svg>
              </button>

              <!-- TikTok -->
              <a href="https://www.tiktok.com/@oneway503?_r=1&_t=ZS-93jfPisWS87" target="_blank" class="hover:scale-110 transition-transform shadow-lg rounded-full" title="TikTok">
                <img src="/custom/tiktok.avif" alt="TikTok" class="w-10 h-10 rounded-full object-cover" />
              </a>
            </div>
          </div>

            <!-- Secure Privacy -->
            <div class="flex gap-4 items-start">
                <div v-show="false" class="flex-shrink-0 w-12 h-12 bg-muted rounded-xl mt-1 p-2.5">
                    <img src="/assets/secure.png.slim.avif" alt="Secure" class="w-full h-full object-contain">
                </div>
                <div class="space-y-3">
                    <h4 class="text-sm text-gray-400 leading-relaxed">{{ store.t('privacyTitle') }}</h4>
                    <p class="text-sm text-gray-400 leading-relaxed">
                        {{ store.t('privacyDesc') }}
                    </p>
                    <a :href="route('privacy-policy')" class="inline-flex text-sm text-gray-400 leading-relaxed transition-colors group">
                        {{ store.t('learnMore') }}
                        <svg class="w-4 h-4 ml-1 rtl:mr-1 rtl:rotate-180 transform transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Purchase Protection -->
            <div class="flex gap-4 items-start">
                <div v-show="false" class="flex-shrink-0 w-12 h-12 bg-muted rounded-xl mt-1 p-2.5">
                    <img src="/assets/purchase.avif" alt="Purchase" class="w-full h-full object-contain">
                </div>
                <div class="space-y-3">
                    <h4 class="text-sm text-gray-400 leading-relaxed">{{ store.t('purchaseProtectionTitle') }}</h4>
                    <p class="text-sm text-gray-400 leading-relaxed">
                        {{ store.t('purchaseProtectionDesc') }}
                    </p>
                    <a :href="route('refund-policy')" class="inline-flex text-sm text-gray-400 leading-relaxed transition-colors group/80 transition-colors group">
                        {{ store.t('learnMore') }}
                        <svg class="w-4 h-4 ml-1 rtl:mr-1 rtl:rotate-180 transform transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Section 2: Useful Links -->
        <div>
          <h4 class="text-lg font-bold mb-6 text-white uppercase tracking-wider">{{ store.t('usefulLinks') }}</h4>
          <ul class="space-y-3">
            <li><a :href="route('homepage')" class="text-sm text-gray-400 hover:text-[#c20000] transition-colors font-medium">{{ store.t('home') }}</a></li>
            <li><a :href="route('shop')" class="text-sm text-gray-400 hover:text-[#c20000] transition-colors font-medium">{{ store.t('shop') }}</a></li>
            <li><a :href="route('about')" class="text-sm text-gray-400 hover:text-[#c20000] transition-colors font-medium">{{ store.t('about') }}</a></li>
            <li><a :href="route('contact')" class="text-sm text-gray-400 hover:text-[#c20000] transition-colors font-medium">{{ store.t('contact') }}</a></li>
            <li><a :href="route('categories.index')" class="text-sm text-gray-400 hover:text-[#c20000] transition-colors font-medium">{{ store.t('allCategories') }}</a></li>
            <li class="pt-4 border-t border-white/5"><a :href="route('privacy-policy')" class="text-xs text-gray-500 hover:text-white transition-colors">{{ store.t('privacyPolicyTitle') }}</a></li>
            <li><a :href="route('terms-and-conditions')" class="text-xs text-gray-500 hover:text-white transition-colors">{{ store.t('termsConditionsTitle') }}</a></li>
            <li><a :href="route('shipping-policy')" class="text-xs text-gray-500 hover:text-white transition-colors">{{ store.t('shippingPolicyTitle') }}</a></li>
            <li><a :href="route('refund-policy')" class="text-xs text-gray-500 hover:text-white transition-colors">{{ store.t('refundPolicyTitle') }}</a></li>
          </ul>
        </div>

        <!-- Section 3: Categories -->
        <div>
          <h4 class="text-lg font-bold mb-6 text-white uppercase tracking-wider">{{ store.t('categories') }}</h4>
          <ul class="space-y-3">
            <li v-for="cat in categories" :key="cat.id">
              <a :href="route('shop', { category: cat.id })" class="text-sm text-gray-400 hover:text-[#c20000] transition-colors font-medium">
                {{ store.locale === 'ar' ? (cat.name_ar || cat.name) : (cat.name_en || cat.name) }}
              </a>
            </li>
          </ul>
        </div>

        <!-- Section 4: Our Branches -->
        <div class="lg:col-span-1">
          <h4 class="text-lg font-bold mb-6 text-white uppercase tracking-wider">{{ store.t('ourBranches') }}</h4>
          <div class="space-y-8 text-sm text-gray-400">
            <!-- Branch: UAE -->
            <div class="space-y-2">
              <div class="flex items-center gap-2 font-bold text-white">
                <FlagIcon country="AE" :size="20" />
                <span>{{ store.locale === 'ar' ? 'الفرع الأول: الإمارات العربية المتحدة' : 'Branch 1: United Arab Emirates' }}</span>
              </div>
              <a href="http://maps.app.goo.gl/ANRzyQkoRjKN2cTs6?g_st=iwb" target="_blank" class="flex items-start gap-2 hover:text-white transition-colors">
                <span class="text-[#c20000]">📍</span>
                <span>{{ store.locale === 'ar' ? 'عجمان الصناعية 2، شارع بيروت' : 'Ajman Industrial 2 Beirut Street' }}</span>
              </a>
              <div class="flex items-center gap-2" dir="ltr">
                <span class="text-[#c20000]">📞</span>
                <a href="https://wa.me/971545516995" class="hover:text-white transition-colors">+971 545 516 995</a>
                  <span>{{ store.t('wholesale') }}</span>
              </div>
              <div class="flex items-center gap-2" dir="ltr">
                <span class="text-[#c20000]">📞</span>
                <a href="https://wa.me/971564533655" class="hover:text-white transition-colors">+971 564 533 655</a>
                  <span>{{ store.t('retail') }}</span>
              </div>
            </div>

            <!-- Branch: Lebanon -->
            <div class="space-y-2">
              <div class="flex items-center gap-2 font-bold text-white">
                <FlagIcon country="LB" :size="20" />
                <span>{{ store.locale === 'ar' ? 'الفرع الثاني: لبنان' : 'Branch 2: Lebanon' }}</span>
              </div>
              <a href="https://maps.app.goo.gl/KLSrobLaMw8TVKdU8?g_st=ic" target="_blank" class="flex items-start gap-2 hover:text-white transition-colors">
                <span class="text-[#c20000]">📍</span>
                <span>{{ store.locale === 'ar' ? 'لبنان - بيروت' : 'Lebanon - Beirut' }}</span>
              </a>
              <div class="flex items-center gap-2" dir="ltr">
                <span class="text-[#c20000]">📞</span>
                <a href="https://wa.me/96176658734" class="hover:text-white transition-colors">+961 76 658 734</a>
              </div>
            </div>

            <!-- Branch: Syria -->
            <div class="space-y-2">
              <div class="flex items-center gap-2 font-bold text-white">
                <FlagIcon country="SY" :size="20" />
                <span>{{ store.locale === 'ar' ? 'الفرع الثالث: سوريا' : 'Branch 3: Syria' }}</span>
              </div>
              <a href="https://maps.app.goo.gl/rCXcEwsCYmNpxiU7A?g_st=ic" target="_blank" class="flex items-start gap-2 hover:text-white transition-colors">
                <span class="text-[#c20000]">📍</span>
                <span>{{ store.locale === 'ar' ? 'سوريا - حلب' : 'Syria - Aleppo' }}</span>
              </a>
              <div class="flex items-center gap-2" dir="ltr">
                <span class="text-[#c20000]">📞</span>
                <a href="https://wa.me/963958900555" class="hover:text-white transition-colors">+963 958 900 555</a>
                 <span>{{ store.t('wholesale') }}</span>
              </div>
                <div class="flex items-center gap-2" dir="ltr">
                    <span class="text-[#c20000]">📞</span>
                    <a href="https://wa.me/963947900555" class="hover:text-white transition-colors">+963 947 900 555</a>
                    <span>{{ store.t('retail') }}</span>
                </div>
            </div>

            <!-- Branch: Turkey -->
            <div class="space-y-2">
              <div class="flex items-center gap-2 font-bold text-white">
                <FlagIcon country="TR" :size="20" />
                <span>{{ store.locale === 'ar' ? 'الفرع الرابع: تركيا' : 'Branch 4: Turkey' }}</span>
              </div>
              <a href="https://maps.app.goo.gl/5Jur4sj3GtYTaXJf7?g_st=ic" target="_blank" class="flex items-start gap-2 hover:text-white transition-colors">
                <span class="text-[#c20000]">📍</span>
                <span>{{ store.locale === 'ar' ? 'تركيا، إسطنبول، مارتر' : 'Turkey Istanbul Merter' }}</span>
              </a>
              <div class="flex items-center gap-2" dir="ltr">
                <span class="text-[#c20000]">📞</span>
                <a href="https://wa.me/905004001621" class="hover:text-white transition-colors">+905 004 001 621</a>
              </div>
            </div>

            <!-- Email -->
            <div class="pt-4 border-t border-white/5">
              <div class="flex items-center gap-2">
                <span class="text-[#c20000]">✉️</span>
                <a href="mailto:theoneway.fashion@gmail.com" class="hover:text-white transition-colors">theoneway.fashion@gmail.com</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Social Modals -->
  <teleport to="body">
    <div v-if="showInstagramModal || showFacebookModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeModals">
      <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
        <div class="p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold flex items-center gap-2 text-black">
              <template v-if="showInstagramModal">
                <span class="w-8 h-8 rounded-lg bg-gradient-to-tr from-[#f09433] via-[#dc2743] to-[#cc2397] flex items-center justify-center">
                  <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/></svg>
                </span>
                Instagram
              </template>
              <template v-else>
                <span class="w-8 h-8 rounded-lg bg-[#1877F2] flex items-center justify-center">
                  <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </span>
                Facebook
              </template>
            </h3>
            <button @click="closeModals" class="text-gray-400 hover:text-black">
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>

          <div class="space-y-4">
            <template v-if="showInstagramModal">
              <a href="https://www.instagram.com/one_way_uae?igsh=MXZjc2dmYWo4bzdieg%3D%3D&utm_source=qr" target="_blank" class="flex items-center justify-between p-4 bg-gray-50 hover:bg-gray-100 rounded-xl transition-colors group text-black">
                <span class="font-bold flex items-center gap-2">
                  <FlagIcon country="AE" :size="20" />
                  {{ store.locale === 'ar' ? 'وان واي الامارات للملابس التركية' : 'One Way Emirates for Turkish Clothing' }}
                </span>
                <svg class="h-5 w-5 rtl:rotate-180 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
              </a>
              <a href="https://www.instagram.com/one_way__gulf?igsh=czBxdzh5eXNhMjJx&utm_source=qr" target="_blank" class="flex items-center justify-between p-4 bg-gray-50 hover:bg-gray-100 rounded-xl transition-colors group text-black">
                <span class="font-bold flex items-center gap-2">
                  <FlagIcon country="AE" :size="20" />
                  {{ store.locale === 'ar' ? 'وان واي الامارات للزي الخليجي ' : 'One Way Emirates for Gulf Traditional Clothing' }}
                </span>
                <svg class="h-5 w-5 rtl:rotate-180 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
              </a>
              <a href="https://www.instagram.com/one_way_syria?igsh=MWp5MXVuMDAydHVlcg%3D%3D&utm_source=qr" target="_blank" class="flex items-center justify-between p-4 bg-gray-50 hover:bg-gray-100 rounded-xl transition-colors group text-black">
                <span class="font-bold flex items-center gap-2">
                  <FlagIcon country="SY" :size="20" />
                  {{ store.locale === 'ar' ? 'وان واي سوريا للملابس التركية' : 'One Way Syria for Turkish Clothing' }}
                </span>
                <svg class="h-5 w-5 rtl:rotate-180 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
              </a>
            </template>
            <template v-else>
              <a href="https://www.facebook.com/share/1CJpEimXG5/?mibextid=wwXIfr" target="_blank" class="flex flex-col p-4 bg-gray-50 hover:bg-gray-100 rounded-xl transition-colors group text-black">
                <span class="font-bold flex items-center gap-2">
                  <FlagIcon country="AE" :size="20" />
                  {{ store.locale === 'ar' ? 'وان واي الامارات للملابس التركية' : 'One Way Emirates for Turkish Clothing' }}
                </span>
                <span class="text-xs text-muted-foreground mt-1">{{ store.locale === 'ar' ? 'موديلات تركية' : 'Turkish Models' }}</span>
              </a>
              <a href="https://www.facebook.com/share/1ByrzvXZMr/?mibextid=wwXIfr" target="_blank" class="flex flex-col p-4 bg-gray-50 hover:bg-gray-100 rounded-xl transition-colors group text-black">
                <span class="font-bold flex items-center gap-2">
                  <FlagIcon country="AE" :size="20" />
                  {{ store.locale === 'ar' ? 'وان واي الامارات للزي الخليجي ' : 'One Way Emirates for Gulf Traditional Clothing' }}
                </span>
                <span class="text-xs text-muted-foreground mt-1">{{ store.locale === 'ar' ? 'عبايات وفساتين' : 'Abayas & Dresses' }}</span>
              </a>
              <a href="https://www.facebook.com/share/1DLusSegBL/?mibextid=wwXIfr" target="_blank" class="flex flex-col p-4 bg-gray-50 hover:bg-gray-100 rounded-xl transition-colors group text-black">
                <span class="font-bold flex items-center gap-2">
                  <FlagIcon country="SY" :size="20" />
                  {{ store.locale === 'ar' ? 'وان واي سوريا للملابس التركية' : 'One Way Syria for Turkish Clothing' }}
                </span>
              </a>
            </template>
          </div>
        </div>
      </div>
    </div>
  </teleport>
</template>

<script>
import { useStore } from '@/stores/store'
import { ref } from 'vue'
import FlagIcon from './FlagIcon.vue'

export default {
  name: 'Sitemap',
  components: {
    FlagIcon
  },
  props: {
    categories: {
      type: Array,
      default: () => []
    },
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
    const showInstagramModal = ref(false)
    const showFacebookModal = ref(false)

    const closeModals = () => {
      showInstagramModal.value = false
      showFacebookModal.value = false
    }

    return {
      store,
      showInstagramModal,
      showFacebookModal,
      closeModals
    }
  }
}
</script>
