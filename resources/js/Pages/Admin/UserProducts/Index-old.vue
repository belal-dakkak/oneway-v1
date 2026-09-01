<template>
  <app-layout title="Products Management">
      <div class="flex justify-around mt-8">
          <div class="flex justify-between" v-if="admin.role === 1 || admin.role === 2">
              <inertia-link :href="route('userProducts.create')">
                  <jet-button class="px-16 mt-8 bg-pcr float-right">
                      {{ __('Send Products')}}
                  </jet-button>
              </inertia-link>
          </div>
          <div class="mt-8 flex justify-between space-x-4">
              <h3 @click="searchForModel()" class="text-xs font-bold capitalize hover:text-blue-400 hover:cursor-pointer text-blue-600 p-3">{{ __('Model')}}</h3>
              <div class="max-w-xs">
                  <input dir="rtl"  type="search" v-model="params.search" :placeholder="__('Save')" class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
              </div>
              <h3 class="text-2xl font-bold capitalize text-primary">{{ __('Products in shops & clients')}}</h3>
          </div>
          <h3 class="text-2xl font-bold capitalize text-primary"><span class="bg-emerald-500 px-2 text-4xl rounded-md text-white">{{ stock_products }}</span> {{ __('All Products Count')}} </h3>

      </div>
    <MeeTable :tableTitle="'All Products'">
        <div v-if="userProducts.data.length === 0" class="my-40 flex items-center justify-center text-xl font-bold text-error">
            <span class="text-center">لا يوجد بضاعة حالياً!</span>
        </div>
        <table v-else class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr class="shadow-2xl py-4">
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer" @click="sort('id')">
                    {{ __('Product')}}
                    <vue-feather :type="'chevron-up'" stroke-width="2" class="p-1 h-4 w-8 place-self-center inline-block" v-if="params.field==='id' &&  params.direction==='asc'"></vue-feather>
                    <vue-feather :type="'chevron-down'" stroke-width="2" class="p-1 h-4 w-8 place-self-center inline-block" v-if="params.field==='id' &&  params.direction==='desc'"></vue-feather>
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer" @click="sort('user_id')">
                    {{ __('Shop/Client Name')}}
                    <vue-feather :type="'chevron-up'" stroke-width="2" class="p-1 h-4 w-8 place-self-center inline-block" v-if="params.field==='id' &&  params.direction==='asc'"></vue-feather>
                    <vue-feather :type="'chevron-down'" stroke-width="2" class="p-1 h-4 w-8 place-self-center inline-block" v-if="params.field==='id' &&  params.direction==='desc'"></vue-feather>
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    Barcode (الباركود)
                    Stock (الكمية)
                    (اللون) Color
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    {{ __('Wholesale Price')}}
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    {{ __('Retail Price')}}
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    {{ __('Date')}}
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    {{ __('Actions')}}
                </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200 text-pcr">
            <tr v-for="item in userProducts.data" :key="item.id" class="font-sans-latin text-sm font-medium">
                <td class="mx-auto max-w-sm p-2 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="flex items-center flex-wrap">
                        <div class="flex-shrink-0">
                            <img class="w-24 h-24 rounded-lg" :src="item.product_color.photo_url" alt="">
                        </div>
                        <div class="flex-shrink-0 h-5 w-5 ml-2 rounded-md" :style="bgColor(item.product_color.color_code)">
                        </div>
                        <div class="ml-1">
                            <div class="text-sm font-medium">{{ item.product_color.product_name }}</div>
                        </div>
                    </div>
                </td>
                <td class="text-right mx-auto max-w-sm p-2 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ item.user.name }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm text-center font-medium bg-fuchsia-200 p-1 rounded-lg">Barcode: {{ item.barcode }}</div>

                        <div class="text-sm text-center font-medium bg-teal-200 p-1 rounded-lg">Stock: {{ item.stock }}</div>
                        <div class="text-sm text-center font-medium bg-teal-200 p-1 rounded-lg">Size: {{ item.size }}</div>

                        <div class="text-sm text-center font-medium bg-rose-200 p-1 rounded-lg">Color: {{ item.product_color.color_name }} {{ item.product_color.color_code }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ item.wholesale_price }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ item.retail_price }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ item.date }}</div>
                    </div>
                </td>
                <td class="w-full flex flex-wrap space-x-2 p-6" dir="ltr">
                    <inertia-link :href="route('userProducts.show', item.id)" class="p-2 pb-1 rounded-md text-white btn-ghost bg-teal-400 hover:bg-teal-600 hover:text-white">
                        <vue-feather :type="'eye'" stroke-width="2"></vue-feather>
                    </inertia-link>
                    <button
                        v-if="false"
                        class="p-2 pb-1 rounded-md text-white btn-ghost bg-rose-400 hover:bg-red-600 hover:text-white"
                        @click="deleteItem(item.id)">
                        <vue-feather :type="'trash-2'" stroke-width="2"></vue-feather>
                    </button>
                    <button class="p-2 pb-1 rounded-md text-white btn-ghost bg-blue-400 hover:bg-blue-600 hover:text-white" @click="sendToShop(item)" v-if="admin.role === 2">
                        <vue-feather :type="'shopping-cart'" stroke-width="2"></vue-feather>
                    </button>
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
                          <span class="absolute top-1 right-1 mt-1 mr-2 cursor-pointer" @click="closeModal()">X</span>

                          <div class="modal-header flex justify-center">
                              <h3 class="text-teal-600 text-3xl font-bold">{{ __('Send')}} {{this.item.product_color.product_name}} {{ __('To')}}</h3>
                          </div>

                          <div class="modal-body border-b-4 border-state-500 pb-6">
                              <div class="flex justify-around items-stretch py-6">
                                  <div class="px-1" dir="rtl">
                                      <jet-label for="user" :value="__('Shop')" />
                                      <Multiselect v-model="user" :options="users" :multiple="false" :close-on-select="true" placeholder="اختر محل من قائمة المحلات" label="name"
                                                   track-by="product_color_id" />
                                  </div>
                                  <div class="px-1">
                                      <jet-label for="wholesale_price" :value="__('Wholesale Price')" dir="rtl" />
                                      <jet-input id="wholesale_price" type="number" class="mt-1 block w-full" v-model="wholesale_price" autocomplete="wholesale_price" />
                                  </div>

                                  <div class="px-1">
                                      <jet-label for="retail_price" :value="__('Retail Price')" dir="rtl" />
                                      <jet-input id="retail_price" type="number" class="mt-1 block w-full" v-model="retail_price" autocomplete="retail_price" />
                                  </div>

                                  <div class="px-1">
                                      <jet-label for="stock" :value="__('Stock')" dir="rtl" />
                                      <jet-input id="stock" type="number" class="mt-1 block w-full" v-model="stock" autocomplete="stock" />
                                  </div>

                                  <div class="px-1" dir="rtl">
                                      <jet-label for="merchant" :value="__('Merchant')" />
                                      <Multiselect v-model="merchant" :options="merchants" :multiple="false" :close-on-select="true" placeholder="اختر تاجر من قائمة التجار" label="name"
                                                   track-by="id" />
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
import { computed } from 'vue'
import { usePage } from '@inertiajs/inertia-vue3'
import JetLabel from "@/Jetstream/Label";
import Multiselect from "@suadelabs/vue3-multiselect";
import JetInput from "@/Jetstream/Input";
import JetInputError from "@/Jetstream/InputError";
import {debounce} from "lodash/function";

const components = { AppLayout, MeeTable, Pagination, JetButton,  JetLabel, Multiselect, JetInput, JetInputError }

export default {
    name: 'PortalProductsIndex',

    components,

    props: {
        products: Object,
        filters: Object,
        users: Object,
        merchants: Object,
        stock_products: Number
    },
    setup() {
        const admin = computed(() => usePage().props.value.auth.user)
        return { admin }
    },
    data() {
        return {
            params: {
                search: this.filters.search,
                field: this.filters.field,
                direction: this.direction
            },
            userProducts: this.products,
            showModal: false,
            itemId: null,
            item: null,
            resultError: false,
            resultLoading: false,
            user: '',
            size: '',
            merchant: '',
            retail_price: '',
            wholesale_price: '',
            stock: '',
            barcode: '',
        }
    },
    methods: {
        confirmResult(){
            if (!this.user || !this.stock  || !this.retail_price  || !this.wholesale_price ){
                this.resultError = true;
            }else{
                this.resultLoading = true;
                let formData = new FormData;
                formData.append('user', this.user.id)
                formData.append('retail_price', this.retail_price)
                formData.append('wholesale_price', this.wholesale_price)
                if (this.merchant)
                    formData.append('merchant', this.merchant.id)
                formData.append('stock', this.stock)
                formData.append('size', this.size)
                formData.append('barcode', this.barcode)
                formData.append('product', this.itemId)
                formData.append('travel', '1')
                formData.append('is_warehouse', '1')

                axios.post(this.route('userProducts.store'), formData,{
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    },
                    preserveScroll: true
                }).then((result) => {
                    if (result.status === 200){
                        this.showSuccessMessage(result.data.msg)
                        this.$inertia.get(route('userProducts.index'))
                    }else{
                        this.showErrorMessage('حدث خطأ ما')
                    }
                    this.resultLoading = false;
                    this.showModal = false;
                    this.itemId = null;
                    this.item = null;
                    this.resultError = false;
                    this.stock = null;
                    this.size = null;
                    this.barcode = null;
                    this.merchant = null;
                    this.user = null;
                    this.retail_price = null;
                    this.wholesale_price = null;
                } );
            }
        },
        closeModal(){
            this.resultLoading = false;
            this.showModal = false;
            this.itemId = null;
            this.item = null;
            this.resultError = false;
            this.barcode = null;
            this.size = null;
            this.stock = null;
            this.user = null;
            this.merchant = null;
            this.retail_price = null;
            this.wholesale_price = null;
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
        async sendToShop(item) {
            this.showModal = true;
            this.itemId = item.product_color_id;
            this.item = item;
            this.stock = item.stock;
            this.size = item.size;
            this.barcode = item.barcode;
            this.wholesale_price = item.wholesale_price;
            this.retail_price = item.retail_price;
        },
        searchForModel(){
            if (this.params.search){
                let myString = this.params.search.replace((/\D\d*/g),'');
                this.params.search = myString;
            }
        },
        sort(field){
            this.params.field = field;
            this.params.direction = this.params.direction === 'asc' ? 'desc' : 'asc';
        },
        deleteItem(id) {
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
                        html: '<p class="text-white pt-5 font-extrabold">تم حذف اللون بنجاح</p>',
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
                    this.$inertia.delete(route('userProducts.destroy', id))
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
                axios.get(this.route('userProducts.index', this.params)).then(response => {
                    this.userProducts = {
                        ...response.data,
                        data: [...response.data.data]
                    }
                });
                // this.$inertia.get(this.route('userProducts.index'), this.params, { replace: true, preserveState: true});
            }),
            deep: true
        }
    },
    mounted() {
        window.addEventListener('scroll', debounce((e) => {
            let pixelsFromBottom = document.documentElement.offsetHeight - document.documentElement.scrollTop - window.innerHeight;

            if (pixelsFromBottom < 200){
                axios.get(this.userProducts.next_page_url, { params: this.params }).then(response => {
                    this.userProducts = {
                        ...response.data,
                        data: [...this.userProducts.data, ...response.data.data]
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
