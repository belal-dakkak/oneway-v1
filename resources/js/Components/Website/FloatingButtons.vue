<template>
  <div>
    <!-- Scroll to Top Button (bottom right) -->
    <transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 translate-y-4" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-300" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-4">
      <button
        v-if="showScrollTop"
        @click="scrollToTop"
        class="fixed bottom-6 right-6 z-50 w-12 h-12 bg-primary text-white rounded-full shadow-lg hover:bg-primary/90 hover:-translate-y-1 transition-all flex items-center justify-center"
        :title="isRTL ? 'العودة للأعلى' : 'Back to top'"
        aria-label="Scroll to top"
      >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
        </svg>
      </button>
    </transition>

    <!-- WhatsApp Button (bottom left) -->
    <a
      v-if="whatsappNumber"
      :href="'https://wa.me/971564533655'"
      target="_blank"
      rel="noopener noreferrer"
      class="fixed bottom-6 left-6 z-50 w-14 h-14 rounded-full shadow-lg hover:-translate-y-1 transition-all flex items-center justify-center"
      :title="isRTL ? 'تواصل عبر واتساب' : 'Chat on WhatsApp'"
      aria-label="WhatsApp"
    >
      <!-- WhatsApp SVG icon (official green) -->
      <svg viewBox="0 0 48 48" class="w-14 h-14" xmlns="http://www.w3.org/2000/svg">
        <circle cx="24" cy="24" r="24" fill="#25D366"/>
        <path fill="#fff" d="M34.5 13.5A14.4 14.4 0 0 0 24 9C16.27 9 10 15.27 10 23a13.9 13.9 0 0 0 1.87 7L10 39l9.3-1.84A14 14 0 0 0 24 38.4c7.73 0 14-6.27 14-14a13.9 13.9 0 0 0-3.5-10.9zm-10.5 21.5a11.6 11.6 0 0 1-5.93-1.62l-.42-.25-4.38.87.9-4.27-.28-.44A11.6 11.6 0 0 1 12.4 23c0-6.4 5.2-11.6 11.6-11.6A11.6 11.6 0 0 1 35.6 23c0 6.4-5.2 11.6-11.6 11.6zm6.36-8.68c-.35-.17-2.06-1.02-2.38-1.13-.32-.12-.55-.17-.78.17-.23.35-.9 1.13-1.1 1.37-.2.23-.4.26-.75.09-.35-.17-1.48-.55-2.82-1.74-1.04-.93-1.74-2.08-1.95-2.43-.2-.35-.02-.54.15-.71.16-.16.35-.4.52-.6.17-.2.23-.35.35-.58.12-.23.06-.43-.03-.6-.09-.17-.78-1.88-1.07-2.57-.28-.67-.57-.58-.78-.59h-.67c-.23 0-.6.09-.92.43-.32.35-1.2 1.17-1.2 2.86s1.23 3.32 1.4 3.55c.17.23 2.42 3.7 5.87 5.19.82.35 1.46.56 1.96.72.82.26 1.57.22 2.16.13.66-.1 2.06-.84 2.35-1.66.29-.81.29-1.51.2-1.66-.08-.14-.3-.23-.65-.4z"/>
      </svg>
    </a>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'

export default {
  props: {
    whatsappNumber: {
      type: String,
      default: ''
    },
    isRTL: {
      type: Boolean,
      default: false
    }
  },
  setup(props) {
    const showScrollTop = ref(false)

    const cleanNumber = computed(() =>
      (props.whatsappNumber || '').replace(/\D/g, '')
    )

    const handleScroll = () => {
      showScrollTop.value = window.scrollY > 100
    }

    const scrollToTop = () => {
      window.scrollTo({ top: 0, behavior: 'smooth' })
    }

    onMounted(() => window.addEventListener('scroll', handleScroll))
    onUnmounted(() => window.removeEventListener('scroll', handleScroll))

    return { showScrollTop, cleanNumber, scrollToTop }
  }
}
</script>
