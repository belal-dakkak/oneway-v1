<template>
    <app-layout title="Website Orders Management">
        <div class="flex justify-around mt-8">
            <div class="flex flex-col">
                <label dir="rtl" class="pr-2"> كل الزبائن </label>
                <div>
                    <Multiselect v-model="buyer" :options="buyers" @select="handleSelect" :multiple="false" :close-on-select="true" placeholder="اختر زبون" label="name" track-by="id" />
                </div>
            </div>

            <div class="flex flex-col">
                <label dir="rtl" class="pr-2">تاريخ من</label>
                <div class="flex justify-center hover:cursor-pointer space-x-4 border border-gray-300 px-4 py-2 rounded-xl shadow-lg" v-bind:class="params.start_date?'pt-4':''">
                    <Datepicker v-model="start_date" @update:modelValue="handleStartDate" :clearable="true">
                        <template #trigger>
                            <p class="clickable-text" v-if="params.start_date">
                                {{ start_date?.value ?? params.start_date }}
                            </p>
                            <p v-else class="text-sm text-gray-500 font-light">اضغط لاختيار التاريخ</p>
                        </template>
                    </Datepicker>
                    <div v-if="params.start_date">
                        <vue-feather @click="resetStartDate" class="hover:cursor-pointer text-pcr-mid" :type="'x'"></vue-feather>
                    </div>
                </div>
            </div>
            <div class="flex flex-col">
                <label dir="rtl" class="pr-2">تاريخ إلى</label>
                <div class="flex justify-center hover:cursor-pointer space-x-4 border border-gray-300 px-4 py-2 rounded-xl shadow-lg" v-bind:class="params.end_date?'pt-4':''">
                    <Datepicker v-model="end_date" @update:modelValue="handleEndDate" :clearable="true">
                        <template #trigger>
                            <p class="clickable-text" v-if="params.end_date">
                                {{ end_date?.value ?? params.end_date }}
                            </p>
                            <p v-else class="text-sm text-gray-500 font-light">اضغط لاختيار التاريخ</p>
                        </template>
                    </Datepicker>
                    <div v-if="params.end_date">
                        <vue-feather @click="resetEndDate" class="hover:cursor-pointer text-pcr-mid" :type="'x'"></vue-feather>
                    </div>
                </div>
            </div>

            <div class="flex flex-col">
                <label dir="rtl" class="pr-2">تاريخ معين</label>
                <div class="flex justify-center hover:cursor-pointer space-x-4 border border-gray-300 px-4 py-2 rounded-xl shadow-lg" v-bind:class="params.date?'pt-4':''">
                    <Datepicker v-model="date" @update:modelValue="handleDate" :clearable="true">
                        <template #trigger>
                            <p class="clickable-text" v-if="params.date">
                                {{ date?.value ?? params.date }}
                            </p>
                            <p v-else class="text-sm text-gray-500 font-light">اضغط لاختيار التاريخ</p>
                        </template>
                    </Datepicker>
                    <div v-if="params.date">
                        <vue-feather @click="resetDate" class="hover:cursor-pointer text-pcr-mid" :type="'x'"></vue-feather>
                    </div>
                </div>
            </div>

            <div class="flex flex-col" v-if="user.role === 1">
                <label dir="rtl" class="pr-2"> إجمالي المبيع (الموقع) </label>
                <span class="bg-emerald-500 px-2 rounded-md text-3xl text-white">{{ Number(totalSales || 0).toFixed(2) }}</span>
            </div>

            <div class="flex flex-col" v-if="user.role === 1">
                <label dir="rtl" class="pr-2"> الإجمالي (الموقع) ش.ض </label>
                <span class="bg-emerald-500 px-2 rounded-md text-3xl text-white">{{ Number(totalPriceWithoutTax).toFixed(2) }}</span>
            </div>

            <div class="flex flex-col" v-if="user.role === 1">
                <label dir="rtl" class="pr-2"> إجمالي الضريبه (الموقع) </label>
                <span class="bg-emerald-500 px-2 rounded-md text-3xl text-white">{{ Number(totalTaxValue).toFixed(2) }}</span>
            </div>

            <div class="flex flex-col">
                <label dir="rtl" class="pr-2"> عدد القطع (الموقع) </label>
                <span class="bg-emerald-500 px-2 rounded-md text-3xl text-white">{{totalCount}}</span>
            </div>

            <div class="mt-8 flex justify-between space-x-4">
                <div class="max-w-xs">
                    <input dir="rtl" type="search" v-model="params.search" placeholder="بحث..." class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                </div>
                <h3 class="text-2xl font-bold capitalize text-primary">طلبيات الموقع الإلكتروني</h3>
            </div>

            <div class="mt-4 flex justify-end">
                <button @click="exportPDF()" class="w-full my-1 font-bold text-gray-600 hover:cursor-pointer text-center text-md bg-gray-50 rounded-md">
                    إنشاء كشف PDF
                </button>
            </div>
        </div>

        <MeeTable :tableTitle="''">
            <div v-if="userOrders.data.length === 0" class="my-40 flex items-center justify-center text-xl font-bold text-error">
                <span class="text-center">لا يوجد طلبيات من الموقع!</span>
            </div>
            <table v-else class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr class="shadow-2xl py-4">
                        <th scope="col" class="px-6 py-3 text-center font-semibold text-sm tracking-wider cursor-pointer">
                            المنتجات
                        </th>
                        <th style="min-width: 200px;"></th>
                        <th scope="col" class="px-2 py-3 text-center font-semibold text-sm tracking-wider cursor-pointer" @click="sort('id')">
                            الترميز (ID)
                            <vue-feather :type="'chevron-up'" stroke-width="2" class="p-1 h-4 w-8 place-self-center inline-block" v-if="params.field==='id' && params.direction==='asc'"></vue-feather>
                            <vue-feather :type="'chevron-down'" stroke-width="2" class="p-1 h-4 w-8 place-self-center inline-block" v-if="params.field==='id' && params.direction==='desc'"></vue-feather>
                        </th>
                        <th scope="col" class="px-2 py-3 text-center font-semibold text-sm tracking-wider">
                            التفاصيل المالية
                        </th>
                        <th scope="col" class="px-2 py-3 text-center font-semibold text-sm tracking-wider">
                            الشاري
                        </th>
                        <th scope="col" class="px-2 py-3 text-center font-semibold text-sm tracking-wider">
                            عنوان الشحن
                        </th>
                        <th scope="col" class="px-2 py-3 text-center font-semibold text-sm tracking-wider">
                            طريقة الدفع
                        </th>
                        <th scope="col" class="px-2 py-3 text-center font-semibold text-sm tracking-wider">
                            الحالة
                        </th>
                        <th scope="col" class="px-6 py-3 text-center font-semibold text-sm tracking-wider">
                            التاريخ
                        </th>
                        <th scope="col" class="px-6 py-3 text-center font-semibold text-sm tracking-wider">
                            الإجراءات
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="item in userOrders.data" :key="item.id" class="font-sans-latin text-sm font-medium">
                        <td class="text-center p-4">
                            <button class="p-2 rounded-md text-white bg-rose-400 hover:bg-red-600" @click="showProducts(item)">
                                <vue-feather :type="'tag'" stroke-width="2"></vue-feather>
                            </button>
                        </td>
                        <td class="p-4">
                            <div class="flex flex-wrap gap-2">
                                <div v-for="order_item in item.items" :key="order_item.id">
                                    <img v-if="order_item?.product?.product_color" :src="order_item.product.product_color.photo_url" class="w-16 h-16 rounded shadow-sm object-cover">
                                </div>
                            </div>
                        </td>
                        <td class="text-center p-4">{{ item.barcode || item.id }}</td>
                        <td class="p-4">
                            <div class="flex flex-col gap-1 text-xs">
                                <span class="bg-purple-100 p-1 rounded text-center">إجمالي المنتجات: {{ formatMoney(item.total_price - (item.shipping_fee || 0) - (item.cod_fee || 0), item) }}</span>
                                <span class="bg-fuchsia-100 p-1 rounded text-center">
                                    إجمالي: {{ formatMoney(item.total_price, item) }}
                                    <syp-equivalent v-if="item.display_currency && Number(item.display_rate) > 0" :usd="item.total_price" :display-currency="orderDisplayCurrency(item)" />
                                </span>
                                <span v-if="item.shipping_fee > 0" class="bg-blue-50 p-1 rounded text-center text-[10px]">توصيل: {{ formatMoney(item.shipping_fee, item) }}</span>
                                <span v-if="item.cod_fee > 0" class="bg-orange-50 p-1 rounded text-center text-[10px]">رسوم دفع: {{ formatMoney(item.cod_fee, item) }}</span>
                                <span class="bg-teal-100 p-1 rounded text-center">مدفوع: {{ formatMoney(item.paid_price, item) }}</span>
                                <span class="bg-rose-100 p-1 rounded text-center">متبقي: {{ formatMoney(item.remain_price, item) }}</span>
                            </div>
                        </td>
                        <td class="text-center p-4">
                            <div class="flex flex-col text-xs">
                                <span class="font-bold">{{ item.first_name }} {{ item.last_name }}</span>
                                <span class="text-gray-500">{{ item.phone }}</span>
                                <span class="text-gray-500">{{ item.email }}</span>
                            </div>
                        </td>
                        <td class="text-center p-4">
                            <div class="flex flex-col text-xs text-right">
                                <span>{{ item.city }}</span>
                                <span>{{ item.building_name }}, {{ item.flat_number }}</span>
                                <span class="truncate max-w-[150px]">{{ item.address }}</span>
                            </div>
                        </td>
                        <td class="text-center p-4 text-xs">
                            <div class="flex flex-col items-center gap-1">
                                <span>{{ item.payment_label }}</span>
                                <span
                                    :class="item.is_paid
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-red-100 text-red-800'"
                                    class="px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                >
                                    {{ item.is_paid ? 'مدفوع' : 'غير مدفوع' }}
                                </span>
                            </div>
                        </td>
                        <td class="text-center p-4">
                            <span :class="{'bg-orange-200 text-orange-800': item.status === 0, 'bg-yellow-200 text-yellow-800': item.status === 1, 'bg-blue-200 text-blue-800': item.status === 2, 'bg-green-200 text-green-800': item.status === 3, 'bg-red-200 text-red-800': item.status === 4}" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                {{ item.status_label || 'قيد الانتظار' }}
                            </span>
                        </td>
                        <td class="text-center p-4 text-xs">{{ item.date }}</td>
                        <td class="text-center p-4 flex justify-center gap-1">
                            <inertia-link v-show="false" :href="route('app.invoice.show', item.id)" class="p-2 bg-purple-500 text-white rounded hover:bg-purple-600 flex items-center justify-center" title="تفاصيل الطلبية">
                                <vue-feather :type="'eye'" class="w-4 h-4"></vue-feather>
                            </inertia-link>
                            <a target="_blank" v-show="false" :href="route('download.invoice.typed', { source: 'website', id: item.id })" class="p-2 bg-teal-500 text-white rounded hover:bg-teal-600 flex items-center justify-center" title="تحميل فاتورة PDF">
                                <vue-feather :type="'share'" class="w-4 h-4"></vue-feather>
                            </a>
                            <a target="_blank" v-show="false" :href="route('invoice.typed.show', { source: 'website', id: item.id })" class="p-2 bg-teal-500 text-white rounded hover:bg-teal-600 flex items-center justify-center" title="عرض الفاتورة">
                                <vue-feather :type="'cast'" class="w-4 h-4"></vue-feather>
                            </a>
                            <a target="_blank" v-show="false" :href="route('invoice.typed.printv2', { source: 'website', id: item.id })" class="p-2 bg-teal-500 text-white rounded hover:bg-teal-600 flex items-center justify-center" title="طباعة الفاتورة">
                                <vue-feather :type="'printer'" class="w-4 h-4"></vue-feather>
                            </a>
                            <button @click="changeStatusTo(item, 2)" class="p-2 bg-blue-500 text-white rounded hover:bg-blue-600" title="توصيل">
                                <vue-feather :type="'truck'" class="w-4 h-4"></vue-feather>
                            </button>
                            <button @click="changeStatusTo(item, 3)" class="p-2 bg-green-500 text-white rounded hover:bg-green-600" title="تم التوصيل">
                                <vue-feather :type="'check-circle'" class="w-4 h-4"></vue-feather>
                            </button>
                            <button @click="changeStatusTo(item, 1)" class="p-2 bg-yellow-500 text-white rounded hover:bg-yellow-600" title="قيد الانتظار">
                                <vue-feather :type="'clock'" class="w-4 h-4"></vue-feather>
                            </button>
                            <button
                                v-if="!item.is_paid"
                                @click="markAsPaid(item)"
                                class="p-2 bg-emerald-600 text-white rounded hover:bg-emerald-700 flex items-center gap-1 text-xs"
                                title="تحديد كمدفوع"
                            >
                                <vue-feather :type="'dollar-sign'" class="w-4 h-4"></vue-feather>
                                <span>تحديد كمدفوع</span>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </MeeTable>

        <transition name="modal">
            <modal v-if="showModal" @close="showModal = false">
                <div class="modal-mask rounded-2xl">
                    <div class="modal-wrapper m-24">
                        <div class="modal-container relative">
                            <span class="absolute top-1 right-1 mt-1 mr-2 cursor-pointer" @click="closeModal()">
                                <vue-feather :type="'x'" class="w-8" stroke-width="2"></vue-feather>
                            </span>
                            <div class="modal-body p-6" style="height: 500px; overflow: auto;">
                                <div class="flex flex-col gap-3">
                                    <div
                                        v-for="(product, index) in products"
                                        :key="product.id"
                                        class="flex items-center gap-4 border rounded-xl p-3 bg-gray-50"
                                    >
                                        <!-- Image -->
                                        <img
                                            class="w-28 h-28 rounded-lg object-cover flex-shrink-0 shadow-sm"
                                            :src="product.product_color?.photo_url"
                                            alt=""
                                        >
                                        <!-- Details -->
                                        <div class="flex flex-col gap-1 text-sm flex-1" dir="rtl">
                                            <span class="font-bold text-base">{{ product.product_color?.product_name_without_barcode }}</span>
                                            <div class="flex flex-wrap gap-x-4 gap-y-1 text-gray-600 text-xs mt-1">
                                                <span>🎨 اللون: <strong>{{ product.product_color?.color_name }}</strong></span>
                                                <span>📐 المقاس: <strong>{{ product.size }}</strong></span>
                                                <span>📦 الكمية: <strong>{{ product.qty }}</strong></span>
                                            </div>
                                            <div class="flex flex-wrap gap-x-4 gap-y-1 text-gray-700 text-xs mt-1">
                                                <span>💰 سعر الوحدة: <strong>{{ formatMoney(product.item_price, activeOrder) }}</strong></span>
                                                <span>🧾 الإجمالي: <strong>{{ formatMoney(product.total_price, activeOrder) }}</strong></span>
                                            </div>
                                            <span class="text-gray-400 text-[10px] mt-1">باركود: {{ product.product_color?.barcode }}</span>
                                        </div>
                                        <!-- Index badge -->
                                        <span class="text-xs font-bold text-gray-400 self-start">#{{ index + 1 }}</span>
                                    </div>
                                </div>
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
import JetButton from '@/Jetstream/Button.vue'
import { ref, onMounted, onUnmounted } from 'vue'
import { throttle } from 'lodash'
import axios from 'axios'
import Datepicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import Multiselect from '@suadelabs/vue3-multiselect'
import { computed } from 'vue'
import { usePage } from '@inertiajs/inertia-vue3'

export default {
    components: { AppLayout, MeeTable, Pagination, JetButton, Datepicker, Multiselect },
    props: {
        orders: Object,
        filters: Object,
        total: Number,
        count: Number,
        total_price_without_tax: Number,
        total_tax_value: Number,
        buyers: Array
    },
    data() {
        return {
            params: {
                search: this.filters.search || '',
                field: this.filters.field || 'id',
                direction: this.filters.direction || 'desc',
                start_date: this.filters.start_date || '',
                end_date: this.filters.end_date || '',
                date: this.filters.date || '',
                buyer: this.filters.buyer || ''
            },
            userOrders: this.orders,
            totalSales: this.total,
            totalPriceWithoutTax: this.total_price_without_tax,
            totalTaxValue: this.total_tax_value,
            totalCount: this.count,
            showModal: false,
            products: null,
            activeOrder: null,
            page: 1,
            isLoading: false
        }
    },
    methods: {
        handleSelect(selectedItem) {
            this.params['buyer'] = selectedItem.id;
        },
        resetDate(){
            this.params.date = null;
            let params = this.params;
            this.handleFilter(params)
        },
        resetStartDate(){
            this.params.start_date = null;
            let params = this.params;
            this.handleFilter(params)
        },
        resetEndDate(){
            this.params.end_date = null;
            let params = this.params;
            this.handleFilter(params)
        },
        async showProducts(item) {
            this.showModal = true;
            this.activeOrder = item;
            try {
                const response = await axios.get(this.route('orders.items', { id: item.id }));
                this.products = response.data[0].items;
            } catch (e) {
                console.error(e);
            }
        },
        closeModal() {
            this.showModal = false;
            this.products = null;
            this.activeOrder = null;
        },
        formatMoney(value, order) {
            const currency = String(order?.curr_type || 'USD').toUpperCase();
            const decimals = currency === 'SYP' ? 0 : 2;
            return Number(value || 0).toLocaleString(undefined, {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
            }) + ' ' + currency;
        },
        orderDisplayCurrency(order) {
            if (!order?.display_currency || Number(order?.display_rate) <= 0) return null;
            return {
                code: order.display_currency,
                rate: Number(order.display_rate),
                decimals: order.display_currency === 'SYP' ? 0 : 2,
                approximate: true,
            };
        },
        async changeStatus(item) {
            try {
                const response = await axios.post(this.route('orders.websiteOrders.changeStatus', { id: item.id }));
                item.status = response.data.status;
                item.status_label = response.data.status_label;
            } catch (e) {
                console.error('Error changing status', e);
            }
        },
        async changeStatusTo(item, status) {
            try {
                const response = await axios.post(this.route('orders.websiteOrders.changeStatus', { id: item.id }), { status: status });
                item.status = response.data.status;
                item.status_label = response.data.status_label;
            } catch (e) {
                console.error('Error changing status', e);
            }
        },
        async markAsPaid(item) {
            if (!confirm('هل تريد تحديد هذا الطلب كمدفوع؟')) return;
            try {
                const response = await axios.post(this.route('orders.websiteOrders.markPaid', { id: item.id }));
                item.paid_price   = response.data.paid_price;
                item.remain_price = response.data.remain_price;
                item.is_paid      = response.data.is_paid;
            } catch (e) {
                console.error('Error marking as paid', e);
            }
        },
        exportPDF() {
            window.location.href = this.route('exportpdf', { ...this.params, order_type: 'website' });
        },
        sort(field) {
            this.params.field = field;
            this.params.direction = this.params.direction === 'asc' ? 'desc' : 'asc';
        },
        handleFilter: throttle(function() {
            axios.get(this.route('websiteOrders', this.params)).then(response => {
                this.userOrders = response.data.orders;
                this.totalSales = response.data.total;
                this.totalPriceWithoutTax = response.data.total_price_without_tax;
                this.totalTaxValue = response.data.total_tax_value;
                this.totalCount = response.data.count;
            });
        }, 500),
        handleScroll() {
            if (window.innerHeight + window.scrollY + 50 >= document.documentElement.offsetHeight && !this.isLoading) {
                this.fetchMore();
            }
        },
        async fetchMore() {
            if (this.page >= this.userOrders.last_page) return;
            this.isLoading = true;
            this.page++;
            try {
                const response = await axios.get(this.route('websiteOrders', { ...this.params, page: this.page }));
                this.userOrders.data.push(...response.data.orders.data);
            } catch (e) {
                console.error(e);
            } finally {
                this.isLoading = false;
            }
        }
    },
    watch: {
        params: {
            handler() {
                this.page = 1;
                this.handleFilter();
            },
            deep: true
        },
        date: {
            handler: throttle(function () {
                this.params.date = this.date.value;
                let params = this.params;
                this.handleFilter(params)
            }),
            deep: true
        },
        start_date: {
            handler: throttle(function () {
                this.params.start_date = this.start_date.value;
                let params = this.params;
                this.handleFilter(params)
            }),
            deep: true
        },
        end_date: {
            handler: throttle(function () {
                this.params.end_date = this.end_date.value;
                let params = this.params;
                this.handleFilter(params)
            }),
            deep: true
        },
    },
    setup() {
        const start_date = ref();
        const end_date = ref();
        const date = ref();
        const buyer = ref();

        const handleDate = (date) => {
            if(date){
                const day = date.getDate();
                const month = date.getMonth() + 1;
                const year = date.getFullYear();

                date.value =  `${year}/${month}/${day}`;
            }
        }

        const handleStartDate = (start_date) => {
            if(start_date){
                const day = start_date.getDate();
                const month = start_date.getMonth() + 1;
                const year = start_date.getFullYear();

                start_date.value =  `${year}/${month}/${day}`;
            }
        }

        const handleEndDate = (end_date) => {
            if(end_date){
                const day = end_date.getDate();
                const month = end_date.getMonth() + 1;
                const year = end_date.getFullYear();

                end_date.value =  `${year}/${month}/${day}`;
            }
        }

        const user = computed(() => usePage().props.value.auth.user)
        return { start_date, handleStartDate, end_date, handleEndDate, date, handleDate, buyer, user }
    },
    mounted() {
        window.addEventListener('scroll', this.handleScroll);
    },
    unmounted() {
        window.removeEventListener('scroll', this.handleScroll);
    }
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
