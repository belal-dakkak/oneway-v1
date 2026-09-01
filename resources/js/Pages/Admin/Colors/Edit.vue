<template>
    <app-layout title="Create Color">
        <template #header>
            <h2 dir="rtl" class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Color')}}
            </h2>
        </template>

        <div>
            <div class="max-w-full mx-auto py-10 sm:px-6 lg:px-8">
                <edit-color-information-form :color="color" />

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
import EditColorInformationForm from "@/Pages/Admin/Colors/EditColorInformationForm.vue";

export default defineComponent({

    components: {
        AppLayout,
        JetSectionBorder,
        LogoutOtherBrowserSessionsForm,
        TwoFactorAuthenticationForm,
        UpdatePasswordForm,
        EditColorInformationForm
    },
    props:{
        color: Object
    }
})
</script>
