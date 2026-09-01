<template>
    <app-layout title="Users Management">
        <div class="flex justify-around mt-8">
            <div class="flex justify-between">
                <inertia-link :href="route('users.create', {'type': type})" v-if="admin.role === 1 || type === '4' || type === '5' || type ==='6'">
                    <jet-button class="px-16 mt-8 bg-pcr float-right">
                        {{ __('Add new account')}}
                    </jet-button>
                </inertia-link>
            </div>
            <div class="mt-8 flex justify-between space-x-4">
                <div class="max-w-xs">
                    <input dir="rtl"  type="search" v-model="params.search" :placeholder="__('Search')" class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                </div>
                <h3 class="text-2xl font-bold capitalize text-primary">{{ getPluralName(type) }}</h3>
            </div>
        </div>
          <MeeTable :tableTitle="'All Products'" dir="rtl">
          <div v-if="allUsers.data.length === 0" class="my-40 flex items-center justify-center text-xl font-bold text-error">
              <span class="text-center">لا يوجد حسابات!</span>
          </div>
          <table v-else class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr class="shadow-2xl py-4">
                  <th scope="col" class="px-6 py-3 text-right font-semibold text-lg text-pcr tracking-wider cursor-pointer" @click="sort('id')">
                       {{__('Name')}} {{ getSingleName(type) }}
                      <vue-feather :type="'chevron-up'" stroke-width="2" class="p-1 h-4 w-8 place-self-center inline-block" v-if="params.field==='id' &&  params.direction==='asc'"></vue-feather>
                      <vue-feather :type="'chevron-down'" stroke-width="2" class="p-1 h-4 w-8 place-self-center inline-block" v-if="params.field==='id' &&  params.direction==='desc'"></vue-feather>
                  </th>
                  <th scope="col" class="px-6 py-3 text-right font-semibold text-lg text-pcr tracking-wider cursor-pointer" v-if="type === '1' || type=== '2' || type === '3'">
                      {{ __('Email')}}
                  </th>
                  <th scope="col" class="px-6 py-3 text-right font-semibold text-lg text-pcr tracking-wider cursor-pointer" v-if="type === '4'">
                      {{ __('Phone Number')}}
                  </th>
                  <th scope="col" class="px-6 py-3 text-right font-semibold text-lg text-pcr tracking-wider cursor-pointer" v-if="type === '4'">
                      {{ __('Address')}}
                  </th>
                  <th scope="col" class="px-6 py-3 text-right font-semibold text-lg text-pcr tracking-wider cursor-pointer" v-if="type === '3' || type=== '2' || type=== '5'">
                      <p v-if="type=== '5'">{{ __('Deserved amount') }}</p>
                      <p v-else>{{ __('Sales Fund')}}</p>
                  </th>
                  <th scope="col" class="px-6 py-3 text-right font-semibold text-lg text-pcr tracking-wider cursor-pointer" v-if="type === '3' || type=== '2'">
                      {{ __('Stock')}}
                  </th>
                  <th scope="col" class="px-6 py-3 text-right font-semibold text-lg text-pcr tracking-wider cursor-pointer" v-if="type === '4'">
                      {{ __('Debit')}}
                  </th>
                  <th scope="col" class="px-6 py-3 text-right font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                      {{ __('Edit')}}
                  </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200 text-pcr" dir="rtl">
              <tr v-for="user in allUsers.data" :key="user.id" class="font-sans-latin text-sm font-medium">
                  <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                      <div class="flex items-center">
                          <div class="flex-shrink-0 h-10 w-10 rounded-full" >
                          </div>
                          <div class="ml-4">
                              <div class="text-sm font-medium">{{ user.name }}</div>
                          </div>
                      </div>
                  </td>
                  <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7" v-if="user.role_id === 1 || user.role_id === 2 || user.role_id === 3">
                      <div class="ml-4">
                          <div class="text-sm font-medium">{{ user.email }}</div>
                      </div>
                  </td>
                  <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7" v-if="user.role_id === 4">
                      <div class="ml-4">
                          <div class="text-sm font-medium">{{ user.phone }}</div>
                      </div>
                  </td>
                  <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7" v-if="user.role_id === 4">
                      <div class="ml-4">
                          <div class="text-sm font-medium">{{ user.address }}</div>
                      </div>
                  </td>
                  <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7" v-if="user.role_id === 3 || user.role_id === 2 || user.role_id === 5">
                      <div class="ml-4">
                          <div class="text-sm font-medium">{{ parseInt(user.wallet?.credit * rate) }}</div>
                      </div>
                  </td>
                  <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7" v-if="user.role_id === 2 || user.role_id === 3">
                      <div class="ml-4">
                          <div class="text-sm font-medium">{{ user.colors_count }}</div>
                      </div>
                  </td>
                  <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7" v-if="user.role_id === 4">
                      <div class="ml-4">
                          <div class="text-sm font-medium">{{ parseInt(user.wallet?.debit * rate) }}</div>
                      </div>
                  </td>
                  <td class="w-full flex items-center justify-center space-x-3 p-6" dir="ltr">
                      <inertia-link :href="route('users.edit', user.id)" class="p-2 pb-1 rounded-md text-white btn-ghost bg-blue-400 hover:bg-blue-600 hover:text-white" v-if="admin.role === 1 || user.role_id !== 3">
                          <vue-feather :type="'edit-3'" stroke-width="2"></vue-feather>
                      </inertia-link>
                      <inertia-link :href="route('orders.index', {'seller_id': user.id})" class="p-2 pb-1 rounded-md text-white btn-ghost bg-teal-400 hover:bg-teal-600 hover:text-white" v-if="admin.role === 2 && user.role_id === 3">
                          <vue-feather :type="'align-right'" stroke-width="2"></vue-feather>
                      </inertia-link>
                      <button
                          v-if="(admin.role === 1) && (user.role_id === 2 || user.role_id === 3)"
                          class="p-2 pb-1 rounded-md text-white btn-ghost bg-purple-400 hover:bg-purple-800 hover:text-white"
                          @click="closeWallet(user.id)">
                          <vue-feather :type="'inbox'" stroke-width="2"></vue-feather>
                      </button>
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
  import {debounce} from "lodash/function";

  const components = { AppLayout, MeeTable, Pagination, JetButton }

  export default {
      name: 'PortalProductsIndex',

      components,

      props: {
          users: Object,
          rate: Number,
          filters: Object,
          type: String
      },
      data() {
          return {
              params: {
                  search: this.filters.search,
                  field: this.filters.field,
                  direction: this.direction
              },
              allUsers: this.users,
          }
      },
      methods: {
          getSingleName(type){
              switch (type) {
                  case '2':
                      return 'المستودع';
                  case '3':
                      return 'المحل';
                  case '4':
                      return 'الزبون';
                  case '5':
                      return 'التاجر';
              }
          },
          getPluralName(type){
              switch (type) {
                  case '2':
                      return 'المستودعات';
                  case '3':
                      return 'المحلات';
                  case '4':
                      return 'الزبائن';
                  case '5':
                      return 'التجار';
              }
          },
          sort(field){
              this.params.field = field;
              this.params.direction = this.params.direction === 'asc' ? 'desc' : 'asc';
          },
          closeWallet(id) {
              let t = 'Are you sure?';
              let d = 'You can\'t undo this operation';
              let w = 'Yes, Close the sales';
              let c = 'Yes, Close the sales';

              this.$swal({
                  title: t,
                  text: d,
                  showCancelButton: true,
                  confirmButtonColor: '#014758',
                  cancelButtonColor: '#d33',
                  confirmButtonText: c,
                  cancelButtonText: 'إلغاء',
                  width: 400,
                  padding: '1em',
                  color: '#014758',
              }).then((result) => {
                  if (result.isConfirmed) {
                      this.$swal.fire({
                          html: '<p class="text-white pt-5 font-extrabold">'+ w+'</p>',
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
                      this.$inertia.get(route('users.wallet.close', id))
                  }
              })

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
                  this.params['type'] = this.type;

                  axios.get(this.route('users.index', this.params)).then(response => {
                      this.allUsers = {
                          ...response.data,
                          data: [...response.data.data]
                      }
                  });
              }),
              deep: true
          }
      },
      setup() {
          const admin = computed(() => usePage().props.value.auth.user)
          return { admin }
      },
      mounted() {
          window.addEventListener('scroll', debounce((e) => {
              let pixelsFromBottom = document.documentElement.offsetHeight - document.documentElement.scrollTop - window.innerHeight;

              this.params['type'] = this.type;

              if (pixelsFromBottom < 200){
                  axios.get(this.allUsers.next_page_url, { params: this.params }).then(response => {
                      this.allUsers = {
                          ...response.data,
                          data: [...this.allUsers.data, ...response.data.data]
                      }
                  });
              }
          }, 100))
      },
  }
  </script>
