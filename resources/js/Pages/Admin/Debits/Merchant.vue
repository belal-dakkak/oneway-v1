<template>
    <app-layout title="Create Color">
        <div class="px-16 mt-8 flex flex-col">
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
                  {{ debit.amount }}
            </span>

            <div class="flex flex-row justify-around mt-4" dir="rtl" v-for="payment in payments">
                <div class="basis-1/2 rounded-2xl text-pcr p-8 pcr-shadow text-left">
                    <div class="flex justify-between py-4 border-b">
                        <p class="font-bold">الكمية المدفوعة</p>
                        <p>{{ payment.amount }}</p>
                    </div>
                    <div class="flex justify-between py-4 border-b">
                        <p class="font-bold">التاريخ</p>
                        <p>{{ payment.date }}</p>
                    </div>

                </div>
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
        payments: Array,
        debit: Object,
        debtor: Object,
        creditor: Object,
    }
})
</script>
