<template>
    <nav v-if="links.length > 3" class="flex justify-center" aria-label="Pagination">
        <div class="flex flex-wrap justify-center -mb-1">
            <template v-for="(link, key) in links" :key="key">
                <span
                    v-if="link.url === null"
                    class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded cursor-not-allowed"
                    v-html="link.label"
                />
                <inertia-link
                    v-else
                    class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white focus:border-indigo-500 focus:text-indigo-500"
                    :class="{ 'bg-white font-bold text-indigo-600 border-indigo-500': link.active }"
                    :href="link.url"
                >
                    <span v-html="link.label"></span>
                </inertia-link>
            </template>
        </div>
    </nav>
</template>

<script>
export default {
    name: 'Pagination',
    props: {
        links: {
            type: Array,
            default: () => [],
        }
    }
}
</script>
