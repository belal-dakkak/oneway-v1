<template>
    <app-layout title="Create Color">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Order Type')}}
            </h2>
        </template>

        <div>
            <div class="max-w-full mx-auto py-10 sm:px-6 lg:px-8" dir="rtl">
                <div class="bg-white-800 text-rose-300 grid grid-cols-1 md:grid-cols-2">
                    <div class="p-16">
                        <inertia-link :href="route('orders.simple')" class="group block bg-white max-w-xl mx-auto rounded-lg p-16 ring-1 ring-slate-900/5 shadow-lg space-y-3 hover:bg-rose-500 hover:ring-rose-500">
                            <div class="flex items-center space-x-3">
                                <vue-feather :type="'align-right'" stroke-width="2" class="h-12 w-6 text-rose-500 group-hover:text-white p-1 place-self-center inline-block"></vue-feather>
                                <h3 class="text-rose-500 p-2 group-hover:text-white text-2xl font-semibold">طلب سريع</h3>
                            </div>
                            <p class="text-rose-400 group-hover:text-white text-lg">
                                {{ __('Fast Order')}}
                            </p>
                        </inertia-link>
                    </div>
                    <div class="p-16">
                        <inertia-link :href="route('orders.complex')" class="group block bg-white max-w-xl mx-auto rounded-lg p-16 ring-1 ring-slate-900/5 shadow-lg space-y-3 hover:bg-teal-500 hover:ring-teal-500">
                            <div class="flex items-center space-x-3">
                                <vue-feather :type="'align-right'" stroke-width="2" class="h-12 w-6 text-teal-500 group-hover:text-white p-1 place-self-center inline-block"></vue-feather>
                                <h3 class="text-teal-500 p-2 group-hover:text-white text-2xl font-semibold">طلب زبون</h3>
                            </div>
                            <p class="text-teal-400 group-hover:text-white text-lg">
                                {{ __('Detailed Client Order')}}
                            </p>
                        </inertia-link>
                    </div>
                </div>
                <jet-section-border />
            </div>
        </div>
    </app-layout>
</template>

<script>
import { defineComponent } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import JetSectionBorder from '@/Jetstream/SectionBorder.vue'

export default defineComponent({
    components: {
        AppLayout,
        JetSectionBorder
    },
    props: {
        users: Object,
        products: Object
    }
})
</script>
