<template>
    <span class="inline-flex items-center justify-center overflow-hidden rounded-sm border border-gray-200" :class="$attrs.class">
        <!-- UK/EN -->
        <svg v-if="name === 'en'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 30" class="w-full h-full">
            <clipPath id="s">
                <path d="M0,0 v30 h60 v-30 z"/>
            </clipPath>
            <path d="M0,0 v30 h60 v-30 z" fill="#012169"/>
            <path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/>
            <path d="M0,0 L60,30 M60,0 L0,30" clip-path="url(#s)" stroke="#C8102E" stroke-width="4"/>
            <path d="M30,0 v30 M0,15 h60" stroke="#fff" stroke-width="10"/>
            <path d="M30,0 v30 M0,15 h60" stroke="#C8102E" stroke-width="6"/>
        </svg>
        <!-- UAE -->
        <svg v-else-if="name === 'ar' || name == 2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 300" class="w-full h-full">
            <rect width="600" height="100" fill="#00732f" />
            <rect width="600" height="100" y="100" fill="#fff" />
            <rect width="600" height="100" y="200" fill="#000" />
            <rect width="150" height="300" fill="red" />
        </svg>
        <!-- Lebanon -->
        <svg v-else-if="name == 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 3 2" class="w-full h-full">
            <rect width="3" height=".5" fill="#ed1c24"/>
            <rect width="3" height="1" y=".5" fill="#fff"/>
            <rect width="3" height=".5" y="1.5" fill="#ed1c24"/>
            <path fill="#00a651" d="M1.5.6l.2.5h-.1v.3h-.2v-.3h-.1z"/>
        </svg>
    </span>
</template>
<script>
export default {
    props: {
        name: String,
    },
}
</script>
