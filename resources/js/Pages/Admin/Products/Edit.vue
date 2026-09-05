<template>
    <app-layout title="تعديل الموديل">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Model')}}
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <div >
                    <update-product-information-form
                        :categories="categories"
                        :colors="colors"
                        :sizes="sizes"
                        :product="product"
                        :selected_sizes="selected_sizes"
                        :selected_colors="selected_colors"
                        :rate="rate"
                        :currency="currency"
                    />

                    <jet-section-border />
                </div>

            </div>
        </div>
    </app-layout>
</template>

<script>
import { defineComponent } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import DeleteUserForm from '@/Pages/Profile/Partials/DeleteUserForm.vue'
import JetSectionBorder from '@/Jetstream/SectionBorder.vue'
import LogoutOtherBrowserSessionsForm from '@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm.vue'
import TwoFactorAuthenticationForm from '@/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue'
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue'
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm.vue'
import UpdateProductInformationForm from "@/Pages/Admin/Products/UpdateProductInformationForm";

export default defineComponent({

    components: {
        AppLayout,
        DeleteUserForm,
        JetSectionBorder,
        LogoutOtherBrowserSessionsForm,
        TwoFactorAuthenticationForm,
        UpdatePasswordForm,
        UpdateProfileInformationForm,
        UpdateProductInformationForm
    },
    props: {
        product: Object,
        categories: Array,
        sizes: Array,
        new_sizes: Array,
        colors: Array,
        selected_sizes: Array,
        selected_colors: Array,
        rate: Number,
        currency: Object,
    },
})
</script>
