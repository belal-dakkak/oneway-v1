<template>
  <div
    class="group cursor-pointer overflow-hidden transition-all hover:shadow-lg bg-card rounded-lg border border-border"
    @click="handleProductClick"
  >
    <div class="relative">
      <div class="aspect-[9/16] overflow-hidden bg-muted">
        <img
            :src="product.colors && product.colors[0]?.stock > 0 && product.colors[0]?.photo_url
            ||
            product.colors && product.colors[1]?.stock > 0 && product.colors[1]?.photo_url
            ||
            product.colors && product.colors[2]?.stock > 0 && product.colors[2]?.photo_url
             || product.image || '/api/placeholder/300/400'"
          :alt="product.name"
          class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300"
        />
      </div>

      <!-- Favorite Button (Top Left) -->
      <button
        class="absolute top-2 left-2 z-10 bg-white/80 hover:bg-white text-foreground p-1.5 rounded-md shadow-sm transition-colors"
        @click.stop="handleFavoriteToggle"
      >
        <svg
          class="h-4 w-4"
          :class="{ 'fill-red-500 text-red-500': favorite }"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
        </svg>
      </button>

      <!-- Section Label Badge (Top Right) -->
        <ShareButton
            class="absolute top-10 left-2 z-10 bg-white/80 hover:bg-white text-foreground pb-1.5 p-1 rounded-md shadow-sm transition-colors"
            :product-name="product.name"
            :product-url="route('product.show', product.id)"
        />
      <div
        v-if="label"
        class="absolute top-2 right-2 z-10 bg-[#c20000] text-white text-[10px] md:text-xs uppercase font-bold px-2 py-0.5 rounded shadow-sm pointer-events-none"
      >
        {{ label }}
      </div>
    </div>

    <div class="py-3 px-2">
      <!-- Line 1: Product Name -->
      <h3 class="font-medium text-md md:text-lg mb-1 line-clamp-1 group-hover:text-[#c20000] transition-colors">
        {{ product.name }}
      </h3>

      <div class="flex items-center flex-wrap gap-1 mb-1 text-[12px] md:text-sm">
        <template v-if="$page.props.isMerchant">
          <span class="font-bold text-[#c20000]">
            {{ store.formatPrice(store.convertFromUsd(product.sale_price)) }}
          </span>
          <span class="bg-primary/10 text-primary text-[10px] font-bold px-1 rounded border border-primary/20 uppercase">{{ store.t('wholesalePrice') }}</span>
        </template>
        <template v-else>
          <span class="font-bold text-[#c20000]">
            {{ store.formatPrice(store.convertFromUsd(product.retail_price)) }}
          </span>
          <span
            v-if="hasDiscount"
            class="text-muted-foreground line-through scale-90 origin-left"
          >
            {{ store.formatPrice(store.convertFromUsd(product.price_before_discount)) }}
          </span>
          <span
            v-if="hasDiscount"
            class="text-green-600 font-semibold"
          >
            {{ discountPercentage }}%
          </span>
        </template>
        <syp-equivalent :usd="$page.props.isMerchant ? product.sale_price : product.retail_price" />
      </div>

      <!-- Line 3: Actions (Review Right, Cart Left) -->
        <div class="flex items-center justify-between pt-1 border-t border-border/50">
        <button
          @click.stop="quickAddToCart"
          class="bg-[#c20000] hover:bg-[#750000] text-white p-1.5 rounded transition-colors shadow-sm"
          title="Add to Cart"
        >
          <svg class="h-3 w-3 md:h-4 md:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </button>

        <span class="text-black text-xs">code:{{ displayBarcode }}</span>

        <div class="flex items-center space-x-1 rtl:space-x-reverse text-[10px] text-muted-foreground">
          <div class="flex items-center" :title="displayRate.toFixed(1) + ' / 5'">
            <svg v-for="i in 5" :key="i" class="h-2 w-2 md:h-3 md:w-3" :class="i <= Math.round(displayRate) ? 'text-yellow-400 fill-yellow-400' : 'text-gray-300 fill-gray-300'" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
          </div>
          <span class="font-medium">({{ displayReviews }})</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {computed, ref} from 'vue'
import { useStore } from '@/stores/store'
import ShareButton from './Share.vue'

export default {
    components: {
        ShareButton
    },
  props: {
    product: {
      type: Object,
      required: true
    },
    label: {
      type: String,
      default: ''
    }
  },
  setup(props) {
    const store = useStore()

    const selectedColor = ref(props.product.colors?.[0] || null)

    const displayBarcode = computed(() => {
      const barcode = selectedColor.value?.barcode || ''
      return barcode.toString().substring(0, 5)
    })
    const discountPercentage = computed(() => {
      const original = parseFloat(props.product.price_before_discount || 0)
      const current = parseFloat(props.product.retail_price || 0)
      if (original > current && original > 0) {
        return Math.round(((original - current) / original) * 100)
      }
      return 0
    })
    const hasDiscount = computed(() => discountPercentage.value > 0)
    const favorite = computed(() => store.isFavorite(props.product.id))

    // Random stars between 4 and 5
    const displayRate = computed(() => {
      if (props.product.rate && props.product.rate > 0) return props.product.rate
      // Generate a stable random rate for this instance
      return 4 + (props.product.id % 10) / 10 + (Math.random() * 0.1)
    })

    const displayReviews = computed(() => {
      if (props.product.reviews_count && props.product.reviews_count > 0) return props.product.reviews_count
      return 10 + (props.product.id % 40)
    })

    const handleProductClick = () => {
      window.location.href = route('product.show', props.product.id)
    }

    const handleFavoriteToggle = (event) => {
      event.stopPropagation()
      store.toggleFavorite(props.product.id)

      const isFavorite = store.isFavorite(props.product.id)
      if (window.Swal) {
        window.Swal.fire({
          icon: isFavorite ? 'success' : 'info',
          title: isFavorite
            ? (store.locale === 'ar' ? 'تمت الإضافة للمفضلة' : 'Added to Favorites')
            : (store.locale === 'ar' ? 'تمت الإزالة من المفضلة' : 'Removed from Favorites'),
          toast: true,
          position: store.isRTL ? 'top-start' : 'top-end',
          timer: 2000,
          showConfirmButton: false
        })
      }
    }

    const quickAddToCart = () => {
      // Select the first color if available
      const defaultColor = props.product.colors?.[0] || { id: 'default', color_name: 'Generic' }

      // Select the first size from the pre-filtered sizes array
      const defaultSize = props.product.sizes?.[0] || 'default'

      // Get stock from user_products for the first color/size combo
      const firstUserProduct = props.product.colors?.[0]?.userProducts?.[0]
      const stock = firstUserProduct?.stock ?? null

      const productToAdd = {
        ...props.product,
        price: store.isMerchant
          ? parseFloat(props.product.sale_price || 0)
          : parseFloat(props.product.retail_price || 0),
        final_price_value: store.isMerchant
          ? props.product.wholesale_price_value
          : props.product.final_price_value
      }

      const result = store.addToCart(productToAdd, defaultColor, { size: defaultSize }, 1, stock)

      if (!result.success) {
        if (window.Swal) {
          window.Swal.fire({
            icon: 'warning',
            title: store.locale === 'ar' ? 'الكمية غير متوفرة' : 'Out of Stock',
            text: store.locale === 'ar'
              ? `الكمية المتاحة: ${result.maxStock}`
              : `Available stock: ${result.maxStock}`,
            timer: 3000,
            showConfirmButton: false,
            toast: true
          })
        }
        return
      }

      // Feedback to user
      if (window.Swal) {
        window.Swal.fire({
          icon: 'success',
          title: store.locale === 'ar' ? 'تمت الإضافة للسلة' : 'Added to Cart',
          text: props.product.name,
          timer: 2000,
          showConfirmButton: false,
          toast: true
        })
      }
    }

    return {
      store,
      hasDiscount,
      discountPercentage,
      displayBarcode,
      favorite,
      displayRate,
      displayReviews,
      handleProductClick,
      handleFavoriteToggle,
      quickAddToCart
    }
  }
}
</script>
