<template>
    <app-layout title="Create Product">
        <div>
            <div class="max-w-full mx-auto py-10 sm:px-6 lg:px-8">

                <div class="flex justify-center">
                    <form class="w-3/4" @submit.prevent v-on:submit.prevent>
                        <div class="col-span-8 py-4 sm:col-span-4" dir="rtl">
                            <jet-label for="barcode" :value="__('Barcode')" />
                            <jet-input id="barcode" type="text" class="mt-1 block w-full" v-model="form.barcode" autocomplete="barcode" />
                            <jet-input-error :message="form.errors.barcode" class="mt-2" />
                        </div>

                        <div class="col-span-8 py-4 sm:col-span-4" dir="rtl">
                            <jet-label for="name" :value="__('Name')" />
                            <jet-input id="name" type="text" class="mt-1 block w-full" v-model="form.name" autocomplete="name" />
                            <jet-input-error :message="form.errors.name" class="mt-2" />
                        </div>

                        <div class="col-span-8 py-4 sm:col-span-4" dir="rtl">
                            <jet-label for="name_en" :value="__('Name EN')" />
                            <jet-input id="name_en" type="text" class="mt-1 block w-full" v-model="form.name_en" autocomplete="name_en" />
                            <jet-input-error :message="form.errors.name_en" class="mt-2" />
                        </div>

                        <div class="col-span-8 py-4 sm:col-span-4" dir="rtl">
                            <jet-label for="details" :value="__('Details')" />
                            <mee-textarea
                                :placeholder="'details'"
                                v-model="form.details"
                            />
                            <jet-input-error :message="form.errors.details" class="mt-2" />
                        </div>

                        <div class="col-span-8 py-4 sm:col-span-4" dir="rtl">
                            <jet-label for="details_en" :value="__('Details EN')" />
                            <mee-textarea
                                :placeholder="'details en'"
                                v-model="form.details_en"
                            />
                            <jet-input-error :message="form.errors.details_en" class="mt-2" />
                        </div>

                        <div class="col-span-8 py-4 sm:col-span-4">
                            <jet-label for="cost_price" :value="__('Cost Price')" dir="rtl" />
                            <jet-input id="cost_price" type="number" class="mt-1 block w-full" v-model="form.cost_price" autocomplete="cost_price" />
                            <jet-input-error :message="form.errors.cost_price" class="mt-2" />
                        </div>

                        <div class="col-span-8 py-4 sm:col-span-4">
                            <jet-label for="sale_price" :value="__('Sale Price')" dir="rtl" />
                            <jet-input id="sale_price" type="number" min="0" class="mt-1 block w-full" v-model="form.sale_price" autocomplete="sale_price" />
                            <jet-input-error :message="form.errors.sale_price" class="mt-2" />
                        </div>

                        <div class="col-span-8 py-4 sm:col-span-4">
                            <jet-label for="retail_price" :value="__('Retail Price')" dir="rtl" />
                            <jet-input id="retail_price" type="number" class="mt-1 block w-full" v-model="form.retail_price" autocomplete="retail_price" />
                            <jet-input-error :message="form.errors.retail_price" class="mt-2" />
                        </div>

                        <div class="col-span-8 py-4 sm:col-span-4">
                            <jet-label for="price_before_discount" :value="__('Price Before Discount')" dir="rtl" />
                            <jet-input id="price_before_discount" type="number" class="mt-1 block w-full" v-model="form.price_before_discount" autocomplete="price_before_discount" />
                            <jet-input-error :message="form.errors.price_before_discount" class="mt-2" />
                        </div>


                        <!-- <div class="col-span-8 py-4 sm:col-span-4" dir="rtl">
                            <jet-label for="categories" :value="__('Sizes')" />
                            <Multiselect v-model="form.selected_sizes" :options="sizes" :multiple="true" :close-on-select="true" placeholder="اختر الاحجام" label="name"
                                         track-by="id" />
                            <jet-input-error :message="form.errors.selected_sizes" class="mt-2" />
                        </div> -->

                        <div class="col-span-8 py-4 sm:col-span-4" dir="rtl">
                            <jet-label for="categories" :value="__('Category')" />
                            <Multiselect v-model="form.selected_category" :options="categories" :multiple="false" :close-on-select="true" placeholder="اختر صنف" label="name"
                                         track-by="id" />
                            <jet-input-error :message="form.errors.selected_category" class="mt-2" />
                        </div>
                        <div class="col-span-8 sm:col-span-4" dir="rtl">
                            <jet-label for="country" :value="__('Country')" />
                            <Multiselect v-model="form.country" :options="[{ name: 'Lebanon', value: '1' },{ name: 'United Arab Emirates', value: '2' },{ name: 'Both', value: '3' }]" :multiple="false" :close-on-select="true" placeholder="اختر البلد" label="name" track-by="value" />
                            <jet-input-error :message="form.errors.country" class="mt-2" />
                        </div>
                        <div class="col-span-8 py-4 sm:col-span-4" dir="rtl">
                            <jet-label :value="__('Colors')" />
                        </div>

                        <div class="col-span-8 py-4 sm:col-span-4" dir="rtl">
                            <div class="border bg-gray-100" v-for="(product, index) in products">
                                <div class="flex justify-around px-4">
                                    <div class="inline-block align-middle mt-16" dir="rtl">
                                        <jet-label for="barcode" :value="__('Barcode')" />
                                        <jet-input id="barcode" type="text" class="mt-1 block w-full" v-model="product.barcode" autocomplete="barcode" />
                                    </div>

                                    <div class="inline-block align-middle w-1/4 mt-16">
                                        <jet-label for="selected_color" :value="__('Color')" />
                                        <Multiselect v-model="product.color" :options="colors" :multiple="false" :close-on-select="true" placeholder="اختر لون" label="name"
                                                     track-by="id" />
                                        <jet-input-error :message="form.errors.selected_products" class="mt-2" />
                                    </div>
                                    <!-- <div class="inline-block align-middle mt-16">
                                        <jet-label for="stock" value="Stock (الكمية)" dir="rtl" />
                                        <jet-input id="stock" type="number" class="mt-1 block w-full" v-model="product.stock" />
                                        <jet-input-error :message="form.errors.selected_products" class="mt-2" />
                                    </div> -->
                                    <div>
                                        <MeeFile
                                            :title="__('Drag files here to add image')"
                                            :name="'image'"
                                            v-model="product.image"
                                        />
                                    </div>
                                </div>
                                <div class="bg-gray-100" v-for="size in product.sizes">
                                    <div class="flex justify-around px-4">
                                        <div class="inline-block align-middle mt-16" dir="rtl">
                                            <jet-label for="categories" :value="'Size (الحجم)'" />
                                            <Multiselect v-model="size.size" :options="sizes" :multiple="false" :close-on-select="true" placeholder="اختر الاحجام" label="name" track-by="id" />
                                            <jet-input-error :message="form.errors.selected_sizes" class="mt-2" />
                                        </div>
                                        <!-- <div class="inline-block align-middle mt-16">
                                            <jet-label for="selected_color" :value="'Size (الحجم)'" />
                                            <Multiselect v-model="size.size" :options="sizes" :multiple="false" :close-on-select="true" placeholder="اختر حجم" label="nsize" track-by="id" />
                                            <jet-input-error :message="form.errors.size" class="mt-2" />
                                        </div> -->
                                        <div class="inline-block align-middle mt-16">
                                            <jet-label for="sizestock" :value="__('Stock (الكمية)')" dir="rtl" />
                                            <jet-input id="sizestock" dir="ltr" type="text" class="mt-1 block w-full" v-model="size.stock" />
                                            <jet-input-error :message="form.errors.stock" class="mt-2" />
                                        </div>
                                    </div>
                                </div>
                                <button class="flex justify-between rounded-lg bg-teal-500 hover:bg-teal-400 text-white p-2" @click="AddSize(index)" type="button">
                                    <p class="px-1">{{ __('Add Size')}}</p>
                                    <vue-feather :type="'plus-circle'" stroke-width="2"></vue-feather>
                                </button>
                            </div>
                            <button class="flex justify-between rounded-lg bg-teal-500 hover:bg-teal-400 text-white p-2" @click="AddField" type="button">
                                <span class="px-1">{{ __('Add Color')}}</span>
                                <vue-feather :type="'plus-circle'" stroke-width="2"></vue-feather>
                            </button>

                        </div>

                        <jet-button :type="'button'" @click="createProductInformation">
                            {{ __('Save')}}
                        </jet-button>
                    </form>
                </div>
                <jet-section-border />
            </div>
        </div>
    </app-layout>
</template>

<script>
import { defineComponent } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import JetSectionBorder from '@/Jetstream/SectionBorder.vue'
import LogoutOtherBrowserSessionsForm from '@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm.vue'
import TwoFactorAuthenticationForm from '@/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue'
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue'
import CreateProductInformationForm from "@/Pages/Admin/ProductColors/CreateProductInformationForm";
import JetButton from "@/Jetstream/Button";
import JetInput from "@/Jetstream/Input";
import JetInputError from "@/Jetstream/InputError";
import JetLabel from "@/Jetstream/Label";
import JetSecondaryButton from "@/Jetstream/SecondaryButton";
import {MeeFile, MeeTextarea} from "@/Shared/Ui";
import Multiselect from "@suadelabs/vue3-multiselect";
import Currency from '@/Utils/Currency.js';

export default defineComponent({

    name:'createProductInformation',
    components: {
        AppLayout,
        JetSectionBorder,
        LogoutOtherBrowserSessionsForm,
        TwoFactorAuthenticationForm,
        UpdatePasswordForm,
        JetButton,
        JetInput,
        JetInputError,
        JetLabel,
        JetSecondaryButton,
        MeeFile,
        Multiselect,
        MeeTextarea
    },
    props: {
        categories: Array,
        colors: Array,
        sizes: Array,
    },
    data(){
        return {
            form: this.$inertia.form({
                _method: 'POST',
                barcode: '',
                name: '',
                name_en:'',
                details: '',
                details_en: '',
                stock: '',
                cost_price: '',
                sale_price: '',
                country: null,
                retail_price: '',
                price_before_discount: '',
                photo: null,
                selected_category: null,
                selected_sizes: null,
                selected_products: null,
            }),
            photoPreview: null,
            all_cities: false,
            all_categories: false,
            all_nationalities: false,
            all_types: false,
            products: [{ image: '', stock: '', color: '', barcode: '' ,sizes:[{size: '',stock: '0'}]}],
            currencyExchange: Currency.getExchangeMethod(),
        }
    },
    methods:{
        AddField: function () {
            this.products.push({ image: '', stock: '', color: '' ,sizes:[{size: '',stock: '0'}]});
        },
        AddSize: function (size) {
            this.products[size].sizes.push({size: '',stock: '0'});
        },
        createProductInformation() {
            if (this.$refs.photo) {
                this.form.photo = this.$refs.photo.files[0]
            }

            this.form.selected_products = this.products;

            this.form.post(route('productColors.store'), {
                errorBag: 'createProductInformation',
                preserveScroll: true,
                onSuccess: () => (this.clearPhotoFileInput()),
            });
        },
        selectNewPhoto() {
            this.$refs.photo.click();
        },

        updatePhotoPreview() {
            const photo = this.$refs.photo.files[0];

            if (! photo) return;

            const reader = new FileReader();

            reader.onload = (e) => {
                this.photoPreview = e.target.result;
            };

            reader.readAsDataURL(photo);

        },


        clearPhotoFileInput() {
            if (this.$refs.photo?.value) {
                this.$refs.photo.value = null;
            }
            window.scrollTo(0, 0);
        },
    }
})
</script>
