<template>
    <app-layout title="Create Category">
        <template #header>
            <h2 dir="rtl" class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Category')}}
            </h2>
        </template>

        <div>
            <div class="max-w-full mx-auto py-10 sm:px-6 lg:px-8">
                <edit-category-information-form :category="category" />

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
import EditCategoryInformationForm from "@/Pages/Admin/Categories/EditCategoryInformationForm.vue";

export default defineComponent({

    components: {
        AppLayout,
        JetSectionBorder,
        LogoutOtherBrowserSessionsForm,
        TwoFactorAuthenticationForm,
        UpdatePasswordForm,
        EditCategoryInformationForm
    },
    props:{
        category: Object
    }
})
</script>
