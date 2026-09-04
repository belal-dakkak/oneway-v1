<template>
    <MeeForm @submitted="createUserProductInformation">
        <div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-5" dir="rtl">
            <div class="px-4">
                <jet-label for="user" :value="__('Shop')" />
                <Multiselect
                    ref="user"
                    v-model="form.user"
                    :options="users"
                    :multiple="false"
                    :close-on-select="true"
                    placeholder="اختر محل من قائمة المحلات"
                    label="name"
                    track-by="id"
                />
            </div>

            <div class="px-4">
                <jet-label for="product" :value="__('Product')" />
                <Multiselect
                    ref="product"
                    v-model="form.product"
                    :options="products"
                    :multiple="false"
                    :close-on-select="true"
                    placeholder="اختر المنتج من القائمة"
                    label="product_name"
                    track-by="id"
                />
            </div>

            <div class="px-4">
                <jet-label for="wholesale_price" :value="__('Wholesale Price') + ' (' + currency.code + ')'" />
                <jet-input id="wholesale_price" type="number" min="0" class="mt-1 block w-full" v-model="form.wholesale_price" />
            </div>

            <div class="px-4">
                <jet-label for="retail_price" :value="__('Retail Price') + ' (' + currency.code + ')'" />
                <jet-input id="retail_price" type="number" min="0" class="mt-1 block w-full" v-model="form.retail_price" />
            </div>

            <div class="px-4">
                <jet-label for="price_before_discount" :value="__('Price Before Discount') + ' (' + currency.code + ')'" />
                <jet-input id="price_before_discount" type="number" min="0" class="mt-1 block w-full" v-model="form.price_before_discount" />
            </div>
        </div>

        <div v-if="transferItems.length" class="space-y-3 px-8 pb-6" dir="rtl">
            <div
                v-for="item in transferItems"
                :key="item.barcode"
                class="grid grid-cols-1 items-end gap-3 rounded-lg border bg-gray-50 p-4 md:grid-cols-4"
            >
                <div><strong>{{ __('Size') }}:</strong> {{ item.size }}</div>
                <div><strong>{{ __('Barcode') }}:</strong> {{ item.barcode }}</div>
                <div><strong>{{ __('Current Stock') }}:</strong> {{ item.available_stock }}</div>
                <div>
                    <jet-label :for="'quantity-' + item.barcode" :value="__('Stock')" />
                    <jet-input
                        :id="'quantity-' + item.barcode"
                        type="number"
                        min="0"
                        :max="item.available_stock"
                        class="mt-1 block w-full"
                        v-model="item.quantity"
                    />
                </div>
            </div>
        </div>
    </MeeForm>
</template>

<script>
import { defineComponent, computed } from 'vue'
import { usePage } from '@inertiajs/inertia-vue3'
import JetInput from '@/Jetstream/Input.vue'
import JetLabel from '@/Jetstream/Label.vue'
import Multiselect from '@suadelabs/vue3-multiselect'
import MeeForm from '@/Shared/Ui/MeeForm'
import Currency from '@/Utils/Currency.js'

export default defineComponent({
    components: { MeeForm, JetInput, JetLabel, Multiselect },

    props: {
        users: Object,
        products: Object,
        sizes: Object,
        currency: Object,
    },

    data() {
        return {
            form: {
                user: null,
                product: null,
                retail_price: '',
                wholesale_price: '',
                price_before_discount: '',
            },
            transferItems: [],
            loading: false,
        }
    },

    watch: {
        'form.product'(product) {
            this.prepareItems(product)
            this.prefillProductPrices(product)
            this.loadExistingPrices()
        },
        'form.user'() {
            this.loadExistingPrices()
        },
    },

    mounted() {
        this.$refs.user.$el.focus()
    },

    methods: {
        prepareItems(product) {
            if (!product) {
                this.transferItems = []
                return
            }

            const sizes = product.clone_list_sizes || product.list_sizes
            if (Array.isArray(sizes)) {
                this.transferItems = sizes.map(item => ({
                    size: item.size,
                    barcode: item.barcode,
                    available_stock: Number(item.stock || 0),
                    quantity: 0,
                }))
                return
            }

            this.transferItems = product.size && product.barcode ? [{
                size: product.size,
                barcode: product.barcode,
                available_stock: Number(product.stock || 0),
                quantity: 0,
            }] : []
        },

        prefillProductPrices(product) {
            if (!product) return

            const baseProduct = product.product || {}
            const wholesale = product.wholesale_price ?? baseProduct.cost_price
            const retail = product.retail_price ?? baseProduct.retail_price
            const beforeDiscount = product.price_before_discount ?? baseProduct.price_before_discount

            this.form.wholesale_price = this.toDisplay(wholesale)
            this.form.retail_price = this.toDisplay(retail)
            this.form.price_before_discount = beforeDiscount ? this.toDisplay(beforeDiscount) : ''
        },

        loadExistingPrices() {
            if (!this.form.user || !this.form.product) return

            axios.post(this.route('userProducts.match'), {
                user: this.form.user.id,
                product: this.productColorId,
            }).then(response => {
                if (!response.data) return
                this.form.retail_price = this.toDisplay(response.data.retail_price)
                this.form.wholesale_price = this.toDisplay(response.data.wholesale_price)
                this.form.price_before_discount = response.data.price_before_discount
                    ? this.toDisplay(response.data.price_before_discount)
                    : ''
            })
        },

        createUserProductInformation() {
            if (this.loading) return

            const items = this.transferItems
                .filter(item => Number(item.quantity) > 0)
                .map(item => ({
                    size: item.size,
                    barcode: item.barcode,
                    quantity: Number(item.quantity),
                }))

            if (!this.form.user || !this.form.product || !this.form.retail_price ||
                !this.form.wholesale_price || !items.length) {
                this.showErrorMessage(__('Please fill required fields'))
                return
            }

            this.loading = true
            axios.post(this.route('userProducts.store'), {
                destination_user_id: this.form.user.id,
                product_color_id: this.productColorId,
                retail_price: this.form.retail_price,
                wholesale_price: this.form.wholesale_price,
                price_before_discount: this.form.price_before_discount || null,
                currency_code: this.currency.code,
                items,
            }).then(response => {
                this.showSuccessMessage(response.data.msg)
                this.$inertia.get(this.route('userProducts.index', {
                    shop: response.data.destination_user_id,
                }))
            }).catch(error => {
                const errors = error.response?.data?.errors
                const firstError = errors ? Object.values(errors).flat()[0] : null
                this.showErrorMessage(firstError || error.response?.data?.message || 'حدث خطأ أثناء إرسال البضاعة')
            }).finally(() => {
                this.loading = false
            })
        },

        toDisplay(value) {
            if (value === null || value === undefined || value === '') return ''
            return Currency.fromUsd(value, this.currency.rate, this.currency.decimals)
        },

        showSuccessMessage(msg) {
            return this.$swal.fire({
                html: '<p class="text-white pt-5 font-extrabold">' + msg + '</p>',
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
            })
        },

        showErrorMessage(msg) {
            return this.$swal.fire({
                html: '<p class="text-white pt-5 font-extrabold">' + msg + '</p>',
                icon: 'warning',
                iconColor: '#FFFFFF',
                width: 400,
                showConfirmButton: false,
                padding: '1em',
                toast: true,
                position: 'bottom-end',
                color: '#FFFFFF',
                background: '#e96e83',
                timer: 2500,
                timerProgressBar: true,
            })
        },
    },

    computed: {
        productColorId() {
            return this.form.product?.product_color_id || this.form.product?.id || null
        },
    },

    setup() {
        const admin = computed(() => usePage().props.value.auth.user)
        return { admin }
    },
})
</script>
