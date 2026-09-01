<template>
    <app-layout title="Create Color">
        <div class="px-16 mt-8 flex flex-col">
            <div class="flex justify-around flex-wrap">
                <div class="flex flex-col justify-around">
                     <span class="lab-name text-center text-2xl font-bold py-2">
                         إرسال
                    </span>
                    <div class="flex justify-around space-x-8">
                        <a target="_blank" :href="encodeUrlWhatsapp()" class="rounded-md px-3 py-2 bg-teal-400 hover:bg-teal-600 text-white">
                            إرسال عبر واتس اب
                        </a>
                    </div>
                    <div class="flex justify-around space-x-8">
                        <a target="_blank" :href="encodeUrlPDF()" class="rounded-md px-3 py-2 bg-teal-400 hover:bg-teal-600 text-white">
                          PDF
                        </a>
                    </div>
                </div>
                <div class="flex flex-col">
                    <div class="mt-4 mb-5">
                        <img class="h-28 shadow-2xl m-auto w-28 rounded-3xl object-cover" :src="'/custom/logo-icon.png'" :alt="'Belal Collection'" />
                    </div>
                    <span class="lab-name text-center text-2xl font-bold py-2">
                          {{ debtor.name }}
                    </span>
                    <span class="lab-name text-center text-2xl font-bold py-2">
                          {{ creditor.name }}
                    </span>
                    <span class="lab-email text-center text-2xl font-bold py-2">
                          {{ Math.round(debit.amount * rate).toFixed(2) }}
                    </span>
                </div>
                <div class="flex flex-col justify-around">
                     <span class="lab-name text-center text-2xl font-bold py-2">
                         بحث حسب التاريخ
                    </span>

                    <div class="flex justify-around space-x-8">
                        <div class="flex flex-col">
                            <label dir="rtl" class="pr-2">إلى تاريخ</label>
                            <div class="flex justify-center hover:cursor-pointer space-x-4 border border-gray-300 px-4 py-2 rounded-xl shadow-2xl" v-bind:class="params.start_date?'pt-4':''">
                                <Datepicker locale="ar" v-model="end_date" @update:modelValue="handleEndDate" :clearable="true">
                                    <template #trigger>
                                        <p class="clickable-text" v-if="params.end_date">
                                            {{ end_date?.value ?? params.end_date }}
                                        </p>
                                        <p v-else class="text-sm text-gray-500 font-light">اضغط لاختيار نهاية التاريخ</p>
                                    </template>
                                </Datepicker>
                                <div v-if="params.end_date">
                                    <vue-feather @click="resetEndDate" class="hover:cursor-pointer text-pcr-mid" :type="'x'"></vue-feather>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <label dir="rtl" class="pr-2">من تاريخ</label>
                            <div class="flex justify-center hover:cursor-pointer space-x-4 border border-gray-300 px-4 py-2 rounded-xl shadow-2xl" v-bind:class="params.start_date?'pt-4':''">
                                <Datepicker locale="ar" v-model="start_date" @update:modelValue="handleDate" :clearable="true">
                                    <template #trigger>
                                        <p class="clickable-text" v-if="params.start_date">
                                            {{ start_date?.value ?? params.start_date }}
                                        </p>
                                        <p v-else class="text-sm text-gray-500 font-light">اضغط لاختيار بداية التاريخ</p>
                                    </template>
                                </Datepicker>
                                <div v-if="params.start_date">
                                    <vue-feather @click="resetStartDate" class="hover:cursor-pointer text-pcr-mid" :type="'x'"></vue-feather>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <MeeTable :tableTitle="'All Products'">
                <div v-if="logs.data.length === 0" class="my-40 flex items-center justify-center text-xl font-bold text-error">
                    <span class="text-center">لا يوجد سجل!</span>
                </div>
                <table v-else class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr class="shadow-2xl py-4">
                        <th scope="col" class="p-2 text-left font-semibold text-md text-pcr tracking-wider cursor-pointer">
                            <input type="checkbox" v-model="params.checked_all" class="w-4 h-4 bg-gray-50 rounded border border-gray-300 focus:ring-3 focus:ring-teal-500 text-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-teal-500 dark:ring-offset-gray-800">
                        </th>
                        <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                            الكمية المدفوعة
                        </th>
                        <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                            التاريخ
                        </th>
                        <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                            التفاصيل
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 text-pcr">
                    <tr v-for="log in logs.data" :key="log.id" class="font-sans-latin text-sm font-medium">
                        <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                            <input type="checkbox" :value="log.id" v-model="params.checked" class="w-4 h-4 bg-gray-50 rounded border border-gray-300 focus:ring-3 focus:ring-teal-500 text-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-teal-500 dark:ring-offset-gray-800">
                        </td>
                        <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full" :style="bgColor(log.color)">
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium">
                                        <span v-if="log.color === 'green'">+</span>
                                        <span v-else>-</span>
                                        <!-- {{ currencyExchange(log.amount, rate) }} -->
                                        {{ Math.round(log.amount * rate).toFixed(2) }}

                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                            <div class="ml-4">
                                <div class="text-sm font-medium">{{ log.date }}</div>
                            </div>
                        </td>
                        <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                            <div class="ml-4">
                                <div dir="rtl" class="text-sm font-medium">{{ log.note }}</div>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <pagination class="mt-10" :links="logs.links"></pagination>
            </MeeTable>

        </div>
    </app-layout>
</template>

<script>
import { defineComponent } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {MeeTable} from "@/Shared/Ui";
import {Pagination} from "@/Shared/Common";
import JetButton from "@/Jetstream/Button";
import Datepicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import { ref } from 'vue';
import {throttle} from "lodash";
import Button from "@/Jetstream/Button";
import Currency from '@/Utils/Currency.js'

export default defineComponent({

    components: {
        Button,
        AppLayout, Datepicker,
        MeeTable, Pagination, JetButton
    },
    data() {
      return {
          params: {
              start_date: this.filters.start_date,
              end_date: this.filters.end_date,
              id: this.debit.id,
              checked: [],
              checked_all: false
          },
          currencyExchange: Currency.getExchangeMethod(),
      }
    },
    props: {
        logs: Array,
        rate: Number,
        debit: Object,
        debtor: Object,
        creditor: Object,
        filters: Object,
    },
    methods: {
        encodeUrlWhatsapp(){
            let number = this.debtor.phone;
            this.params.start_date = this.start_date?.value??"";
            this.params.end_date = this.end_date?.value??"";
            let url = encodeURIComponent("*قم بفتح هذا الرابط للاطلاع على كشف حسابك من محلات وان واي*\n"+"*Open this link to view your account statement from One Way stores*\n"+route('client.account.log', this.params));
            return `https://wa.me/${number}/?text=${url}`;
        },
        encodeUrlPDF(){
            return route('client.account.log', this.params);
        },
        bgColor(color){
            let newColor = color === 'green'?'#34d399':'#f43f5e';
            return {
                'background-color': newColor
            };
        },
        handleFilter(params){
            Object.keys(params).forEach(key => {
                if (params[key] == '' || params[key] == null){
                    delete params[key]
                }
            }, 150);
            params['id'] = this.debit.id
            this.$inertia.get(this.route('clientDebit.log', params), this.params, { replace: true, preserveState: true});
        },
        resetStartDate(){
            this.params.start_date = null;
            let params = this.params;
            // this.handleFilter(params)
        },
        resetEndDate(){
            this.params.end_date = null;
            let params = this.params;
            this.handleFilter(params)
        }
    },
    setup() {

        const start_date = ref();
        const end_date = ref();
        const handleDate = (start_date) => {
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

        return { start_date, end_date, handleDate, handleEndDate }
    },
    watch: {
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
        'params.checked_all': {
            handler: throttle(function (value) {
                if (value === false && this.params.checked.length > 0){
                    this.params.checked = [];
                    this.params.checked_all = false;
                }
            }),
            deep: true
        },
        'params.checked': {
            handler: throttle(function () {
                if (this.params.checked.length > 0)
                    this.params.checked_all = true

            }),
            deep: true
        }

    },
})
</script>
