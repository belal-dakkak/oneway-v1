<template>
    <app-layout title="Create Mobile Slider">
        <template #header>
            <h2 dir="rtl" class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Add Mobile Slider')}}
            </h2>
        </template>

        <div>
            <div class="max-w-full mx-auto py-10 sm:px-6 lg:px-8">
                <create-mobile-slider-information-form/>

                <jet-section-border />
            </div>
        </div>
    </app-layout>
</template>

<script>
import { defineComponent } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import JetSectionBorder from '@/Jetstream/SectionBorder.vue'
import CreateMobileSliderInformationForm from "./CreateMobileSliderInformationForm";

export default defineComponent({
    components: {
        AppLayout,
        JetSectionBorder,
        CreateMobileSliderInformationForm
    },
})
</script>
