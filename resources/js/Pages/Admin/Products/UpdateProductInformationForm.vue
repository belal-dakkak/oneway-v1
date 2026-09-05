<template>
    <jet-form-section @submitted="updateProductInformation">
        <template #form>
            <!-- Name -->
            <div class="col-span-8 sm:col-span-4" dir="rtl">
                <jet-label for="name" :value="__('Name')" />
                <jet-input id="name" type="text" class="mt-1 block w-full" v-model="form.name" autocomplete="name" />
                <jet-input-error :message="form.errors.name" class="mt-2" />
            </div>

            <div class="col-span-8 sm:col-span-4" dir="rtl">
                <jet-label for="name_en" :value="__('Name EN')" />
                <jet-input id="name_en" type="text" class="mt-1 block w-full" v-model="form.name_en" autocomplete="name_en" />
                <jet-input-error :message="form.errors.name_en" class="mt-2" />
            </div>

            <div class="col-span-8 sm:col-span-4" dir="rtl">
                <jet-label for="barcode" :value="__('Barcode')" />
                <jet-input id="barcode" type="text" class="mt-1 block w-full" v-model="form.barcode" autocomplete="barcode" />
                <jet-input-error :message="form.errors.barcode" class="mt-2" />
            </div>

            <div class="col-span-8 sm:col-span-4" dir="rtl">
                <jet-label for="details" :value="__('Details')" />
                <mee-textarea
                    :placeholder="'details'"
                    v-model="form.details"
                />
                <jet-input-error :message="form.errors.details" class="mt-2" />
            </div>

            <div class="col-span-8 sm:col-span-4" dir="rtl">
                <jet-label for="details_en" :value="__('Details EN')" />
                <mee-textarea
                    :placeholder="'details en'"
                    v-model="form.details_en"
                />
                <jet-input-error :message="form.errors.details_en" class="mt-2" />
            </div>

            <div class="col-span-8 sm:col-span-4">
                <jet-label for="cost_price" :value="__('Cost Price') + ' (' + currency.code + ')'" dir="rtl" />
                <jet-input id="cost_price" type="number" min="0" :step="currency.code === 'SYP' ? 1 : 0.01" class="mt-1 block w-full" v-model="form.cost_price" autocomplete="cost_price" @blur="normalizePrice('cost_price')" />
                <syp-equivalent :usd="form.cost_price" />
                <jet-input-error :message="form.errors.cost_price" class="mt-2" />
            </div>

            <div class="col-span-8 py-4 sm:col-span-4">
                <jet-label for="sale_price" :value="__('Sale Price') + ' (' + currency.code + ')'" dir="rtl" />
                <jet-input id="sale_price" type="number" min="0" :step="currency.code === 'SYP' ? 1 : 0.01" class="mt-1 block w-full" v-model="form.sale_price" autocomplete="sale_price" @blur="normalizePrice('sale_price')" />
                <syp-equivalent :usd="form.sale_price" />
                <jet-input-error :message="form.errors.sale_price" class="mt-2" />
            </div>

            <div class="col-span-8 sm:col-span-4">
                <jet-label for="retail_price" :value="__('Retail Price') + ' (' + currency.code + ')'" dir="rtl" />
                <jet-input id="retail_price" type="number" min="0" :step="currency.code === 'SYP' ? 1 : 0.01" class="mt-1 block w-full" v-model="form.retail_price" autocomplete="retail_price" @blur="normalizePrice('retail_price')" />
                <syp-equivalent :usd="form.retail_price" />
                <jet-input-error :message="form.errors.retail_price" class="mt-2" />
            </div>

           <div class="col-span-8 sm:col-span-4">
                <jet-label for="price_before_discount" :value="__('Price Before Discount') + ' (' + currency.code + ')'" dir="rtl" />
                <jet-input id="price_before_discount" type="number" min="0" :step="currency.code === 'SYP' ? 1 : 0.01" class="mt-1 block w-full" v-model="form.price_before_discount" autocomplete="price_before_discount" @blur="normalizePrice('price_before_discount')" />
                <syp-equivalent :usd="form.price_before_discount" />
                <jet-input-error :message="form.errors.price_before_discount" class="mt-2" />
            </div>

            <!-- <div class="col-span-8 sm:col-span-4" dir="rtl">
                <jet-label for="categories" :value="__('Sizes')" />
                <Multiselect v-model="form.selected_sizes" :options="sizes" :multiple="true" :close-on-select="true" placeholder="اختر الاحجام" label="name"
                             track-by="id" />
                <jet-input-error :message="form.errors.selected_sizes" class="mt-2" />
            </div> -->

            <div class="col-span-8 sm:col-span-4" dir="rtl">
                <jet-label for="categories" :value="__('Category')" />
                <Multiselect v-model="form.selected_category" :options="categories" :multiple="false" :close-on-select="true" placeholder="اختر صنف" label="name" track-by="id" />
                <jet-input-error :message="form.errors.selected_category" class="mt-2" />
            </div>
            <div class="col-span-8 sm:col-span-4" dir="rtl">
                <jet-label for="categories" :value="__('Country')" />
                <Multiselect v-model="form.country" :options="[{ name: 'Lebanon', value: '1',id: '1' },{ name: 'United Arab Emirates', value: '2',id: '2' },{ name: 'All Countries', value: '3',id: '3' },{ name: 'Syria', value: '4',id: '4' }]" :multiple="false" :close-on-select="true" placeholder="اختر البلد" label="name" track-by="id" />
                <jet-input-error :message="form.errors.country" class="mt-2" />
            </div>

            <div class="col-span-8 sm:col-span-4" dir="rtl">
                <jet-label :value="__('Colors')" />
            </div>

            <div class="col-span-8 sm:col-span-4" dir="rtl">
               <div class="border bg-gray-100" v-for="(sub, index) in products">
                   <div class="flex justify-around px-4">
                       <img :src="sub.image_url" class="rounded-2xl w-30">
                   </div>
                   <div class="flex justify-around px-4">
                       <div class="inline-block align-middle mt-16">
                           <jet-label for="selected_color" :value="__('Color')" />
                           <Multiselect v-model="sub.color" :options="colors" :multiple="false" :close-on-select="true" placeholder="اختر لون" label="name"
                                        track-by="id" />
                           <jet-input-error :message="form.errors.color" class="mt-2" />
                       </div>
                       <div class="inline-block align-middle mt-16">
                           <jet-label for="barcode" :value="__('Barcode')" dir="rtl" />
                           <jet-input id="barcode" dir="ltr" type="text" class="mt-1 block w-full" v-model="sub.barcode" />
                           <jet-input-error :message="form.errors.barcode" class="mt-2" />
                       </div>
                       <!-- <div class="inline-block align-middle mt-16">
                           <jet-label for="stock" value="Stock (الكمية)" dir="rtl" />
                           <jet-input id="stock" type="number" class="mt-1 block w-full" v-model="sub.stock" />
                           <jet-input-error :message="form.errors.stock" class="mt-2" />
                       </div> -->

                       <div>
                           <MeeFile
                               :title="__('Drag files here to add image')"
                               :name="'image'"
                               v-model="sub.image"
                           />
                       </div>
                    </div>
                    <div class="bg-gray-100" v-for="size in sub.sizes">
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
                   <p class="px-1">{{ __('Add Color')}}</p>
                   <vue-feather :type="'plus-circle'" stroke-width="2"></vue-feather>
               </button>

           </div>

        </template>

        <template #title>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight" dir="rtl">
                {{ __('Edit Model')}}
            </h2>
        </template>

        <template #actions>
            <jet-action-message :on="form.recentlySuccessful" class="mr-3">
                تم الحفظ.
            </jet-action-message>

            <jet-button class="px-16 py-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                {{ __('Save')}}
            </jet-button>
        </template>
    </jet-form-section>
</template>

<script>
    import { defineComponent } from 'vue'
    import JetButton from '@/Jetstream/Button.vue'
    import JetFormSection from '@/Jetstream/FormSection.vue'
    import JetInput from '@/Jetstream/Input.vue'
    import JetInputError from '@/Jetstream/InputError.vue'
    import JetLabel from '@/Jetstream/Label.vue'
    import JetActionMessage from '@/Jetstream/ActionMessage.vue'
    import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'
    import { MeeTextarea, MeeRadio, MeeStatus, MeeFile, TextField } from "@/Shared/Ui";
    import Multiselect from '@suadelabs/vue3-multiselect'
    import JetCheckbox from '@/Jetstream/Checkbox.vue'
    import Currency from '@/Utils/Currency.js';


    export default defineComponent({
        components: {
            JetActionMessage,
            JetButton,
            JetFormSection,
            JetInput,
            JetInputError,
            JetLabel,
            JetSecondaryButton,
            JetCheckbox,
            MeeTextarea,
            MeeRadio,
            MeeStatus,
            Multiselect,
            TextField,
            MeeFile
        },

        props: {
            product: Object,
            categories: {
                type: Array,
                default: () => []
            },
            colors: Array,
            sizes: Array,
            new_sizes: Array,
            selected_sizes: Array,
            selected_colors: Array,
            rate: Number,
            currency: { type: Object, default: () => ({ code: 'USD', rate: 1, decimals: 2 }) },
        },

        data() {
            return {
                form: this.$inertia.form({
                    _method: 'PUT',
                    name: this.product.name,
                    name_en: this.product.name_en,
                    barcode: this.product.barcode,
                    details: this.product.details,
                    details_en: this.product.details_en,
                    cost_price: Currency.exchange(this.product.cost_price, this.rate),
                    sale_price: Currency.exchange(this.product.sale_price, this.rate),
                    retail_price: Currency.exchange(this.product.retail_price, this.rate),
                    price_before_discount: Currency.exchange(this.product.price_before_discount_raw, this.rate),
                    photo: null,
                    country: { id:this.product.country_id, name: this.product.country_name } ,
                    selected_category: this.product.category,
                    selected_sizes: this.selected_sizes,
                    selected_products: this.selected_colors,
                }),
                photoPreview: null,
                all_cities: false,
                all_categories: false,
                all_nationalities: false,
                all_types: false,
                products: this.selected_colors,
            }
        },

        methods: {
            normalizePrice(field) {
                if (this.form[field] === '' || this.form[field] === null || this.form[field] === undefined) return
                this.form[field] = Currency.normalizeInput(this.form[field], this.currency.code)
            },
            AddField: function () {
                this.products.push({ image: '', stock: '', color: '' ,sizes:[{size: '',stock: '0'}]});
            },
            AddSize: function (size) {
                this.products[size].sizes.push({size: '',stock: '0'});
            },
            updateProductInformation() {
                if (this.$refs.photo) {
                    this.form.photo = this.$refs.photo.files[0]
                }

                this.form.selected_products = this.products;

                this.form.post(route('products.update', this.product.id), {
                    errorBag: 'updateProductInformation',
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
            },
        },
    })
</script>
