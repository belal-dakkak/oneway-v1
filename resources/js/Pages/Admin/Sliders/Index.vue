<template>
  <app-layout title="Slider Management">
      <div class="flex justify-around mt-8">
          <div class="flex justify-between">
              <inertia-link :href="route('sliders.create')">
                  <jet-button class="px-16 mt-8 bg-pcr float-right">
                      {{ __('Add Slider')}}
                  </jet-button>
              </inertia-link>
          </div>
      </div>
    <MeeTable :tableTitle="'All Products'">
        <div v-if="sliders.data.length === 0" class="my-40 flex items-center justify-center text-xl font-bold text-error">
            <span class="text-center">{{ __('No Sliders')}}</span>
        </div>
        <table v-else class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr class="shadow-2xl py-4">
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer" @click="sort('id')">
                    {{ __('Slider')}}
                </th>
                <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                    {{ __('Actions')}}
                </th>
            </tr>

          </thead>
          <tbody class="bg-white divide-y divide-gray-200 text-pcr">

            <tr v-for="slider in sliders.data" :key="slider.id" class="font-sans-latin text-sm font-medium">
                <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="flex items-center">
                        <div class="ml-4">
                            <img class="rounded-lg w-16" :src="slider.image_url" alt="">
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium">{{ slider.position }}</div>
                        </div>
                    </div>
                </td>

                <td class="mx-auto max-w-sm p-2 text-sm leading-6 sm:text-base sm:leading-7">
                    <div class="flex items-center">
                        <inertia-link :href="route('sliders.edit', slider.id)" class="p-2 m-1 pt-4 rounded-md text-white btn-ghost bg-teal-400 hover:bg-teal-600 hover:text-white">
                            <vue-feather :type="'edit'" stroke-width="2"></vue-feather>
                        </inertia-link>
                        <div @click="deleteSlider(slider.id)" class="p-2 m-1 pt-4 rounded-md cursor-pointer text-white btn-ghost bg-rose-400 hover:bg-rose-600 hover:text-white">
                            <vue-feather :type="'trash'" stroke-width="2"></vue-feather>
                        </div>
                    </div>
                </td>

            </tr>
          </tbody>
        </table>
        <pagination class="mt-10" :links="sliders.links"></pagination>
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
    name: 'PortalProductsIndex',

    components,

    props: {
        sliders: Object,
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
        deleteSlider(id) {
            this.$swal({
                title: 'هل أنت متأكد؟',
                text: "لن تتمكن من التراجع عن هذه الخطوة",
                showCancelButton: true,
                confirmButtonColor: '#014758',
                cancelButtonColor: '#d33',
                confirmButtonText: 'نعم احذف الصنف',
                width: 400,
                padding: '1em',
                color: '#014758',
            }).then((result) => {
                if (result.isConfirmed) {
                    this.$swal.fire({
                        html: '<p class="text-white pt-5 font-extrabold">تم حذف الصنف بنجاح</p>',
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
                    this.$inertia.delete(route('sliders.destroy', id))
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
                this.$inertia.get(this.route('sliders.index'), this.params, { replace: true, preserveState: true});
            }),
            deep: true
        }
    }
}
</script>
