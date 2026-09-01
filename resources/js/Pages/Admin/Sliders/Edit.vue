<template>
    <app-layout title="Create Slider">
        <template #header>
            <h2 dir="rtl" class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Slider')}}
            </h2>
        </template>

        <div>
            <div class="max-w-full mx-auto py-10 sm:px-6 lg:px-8">
                <edit-slider-information-form :slider="slider" :products="products" />

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
import EditSliderInformationForm from "@/Pages/Admin/Sliders/EditSliderInformationForm.vue";

export default defineComponent({

    components: {
        AppLayout,
        JetSectionBorder,
        LogoutOtherBrowserSessionsForm,
        TwoFactorAuthenticationForm,
        UpdatePasswordForm,
        EditSliderInformationForm
    },
    props:{
        slider: Object,
        products: Array,
    },
})
</script>
