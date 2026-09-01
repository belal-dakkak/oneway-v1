<template>
    <div dir="rtl">

       <div class="flex justify-between">
           <div class="flex justify-between block bg-white text-teal-500 hover:text-white hover:bg-teal-400 max-w-xs mx-auto rounded-lg p-6 m-8 ring-1 ring-teal-400">
               <div>
                   <div class="flex items-center space-x-3">
                       <h3 class="p-2 text-2xl font-bold">الصندوق</h3>
                   </div>
                   <p class="p-2 text-xl font-bold">
                       {{ user.credit }}
                   </p>
               </div>
               <div>
                   <vue-feather :type="'credit-card'" stroke-width="2" class="h-12 w-24 p-1 place-self-center inline-block"></vue-feather>
               </div>
           </div>
           <div class="flex justify-between block bg-white text-rose-500 hover:text-white hover:bg-rose-400 max-w-xs mx-auto rounded-lg p-6 m-8 ring-1 ring-rose-400">
               <div>
                   <div class="flex items-center space-x-3">
                       <h3 class="p-2 text-2xl font-bold">الديون المستحقة</h3>
                   </div>
                   <p class="p-2 text-xl font-bold">
                       {{ user.debit }}
                   </p>
               </div>
               <div>
                   <vue-feather :type="'credit-card'" stroke-width="2" class="h-12 w-24 p-1 place-self-center inline-block"></vue-feather>
               </div>
           </div>
       </div>
        <div class="bg-white-800 text-yellow-300 grid grid-cols-1 md:grid-cols-4">
            <div class="p-6" v-for="category in categories">
                <a href="#" class="group block bg-black max-w-xs mx-auto rounded-lg p-6 ring-1 ring-slate-900/5 shadow-lg space-y-3 hover:bg-yellow-500 hover:ring-yellow-500">
                    <div class="flex items-center space-x-3">
                        <vue-feather :type="'align-right'" stroke-width="2" class="h-12 w-6 text-yellow-500 group-hover:text-black p-1 place-self-center inline-block"></vue-feather>
                        <h3 class="text-yellow-500 p-2 group-hover:text-black text-sm font-semibold">{{ category.name }}</h3>
                    </div>
                    <p class="text-yellow-300 group-hover:text-black text-sm">
                        عدد الموديلات: {{ category.products_count }}
                    </p>
                    <p class="text-yellow-300 group-hover:text-black text-sm">
                        عدد الألوان: {{ category.colors_count }}
                    </p>
                </a>
            </div>
        </div>
    </div>
</template>
<script>
import {computed} from "vue";
import {usePage} from "@inertiajs/inertia-vue3";

const components = {}

export default {
    name: 'DashboardWelcome',

    components,

    props: {
        categories: Object,
    },
    setup() {
        const user = computed(() => usePage().props.value.auth.user)
        return { user }
    },
}
</script>
