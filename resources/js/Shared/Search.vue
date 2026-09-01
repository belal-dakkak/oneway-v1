<template>
    <div class="relative w-full lg:max-w-md">
        <vue-feather :type="'search'" size="20" stroke-width="2" class="absolute top-3 ml-3 pointer-events-none"></vue-feather>
        <input type="text"
               name="search"
               @keyup.esc="reset()"
               @blur="reset()"
               v-model="term"
               id="search"
               :placeholder="title"
               autocomplete="off" aria-label="'{{title}}'"
               class="w-full h-12 placeholder-gray-400 search-box px-12 py-3 font-semibold text-black  rounded-2xl shadow-xl focus:ring-gray-100 focus:ring-1"
        >
        <button v-if="term" @click="reset()" class="absolute top-0 right-0 p-3 text-orange-500 hover:text-orange-600 focus:outline-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        <ul v-if="term" class="absolute right-0 left-0 z-10 mb-4 w-full flex flex-col rounded-b-lg border border-t-0 border-b-0 border-gray-200 shadow-lg bg-white">
            <li v-for="(result, index) in results" :key="index">
                <inertia-link @mousedown.prevent :href="result.item.link" class="p-4 text-sm font-medium border-b border-gray-200 cursor-pointer hover:bg-emerald-50 block">
                    {{ result.item.title }}
                    <span class="block mt-1 text-xs font-normal tracking-wide text-gray-600 uppercase">{{ result.item.name }}</span>
                </inertia-link>
            </li>

            <li v-if="!results.length" class="p-4 w-full rounded-b-lg shadow my-0 text-sm">
                No lessons for <strong>{{ term }}.</strong>
            </li>
        </ul>
    </div>
</template>

<script>
import Fuse from 'fuse.js';

export default {
    data() {
        return {
            term: null,
            fuse: null,
        }
    },
    props: {
        title: String
    },
    computed: {
        results() {
            return this.term ? this.fuse.search(this.term).slice(0, 8) : [];
        }
    },
    created() {
        this.fuse = new Fuse(this.$page.props.searchItems, {
            keys: ['title', 'name']
        })
    },
    methods: {
        reset() {
            this.term = null;
        }
    }
};
</script>
