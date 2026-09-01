<template>
    <MeeForm @submitted="createUserProductInformation">

        <div class="grid grid-cols-5 p-4" dir="rtl">

            <div class="px-4" dir="rtl">
                <jet-label for="user" :value="__('Shop')" />
                <Multiselect ref="user" v-model="form.user" v-on:select="goToProducts" :options="users" :multiple="false" :close-on-select="true" placeholder="اختر محل من قائمة المحلات" label="name"
                             track-by="product_color_id" />
                <jet-input-error :message="form.errors.user" class="mt-2" />
            </div>

            <div class="px-4" dir="rtl">
                <jet-label for="product" :value="__('Product')" />
                <Multiselect ref="product" v-model="form.product" v-on:select="goToPrice" :options="products" :multiple="false" :close-on-select="true" placeholder="اختر المنتج من القائمة" label="product_name"
                             track-by="id" />
                <jet-input-error :message="form.errors.product" class="mt-2" />
            </div>
            <!-- <div class="px-4" dir="rtl">
                <jet-label for="categories" :value="__('Size')" />
                <Multiselect v-model="form.size" :options="sizes" :multiple="false" :close-on-select="true" placeholder="اختر الاحجام" label="name" track-by="id" />
                <jet-input-error :message="form.errors.sizes" class="mt-2" />
            </div> -->
            <div class="px-4">
                <jet-label for="wholesale_price" :value="__('Wholesale Price')" dir="rtl" />
                <jet-input ref="price" id="wholesale_price" type="number" class="mt-1 block w-full" v-model="form.wholesale_price" autocomplete="wholesale_price" />
                <jet-input-error :message="form.errors.wholesale_price" class="mt-2" />
            </div>

            <div class="px-4">
                <jet-label for="retail_price" :value="__('Retail Price')" dir="rtl" />
                <jet-input id="retail_price" type="number" class="mt-1 block w-full" v-model="form.retail_price" autocomplete="retail_price" />
                <jet-input-error :message="form.errors.retail_price" class="mt-2" />
            </div>

            <div class="px-4">
                <jet-label for="price_before_discount" :value="__('Price Before Discount')" dir="rtl" />
                <jet-input id="price_before_discount" type="number" class="mt-1 block w-full" v-model="form.price_before_discount" autocomplete="price_before_discount" />
                <jet-input-error :message="form.errors.price_before_discount" class="mt-2" />
            </div>

            <div class="px-4">
                <jet-label for="stock" :value="__('Stock')" dir="rtl" />
                <jet-input id="stock" type="number" class="mt-1 block w-full" v-model="form.stock" autocomplete="stock" />
                <jet-input-error :message="form.errors.stock" class="mt-2" />
            </div>
            <div class="px-4">
                <jet-label for="barcode" :value="__('Barcode')" dir="rtl" />
                <jet-input id="barcode" type="text" class="mt-1 block w-full" v-model="form.barcode" autocomplete="barcode" />
                <jet-input-error :message="form.errors.barcode" class="mt-2" />
            </div>
        </div>
    </MeeForm>
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
    import { MeeTextarea, MeeRadio, MeeStatus } from "@/Shared/Ui";
    import Multiselect from '@suadelabs/vue3-multiselect'
    import JetCheckbox from '@/Jetstream/Checkbox.vue'
    import MeeForm from "@/Shared/Ui/MeeForm";
    import {throttle} from "lodash";
    import {computed} from "vue";
    import {usePage} from "@inertiajs/inertia-vue3";
    import axios from "axios";

    export default defineComponent({
        components: {
            MeeForm,
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
        },
        props: {
          users: Object,
          products: Object,
          sizes: Object
        },
        data() {
            return {
                form: this.$inertia.form({
                    _method: 'POST',
                    user: '',
                    product: '',
                    retail_price: '',
                    wholesale_price: '',
                    stock: '',
                    size: '',
                    barcode: '',
                    price_before_discount: ''
                }),
                user: 'init',
                product: 'init'
            }
        },

        mounted() {
            this.$refs.user.$el.focus()
        },

        methods: {
            createUserProductInformation() {
                if (!this.form.user || !this.form.product || !this.form.stock || !this.form.retail_price)
                    this.showErrorMessage(__('Please fill required fields'))
                if(this.admin.role === 2){
                    if (this.form.stock > this.form.product.stock)
                        this.showErrorMessage(__('The number you entered bigger that what you have!'))
                    else{
                        this.form.post(route('userProducts.store'), {
                            errorBag: 'createUserProductInformation',
                            preserveScroll: true
                        });
                    }
                }else{
                    let formData = new FormData;
                    formData = this.form.data()
                    axios.post(this.route('userProducts.store'), formData).then((response) => {
                            if (response.data.success){
                                this.showSuccessMessage(response.data.msg)
                                this.form.user = ''
                                this.form.product = ''
                                this.form.stock = ''
                                this.form.retail_price = ''
                                this.form.barcode = ''
                                this.form.wholesale_price = ''
                                this.form.price_before_discount = ''
                                this.form.barcode = ''
                            }else{
                                this.showErrorMessage('حدث خطأ ما')
                            }
                    }).catch(error => {
                        this.showErrorMessage('حدث خطأ ما')
                    })
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
            goToProducts(){
                this.$refs.product.$el.focus()
            },
            goToPrice(){
                this.$refs.price.$el.focus()
            }
        },

        watch: {
            form: {
                handler: throttle(function () {
                    if (this.form.user && this.form.product && this.user !== this.form.user && this.product !== this.form.product){
                        this.user = this.form.user;
                        this.product = this.form.product;

                        let formData = new FormData;
                        formData.append('user', this.form.user.id)
                        formData.append('product', this.form.product.id)
                        formData.append('barcode', this.form.barcode)
                        axios.post(this.route('userProducts.match'), formData)
                            .then((response) => {
                                if (response){
                                    this.form.stock = response.data.stock;
                                    this.form.retail_price = response.data.retail_price;
                                    this.form.wholesale_price = response.data.wholesale_price;
                                    this.form.price_before_discount = response.data.price_before_discount;
                                }
                            })
                    }
                }),
                deep: true
            }
        },
        setup() {
            const admin = computed(() => usePage().props.value.auth.user)
            return { admin }
        },
    })
</script>
