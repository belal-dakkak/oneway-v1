<template>
    <app-layout title="Create Color">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Order Details')}}
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <div class="bg-white-800 grid grid-cols-1 md:grid-cols-2">
                    <div class="p-2">
                        {{ __('Order Barcode')}} : {{ order.barcode }}
                    </div>
                    <div class="p-2">
                        {{ __('Order Sent Date')}} : {{ order.sent_date }}
                    </div>
                </div>
                <div class="bg-white-800 grid grid-cols-1 md:grid-cols-2">
                    <div class="p-2">
                        {{ __('Order Date')}} : {{ order.date }}
                    </div>
                    <div class="p-2">
                        {{ __('Total Price')}} : {{ order.total_price }}
                    </div>
                </div>
                <div v-if="order.shipping_fee || order.cod_fee" class="bg-white-800 grid grid-cols-1 md:grid-cols-2">
                    <div class="p-2">
                        {{ __('Shipping Fee')}} : {{ order.shipping_fee }}
                    </div>
                    <div class="p-2">
                        {{ __('COD Fee')}} : {{ order.cod_fee }}
                    </div>
                </div>
                <div class="bg-white-800 grid grid-cols-1 md:grid-cols-2">
                    <div class="p-2">
                        {{ __('Client Name')}} : {{ buyer.name }}
                    </div>
                    <div class="p-2">
                        {{ __('Client Number')}} : {{ buyer.phone }}
                    </div>
                </div>
                <div v-if="order.discount" class="bg-white-800 grid grid-cols-1 md:grid-cols-2">
                    <div class="p-2">
                        {{ __('Total Price Before Discount')}} : {{ order.total_price_before_discount }}
                    </div>
                    <div class="p-2">
                        {{ __('Discount')}} : {{ order.discount }}
                    </div>
                </div>
                <div class="bg-white-800 grid grid-cols-1 md:grid-cols-2">
                    <div class="p-2">
                        {{ __('Cash or Credit')}} : {{ buyer.invoice ?  __('credit') : __('cash') }}
                    </div>
                    <div class="p-2">
                        {{ __('Tap code')}} : {{ buyer.invoice ?? __('cash') }}
                    </div>
                </div>

                <div class="bg-white-800 grid grid-cols-1 md:grid-cols-2">
                    <div class="p-2">
                        {{ __('City')}} : {{ city ? city.name : '' }}
                    </div>
                    <div class="p-2">
                        {{ __('Address')}} : {{ address ? address.address : '' }}
                    </div>
                </div>

                <div v-if="address" class="bg-white-800 grid grid-cols-1 md:grid-cols-2">
                    <div class="p-2">
                        {{ __('Apartment')}} : {{ address.apartment }}
                    </div>
                    <div class="p-2">
                        {{ __('Building')}} : {{ address.building }}
                    </div>
                </div>

                <div v-if="address.label" class="bg-white-800 grid grid-cols-1 md:grid-cols-2">
                    <div class="p-2">
                        {{ __('Label')}} : {{ address.label }}
                    </div>
                    <div class="p-2">
                        {{ __('Phone')}} : {{ address.phone }}
                    </div>
                </div>


                <br>
                <hr>
                <br>

                <div class="bg-white-800 p-4">
                    <h3 class="text-lg font-semibold mb-4">{{ __('Change Order Status') }}</h3>
                    <div class="flex gap-2">
                        <button @click="changeStatusTo(2)" class="p-2 bg-blue-500 text-white rounded hover:bg-blue-600 flex items-center gap-2">
                            <vue-feather :type="'truck'" class="w-4 h-4"></vue-feather>
                            <span>{{ __('Shipping') }}</span>
                        </button>
                        <button @click="changeStatusTo(3)" class="p-2 bg-green-500 text-white rounded hover:bg-green-600 flex items-center gap-2">
                            <vue-feather :type="'check-circle'" class="w-4 h-4"></vue-feather>
                            <span>{{ __('Delivered') }}</span>
                        </button>
                        <button @click="changeStatusTo(1)" class="p-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 flex items-center gap-2">
                            <vue-feather :type="'clock'" class="w-4 h-4"></vue-feather>
                            <span>{{ __('Pending') }}</span>
                        </button>
                    </div>
                </div>

                <div class="bg-white-800 p-4 mt-4">
                    <h3 class="text-lg font-semibold mb-4">{{ __('Print / PDF Actions') }}</h3>
                    <div class="flex gap-2">
                        <a target="_blank" :href="route('download.invoice.show', order.id)" class="p-2 bg-teal-500 text-white rounded hover:bg-teal-600 flex items-center gap-2 shadow-sm">
                            <vue-feather :type="'share'" class="w-4 h-4"></vue-feather>
                            <span>{{ __('Download PDF') }}</span>
                        </a>
                        <a target="_blank" :href="route('invoice.show', order.id)" class="p-2 bg-teal-500 text-white rounded hover:bg-teal-600 flex items-center gap-2 shadow-sm">
                            <vue-feather :type="'cast'" class="w-4 h-4"></vue-feather>
                            <span>{{ __('View Invoice') }}</span>
                        </a>
                        <a target="_blank" :href=" ('/invoice/print-v2/'+order.id) " class="p-2 bg-teal-500 text-white rounded hover:bg-teal-600 flex items-center gap-2 shadow-sm">
                            <vue-feather :type="'printer'" class="w-4 h-4"></vue-feather>
                            <span>{{ __('Print Invoice') }}</span>
                        </a>
                    </div>
                </div>

                <br>
                <hr>
                <br>

                <div v-for="item in items" class="bg-white-800 grid grid-cols-1 md:grid-cols-5">
                    <div class="p-2">
                        {{ item.name}}
                    </div>
                    <div class="p-2">
                        {{ item.size }}
                    </div>
                    <div class="p-2">
                        {{ item.item_price}}
                    </div>
                    <div class="p-2">
                        {{ item.qty}}X
                    </div>
                    <div class="p-2">
                        {{ item.total_price}}
                    </div>
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
import axios from 'axios'

export default defineComponent({
    components: {
        AppLayout,
        JetSectionBorder
    },
    props: {
        order: Object,
        address: Object,
        city: Object,
        buyer: Object,
        items: Array
    },
    methods: {
        async changeStatusTo(status) {
            try {
                const response = await axios.post(this.route('orders.websiteOrders.changeStatus', { id: this.order.id }), { status: status });
                this.order.status = response.data.status;
                this.order.status_label = response.data.status_label;
            } catch (e) {
                console.error('Error changing status', e);
            }
        }
    }
})
</script>
