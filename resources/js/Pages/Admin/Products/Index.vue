<template>
  <app-layout title="Products Management">
      <div class="flex justify-around mt-8">
          <div class="flex justify-between">
              <inertia-link :href="route('productColors.create')">
                  <jet-button class="px-16 mt-8 bg-pcr float-right">
                      {{ __('Add Model')}}
                  </jet-button>
              </inertia-link>
          </div>
          <div class="mt-8 flex justify-between space-x-4">
              <div class="max-w-xs">
                  <input dir="rtl"  type="search" v-model="params.search" :placeholder="__('Search')" class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
              </div>
              <h3 class="text-2xl font-bold capitalize text-primary">{{ __('Models')}}</h3>
          </div>
      </div>
    <MeeTable :tableTitle="'All Products'">
        <div v-if="productModels.data.length === 0" class="my-40 flex items-center justify-center text-xl font-bold text-error">
            <span class="text-center">لا يوجد موديلات!</span>
        </div>
        <table v-else class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr class="shadow-2xl py-4">
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    {{ __('Images')}}
                </th>
              <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer" @click="sort('id')">
                  {{ __('Model')}}
                  <vue-feather :type="'chevron-up'" stroke-width="2" class="p-1 h-4 w-8 place-self-center inline-block" v-if="params.field==='id' &&  params.direction==='asc'"></vue-feather>
                  <vue-feather :type="'chevron-down'" stroke-width="2" class="p-1 h-4 w-8 place-self-center inline-block" v-if="params.field==='id' &&  params.direction==='desc'"></vue-feather>
              </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    <span>Barcode(الباركود)</span>
                    <span>{{ __('Category')}}</span>
                    <span>{{ __('Colors')}}</span>
                    <span>{{ __('Sizes')}}</span>
                    <span>{{ __('Country')}}</span>
                </th>

                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer" @click="sort('cost_price')">
                    {{ __('Cost Price')}}
                    <vue-feather :type="'chevron-up'" stroke-width="2" class="p-1 h-4 w-8 place-self-center inline-block" v-if="params.field==='cost_price' &&  params.direction==='asc'"></vue-feather>
                    <vue-feather :type="'chevron-down'" stroke-width="2" class="p-1 h-4 w-8 place-self-center inline-block" v-if="params.field==='cost_price' &&  params.direction==='desc'"></vue-feather>
                </th>

                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer" @click="sort('sale_price')">
                  {{ __('Sale Price')}}
                  <vue-feather :type="'chevron-up'" stroke-width="2" class="p-1 h-4 w-8 place-self-center inline-block" v-if="params.field==='sale_price' &&  params.direction==='asc'"></vue-feather>
                  <vue-feather :type="'chevron-down'" stroke-width="2" class="p-1 h-4 w-8 place-self-center inline-block" v-if="params.field==='sale_price' &&  params.direction==='desc'"></vue-feather>
                </th>

                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    {{ __('Retail Price')}}
                </th>

                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    {{ __('Price Before Discount')}}
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider">
                    {{ __('Merchant View')}}
                </th>
              <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider">
                  {{ __('Actions')}}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200 text-pcr">
            <tr v-for="item in productModels.data" :key="item.id" class="font-sans-latin text-sm font-medium">
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="grid grid-cols-3">
                        <img v-for="color in item.colors" class="rounded-lg w-30" :src="color.photo_url" alt="">
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div dir="rtl" class="text-sm font-medium">{{ item.name }}</div>
                        <div dir="rtl" class="text-xs">{{ item.details }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm text-center font-medium bg-fuchsia-200 p-1 rounded-lg">Barcode: {{ item.barcode }}</div>
                        <div class="text-sm text-center font-medium bg-teal-200 p-1 rounded-lg">Category: {{ item.category.name }}</div>
                        <div class="text-sm text-center font-medium bg-rose-200 p-1 rounded-lg" v-for="color in item.colors">{{ color.color_name }} | {{ color.stock }}</div>
                        <div class="text-sm text-center font-medium bg-teal-200 p-1 rounded-lg">Sizes: {{ item.raw_sizes }}</div>
                        <div class="text-sm text-center font-medium bg-teal-200 p-1 rounded-lg">Country: {{ item.country_name }}</div>
                    </div>
                </td>

                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">{{ currencyExchange(item.cost_price, rate) }} {{ currency.code }}<syp-equivalent :usd="item.cost_price" :display-currency="currency.display" /></td>

                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">{{ currencyExchange(item.sale_price, rate) }} {{ currency.code }}<syp-equivalent :usd="item.sale_price" :display-currency="currency.display" /></td>

                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">{{ currencyExchange(item.retail_price, rate) }} {{ currency.code }}<syp-equivalent :usd="item.retail_price" :display-currency="currency.display" /></td>

                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">{{ item.price_before_discount_raw ? currencyExchange(item.price_before_discount_raw, rate) : __('Not Provided') }} {{ item.price_before_discount_raw ? currency.code : '' }}<syp-equivalent v-if="item.price_before_discount_raw" :usd="item.price_before_discount_raw" :display-currency="currency.display" /></td>

                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <button 
                        @click="toggleVisibility(item)" 
                        :class="item.shown_for_merchant ? 'bg-emerald-500 hover:bg-emerald-600' : 'bg-rose-500 hover:bg-rose-600'"
                        class="px-4 py-2 rounded-lg text-white font-bold transition-colors duration-200"
                    >
                        {{ item.shown_for_merchant ? __('Shown') : __('Hidden') }}
                    </button>
                </td>
                <td class="w-full flex items-center justify-center space-x-3 p-6">
                    <inertia-link :href="route('products.edit', item.id)" class="p-2 pb-1 rounded-md text-white btn-ghost bg-teal-400 hover:bg-teal-600 hover:text-white">
                        <vue-feather :type="'edit-3'" stroke-width="2"></vue-feather>
                    </inertia-link>
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
import { createPopper } from '@popperjs/core';
import JetButton from '@/Jetstream/Button.vue'
import {debounce} from "lodash/function";
import axios from "axios";
import Currency from '@/Utils/Currency.js';

const components = { AppLayout, MeeTable, Pagination, JetButton }

export default {
    name: 'PortalProductsIndex',

    components,

    props: {
        products: Object,
        filters: Object,
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
            productModels: this.products,
            rate: this.rate,
            currencyExchange: Currency.getExchangeMethod(),
            page: 2,
        }
    },
    methods: {
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
            axios.delete(this.route('products.destroy', id)).then((response) => {
                if (response.data.success){
                    this.showSuccessMessage(response.data.msg)
                    let params = this.params
                    this.handleFilter(params)
                }else{
                    this.showErrorMessage('حدث خطأ ما')
                }
            }).catch(error => {
                this.showErrorMessage('حدث خطأ ما')
            })

        },
        toggleVisibility(item) {
            axios.post(this.route('products.toggleMerchantVisibility', item.id)).then(response => {
                if (response.data.success) {
                    item.shown_for_merchant = response.data.shown_for_merchant;
                    this.showSuccessMessage(response.data.msg);
                }
            }).catch(error => {
                this.showErrorMessage('حدث خطأ ما');
            });
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
            axios.get(this.route('products.index', this.params)).then(response => {
                this.productModels = {
                    ...response.data,
                    data: [...response.data.data]
                }
            });
        },

        // new code
        fetchData() {
            // Fetch data from the server and update this.items
            // Update loading state accordingly

            if(this.page <= this.productModels.last_page ) {

                // axios.get(this.productModels.next_page_url, { params: this.params }).then(response => {
                axios.get(app_url+'admin/products/products', { params: { params: this.params, page: this.page } }).then(response => {
                    this.productModels = {
                        ...response.data,
                        data: [...this.productModels.data, ...response.data.data]
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
                this.handleFilter(params)
                // this.$inertia.get(this.route('products.index'), this.params, { replace: true, preserveState: true});
            }),
            deep: true
        }
    },
    mounted() {

        this.fetchData();
        window.addEventListener('scroll', this.handleScroll);

        // window.addEventListener('scroll', debounce((e) => {
        //     let pixelsFromBottom = document.documentElement.offsetHeight - document.documentElement.scrollTop - window.innerHeight;

        //     if (pixelsFromBottom < 200){
        //         axios.get(this.productModels.next_page_url, { params: this.params }).then(response => {
        //             this.productModels = {
        //                 ...response.data,
        //                 data: [...this.productModels.data, ...response.data.data]
        //             }
        //         });
        //     }
        // }, 500))
    },
}
</script>


<style>
    th , td {
        text-align: center !important;
    }
</style>
