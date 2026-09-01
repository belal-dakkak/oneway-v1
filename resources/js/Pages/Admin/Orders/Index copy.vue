<template>
  <app-layout title="Products Management">
      <div class="flex justify-around mt-8">


          <div class="flex justify-between" v-if="user.role === 3 || user.role === 2">

              <inertia-link :href="route('orders.create')">
                  <jet-button class="px-16 mt-8 bg-pcr float-right">
                      إنشاء طلبية
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
          <div class="flex justify-between" v-if="user.role === 1">
                <jet-button class="px-16 mt-8 bg-pcr float-right">
                    <download-csv
                        class   = "btn btn-default"
                        :data   = "json_data"
                        name = "orders.csv">
                        إنشاء كشف
                    </download-csv>
                </jet-button>
            </div>
          <div class="h-1/3 border-gray-300 shadow-sm border-2 rounded-md px-2">
              <jet-dropdown align="right" width="48">
                  <template #trigger>
                      <button type="button" class="inline-flex items-center px-1 py-2 border border-transparent  rounded-md  focus:outline-none transition">
                          <p v-if="params.buyer">{{ getBuyerName(params.buyer) }}</p>
                          <p v-else>بحث بحسب الزبون</p>
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

          <div class="flex flex-col" v-if="user.role === 1">
              <label dir="rtl" class="pr-2"> إجمالي المبيع</label>
              <span class="bg-emerald-500 px-2 rounded-md text-3xl text-white">{{totalSales}}</span>
          </div>
          <div class="mt-8 flex justify-between space-x-4">
              <div class="max-w-xs">
                  <input dir="rtl"  type="search" v-model="params.search" placeholder="بحث..." class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
              </div>
              <h3 class="text-2xl font-bold capitalize text-primary">الطلبيات</h3>
          </div>
      </div>
        <MeeTable :tableTitle="''">
        <div v-if="userOrders.data.length === 0" class="my-40 flex items-center justify-center text-xl font-bold text-error">
            <span class="text-center">لا يوجد طلبيات!</span>
        </div>
        <table v-else class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr class="shadow-2xl py-4">
                <th scope="col" class="px-6 py-3 text-left font-semibold text-sm tracking-wider cursor-pointer">
                    المنتجات
                </th>
                <th scope="col" class="px-2 py-3 text-left font-semibold text-sm tracking-wider cursor-pointer" @click="sort('id')">
                    Barcode (الباركود)
                    <vue-feather :type="'chevron-up'" stroke-width="2" class="p-1 h-4 w-8 place-self-center inline-block" v-if="params.field==='id' &&  params.direction==='asc'"></vue-feather>
                    <vue-feather :type="'chevron-down'" stroke-width="2" class="p-1 h-4 w-8 place-self-center inline-block" v-if="params.field==='id' &&  params.direction==='desc'"></vue-feather>
                </th>
                <th scope="col" class="px-2 py-3 text-left font-semibold text-sm tracking-wider cursor-pointer">
                    <p>السعر الإجمالي</p>
                    <p>السعر المدفوع</p>
                    <p>المبلغ المتبقي</p>
                </th>
                <th scope="col" class="px-2 py-3 text-left font-semibold text-sm tracking-wider cursor-pointer">
                    المحل
                </th>
                <th scope="col" class="px-2 py-3 text-left font-semibold text-sm tracking-wider cursor-pointer">
                    الشاري
                </th>
                <th scope="col" class="px-2 py-3 text-left font-semibold text-sm tracking-wider cursor-pointer">
                    شركة الشحن
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-sm tracking-wider cursor-pointer">
                    التاريخ
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-sm tracking-wider cursor-pointer">
                    الإجراءات
                </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="item in userOrders.data" :key="item.id" class="font-sans-latin text-sm font-medium">
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
                <td class="mx-auto max-w-sm p-2 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm text-center font-medium font-medium bg-fuchsia-200 p-1 m-1 rounded-lg">{{ item.total_price }}</div>
                        <div class="text-sm text-center font-medium font-medium bg-teal-200 p-1 m-1 rounded-lg">{{ item.paid_price }}</div>
                        <div class="text-sm text-center font-medium font-medium bg-rose-200 p-1 m-1 rounded-lg">{{ item.remain_price }}</div>
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
                <td class="flex flex-wrap" dir="ltr">
                    <a target="_blank" :href="route('download.invoice.show', item.id)" class="p-2 m-1 pb-1 rounded-md text-white btn-ghost bg-teal-400 hover:bg-teal-600 hover:text-white">
                        <vue-feather :type="'share'" stroke-width="2"></vue-feather>
                    </a>
                    <a target="_blank" :href="route('invoice.show', item.id)" class="p-2 m-1 pb-1 rounded-md text-white btn-ghost bg-teal-400 hover:bg-teal-600 hover:text-white">
                        <vue-feather :type="'cast'" stroke-width="2"></vue-feather>
                    </a>
                    <a v-if="item.buyer" target="_blank" :href="encodeUrlWhatsApp(item.id, item.buyer?.phone)" data-action="share/whatsapp/share" class="p-2 m-1 pb-1 rounded-md text-white btn-ghost bg-teal-400 hover:bg-teal-600 hover:text-white">
                        <vue-feather :type="'send'" stroke-width="2"></vue-feather>
                    </a>
                    <a v-if="item.shipper" target="_blank" :href="encodeUrlWhatsApp(item.id, item.shipper?.phone, true)" data-action="share/whatsapp/share" class="p-2 m-1 pb-1 rounded-md text-white btn-ghost bg-teal-400 hover:bg-teal-600 hover:text-white">
                        <vue-feather :type="'truck'" stroke-width="2"></vue-feather>
                    </a>
<!--                    <button-->
<!--                        v-if="(item.remain_price > 0) && (user.role === 3 || user.role === 2)"-->
<!--                        class="p-2 m-1 pb-1 rounded-md text-white btn-ghost bg-rose-400 hover:bg-red-600 hover:text-white"-->
<!--                        @click="addPayment(item.id)">-->
<!--                        <vue-feather :type="'credit-card'" stroke-width="2"></vue-feather>-->
<!--                    </button>-->
                    <a @click="printItem(item.id)" class="p-2 m-1 pb-1 rounded-md text-white btn-ghost bg-teal-400 hover:bg-teal-600 hover:text-white">
                        <vue-feather :type="'credit-card'" stroke-width="2"></vue-feather>
                    </a>
                    <inertia-link :href="route('orders.edit', item.id)" class="p-2 m-1 pb-1 rounded-md text-white btn-ghost bg-teal-400 hover:bg-teal-600 hover:text-white">
                        <vue-feather :type="'edit'" stroke-width="2"></vue-feather>
                    </inertia-link>
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

                          <div class="modal-body border-b-4 border-state-500 pb-6" style="height: 500px;overflow: auto;">
                              <div class="flex grid grid-cols-2 items-stretch">
                                  <div class="flex justify-between p-2" v-for="product in products">
                                      <img class="w-20 h-20 rounded-md" :src="product.product.product_color.photo_url" alt="">
                                      <div class="text-sm mx-1 text-left" dir="rtl">{{ product.product.product_color.product_name +" size:"+ product.product.size }} ( {{ product.qty }} )</div>
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
import JetDropdown from "@/Jetstream/Dropdown";
import JetDropdownLink from "@/Jetstream/DropdownLink";
import {debounce} from "lodash/function";
import {ref} from "vue";
import Datepicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import JsonCSV from 'vue-json-csv'

const components = { AppLayout, MeeTable, Pagination, JetButton,  JetDropdown, JetDropdownLink, Datepicker, 'download-csv': JsonCSV}

export default {
    name: 'PortalProductsIndex',

    components,

    props: {
        orders: Object,
        filters: Object,
        shops: Array,
        buyers: Array,
        total: Number
    },
    data() {
        var ordersd = []
        this.orders.data.forEach(order => {
            ordersd.push({
                'barcode': order.barcode,
                'total_price': order.total_price,
                'paid_price': order.paid_price,
                'remain_price': order.remain_price,
                'seller': order.seller?order.seller.name:'',
                'buyer': order.buyer?order.buyer.name:'',
                'shipper': order.shipper?order.shipper.name:'',
                'date': order.date
            })
        });
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
            userOrders: this.orders,
            isLoading: false,
            showModal: false,
            item: null,
            products: null,
            totalSales: this.total,
            json_data: ordersd
        }
    },
    methods: {
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
        getShopName(id){
            const shopObject = this.shops.find((s) => s.id === id)
            return shopObject.name;
        },
        getBuyerName(id){
            const buyerObject = this.buyers.find((s) => s.id === id)
            return buyerObject.name;
        },
        filter(filter, value){
            this.params[filter] = value;
        },
        encodeUrlWhatsApp(id, number, ship = false){
            let url;
            if (ship)
                url = encodeURIComponent(route('invoice.shipper.show', id));
            else
                url = encodeURIComponent(route('download.invoice.show', id));
                // url = encodeURIComponent(route('invoice.show', id));

            return `https://wa.me/${number}/?text=${url}`;
        },
        printItem(item){
            this.isLoading = true;
            let formData = new FormData;
            formData.append('order', item)
            axios.post(this.route('orders.print', item), formData)
                .then((response) => {
                    this.isLoading = false;
                    this.showSuccessMessage('تمت عملية الطباعة بنجاح')
                }).catch(error => {
                this.isLoading = false;
                this.showErrorMessage('فشل عملية الطباعة, حاول لاحقاً رجاءً')
            })
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
                            this.$inertia.get(this.route('orders.index'))
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
                if (params[key] == ''){
                    delete params[key]
                }
            }, 400);

            axios.get(this.route('orders.index', this.params)).then(response => {
                this.totalSales = response.data.total
                this.userOrders = {
                    ...response.data.orders,
                    data: [...response.data.orders.data]
                }
                this.json_data = []
                this.userOrders.data.forEach(order => {
                    this.json_data.push({
                        'barcode': order.barcode,
                        'total_price': order.total_price,
                        'paid_price': order.paid_price,
                        'remain_price': order.remain_price,
                        'seller': order.seller?order.seller.name:'',
                        'buyer': order.buyer?order.buyer.name:'',
                        'shipper': order.shipper?order.shipper.name:'',
                        'date': order.date
                    })
                });
            });
        },
    },
    watch: {
        params: {
            handler: throttle(function () {
                let params = this.params;
                this.handleFilter(params)
                // this.$inertia.get(this.route('orders.index'), this.params, { replace: true, preserveState: true});
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

        return { user, start_date, handleStartDate, end_date, handleEndDate, date, handleDate }
    },
    mounted() {
        window.addEventListener('scroll', debounce((e) => {
            let pixelsFromBottom = document.documentElement.offsetHeight - document.documentElement.scrollTop - window.innerHeight;

            if (pixelsFromBottom < 200){
                axios.get(this.userOrders.next_page_url, { params: this.params }).then(response => {
                    this.totalSales = response.data.total
                    this.userOrders = {
                        ...response.data.orders,
                        data: [...this.userOrders.data, ...response.data.orders.data]
                    }
                    this.json_data = []
                    this.userOrders.data.forEach(order => {
                        this.json_data.push({
                            'barcode': order.barcode,
                            'total_price': order.total_price,
                            'paid_price': order.paid_price,
                            'remain_price': order.remain_price,
                            'seller': order.seller?order.seller.name:'',
                            'buyer': order.buyer?order.buyer.name:'',
                            'shipper': order.shipper?order.shipper.name:'',
                            'date': order.date
                        })
                    });
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
