<template>
    <app-layout title="Edit Mobile Slider">
        <template #header>
            <h2 dir="rtl" class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Mobile Slider')}}
            </h2>
        </template>

        <div>
            <div class="max-w-full mx-auto py-10 sm:px-6 lg:px-8">
                <edit-mobile-slider-information-form :slider="slider" />

                <jet-section-border />
            </div>
        </div>
    </app-layout>
</template>

<script>
import { defineComponent } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import JetSectionBorder from '@/Jetstream/SectionBorder.vue'
import EditMobileSliderInformationForm from "./EditMobileSliderInformationForm";

export default defineComponent({
    components: {
        AppLayout,
        JetSectionBorder,
        EditMobileSliderInformationForm
    },
    props:{
        slider: Object,
    },
})
</script>
