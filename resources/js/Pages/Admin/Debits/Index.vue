<template>
  <app-layout title="Products Management">
      <div class="mt-8 flex justify-around space-x-4">
          <h3 class="text-2xl font-bold capitalize text-primary">ديون للتجار</h3>
          <div class="max-w-xs">
              <input dir="rtl" v-if="false" type="search" v-model="params.search" placeholder="بحث..." class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
          </div>
          <div class="border-gray-300 shadow-sm border-2 rounded-md px-2" v-if="admin.role === 1">
              <jet-dropdown align="right" width="48">
                  <template #trigger>
                      <button type="button" class="inline-flex items-center px-1 py-2 border border-transparent  rounded-md  focus:outline-none transition">
                          <p v-if="params.shop">{{ getShopName(params.shop) }}</p>
                          <p v-else>بحث بحسب المحل</p>
                      </button>
                  </template>
                  <template #icon v-if="params.shop">
                      <vue-feather class="hover:cursor-pointer mt-2 text-pcr-mid" @click="filter('shop', 0)" :type="'x'"></vue-feather>
                  </template>
                  <template #content>
                      <jet-dropdown-link @click="filter('shop', 0)">كل المحلات</jet-dropdown-link>
                      <div class="border-t border-gray-100"></div>
                      <div v-for="shop in shops">
                          <p v-if="params.shop === shop.id" class="font-bold text-teal-500 hover:cursor-pointer text-center text-md bg-gray-100">{{ shop.name }}</p>
                          <jet-dropdown-link v-else @click="filter('shop', shop.id)">{{ shop.name}}</jet-dropdown-link>
                          <div class="border-t border-gray-100"></div>
                      </div>
                  </template>
              </jet-dropdown>
          </div>
      </div>

      <MeeTable :tableTitle="'All Products'">
        <div v-if="debits.data.length === 0" class="my-40 flex items-center justify-center text-xl font-bold text-error">
            <span class="text-center">لا يوجد ديون للتجار!</span>
        </div>
        <table v-else class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr class="shadow-2xl py-4">
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer" v-if="admin.role === 1 || admin.role === 2">
                    التاجر
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer" @click="sort('id')">
                    رقم الدفعة
                    <vue-feather :type="'chevron-up'" stroke-width="2" class="p-1 h-4 w-8 place-self-center inline-block" v-if="params.field==='id' &&  params.direction==='asc'"></vue-feather>
                    <vue-feather :type="'chevron-down'" stroke-width="2" class="p-1 h-4 w-8 place-self-center inline-block" v-if="params.field==='id' &&  params.direction==='desc'"></vue-feather>
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    المحل/المستودع
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    سبب الدين
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    المبلغ
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    تاريخ الدين
                </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200 text-pcr">
            <tr v-for="item in debits.data" :key="item.id" class="font-sans-latin text-sm font-medium">
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7" v-if="admin.role === 1 || admin.role === 2">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ item.creditor?.name }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ item.id }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ item.debtor.name }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium" dir="rtl">{{ item.user_product_log?.note }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ item.amount }}</div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="ml-4">
                        <div class="text-sm font-medium">{{ item.date }}</div>
                    </div>
                </td>
            </tr>
          </tbody>
        </table>
        <pagination class="mt-10" :links="debits.links"></pagination>
    </MeeTable>
  </app-layout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import { MeeTable } from '@/Shared/Ui'
import { Pagination } from '@/Shared/Common'
import {throttle} from "lodash";
import JetButton from '@/Jetstream/Button.vue'
import {computed} from "vue";
import {usePage} from "@inertiajs/inertia-vue3";
import JetDropdown from '@/Jetstream/Dropdown.vue'
import JetDropdownLink from '@/Jetstream/DropdownLink.vue'

const components = { AppLayout, MeeTable, Pagination, JetButton, JetDropdown, JetDropdownLink }

export default {
    name: 'PortalProductsIndex',

    components,

    props: {
        debits: Object,
        filters: Object,
        shops: Array
    },
    data() {
        return {
            params: {
                search: this.filters.search,
                field: this.filters.field,
                shop: this.filters.shop,
                direction: this.direction
            }
        }
    },
    methods: {
        sort(field){
            this.params.field = field;
            this.params.direction = this.params.direction === 'asc' ? 'desc' : 'asc';
        },
        async addPayment(id) {
            await this.$swal({
                title: 'إضافة دفعة من التاجر',
                text: "رجاءً أدخل قيمة الدفعة",
                input: 'text',
                inputPlaceholder: 'قيمة الدفعة',
                showCancelButton: true,
                confirmButtonColor: '#014758',
                cancelButtonColor: '#d33',
                confirmButtonText: 'أضف الدفعة',
                width: 400,
                padding: '1em',
                color: '#014758',
                inputValidator: (value) => {
                    return new Promise((resolve) => {
                        if (value > 0) {
                            resolve()
                        } else {
                            resolve('يجب أن تدخل قيمة الدفعة')
                        }
                    })
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    axios.post(route('debits.payment'), {
                        debit: id,
                        amount: result.value
                    }).then((response) => {
                        if (response.data.success){
                            this.showSuccessMessage('تم إضافة قيمة الدفعة')
                            this.$inertia.get(this.route('debits.index'))
                        } else
                            this.showErrorMessage(response.data.error)
                    })
                }
            })
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
        getShopName(id){
            const shopObject = this.shops.find((s) => s.id === id)
            return shopObject.name;
        },
        filter(filter, value){
            this.params[filter] = value;
        },
        payDebit(id) {
            this.$swal({
                title: 'هل أنت متأكد؟',
                text: "لن تتمكن من التراجع عن هذه الخطوة",
                showCancelButton: true,
                confirmButtonColor: '#014758',
                cancelButtonColor: '#d33',
                confirmButtonText: 'نعم تسدد الدفعة',
                width: 400,
                padding: '1em',
                color: '#014758',
            }).then((result) => {
                if (result.isConfirmed) {
                    this.$swal.fire({
                            html: '<p class="text-white pt-5 font-extrabold">تم تسديد الدفعة بنجاح</p>',
                            icon: 'success',
                            iconColor: '#FFFFFF',
                            width: 400,
                            showConfirmButton: false,
                            padding: '1em',
                            toast: true,
                            position: 'bottom-end',
                            color: '#FFFFFF',
                            background: '#e07575',
                            timer: 2000,
                            timerProgressBar: true,
                        },
                    )
                    this.$inertia.get(route('debits.pay', id))
                }
            })

        },

    },
    watch: {
        params: {
            handler: throttle(function () {
                let params = this.params;
                Object.keys(params).forEach(key => {
                    if (params[key] == ''){
                        delete params[key]
                    }
                }, 150);
                this.$inertia.get(this.route('debits.index'), this.params, { replace: true, preserveState: true});
            }),
            deep: true
        }
    },
    setup() {
        const admin = computed(() => usePage().props.value.auth.user)
        return { admin }
    },
}
</script>
