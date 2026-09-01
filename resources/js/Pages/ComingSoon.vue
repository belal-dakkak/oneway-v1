<template>
  <div class="min-h-screen bg-background flex flex-col">
    <Header
      :categories="categories"
      :phone="phone"
      :email="email"
      :facebook="facebook"
      :instagram="instagram"
      :tiktok="tiktok"
      :whatsapp="whatsapp"
      :address="address"
    />

    <main class="flex-grow flex items-center justify-center py-20 px-4">
      <div class="max-w-2xl w-full text-center space-y-8 animate-in fade-in slide-in-from-bottom-8 duration-700">
        <!-- Icon/Illustration -->
        <div class="relative inline-block">
          <div class="absolute inset-0 bg-primary/20 blur-3xl rounded-full"></div>
          <div class="relative bg-card border border-border p-8 rounded-full shadow-2xl inline-flex items-center justify-center">
            <svg class="h-16 w-16 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>

        <div class="space-y-4">
          <h1 class="text-4xl md:text-6xl font-black tracking-tighter">
            {{ store.isRTL ? 'قريباً جداً' : 'Coming Soon' }}
          </h1>
          <p class="text-xl text-muted-foreground font-medium max-w-lg mx-auto leading-relaxed">
            {{ store.isRTL
              ? 'نحن نعمل بجهد لإطلاق خدماتنا في هذا السوق. ترقبوا انطلاقتنا قريباً!'
              : "We're working hard to launch our services in this market. Stay tuned for our launch very soon!"
            }}
          </p>
        </div>

        <div class="pt-8">
          <Link
            :href="route('homepage')"
            class="inline-flex items-center justify-center h-14 px-8 rounded-full bg-primary text-primary-foreground font-bold text-lg hover:scale-105 active:scale-95 transition-all shadow-xl shadow-primary/25 group"
          >
            <svg
              class="h-5 w-5 mr-2 rtl:hidden group-hover:-translate-x-1 transition-transform"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <svg
              class="h-5 w-5 ml-2 ltr:hidden group-hover:translate-x-1 transition-transform"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
            {{ store.isRTL ? 'العودة للرئيسية' : 'Back to Home' }}
          </Link>
        </div>

        <!-- Decorative Progress Bar -->
        <div class="max-w-xs mx-auto pt-12">
            <div class="h-1.5 w-full bg-muted rounded-full overflow-hidden">
                <div class="h-full bg-primary w-2/3 animate-pulse"></div>
            </div>
            <p class="text-xs text-muted-foreground mt-3 font-semibold tracking-widest uppercase">
                {{ store.isRTL ? 'تحت الإنشاء' : 'Under Construction' }}
            </p>
        </div>
      </div>
    </main>

    <Footer
      :phone="phone"
      :email="email"
      :facebook="facebook"
      :instagram="instagram"
      :tiktok="tiktok"
      :whatsapp="whatsapp"
      :address="address"
    />
  </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/inertia-vue3'
import Header from '@/Components/Website/Header.vue'
import Footer from '@/Components/Website/Footer.vue'
import { useStore } from '@/stores/store'

const store = useStore()

defineProps({
  categories: Array,
  phone: String,
  email: String,
  facebook: String,
  instagram: String,
  tiktok: String,
  whatsapp: String,
  address: String
})
</script>
