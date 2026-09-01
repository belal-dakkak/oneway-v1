<template>
  <app-layout title="Branch Management">
      <div class="flex justify-around mt-8">
          <div class="flex justify-between">
              <inertia-link :href="route('branches.create')">
                  <jet-button class="px-16 mt-8 bg-pcr float-right">
                      {{ __('Add Branch')}}
                  </jet-button>
              </inertia-link>
          </div>
          <div class="mt-8 flex justify-between space-x-4">
              <div class="max-w-xs">
                  <input dir="rtl"  type="search" v-model="params.search" :placeholder="__('Search')" class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
              </div>
              <h3 class="text-2xl font-bold capitalize text-primary">{{ __('Branches')}}</h3>
          </div>
      </div>
    <MeeTable :tableTitle="'All Branches'">
        <div v-if="branches.data.length === 0" class="my-40 flex items-center justify-center text-xl font-bold text-error">
            <span class="text-center">{{ __('No Branches')}}</span>
        </div>
        <table v-else class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr class="shadow-2xl py-4">
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer" @click="sort('name_ar')">
                    {{ __('Branch AR')}}
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer" @click="sort('name_en')">
                    {{ __('Branch EN')}}
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer" @click="sort('address_ar')">
                    {{ __('Address AR')}}
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer" @click="sort('address_en')">
                    {{ __('Address EN')}}
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    {{ __('Latitude')}}
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    {{ __('Longitude')}}
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    {{ __('Actions')}}
                </th>
            </tr>

          </thead>
          <tbody class="bg-white divide-y divide-gray-200 text-pcr">

            <tr v-for="branch in branches.data" :key="branch.id" class="font-sans-latin text-sm font-medium">
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="flex items-center">
                        <div class="ml-4">
                            <div class="text-sm font-medium">{{ branch.name_ar }}</div>
                        </div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="flex items-center">
                        <div class="ml-4">
                            <div class="text-sm font-medium">{{ branch.name_en }}</div>
                        </div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="flex items-center">
                        <div class="ml-4">
                            <div class="text-sm font-medium">{{ branch.address_ar }}</div>
                        </div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="flex items-center">
                        <div class="ml-4">
                            <div class="text-sm font-medium">{{ branch.address_en }}</div>
                        </div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="flex items-center">
                        <div class="ml-4">
                            <div class="text-sm font-medium">{{ branch.latitude }}</div>
                        </div>
                    </div>
                </td>
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="flex items-center">
                        <div class="ml-4">
                            <div class="text-sm font-medium">{{ branch.longitude }}</div>
                        </div>
                    </div>
                </td>

                <td class="mx-auto max-w-sm p-2 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="flex items-center">
                        <inertia-link :href="route('branches.edit', branch.id)" class="p-2 m-1 pt-4 rounded-md text-white btn-ghost bg-teal-400 hover:bg-teal-600 hover:text-white">
                            <vue-feather :type="'edit'" stroke-width="2"></vue-feather>
                        </inertia-link>
                        <div @click="deleteBranch(branch.id)" class="p-2 m-1 pt-4 rounded-md cursor-pointer text-white btn-ghost bg-rose-400 hover:bg-rose-600 hover:text-white">
                            <vue-feather :type="'trash'" stroke-width="2"></vue-feather>
                        </div>
                    </div>
                </td>

            </tr>
          </tbody>
        </table>
        <pagination class="mt-10" :links="branches.links"></pagination>
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

const components = { AppLayout, MeeTable, Pagination, JetButton }

export default {
    name: 'PortalBranchesIndex',

    components,

    props: {
        branches: Object,
        filters: Object
    },
    data() {
        return {
            params: {
                search: this.filters.search,
                field: this.filters.field,
                direction: this.direction
            }
        }
    },
    methods: {
        sort(field){
            this.params.field = field;
            this.params.direction = this.params.direction === 'asc' ? 'desc' : 'asc';
        },
        deleteBranch(id) {
            this.$swal({
                title: 'هل أنت متأكد؟',
                text: "لن تتمكن من التراجع عن هذه الخطوة",
                showCancelButton: true,
                confirmButtonColor: '#014758',
                cancelButtonColor: '#d33',
                confirmButtonText: 'نعم احذف الفرع',
                width: 400,
                padding: '1em',
                color: '#014758',
            }).then((result) => {
                if (result.isConfirmed) {
                    this.$swal.fire({
                        html: '<p class="text-white pt-5 font-extrabold">تم حذف الفرع بنجاح</p>',
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
                    this.$inertia.delete(route('branches.destroy', id))
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
                this.$inertia.get(this.route('branches.index'), this.params, { replace: true, preserveState: true});
            }),
            deep: true
        }
    }
}
</script>
