<template>
  <header class="sticky top-0 z-50 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 border-b">
    <div class="container mx-auto px-4">
      <div class="flex items-center justify-between h-16">
        <button class="md:hidden p-2 -ml-2 hover:bg-muted rounded-md transition-colors" @click="isMenuOpen = true">
          <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>

        <div class="flex items-center h-full py-1">
          <a :href="route('homepage')" class="hover:opacity-90 transition-opacity h-full flex items-center">
            <img src="/custom/logo-icon-black.png" alt="Oneway Logo" class="max-h-full w-auto object-contain" />
          </a>
        </div>

        <div class="hidden md:flex flex-1 items-center space-x-4 rtl:space-x-reverse mx-8">
          <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              type="text"
              v-model="searchQuery"
              @keyup.enter="handleSearch"
              :placeholder="store.t('searchPlaceholder')"
              class="w-full pl-10 pr-4 border border-input bg-background rounded-md px-3 py-2 text-base md:text-sm focus:outline-none focus:ring-1 focus:ring-primary"
            />
          </div>
          <div class="w-72 flex items-center space-x-1 rtl:space-x-reverse">
            <template v-if="!$page.props.isMerchant">
              <div class="relative flex-1 flex items-center h-full">
                <input
                  type="text"
                  v-model="merchantCode"
                  @keyup.enter="handleMerchantVerify"
                  @focus="isMerchantInputFocused = true"
                  @blur="isMerchantInputFocused = false"
                  class="flex-1 px-3 py-2 border border-input bg-background rounded-md text-base md:text-sm focus:outline-none focus:ring-1 focus:ring-primary"
                />
                <div v-if="!merchantCode && !isMerchantInputFocused" class="absolute inset-0 flex items-center overflow-hidden pointer-events-none px-3">
                   <div :class="['whitespace-nowrap animate-marquee text-[10px] md:text-sm text-muted-foreground/60', store.isRTL ? 'animate-marquee-rtl' : 'animate-marquee-ltr']">
                    {{ store.t('merchantPrompt') }}
                  </div>
                </div>
              </div>
              <button
                @click="handleMerchantVerify"
                class="bg-primary text-white px-3 py-2 rounded-md text-[10px] hover:bg-primary-hover transition-colors whitespace-nowrap"
              >
                {{ store.t('verify') }}
              </button>
            </template>
            <div v-else class="flex items-center text-primary font-bold text-xs uppercase tracking-wider bg-primary/10 px-3 py-2 rounded-md border border-primary/20">
              <svg class="h-4 w-4 mr-2 rtl:ml-2 rtl:mr-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
              {{ store.t('merchantAccount') }}
            </div>
            <button
              v-if="$page.props.isMerchant"
              @click="store.disableMerchantMode"
              class="text-[10px] text-muted-foreground hover:text-primary underline transition-colors whitespace-nowrap"
            >
              {{ store.t('backToVisitorMode') }}
            </button>
          </div>
        </div>

        <div class="flex items-center space-x-2 rtl:space-x-reverse">

          <button class="md:hidden p-1 hover:text-primary transition-colors" @click="isSearchOpen = !isSearchOpen">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </button>



          <button class="relative hover:text-primary transition-colors p-1" @click="goTo(route('favorites.index'))">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
            <span
              v-if="store.getFavoritesCount > 0"
              class="absolute -top-1 -right-1 h-5 w-5 rounded-full bg-accent text-primary flex items-center justify-center text-[15px] font-bold"
            >
              {{ store.getFavoritesCount }}
            </span>
          </button>

          <button class="relative hover:text-primary transition-colors p-1" @click="goTo(route('cart'))">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span
              v-if="store.getCartCount > 0"
              class="absolute -top-1 -right-1 h-5 w-5 rounded-full bg-destructive text-primary flex items-center justify-center text-[15px] font-bold"
            >
              {{ store.getCartCount }}
            </span>
          </button>

          <!-- User Menu -->
          <div class="relative">
            <template v-if="$page.props.auth.user">
              <div class="flex items-center space-x-2 rtl:space-x-reverse group">
                <button
                  class="flex items-center space-x-2 rtl:space-x-reverse hover:text-primary transition-colors p-1"
                  @click="isUserMenuOpen = !isUserMenuOpen"
                >
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  <span class="hidden md:block text-sm font-medium">{{ $page.props.auth.user.name }}</span>
                </button>

                <!-- Dropdown -->
                <div
                  v-if="isUserMenuOpen"
                  class="absolute top-full mt-2 ltr:right-0 rtl:left-0 w-48 bg-white border border-border rounded-md shadow-lg py-1 z-50 text-foreground"
                >
                  <a :href="route('website.profile')" class="block px-4 py-2 text-sm hover:bg-muted transition-colors">{{ store.t('profile') }}</a>
                  <button
                    @click="logout"
                    class="w-full text-left ltr:text-left rtl:text-right block px-4 py-2 text-sm hover:bg-muted transition-colors text-destructive"
                  >
                    {{ store.t('logout') }}
                  </button>
                </div>
              </div>
            </template>
            <template v-else>
              <button class="hover:text-primary transition-colors p-1" @click="goTo(route('login'))">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </button>
            </template>
          </div>
        </div>
      </div>

      <!-- Mobile Navbar Second Line (Navigation) -->
      <div class="md:hidden border-t py-2 px-4 bg-zinc-50/80 backdrop-blur-sm -mx-4">
        <div class="flex items-center space-x-1 rtl:space-x-reverse overflow-x-auto no-scrollbar">
          <!-- Home & Contact & About Us Links -->
          <div class="flex items-center space-x-2 rtl:space-x-reverse whitespace-nowrap px-1">
            <a :href="route('homepage')" class="text-[12px] font-bold text-foreground/80 hover:text-primary transition-colors">{{ store.t('home') }}</a>
            <a :href="route('contact')" class="text-[12px] font-bold text-foreground/80 hover:text-primary transition-colors">{{ store.t('contact') }}</a>
            <a :href="route('about')" class="text-[12px] font-bold text-foreground/80 hover:text-primary transition-colors">{{ store.t('about') }}</a>
          </div>

          <div class="relative">
            <button
              @click="isCountryMenuOpen = !isCountryMenuOpen"
              class="h-8 px-1 flex items-center space-x-1 rtl:space-x-reverse bg-white border border-border text-[12px] font-bold rounded-lg shadow-sm active:scale-95 transition-all whitespace-nowrap"
            >
              <FlagIcon :country="store.country" :size="16" />
              <span>{{ store.t('country') }}</span>
            </button>
            <!-- Mobile Dropdown with Teleport to avoid clipping -->
            <Teleport to="body">
              <div v-if="isCountryMenuOpen" class="md:hidden fixed inset-0 z-[100] flex items-end justify-center">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-[2px]" @click="isCountryMenuOpen = false"></div>
                <div class="relative w-full bg-white rounded-t-2xl shadow-2xl p-4 pb-8 animate-in slide-in-from-bottom duration-300">
                  <div class="w-12 h-1.5 bg-muted rounded-full mx-auto mb-6"></div>
                  <div class="flex justify-between items-center mb-6 px-2">
                    <h3 class="text-lg font-bold">{{ store.t('country') }}</h3>
                    <button @click="isCountryMenuOpen = false" class="p-2 hover:bg-muted rounded-full transition-colors">
                      <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </div>
                  <div class="space-y-3 px-2">
                    <button
                      v-for="c in countries"
                      :key="c.code"
                      @click="handleCountryClick(c.code); isCountryMenuOpen = false"
                      class="w-full flex items-center justify-between p-4 bg-muted/50 hover:bg-muted rounded-xl transition-all active:scale-[0.98]"
                      :class="{ 'ring-2 ring-primary bg-primary/5': store.country === c.code }"
                    >
                      <div class="flex items-center space-x-3 rtl:space-x-reverse">
                        <FlagIcon :country="c.code" :size="24" />
                        <span class="font-bold">{{ store.isRTL ? c.nameAr : c.name }}</span>
                      </div>
                      <div v-if="store.country === c.code" class="text-primary">
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                      </div>
                    </button>
                  </div>
                </div>
              </div>
            </Teleport>
          </div>

          <button
            v-if="store.country !== 'LB' && store.country !== 'SY'"
            @click="store.toggleCurrency"
            class="h-8 px-1 bg-white border border-border text-[12px] font-bold rounded-lg shadow-sm active:scale-95 transition-all whitespace-nowrap"
          >
            {{ store.t('currency') }}: {{ store.currency }}
          </button>
          <div
            v-else
            class="h-8 px-1 bg-gray-50 border border-border text-[12px] font-bold rounded-lg shadow-sm flex items-center whitespace-nowrap"
          >
            {{ store.t('currency') }}: {{ store.currency }}
          </div>

          <!-- Translate Button -->
          <button
            @click="store.toggleRTL"
            style="color: white!important;"
            class="h-8 px-2 bg-primary text-white text-[12px] font-bold rounded-lg shadow-sm active:scale-95 transition-all whitespace-nowrap"
          >
            {{ store.isRTL ? 'English' : 'العربية' }}
          </button>
        </div>
      </div>

      <!-- Mobile Navbar Third Line (Merchant Only) -->
      <div class="md:hidden border-t py-2.5 px-4 bg-white -mx-4">
        <template v-if="!$page.props.isMerchant">
          <div class="flex items-center gap-2">
            <div class="flex-1 relative flex items-center h-10 bg-zinc-50 border border-border rounded-lg overflow-hidden group focus-within:ring-1 focus-within:ring-primary transition-all">
              <input
                type="text"
                v-model="merchantCode"
                @keyup.enter="handleMerchantVerify"
                @focus="isMerchantInputFocused = true"
                @blur="isMerchantInputFocused = false"
                class="w-full px-3 py-2 bg-transparent border-none text-base sm:text-xs focus:ring-0 h-full"
              />
              <div v-if="!merchantCode && !isMerchantInputFocused" class="absolute inset-0 flex items-center overflow-hidden pointer-events-none px-3">
                 <div :class="['whitespace-nowrap animate-marquee text-[10px] sm:text-xs text-muted-foreground/60', store.isRTL ? 'animate-marquee-rtl' : 'animate-marquee-ltr']">
                  {{ store.t('merchantPrompt') }}
                </div>
              </div>
            </div>
            <button
              @click="handleMerchantVerify"
              class="h-10 px-4 bg-zinc-900 text-white text-[12px] font-bold rounded-lg shadow-sm active:scale-95 transition-all whitespace-nowrap"
            >
              {{ store.t('verify') }}
            </button>
          </div>
        </template>
        <div v-else class="h-10 flex items-center justify-between text-primary font-bold text-[10px] uppercase tracking-wider px-3 rounded-lg border border-primary/20 bg-primary/5">
          <span>{{ store.t('merchantAccount') }}</span>
          <button
            @click="store.disableMerchantMode"
            class="text-[10px] text-muted-foreground hover:text-primary underline font-medium"
          >
            {{ store.t('backToVisitorMode') }}
          </button>
        </div>
      </div>

      <div v-if="isSearchOpen" class="md:hidden py-3 border-t">
        <div class="relative">
          <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            type="text"
            v-model="searchQuery"
            @keyup.enter="handleSearch"
            :placeholder="store.t('searchPlaceholder')"
            class="w-full pl-10 pr-4 border border-input bg-background rounded-md px-3 py-2 text-base focus:outline-none focus:ring-1 focus:ring-primary"
          />
        </div>
      </div>

      <nav class="hidden md:flex items-center justify-between py-4 border-t px-2">
        <div class="flex items-center space-x-8 rtl:space-x-reverse">
          <a :href="route('homepage')" class="text-sm font-medium hover:text-primary transition-colors">{{ store.t('home') }}</a>
          <a :href="route('shop')" class="text-sm font-medium hover:text-primary transition-colors">{{ store.t('shop') }}</a>
          <a :href="route('categories.web')" class="text-sm font-medium hover:text-primary transition-colors">{{ store.t('categories') }}</a>
          <a :href="route('about')" class="text-sm font-medium hover:text-primary transition-colors">{{ store.t('about') }}</a>
          <a :href="route('contact')" class="text-sm font-medium hover:text-primary transition-colors">{{ store.t('contact') }}</a>
          <a v-show="false" :href="route('shop', { sale: 'true' })" class="text-sm font-medium text-red-600 hover:text-red-700 transition-colors">{{ store.t('sale') }}</a>
        </div>
        <div class="flex items-center space-x-4 rtl:space-x-reverse">
          <!-- Desktop Country Switcher -->
          <div class="relative">
            <button
              @click="isCountryMenuOpen = !isCountryMenuOpen"
              class="flex items-center space-x-2 hover:text-primary transition-colors p-1 text-xs font-bold border border-border rounded px-2 h-8"
            >
              <FlagIcon :country="store.country" :size="20" />
              <span class="font-bold mr-1 text-xs" style="margin-right: 4px;">{{ store.t('country') }}</span>
              <svg class="h-3 w-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div
              v-if="isCountryMenuOpen"
              class="absolute top-full mt-2 ltr:right-0 rtl:left-0 w-60 bg-white border border-border rounded-md shadow-lg py-2 z-50 text-foreground"
            >
              <button
                v-for="c in countries"
                :key="c.code"
                @click="handleCountryClick(c.code)"
                class="w-full text-left ltr:text-left rtl:text-right px-4 py-2 text-sm hover:bg-muted flex items-center space-x-3 transition-colors"
                :class="{ 'bg-primary/5 text-primary': store.country === c.code }"
              >
                <FlagIcon :country="c.code" :size="20" />
                <span class="font-medium mx-2" style="margin-right: 4px !important;">{{ store.isRTL ? c.nameAr : c.name }}</span>
              </button>
            </div>
          </div>

          <!-- Desktop Currency Toggle -->
          <button
            v-if="store.country !== 'LB' && store.country !== 'SY'"
            @click="store.toggleCurrency"
            class="flex items-center space-x-1 hover:text-primary transition-colors p-1 text-xs font-bold border border-border rounded px-2 h-8"
          >
            {{ store.t('currency') }}: {{ store.currency }}
          </button>
          <div
            v-else
            class="flex items-center space-x-1 p-1 text-xs font-bold border border-border rounded px-2 h-8 bg-gray-50"
          >
            {{ store.t('currency') }}: {{ store.currency }}
          </div>

          <button
            @click="store.toggleRTL"
            class="text-sm font-semibold hover:bg-[#a30000] hover:text-white transition-colors px-4 py-1.5 border border-[#c20000] rounded text-[#c20000]"
          >
            {{ store.isRTL ? 'English' : 'اللغة العربية' }}
          </button>
        </div>
      </nav>
    </div>

    <!-- Mobile Menu Drawer -->
    <Teleport to="body">
      <transition
        enter-active-class="transition ease-in-out duration-300 transform"
        enter-from-class="ltr:-translate-x-full rtl:translate-x-full"
        enter-to-class="translate-x-0"
        leave-active-class="transition ease-in-out duration-300 transform"
        leave-from-class="translate-x-0"
        leave-to-class="ltr:-translate-x-full rtl:translate-x-full"
      >
        <div v-if="isMenuOpen" class="fixed inset-0 z-[100] flex">
          <!-- Backdrop -->
          <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="isMenuOpen = false"></div>

          <!-- Drawer -->
          <div class="relative flex flex-col w-full max-w-xs bg-white shadow-xl rtl:right-0 ltr:left-0 h-full overflow-y-auto">
            <div class="flex items-center justify-between p-4 border-b">
              <img src="/custom/logo-icon-black.png" alt="Oneway Logo" class="h-12 w-auto" />
              <button @click="isMenuOpen = false" class="p-2 -mr-2 hover:bg-muted rounded-md transition-colors">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div class="flex-1 py-6 px-4 space-y-4">
              <a :href="route('homepage')" class="flex items-center p-3 text-lg font-semibold border-b border-border/50 hover:text-primary transition-colors" @click="isMenuOpen = false">
                {{ store.t('home') }}
              </a>
              <a :href="route('shop')" class="flex items-center p-3 text-lg font-semibold border-b border-border/50 hover:text-primary transition-colors" @click="isMenuOpen = false">
                {{ store.t('shop') }}
              </a>
              <a :href="route('categories.index')" class="flex items-center p-3 text-lg font-semibold border-b border-border/50 hover:text-primary transition-colors" @click="isMenuOpen = false">
                {{ store.t('categories') }}
              </a>
              <a :href="route('about')" class="flex items-center p-3 text-lg font-semibold border-b border-border/50 hover:text-primary transition-colors" @click="isMenuOpen = false">
                {{ store.t('about') }}
              </a>
              <a :href="route('contact')" class="flex items-center p-3 text-lg font-semibold border-b border-border/50 hover:text-primary transition-colors" @click="isMenuOpen = false">
                {{ store.t('contact') }}
              </a>

              <!-- Merchant Section in Menu -->
              <div class="pt-4 px-2">
                <template v-if="!$page.props.isMerchant">
                  <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3 px-1">{{ store.t('merchantAccount') }}</p>
                  <div class="flex flex-col space-y-2">
                    <div class="relative flex items-center overflow-hidden h-12 bg-muted rounded-md">
                      <input
                        type="text"
                        v-model="merchantCode"
                        @keyup.enter="handleMerchantVerify"
                        @focus="isMerchantInputFocused = true"
                        @blur="isMerchantInputFocused = false"
                        class="w-full px-4 py-3 bg-transparent border-none text-base focus:ring-2 focus:ring-primary h-full"
                      />
                      <div v-if="!merchantCode && !isMerchantInputFocused" class="absolute inset-0 flex items-center overflow-hidden pointer-events-none px-4">
                         <div :class="['whitespace-nowrap animate-marquee text-sm text-muted-foreground/60', store.isRTL ? 'animate-marquee-rtl' : 'animate-marquee-ltr']">
                          {{ store.t('merchantPrompt') }}
                        </div>
                      </div>
                    </div>
                    <button
                      @click="handleMerchantVerify"
                      class="w-full bg-zinc-900 text-white py-3 rounded-md text-sm font-bold active:scale-95 transition-all"
                    >
                      {{ store.t('verify') }}
                    </button>
                  </div>
                </template>
                <div v-else class="bg-primary/10 border border-primary/20 rounded-lg p-4 flex flex-col space-y-3 text-primary">
                  <div class="flex items-center">
                    <svg class="h-5 w-5 rtl:ml-3 ltr:mr-3" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span class="font-bold text-sm uppercase tracking-wider">{{ store.t('merchantAccount') }}</span>
                  </div>
                  <button
                    @click="store.disableMerchantMode"
                    class="w-full text-center text-xs text-muted-foreground hover:text-primary underline font-medium border-t border-primary/20 pt-2"
                  >
                    {{ store.t('backToVisitorMode') }}
                  </button>
                </div>
              </div>
            </div>

            <div class="p-6 border-t space-y-4">
              <div class="grid grid-cols-2 gap-2">
                <button
                  v-if="store.country !== 'LB' && store.country !== 'SY'"
                  @click="store.toggleCurrency"
                  class="flex items-center justify-center space-x-2 rtl:space-x-reverse font-bold bg-secondary text-secondary-foreground py-3 rounded-md shadow-md active:scale-95 transition-all"
                >
                  <span class="text-xs text-muted-foreground font-medium">{{ store.t('currency') }}:</span>
                  <span>{{ store.currency }}</span>
                </button>
                <div
                  v-else
                  class="flex items-center justify-center space-x-2 rtl:space-x-reverse font-bold bg-gray-100 text-gray-500 py-3 rounded-md shadow-md"
                >
                  <span class="text-xs text-gray-400 font-medium">{{ store.t('currency') }}:</span>
                  <span>{{ store.currency }}</span>
                </div>
                <div class="relative">
                  <button
                    @click="isCountryMenuOpen = !isCountryMenuOpen"
                    class="w-full flex items-center justify-center space-x-2 rtl:space-x-reverse font-bold bg-secondary text-secondary-foreground py-3 rounded-md shadow-md active:scale-95 transition-all"
                  >
                    <FlagIcon :country="store.country" :size="20" />
                    <span class="text-xs font-medium">{{ store.t('country') }}</span>
                  </button>
                  <!-- Country Menu (Drawer Context) -->
                  <div
                    v-if="isCountryMenuOpen"
                    class="absolute bottom-full mb-2 left-0 right-0 bg-card border border-border rounded-md shadow-lg py-2 z-50 text-foreground"
                  >
                    <button
                      v-for="c in countries"
                      :key="c.code"
                      @click="handleCountryClick(c.code)"
                      class="w-full text-left ltr:text-left rtl:text-right px-4 py-2 text-sm hover:bg-muted flex items-center space-x-3 transition-colors"
                      :class="{ 'bg-primary/5 text-primary': store.country === c.code }"
                    >
                      <FlagIcon :country="c.code" :size="20" />
                      <span class="font-medium mx-2">{{ store.isRTL ? c.nameAr : c.name }}</span>
                    </button>
                  </div>
                </div>
              </div>
              <button
                @click="store.toggleRTL"
                class="w-full text-center font-bold bg-primary text-white py-3 rounded-md shadow-md active:scale-95 transition-all"
              >
                {{ store.isRTL ? 'English' : 'العربية' }}
              </button>

              <div class="flex justify-center space-x-6 rtl:space-x-reverse pt-4 text-muted-foreground">
                <a v-if="facebook" :href="facebook" target="_blank" class="hover:text-primary transition-colors">
                  <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                  </svg>
                </a>
                <a v-if="instagram" :href="instagram" target="_blank" class="hover:text-primary transition-colors">
                  <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/>
                  </svg>
                </a>
                <a v-if="tiktok" :href="tiktok" target="_blank" class="hover:text-primary transition-colors">
                  <img src="/custom/tiktok.avif" alt="TikTok" class="h-6 w-6" />
                </a>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>
  </header>
</template>

<script>
import { ref } from 'vue'
import { useStore } from '@/stores/store'
import { Inertia } from '@inertiajs/inertia'
import { usePage } from '@inertiajs/inertia-vue3'
import FlagIcon from './FlagIcon.vue'

export default {
  props: {
    title: String,
    facebook: String,
    instagram: String,
    tiktok: String
  },
  components: {
    FlagIcon
  },
  setup() {
    const store = useStore()

    // Sync isMerchant state from Inertia props
    const { props } = usePage()
    if (props.value.isMerchant !== undefined) {
      store.isMerchant = props.value.isMerchant
    }
    if (props.value.country) {
      store.country = props.value.country
    }

    const isSearchOpen = ref(false)
    const isUserMenuOpen = ref(false)
    const isCountryMenuOpen = ref(false)
    const isMenuOpen = ref(false)
    const merchantCode = ref('')
    const searchQuery = ref('')
    const isMerchantInputFocused = ref(false)

    const countries = [
      { code: 'AE', name: 'United Arab Emirates', nameAr: 'الإمارات العربية المتحدة' },
      { code: 'LB', name: 'The Lebanese Republic', nameAr: 'الجمهورية اللبنانية' },
      { code: 'SY', name: 'Syrian Arab Republic', nameAr: 'الجمهورية العربية السورية' },
      { code: 'TR', name: 'The Republic of Türkiye', nameAr: 'جمهورية تركيا' },
    ]

    const handleCountryClick = (code) => {
      isCountryMenuOpen.value = false
      if (code === 'SY' || code === 'TR') {
        Inertia.visit(route('coming-soon'))
      } else {
        store.switchCountry(code)
      }
    }

    const goTo = (url) => {
      window.location.href = url
    }

    const logout = () => {
      Inertia.post(route('logout'))
    }

    const handleSearch = () => {
      if (searchQuery.value.trim()) {
        window.location.href = route('shop', { search: searchQuery.value.trim() })
      }
    }

    const handleMerchantVerify = async () => {
      if (!merchantCode.value) return

      const result = await store.verifyMerchantCode(merchantCode.value)

      if (window.Swal) {
        window.Swal.fire({
          icon: result.success ? 'success' : 'error',
          title: result.success
            ? (store.locale === 'ar' ? 'تم التحقق بنجاح' : 'Verified Successfully')
            : (store.locale === 'ar' ? 'رمز غير صالح' : 'Invalid Code'),
          text: result.message,
          confirmButtonText: store.locale === 'ar' ? 'حسناً' : 'OK',
          timer: result.success ? 2000 : undefined
        })
      } else {
        alert(result.message)
      }
    }

    return {
      store,
      isSearchOpen,
      isUserMenuOpen,
      isCountryMenuOpen,
      isMenuOpen,
      merchantCode,
      searchQuery,
      isMerchantInputFocused,
      countries,
      handleCountryClick,
      goTo,
      logout,
      handleSearch,
      handleMerchantVerify
    }
  }
}
</script>

<style scoped>
@keyframes marquee-ltr {
  0% { transform: translateX(100%); }
  100% { transform: translateX(-150%); }
}

@keyframes marquee-rtl {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(150%); }
}

.animate-marquee-ltr {
  animation: marquee-ltr 15s linear infinite;
  display: inline-block;
  min-width: 100%;
}

.animate-marquee-rtl {
  animation: marquee-rtl 15s linear infinite;
  display: inline-block;
  min-width: 100%;
}

.animate-marquee:hover {
  animation-play-state: paused;
}
</style>
