<template>
    <app-layout title="Create Setting">
        <template #header>
            <h2 dir="rtl" class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Settings')}}
            </h2>
        </template>

        <div>
            <div class="max-w-full mx-auto py-10 sm:px-6 lg:px-8">
                <edit-setting-information-form :title="title" :phone="phone" :tiktok="tiktok" :facebook="facebook" :instagram="instagram" :whatsapp="whatsapp" :address="address" :email="email" :settinglanguage="settinglanguage" />
                <jet-section-border />
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
import EditSettingInformationForm from "@/Pages/Admin/Settings/EditSettingInformationForm.vue";

export default defineComponent({

    components: {
        AppLayout,
        JetSectionBorder,
        LogoutOtherBrowserSessionsForm,
        TwoFactorAuthenticationForm,
        UpdatePasswordForm,
        EditSettingInformationForm
    },
    props:{
            title: String,
            phone: String,
            tiktok: String,
            facebook: String,
            instagram: String,
            whatsapp: String,
            address: String,
            email: String,
            settinglanguage: String,
    },
})
</script>
