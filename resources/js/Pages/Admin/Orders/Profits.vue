<template>
  <app-layout title="Products Management">
      <div class="mt-8 flex justify-around space-x-4">
          <h3 class="text-2xl font-bold capitalize text-primary"><span class="bg-emerald-500 px-2 rounded-md text-white">
              {{ currencyExchange(allProfits, rate, true) }}</span> الأرباح</h3>
          <div class="max-w-xs">
              <input dir="rtl"  type="search" v-model="params.search" placeholder="بحث..." class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
          </div>
          <div class="flex justify-between space-x-12">
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

              <div class="flex flex-col">
                  <label dir="rtl" class="pr-2">بحث بحسب المحل</label>
                  <div class="border-gray-300 shadow-sm border-2 rounded-md px-2">
                      <jet-dropdown align="right" width="48">
                          <template #trigger>
                              <button type="button" class="inline-flex items-center px-1 py-2 border border-transparent  rounded-md  focus:outline-none transition">
                                  <p v-if="params.shop">{{ getShopName(params.shop) }}</p>
                                  <p v-else>اضغط لاختيار المحل</p>
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
              </div>
              <div class="flex flex-col">
                  <label dir="rtl" class="pr-2">بحث بحسب الزبون</label>
                  <div class="border-gray-300 shadow-sm border-2 rounded-md px-2">
                      <jet-dropdown align="right" width="48">
                          <template #trigger>
                              <button type="button" class="inline-flex items-center px-1 py-2 border border-transparent  rounded-md  focus:outline-none transition">
                                  <p v-if="params.buyer">{{ getClientName(params.buyer) }}</p>
                                  <p v-else>اضغط لاختيار الزبون</p>
                              </button>
                          </template>
                          <template #icon v-if="params.buyer">
                              <vue-feather class="hover:cursor-pointer mt-2 text-pcr-mid" @click="filter('buyer', 0)" :type="'x'"></vue-feather>
                          </template>
                          <template #content>
                              <button @click="filter('buyer', 0)" class="w-full my-1 font-bold text-gray-600 hover:cursor-pointer text-center text-md bg-gray-50 rounded-md">كل المحلات</button>
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
              </div>

          </div>
      </div>
      <MeeTable :tableTitle="''">
        <div v-if="profitOrders.data.length === 0" class="my-40 flex items-center justify-center text-xl font-bold text-error">
            <span class="text-center">لا يوجد مرابح بعد!</span>
        </div>
        <table v-else class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr class="shadow-2xl py-4">
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    المنتجات
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer" @click="sort('id')">
                    Barcode (الباركود)
                    <vue-feather :type="'chevron-up'" stroke-width="2" class="p-1 h-4 w-8 place-self-center inline-block" v-if="params.field==='id' &&  params.direction==='asc'"></vue-feather>
                    <vue-feather :type="'chevron-down'" stroke-width="2" class="p-1 h-4 w-8 place-self-center inline-block" v-if="params.field==='id' &&  params.direction==='desc'"></vue-feather>
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    السعر الإجمالي
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    السعر المدفوع
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    المبلغ المتبقي
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    المحل
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    الشاري
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    شركة الشحن
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    التاريخ
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    المربح
                </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200 text-pcr">
            <tr v-for="item in profitOrders.data" :key="item.id" class="font-sans-latin text-sm font-medium">
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <button
                        class="p-2 m-1 pb-1 self-center rounded-md text-white btn-ghost bg-rose-400 hover:bg-red-600 hover:text-white"
                        @click="showProducts(item)">
                        <vue-feather :type="'tag'" stroke-width="2"></vue-feather>
                        البضائع
                    </button>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ item.barcode }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ item.total_price }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ item.paid_price }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ item.remain_price }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ item.seller?.name }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ item.buyer?.name }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ item.shipper?.name }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ item.date }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ getProfit(item) }}</div>
                    </div>
                </td>
            </tr>
          </tbody>
        </table>

    </MeeTable>

    <transition name="modal">
          <modal v-if="showModal" @close="showModal = false">
              <div class="modal-mask rounded-2xl">
                  <div class="modal-wrapper m-24 ">
                      <div class="modal-container relative">
                          <span class="absolute top-1 right-1 mt-1 mr-2 cursor-pointer" @click="closeModal()">
                              <vue-feather :type="'x'" class="w-8" stroke-width="2"></vue-feather>
                          </span>
                          <div class="modal-header flex justify-center">
                              <h3 class="text-teal-600 text-3xl font-bold"></h3>
                          </div>

                          <div class="modal-body border-b-4 border-state-500 pb-6">
                              <div class="flex grid grid-cols-2 items-stretch">
                                  <div class="flex justify-between" v-for="product in products">
                                      <img class="w-20 h-20 rounded-md" :src="product.product.product_color.photo_url" alt="">
                                      <div class="text-sm mx-1 text-left" dir="rtl">{{ product.product.product_color.product_name }} ( {{ product.qty }} )</div>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>

          </modal>
      </transition>

  </app-layout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import { MeeTable } from '@/Shared/Ui'
import { Pagination } from '@/Shared/Common'
import {throttle} from "lodash";
import { createPopper } from '@popperjs/core';
import JetButton from '@/Jetstream/Button.vue'
import { computed } from 'vue'
import { usePage } from '@inertiajs/inertia-vue3'
import JetDropdown from '@/Jetstream/Dropdown.vue'
import JetDropdownLink from '@/Jetstream/DropdownLink.vue'
import {ref} from "vue";
import Datepicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import {debounce} from "lodash/function";
import Currency from '@/Utils/Currency.js';

const components = { AppLayout, MeeTable, Pagination, JetButton, JetDropdown, JetDropdownLink, Datepicker }

export default {
    name: 'PortalProductsIndex',

    components,

    props: {
        orders: Object,
        filters: Object,
        shops: Array,
        buyers: Array,
        profit: Number,
        rate: Number
    },
    data() {
        return {
            params: {
                search: this.filters.search,
                field: this.filters.field,
                shop: this.filters.shop,
                buyer: this.filters.buyer,
                direction: this.direction,
                date: this.filters.date,
                start_date: this.filters.start_date,
                end_date: this.filters.end_date
            },
            profitOrders: this.orders,
            showModal: false,
            item: null,
            products: null,
            allProfits: this.profit,
            currencyExchange: Currency.getExchangeMethod(),
            rate: this.rate
        }
    },
    methods: {
        getProfit(item){
            let profit = 0;
            item.items.forEach((orderItem) => {
                profit += (Number(orderItem.item_price) - Number(orderItem.product.wholesale_price)) * Number(orderItem.qty)
            });

            return Currency.exchange(profit, item.curr_rate);
        },
        async showProducts(item) {
            this.showModal = true;
            this.item = item;
            this.products = item.items;
        },
        closeModal(){
            this.showModal = false;
            this.products = null;
            this.item = null
        },
        encodeUrlWhatsApp(id, number){
            let url = encodeURIComponent(route('invoice.show', id));
            return `https://wa.me/${number}/?text=${url}`;
        },
        getShopName(id){
            const shopObject = this.shops.find((s) => s.id === id)
            return shopObject.name;
        },
        getClientName(id){
            const buyerObject = this.buyers.find((s) => s.id === id)
            return buyerObject.name;
        },
        filter(filter, value){
            this.params[filter] = value;
        },
        async addPayment(id) {
            await this.$swal({
                title: 'إضافة دفعة من الزبون',
                text: "رجاءً أدخل قيمة الدفعة",
                input: 'text',
                inputPlaceholder: 'قيمة الدفعة',
                showCancelButton: true,
                confirmButtonColor: '#014758',
                cancelButtonColor: '#d33',
                confirmButtonText: 'أضف الدفعة',
                width: 400,
                padding: '1em',
                color: '#014758',
                inputValidator: (value) => {
                    return new Promise((resolve) => {
                        if (value > 0) {
                            resolve()
                        } else {
                            resolve('يجب أن تدخل قيمة الدفعة')
                        }
                    })
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    axios.post(route('orders.payment'), {
                        order: id,
                        amount: result.value
                    }).then((response) => {

                        if (response.data.success){
                            this.showSuccessMessage('تم إضافة قيمة الدفعة')
                            this.$inertia.get(this.route('orders.profits'))
                        } else
                            this.showErrorMessage(response.data.error)
                    })
                }
            })
        },
        showSuccessMessage(msg){
            return this.$swal.fire({
                    html: '<p class="text-white pt-5 font-extrabold">'+msg+'</p>',
                    icon: 'success',
                    iconColor: '#FFFFFF',
                    width: 400,
                    showConfirmButton: false,
                    padding: '1em',
                    toast: true,
                    position: 'bottom-end',
                    color: '#FFFFFF',
                    background: '#34d399',
                    timer: 2000,
                    timerProgressBar: true,
                },
            )
        },
        showErrorMessage(msg){
            return this.$swal.fire({
                    html: '<p class="text-white pt-5 font-extrabold">'+msg+'</p>',
                    icon: 'warning',
                    iconColor: '#FFFFFF',
                    width: 400,
                    showConfirmButton: false,
                    padding: '1em',
                    toast: true,
                    position: 'bottom-end',
                    color: '#FFFFFF',
                    background: '#e96e83',
                    timer: 2000,
                    timerProgressBar: true,
                },
            )
        },
        sort(field){
            this.params.field = field;
            this.params.direction = this.params.direction === 'asc' ? 'desc' : 'asc';
        },
        deleteColor(id) {
            this.$swal({
                title: 'هل أنت متأكد؟',
                text: "لن تتمكن من التراجع عن هذه الخطوة",
                showCancelButton: true,
                confirmButtonColor: '#014758',
                cancelButtonColor: '#d33',
                confirmButtonText: 'نعم احذف اللون',
                width: 400,
                padding: '1em',
                color: '#014758',
            }).then((result) => {
                if (result.isConfirmed) {
                    this.$swal.fire({
                        html: '<p class="text-white pt-5 font-extrabold">تم حذف الطلبية بنجاح</p>',
                        icon: 'success',
                        iconColor: '#FFFFFF',
                        width: 400,
                        showConfirmButton: false,
                        padding: '1em',
                        toast: true,
                        position: 'bottom-end',
                        color: '#FFFFFF',
                        background: '#e07575',
                        timer: 2000,
                        timerProgressBar: true,
                        },
                    )
                    this.$inertia.delete(route('orders.destroy', id))
                }
            })

        },
        bgColor(color){
            return {
                'background-color': color
            };
        },
        openPopover(event, tooltipID) {
            let element = event.target;
            while (element.nodeName !== "BUTTON") {
                element = element.parentNode;
            }
            createPopper(element, document.getElementById(tooltipID), {
                placement: 'top'
            });
            document.getElementById(tooltipID).classList.toggle("hidden");
        },
        handleFilter(params){
            Object.keys(params).forEach(key => {
                if (params[key] == '' || params[key] == null){
                    delete params[key]
                }
            }, 400);

            axios.get(this.route('orders.profits', this.params)).then(response => {

                if (response.data.profit < 0){
                    this.allProfits = 0;
                }else {
                    this.allProfits = response.data.profit;
                }
                this.profitOrders = {
                    ...response.data.orders,
                    data: [...response.data.orders.data]
                }
            });
            // this.$inertia.get(this.route('orders.profits', params), this.params, { replace: true, preserveState: true});
        },
        resetDate(){
            this.params.date = null;
            let params = this.params;
            this.handleFilter(params)
        },
        resetStartDate(){
            this.params.start_date = null;
            let params = this.params;
            this.handleFilter(params)
        },
        resetEndDate(){
            this.params.end_date = null;
            let params = this.params;
            this.handleFilter(params)
        },
    },
    watch: {
        params: {
            handler: throttle(function () {
                let params = this.params;
                this.handleFilter(params)
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
        const user = computed(() => usePage().props.value.auth.user)

        const date = ref();
        const start_date = ref();
        const end_date = ref();
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

        return { user, date, handleDate, start_date, handleStartDate, end_date, handleEndDate }
    },
    mounted() {
        window.addEventListener('scroll', debounce((e) => {
            let pixelsFromBottom = document.documentElement.offsetHeight - document.documentElement.scrollTop - window.innerHeight;

            if (pixelsFromBottom < 200){
                axios.get(this.profitOrders.next_page_url, { params: this.params }).then(response => {
                    this.profitOrders = {
                        ...response.data.orders,
                        data: [...this.profitOrders.data, ...response.data.orders.data]
                    }
                });
            }
        }, 100))
    },

}
</script>
<style scoped>
.modal-mask {
    position: fixed;
    z-index: 9998;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: table;
    transition: opacity 0.3s ease;
}

.modal-wrapper {
    display: table-cell;
    vertical-align: middle;
}

.modal-container {
    width: 70%;
    margin: 0px auto;
    padding: 20px 30px;
    background-color: #fff;
    border-radius: 2px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
    transition: all 0.3s ease;
    font-family: Helvetica, Arial, sans-serif;
}

.modal-header h3 {
    margin-top: 20px;
}

.modal-body {
    margin: 20px 0;
}

.modal-default-button {
    float: right;
}
.modal-enter-from, .modal-leave-to {
    opacity: 0;
}

.modal-enter-active .modal-container,
.modal-leave-active .modal-container {
    -webkit-transform: scale(1.1);
    transform: scale(1.1);
}

</style>
