<template>
  <span class="inline-flex items-center justify-center overflow-hidden rounded-sm border border-border/50 shadow-sm" :style="{ width: size + 'px', height: (size * 0.7) + 'px' }">
    <!-- UAE -->
    <svg v-if="flagCode === 'ae'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 300" class="w-full h-full">
      <rect width="600" height="100" fill="#00732f" />
      <rect width="600" height="100" y="100" fill="#fff" />
      <rect width="600" height="100" y="200" fill="#000" />
      <rect width="150" height="300" fill="red" />
    </svg>
    <!-- Lebanon -->
    <svg v-else-if="flagCode === 'lb'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 3 2" class="w-full h-full">
      <rect width="3" height=".5" fill="#ed1c24"/>
      <rect width="3" height="1" y=".5" fill="#fff"/>
      <rect width="3" height=".5" y="1.5" fill="#ed1c24"/>
      <path fill="#00a651" d="M1.5.6l.2.5h-.1v.3h-.2v-.3h-.1z"/>
    </svg>
    <!-- Syria (3-star version) -->
    <svg v-else-if="flagCode === 'sy'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 3 2" class="w-full h-full">
      <rect width="3" height=".667" fill="#007a33"/>
      <rect width="3" height=".667" y=".667" fill="#fff"/>
      <rect width="3" height=".667" y="1.333" fill="#000"/>
      <!-- Three red stars -->
      <path fill="#bc0000" d="M0.75 1l0.08-0.12 0.08 0.12-0.08-0.04zM1.5 1l0.08-0.12 0.08 0.12-0.08-0.04zM2.25 1l0.08-0.12 0.08 0.12-0.08-0.04z"/>
    </svg>
    <!-- Turkey -->
    <svg v-else-if="flagCode === 'tr'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 800" class="w-full h-full">
      <rect width="1200" height="800" fill="#e30a17"/>
      <circle cx="400" cy="400" r="200" fill="#fff"/>
      <circle cx="450" cy="400" r="160" fill="#e30a17"/>
      <path fill="#fff" d="M583 400l-147 48 56-154 56 154-147-48z"/>
    </svg>
    <div v-else class="w-full h-full bg-gray-200 flex items-center justify-center text-[8px]">{{ country }}</div>
  </span>
</template>

<script>
export default {
  props: {
    country: {
      type: String,
      required: true
    },
    size: {
      type: Number,
      default: 20
    }
  },
  computed: {
    flagCode() {
      const code = (this.country || '').toUpperCase();
      if (code === 'UAE' || code === 'AE') return 'ae';
      return code.toLowerCase();
    }
  }
}
</script>
