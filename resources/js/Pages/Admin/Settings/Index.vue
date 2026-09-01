
    <template>
        <app-layout title="Settings Management">
            <MeeTable :tableTitle="'All Products'">

                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr class="shadow-2xl py-4">
                            <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer" @click="sort('id')">
                                {{ __('Language')}}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left font-semibold text-lg text-pcr tracking-wider cursor-pointer">
                                {{ __('Actions')}}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 text-pcr">
                    <tr class="font-sans-latin text-sm font-medium">
                        <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                            <div class="flex items-center">
                                <div class="ml-4">
                                    <div class="text-sm font-medium">{{ __('Arabic')}}</div>
                                </div>
                            </div>
                        </td>
                        <td class="mx-auto max-w-sm p-2 text-sm leading-6 sm:text-base sm:leading-7">
                            <div class="flex items-center">
                                <inertia-link :href="route('settings.edit', 'ar')" class="p-2 m-1 pt-4 rounded-md text-white btn-ghost bg-teal-400 hover:bg-teal-600 hover:text-white">
                                    <vue-feather :type="'edit'" stroke-width="2"></vue-feather>
                                </inertia-link>
                            </div>
                        </td>
                    </tr>
                    <tr class="font-sans-latin text-sm font-medium">
                        <td class="mx-auto max-w-sm p-6 text-sm leading-6 sm:text-base sm:leading-7">
                            <div class="flex items-center">
                                <div class="ml-4">
                                    <div class="text-sm font-medium">{{ __('English')}}</div>
                                </div>
                            </div>
                        </td>
                        <td class="mx-auto max-w-sm p-2 text-sm leading-6 sm:text-base sm:leading-7">
                            <div class="flex items-center">
                                <inertia-link :href="route('settings.edit', 'en')" class="p-2 m-1 pt-4 rounded-md text-white btn-ghost bg-teal-400 hover:bg-teal-600 hover:text-white">
                                    <vue-feather :type="'edit'" stroke-width="2"></vue-feather>
                                </inertia-link>
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

  const components = { AppLayout, MeeTable, Pagination, JetButton }

  export default {
      name: 'PortalProductsIndex',
      components,

      props: {
        data: Object // Define the prop to accept an object
      },


      methods: {
          sort(field){
              this.params.field = field;
              this.params.direction = this.params.direction === 'asc' ? 'desc' : 'asc';
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


  }
  </script>
