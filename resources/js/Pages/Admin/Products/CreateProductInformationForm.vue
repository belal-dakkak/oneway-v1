<template>
    <jet-form-section @submitted="createProductInformation">
        <template #form>
            <!-- Name -->
            <div class="col-span-8 sm:col-span-4" dir="rtl">
                <jet-label for="name" :value="__('Name')" />
                <jet-input id="name" type="text" class="mt-1 block w-full" v-model="form.name" autocomplete="name" />
                <jet-input-error :message="form.errors.name" class="mt-2" />
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
                <jet-label for="cost_price" :value="__('Cost Price')" dir="rtl" />
                <jet-input id="cost_price" type="number" class="mt-1 block w-full" v-model="form.cost_price" autocomplete="cost_price" />
                <jet-input-error :message="form.errors.cost_price" class="mt-2" />
            </div>

            <div class="col-span-8 sm:col-span-4">
                <jet-label for="retail_price" :value="__('Retail Price')" dir="rtl" />
                <jet-input id="retail_price" type="number" class="mt-1 block w-full" v-model="form.retail_price" autocomplete="retail_price" />
                <jet-input-error :message="form.errors.retail_price" class="mt-2" />
            </div>

            <div class="col-span-8 sm:col-span-4">
                <jet-label for="price_before_discount" :value="__('Price Before Discount')" dir="rtl" />
                <jet-input id="price_before_discount" type="number" class="mt-1 block w-full" v-model="form.price_before_discount" autocomplete="price_before_discount" />
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
                <Multiselect v-model="form.selected_category" :options="categories" :multiple="false" :close-on-select="true" placeholder="اختر صنف" label="name"
                             track-by="id" />
                <jet-input-error :message="form.errors.selected_category" class="mt-2" />
            </div>
            <div class="col-span-8 sm:col-span-4" dir="rtl">
                <jet-label for="country" :value="__('Country')" />
                <Multiselect v-model="form.country" :options="[{ name: 'Lebanon', value: '1' },{ name: 'United Arab Emirates', value: '2' },{ name: 'Both', value: '3' }]" :multiple="false" :close-on-select="true" placeholder="اختر البلد" label="name" track-by="value" />
                <jet-input-error :message="form.errors.country" class="mt-2" />
            </div>
            <div class="col-span-8 sm:col-span-4" dir="rtl">
                <jet-label :value="__('Colors')" />
            </div>

            <div class="col-span-8 sm:col-span-4" dir="rtl">
               <div class="border bg-gray-100" v-for="(product, index) in products">
                   <div class="flex justify-around px-4">
                       <div class="inline-block align-middle mt-16">
                           <jet-label for="selected_color" :value="__('Color')" />
                           <Multiselect v-model="product.color" :options="colors" :multiple="false" :close-on-select="true" placeholder="اختر لون" label="name"
                                        track-by="id" />
                           <jet-input-error :message="form.errors.color" class="mt-2" />
                       </div>
                       <!-- <div class="inline-block align-middle mt-16">
                           <jet-label for="stock" value="Stock (الكمية)" dir="rtl" />
                           <jet-input id="stock" type="number" class="mt-1 block w-full" v-model="product.stock" />
                           <jet-input-error :message="form.errors.stock" class="mt-2" />
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
                   <p class="px-1">{{ __('Add Color')}}</p>
                   <vue-feather :type="'plus-circle'" stroke-width="2"></vue-feather>
               </button>

           </div>

        </template>

        <template #title>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight" dir="rtl">
                {{ __('Add Product')}}
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
            categories: {
                type: Array,
                default: () => []
            },
            colors: Array,
            sizes: Array,
        },

        data() {
            return {
                form: this.$inertia.form({
                    _method: 'POST',
                    name: '',
                    details: '',
                    details_en: '',
                    stock: '',
                    cost_price: '',
                    retail_price: '',
                    price_before_discount: '',
                    country: null,
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
                products: [{ image: '', stock: '', color: '' ,sizes:[{size: '',stock: '0'}]}]
            }
        },

        methods: {
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

                this.form.post(route('products.store'), {
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
            },
        },
    })
</script>
