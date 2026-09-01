<template>
    <div v-show="links.length > 3">
        <div class="flex flex-wrap -mb-1">
            <template v-for="(link, key) in links" :key="key">
                <div v-if="link.url === null" :key="`${key}-disabled`" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded" v-html="link.label"/>
                <inertia-link v-else :key="key" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white focus:border-indigo-500 focus:text-indigo-500" :class="{ 'bg-white': link.active }" :href="getUrlWithParams(link.url)">
                    <span v-html="link.label"></span>
                </inertia-link>
            </template>
        </div>
    </div>
</template>

<script>
export default {
    name: 'Pagination',
    props: {
        links: Array,
        params: String
    },
    methods: {
        getUrlWithParams(url){
            if (url.includes('?')){
                return url+'&'+this.params;
            }else{
                return url+'?'+this.params;
            }
        }
    }
}
</script>
