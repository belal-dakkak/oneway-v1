<template>
    <app-layout title="Create Order">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                مرتجعات
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <form class="w-full" @submit.prevent v-on:submit.prevent>

                    <jet-button :type="'button'" class="mb-4" @click="createRefundSimple" v-if="form.total_qty > 0">
                        حفظ
                    </jet-button>

                    <button v-else type="button" class=" mb-4 inline-flex items-center px-4 py-2 bg-gray-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-300 active:bg-gray-500 focus:outline-none focus:border-gray-500 focus:ring focus:ring-gray-300 disabled:opacity-25 transition">
                        أدخل البضاعة
                    </button>

                    <div class="grid grid-cols-3" dir="rtl" v-for="(product, index) in products">

                        <div class="">
                            <img v-if="product.image" :src="product.image" class="w-32 h-32">
                            <div v-else  class="w-12 h-12 m-4 rounded-full bg-teal-400"></div>
                            <p>{{ product.name }}</p>
                        </div>

                        <div class="px-4" dir="rtl">
                            <jet-label for="product" value="المنتج" />
                            <jet-input autofocus v-on:keyup.prevent="controlProduct" ref="product" v-model="product.barcode" class="mt-1 block w-full" type="text"></jet-input>
                            <jet-input-error :message="form.errors.product" class="mt-2" />
                            <input type="hidden" name="product_id" v-model="product.product_id">
                        </div>


                        <div class="px-4 flex justify-around">
                            <div class="">
                                <jet-label for="retail_price" value="سعر المبيع" dir="rtl" />
                                <jet-input ref="price" id="retail_price" type="number" min="0" step="0.01" class="mt-1 block w-full" v-model="product.price" autocomplete="retail_price" />
                                <syp-equivalent :usd="product.price" />
                                <jet-input-error :message="form.errors.retail_price" class="mt-2" />
                            </div>

                            <div>
                                <jet-label for="qty" value="العدد" dir="rtl" />
                                <jet-input id="qty" type="number" class="mt-1 block w-full" v-model="product.qty" autocomplete="qty" />
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

                    <p class="text-2xl flex justify-between my-2 text-center"><span class="rounded-md px-3 py-1 bg-gray-800 text-white">{{ form.total_price }}</span> <span class="mt-1 ml-4">السعر الكلي </span> </p>
                    <p class="text-xl mt-2 flex justify-between my-2 text-center"><span class="rounded-md px-3 py-1 bg-black text-white">{{ form.total_qty }}</span> <span class="mt-1 ml-4">العدد الكلي</span> </p>

                </form>
            </div>
        </div>
    </app-layout>

</template>

<script>

import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import JetSectionBorder from '@/Jetstream/SectionBorder.vue'
import JetLabel from '@/Jetstream/Label.vue'
import Multiselect from "@suadelabs/vue3-multiselect"
import JetInput from '@/Jetstream/Input'
import JetInputError from '@/Jetstream/InputError'
import JetButton from '@/Jetstream/Button.vue'

import {MeeForm} from "@/Shared/Ui";
import {computed} from "vue";
import {usePage} from "@inertiajs/inertia-vue3";
import {throttle} from "lodash";

export default defineComponent({
    components: {
        AppLayout,
        JetSectionBorder,
        JetLabel,
        JetInput,
        JetInputError,
        Multiselect,
        MeeForm,
        JetButton
    },
    data() {
        return {
            form: this.$inertia.form({
                _method: 'POST',
                selected_products: null,
                type: 1,
                total_price: 0,
                total_qty: 0,
            }),
            products: [{barcode: '', qty: '', price: '', product_id: '', name: '', image: ''}]
        }
    },
    mounted() {
        this.$refs.product[0].$el.focus()
    },
    methods: {
        deleteProduct(index){
            if (this.products.length > 1)
                this.products.splice(index, 1);
        },
        createRefundSimple() {
            this.form.selected_products = this.products.filter(value => value.product_id !== '');

            this.form.post(route('refunds.store'), {
                errorBag: 'createRefundSimple',
                preserveScroll: true
            });

            while(this.products.length > 0) {
                this.products.pop();
            }

            this.products = [{barcode: '', qty: '', price: '', product_id: '', name: '', image: ''}];

            this.$refs.product[0].$el.focus()
            this.form.discount = 0
            this.showSuccessMessage('تمت إضافة المرتجع بنجاح');
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
        controlProduct(event){
            let newBarcode = event.target.value
            let found = this.products.find(x => x.barcode === newBarcode)
            let element = event.target;
            const currentIndex = Array.from(element.form.elements).indexOf(element);

            if ((event.keyCode >= 48 && event.keyCode <= 57) || (event.keyCode >= 65 && event.keyCode <= 90) || (event.keyCode >= 95 && event.keyCode <= 105)){
                if (found.qty != 0 && found.qty != '' && found.qty != undefined){
                    found.qty ++

                    element.value = ''
                    this.products.pop()
                    this.products.push({ barcode: null, qty: '', price: '', product_id: '', name: '', image: '' })

                } else{
                    let formData = new FormData;
                    formData.append('product', newBarcode)

                    axios.post(this.route('refunds.match'), formData)
                        .then((response) => {
                            if (response.data){
                                let [lastItem] = this.products.slice(-1)
                                lastItem.price = response.data.price

                                lastItem.qty = response.data.stock ?? 1
                                lastItem.product_id = response.data.id
                                lastItem.image = response.data.product_color.photo_url
                                lastItem.name = response.data.product_color.product_name

                                this.products.push({ barcode: '', qty: '', price: '', product_id: '', name: '', image: '' })
                            }
                        })
                }

                element.form.elements.item(
                    currentIndex < element.form.elements.length - 1 ?
                        currentIndex + 1 :
                        0
                ).focus();
            }else{
                if (event.keyCode === 8){
                    event.preventDefault();
                    if (this.products.length > 1)
                        this.products.splice(currentIndex-1, 1)
                }

            }
        }

    },
    setup() {
        const admin = computed(() => usePage().props.value.auth.user)
        return { admin }
    },
    watch: {
        products: {
            handler: throttle(function () {
                var totalPrice = 0;
                var totalQty = 0;
                this.products.forEach(function (productItem) {
                    if (productItem.product_id){
                        let sum = productItem['price'] * productItem['qty'];
                        totalPrice += sum;
                        totalQty += productItem['qty']
                    }
                });
                this.form.total_price = totalPrice;
                this.form.total_qty = totalQty;
            }),
            deep: true
        }
    }

})
</script>
<style scoped>
.multiselect__input{
    padding: 0 !important;
}
</style>
