<template>
    <app-layout title="Create Color">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Export to shop/warehouse')}}
            </h2>
        </template>

        <div>
            <div class="max-w-full mx-auto py-10 sm:px-6 lg:px-8">
                <create-user-product-information-form
                    :users="users"
                    :products="products"
                    :sizes = "sizes"
                    :currency="currency"
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
        users: Object,
        products: Object,
        sizes: Object,
        currency: Object,
    }
})
</script>
