<template>
  <div class="min-h-screen" :class="{ 'rtl': store.isRTL }">
    <Header
      :title="title"
      :facebook="facebook"
      :instagram="instagram"
      :tiktok="tiktok"
      @menu-toggle="handleMenuToggle"
    />

    <Head>
      <title>{{ store.locale === 'ar' ? product.name : product.name_en }} | One Way</title>
      <meta name="description" :content="product.details" />
      <meta property="og:title" :content="store.locale === 'ar' ? product.name : product.name_en" />
      <meta property="og:description" :content="product.details" />
      <meta property="og:image" :content="product.image_url" />
    </Head>

    <main class="container mx-auto px-4 py-8">
      <div v-if="product" class="grid lg:grid-cols-12 gap-12 mb-16">
        <div class="lg:col-span-4">
          <ProductGallery
            :images="galleryImages"
            :initial-index="activeIndex"
            @change="handleGalleryChange"
          >
            <!-- Sale Badge Slot (Desktop) -->
            <template #badges>
              <div class="absolute top-4 rtl:right-4 ltr:left-4 flex flex-col space-y-2 pointer-events-none">
                <span
                  v-if="hasDiscount && !$page.props.isMerchant"
                  class="bg-destructive text-destructive-foreground px-2 py-1 rounded text-sm font-medium shadow-sm"
                >
                  -{{ discountPercentage }}%
                </span>
                <span
                  v-if="$page.props.isMerchant"
                  class="bg-primary text-white px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider shadow-sm"
                >
                  {{ store.t('wholesalePrice') }}
                </span>
              </div>
            </template>

            <!-- Sale Badge Slot (Mobile) -->
            <template #badges-mobile>
              <div class="absolute top-4 rtl:right-4 ltr:left-4 flex flex-col space-y-2 pointer-events-none">
                <span
                  v-if="hasDiscount && !$page.props.isMerchant"
                  class="bg-destructive text-destructive-foreground px-2 py-1 rounded text-xs font-bold shadow-md"
                >
                  -{{ discountPercentage }}%
                </span>
              </div>
            </template>
          </ProductGallery>

            <!-- Mobile Optimized Head (Name, Price) -->
            <div class="lg:hidden mt-4 pb-6 border-b border-border/50">
              <span class="text-xs font-medium text-primary uppercase tracking-wider">{{ store.locale === 'ar' ? (product.category?.name || product.category?.name) : (product.category?.name_en || product.category?.name) }}</span>
              <h1 class="text-2xl font-extrabold text-foreground tracking-tight mb-2">
                {{ store.locale === 'ar' ? (product.name || product.name) : (product.name_en || product.name) }}
              </h1>
              <div class="flex items-center space-x-4 rtl:space-x-reverse">
                <template v-if="$page.props.isMerchant">
                  <span class="text-2xl font-bold text-primary">
                    {{ store.formatPrice(store.convertFromUsd(product.sale_price)) }}
                  </span>
                </template>
                <template v-else>
                  <span class="text-2xl font-bold text-primary">
                    {{ store.formatPrice(store.convertFromUsd(product.retail_price)) }}
                  </span>
                  <span v-if="hasDiscount" class="text-lg text-muted-foreground line-through">
                    {{ store.formatPrice(store.convertFromUsd(product.price_before_discount)) }}
                  </span>
                </template>
              </div>
            </div>

            <!-- Repositioned Color Selection (Mobile Optimized) -->
          <div v-if="product.colors && product.colors.length > 0" class="mt-8 mb-8">
            <h3 class="font-semibold mb-4 text-lg flex items-center justify-between rtl:text-right">
              <span>{{ store.t('color') }}: <span class="text-primary font-bold ml-2">{{ selectedColor?.color_name }}</span></span>
            </h3>
            <div class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 gap-3">
              <button
                v-for="(color, index) in product.colors"
                :key="color.id"
                class="relative aspect-[3/4] rounded-lg border-2 transition-all p-0.5 overflow-hidden group shadow-sm bg-muted"
                :class="selectedColor && selectedColor.id === color.id ? 'border-primary ring-2 ring-primary/20 scale-105 z-10' : 'border-border hover:border-primary/50'"
                @click="handleColorSelect(color, index)"
                :title="color.color_name"
              >
                <img :src="color.photo_url" :alt="color.color_name" class="w-full h-full object-cover rounded-md transition-transform duration-300 group-hover:scale-110" />
                <div v-if="selectedColor && selectedColor.id === color.id" class="absolute inset-0 bg-primary/10 pointer-events-none"></div>
              </button>
            </div>
          </div>
          <!-- Size Selection (Mobile Optimized - under images/colors) -->
          <div v-if="availableSizes.length > 0" class="mt-8 mb-8 lg:hidden pb-6 border-b border-border/50">
            <h3 class="font-semibold mb-4 text-lg flex items-center justify-between rtl:text-right">
              <span>{{ store.t('availableSizes') }}: <span class="text-primary font-bold ml-2">{{ selectedSize }}</span></span>
            </h3>
            <div class="flex flex-wrap gap-3">
              <button
                v-for="size in availableSizes"
                :key="size.size"
                class="min-w-[50px] px-4 py-2.5 border rounded-lg font-bold transition-all shadow-sm flex flex-col items-center justify-center gap-0.5"
                :class="selectedSize === size.size ? 'bg-primary hover:text-white text-white border-primary shadow-primary/20 scale-105' : 'bg-card border-border hover:border-primary hover:text-primary text-foreground/80'"
                :disabled="size.stock <= 0"
                @click="handleSizeSelect(size.size, size.barcode)"
              >
                <span class="text-base">{{ size.size }}</span>
                <span v-if="size.stock <= 0" class="text-[9px] font-medium text-destructive leading-none">{{ store.locale === 'ar' ? 'نفذت' : 'Out' }}</span>
              </button>
            </div>
          </div>

          <!-- Mobile Optimized Quantity & Actions (Under Sizes) -->
          <div class="lg:hidden space-y-6">
            <!-- Quantity/Stock -->
            <div class="mb-4">
              <h3 class="font-semibold mb-3 flex items-center gap-3 text-lg">
                {{ store.t('quantity') }}
                <span v-if="selectedColor && selectedSizeStock !== Infinity" class="text-sm font-normal text-muted-foreground">
                  ({{ store.locale === 'ar' ? `${selectedSizeStock} متوفر` : `${selectedSizeStock} in stock` }})
                </span>
              </h3>
              <div class="flex items-center space-x-4 rtl:space-x-reverse">
                <div class="flex items-center border border-border rounded-lg bg-card h-12">
                  <button class="px-4 h-full hover:bg-accent transition-colors" @click="quantity = Math.max(1, quantity - 1)">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>
                  </button>
                  <span class="w-10 text-center font-bold text-lg">{{ quantity }}</span>
                  <button class="px-4 h-full hover:bg-accent transition-colors disabled:opacity-40" @click="quantity = Math.min(selectedSizeStock, quantity + 1)" :disabled="quantity >= selectedSizeStock">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                  </button>
                </div>
                <span v-if="selectedSize && selectedSizeStock <= 5 && selectedSizeStock > 0" class="text-xs font-semibold text-amber-600">
                  {{ store.locale === 'ar' ? `متبقي ${selectedSizeStock} فقط` : `Only ${selectedSizeStock} left` }}
                </span>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col gap-3 pb-8 border-b border-border/50">
              <button
                class="w-full bg-[#c20000] text-white hover:bg-[#c20000]/90 h-14 rounded-xl text-lg font-bold flex items-center justify-center transition-all shadow-lg active:scale-95 disabled:opacity-50"
                @click="addToCart"
                :disabled="!selectedSize"
              >
                <svg class="h-6 w-6 rtl:ml-3 ltr:mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                {{ selectedSize ? store.t('addToCart') : store.t('selectSize') }}
              </button>

              <div class="grid grid-cols-2 gap-3">
                <button
                  class="h-12 border border-border rounded-xl hover:bg-accent transition-colors flex items-center justify-center gap-2 font-bold text-xs"
                  @click="toggleFavoriteDetail"
                >
                  <svg class="h-5 w-5" :class="{ 'fill-red-500 text-red-500': favorite }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                  </svg>
                  {{ favorite ? store.t('inFavorites') : store.t('addToFavorites') }}
                </button>
                <button
                  class="h-12 border border-border rounded-xl hover:bg-accent transition-colors flex items-center justify-center gap-2 font-bold text-xs"
                  @click="shareProduct(product.name, `/product/${product.id}`)"
                >
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M12 3v12m-4-4l4-4 4 4M4 13v6a2 2 0 002 2h12a2 2 0 002-2v-6" />
                  </svg>
                  {{ store.t('share') }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Product Details -->
        <div class="lg:col-span-8 space-y-6 rtl:text-right">
          <div class="hidden lg:block">
            <span class="text-sm font-medium text-primary uppercase tracking-wider">{{ store.locale === 'ar' ? (product.category?.name || product.category?.name) : (product.category?.name_en || product.category?.name) }}</span>
            <div class="flex items-center gap-4 mb-2">
              <h1 class="text-3xl md:text-4xl font-extrabold text-foreground tracking-tight">
                {{ store.locale === 'ar' ? (product.name || product.name) : (product.name_en || product.name) }}
              </h1>
              <span v-if="displayBarcode" class="px-2 py-1 bg-muted text-muted-foreground text-xs font-mono rounded border border-border self-center">
                {{ displayBarcode }}
              </span>
            </div>
            <div class="flex items-center space-x-4 rtl:space-x-reverse mb-6">
              <template v-if="$page.props.isMerchant">
                <span class="text-3xl font-bold text-primary">
                  {{ store.formatPrice(store.convertFromUsd(product.sale_price)) }}
                </span>
                <span class="text-sm font-bold text-primary underline decoration-2 underline-offset-4 uppercase tracking-tighter">{{ store.t('wholesalePrice') }}</span>
              </template>
              <template v-else>
                <span class="text-3xl font-bold text-primary">
                  {{ store.formatPrice(store.convertFromUsd(product.retail_price)) }}
                </span>
                <span
                  v-if="hasDiscount"
                  class="text-xl text-muted-foreground line-through"
                >
                  {{ store.formatPrice(store.convertFromUsd(product.price_before_discount)) }}
                </span>
              </template>
            </div>
          </div>

          <div class="border-t border-border pt-6">
            <!-- Description -->
            <div class="mb-8">
              <h3 class="font-semibold text-lg mb-3">{{ store.t('description') }}</h3>
              <p class="text-muted-foreground leading-relaxed">
                {{ store.locale === 'ar' ? (product.details || product.details || 'لا يوجد وصف متاح لهذا المنتج.') : (product.details_en || product.details || 'No description available for this product.') }}
              </p>
            </div>



            <!-- Size Selection -->
            <div v-if="availableSizes.length > 0" class="mb-8 hidden lg:block">
              <h3 class="font-semibold mb-3">{{ store.t('availableSizes') }}</h3>
              <div class="flex flex-wrap gap-3">
                <button
                  v-for="size in availableSizes"
                  :key="size.size"
                  class="px-6 py-2 border rounded-md font-medium transition-all"
                  :class="selectedSize === size.size ? 'bg-primary text-white hover:text-white border-primary' : 'border-border hover:border-primary hover:text-primary'"
                  :disabled="size.stock <= 0"
                  @click="handleSizeSelect(size.size, size.barcode)"
                >
                  {{ size.size }}
                  <span v-if="size.stock <= 0" class="text-xs block text-destructive">{{ store.locale === 'ar' ? 'نفذت الكمية' : 'Sold Out' }}</span>
                </button>
              </div>
            </div>

            <!-- Quantity (Desktop Only) -->
            <div class="mb-8 hidden lg:block">
              <h3 class="font-semibold mb-3 flex items-center gap-3">
                {{ store.t('quantity') }}
                <span v-if="selectedColor && selectedSizeStock !== Infinity" class="text-sm font-normal text-muted-foreground">
                  ({{ store.locale === 'ar' ? `${selectedSizeStock} متوفر` : `${selectedSizeStock} in stock` }})
                </span>
              </h3>
              <div class="flex items-center space-x-4 rtl:space-x-reverse">
                <div class="flex items-center border border-border rounded-md">
                  <button
                    class="p-3 hover:bg-accent transition-colors"
                    @click="quantity = Math.max(1, quantity - 1)"
                  >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                    </svg>
                  </button>
                  <span class="w-12 text-center font-bold">{{ quantity }}</span>
                  <button
                    class="p-3 hover:bg-accent transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                    @click="quantity = Math.min(selectedSizeStock, quantity + 1)"
                    :disabled="quantity >= selectedSizeStock"
                  >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                  </button>
                </div>
                <!-- Low stock warning -->
                <span v-if="selectedSize && selectedSizeStock <= 5 && selectedSizeStock > 0" class="text-xs font-semibold text-amber-600">
                  {{ store.locale === 'ar' ? `متبقي ${selectedSizeStock} فقط` : `Only ${selectedSizeStock} left` }}
                </span>
              </div>
            </div>

            <!-- Actions (Desktop Only) -->
            <div class="hidden lg:flex flex-col sm:flex-row gap-4 mb-8">
              <button
                class="flex-1 bg-primary text-white hover:text-white hover:bg-primary/90 px-8 py-4 rounded-md text-lg font-bold flex items-center justify-center transition-all shadow-lg shadow-primary/20"
                @click="addToCart"
                :disabled="!selectedSize"
              >
                <svg class="h-5 w-5 rtl:ml-3 ltr:mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                {{ selectedSize ? store.t('addToCart') : store.t('selectSize') }}
              </button>
              <div class="flex flex-col gap-4">
                <button
                  class="p-4 border border-border rounded-md hover:bg-accent transition-colors flex items-center justify-center"
                  @click="toggleFavoriteDetail"
                >
                  <svg
                    class="h-6 w-6"
                    :class="{ 'fill-red-500 text-red-500': favorite }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                  </svg>
                </button>

                <button @click="shareProduct(product.name, `/product/${product.id}`)"
                    class="p-4 border border-border rounded-md hover:bg-accent transition-colors flex items-center justify-center"
                >
                  <svg
                      xmlns="http://www.w3.org/2000/svg"
                      width="22"
                      height="22"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                  >
                    <path d="M12 3v12"></path>
                    <path d="M8 7l4-4 4 4"></path>
                    <rect x="4" y="13" width="16" height="8" rx="2"></rect>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Trust Badges -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-lg border border-border">
              <div class="flex items-center space-x-3 rtl:space-x-reverse">
                <div class="p-2 bg-background rounded-full">
                  <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
                <span class="text-sm font-medium">{{ store.t('genuineProduct') }}</span>
              </div>
              <div class="flex items-center space-x-3 rtl:space-x-reverse">
                <div class="p-2 bg-background rounded-full">
                  <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                  </svg>
                </div>
                <span class="text-sm font-medium">{{ store.t('secureCheckout') }}</span>
              </div>
                <a class="flex items-center space-x-3 rtl:space-x-reverse" :href="route('shipping-policy')">
                    <div class="p-2 bg-background rounded-full">
                        <svg class="h-5 w-5 text-white" fill="#22c55e" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <span class="text-sm font-medium">{{ store.t('shippingPolicyTitle') }}</span>
                </a>
                <a class="flex items-center space-x-3 rtl:space-x-reverse" :href="route('refund-policy')">
                    <div class="p-2 bg-background rounded-full">
                        <svg class="h-5 w-5 text-white" fill="#22c55e" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <span class="text-sm font-medium">{{ store.t('refundPolicyTitle') }}</span>
                </a>
                <div class="flex items-center space-x-3 rtl:space-x-reverse">
                    <div class="p-2 bg-background rounded-full">
                        <svg class="h-5 w-5 text-white" fill="#22c55e" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <span class="text-sm font-medium">{{ store.t('shippingFeeMessage') }}</span>
                </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Related Products -->
      <div v-if="relatedProducts && relatedProducts.length > 0" class="pb-20 text-center">
        <ProductCarousel
          :title="store.t('youMightAlsoLike')"
          :products="transformList(relatedProducts)"
          class="mt-20"
        />
        <div class="mt-8">
          <a
            :href="route('shop')"
            class="inline-block bg-[#c20000] hover:bg-white text-white hover:text-[#c20000] font-bold py-3 px-10 rounded-md transition-all border-2 border-[#c20000] shadow-lg hover:shadow-xl"
          >
            {{ store.t('showAllProducts') }}
          </a>
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
    <FloatingButtons :whatsapp-number="whatsapp || phone" :is-r-t-l="store.isRTL" />
  </div>
</template>

<script>
import { Head } from '@inertiajs/inertia-vue3'
import Header from '../Components/Website/Header.vue'
import ProductCard from '../Components/Website/ProductCard.vue'
import Sitemap from '../Components/Website/Sitemap.vue'
import Footer from '../Components/Website/Footer.vue'
import FloatingButtons from '../Components/Website/FloatingButtons.vue'
import ProductCarousel from '../Components/Website/ProductCarousel.vue'
import ProductGallery from '../Components/Website/ProductGallery.vue'
import { ref, computed, onMounted } from 'vue'
import { useStore } from '@/stores/store'
import ShareButton from '../Components/Website/Share.vue'
import Button from "@/Jetstream/Button.vue";

export default {
  methods: {
    async shareProduct(productName, productUrl) {
      const fullUrl = window.location.origin + productUrl;

      const shareData = {
        title: productName,
        text: this.store.t('checkThisProductOut'),
        url: fullUrl
      };

      if (navigator.share) {
        try {
          await navigator.share(shareData);
        } catch (error) {

        }
      } else {
        // Fallback
        navigator.clipboard.writeText(fullUrl);
        window.open(
            `https://wa.me/?text=${encodeURIComponent(this.store.t('checkThisProductOut') + ' ' + fullUrl)}`,
            "_blank"
        );
      }
    },
    transformList(list) {
      return list.map(item => this.transformProduct(item))
    },
    transformProduct(item) {
      const product = item;
      return {
        id: product.id,
        name: this.store.locale === 'ar' ? (product.name || product.name) : (product.name_en || product.name),
        image: product.image_url || product.photo_url || product.image || '/api/placeholder/300/400',
        retail_price: product.retail_price,
        price_before_discount: product.price_before_discount,
        price_before_discount_value: product.price_before_discount_value,
        sale_price: product.sale_price,
        final_price_value: product.final_price_value,
        wholesale_price_value: product.wholesale_price_value,
        isNew: false,
        colors: product.colors || [],
        rate: 4 + Math.random(),
        reviews_count: Math.floor(Math.random() * 50) + 10,
        formatted_wholesale_price: product.formatted_wholesale_price || ''
      }
    },
  },
  components: {
    Head,
    Button,
    Header,
    ProductCard,
    Sitemap,
    Footer,
    FloatingButtons,
    ProductCarousel,
    ProductGallery,
    ShareButton
  },
  props: {
    product: Object,
    relatedProducts: Array,
    categories: Array,
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
    const selectedColor = ref(props.product.colors?.[0] || null)
    const selectedSize = ref(null)
    const quantity = ref(1)

    const displayBarcode = computed(() => {
      const barcode = selectedColor.value?.barcode || ''
      return barcode.toString().substring(0, 5)
    })

    // Gallery State
    const activeIndex = ref(0)

    const selectedSizeData = computed(() => {
      if (!selectedSize.value || !selectedColor.value) return null
      return selectedColor.value.user_products?.find(up => up.size === selectedSize.value) || null
    })

    const displayPriceValue = computed(() => {
      return props.product.final_price_value
    })

    const displayOldPriceValue = computed(() => {
      return props.product.price_before_discount_value
    })

    const hasDiscount = computed(() => {
      const original = parseFloat(props.product.price_before_discount_value)
      const current = parseFloat(props.product.final_price_value)
      return original > current
    })

    const discountPercentage = computed(() => {
      const original = parseFloat(props.product.price_before_discount_value)
      const current = parseFloat(props.product.final_price_value)
      if (original > current) {
        return Math.round(((original - current) / original) * 100)
      }
      return 0
    })

    const galleryImages = computed(() => {
      return props.product.colors.map(color => ({
        id: color.id,
        src: color.photo_url || color.image,
        alt: color.color_name
      }))
    })

    const availableSizes = computed(() => {
      if (!selectedColor.value) return []

      // Primary: read from userProducts (real stock from user_products table)
      const ups = selectedColor.value.user_products;
      if (ups && ups.length > 0) {
        // Group by size to handle duplicates (same size in multiple stores)
        const sizeMap = new Map()
        
        ups
          .filter(up => up.size && up.stock > 0)
          .forEach(up => {
            const size = up.size
            if (sizeMap.has(size)) {
              // Aggregate stock for duplicate sizes
              const existing = sizeMap.get(size)
              existing.stock += up.stock
            } else {
              sizeMap.set(size, {
                size: up.size,
                stock: up.stock,
                barcode: up.barcode,
                final_price: up.final_price,
                formatted_price_before_discount: up.formatted_price_before_discount
              })
            }
          })
        
        return Array.from(sizeMap.values())
      }

      // Fallback: read from the sizes JSON field (product_colors.sizes — less accurate)
      if (!selectedColor.value.sizes) return []
      try {
        const sizes = typeof selectedColor.value.sizes === 'string'
          ? JSON.parse(selectedColor.value.sizes)
          : selectedColor.value.sizes
        
        // Deduplicate and aggregate sizes in fallback as well
        const sizeMap = new Map()
        ;(sizes || []).filter(s => s.size && s.stock > 0).forEach(s => {
          const size = s.size
          const stockVal = parseInt(s.stock) || 0
          if (sizeMap.has(size)) {
            const existing = sizeMap.get(size)
            existing.stock = (parseInt(existing.stock) || 0) + stockVal
          } else {
            sizeMap.set(size, { ...s, stock: stockVal })
          }
        })
        
        return Array.from(sizeMap.values())
      } catch (e) {
        console.error('Error parsing sizes', e)
        return []
      }
    })

    // Stock is per size if selected, otherwise per color
    const selectedSizeStock = computed(() => {
      if (!selectedColor.value) return Infinity

      // If a size is selected, find its specific stock
      if (selectedSize.value) {
        const ups = selectedColor.value.user_products
        if (ups && ups.length > 0) {
          // Combine stock for the selected size across all stores/shops
          return ups
            .filter(up => up.size === selectedSize.value)
            .reduce((sum, up) => sum + (up.stock || 0), 0)
        }

        // Fallback to sizes JSON if user_products is empty
        if (selectedColor.value.sizes) {
          try {
            const sizes = typeof selectedColor.value.sizes === 'string'
              ? JSON.parse(selectedColor.value.sizes)
              : selectedColor.value.sizes
            // Combine stock in fallback if there are multiple entries for the same size
            return (sizes || [])
              .filter(s => s.size === selectedSize.value)
              .reduce((sum, s) => sum + (parseInt(s.stock) || 0), 0)
          } catch (e) {
            console.error('Error parsing sizes for stock', e)
          }
        }
        return 0
      }

      // If no size selected, sum all userProducts entries for the selected color (as a general limit)
      const ups = selectedColor.value.user_products
      if (ups && ups.length > 0) {
        return ups.reduce((sum, up) => sum + (up.stock || 0), 0)
      }

      // Final fallback
      return Infinity
    })

    const favorite = computed(() => store.isFavorite(props.product.id))

    const handleColorSelect = (color, index) => {
      selectedColor.value = color
      selectedSize.value = null // Reset size when color changes
      quantity.value = 1        // Reset quantity too
      activeIndex.value = index // Sync gallery index if opened from here
    }

    const handleGalleryChange = (index) => {
      if (props.product.colors[index]) {
        selectedColor.value = props.product.colors[index]
        // We DON'T reset size here to allow user to swipe images while keeping size selected
      }
    }

    const handleSizeSelect = (size, barcode = null) => {
      selectedSize.value = size
      // selectedBarcode.value = barcode // No longer overriding on size select as per user request
      quantity.value = 1 // Reset quantity when size changes
    }

    const openGallery = (index) => {
      activeIndex.value = index
    }

    const addToCart = () => {
      if (!selectedColor.value) {
        const msg = store.locale === 'ar' ? 'يرجى اختيار اللون أولاً' : 'Please select a color first'
        if (window.Swal) {
          window.Swal.fire({
            icon: 'warning',
            title: msg,
            confirmButtonText: store.locale === 'ar' ? 'حسناً' : 'OK'
          })
        } else {
          alert(msg)
        }
        return
      }

      if (!selectedSize.value) {
        const msg = store.locale === 'ar' ? 'يرجى اختيار المقاس أولاً' : 'Please select a size first'
        if (window.Swal) {
          window.Swal.fire({
            icon: 'warning',
            title: msg,
            confirmButtonText: store.locale === 'ar' ? 'حسناً' : 'OK'
          })
        } else {
          alert(msg)
        }
        return
      }

      const productToAdd = {
        ...props.product,
        name: store.locale === 'ar' ? (props.product.name_ar || props.product.name) : (props.product.name_en || props.product.name),
        price: store.isMerchant
          ? parseFloat(props.product.sale_price)
          : parseFloat(props.product.retail_price),
        final_price_value: store.isMerchant
          ? props.product.wholesale_price_value
          : props.product.final_price_value
      }

      const result = store.addToCart(productToAdd, selectedColor.value, { size: selectedSize.value }, quantity.value, selectedSizeStock.value)

      if (!result.success) {
        const msg = store.locale === 'ar'
          ? `لا يمكن إضافة هذه الكمية. الكمية المتاحة: ${result.maxStock}`
          : `Cannot add this quantity. Available stock: ${result.maxStock}`

        if (window.Swal) {
          window.Swal.fire({
            icon: 'error',
            title: store.locale === 'ar' ? 'خطأ' : 'Error',
            text: msg,
            confirmButtonText: store.locale === 'ar' ? 'حسناً' : 'OK'
          })
        } else {
          alert(msg)
        }
        return
      }

      const msg = store.locale === 'ar' ? 'تمت إضافة المنتج إلى السلة!' : 'Product added to cart!'

      if (window.Swal) {
        window.Swal.fire({
          icon: 'success',
          title: msg,
          timer: 2000,
          showConfirmButton: false,
          toast: true
        })
      }
    }

    const transformRelated = (p) => {
      return {
        id: p.id,
        name: store.locale === 'ar' ? (p.name || p.name) : (p.name_en || p.name),
        image: p.photo_url || p.image,
        originalPrice: p.formatted_price_before_discount,
        discountedPrice: p.final_price,
        final_price: p.final_price,
        discountPercentage: p.price_before_discount > p.retail_price ? Math.round(((p.price_before_discount - p.retail_price) / p.price_before_discount) * 100) : 0,
        colors: p.colors || [],
        rate: 4 + (p.id % 10) / 10, // Deterministic random rate
        reviews_count: 10 + (p.id % 40)
      }
    }

    const handleMenuToggle = () => {

    }

    onMounted(() => {
      if (availableSizes.value.length === 1 && availableSizes.value[0].stock > 0) {
        selectedSize.value = availableSizes.value[0].size
      }
    })

    const toggleFavoriteDetail = () => {
      store.toggleFavorite(props.product.id)
      const isFavorite = store.isFavorite(props.product.id)
      if (window.Swal) {
        window.Swal.fire({
          icon: isFavorite ? 'success' : 'info',
          title: isFavorite
            ? (store.locale === 'ar' ? 'تمت الإضافة للمفضلة' : 'Added to Favorites')
            : (store.locale === 'ar' ? 'تمت الإزالة من المفضلة' : 'Removed from Favorites'),
          toast: true,
          timer: 2000,
          showConfirmButton: false
        })
      }
    }

    return {
      store,
      selectedColor,
      selectedSize,
      displayBarcode,
      quantity,
      handleColorSelect,
      hasDiscount,
      discountPercentage,
      galleryImages,
      activeIndex,
      availableSizes,
      selectedSizeStock,
      favorite,
      handleSizeSelect,
      handleGalleryChange,
      addToCart,
      transformRelated,
      handleMenuToggle,
      displayPriceValue,
      displayOldPriceValue,
      selectedSizeData,
      toggleFavoriteDetail
    }
  }
}
</script>

<style scoped>
:deep(.carousel__prev),
:deep(.carousel__next) {
  background-color: white;
  color: #c20000;
  border-radius: 50%;
  width: 48px;
  height: 48px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.3);
  transition: all 0.3s ease;
}

:deep(.carousel__prev:hover),
:deep(.carousel__next:hover) {
  transform: scale(1.1);
  background-color: #c20000;
  color: white;
}

:deep(.carousel__track) {
  display: flex;
  align-items: center;
}

::-webkit-scrollbar {
  height: 4px;
}

::-webkit-scrollbar-thumb {
  background: rgba(255,255,255,0.2);
  border-radius: 10px;
}

::-webkit-scrollbar-track {
  background: transparent;
}
</style>
