<template>
    <app-layout title="Create Color">
        <div class="px-16 mt-8 flex flex-col">
            <div class="mt-4 mb-5">
                <img class="h-28 shadow-2xl m-auto w-28 rounded-3xl object-cover" :src="productColor.photo_url" :alt="product.name" />
            </div>
            <span class="lab-name text-center text-2xl font-bold py-2">
                  {{ product.name }}
            </span>

            <span class="lab-email text-center text-2xl font-bold py-2">
                  {{ productColor.barcode }}
            </span>

            <div class="flex flex-row justify-around mt-4" dir="rtl">
                <div class="basis-1/2 rounded-2xl text-pcr p-8 pcr-shadow text-left">
                    <div class="flex justify-between py-4 border-b">
                        <p class="font-bold">العدد الحالي</p>
                        <p>{{ userProduct.stock }}</p>
                    </div>
                    <div class="flex justify-between py-4 border-b">
                        <p class="font-bold">اللون</p>
                        <p>{{ productColor.color_name }}</p>
                    </div>

                </div>

                <div class="basis-1/2 rounded-2xl text-pcr p-8 pcr-shadow text-left">
                    <div class="flex justify-between py-4 border-b">
                        <p class="font-bold">تفاصيل المنتج</p>
                        <p>{{ product.details }}</p>
                    </div>
                    <div class="flex justify-between py-4 border-b">
                        <p class="font-bold">الأحجام</p>
                        <p>{{ product.raw_sizes }}</p>
                    </div>
                </div>
            </div>

            <div class="mx-16 rounded-2xl" dir="rtl">
                <ol class="relative border-l border-gray-200 dark:border-gray-700">
                    <li class="mb-10 ml-6">
                      <span class="flex absolute -left-6 justify-center items-center w-10 h-10 bg-blue-200 rounded-full ring-12 ring-white">
                          <img class="rounded-full shadow-lg" :src="log.image" alt="log image">
                      </span>
                        <div class="p-4 bg-white rounded-lg border border-gray-200 shadow-sm">
                            <div class="justify-between items-center mb-3 sm:flex">
                                <time class="mb-1 text-xs font-normal text-gray-400 sm:order-last sm:mb-0">
                                    <span>{{ log.date }}</span>
                                </time>
                                <div class="text-sm font-normal text-gray-500 lex">{{ user.name }}</div>
                            </div>
                            <div class="px-2 pb-3 text-xs font-normal text-gray-500 lex"></div>
                            <div class="p-3 text-2xl italic font-normal text-gray-500 bg-gray-50 rounded-lg border border-gray-200">{{ log.note }}</div>
                        </div>
                    </li>
                </ol>
            </div>

            <div v-if="!log.approved" class="flex justify-center">
                <inertia-link :href="route('notification.approve', log.id)" class="rounded-xl bg-teal-500 hover:bg-teal-800 text-white px-12 py-4 text-2xl">تأكيد</inertia-link>
            </div>

        </div>
    </app-layout>
</template>

<script>
import { defineComponent } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import JetSectionBorder from '@/Jetstream/SectionBorder.vue'
import LogoutOtherBrowserSessionsForm from '@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm.vue'
import TwoFactorAuthenticationForm from '@/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue'
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue'
import CreateUserProductInformationForm from "@/Pages/Admin/UserProducts/CreateUserProductInformationForm";

export default defineComponent({

    components: {
        AppLayout,
        JetSectionBorder,
        LogoutOtherBrowserSessionsForm,
        TwoFactorAuthenticationForm,
        UpdatePasswordForm,
        CreateUserProductInformationForm
    },
    props: {
        log: Object,
        user: Object,
        productColor: Object,
        product: Object,
        userProduct: Object,
    }
})
</script>
