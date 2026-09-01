<template>
    <app-layout title="Create Order">
        <template #header>
            <div class="flex justify-between gap-4">
                <h2 class="mx-2 font-semibold text-xl text-gray-800">
                    {{ __('Create Fast Order')}}
                </h2>
                <div class="flex justify-around">
                    <inertia-link :href="route('orders.complex')" class="mx-2">
                        <h3 class="text-teal-500 group-hover:text-white text-2xl font-semibold underline">طلب زبون</h3>
                    </inertia-link>
                </div>
            </div>
        </template>

        <div>
            <div class="max-w-full px-20 mx-auto py-5 sm:px-6 lg:px-8">
                <form class="w-full" @submit.prevent v-on:submit.prevent>

                    <div class="grid grid-cols-3" dir="rtl" v-for="(product, index) in products">

                        <div class="">
                            <img v-if="product.image" :src="product.image" class="w-32 h-32">
                            <div v-else  class="w-12 h-12 m-4 rounded-full bg-teal-400"></div>
                            <p>{{ product.name }}</p>
                        </div>

                        <div class="px-4" dir="rtl">
                            <jet-label for="product" :value="__('Product')" />
                            <jet-input autofocus v-on:keyup.prevent="controlProduct" ref="product" v-model="product.barcode" class="mt-1 block w-full" type="text"></jet-input>
                            <jet-input-error :message="form.errors.product" class="mt-2" />
                            <input type="hidden" name="product_id" v-model="product.product_id">
                        </div>


                        <div class="px-4 flex justify-around">
                            <div class="">
                                <jet-label for="retail_price" :value="__('Retail Price')" dir="rtl" />
                                <jet-input ref="price" id="retail_price" type="number" class="mt-1 block w-full" v-model="product.price" autocomplete="retail_price" />
                                <jet-input-error :message="form.errors.retail_price" class="mt-2" />
                            </div>

                            <div>
                                <jet-label v-if="product.qty_limit" for="qty" :value="__('Allowed number')+product.qty_limit" dir="rtl" />
                                <jet-label v-else for="qty" :value="__('Quantity')" dir="rtl" />
                                <jet-input id="qty" type="number" @input="checkQty(product)" :max="product.qty_limit??1" class="mt-1 block w-full" v-model="product.qty" autocomplete="qty" />
                                <jet-input-error :message="form.errors.qty" class="mt-2" />
                            </div>
                            <div>
                                <div class="mt-8" @click="deleteProduct(index)">
                                    <vue-feather :type="'trash-2'" class="text-rose-500" stroke-width="2"></vue-feather>
                                </div>
                            </div>
                        </div>

                    </div>

                    <jet-section-border />
                    <div class="w-full px-6" dir="rtl">
                        <jet-label for="currency" :value="__('Currency Type')" />
                        <Multiselect v-model="form.currency" :options="currencies" :multiple="false" :close-on-select="true" placeholder="اختر نوع العملة التي تم بها الدفع" label="name" @tag="asyncFind" @search-change="asyncFind"
                             track-by="value" />
                        <jet-input-error :message="form.errors.currency" class="mt-2" />
                    </div>

                    <p class="text-2xl flex justify-between my-2 text-center"><span class="rounded-md px-3 py-1 bg-gray-800 text-white">{{ form.total_price_without_vat }}</span> <span class="mt-1 ml-4">{{ __('Total Price Without Vat')}}</span> </p>
                    <p class="text-2xl flex justify-between my-2 text-center"><span class="rounded-md px-3 py-1 bg-gray-800 text-white">{{ form.total_vat_value }}</span> <span class="mt-1 ml-4">{{ __('Vat Value')}}</span> </p>
                    <p class="text-2xl flex justify-between my-2 text-center"><span class="rounded-md px-3 py-1 bg-gray-800 text-white">{{ form.total_vat_ratio }}</span> <span class="mt-1 ml-4">{{ __('Vat Ratio')}}</span> </p>

                    <p class="text-2xl flex justify-between my-2 text-center"><span class="rounded-md px-3 py-1 bg-gray-800 text-white">{{ form.total_price_before_discount }}</span> <span class="mt-1 ml-4">{{ __('Total Price')}}</span> </p>

                    <p class="text-2xl flex justify-between my-2 text-center"><span class="rounded-md px-3 py-1 bg-rose-400 text-white">{{ form.discount }}</span> <span class="mt-1 ml-4">{{ __('Discount')}}</span> </p>
                    <p class="text-2xl flex justify-between my-2 text-center" v-if="form.enable_shipping"><span class="rounded-md px-3 py-1 bg-blue-400 text-white">{{ form.shipping_fee }}</span> <span class="mt-1 ml-4">رسوم الشحن</span> </p>
                    <p class="text-2xl flex justify-between my-2 text-center" v-if="form.enable_cod"><span class="rounded-md px-3 py-1 bg-blue-400 text-white">{{ form.cod_fee }}</span> <span class="mt-1 ml-4">رسوم COD</span> </p>
                    <p class="text-2xl flex justify-between my-2 text-center"><span class="rounded-md px-3 py-1 bg-gray-800 text-white">{{ form.total_price }}</span> <span class="mt-1 ml-4">{{ __('Total Price After Discount')}} + الرسوم</span> </p>
                    <p class="text-xl mt-2 flex justify-between my-2 text-center"><span class="rounded-md px-3 py-1 bg-black text-white">{{ form.total_qty }}</span> <span class="mt-1 ml-4">{{ __('Total Quantity')}}</span> </p>

                    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="w-full px-6">
                            <jet-label for="discount" :value="__('Discount')" dir="rtl" />
                            <jet-input ref="discount" id="discount" type="number" class="mt-1 block w-full" v-model="form.discount" autocomplete="discount" />
                            <jet-input-error :message="form.errors.discount" class="mt-2" />
                        </div>

                        <div class="w-full px-6" dir="rtl">
                            <label class="flex items-center mt-6">
                                <input type="checkbox" class="form-checkbox text-indigo-600" v-model="form.enable_shipping">
                                <span class="ml-2 mr-2">إضافة رسوم الشحن</span>
                            </label>
                            <div v-if="form.enable_shipping" class="mt-2">
                                <jet-input id="shipping_fee" type="number" class="block w-full" v-model="form.shipping_fee" />
                            </div>
                        </div>

                        <div class="w-full px-6" dir="rtl">
                            <label class="flex items-center mt-6">
                                <input type="checkbox" class="form-checkbox text-indigo-600" v-model="form.enable_cod">
                                <span class="ml-2 mr-2">إضافة رسوم الدفع عند الاستلام (COD)</span>
                            </label>
                            <div v-if="form.enable_cod" class="mt-2">
                                <jet-input id="cod_fee" type="number" class="block w-full" v-model="form.cod_fee" />
                            </div>
                        </div>
                    </div>
                    <div class="w-full px-6" dir="rtl">
                        <jet-label for="payment" :value="__('PAY Type')" />
                        <Multiselect v-model="form.payment" :options="[{ name: 'Pay Cash', value: '0' },{ name: 'Pay by Visa/Debit Card', value: '1' },{ name: 'Pay by Cheque', value: '2' }]" :multiple="false" :close-on-select="true" placeholder="اختر وسيلة دفع" label="name"
                             track-by="value" />
                        <jet-input-error :message="form.errors.payment" class="mt-2" />
                    </div>

                    <div v-if="form.total_qty > 0" class="flex justify-start space-x-12">
                        <jet-button :type="'button'" class="mb-4" @click="createOrderSimple">
                            {{ __('Save')}}
                        </jet-button>

                        <jet-button :type="'button'" class="mb-4 bg-fuchsia-800 hover:bg-fuchsia-700" @click="createOrderSimple(3, true)">
                            {{ __('Save & Print')}}
                        </jet-button>

                        <jet-button :type="'button'" class="mb-4 bg-fuchsia-800 hover:bg-fuchsia-700" @click="createOrderSimple_v2(3, true)">
                            {{ __('Save & Print V2')}}
                        </jet-button>

                    </div>
                    <button v-else type="button" class=" mb-4 inline-flex items-center px-4 py-2 bg-gray-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-300 active:bg-gray-500 focus:outline-none focus:border-gray-500 focus:ring focus:ring-gray-300 disabled:opacity-25 transition">
                        {{ __('Enter order products')}}
                    </button>
                </form>

                <div class="flex justify-between block bg-white text-black-500 hover:text-white hover:bg-gray-800 max-w-xs mx-auto rounded-lg p-6 m-8 ring-1 ring-black-400">
                    <div>
                        <div class="flex items-center space-x-3">
                            <h3 class="p-2 text-2xl font-bold">{{ __('Sales Fund')}}</h3>
                        </div>
                        <p class="p-2 text-4xl font-bold">
                            {{ admin.credit }}
                        </p>
                    </div>
                    <div>
                        <vue-feather :type="'credit-card'" stroke-width="2" class="h-12 w-24 p-1 place-self-center inline-block"></vue-feather>
                    </div>
                </div>

                <div class="flex justify-between block bg-white text-black-500 hover:text-white hover:bg-gray-800 max-w-xs mx-auto rounded-lg p-6 m-8 ring-1 ring-black-400">
                    <div>
                        <div class="flex items-center space-x-3">
                            <h3 class="p-2 text-2xl font-bold">{{ __('All Products Count')}}</h3>
                        </div>
                        <p class="p-2 text-4xl font-bold">
                            {{ all_products }}
                        </p>
                    </div>
                    <div>
                        <vue-feather :type="'truck'" stroke-width="2" class="h-12 w-24 p-1 place-self-center inline-block"></vue-feather>
                    </div>
                </div>

            </div>
        </div>
    </app-layout>

</template>

<script>
import {throttle} from "lodash";

import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import JetSectionBorder from '@/Jetstream/SectionBorder.vue'
import JetLabel from '@/Jetstream/Label.vue'
import Multiselect from "@suadelabs/vue3-multiselect"
import JetInput from '@/Jetstream/Input'
import JetInputError from '@/Jetstream/InputError'
import JetButton from '@/Jetstream/Button.vue'

import {MeeForm} from "@/Shared/Ui";
import Button from "@/Jetstream/Button";
import {computed} from "vue";
import {usePage} from "@inertiajs/inertia-vue3";
import Currency from '@/Utils/Currency.js';
import Receipt from '@/Utils/Receipt.js';

export default defineComponent({
    components: {
        Button,
        AppLayout,
        JetSectionBorder,
        JetLabel,
        JetInput,
        JetInputError,
        Multiselect,
        MeeForm,
        JetButton,
    },
    props: {
        all_products: Number,
        currencies: Object,
        rate: Number,
        tax_ratio: Number,
        enable_tax: String,
    },
    mounted() {
        this.$refs.product[0].$el.focus()
    },
    data() {
        return {
            form: this.$inertia.form({
                _method: 'POST',
                selected_products: null,
                type: 1,
                order_type: 'simple',
                payment: 0,
                rate: this.rate,
                currency: this.currencies[0],
                total_price: 0,
                total_qty: 0,
                discount: 0,
                total_price_before_discount: 0,

                total_price_without_vat: 0,
                total_vat_value: 0,
                total_vat_ratio: 0,

                shipping_fee: 0,
                cod_fee: 0,
                enable_shipping: false,
                enable_cod: false,

                action_status: 1
            }),
            products: [{barcode: '', qty: '', price: '', product_id: '', name: '', image: '', qty_limit: ''}],
            currencyExchange: Currency.getExchangeMethod(),
        }
    },
    methods: {
        asyncFind(query){

        },
        deleteProduct(index){
            if (this.products.length > 1 && this.products[index].barcode)
                this.products.splice(index, 1);
        },
        createOrderSimple(status = 1, print = false) {
            if (status > 1)
                this.form.action_status = status;
            this.form.selected_products = this.products.filter(value => value.product_id !== '');
            this.form.post(route('orders.store'), {
                errorBag: 'createOrderSimple',
                preserveScroll: true,
                onSuccess: (res) => {
                    if (res.props.flash.server_error)
                        this.showErrorMessage(res.props.flash.server_error)
                    else{
                        while(this.products.length > 0) {
                            this.products.pop();
                        }

                        this.products = [{barcode: '', qty: '', price: '', product_id: '', name: '', image: '', qty_limit: ''}];
                        this.$refs.product[0].$el.focus()
                        this.form.discount = 0

						const search = location.search.substring(1);
						const queryParams = JSON.parse('{"' + decodeURI(search).replace(/"/g, '\\"').replace(/&/g, '","').replace(/=/g,'":"') + '"}');
						if(print)
						{
							const id = queryParams['order_id'];

                            // window.location.href = '/invoice/print-v2/'+id; //Will take you to Google.


							axios.get(this.route('orders.print-info', id))
							.then(async (response) => {
								this.isLoading = false;

								response.data.pay_method = response.data.payment_label ?? " ";

								await Receipt.printOrder(response.data);

								this.showSuccessMessage('تمت عملية الطباعة بنجاح')
							})
							.catch(error => {

								this.showErrorMessage('فشل عملية الطباعة, حاول لاحقاً رجاءً')
							});

						}

                        this.showSuccessMessage('تمت إضافة الطلبية بنجاح');
                    }
                },
            });
        },
        createOrderSimple_v2(status = 1, print = false) {
            if (status > 1)
                this.form.action_status = status;
            this.form.selected_products = this.products.filter(value => value.product_id !== '');
            this.form.post(route('orders.store'), {
                errorBag: 'createOrderSimple',
                preserveScroll: true,
                onSuccess: (res) => {
                    if (res.props.flash.server_error)
                        this.showErrorMessage(res.props.flash.server_error)
                    else{
                        while(this.products.length > 0) {
                            this.products.pop();
                        }

                        this.products = [{barcode: '', qty: '', price: '', product_id: '', name: '', image: '', qty_limit: ''}];
                        this.$refs.product[0].$el.focus()
                        this.form.discount = 0

						const search = location.search.substring(1);
						const queryParams = JSON.parse('{"' + decodeURI(search).replace(/"/g, '\\"').replace(/&/g, '","').replace(/=/g,'":"') + '"}');
						if(print)
						{
							const id = queryParams['order_id'];

                            window.location.href = '/invoice/print-v2/'+id; //Will take you to Google.

                            /*
							axios.get(this.route('orders.print-info', id))
							.then(async (response) => {
								this.isLoading = false;

								response.data.pay_method = response.data.payment_label ?? " ";

								await Receipt.printOrder(response.data);

								this.showSuccessMessage('تمت عملية الطباعة بنجاح')
							})
							.catch(error => {

								this.showErrorMessage('فشل عملية الطباعة, حاول لاحقاً رجاءً')
							});
                            */

						}

                        this.showSuccessMessage('تمت إضافة الطلبية بنجاح');
                    }
                },
            });
        },
        checkQty(product){
          if (product.qty > product.qty_limit){
              this.showErrorMessage('انتبه لقد تجاوزت الحد الأقصى')
              product.qty = product.qty_limit
          }
        },
        controlProduct(event){
            let newBarcode = event.target.value
            let found = this.products.find(x => x.barcode === newBarcode)
            let element = event.target;
            const currentIndex = Array.from(element.form.elements).indexOf(element);

            if ((event.keyCode >= 48 && event.keyCode <= 57) || (event.keyCode >= 65 && event.keyCode <= 90) || (event.keyCode >= 95 && event.keyCode <= 105)){
                if (found.qty != 0 && found.qty != '' && found.qty != undefined){
                    if (found.qty < found.qty_limit){
                        found.qty ++

                        element.value = ''
                        this.products.pop()
                        this.products.push({ barcode: null, qty: '', price: '', product_id: '', name: '', image: '', qty_limit: '' })

                        element.form.elements.item(
                            currentIndex < element.form.elements.length - 1 ?
                                currentIndex + 1 :
                                0
                        ).focus();
                    }else{
                        element.value = ''
                        this.showErrorMessage('لقد وصلت الى الحد الأقصى')
                    }

                } else{
                    let formData = new FormData;
                    newBarcode = newBarcode.replace(/(\r\n|\n|\r)/gm, "");

                    formData.append('product', newBarcode)

                    axios.post(this.route('orders.match'), formData)
                        .then((response) => {
                            if (response.data){
                                let [lastItem] = this.products.slice(-1)
                                lastItem.price = Currency.exchange(response.data.retail_price, this.form.rate)
                                lastItem.qty = 1
                                lastItem.qty_limit = response.data.stock
                                lastItem.product_id = response.data.id
                                lastItem.image = response.data.product_color.photo_url
                                lastItem.name = response.data.product_color.product_name+" - "+response.data.size
                                this.products.push({ barcode: '', qty: '', price: '', product_id: '', name: '', image: '', qty_limit: '' })
                                element.form.elements.item(
                                    currentIndex < element.form.elements.length - 1 ?
                                        currentIndex + 1 :
                                        0
                                ).focus();
                            }
                        })
                }


            }else{
                if (event.keyCode === 8){
                    event.preventDefault();
                    if (this.products.length > 1)
                        this.products.splice(currentIndex-1, 1)
                }

            }
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
    },
    watch: {
        products: {
            handler: throttle(function () {
                var totalPrice = 0;
                var totalQty = 0;

                this.products.forEach(function (productItem) {

                    if (productItem.product_id){
                        let sum = Number(productItem['price']) * Number(productItem['qty']);
                        totalPrice += Number(sum);
                        totalQty  = Number(totalQty) + Number(productItem['qty'])
                    }
                });

                this.form.total_price_before_discount = totalPrice;

                let shipping = this.form.enable_shipping ? Number(this.form.shipping_fee) : 0;
                let cod = 0;
                
                if (this.form.enable_cod) {
                    cod = Number(this.form.cod_fee) > 0 ? (totalPrice * (Number(this.form.cod_fee) / 100)) : 0;
                } else {
                    this.form.cod_fee = 0;
                }

                this.form.total_price = Number(totalPrice) - Number(this.form.discount) + shipping + cod;
                this.form.total_qty = totalQty;

                var tax_ratio   = this.tax_ratio;
                var enable_tax  = this.enable_tax;

                if(enable_tax == 'yes') {

                    var order_total_price       = totalPrice;

                    var order_total_price_without_tax = totalPrice / (1 + (tax_ratio / 100) );
                    var order_total_tax_value         = order_total_price - order_total_price_without_tax;

                    // var order_total_tax_value         = (order_total_price * tax_ratio) / 100;
                    // var order_total_price_without_tax = order_total_price - order_total_tax_value;


                    this.form.total_price_without_vat = order_total_price_without_tax.toFixed(2);
                    this.form.total_vat_value         = order_total_tax_value.toFixed(2);
                    this.form.total_vat_ratio         = tax_ratio + '%';

                } else {
                    this.form.total_price_without_vat = totalPrice;
                    this.form.total_vat_value         = 0;
                    this.form.total_vat_ratio         = '0 %';
                }

            }),
            deep: true
        },
        'form.discount':{
            handler: throttle(function () {
                let shipping = this.form.enable_shipping ? Number(this.form.shipping_fee) : 0;
                let cod = this.form.enable_cod ? (Number(this.form.total_price_before_discount) * (Number(this.form.cod_fee) / 100)) : 0;
                this.form.total_price = Number(this.form.total_price_before_discount) - Number(this.form.discount) + shipping + cod;
            })
        },
        'form.enable_shipping':{
            handler: throttle(function () {
                let shipping = this.form.enable_shipping ? Number(this.form.shipping_fee) : 0;
                let cod = this.form.enable_cod ? (Number(this.form.total_price_before_discount) * (Number(this.form.cod_fee) / 100)) : 0;
                this.form.total_price = Number(this.form.total_price_before_discount) - Number(this.form.discount) + shipping + cod;
            })
        },
        'form.shipping_fee':{
            handler: throttle(function () {
                let shipping = this.form.enable_shipping ? Number(this.form.shipping_fee) : 0;
                let cod = this.form.enable_cod ? (Number(this.form.total_price_before_discount) * (Number(this.form.cod_fee) / 100)) : 0;
                this.form.total_price = Number(this.form.total_price_before_discount) - Number(this.form.discount) + shipping + cod;
            })
        },
        'form.enable_cod':{
            handler: throttle(function () {
                if (this.form.enable_cod) {
                    this.form.cod_fee = 10; // Default 10% COD fee
                } else {
                    this.form.cod_fee = 0;
                }
                let shipping = this.form.enable_shipping ? Number(this.form.shipping_fee) : 0;
                let cod = this.form.enable_cod ? (Number(this.form.total_price_before_discount) * (Number(this.form.cod_fee) / 100)) : 0;
                this.form.total_price = Number(this.form.total_price_before_discount) - Number(this.form.discount) + shipping + cod;
            })
        },
        'form.cod_fee':{
            handler: throttle(function () {
                let shipping = this.form.enable_shipping ? Number(this.form.shipping_fee) : 0;
                let cod = this.form.enable_cod ? (Number(this.form.total_price_before_discount) * (Number(this.form.cod_fee) / 100)) : 0;
                this.form.total_price = Number(this.form.total_price_before_discount) - Number(this.form.discount) + shipping + cod;
            })
        },
        'form.currency':{
            handler: throttle(function () {
                if(this.form.currency.rate)
                    this.form.rate = this.form.currency.rate

            })
        },
    },
    setup() {
        const admin = computed(() => usePage().props.value.auth.user)
        return { admin }
    },
})
</script>
<style scoped>
.multiselect__input{
    padding: 0 !important;
}
</style>
