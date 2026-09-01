<template>
  <div class="min-h-screen" :class="{ 'rtl': store.isRTL }">
    <Header
      :title="title"
      :facebook="facebook"
      :instagram="instagram"
      :tiktok="tiktok"
      @menu-toggle="handleMenuToggle"
    />

    <main>
      <!-- About Section -->
      <section id="about" class="bg-white text-black h-screen flex flex-col justify-center items-center relative text-center px-4">
        <div class="max-w-4xl space-y-6">
          <h2 class="text-3xl md:text-5xl font-extrabold uppercase tracking-tight">
            {{ store.t('aboutTitle') }}
          </h2>
          <p class="text-lg md:text-2xl leading-tight font-bold">
            {{ store.t('aboutDesc1') }}
          </p>
          <div id="line-1" class="h-0.5 mt-10 w-4/5 my-6 mx-auto bg-gray-200"></div>
          <p class="text-xl md:text-2xl leading-relaxed font-bold">
            {{ store.t('aboutDesc2') }}
          </p>
        </div>
      </section>

      <!-- Foundation Section -->
      <section id="foundation" class="bg-white text-black h-screen flex flex-col md:flex-row justify-center md:justify-between items-center px-4 md:px-20 gap-12">
        <div class="flex flex-col text-center md:text-left space-y-6 max-w-2xl">
          <h2 class="text-3xl md:text-5xl font-extrabold text-center uppercase tracking-tight">
            {{ store.t('foundationTitle') }}
          </h2>
          <p class="text-xl md:text-2xl leading-relaxed text-center font-bold">
            {{ store.t('foundationDesc') }}
          </p>
        </div>
        <div class="hidden md:flex items-center justify-center flex-shrink-0">
          <img src="/assets/rose.svg" alt="foundation" class="w-64 h-64 md:w-96 md:h-96 opacity-80" />
        </div>
      </section>

        <section id="mission" class="bg-white text-black h-screen flex justify-center relative">
            <img :src="'/assets/sample-4.jpg'" class="rounded-2xl h-3/4 relative" alt="mission">
            <div class="absolute shadow-2xl bg-white left-0 md:left-52 space-y-6 bottom-44 max-w-7xl w-full md:max-w-xs p-6 rounded-xl" id="mission-area">
                <div class="text-sm rounded-3xl w-fit px-2 py-1">{{ store.t('locationUAELebanon') }}</div>
                <h2 class="text-3xl font-extrabold">{{ store.t('missionTitle') || store.t('mission') }}</h2>
                <p class="text-xl">
                    {{ store.t('missionDesc') }}
                </p>
            </div>
        </section>

      <!-- Vision Section -->
      <section id="vision" class="bg-white text-black h-screen flex justify-center items-center relative px-4">
        <div class="relative w-3/4 max-w-6xl h-[50vh] rounded-2xl overflow-hidden shadow-2xl">
          <img src="/assets/sample-1.webp" class="w-full h-full object-cover" alt="vision" />
          <div class="absolute inset-0 bg-white/20"></div>
          <div class="absolute ltr:right-4 ltr:md:right-12 rtl:left-4 rtl:md:left-12 bottom-8 md:bottom-12 space-y-4 max-w-md p-8 rounded-xl bg-white/90 backdrop-blur-md shadow-lg border border-gray-100 ltr:text-right rtl:text-left" id="vision-area">
            <div class="text-xs font-bold uppercase tracking-wider rounded-full bg-gray-100 text-gray-800 w-fit px-3 py-1 mb-2 ltr:ml-auto rtl:mr-auto">
              {{ store.t('locationMiddleEast') }}
            </div>
            <h2 class="text-3xl font-extrabold">{{ store.t('visionTitle') || store.t('vision') }}</h2>
            <p class="text-lg text-gray-700 leading-relaxed">
              {{ store.t('visionDesc') }}
            </p>
          </div>
        </div>
      </section>

      <!-- Objective Section -->
      <section id="objective" class="bg-white text-black h-screen flex justify-center items-center relative px-4">
        <div class="relative w-3/4 max-w-6xl h-[50vh] rounded-2xl overflow-hidden shadow-2xl">
          <img src="/assets/sample-2.webp" class="w-full h-full object-cover" alt="objective" />
          <div class="absolute inset-0 bg-white/20"></div>
          <div class="absolute ltr:left-4 ltr:md:left-12 rtl:right-4 rtl:md:right-12 bottom-8 md:bottom-12 space-y-4 max-w-md p-8 rounded-xl bg-white/90 backdrop-blur-md shadow-lg border border-gray-100" id="objective-area">
            <div class="text-xs font-bold uppercase tracking-wider rounded-full bg-gray-100 text-gray-800 w-fit px-3 py-1 mb-2">
              {{ store.t('locationGlobal') }}
            </div>
            <h2 class="text-3xl font-extrabold">{{ store.t('objectiveTitle') }}</h2>
            <p class="text-lg text-gray-700 leading-relaxed">
              {{ store.t('objectiveDesc') }}
            </p>
          </div>
        </div>
      </section>

      <!-- Story Section -->
      <section id="story" class="bg-white text-black h-screen flex flex-col lg:flex-row justify-center items-center px-4 lg:px-24 gap-12">
        <div class="w-full lg:w-1/2 h-[40vh] lg:h-[60vh] rounded-xl overflow-hidden shadow-xl hidden md:block">
          <img src="/assets/story.jpg" alt="story" class="w-full h-full object-cover" />
        </div>
        <div class="flex flex-col space-y-8 w-full lg:w-1/2 text-center ltr:lg:text-left rtl:lg:text-right">
          <h2 class="text-3xl md:text-6xl font-extrabold uppercase tracking-tight">
            {{ store.t('storesFactoriesTitle') }}
          </h2>
          <p class="text-xl md:text-3xl text-gray-600 leading-relaxed">
            {{ store.t('storesFactoriesDesc') }}
          </p>
        </div>
      </section>
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

<style>
#about{
    background-image: url("/custom/heroimage.svg");
    background-repeat: no-repeat;
    background-size: auto;
}

#mission {
    background-color: white !important;
}
</style>
<script>
import Header from '../Components/Website/Header.vue'
import Sitemap from '../Components/Website/Sitemap.vue'
import Footer from '../Components/Website/Footer.vue'
import FloatingButtons from '../Components/Website/FloatingButtons.vue'
import { useStore } from '@/stores/store'

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
    const handleMenuToggle = () => {

    }

    return {
      store,
      handleMenuToggle
    }
  }
}
</script>
