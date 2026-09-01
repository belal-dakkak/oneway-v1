<template>
  <div class="min-h-screen" :class="{ 'rtl': store.isRTL }">
    <Header
      :title="title"
      :facebook="facebook"
      :instagram="instagram"
      :tiktok="tiktok"
      @menu-toggle="handleMenuToggle"
    />

    <main class="py-12">
      <div class="container mx-auto px-4">
        <div class="max-w-5xl mx-auto">
          <div class="grid md:grid-cols-2 gap-12">
            <!-- Contact Info -->
            <div class="space-y-12 rtl:text-right">
              <div>
                <h1 class="text-5xl font-bold mb-6">{{ store.t('letsConnect') }}</h1>
                <p class="text-xl text-muted-foreground leading-relaxed">
                  {{ store.locale === 'ar' ? 'لديك سؤال أو تبحث عن شراكة؟ نحن هنا لمساعدتك. تواصل معنا عبر أي من قنواتنا.' : 'Have a question or looking for a partnership? We\'re here to help you. Reach out to us through any of our channels.' }}
                </p>
              </div>

                <div class="flex space-x-4 rtl:space-x-reverse pt-8">
                    <!-- Facebook Popup -->
                    <button
                        @click="showFacebookModal = true"
                        class="p-5 bg-[#1877F2] text-white border border-border rounded-full hover:scale-110 transition-transform shadow-lg"
                    >
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </button>

                    <!-- Instagram Popup -->
                    <button
                        @click="showInstagramModal = true"
                        class="p-5 bg-gradient-to-tr from-[#f09433] via-[#dc2743] to-[#cc2397] text-white border border-border rounded-full hover:scale-110 transition-transform shadow-lg"
                    >
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/>
                        </svg>
                    </button>

                    <!-- TikTok Link -->
                    <div class="rounded-full">
                        <a href="https://www.tiktok.com/@oneway503?_r=1&_t=ZS-93jfPisWS87" target="_blank" class="hover:text-primary transition-colors">
                            <img src="/custom/tiktok-icon-logo.png" alt="TikTok" class="h-16 w-16 rounded-full" />
                        </a>
                    </div>

                    <button
                        @click="showTelegramModal = true"
                        class="p-5 bg-gradient-to-tr from-[#0088cc] via-[#0088cc] to-[#0088cc] text-white border border-border rounded-full hover:scale-110 transition-transform shadow-lg"
                    >
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M21.944 4.665c.317-1.49-.542-2.074-1.846-1.58L2.84 9.692c-1.45.56-1.43 1.37-.247 1.733l4.43 1.383 10.25-6.466c.484-.294.928-.136.564.187l-8.304 7.5-.318 4.72c.466 0 .672-.214.933-.467l2.24-2.177 4.66 3.44c.86.474 1.476.23 1.69-.796l3.206-15.084z"/>
                        </svg>
                    </button>
                </div>

              <div class="space-y-8">
                <!-- Branch 1: UAE -->
                <div class="flex items-start">
                  <div class="p-2.5 bg-primary text-white rounded-lg rtl:ml-4 ltr:mr-4">
                    <FlagIcon country="AE" :size="30" />
                  </div>
                  <div>
                    <h3 class="font-bold text-lg mb-1">{{ store.locale === 'ar' ? 'الفرع الأول: الإمارات العربية المتحدة' : 'Branch 1: United Arab Emirates' }}</h3>
                    <a href="http://maps.app.goo.gl/ANRzyQkoRjKN2cTs6?g_st=iwb" target="_blank" class="flex items-center gap-2 text-muted-foreground hover:text-primary transition-colors">
                      <span>📍</span>
                      <span>{{ store.locale === 'ar' ? 'عجمان الصناعية 2، شارع بيروت' : 'Ajman Industrial 2 Beirut Street' }}</span>
                    </a>
                    <div class="flex flex-col mt-1" :class="store.locale === 'ar'?'float-right':''">
                      <a href="https://wa.me/971545516995" class="text-muted-foreground hover:text-primary transition-colors flex items-center gap-2" dir="ltr">
                        <span>📞</span> +971 545 516 995
                        <span>{{ store.t('wholesale') }}</span>
                      </a>
                      <a href="https://wa.me/971564533655" class="text-muted-foreground hover:text-primary transition-colors flex items-center gap-2" dir="ltr">
                        <span>📞</span> +971 564 533 655
                        <span>{{ store.t('retail') }}</span>
                      </a>
                    </div>
                  </div>
                </div>

                <!-- Branch 2: Lebanon -->
                <div class="flex items-start">
                  <div class="p-2.5 bg-primary text-white rounded-lg rtl:ml-4 ltr:mr-4">
                    <FlagIcon country="LB" :size="30" />
                  </div>
                  <div>
                    <h3 class="font-bold text-lg mb-1">{{ store.locale === 'ar' ? 'الفرع الثاني: لبنان' : 'Branch 2: Lebanon' }}</h3>
                    <a href="https://maps.app.goo.gl/KLSrobLaMw8TVKdU8?g_st=ic" target="_blank" class="flex items-center gap-2 text-muted-foreground hover:text-primary transition-colors">
                      <span>📍</span>
                      <span>{{ store.locale === 'ar' ? 'لبنان - بيروت' : 'Lebanon - Beirut' }}</span>
                    </a>
                    <a href="https://wa.me/96176658734" class="text-muted-foreground hover:text-primary transition-colors flex items-center gap-2 mt-1" dir="ltr">
                      <span>📞</span> +961 76 658 734
                    </a>
                  </div>
                </div>

                <!-- Branch 3: Syria -->
                <div class="flex items-start">
                  <div class="p-2.5 bg-primary text-white rounded-lg rtl:ml-4 ltr:mr-4">
                    <FlagIcon country="SY" :size="30" />
                  </div>
                  <div>
                    <h3 class="font-bold text-lg mb-1">{{ store.locale === 'ar' ? 'الفرع الثالث: سوريا' : 'Branch 3: Syria' }}</h3>
                    <a href="https://maps.app.goo.gl/rCXcEwsCYmNpxiU7A?g_st=ic" target="_blank" class="flex items-center gap-2 text-muted-foreground hover:text-primary transition-colors">
                      <span>📍</span>
                      <span>{{ store.locale === 'ar' ? 'سوريا - حلب' : 'Syria - Aleppo' }}</span>
                    </a>
                      <div class="flex flex-col mt-1" :class="store.locale === 'ar'?'float-right':''">
                          <a href="https://wa.me/963958900555" class="text-muted-foreground hover:text-primary transition-colors flex items-center gap-2" dir="ltr">
                              <span>📞</span> +963 958 900 555
                              <span>{{ store.t('wholesale') }}</span>
                          </a>
                          <a href="https://wa.me/963947900555" class="text-muted-foreground hover:text-primary transition-colors flex items-center gap-2" dir="ltr">
                              <span>📞</span> +963 947 900 555
                              <span>{{ store.t('retail') }}</span>
                          </a>
                      </div>
                  </div>
                </div>

                <!-- Branch 4: Turkey -->
                <div class="flex items-start">
                  <div class="p-2.5 bg-primary text-white rounded-lg rtl:ml-4 ltr:mr-4">
                    <FlagIcon country="TR" :size="30" />
                  </div>
                  <div>
                    <h3 class="font-bold text-lg mb-1">{{ store.locale === 'ar' ? 'الفرع الرابع: تركيا' : 'Branch 4: Turkey' }}</h3>
                    <a href="https://maps.app.goo.gl/5Jur4sj3GtYTaXJf7?g_st=ic" target="_blank" class="flex items-center gap-2 text-muted-foreground hover:text-primary transition-colors">
                      <span>📍</span>
                      <span>{{ store.locale === 'ar' ? 'تركيا، إسطنبول، مارتر' : 'Turkey Istanbul Merter' }}</span>
                    </a>
                    <a href="https://wa.me/905004001621" class="text-muted-foreground hover:text-primary transition-colors flex items-center gap-2 mt-1" dir="ltr">
                      <span>📞</span> +905 004 001 621
                    </a>
                  </div>
                </div>

                <!-- Email Address -->
                <div class="flex items-start pt-6 border-t border-border">
                  <div class="p-3 bg-primary text-white rounded-lg rtl:ml-4 ltr:mr-4">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <div>
                    <h3 class="font-bold text-lg mb-1">{{ store.locale === 'ar' ? 'البريد الإلكتروني' : 'Email' }}</h3>
                    <a href="mailto:theoneway.fashion@gmail.com" class="text-muted-foreground hover:text-primary transition-colors">theoneway.fashion@gmail.com</a>
                  </div>
                </div>
              </div>

            </div>

            <!-- Contact Form -->
            <div class="bg-card border border-border rounded-2xl p-8 shadow-2xl rtl:text-right">
              <form @submit.prevent="handleSubmit" class="space-y-6">
                <div class="grid md:grid-cols-2 gap-6">
                  <div class="space-y-2">
                    <label class="text-sm font-medium">{{ store.t('firstName') }}</label>
                    <input v-model="form.first_name" type="text" class="w-full bg-gray-100 border-none rounded-md px-4 py-3 focus:ring-2 focus:ring-primary" :placeholder="store.t('firstNamePlaceholder')">
                  </div>
                  <div class="space-y-2">
                    <label class="text-sm font-medium">{{ store.t('lastName') }}</label>
                    <input v-model="form.last_name" type="text" class="w-full bg-gray-100 border-none rounded-md px-4 py-3 focus:ring-2 focus:ring-primary" :placeholder="store.t('lastNamePlaceholder')">
                  </div>
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium">{{ store.t('emailAddress') }}</label>
                  <input v-model="form.email" type="email" class="w-full bg-gray-100 border-none rounded-md px-4 py-3 focus:ring-2 focus:ring-primary" :placeholder="store.t('emailAddress')">
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium">{{ store.t('phone') }}</label>
                  <input dir="ltr" v-model="form.phone" type="text" class="w-full bg-gray-100 border-none rounded-md px-4 py-3 focus:ring-2 focus:ring-primary" placeholder="+971 50 123 4567">
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium">{{ store.t('messageTopic') }}</label>
                  <select v-model="form.subject" class="w-full bg-gray-100 border-none rounded-md px-4 py-3 focus:ring-2 focus:ring-primary rtl:bg-left ltr:bg-right">
                    <option value="product_inquiry">{{ store.t('productInquiry') }}</option>
                    <option value="business_partnership">{{ store.t('businessPartnership') }}</option>
                    <option value="customer_support">{{ store.t('customerSupport') }}</option>
                    <option value="other">{{ store.t('other') }}</option>
                  </select>
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium">{{ store.t('howCanWeHelp') }}</label>
                  <textarea v-model="form.message" rows="6" class="w-full bg-gray-100 border-none rounded-md px-4 py-3 focus:ring-2 focus:ring-primary resize-none" :placeholder="store.t('messagePlaceholder')"></textarea>
                </div>
                <button type="submit" :disabled="loading" class="w-full bg-primary text-white font-bold py-4 rounded-md transition-all shadow-lg disabled:opacity-50">
                  <span v-if="loading">{{ store.locale === 'ar' ? 'جاري الإرسال...' : 'Sending...' }}</span>
                  <span v-else>{{ store.t('sendMessage') }}</span>
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Social Modals -->
    <div v-if="showInstagramModal || showFacebookModal || showTelegramModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeModals">
      <div class="bg-card w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
        <div class="p-6 bg-white">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold flex items-center gap-2">
              <template v-if="showInstagramModal">
                <span class="w-8 h-8 rounded-lg bg-gradient-to-tr from-[#f09433] via-[#dc2743] to-[#cc2397] flex items-center justify-center">
                  <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/></svg>
                </span>
                Instagram
              </template>
              <template v-else-if="showFacebookModal">
                <span class="w-8 h-8 rounded-lg bg-[#1877F2] flex items-center justify-center">
                  <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </span>
                Facebook
              </template>
                <template v-else-if="showTelegramModal">
               <span class="w-8 h-8 rounded-lg bg-[#229ED9] flex items-center justify-center">
                <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M21.944 4.665c.317-1.49-.542-2.074-1.846-1.58L2.84 9.692c-1.45.56-1.43 1.37-.247 1.733l4.43 1.383 10.25-6.466c.484-.294.928-.136.564.187l-8.304 7.5-.318 4.72c.466 0 .672-.214.933-.467l2.24-2.177 4.66 3.44c.86.474 1.476.23 1.69-.796l3.206-15.084z"/>
                </svg>
                </span>
                    Telegram
                </template>
            </h3>
            <button @click="closeModals" class="text-muted-foreground hover:text-foreground">
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>

          <div class="space-y-4">
            <template v-if="showInstagramModal">
              <a href="https://www.instagram.com/one_way_uae?igsh=MXZjc2dmYWo4bzdieg%3D%3D&utm_source=qr" target="_blank" class="flex items-center justify-between p-4 bg-accent/50 hover:bg-accent rounded-xl transition-colors group">
                <span class="font-bold flex items-center gap-2">
                  <FlagIcon country="AE" :size="20" />
                  {{ store.locale === 'ar' ? 'وان واي الامارات للملابس التركية' : 'One Way Emirates for Turkish Clothing' }}
                </span>
                <svg class="h-5 w-5 rtl:rotate-180 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
              </a>
              <a href="https://www.instagram.com/one_way__gulf?igsh=czBxdzh5eXNhMjJx&utm_source=qr" target="_blank" class="flex items-center justify-between p-4 bg-accent/50 hover:bg-accent rounded-xl transition-colors group">
                <span class="font-bold flex items-center gap-2">
                  <FlagIcon country="AE" :size="20" />
                  {{ store.locale === 'ar' ? 'وان واي الامارات للزي الخليجي ' : 'One Way Emirates for Gulf Traditional Clothing' }}
                </span>
                <svg class="h-5 w-5 rtl:rotate-180 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
              </a>
              <a href="https://www.instagram.com/one_way_syria?igsh=MWp5MXVuMDAydHVlcg%3D%3D&utm_source=qr" target="_blank" class="flex items-center justify-between p-4 bg-accent/50 hover:bg-accent rounded-xl transition-colors group">
                <span class="font-bold flex items-center gap-2">
                  <FlagIcon country="SY" :size="20" />
                  {{ store.locale === 'ar' ? 'وان واي سوريا للملابس التركية' : 'One Way Syria for Turkish Clothing' }}
                </span>
                <svg class="h-5 w-5 rtl:rotate-180 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
              </a>
            </template>
            <template v-else-if="showFacebookModal">
              <a href="https://www.facebook.com/share/1CJpEimXG5/?mibextid=wwXIfr" target="_blank" class="flex flex-col p-4 bg-accent/50 hover:bg-accent rounded-xl transition-colors group">
                <span class="font-bold flex items-center gap-2">
                  <FlagIcon country="AE" :size="20" />
                  {{ store.locale === 'ar' ? 'وان واي الامارات للملابس التركية' : 'One Way Emirates for Turkish Clothing' }}
                </span>
                <span class="text-xs text-muted-foreground mt-1">{{ store.locale === 'ar' ? 'موديلات تركية' : 'Turkish Models' }}</span>
              </a>
              <a href="https://www.facebook.com/share/1ByrzvXZMr/?mibextid=wwXIfr" target="_blank" class="flex flex-col p-4 bg-accent/50 hover:bg-accent rounded-xl transition-colors group">
                <span class="font-bold flex items-center gap-2">
                  <FlagIcon country="AE" :size="20" />
                  {{ store.locale === 'ar' ? 'وان واي الامارات للزي الخليجي ' : 'One Way Emirates for Gulf Traditional Clothing' }}
                </span>
                <span class="text-xs text-muted-foreground mt-1">{{ store.locale === 'ar' ? 'عبايات وفساتين' : 'Abayas & Dresses' }}</span>
              </a>
              <a href="https://www.facebook.com/share/1DLusSegBL/?mibextid=wwXIfr" target="_blank" class="flex flex-col p-4 bg-accent/50 hover:bg-accent rounded-xl transition-colors group">
                <span class="font-bold flex items-center gap-2">
                  <FlagIcon country="SY" :size="20" />
                  {{ store.locale === 'ar' ? 'وان واي سوريا للملابس التركية' : 'One Way Syria for Turkish Clothing' }}
                </span>
              </a>
            </template>
              <template v-else-if="showTelegramModal">
                  <a href="https://t.me/oneway_fashion" target="_blank" class="flex flex-col p-4 bg-accent/50 hover:bg-accent rounded-xl transition-colors group">
                        <span class="font-bold flex items-center gap-2">
                          <FlagIcon country="AE" :size="20" />
                          {{ store.locale === 'ar' ? '👗 قناة الموديلات التركية والسبورات:' : 'One Way Emirates for Turkish Clothing' }}
                        </span>
                        <span class="text-xs text-muted-foreground mt-1">{{ store.locale === 'ar' ? 'موديلات تركية' : 'Turkish Models' }}</span>
                  </a>
                  <a href="https://t.me/One_Way_for_Gulf_Clothing" target="_blank" class="flex flex-col p-4 bg-accent/50 hover:bg-accent rounded-xl transition-colors group">
                <span class="font-bold flex items-center gap-2">
                  <FlagIcon country="AE" :size="20" />
                  {{ store.locale === 'ar' ? '🖤 قناة الزي الخليجي:' : 'One Way Emirates for Gulf Traditional Clothing' }}
                </span>
                      <span class="text-xs text-muted-foreground mt-1">{{ store.locale === 'ar' ? 'عبايات وفساتين' : 'Abayas & Dresses' }}</span>
                  </a>
              </template>
          </div>
        </div>
      </div>
    </div>

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
import { useStore } from '@/stores/store'
import { ref, reactive } from 'vue'
import { Inertia } from '@inertiajs/inertia'
import FlagIcon from '../Components/Website/FlagIcon.vue'
import Header from '../Components/Website/Header.vue'
import Sitemap from '../Components/Website/Sitemap.vue'
import Footer from '../Components/Website/Footer.vue'
import FloatingButtons from '../Components/Website/FloatingButtons.vue'

export default {
  components: {
    Header,
    Sitemap,
    Footer,
    FloatingButtons,
    FlagIcon
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
    const showInstagramModal = ref(false)
    const showFacebookModal = ref(false)
    const showTelegramModal = ref(false)
    const loading = ref(false)
    const form = reactive({
      first_name: '',
      last_name: '',
      email: '',
      phone: '',
      subject: 'product_inquiry',
      message: ''
    })

    const closeModals = () => {
      showInstagramModal.value = false
      showFacebookModal.value = false
      showTelegramModal.value = false
    }

    const handleMenuToggle = () => {

    }

    const handleSubmit = () => {
      if (!form.first_name || !form.email || !form.phone || !form.message) {
        alert(store.locale === 'ar' ? 'يرجى ملء جميع الحقول المطلوبة' : 'Please fill in all required fields')
        return
      }

      // Phone Validation: UAE, Lebanon, Syria
      const phoneClean = form.phone.replace(/[\s\-\(\)]/g, '')
      const uaeRegex = /^(?:\+971|00971|0)?5[02456]\d{7}$/
      const lebanonRegex = /^(?:\+961|00961|0)?(?:3|70|71|76|78|79|81)\d{6}$/
      const syriaRegex = /^(?:\+963|00963|0)?9[345689]\d{7}$/

      if (!uaeRegex.test(phoneClean) && !lebanonRegex.test(phoneClean) && !syriaRegex.test(phoneClean)) {
        alert(store.t('phoneValidationError'))
        return
      }

      loading.value = true
      Inertia.post(route('contact-messages.store'), form, {
        preserveScroll: true,
        onSuccess: () => {
          loading.value = false
          form.first_name = ''
          form.last_name = ''
          form.email = ''
          form.phone = ''
          form.subject = 'product_inquiry'
          form.message = ''
          alert(store.t('messageSentSuccess'))
        },
        onError: (errors) => {
          loading.value = false
          console.error(errors)
          alert(store.locale === 'ar' ? 'حدث خطأ ما، يرجى المحاولة مرة أخرى' : 'Something went wrong, please try again')
        }
      })
    }

    return {
      store,
      showInstagramModal,
      showTelegramModal,
      showFacebookModal,
      closeModals,
      handleMenuToggle,
      handleSubmit,
      loading,
      form
    }
  }
}
</script>
