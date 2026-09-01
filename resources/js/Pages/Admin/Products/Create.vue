<template>
    <app-layout title="Create Product">
        <div>
            <div class="max-w-full mx-auto py-10 sm:px-6 lg:px-8">
                <create-product-information-form
                    :categories="categories"
                    :colors="colors"
                    :sizes="sizes"
                />

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
import CreateProductInformationForm from "@/Pages/Admin/Products/CreateProductInformationForm";

export default defineComponent({

    components: {
        AppLayout,
        JetSectionBorder,
        LogoutOtherBrowserSessionsForm,
        TwoFactorAuthenticationForm,
        UpdatePasswordForm,
        CreateProductInformationForm
    },
    props: {
        categories: Array,
        colors: Array,
        sizes: Array,
    }
})
</script>
