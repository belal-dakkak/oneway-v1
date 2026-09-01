<template>
  <app-layout title="Merchant Codes Management">
      <div class="flex justify-around mt-8">
          <div class="flex flex-col space-y-4">
              <div class="flex space-x-2">
                  <input type="number" v-model="generateCount" min="1" max="100" class="w-20 rounded-md border-gray-300 shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
                  <jet-button @click="generateCodes" class="px-8 bg-primary hover:bg-primary-hover">
                      {{ __('Generate Codes') }}
                  </jet-button>
              </div>
          </div>
          <div class="mt-8 flex justify-between space-x-4">
              <div class="max-w-xs">
                  <input dir="rtl" type="search" v-model="params.search" :placeholder="__('Search')" class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
              </div>
              <h3 class="text-2xl font-bold capitalize text-primary">{{ __('Merchant Codes') }}</h3>
          </div>
      </div>

    <MeeTable :tableTitle="'All Merchant Codes'">
        <div v-if="codes.data.length === 0" class="my-40 flex items-center justify-center text-xl font-bold text-error">
            <span class="text-center">{{ __('No Codes Found') }}</span>
        </div>
        <table v-else class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr class="shadow-2xl py-4">
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider">
                    {{ __('Code') }}
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider">
                    {{ __('Status') }}
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider">
                    {{ __('Created At') }}
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider">
                    {{ __('Actions') }}
                </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200 text-pcr">
            <tr v-for="code in codes.data" :key="code.id" class="font-sans-latin text-sm font-medium">
                <td class="px-6 py-4 whitespace-nowrap">
                   <span class="font-mono bg-gray-100 px-2 py-1 rounded text-lg font-bold tracking-widest">{{ code.code }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="code.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                        {{ code.is_active ? __('Active') : __('Inactive') }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ code.created_at }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium flex space-x-2 rtl:space-x-reverse">
                    <button @click="toggleStatus(code.id)" class="p-2 rounded-md text-white bg-teal-400 hover:bg-teal-600 shadow-sm" :title="code.is_active ? 'Deactivate' : 'Activate'">
                        <vue-feather :type="code.is_active ? 'pause' : 'play'" stroke-width="2" size="18"></vue-feather>
                    </button>
                    <button @click="deleteCode(code.id)" class="p-2 rounded-md text-white bg-red-400 hover:bg-red-600 shadow-sm" title="Delete">
                        <vue-feather :type="'trash-2'" stroke-width="2" size="18"></vue-feather>
                    </button>
                </td>
            </tr>
          </tbody>
        </table>
        <pagination class="mt-10" :links="codes.links"></pagination>
    </MeeTable>
  </app-layout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
import { MeeTable } from '@/Shared/Ui'
import { Pagination } from '@/Shared/Common'
import { throttle } from "lodash";
import JetButton from '@/Jetstream/Button.vue'

export default {
    name: 'MerchantCodesIndex',
    components: { AppLayout, MeeTable, Pagination, JetButton },
    props: {
        codes: Object,
        filters: Object
    },
    data() {
        return {
            generateCount: 1,
            params: {
                search: this.filters.search || ''
            }
        }
    },
    methods: {
        generateCodes() {
            this.$inertia.post(route('admin.merchantCodes.generate'), { count: this.generateCount }, {
                onSuccess: () => {
                    this.generateCount = 1;
                }
            });
        },
        toggleStatus(id) {
            this.$inertia.post(route('admin.merchantCodes.toggle', id));
        },
        deleteCode(id) {
            if (confirm('Are you sure you want to delete this code?')) {
                this.$inertia.delete(route('admin.merchantCodes.destroy', id));
            }
        }
    },
    watch: {
        params: {
            handler: throttle(function () {
                this.$inertia.get(route('admin.merchantCodes.index'), this.params, { replace: true, preserveState: true });
            }, 150),
            deep: true
        }
    }
}
</script>
