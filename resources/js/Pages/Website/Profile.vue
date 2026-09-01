<template>
  <div class="min-h-screen bg-gray-50" :class="{ 'rtl': store.isRTL }">
    <Header
      :title="'One Way Profile'"
      :categories="categories"
      :phone="phone"
      :email="email"
      :facebook="facebook"
      :instagram="instagram"
      :tiktok="tiktok"
    />

    <main class="py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Hero Section -->
        <div class="relative bg-white rounded-3xl shadow-xl overflow-hidden mb-8 border border-gray-100">
          <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-r from-[#c20000] to-[#750000]"></div>
          <div class="relative pt-16 pb-8 px-8 flex flex-col md:flex-row items-center md:items-end gap-6">
            <div class="w-32 h-32 rounded-2xl bg-white p-1 shadow-lg -mt-4">
               <img
                :src="user.profile_photo_url"
                :alt="user.name"
                class="w-full h-full object-cover rounded-xl"
              />
            </div>
            <div class="flex-1 text-center md:text-left rtl:md:text-right">
              <h1 class="bg-white/90 p-2 pl-4 rounded-lg text-3xl font-bold text-gray-900">{{ user.name }}</h1>
              <p class="text-gray-500 pl-4">{{ user.email }}</p>
            </div>
            <div class="flex gap-4">
              <div class="bg-gray-50 p-4 rounded-2xl text-center min-w-[100px] border border-gray-100">
                <div class="text-2xl font-bold text-[#c20000]">{{ orders.length }}</div>
                <div class="text-xs text-gray-500 uppercase font-semibold">{{ store.t('orders') || 'Orders' }}</div>
              </div>
              <div class="bg-gray-50 p-4 rounded-2xl text-center min-w-[100px] border border-gray-100">
                <div class="text-2xl font-bold text-[#c20000]">{{ favorites.length }}</div>
                <div class="text-xs text-gray-500 uppercase font-semibold">{{ store.t('favorites') || 'Favorites' }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex flex-wrap gap-2 mb-8 bg-white p-2 rounded-2xl shadow-sm border border-gray-100 sticky top-20 z-20">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            class="flex-1 py-3 px-6 rounded-xl font-bold transition-all duration-300 flex items-center justify-center gap-2"
            :class="activeTab === tab.id ? 'bg-[#c20000] text-white shadow-lg shadow-red-200 hover:text-white' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700'"
          >
            <span class="text-xl">{{ tab.icon }}</span>
            {{ store.t(tab.label) || tab.label }}
          </button>
        </div>

        <!-- Tab Content -->
        <div class="min-h-[400px]">
          <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-4"
            mode="out-in"
          >
            <!-- Information Tab -->
            <div v-if="activeTab === 'info'" key="info" class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100">
              <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">{{ store.t('personalInformation') || 'Personal Information' }}</h2>
                <span class="px-4 py-1 bg-green-100 text-green-700 rounded-full text-sm font-bold">Active</span>
              </div>

              <form @submit.prevent="updateProfile" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                  <label class="text-sm font-bold text-gray-600 block">{{ store.t('fullName') || 'Full Name' }}</label>
                  <input
                    v-model="form.name"
                    type="text"
                    class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#c20000] focus:bg-white transition-all outline-none"
                    :placeholder="store.t('fullName')"
                  />
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-bold text-gray-600 block">{{ store.t('emailAddress') || 'Email Address' }}</label>
                  <input
                    v-model="form.email"
                    type="email"
                    class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#c20000] focus:bg-white transition-all outline-none"
                    :placeholder="store.t('emailAddress')"
                  />
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-bold text-gray-600 block">{{ store.t('phoneNumber') || 'Phone Number' }}</label>
                  <input
                    v-model="form.phone"
                    type="text"
                    class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#c20000] focus:bg-white transition-all outline-none"
                    :placeholder="store.t('phoneNumber')"
                  />
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-bold text-gray-600 block">{{ store.t('address') || 'Address' }}</label>
                  <input
                    v-model="form.address"
                    type="text"
                    class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#c20000] focus:bg-white transition-all outline-none"
                    :placeholder="store.t('address')"
                  />
                </div>

                <div class="md:col-span-2 pt-4">
                  <button
                    type="submit"
                    class="w-full md:w-auto bg-[#c20000] hover:bg-[#750000] text-white font-bold py-4 px-12 rounded-xl transition-all shadow-lg hover:shadow-xl disabled:opacity-50"
                    :disabled="form.processing"
                  >
                    {{ form.processing ? (store.t('saving') || 'Saving...') : (store.t('saveChanges') || 'Save Changes') }}
                  </button>
                </div>
              </form>
            </div>

            <!-- Orders Tab -->
            <div v-else-if="activeTab === 'orders'" key="orders" class="space-y-4">
              <div v-if="orders.length === 0" class="bg-white rounded-3xl shadow-xl p-12 text-center border border-gray-100">
                <div class="text-6xl mb-4">📦</div>
                <h3 class="text-xl font-bold mb-2">{{ store.t('noOrdersYet') || 'No orders yet' }}</h3>
                <p class="text-gray-500 mb-6">{{ store.t('startShoppingToSeeOrders') || 'Start shopping to see your orders here' }}</p>
                <a :href="route('shop')" class="inline-block bg-[#c20000] text-white font-bold py-3 px-8 rounded-xl ring-2 ring-red-100 hover:bg-[#750000] transition-all">
                  {{ store.t('shopNow') || 'Shop Now' }}
                </a>
              </div>

              <div v-for="order in orders" :key="order.id" class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100 hover:shadow-2xl transition-shadow duration-300">
                <div class="p-6 bg-gray-50 flex flex-wrap justify-between items-center gap-4 border-b border-gray-100">
                  <div class="flex items-center gap-4">
                    <div class="bg-white p-3 rounded-2xl shadow-sm">
                      <span class="text-2xl">🛍️</span>
                    </div>
                    <div>
                      <div class="text-sm text-gray-500 font-bold uppercase">{{ store.t('orderNumber') || 'Order' }} #{{ order.barcode }}</div>
                      <div class="font-bold">{{ order.date }}</div>
                    </div>
                  </div>
                  <div class="flex items-center gap-4">
                    <div class="text-right">
                      <div class="text-sm text-gray-500 font-bold uppercase">{{ store.t('total') || 'Total' }}</div>
                      <div class="text-xl font-bold text-[#c20000]">{{ store.formatPrice(order.total_price) }}</div>
                    </div>
                    <span
                      class="px-4 py-2 rounded-xl text-sm font-bold shadow-sm"
                      :class="getStatusClass(order.status)"
                    >
                      {{ getStatusLabel(order.status) }}
                    </span>
                  </div>
                </div>

                <div class="p-6">
                  <div class="space-y-4">
                    <div v-for="item in order.items" :key="item.id" class="flex items-center gap-4">
                      <div class="w-16 h-16 bg-gray-50 rounded-xl overflow-hidden flex-shrink-0 border border-gray-100">
                        <img
                          :src="item.product_color?.photo_url || '/api/placeholder/100/100'"
                          class="w-full h-full object-cover"
                        />
                      </div>
                      <div class="flex-1">
                        <h4 class="font-bold text-gray-900">{{ item.product_color?.product?.name || 'Product' }}</h4>
                        <div class="text-sm text-gray-500">
                          {{ store.t('size') || 'Size' }}: {{ item.size }} | {{ store.t('qty') || 'Qty' }}: {{ item.qty }}
                        </div>
                      </div>
                      <div class="font-bold">
                        {{ store.formatPrice(item.total_price) }}
                      </div>
                    </div>
                  </div>

                  <div class="mt-6 pt-6 border-t border-gray-100 flex justify-end gap-4">
                    <button class="px-6 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-xl font-bold transition-all">
                      {{ store.t('viewDetails') || 'View Details' }}
                    </button>
                    <a :href="'/invoice/' + order.id" target="_blank" class="px-6 py-2 bg-white border-2 border-gray-200 hover:border-[#c20000] hover:text-[#c20000] text-gray-700 rounded-xl font-bold transition-all">
                      {{ store.t('invoice') || 'Invoice' }}
                    </a>
                  </div>
                </div>
              </div>
            </div>

            <!-- Favorites Tab -->
            <div v-else-if="activeTab === 'favorites'" key="favorites">
              <div v-if="favorites.length === 0" class="bg-white rounded-3xl shadow-xl p-12 text-center border border-gray-100">
                <div class="text-6xl mb-4">❤️</div>
                <h3 class="text-xl font-bold mb-2">{{ store.t('noFavoritesYet') || 'No favorites yet' }}</h3>
                <p class="text-gray-500 mb-6">{{ store.t('startExploringToSaveFavorites') || 'Start exploring products and save your favorites here' }}</p>
                <a :href="route('shop')" class="inline-block bg-[#c20000] text-white font-bold py-3 px-8 rounded-xl shadow-lg hover:bg-[#750000] transition-all">
                  {{ store.t('exploreProducts') || 'Explore Products' }}
                </a>
              </div>

              <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <ProductCard
                  v-for="product in favorites"
                  :key="product.id"
                  :product="product"
                />
              </div>
            </div>

            <!-- Security Tab -->
            <div v-else-if="activeTab === 'security'" key="security" class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100">
              <div class="mb-6">
                <h2 class="text-2xl font-bold">{{ store.t('security') || 'Security' }}</h2>
                <p class="text-gray-500">{{ store.t('updatePasswordMessage') || 'Ensure your account is using a long, random password to stay secure.' }}</p>
              </div>

              <form @submit.prevent="updatePassword" class="max-w-2xl space-y-6">
                <div class="space-y-2">
                  <label class="text-sm font-bold text-gray-600 block">{{ store.t('currentPassword') || 'Current Password' }}</label>
                  <input
                    v-model="passwordForm.current_password"
                    type="password"
                    class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#c20000] focus:bg-white transition-all outline-none"
                    autocomplete="current-password"
                  />
                  <div v-if="passwordForm.errors.current_password" class="text-red-500 text-sm">{{ passwordForm.errors.current_password }}</div>
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-bold text-gray-600 block">{{ store.t('newPassword') || 'New Password' }}</label>
                  <input
                    v-model="passwordForm.password"
                    type="password"
                    class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#c20000] focus:bg-white transition-all outline-none"
                    autocomplete="new-password"
                  />
                  <div v-if="passwordForm.errors.password" class="text-red-500 text-sm">{{ passwordForm.errors.password }}</div>
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-bold text-gray-600 block">{{ store.t('confirmPassword') || 'Confirm Password' }}</label>
                  <input
                    v-model="passwordForm.password_confirmation"
                    type="password"
                    class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#c20000] focus:bg-white transition-all outline-none"
                    autocomplete="new-password"
                  />
                  <div v-if="passwordForm.errors.password_confirmation" class="text-red-500 text-sm">{{ passwordForm.errors.password_confirmation }}</div>
                </div>

                <div class="pt-4">
                  <button
                    type="submit"
                    class="w-full md:w-auto bg-[#c20000] hover:bg-[#750000] text-white font-bold py-4 px-12 rounded-xl transition-all shadow-lg hover:shadow-xl disabled:opacity-50"
                    :disabled="passwordForm.processing"
                  >
                    {{ passwordForm.processing ? (store.t('saving') || 'Saving...') : (store.t('updatePassword') || 'Update Password') }}
                  </button>
                </div>
              </form>
            </div>
          </transition>
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
  </div>
</template>

<script>
import Header from '@/Components/Website/Header.vue'
import Sitemap from '@/Components/Website/Sitemap.vue'
import Footer from '@/Components/Website/Footer.vue'
import ProductCard from '@/Components/Website/ProductCard.vue'
import { useStore } from '@/stores/store'
import { useForm } from '@inertiajs/inertia-vue3'
import { ref } from 'vue'

export default {
  components: {
    Header,
    Sitemap,
    Footer,
    ProductCard
  },
  props: {
    user: Object,
    orders: Array,
    favorites: Array,
    categories: Array,
    phone: String,
    email: String,
    facebook: String,
    instagram: String,
    tiktok: String,
    address: String,
  },
  setup(props) {
    const store = useStore()
    const activeTab = ref('info')

    const tabs = [
      { id: 'info', label: 'information', icon: '👤' },
      { id: 'orders', label: 'myOrders', icon: '📦' },
      { id: 'favorites', label: 'myFavourites', icon: '❤️' },
      { id: 'security', label: 'security', icon: '🔐' },
    ]

    const form = useForm({
      name: props.user.name,
      email: props.user.email,
      phone: props.user.phone,
      address: props.user.address,
    })

    const passwordForm = useForm({
      current_password: '',
      password: '',
      password_confirmation: '',
    })

    const updateProfile = () => {
      form.post(route('website.profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
          if (window.Swal) {
            window.Swal.fire({
              icon: 'success',
              title: store.t('success') || 'Success',
              text: store.t('profileUpdated') || 'Profile updated successfully',
              toast: true,
              position: 'top-end',
              timer: 3000,
              showConfirmButton: false
            })
          }
        }
      })
    }

    const updatePassword = () => {
      passwordForm.put(route('user-password.update'), {
        errorBag: 'updatePassword',
        preserveScroll: true,
        onSuccess: () => {
          passwordForm.reset()
          if (window.Swal) {
            window.Swal.fire({
              icon: 'success',
              title: store.t('success') || 'Success',
              text: store.t('passwordUpdated') || 'Password updated successfully',
              toast: true,
              position: 'top-end',
              timer: 3000,
              showConfirmButton: false
            })
          }
        }
      })
    }

    const getStatusLabel = (status) => {
      switch (status) {
        case 1: return store.t('pending') || 'Pending';
        case 2: return store.t('ongoing') || 'Ongoing';
        case 3: return store.t('delivered') || 'Delivered';
        default: return 'Unknown';
      }
    }

    const getStatusClass = (status) => {
      switch (status) {
        case 1: return 'bg-yellow-100 text-yellow-700';
        case 2: return 'bg-blue-100 text-blue-700';
        case 3: return 'bg-green-100 text-green-700';
        default: return 'bg-gray-100 text-gray-700';
      }
    }

    return {
      store,
      activeTab,
      tabs,
      form,
      passwordForm,
      updateProfile,
      updatePassword,
      getStatusLabel,
      getStatusClass
    }
  }
}
</script>

<style scoped>
.rtl {
  direction: rtl;
}
</style>
