<template>
  <app-layout title="Products Management">
      <div class="mt-8 flex justify-around space-x-4">

          <h3 class="text-2xl mt-4 font-bold capitalize text-primary"><span class="bg-emerald-500 px-2 py-0 text-6xl rounded-md text-white">{{ currencyExchange(debitsSum, rate, true) }} </span> ديون للزبائن</h3>
          <div class="max-w-xs text-right">
              <label>رقم الهاتف</label>
              <input dir="rtl" type="search" v-model="params.searchPhone" placeholder="بحث..." class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
          </div>
          <div class="max-w-xs text-right">
              <label>الزبون</label>
              <input dir="rtl" type="search" v-model="params.search" placeholder="بحث..." class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
          </div>
          <div class="border-gray-300 shadow-sm border-2 rounded-md px-2 h-fit">
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
      </div>

      <MeeTable :tableTitle="'All Products'">
        <div v-if="debitClients.data.length === 0" class="my-40 flex items-center justify-center text-xl font-bold text-error">
            <span class="text-center">لا يوجد ديون للزبائن!</span>
        </div>
        <table v-else class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr class="shadow-2xl py-4">
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    المحل/المستودع
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    الزبون
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    المبلغ
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    الحساب
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    تسديد
                </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200 text-pcr">
            <tr v-for="item in debitClients.data" :key="item.id" class="font-sans-latin text-sm font-medium">
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ item.creditor.name }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ item.debtor.name }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ Math.round(item.amount* rate).toFixed(2) }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium text-white bg-emerald-500 p-2 rounded-md my-4 text-center">{{ Math.round(item.debtor.wallet.credit * rate).toFixed(2) }} الرصيد</div>
                        <div class="text-sm font-medium text-white bg-rose-500 p-2 rounded-md my-4 text-center">{{ Math.round(item.debtor.wallet.debit * rate).toFixed(2) }} الدين</div>
                    </div>
                </td>
                <td class="flex flex-wrap mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">

                    <div class="m-4">
                        <button @click="addPayment(item.id)" class="w-full flex justify-between rounded-md text-white bg-teal-400 hover:bg-teal-600 hover:text-white">
                            <p class="m-1 p-1">تسديد</p>
                            <vue-feather class="m-2 mt-3" :type="'credit-card'" stroke-width="2"></vue-feather>
                        </button>
                    </div>

                    <div class="m-4">
                        <button @click="addWithdraw(item.id)" class="w-full flex justify-between rounded-md text-white bg-fuchsia-600 hover:bg-fuchsia-800 hover:text-white">
                            <p class="m-1 p-1">سحب</p>
                            <vue-feather class="m-2 mt-3" :type="'credit-card'" stroke-width="2"></vue-feather>
                        </button>
                    </div>

                    <div class="m-4">
                        <inertia-link :href="route('clientDebit.log', item.id)" class="w-full flex justify-between rounded-md text-white bg-indigo-600 hover:bg-indigo-800 hover:text-white">
                            <p class="m-1 p-1">التفاصيل</p>
                            <vue-feather class="m-2 mt-3" :type="'align-center'" stroke-width="2"></vue-feather>
                        </inertia-link>
                    </div>

                    <div class="m-4">
                        <button @click="refundProduct(item)" class="w-full flex justify-between rounded-md text-white bg-rose-400 hover:bg-red-600 hover:text-white">
                            <p class="m-1 p-1">مرتجع</p>
                            <vue-feather class="m-2 mt-3" :type="'credit-card'" stroke-width="2"></vue-feather>
                        </button>
                    </div>

                    <div
                        class="m-4"
                        v-if="false"
                    >
                        <button @click="closeAccount(item.id)" class="w-full flex justify-between rounded-md text-white bg-red-600 hover:bg-red-800 hover:text-white">
                            <p class="m-1 p-1">تسكير الحساب {{ item.amount }}</p>
                            <vue-feather class="m-2 mt-3" :type="'x'" stroke-width="2"></vue-feather>
                        </button>
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
                              <h3 class="text-teal-600 text-3xl font-bold">مرتجع من الزبون {{item.debtor.name}}</h3>
                          </div>

                          <div class="modal-body border-b-4 border-state-500 pb-6" style="height: 450px;overflow: auto;">
                                <div class="px-4" dir="rtl">
                                    <jet-label for="sproduct" :value="__('Product')" />
                                    <jet-input autofocus v-on:keyup.prevent="controlProduct" ref="sproduct" v-model="sproduct" class="mt-1 block w-full" type="text"></jet-input>
                                </div>
                                <div class="flex justify-around items-stretch"  v-for="(rproduct, index) in rproducts" style="padding: 20px;border: 1px solid;">
                                        <div class="px-1 w-1/4">
                                            <jet-label for="stock" value="العدد" dir="rtl" />
                                            <jet-label v-if="rproduct" for="qty" :value="' العدد المسموح '+rproduct.qty" dir="rtl" />
                                            <jet-input :id="rproduct+'stock'" type="number" class="mt-1 block w-full" v-model="rproduct.qty" autocomplete="stock" />
                                        </div>

                                        <div class="px-1 w-3/4" dir="rtl">
                                            <jet-label for="user" :value="'المنتج'+rproduct.product_name" />
                                            <!-- <Multiselect v-model="product" :options="products" :multiple="false" :close-on-select="true" placeholder="اختر بضاعة من قائمة البضائع" label="product_name" track-by="id" /> -->
                                        </div>
                                        <div>
                                            <div class="mt-3" @click="deleteProduct(index)">
                                                <vue-feather :type="'trash-2'" class="text-rose-500" stroke-width="2"></vue-feather>
                                            </div>
                                        </div>
                                </div>
                                <div class="px-4" dir="rtl">
                                    <jet-label :value="count_products" />
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
                                  حفظ
                              </button>
                              <button class="modal-default-button px-2 py-1 hover:bg-rose-600 bg-rose-400 text-white rounded-lg" @click="closeModal()">
                                  إلغاء
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
import JetButton from '@/Jetstream/Button.vue'
import {computed} from "vue";
import {usePage} from "@inertiajs/inertia-vue3";
import JetDropdown from '@/Jetstream/Dropdown.vue'
import JetDropdownLink from '@/Jetstream/DropdownLink.vue'
import JetLabel from "@/Jetstream/Label";
import Multiselect from "@suadelabs/vue3-multiselect";
import JetInput from "@/Jetstream/Input";
import JetInputError from "@/Jetstream/InputError";
import {debounce} from "lodash/function";
import Currency from '@/Utils/Currency.js'

const components = { AppLayout, MeeTable, Pagination, JetButton, JetDropdown, JetDropdownLink, JetLabel, Multiselect, JetInput, JetInputError }

export default {
    name: 'ClientDebitIndex',

    components,

    props: {
        clients: Object,
        filters: Object,
        shops: Array,
        rate: Number,
        sum: String
    },
    data() {
        return {
            params: {
                search: this.filters.search,
                searchPhone: this.filters.searchPhone,
                field: this.filters.field,
                shop: this.filters.shop,
                direction: this.direction
            },
            debitClients: this.clients,
            isLoading: false,
            showModal: false,
            resultError: false,
            resultLoading: false,
            item: null,
            products: null,
            rproducts: [],
            stock: null,
            stock_limit: null,
            product: null,
            sproduct: null,
            debitsSum: this.sum,
            shop_id:null,
            user_id:null,
            count_products:0,
            currencyExchange: Currency.getExchangeMethod(),
            page: 2,

        }
    },
    methods: {
        controlProduct(event){
            let model = event.target.value
            let element = event.target;
            let uid = this.user_id
            let mid = this.shop_id
            let data = {
              'user_id': uid,
              'shop_id': mid,
              'group':1,
              'barcode': model
            };

            if ((event.keyCode >= 48 && event.keyCode <= 57) || (event.keyCode >= 65 && event.keyCode <= 90) || (event.keyCode >= 95 && event.keyCode <= 105)){
                if(model.length > 4){
                    axios.get(this.route('orders.client', data)).then(response => {
                        for (let index = 0; index < response.data.length; index++) {
                            let found = this.rproducts.find(x => x.product.barcode === response.data[index].product.barcode)
                            if(found){
                                if (found.stock != 0 && found.stock != '' && found.stock != undefined){

                                }
                            }else{
                                let sim = 0;
                                if(response.data[index].qty > 0)
                                    this.rproducts.push(response.data[index])
                                    this.rproducts.forEach(function(value,index) {
                                        sim += value.qty
                                    })
                                    this.count_products = sim

                            }
                        }
                    });
                }
            }
        },
        deleteProduct(index){

                this.rproducts.splice(index, 1);

            let sim = 0
            this.rproducts.forEach(function(value,index) {
                sim += value.qty
            })
            this.count_products = sim

        },
        sort(field){
            this.params.field = field;
            this.params.direction = this.params.direction === 'asc' ? 'desc' : 'asc';
        },
        confirmResult(){
            // if (!this.item || !this.stock  || !this.product ){
            if (!this.rproducts ){
                this.resultError = true;
            }else{
                this.resultLoading = true;
                let formData = new FormData;
                // formData.append('qty', this.stock)
                // formData.append('order_item_id', this.product.id)
                let prods = []
                for (let index = 0; index < this.rproducts.length; index++) {
                    prods.push({'qty':this.rproducts[index].qty,'order_item_id':this.rproducts[index].id})
                }
                formData.append('user_products', JSON.stringify(prods))
                formData.append('client_id', this.item.debtor_id)
                formData.append('client_debit_id', this.item.id)

                axios.post(this.route('merchantRefunds.client'), formData,{
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    },
                    preserveScroll: true
                }).then((result) => {
                    if (result.data.success){
                        this.showSuccessMessage(result.data.msg)
                        this.$inertia.get(route('clientDebit.clients'));
                    }else{
                        this.showErrorMessage(result.data.error)
                    }
                    this.resultLoading = false;
                    this.showModal = false;
                    this.item = null;
                    this.resultError = false;
                    this.stock = null;
                    this.merchant = null;
                    this.user = null;
                    this.product = null;
                    this.products = null;
                } );
            }
        },
        async refundProduct(item) {
            this.user_id = item.debtor_id
            this.shop_id = item.creditor_id
            let data = {
              'user_id': item.debtor_id,
              'shop_id': item.creditor_id
            };
            this.item = item;
            this.showModal = true;
            // axios.get(this.route('orders.client', data)).then(response => {
            //     this.products = response.data
            //     this.item = item;
            //     this.showModal = true;
            // });
        },
        closeModal(){
            this.showModal = false;
            this.products = null;
            this.item = null;
            this.product = null;
            this.stock = null;
        },
        async addPayment(id) {
            await this.$swal({
                title: 'إضافة دفعة لـ الزبون',
                text: "رجاءً أدخل قيمة الدفعة",
                input: 'text',
                inputPlaceholder: 'قيمة الدفعة',
                showCancelButton: true,
                confirmButtonColor: '#014758',
                cancelButtonColor: '#d33',
                confirmButtonText: 'أضف الدفعة',
                cancelButtonText: 'إلغاء',
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
                    axios.post(route('clientDebit.payment'), {
                        debit: id,
                        amount: result.value
                    }).then((response) => {
                        if (response.data.success){
                            this.showSuccessMessage('تم إضافة قيمة الدفعة')
                            this.$inertia.get(this.route('clientDebit.clients'))
                        } else
                            this.showErrorMessage(response.data.error)
                    }).catch((error) => {
                        this.showErrorMessage(error)
                    })
                }
            })
        },
        async addWithdraw(id) {
            await this.$swal({
                title: 'سحب كاش لـ الزبون',
                text: "رجاءً أدخل قيمة الدفعة",
                input: 'text',
                inputPlaceholder: 'قيمة الدفعة',
                showCancelButton: true,
                confirmButtonColor: '#014758',
                cancelButtonColor: '#d33',
                confirmButtonText: 'أضف الدفعة',
                cancelButtonText: 'إلغاء',
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
                    axios.post(route('clientDebit.withdraw'), {
                        debit: id,
                        amount: result.value
                    }).then((response) => {
                        if (response.data.success){
                            this.showSuccessMessage('تم إضافة قيمة الدفعة')
                            this.$inertia.get(this.route('clientDebit.clients'))
                        } else
                            this.showErrorMessage(response.data.error)
                    }).catch((error) => {
                        this.showErrorMessage(error)
                    })
                }
            })
        },
        async closeAccount(id) {
            await this.$swal({
                title: 'إغلاق حساب الزبون',
                text: "هل أنت متأكد أنك تريد إغلاق حساب الزبون؟ سيتم مسح السجل",
                showCancelButton: true,
                confirmButtonColor: '#014758',
                cancelButtonColor: '#d33',
                confirmButtonText: 'إغلاق الحساب',
                cancelButtonText: 'إلغاء',
                width: 400,
                padding: '1em',
                color: '#014758',
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.post(route('clientDebit.close'), {
                        debit: id,
                    }).then((response) => {
                        if (response.data.success){
                            this.showSuccessMessage('تم تسكير حساب الزبون')
                            this.$inertia.get(this.route('clientDebit.clients'))
                        } else
                            this.showErrorMessage(response.data.error)
                    }).catch((error) => {
                        this.showErrorMessage(error)
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
        getShopName(id){
            const shopObject = this.shops.find((s) => s.id === id)
            return shopObject.name;
        },
        filter(filter, value){
            this.params[filter] = value;
        },
        payDebit(id) {
            this.$swal({
                title: 'هل أنت متأكد؟',
                text: "لن تتمكن من التراجع عن هذه الخطوة",
                showCancelButton: true,
                confirmButtonColor: '#014758',
                cancelButtonColor: '#d33',
                confirmButtonText: 'نعم تسدد الدفعة',
                width: 400,
                padding: '1em',
                color: '#014758',
            }).then((result) => {
                if (result.isConfirmed) {
                    this.$swal.fire({
                            html: '<p class="text-white pt-5 font-extrabold">تم تسديد الدفعة بنجاح</p>',
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
                    this.$inertia.get(route('clients.pay', id))
                }
            })

        },
        getAllDebits(){
            let debit = 0;
            this.debitClients.data.forEach((item) => {
                debit += Number(item.amount)
            });
            return debit;
        },

        // new code
        fetchData() {
            // Fetch data from the server and update this.items
            // Update loading state accordingly


            if(this.page <= this.debitClients.last_page ) {

                // axios.get(this.route('clientDebit.clients', { params: this.params,page: this.page++ })).then(response => {
                //     this.debitClients = {
                //         ...response.data.debits,
                //         data: [...this.debitClients.data, ...response.data.debits.data]
                //     }
                // });

                //axios.get(this.debitClients.next_page_url, { params: this.params }).then(response => {
                axios.get(app_url+'admin/clientDebit/clients/all', { params: { params: this.params, page: this.page } }).then(response => {
                    this.debitClients = {
                        ...response.data.debits,
                        data: [...this.debitClients.data, ...response.data.debits.data]
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
                }, 150);
                axios.get(this.route('clientDebit.clients', this.params)).then(response => {
                    this.debitsSum = response.data.sum
                    this.debitClients = {
                        ...response.data.debits,
                        data: [...response.data.debits.data]
                    }
                });
            }),
            deep: true
        },
        stock: {
            handler: throttle(function () {
               if (this.product){
                   if (this.stock > this.product.qty){
                       this.stock = this.product.qty;

                       this.showErrorMessage('لا يمكنك تجاوز العدد الموجود بالمتجر')
                   }
               }
            }),
            deep: true
        }
    },
    setup() {
        const admin = computed(() => usePage().props.value.auth.user)
        return { admin }
    },
    mounted() {

        this.fetchData();
        window.addEventListener('scroll', this.handleScroll);

        // window.addEventListener('scroll', debounce((e) => {
        //     let pixelsFromBottom = document.documentElement.offsetHeight - document.documentElement.scrollTop - window.innerHeight;

        //     if (pixelsFromBottom < 200){
        //         axios.get(this.debitClients.next_page_url, { params: this.params }).then(response => {
        //             this.debitClients = {
        //                 ...response.data.debits,
        //                 data: [...this.debitClients.data, ...response.data.debits.data]
        //             }
        //         });
        //     }
        // }, 100))
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
