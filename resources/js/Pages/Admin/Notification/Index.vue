<template>
  <app-layout title="Products Management">
      <div class="flex justify-around mt-8">
          <div class="mt-8 flex justify-between space-x-4">
              <h3 class="text-2xl font-bold capitalize text-primary">الإشعارات</h3>
          </div>
      </div>
    <MeeTable :tableTitle="'All Products'">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr class="shadow-2xl py-4">
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    رقم الإشعار
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    صورة المنتج
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    الإشعار
                </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200 text-pcr">
            <tr v-for="item in userNotifications" :key="item.id" class="font-sans-latin text-sm font-medium" :class="item.read_at?'':'bg-gray-100'">
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <inertia-link :href="route('notification.show', {'notification': item.id})" class="text-sm font-medium">{{ item.id }}</inertia-link>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="flex-shrink-0 w-full">

                            <div v-if="item.data && item.data['table'] && item.data['table'].product_color">
                                <img class="w-24 rounded-lg" :src="item.data['table'].product_color.photo_url" alt="" />
                            </div>

                        </div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div dir="rtl" class="text-sm font-medium">{{ item.data['message'] }}</div>
                    </div>
                </td>
            </tr>
          </tbody>
        </table>

    </MeeTable>
  </app-layout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import { MeeTable } from '@/Shared/Ui'
import { Pagination } from '@/Shared/Common'
import {throttle} from "lodash";
import JetButton from '@/Jetstream/Button.vue'
import {computed} from "vue";
import {usePage} from "@inertiajs/inertia-vue3";

const components = { AppLayout, MeeTable, Pagination, JetButton }

export default {
    name: 'PortalProductsIndex',

    components,

    props: {
        userNotifications: Object,
        filters: Object
    },
    data() {
        return {
            params: {
                search: this.filters.search,
                field: this.filters.field,
                direction: this.direction
            }
        }
    },
    methods: {
        sort(field){
            this.params.field = field;
            this.params.direction = this.params.direction === 'asc' ? 'desc' : 'asc';
        }
    },
    watch: {
        params: {
            handler: throttle(function () {
                let params = this.params;
                Object.keys(params).forEach(key => {
                    if (params[key] == ''){
                        delete params[key]
                    }
                }, 150);
                this.$inertia.get(this.route('notification.index'), this.params, { replace: true, preserveState: true});
            }),
            deep: true
        }
    },
    setup() {
        const admin = computed(() => usePage().props.value.auth.user)
        return { admin }
    },
}
</script>
