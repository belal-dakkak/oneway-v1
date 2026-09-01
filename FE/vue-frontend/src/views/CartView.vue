<template>
  <div class="min-h-screen" :class="{ 'rtl': store.isRTL, 'ltr': !store.isRTL }">
    <Header @menu-toggle="handleMenuToggle" />
    
    <main class="container mx-auto px-4 py-8">
      <h1 class="text-3xl font-bold mb-8">Shopping Cart</h1>

      <div v-if="store.cart.length === 0" class="text-center">
        <ShoppingBag class="h-24 w-24 mx-auto text-muted-foreground mb-6" />
        <h2 class="text-3xl font-bold mb-4">Your cart is empty</h2>
        <p class="text-muted-foreground mb-8">
          Looks like you haven't added any items to your cart yet.
        </p>
        <button 
          class="px-6 py-3 bg-accent hover:bg-accent/90 text-accent-foreground rounded-md text-lg"
          @click="$router.push('/products')"
        >
          Continue Shopping
          <ArrowRight class="h-4 w-4 ml-2 inline" />
        </button>
      </div>

      <div v-else class="grid lg:grid-cols-3 gap-8">
        <!-- Cart items -->
        <div class="lg:col-span-2 space-y-4">
          <div
            v-for="item in store.cart"
            :key="item.id"
            class="bg-card rounded-lg p-6 shadow-sm border border-border"
          >
            <div class="flex gap-4">
              <!-- Product image -->
              <div class="w-24 h-24 bg-muted rounded-lg overflow-hidden flex-shrink-0">
                <img
                  :src="item.color.image || '/api/placeholder/100/100'"
                  :alt="item.product.name"
                  class="w-full h-full object-cover"
                />
              </div>

              <!-- Product details -->
              <div class="flex-1">
                <div class="flex justify-between mb-2">
                  <div>
                    <h3 class="font-semibold">{{ item.product.name }}</h3>
                    <p class="text-sm text-muted-foreground">
                      Color: {{ item.color.name }} | Size: {{ item.size.size }}
                    </p>
                  </div>
                  <button
                    class="text-muted-foreground hover:text-destructive p-2 rounded-md"
                    @click="handleRemoveItem(item.id)"
                  >
                    <X class="h-4 w-4" />
                  </button>
                </div>

                <div class="flex justify-between items-center">
                  <!-- Quantity controls -->
                  <div class="flex items-center space-x-2">
                    <button
                      class="p-2 border border-border rounded-md hover:bg-accent hover:text-accent-foreground"
                      @click="handleQuantityChange(item.id, item.quantity - 1)"
                      :disabled="item.quantity <= 1"
                    >
                      <Minus class="h-3 w-3" />
                    </button>
                    <span class="w-8 text-center">{{ item.quantity }}</span>
                    <button
                      class="p-2 border border-border rounded-md hover:bg-accent hover:text-accent-foreground"
                      @click="handleQuantityChange(item.id, item.quantity + 1)"
                      :disabled="item.quantity >= item.size.stock"
                    >
                      <Plus class="h-3 w-3" />
                    </button>
                  </div>

                  <!-- Price -->
                  <div class="text-right">
                    <p class="font-semibold">
                      ${{ (item.product.discountedPrice || item.product.originalPrice) * item.quantity }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                      ${{ item.product.discountedPrice || item.product.originalPrice }} each
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Clear cart button -->
          <div class="flex justify-end">
            <button
              class="text-destructive hover:text-destructive px-4 py-2 border border-border rounded-md"
              @click="store.clearCart"
            >
              Clear Cart
            </button>
          </div>
        </div>

        <!-- Order summary -->
        <div class="lg:col-span-1">
          <div class="bg-card rounded-lg p-6 shadow-sm border border-border sticky top-4">
            <h2 class="text-xl font-semibold mb-4">Order Summary</h2>

            <div class="space-y-3">
              <div class="flex justify-between">
                <span>Subtotal</span>
                <span>${{ subtotal.toFixed(2) }}</span>
              </div>

              <div class="flex justify-between">
                <span>Shipping</span>
                <span>{{ shipping === 0 ? 'FREE' : `$${shipping.toFixed(2)}` }}</span>
              </div>

              <div v-if="shipping > 0" class="text-sm text-muted-foreground">
                Add ${{ (100 - subtotal).toFixed(2) }} more for free shipping
              </div>

              <div class="flex justify-between">
                <span>Tax</span>
                <span>${{ tax.toFixed(2) }}</span>
              </div>

              <div class="border-t border-border pt-3">
                <div class="flex justify-between font-semibold text-lg">
                  <span>Total</span>
                  <span>${{ total.toFixed(2) }}</span>
                </div>
              </div>
            </div>

            <!-- Promo code -->
            <div class="mt-6">
              <div class="flex space-x-2">
                <input
                  type="text"
                  placeholder="Promo code"
                  v-model="promoCode"
                  class="flex-1 border border-input bg-background rounded-md px-3 py-2 text-sm ring-offset-background file:border-0 placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
                <button class="px-3 py-2 border border-border rounded-md hover:bg-accent hover:text-accent-foreground" @click="handleApplyPromo">
                  Apply
                </button>
              </div>
            </div>

            <!-- Checkout button -->
            <button class="w-full mt-6 px-4 py-3 bg-accent hover:bg-accent/90 text-accent-foreground rounded-md text-lg font-medium">
              Proceed to Checkout
              <ArrowRight class="h-4 w-4 ml-2 inline" />
            </button>

            <!-- Continue shopping -->
            <button 
              class="w-full mt-3 px-4 py-2 border border-border rounded-md hover:bg-accent hover:text-accent-foreground"
              @click="$router.push('/products')"
            >
              Continue Shopping
            </button>

            <!-- Security note -->
            <div class="mt-6 text-center">
              <p class="text-xs text-muted-foreground">
                🔒 Secure checkout powered by industry-standard encryption
              </p>
            </div>
          </div>
        </div>
      </div>
    </main>

    <Footer />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Minus, Plus, X, ShoppingBag, ArrowRight } from 'lucide-vue-next'
import Header from '@/components/Header.vue'
import Footer from '@/components/Footer.vue'
import { useStore } from '@/stores/store'

const store = useStore()
const promoCode = ref('')

const subtotal = computed(() => store.getCartTotal)
const shipping = computed(() => subtotal.value > 100 ? 0 : 10)
const tax = computed(() => subtotal.value * 0.05) // 5% tax
const total = computed(() => subtotal.value + shipping.value + tax.value)

const handleMenuToggle = () => {
  console.log('Menu toggle')
}

const handleQuantityChange = (itemId: string, quantity: number) => {
  store.updateQuantity(itemId, quantity)
}

const handleRemoveItem = (itemId: string) => {
  store.removeFromCart(itemId)
}

const handleApplyPromo = () => {
  // Placeholder for promo code logic
  console.log('Applying promo code:', promoCode.value)
}

onMounted(() => {
  // Apply RTL class to body when RTL is enabled
  if (store.isRTL) {
    document.body.classList.add('rtl')
    document.body.classList.remove('ltr')
  } else {
    document.body.classList.add('ltr')
    document.body.classList.remove('rtl')
  }
})
</script>