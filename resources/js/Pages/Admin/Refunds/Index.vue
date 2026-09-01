<template>
  <app-layout title="Products Management">
      <div class="flex justify-around mt-8">
          <div class="flex justify-between" v-if="admin.role !== 1">
              <inertia-link :href="route('refunds.create')">
                  <jet-button class="px-16 mt-8 bg-pcr float-right">
                      ترجيع بضاعة
                  </jet-button>
              </inertia-link>
          </div>
          <div v-else class="h-1/3 border-gray-300 shadow-sm border-2 rounded-md px-2">
              <jet-dropdown align="right" width="48">
                  <template #trigger>
                      <button type="button" class="inline-flex items-center px-1 py-2 border border-transparent  rounded-md  focus:outline-none transition">
                          <p v-if="params.shop">{{ getShopName(params.shop) }}</p>
                          <p v-else>بحث بحسب المحل</p>
                      </button>
                  </template>
                  <template #icon v-if="params.shop">
                      <vue-feather class="hover:cursor-pointer mt-2 text-pcr-mid" @click="filter('shop', 0)" :type="'x'"></vue-feather>
                  </template>
                  <template #content>
                      <button @click="filter('shop', 0)" class="w-full my-1 font-bold text-gray-600 hover:cursor-pointer text-center text-md bg-gray-50 rounded-md">كل المحلات</button>
                      <div class="border-t border-gray-100"></div>
                      <div v-for="shop in shops">
                          <p v-if="params.shop === shop.id" class="font-bold text-teal-500 hover:cursor-pointer text-center text-md bg-gray-100">{{ shop.name }}</p>
                          <button v-else @click="filter('shop', shop.id)" class="w-full my-1 font-bold text-gray-600 hover:cursor-pointer text-center text-md bg-gray-50 rounded-md">
                              {{ shop.name}}
                          </button>
                          <div class="border-t border-gray-100"></div>
                      </div>
                  </template>
              </jet-dropdown>
          </div>
          <div class="h-1/3 border-gray-300 shadow-sm border-2 rounded-md px-2" id="customers-list">
              <jet-dropdown align="right" width="48">
                  <template #trigger>
                      <button type="button" class="inline-flex items-center px-1 py-2 border border-transparent  rounded-md  focus:outline-none transition">
                          <span v-if="params.buyer">{{ getBuyerName(params.buyer) }}</span>
                          <span v-else>بحث بحسب الزبون</span>
                      </button>
                  </template>
                  <template #icon v-if="params.buyer">
                      <vue-feather class="hover:cursor-pointer mt-2 text-pcr-mid" @click="filter('buyer', 0)" :type="'x'"></vue-feather>
                  </template>
                  <template #content>
                      <button @click="filter('buyer', 0)" class="w-full my-1 font-bold text-gray-600 hover:cursor-pointer text-center text-md bg-gray-50 rounded-md">كل الزبائن</button>
                      <div class="border-t border-gray-100"></div>
                      <div v-for="buyer in buyers">
                          <p v-if="params.buyer === buyer.id" class="font-bold text-teal-500 hover:cursor-pointer text-center text-md bg-gray-100">{{ buyer.name }}</p>
                          <button v-else @click="filter('buyer', buyer.id)" class="w-full my-1 font-bold text-gray-600 hover:cursor-pointer text-center text-md bg-gray-50 rounded-md">
                              {{ buyer.name}}
                          </button>
                          <div class="border-t border-gray-100"></div>
                      </div>
                  </template>
              </jet-dropdown>
          </div>

          <div class="flex flex-col">
              <label dir="rtl" class="pr-2">تاريخ من</label>

              <div class="flex justify-center hover:cursor-pointer space-x-4 border border-gray-300 px-4 py-2 rounded-xl shadow-lg" v-bind:class="params.start_date?'pt-4':''">
                  <Datepicker v-model="start_date" @update:modelValue="handleStartDate" :clearable="true">
                      <template #trigger>
                          <p class="clickable-text" v-if="params.start_date">
                              {{ start_date?.value ?? params.start_date }}
                          </p>
                          <p v-else class="text-sm text-gray-500 font-light">اضغط لاختيار التاريخ</p>
                      </template>
                  </Datepicker>
                  <div v-if="params.start_date">
                      <vue-feather @click="resetStartDate" class="hover:cursor-pointer text-pcr-mid" :type="'x'"></vue-feather>
                  </div>
              </div>
          </div>
          <div class="flex flex-col">
              <label dir="rtl" class="pr-2">تاريخ إلى</label>

              <div class="flex justify-center hover:cursor-pointer space-x-4 border border-gray-300 px-4 py-2 rounded-xl shadow-lg" v-bind:class="params.start_date?'pt-4':''">
                  <Datepicker v-model="end_date" @update:modelValue="handleEndDate" :clearable="true">
                      <template #trigger>
                          <p class="clickable-text" v-if="params.end_date">
                              {{ end_date?.value ?? params.end_date }}
                          </p>
                          <p v-else class="text-sm text-gray-500 font-light">اضغط لاختيار التاريخ</p>
                      </template>
                  </Datepicker>
                  <div v-if="params.end_date">
                      <vue-feather @click="resetEndDate" class="hover:cursor-pointer text-pcr-mid" :type="'x'"></vue-feather>
                  </div>
              </div>
          </div>

          <div class="flex flex-col">
              <label dir="rtl" class="pr-2">تاريخ معين</label>

              <div class="flex justify-center hover:cursor-pointer space-x-4 border border-gray-300 px-4 py-2 rounded-xl shadow-lg" v-bind:class="params.start_date?'pt-4':''">
                  <Datepicker v-model="date" @update:modelValue="handleDate" :clearable="true">
                      <template #trigger>
                          <p class="clickable-text" v-if="params.date">
                              {{ date?.value ?? params.date }}
                          </p>
                          <p v-else class="text-sm text-gray-500 font-light">اضغط لاختيار التاريخ</p>
                      </template>
                  </Datepicker>
                  <div v-if="params.date">
                      <vue-feather @click="resetDate" class="hover:cursor-pointer text-pcr-mid" :type="'x'"></vue-feather>
                  </div>
              </div>
          </div>

          <div class="flex flex-col" v-if="admin.role === 1">
              <label dir="rtl" class="pr-2"> إجمالي المرتجعات</label>
              <span class="bg-emerald-500 px-2 rounded-md text-3xl text-white">{{ currencyExchange(totalRefunds, rate, true) }}</span>
          </div>

          <div class="flex justify-between block bg-white text-black-500 hover:text-white hover:bg-gray-800 rounded-lg p-6 ring-1 ring-black-400">
              <div>
                  <div class="flex items-center space-x-3">
                      <h3 class="p-2 text-2xl font-bold">الصندوق</h3>
                  </div>
                  <p class="p-2 text-4xl font-bold">
                      {{ admin.credit }}
                  </p>
              </div>
              <div>
                  <vue-feather :type="'credit-card'" stroke-width="2" class="h-12 w-24 p-1 place-self-center inline-block"></vue-feather>
              </div>
          </div>
          <div class="mt-8 flex justify-between space-x-4">
              <div class="max-w-xs">
                  <input dir="rtl"  type="search" v-model="params.search" placeholder="بحث..." class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
              </div>
              <h3 class="text-2xl font-bold capitalize text-primary">المرتجعات</h3>
          </div>
      </div>
    <MeeTable :tableTitle="'All Products'">
        <div v-if="userRefunds.data.length === 0" class="my-40 flex items-center justify-center text-xl font-bold text-error">
            <span class="text-center">لا يوجد مرتجعات!</span>
        </div>
        <table v-else class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr class="shadow-2xl py-4">
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    Barcode (الباركود)
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer" v-if="admin.role === 1">
                    اسم المحل
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    اسم المنتج
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    الزبون
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    سعر القطعة
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    العدد
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    السعر الكلي
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    تاريخ المرتجع
                </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200 text-pcr">
            <tr v-for="item in userRefunds.data" :key="item.id" class="font-sans-latin text-sm font-medium">
                <td class="flex justify-around mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <img class="w-16 rounded-lg" :src="item.item_image" alt="">
                    <div class="ml-4">
                        <div class="text-sm font-medium">باركود الطلبية: {{ item.order_barcode }}</div>
                        <div class="text-sm font-medium">{{ item.item_barcode }} :باركود المنتج</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7" v-if="admin.role === 1">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ item.shop_name }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ item.item_name }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ item.client_name }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ currencyExchange(item.item_price, rate) }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ item.qty }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ currencyExchange(item.total_price, rate) }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ item.date }}</div>
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
import {computed, ref} from "vue";
import {usePage} from "@inertiajs/inertia-vue3";
import JetDropdown from "@/Jetstream/Dropdown";
import JetDropdownLink from "@/Jetstream/DropdownLink";
import {debounce} from "lodash/function";
import Datepicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import Currency from '@/Utils/Currency';

const components = { AppLayout, MeeTable, Pagination, JetButton, JetDropdown, JetDropdownLink, Datepicker }

export default {
    name: 'PortalProductsIndex',

    components,

    props: {
        rate: Number,
        refunds: Object,
        filters: Object,
        shops: Array,
        buyers: Array,
        total: Number,
    },
    data() {
        return {
            params: {
                search: this.filters.search,
                field: this.filters.field,
                direction: this.direction,
                shop: this.filters.shop,
                buyer: this.filters.buyer,
                date: this.filters.date,
                start_date: this.filters.start_date,
                end_date: this.filters.end_date
            },
            userRefunds: this.refunds,
            totalRefunds: this.total,
            currencyExchange: Currency.getExchangeMethod(),
        }
    },
    methods: {
        sort(field) {
            this.params.field = field;
            this.params.direction = this.params.direction === 'asc' ? 'desc' : 'asc';
        },
        getShopName(id) {
            const shopObject = this.shops.find((s) => s.id === id)
            return shopObject.name;
        },
        getBuyerName(id) {
            const buyerObject = this.buyers.find((s) => s.id === id)
            return buyerObject.name;
        },
        filter(filter, value) {
            this.params[filter] = value;
        },
        resetDate() {
            this.params.date = null;
            let params = this.params;
            this.handleFilter(params)
        },
        resetStartDate() {
            this.params.start_date = null;
            let params = this.params;
            this.handleFilter(params)
        },
        resetEndDate() {
            this.params.end_date = null;
            let params = this.params;
            this.handleFilter(params)
        },
        handleFilter(params) {
            Object.keys(params).forEach(key => {
                if (params[key] == ''){
                    delete params[key]
                }
            }, 150);

            axios.get(this.route('refunds.index', this.params)).then(response => {
                this.totalSales = response.data.total
                this.userRefunds = {
                    ...response.data.orders,
                    data: [...response.data.orders.data]
                }
            });
        },
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
                axios.get(this.route('refunds.index', this.params)).then(response => {
                    this.userRefunds = {
                        ...response.data.refunds,
                        data: [...response.data.refunds.data]
                    }
                    this.totalRefunds = response.data.total;
                });
                // this.$inertia.get(this.route('refunds.index'), this.params, { replace: true, preserveState: true});
            }),
            deep: true
        },
        date: {
            handler: throttle(function () {
                this.params.date = this.date.value;
                let params = this.params;
                this.handleFilter(params)
            }),
            deep: true
        },
        start_date: {
            handler: throttle(function () {
                this.params.start_date = this.start_date.value;
                let params = this.params;
                this.handleFilter(params)
            }),
            deep: true
        },
        end_date: {
            handler: throttle(function () {
                this.params.end_date = this.end_date.value;
                let params = this.params;
                this.handleFilter(params)
            }),
            deep: true
        },
    },
    setup() {
        const admin = computed(() => usePage().props.value.auth.user)

        const start_date = ref();
        const end_date = ref();
        const date = ref();

        const handleDate = (date) => {
            if(date){
                const day = date.getDate();
                const month = date.getMonth() + 1;
                const year = date.getFullYear();

                date.value =  `${year}/${month}/${day}`;
            }
        }

        const handleStartDate = (start_date) => {
            if(start_date){
                const day = start_date.getDate();
                const month = start_date.getMonth() + 1;
                const year = start_date.getFullYear();

                start_date.value =  `${year}/${month}/${day}`;
            }
        }

        const handleEndDate = (end_date) => {
            if(end_date){
                const day = end_date.getDate();
                const month = end_date.getMonth() + 1;
                const year = end_date.getFullYear();

                end_date.value =  `${year}/${month}/${day}`;
            }
        }

        return { admin, start_date, handleStartDate, end_date, handleEndDate, date, handleDate }
    },
    mounted() {
        window.addEventListener('scroll', debounce((e) => {
            let pixelsFromBottom = document.documentElement.offsetHeight - document.documentElement.scrollTop - window.innerHeight;

            if (pixelsFromBottom < 200){
                axios.get(this.userRefunds.next_page_url, { params: this.params }).then(response => {
                    this.userRefunds = {
                        ...response.data,
                        data: [...this.userRefunds.data, ...response.data.data]
                    }
                });
            }
        }, 100))
    },
}
</script>


<style>
    #customers-list .absolute {
        max-height: 400px;
        overflow-y: scroll;
    }
</style>
