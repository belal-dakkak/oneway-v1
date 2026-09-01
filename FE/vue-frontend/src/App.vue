<template>
  <RouterView />
</template>

<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import { useStore } from '@/stores/store'

const store = useStore()

onMounted(() => {
  // Watch for RTL changes and update body class
  const updateBodyClass = () => {
    if (store.isRTL) {
      document.body.classList.add('rtl')
      document.body.classList.remove('ltr')
    } else {
      document.body.classList.add('ltr')
      document.body.classList.remove('rtl')
    }
  }

  // Initial update
  updateBodyClass()

  // Watch for changes
  const unwatch = store.$subscribe((mutation, state) => {
    if (mutation.type === 'direct' && mutation.events?.includes('isRTL')) {
      updateBodyClass()
    }
  })

  // Cleanup on unmount
  onUnmounted(() => {
    unwatch()
  })
})
</script>

<style>
@import '@/assets/main.css';

body {
  @apply bg-background text-foreground;
}
</style>