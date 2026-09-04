<template>
  <app-layout title="Products Management">
      <div class="flex justify-around mt-8">
          <div class="flex justify-between">
              <inertia-link :href="route('productColors.create')" v-if="admin.role === 1 || admin.role === 2">
                  <jet-button class="px-16 mt-8 bg-pcr float-right">
                      {{ __('Add Product')}}
                  </jet-button>
              </inertia-link>
          </div>
          <div class="mt-8 flex justify-between space-x-4">
              <h3 @click="searchForModel()" class="text-xs font-bold capitalize hover:text-blue-400 hover:cursor-pointer text-blue-600 p-3">{{ __('Model')}}</h3>
              <div class="max-w-2xl">
                  <input dir="rtl"  type="search" v-model="params.search" :placeholder="__('Search')" class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
              </div>
              <h3 class="text-2xl font-bold capitalize text-primary">{{ __('Products')}}</h3>
          </div>
      </div>
      <div
          v-if="isLoading"
          class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center"
          id="my-modal"
      ><loading></loading></div>
    <MeeTable :tableTitle="'All Products'">
        <div v-if="productColors.data.length === 0" class="my-40 flex items-center justify-center text-xl font-bold text-error">
            <span class="text-center">لا يوجد منتجات!</span>
        </div>
        <table v-else class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr class="shadow-2xl py-4">
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    {{ __('Image')}}
                </th>
              <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer" @click="sort('id')">
                  {{ __('Product')}}
                  <vue-feather :type="'chevron-up'" stroke-width="2" class="p-1 h-4 w-8 place-self-center inline-block" v-if="params.field==='id' &&  params.direction==='asc'"></vue-feather>
                  <vue-feather :type="'chevron-down'" stroke-width="2" class="p-1 h-4 w-8 place-self-center inline-block" v-if="params.field==='id' &&  params.direction==='desc'"></vue-feather>
              </th>
                <th scope="col" class="flex flex-col px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    <span>Barcode(الباركود)</span>
                    <span>Stock(الكمية)</span>
                    <span>{{ __('Color')}}{{ __('Category')}}</span>
                </th>
              <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer" @click="sort('cost_price')">
                  {{ __('Cost Price')}}
                  <vue-feather :type="'chevron-up'" stroke-width="2" class="p-1 h-4 w-8 place-self-center inline-block" v-if="params.field==='cost_price' &&  params.direction==='asc'"></vue-feather>
                  <vue-feather :type="'chevron-down'" stroke-width="2" class="p-1 h-4 w-8 place-self-center inline-block" v-if="params.field==='cost_price' &&  params.direction==='desc'"></vue-feather>
              </th>
                <!-- <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    {{ __('Sizes')}}
                </th> -->
              <th scope="col" class="px-8 py-3 text-left font-semibold text-lg text-pcr tracking-wider" v-if="admin.role === 1 || admin.role === 2">
                  {{ __('Actions')}}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200 text-pcr">
            <tr v-for="item in productColors.data" :key="item.id" class="font-sans-latin text-sm font-medium">
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-full">
                            <img class="w-full rounded-lg" :src="item.photo_url" alt="">
                        </div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div dir="rtl" class="text-sm font-medium">{{ item.product.name }}</div>
                        <div dir="rtl" class="text-xs">{{ item.product.details }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm text-center font-medium bg-fuchsia-200 p-1 rounded-lg">Barcode: {{ item.barcode }}</div>

                        <div class="text-sm text-center font-medium bg-amber-100 p-1 rounded-lg">
                            الكمية المسجلة: {{ catalogStock(item) }}
                        </div>

                        <!-- <div class="text-sm text-center font-medium bg-teal-200 p-1 rounded-lg">Sizes: {{ item.size }}</div> -->

                        <div class="text-sm text-center font-medium bg-rose-200 p-1 rounded-lg">Color: {{ item.color.name }} {{ item.color.code }}</div>

                        <div class="text-sm text-center font-medium bg-teal-200 p-1 rounded-lg">Category: {{ item.product.category.name }}</div>
                    </div>
                </td>

                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">{{ formattedPrice(item.product.cost_price) }} {{ currency.code }}</td>
                <!-- <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">{{ item.product.raw_sizes }}</td> -->
                <td class="flex flex-wrap space-x-3 mt-2" v-if="admin.role === 1 || admin.role === 2">
                     <!--<p @click="printItems(item.id,item.stock,item.barcode)" class="p-2 pb-1 rounded-md text-white btn-ghost bg-teal-400 hover:bg-teal-600 hover:text-white">
                        <vue-feather :type="'printer'" stroke-width="2"></vue-feather>
                    </p>

                    <a @click="printItem(item.id,item.barcode)" class="p-2 pb-1 rounded-md text-white btn-ghost bg-teal-400 hover:bg-teal-600 hover:text-white">
                        <vue-feather :type="'credit-card'" stroke-width="2"></vue-feather>
                    </a> -->

                    <button class="p-2 pb-1 rounded-md text-white btn-ghost bg-blue-400 hover:bg-blue-600 hover:text-white" @click="sendToShop(item)" v-if="admin.role === 1 || admin.role === 2">
                        <vue-feather :type="'shopping-cart'" stroke-width="2"></vue-feather>
                    </button>
                </td>
            </tr>
          </tbody>
        </table>
    </MeeTable>

      <transition name="modal">
          <modal v-if="showModal" @close="closeModal">
              <div class="modal-mask rounded-2xl">
                  <div class="modal-wrapper m-24 ">
                      <div class="modal-container relative">
                          <span class="absolute top-1 right-1 mt-1 mr-2 cursor-pointer" @click="closeModal()">X</span>

                          <div class="modal-header flex justify-center">
                               <h3 class="text-teal-600 text-3xl font-bold">{{ __('Send')}} {{this.item.product.name}} {{ __('To')}}</h3>
                           </div>
                           <div class="mt-3 text-center text-lg font-bold text-pcr" dir="rtl">
                               إجمالي الكمية المراد إرسالها: {{ transferQuantityTotal }}
                           </div>

                          <div class="modal-body border-b-4 border-state-500 pb-6"  style="height: 500px;overflow: scroll;">
                              <div class="flex justify-around items-stretch py-6">
                                  <div class="px-1" dir="rtl">
                                      <jet-label for="user" :value="__('Shop')" />
                                      <Multiselect v-model="user" :options="users" :multiple="false" :close-on-select="true" placeholder="اختر محل أو مستودع من القائمة" label="name"
                                                   track-by="id" />
                                  </div>
                                  <div class="px-1">
                                      <jet-label for="wholesale_price" :value="__('Wholesale Price') + ' (' + currency.code + ')'" dir="rtl" />
                                      <jet-input id="wholesale_price" type="number" min="0" :step="priceStep()" class="mt-1 block w-full" v-model="wholesale_price" autocomplete="wholesale_price" @blur="normalizePrice('wholesale_price')" />
                                  </div>

                                  <div class="px-1">
                                      <jet-label for="retail_price" :value="__('Retail Price') + ' (' + currency.code + ')'" dir="rtl" />
                                      <jet-input id="retail_price" type="number" min="0" :step="priceStep()" class="mt-1 block w-full" v-model="retail_price" autocomplete="retail_price" @blur="normalizePrice('retail_price')" />
                                  </div>

                                  <!-- <div class="px-1">
                                      <jet-label for="stock" :value="__('Stock')" dir="rtl" />
                                      <jet-input id="stock" type="number" class="mt-1 block w-full" v-model="stock" autocomplete="stock" />
                                  </div> -->
                                  <div class="px-1" dir="rtl">
                                      <jet-label for="merchant" :value="__('Merchant')" />
                                      <Multiselect v-model="merchant" :options="merchants" :multiple="false" :close-on-select="true" placeholder="اختر تاجر من قائمة التجار" label="name"
                                                   track-by="id" />
                                  </div>
                                  <!-- <div class="px-1">
                                      <jet-label for="barcode" :value="__('Barcode')" dir="rtl" />
                                      <jet-input id="barcode" type="text" class="mt-1 block w-full" v-model="barcode" autocomplete="barcode" />
                                  </div> -->
                              </div>
                              <div class="bg-gray-100 w-100" v-for="(size,index) in clsizes">
                                    <div class="flex justify-around px-4" style="align-items: end;border: 1px solid;padding: 15px;border-radius: 15px;">
                                        <div class="flex flex-wrap space-x-3 mt-2" v-if="admin.role === 1 || admin.role === 2" style="margin: auto 0;">
                                            <p @click="printItemsMulti(itemId, size.barcode, Number(size.quantity) || 1)" class="p-2 pb-1 rounded-md text-white btn-ghost bg-teal-400 hover:bg-teal-600 hover:text-white" style="max-height: 50px;">
                                                <vue-feather :type="'printer'" stroke-width="2"></vue-feather>
                                            </p>

                                            <a @click="printItems(itemId, size.barcode)" class="p-2 pb-1 rounded-md text-white btn-ghost bg-teal-400 hover:bg-teal-600 hover:text-white" style="max-height: 50px;">
                                                <vue-feather :type="'credit-card'" stroke-width="2"></vue-feather>
                                            </a>
                                        </div>
                                        <div class="inline-block align-middle mt-2" dir="rtl">
                                            <jet-label for="" :value="'Size (الحجم)'" style="font-size: 1rem;" />
                                            <jet-label for="" :value="size.size" style="font-size: 1rem;color: red;text-align: center;padding: 10px;" />
                                        </div>
                                        <div class="inline-block align-middle mt-2" dir="rtl">
                                            <jet-label for="" :value="'Barcode (الباركود)'" style="font-size: 1rem;" />
                                             <jet-label for="" :value="size.barcode" style="font-size: 1rem;color: red;text-align: center;padding: 10px;" />
                                             <jet-label for="" :value="'الكمية المسجلة: ' + (Number(size.stock) || 0)" style="font-size: 14px;color: #92400e;text-align: center;padding: 10px;" dir="rtl" />
                                        </div>
                                        <div class="inline-block align-middle mt-2">
                                            <jet-label :for="'sizestock'+size.size" :value="__('Stock (الكمية)')" style="font-size: 1rem;" dir="rtl" />
                                            <jet-label :for="'sizestock'+size.size" value="أدخل الكمية المراد إرسالها" style="font-size: 14px;color: red;text-align: center;padding: 10px;" dir="rtl" />
                                            <jet-input :id="'sizestock'+size.size" dir="ltr" type="number" min="1" class="mt-1 block w-full" v-model="size.quantity" />
                                        </div>
                                    </div>
                                </div>
                              <div class="flex justify-center mt-6" v-if="resultError">
                                  <div class="bg-rose-500 text-white text-center rounded-md w-3/4">
                                      <vue-feather :type="'alert-triangle'" stroke-width="2" class="h-4 w-8 place-self-center inline-block"></vue-feather>
                                      {{ __('Please fill required fields')}}
                                  </div>
                              </div>
                          </div>

                          <div class="modal-footer mt-12 border">
                              <button v-if="this.resultLoading" class="px-8 py-1 hover:bg-teal-300 bg-teal-400 text-white rounded-lg" disabled>
                                  <svg class="mr-2 w-4 h-4 text-gray-200 animate-spin fill-pcr" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                      <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"></path>
                                      <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"></path>
                                  </svg>
                                  يتم الحفظ...
                              </button>
                              <button v-else class="px-4 py-1 hover:bg-teal-500 bg-teal-400 text-white rounded-lg" @click="confirmResult()">
                                  {{ __('Save')}}
                              </button>
                              <button class="modal-default-button px-2 py-1 hover:bg-rose-600 bg-rose-400 text-white rounded-lg" @click="closeModal()">
                                  {{ __('Cancel')}}
                              </button>
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
import loading from "@/Shared/loading";
import {debounce} from "lodash/function";
import JetLabel from '@/Jetstream/Label.vue'
import Multiselect from '@suadelabs/vue3-multiselect'
import JetInput from '@/Jetstream/Input.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import {computed} from "vue";
import {usePage} from "@inertiajs/inertia-vue3";
import Currency from '@/Utils/Currency.js';
import Receipt from '@/Utils/Receipt.js';

const components = { AppLayout, MeeTable, Pagination, JetButton, loading, JetLabel, Multiselect, JetInput, JetInputError }

export default {
    name: 'PortalProductsIndex',

    components,

    props: {
        products: Object,
        nproducts: Object,
        filters: Object,
        users: [Array, Object],
        merchants: Object,
        sizes: Object,
        rate: Number,
        currency: Object,
    },
    data() {
        return {
            params: {
                search: this.filters.search,
                field: this.filters.field,
                direction: this.direction
            },
            isLoading: false,
            productColors: this.products,
            showModal: false,
            itemId: null,
            item: null,
            resultError: false,
            resultLoading: false,
            user: '',
            merchant: '',
            retail_price: '',
            wholesale_price: '',
            stock: '',
            size: '',
            barcode: '',
            lsizes: null,
            clsizes:null,
            page: 2,

        }
    },
    computed: {
        transferQuantityTotal() {
            return (this.clsizes || []).reduce((total, size) => {
                const quantity = Number(size.quantity)
                return total + (Number.isFinite(quantity) && quantity > 0 ? quantity : 0)
            }, 0)
        },
    },
    methods: {
        catalogStock(item) {
            const sizes = Array.isArray(item?.clone_list_sizes) ? item.clone_list_sizes : []
            if (!sizes.length) return Number(item?.stock) || 0

            return sizes.reduce((total, size) => total + (Number(size.stock) || 0), 0)
        },
        confirmResult(){
            this.normalizePrice('retail_price')
            this.normalizePrice('wholesale_price')
            const destinationId = this.user?.id
            const items = (this.clsizes || []).filter(size => Number(size.quantity) > 0)
            if (!destinationId || !this.retail_price || !this.wholesale_price || !items.length){
                this.resultError = true;
            }else{
                this.resultLoading = true;
                let formData = new FormData;
                formData.append('destination_user_id', destinationId)
                if (this.merchant)
                    formData.append('merchant_id', this.merchant.id)
                formData.append('retail_price', this.retail_price)
                formData.append('wholesale_price', this.wholesale_price)
                formData.append('currency_code', this.currency.code)
                formData.append('source_type', 'catalog')
                formData.append('product_color_id', this.itemId)
                formData.append('items', JSON.stringify(items))

                axios.post(this.route('userProducts.store'), formData,{
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    },
                    preserveScroll: true
                }).then((result) => {
                    if (result.status === 200){
                        this.showSuccessMessage(result.data.msg)
                        this.$inertia.get(route('userProducts.index', {shop: destinationId}))
                    }else{
                        this.showErrorMessage('حدث خطأ ما')
                    }
                    this.resultLoading = false;
                    this.showModal = false;
                    this.itemId = null;
                    this.size = null;
                    this.item = null;
                    this.resultError = false;
                    this.stock = null;
                    this.merchant = null;
                    this.user = null;
                    this.retail_price = null;
                    this.wholesale_price = null;
                }).catch((error) => {
                    this.resultLoading = false;
                    this.showErrorMessage(this.transferErrorMessage(error))
                });
            }
        },
        searchForModel(){
          if (this.params.search){
              let myString = this.params.search.replace((/\D\d*/g),'');
              this.params.search = myString;
          }
        },
        closeModal(){
            this.showModal = false;
            this.resetTransferState();
        },
        resetTransferState(){
            this.resultLoading = false;
            this.itemId = null;
            this.size = null;
            this.item = null;
            this.resultError = false;
            this.stock = null;
            this.barcode = null;
            this.user = null;
            this.merchant = null;
            this.retail_price = null;
            this.wholesale_price = null;
            this.lsizes = null;
            this.clsizes = null;
        },
        async sendToShop(item) {
            this.resetTransferState();
            const catalogSizes = Array.isArray(item.clone_list_sizes)
                ? item.clone_list_sizes.filter(size => size.size && size.barcode)
                : [];
            if (!catalogSizes.length) {
                this.showErrorMessage('لا توجد مقاسات صالحة للإرسال لهذا الموديل');
                return;
            }

            this.showModal = true;
            this.itemId = item.id;
            this.size = item.size;
            this.item = item;
            this.barcode = item.barcode;
            this.lsizes = catalogSizes
            this.clsizes = catalogSizes.map(size => ({
                ...size,
                quantity: 0,
            }))
            this.stock = item.stock;
            this.wholesale_price = this.displayPrice(item.product.cost_price);
            this.retail_price    = this.displayPrice(item.product.retail_price);
        },
        transferErrorMessage(error) {
            const errors = error.response?.data?.errors || {};
            const fields = ['destination_user_id', 'items', 'merchant_id', 'retail_price', 'wholesale_price'];

            for (const field of fields) {
                const messages = errors[field];
                if (Array.isArray(messages) && messages.length) return messages[0];
                if (typeof messages === 'string' && messages) return messages;
            }

            return error.response?.data?.message || 'حدث خطأ أثناء إرسال البضاعة';
        },
        displayPrice(value) {
            return Currency.fromUsd(value, this.currency.rate, this.currency.decimals)
        },
        formattedPrice(value) {
            return Currency.formatFromUsd(value, this.currency.rate, this.currency.code)
        },
        priceStep() {
            return Currency.inputStep(this.currency.code)
        },
        normalizePrice(field) {
            if (this[field] === '' || this[field] === null || this[field] === undefined) return
            this[field] = Currency.normalizeInput(this[field], this.currency.code)
        },
        async printItems(item, barcode){
            this.isLoading = true;

            axios.post(this.route('products.print'), {id: item, barcode: barcode})
                .then((response) => {
                    this.isLoading = false;

                    const product = {
                        barcode: barcode,
                        name: response.data.name
                    };

                    Receipt.printProduct(product);
                    this.showSuccessMessage(('عملية طباعة ناجحة'))
                }).catch(error => {

                    this.isLoading = false;
                    this.showErrorMessage(('فشلت عملية الطباعة'))
            })
        },
        async printItemsMulti(item, barcode, stock){
            this.isLoading = true;

            axios.post(this.route('products.print'), {id: item, barcode: barcode})
                .then((response) => {
                    this.isLoading = false;

                    const product = {
                        barcode: barcode,
                        name: response.data.name,
                    };

                    Receipt.printProductMulti(product, stock);
                    this.showSuccessMessage(('Printed Successfully'))
                }).catch(error => {

                    this.isLoading = false;
                    this.showErrorMessage(('Printing Failed!'))
            })
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
        getGender(gender){
            if (gender === 1)
                return 'Male';
            if (gender === 2)
                return 'Female';
        },
        getStatus(status){
            if (status === 1)
                return 'Active';
            else
                return 'Inactive';
        },
        sort(field){
            this.params.field = field;
            this.params.direction = this.params.direction === 'asc' ? 'desc' : 'asc';
        },
        deleteProduct(id) {
            this.$swal({
                title: __('Are you sure?'),
                text: __('You can\'t undo this operation'),
                showCancelButton: true,
                confirmButtonColor: '#014758',
                cancelButtonColor: '#d33',
                confirmButtonText: __('Yes, Delete'),
                cancelButtonText: __('Cancel'),
                width: 400,
                padding: '1em',
                color: '#014758',
            }).then((result) => {
                if (result.isConfirmed) {
                    this.$swal.fire({
                        html: '<p class="text-white pt-5 font-extrabold">'+__('Item Deleted Successfully')+'</p>',
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
                    this.$inertia.delete(route('productColors.destroy', id))
                }
            })

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

        // new code
        fetchData() {
            // Fetch data from the server and update this.items
            // Update loading state accordingly


            if(this.page <= this.productColors.last_page ) {

                // axios.get(this.route('productColors.index', { params: this.params,page: this.page++ })).then(response => {
                //     this.productColors = {
                //         ...response.data,
                //         data: [...this.productColors.data, ...response.data.data]
                //     }
                // });

                // let params = this.params;
                // Object.keys(params).forEach(key => {
                //     if (params[key] == ''){
                //         delete params[key]
                //     }
                // }, 400);

                // axios.get(this.productColors.next_page_url, { params: params }).then(response => {
                axios.get(app_url+'admin/productColors', { params: { params: this.params, page: this.page } }).then(response => {
                    this.productColors = {
                        ...response.data,
                        data: [...this.productColors.data, ...response.data.data]
                    }
                });
            }


        },
        handleScroll() {
            // let pixelsFromBottom = document.documentElement.offsetHeight - document.documentElement.scrollTop - window.innerHeight;
            // if (pixelsFromBottom < 50){
            if (window.innerHeight + window.scrollY + 50 >= document.documentElement.offsetHeight &&!this.loading) {
                this.page++;
                this.fetchData();
            }
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
                }, 400);
                axios.get(this.route('productColors.index', this.params)).then(response => {
                    this.productColors = {
                        ...response.data,
                        data: [...response.data.data]
                    }
                });
                // this.$inertia.get(this.route('productColors.index'), this.params, { replace: true, preserveState: true});
            }),
            deep: true
        }


    },
    mounted() {

        this.fetchData();
        window.addEventListener('scroll', this.handleScroll);

        // undefined;
        // window.addEventListener('scroll', debounce((e) => {
        //     let pixelsFromBottom = document.documentElement.offsetHeight - document.documentElement.scrollTop - window.innerHeight;

        //     if (pixelsFromBottom < 100){
        //         let params = this.params;
        //         Object.keys(params).forEach(key => {
        //             if (params[key] == ''){
        //                 delete params[key]
        //             }
        //         }, 400);
        //         axios.get(this.productColors.next_page_url, { params: params }).then(response => {
        //             this.productColors = {
        //                 ...response.data,
        //                 data: [...this.productColors.data, ...response.data.data]
        //             }
        //         });
        //     }
        // }, 300))
    },
    setup() {
        const admin = computed(() => usePage().props.value.auth.user)
        return { admin }
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
    width: 90%;
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
