<template>
    <button @click="shareProduct" class="share-button bg-white/80">
        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="18"
            height="18"
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
</template>

<script>
import { useStore } from '@/stores/store'

export default {
    setup() {
        const store = useStore()
        return { store }
    },
    props: {
        productName: {
            type: String,
            required: true
        },
        productUrl: {
            type: String,
            required: true
        }
    },

    methods: {
        async shareProduct(event) {
          event.stopPropagation()

            const fullUrl = this.productUrl.startsWith('http') 
              ? this.productUrl 
              : window.location.origin + this.productUrl;

            const shareData = {
                title: this.productName,
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
        }
    }
};
</script>

<style scoped>
.share-button {
    border-radius: 6px;
    border: none;
    color: black;
    cursor: pointer;
}
</style>
