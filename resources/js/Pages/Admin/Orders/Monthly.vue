<template>
    <app-layout title="Products Management">
        <div class="flex justify-around mt-8">




            <div class="h-1/3 border-gray-300 shadow-sm border-2 rounded-md px-2">

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
                        <button @click="filter('shop', 0)" class="w-full my-1 font-bold text-gray-600 hover:cursor-pointer text-center text-md bg-gray-50 rounded-md">كل المحلات</button>
                        <div class="border-t border-gray-100"></div>
                        <div v-for="shop in shops">
                            <p v-if="params.shop === shop.id" class="font-bold text-teal-500 hover:cursor-pointer text-center text-md bg-gray-100">{{ shop.name }}</p>
                            <button v-else @click="filter('shop', shop.id)" class="w-full my-1 font-bold text-gray-600 hover:cursor-pointer text-center text-md bg-gray-50 rounded-md">
                                {{ shop.name}}
                            </button>
                            <div class="border-t border-gray-100"></div>
                        </div>
                    </template>
                </jet-dropdown>

            </div>

            <div class="flex justify-between" v-if="user.role === 1">
                  <button @click="exportPDF()" class="btn btn-default w-full my-1 font-bold text-gray-600 hover:cursor-pointer text-center text-md bg-gray-50 rounded-md" style="background-color: #000;color: #FFF;padding-left: 8px;padding-right: 8px;height: 42px;font-size: 14px;">
                      إنشاء كشف PDF
                  </button>
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

                <div class="flex justify-center hover:cursor-pointer space-x-4 border border-gray-300 px-4 py-2 rounded-xl shadow-lg" v-bind:class="params.start_date?'pt-4':''">
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

                <div class="flex justify-center hover:cursor-pointer space-x-4 border border-gray-300 px-4 py-2 rounded-xl shadow-lg" v-bind:class="params.start_date?'pt-4':''">
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
                <label dir="rtl" class="pr-2"> إجمالي المبيع</label>
                <span class="bg-emerald-500 px-2 rounded-md text-3xl text-white">{{ Number(totalSales).toFixed(2) }}</span>
            </div>

            <div class="flex flex-col" v-if="user.role === 1">
                <label dir="rtl" class="pr-2">  الإجمالي غير  ش.ض</label>
                <span class="bg-emerald-500 px-2 rounded-md text-3xl text-white">{{ Number(totalPriceWithoutTax).toFixed(2) }}</span>
            </div>

            <div class="flex flex-col" v-if="user.role === 1">
                <label dir="rtl" class="pr-2"> إجمالي الضريبه</label>
                <span class="bg-emerald-500 px-2 rounded-md text-3xl text-white">{{ Number(totalTaxValue).toFixed(2) }}</span>
            </div>

            <div class="flex flex-col" v-if="user.role === 1">
                <label dir="rtl" class="pr-2"> إجمالي المرتجعات</label>
                <span class="bg-emerald-500 px-2 rounded-md text-3xl text-white">{{ Number(totalRefunds).toFixed(2) }}</span>
            </div>

            <div class="flex flex-col" v-if="user.role === 1">
                <label dir="rtl" class="pr-2">  عدد القطع</label>
                <span class="bg-emerald-500 px-2 rounded-md text-3xl text-white">{{totalCount}}</span>
            </div>


        </div>
          <MeeTable :tableTitle="''">
          <div v-if="userOrders.length === 0" class="my-40 flex items-center justify-center text-xl font-bold text-error">
              <span class="text-center">لا يوجد طلبيات!</span>
          </div>
          <table v-else class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr class="shadow-2xl py-4">

                  <th scope="col" class="px-6 py-3 text-center font-semibold text-sm tracking-wider cursor-pointer">
                      المحل
                  </th>

                  <th scope="col" class="px-6 py-3 text-center font-semibold text-sm tracking-wider cursor-pointer">
                      التاريخ
                  </th>

                  <th scope="col" class="px-6 py-3 text-center font-semibold text-sm tracking-wider cursor-pointer">
                    اجمالي المرتجعات
                  </th>

                  <th scope="col" class="px-6 py-3 text-center font-semibold text-sm tracking-wider cursor-pointer">
                    اجمالي عدد العناصر
                  </th>

                  <th scope="col" class="px-6 py-3 text-center font-semibold text-sm tracking-wider cursor-pointer">
                      اجمالي المبيعات بدون الضريبة
                  </th>

                  <th scope="col" class="px-6 py-3 text-center font-semibold text-sm tracking-wider cursor-pointer">
                      اجمالي الضريبة
                  </th>

                  <th scope="col" class="px-6 py-3 text-center font-semibold text-sm tracking-wider cursor-pointer">
                    اجمالي المبيعات شاملة الضريبة
                  </th>

                  <th scope="col" class="px-6 py-3 text-center font-semibold text-sm tracking-wider cursor-pointer">
                      عملة الدفع
                  </th>

              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="item in userOrders" :key="item.id" class="font-sans-latin text-sm font-medium">

                 <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                      <div class="ml-4">
                          <div class="text-sm font-medium">{{ item.shop_name }}</div>
                      </div>
                  </td>

                 <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                      <div class="ml-4">
                          <div class="text-sm font-medium">{{ item.date }}</div>
                      </div>
                  </td>

                  <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                      <div class="ml-4">
                          <div class="text-sm font-medium">{{ item.total_refund != null ? item.total_refund : 0 }}</div>
                      </div>
                  </td>

                  <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                      <div class="ml-4">
                          <div class="text-sm font-medium"> {{ item.count != null ? item.count : 0 }} </div>
                      </div>
                  </td>

                  <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                      <div class="ml-4">
                          <div class="text-sm font-medium">{{ item.price_without_tax }}</div>
                      </div>
                  </td>

                  <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                      <div class="ml-4">
                          <div class="text-sm font-medium">{{ item.tax_value != null ? item.tax_value : 0 }}</div>
                      </div>
                  </td>

                  <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                      <div class="ml-4">
                          <div class="text-sm font-medium">{{ item.total_price }}</div>
                      </div>
                  </td>


                  <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                      <div class="ml-4">
                          <div class="text-sm font-medium"> AED </div>
                      </div>
                  </td>


              </tr>
            </tbody>
          </table>
      </MeeTable>


    </app-layout>

  </template>

  <script>
  import AppLayout from '@/Layouts/AppLayout.vue'
  import { MeeTable } from '@/Shared/Ui'
  import { Pagination } from '@/Shared/Common'
  import {throttle} from "lodash";
  import { createPopper } from '@popperjs/core';
  import JetButton from '@/Jetstream/Button.vue'
  import { computed } from 'vue'
  import { usePage } from '@inertiajs/inertia-vue3'
  import JetDropdown from "@/Jetstream/Dropdown";
  import JetDropdownLink from "@/Jetstream/DropdownLink";
  import {debounce} from "lodash/function";
  import {ref} from "vue";
  import Datepicker from '@vuepic/vue-datepicker'
  import '@vuepic/vue-datepicker/dist/main.css'
  import JsonCSV from 'vue-json-csv'
  import Currency from '@/Utils/Currency.js';
  import Receipt from '@/Utils/Receipt.js';

  import JetLabel from '@/Jetstream/Label.vue'
  import Multiselect from '@suadelabs/vue3-multiselect'


  const components = { AppLayout, MeeTable, Pagination, JetButton,  JetDropdown, JetDropdownLink, Datepicker, JetLabel, Multiselect, 'download-csv': JsonCSV}

  export default {
      name: 'PortalProductsIndex',

      components,

      props: {
          orders: Object,
          filters: Object,
          shops: Array,
          buyers: Array,
          totalRefunds: Number,
          total: Number,
          total_price_without_tax: Number,
          total_tax_value: Number,
          count: Number,
          total_refund: Number,
          shop_name: String
      },
      data() {
          var ordersd = []

          this.orders.forEach(order => {
              ordersd.push({
                  'shop_name': order.shop_name,
                  'date': order.date,
                  'total_refund': order.total_refund,
                  'count': order.count,
                  'price_without_tax': order.price_without_tax,
                  'tax_value': order.tax_value,
                  'total_price': order.total_price,
              })
          });



          return {
              params: {
                  search: this.filters.search,
                  field: this.filters.field,
                  direction: this.direction,
                  shop: this.filters.shop,
                  buyer: this.filters.buyer,
                  date: this.filters.date,
                  start_date: this.filters.start_date,
                  end_date: this.filters.end_date
              },
              userOrders: this.orders,
              isLoading: false,
              showModal: false,
              item: null,
              products: null,
              totalSales: this.total,
              totalRefunds: this.totalRefunds,
              totalPriceWithoutTax: this.total_price_without_tax,
              totalTaxValue: this.total_tax_value,
              totalCount: this.count,
              json_data: ordersd,
              currencyFormat: Currency.getFormatMethod(),
              page: 2,

          }
      },
      methods: {

          handleSelect(selectedItem) {
              // Extract the ID of the selected object
              this.params['buyer'] = selectedItem.id;
              // Perform additional actions if needed

          },


          getShopName(id){
              const shopObject = this.shops.find((s) => s.id === id)
              return shopObject.name;
          },

          filter(filter, value){
              this.params[filter] = value;
          },
          exportPDF(){
              window.location.href = this.route('exportpdf2', this.params)
          },
          encodeUrlWhatsApp(id, number, ship = false){
              let url;
              if (ship)
                  url = encodeURIComponent(route('invoice.shipper.show', id));
              else
                  url = encodeURIComponent("*قم بفتح هذا الرابط للاطلاع على فاتورتك من محلات وان واي*\n"+"*Open this link to view your bill from One Way stores*\n"+route('download.invoice.show', id));
                  // url = encodeURIComponent(route('invoice.show', id));

              return `https://wa.me/${number}/?text=${url}`;
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
          sort(field){
              this.params.field = field;
              this.params.direction = this.params.direction === 'asc' ? 'desc' : 'asc';
          },

          bgColor(color){
              return {
                  'background-color': color
              };
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
          openPopover(event, tooltipID) {
              let element = event.target;
              while (element.nodeName !== "BUTTON") {
                  element = element.parentNode;
              }
              createPopper(element, document.getElementById(tooltipID), {
                  placement: 'top'
              });
              document.getElementById(tooltipID).classList.toggle("hidden");
          },
          handleFilter(params){
              Object.keys(params).forEach(key => {
                  if (params[key] == ''){
                      delete params[key]
                  }
              }, 150);

              axios.get(this.route('orders.monthly_orders', this.params)).then(response => {

                  this.totalRefunds = response.data.totalRefunds
                  this.totalSales = response.data.total
                  this.totalPriceWithoutTax = response.data.total_price_without_tax
                  this.totalTaxValue = response.data.total_tax_value
                  this.totalCount = response.data.count

                  this.userOrders = {
                      ...response.data.orders,
                      data: [...response.data.orders]
                  }

                  this.json_data = []
                  this.userOrders.data.forEach(order => {
                      this.json_data.push({
                        'shop_name': order.shop_name,
                        'date': order.date,
                        'total_refund': order.total_refund,
                        'count': order.count,
                        'price_without_tax': order.price_without_tax,
                        'tax_value': order.tax_value,
                        'total_price': order.total_price,
                      })
                  });
              });
          },

          // new code
        fetchData() {
            // Fetch data from the server and update this.items
            // Update loading state accordingly

            if(this.page <= this.userOrders.last_page ) {

                axios.get(app_url+'admin/orders/monthly/orders', { params: { params: this.params, page: this.page++ } }).then(response => {
                    this.totalRefunds = response.data.totalRefunds
                    this.totalSales = response.data.total
                    this.totalPriceWithoutTax = response.data.total_price_without_tax
                    this.totalTaxValue = response.data.total_tax_value
                    this.totalCount = response.data.count
                    this.userOrders = {
                        ...response.data.orders,
                        data: [...this.userOrders, ...response.data.orders]
                    }
                    this.json_data = []
                    this.userOrders.data.forEach(order => {
                        this.json_data.push({
                            'shop_name': order.shop_name,
                            'date': order.date,
                            'total_refund': order.total_refund,
                            'count': order.count,
                            'price_without_tax': order.price_without_tax,
                            'tax_value': order.tax_value,
                            'total_price': order.total_price,
                        })
                    });
                });


            }


        },
        handleScroll() {
            // let pixelsFromBottom = document.documentElement.offsetHeight - document.documentElement.scrollTop - window.innerHeight;
            // if (pixelsFromBottom < 50){
            if (window.innerHeight + window.scrollY + 50 >= document.documentElement.offsetHeight &&!this.loading) {
                //this.page++;
                this.fetchData();
            }
        },
      },
      watch: {
          params: {
              handler: throttle(function () {
                  let params = this.params;
                  this.handleFilter(params)
                  // this.$inertia.get(this.route('orders.monthly_orders'), this.params, { replace: true, preserveState: true});
              }),
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
          const user = computed(() => usePage().props.value.auth.user)

          const start_date = ref();
          const end_date = ref();
          const date = ref();

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

          return { user, start_date, handleStartDate, end_date, handleEndDate, date, handleDate }
      },
      mounted() {

            this.fetchData();
            window.addEventListener('scroll', this.handleScroll);

      },
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

  table th , table td {
    text-align: center !important
  }

  </style>
