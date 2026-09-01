<template>
    <app-layout title="Create Order">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                إنشاء قصة
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <form class="w-full" @submit.prevent v-on:submit.prevent>

                    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8 flex justify-around">

                        <div class="w-full px-6">
                            <jet-label for="total" value="المجموع" dir="rtl" />
                            <jet-input ref="total" dir="rtl" id="total" type="text" class="mt-1 block w-full" v-model="form.total" autocomplete="total" />
                            <jet-input-error :message="form.errors.total" class="mt-2" />
                        </div>

                        <div class="w-full px-6">
                            <jet-label for="size" value="الطول" dir="rtl" />
                            <jet-input ref="size" dir="rtl" id="size" type="text" class="mt-1 block w-full" v-model="form.size" autocomplete="size" />
                            <jet-input-error :message="form.errors.size" class="mt-2" />
                        </div>

                        <div class="w-full px-6">
                            <jet-label for="name" value="الصنف" dir="rtl" />
                            <jet-input ref="name" dir="rtl" id="name" type="text" class="mt-1 block w-full" v-model="form.name" autocomplete="name" />
                            <jet-input-error :message="form.errors.name" class="mt-2" />
                        </div>

                    </div>
                    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8 flex justify-around">

                        <div class="w-full px-6">
                            <jet-label for="date" value="تاريخ القصة" dir="rtl" />
                            <jet-input ref="date" dir="rtl" id="date" type="date" class="mt-1 block w-full" v-model="form.date" autocomplete="date" />
                            <jet-input-error :message="form.errors.date" class="mt-2" />
                        </div>

                        <div class="w-full px-6" dir="rtl">
                            <jet-label for="sizes" value="الأحجام" />
                            <Multiselect @tag="addSize" v-model="form.sizes" :options="form.sizesOptions" :multiple="true" :taggable="true" :close-on-select="false" placeholder="اختر الاحجام" label="name"
                                         track-by="id" />
                            <jet-input-error :message="form.errors.selected_sizes" class="mt-2" />
                        </div>

                        <div class="w-full px-6" dir="rtl">
                            <jet-label for="colors" value="الألوان" />
                            <Multiselect @tag="addColor" v-model="form.colors" :options="form.colorsOptions" :multiple="true" :taggable="true" :close-on-select="false" placeholder="اختر الاحجام" label="name"
                                         track-by="id" />
                            <jet-input-error :message="form.errors.selected_colors" class="mt-2" />
                        </div>

                    </div>

                    <div>
                        <div class="w-full px-6">
                            <div>
                                <MeeFile
                                    :title="'اسحب الملف لإضافة صورة رئيسية'"
                                    :name="'image'"
                                    v-model="form.image"
                                />
                            </div>
                        </div>
                    </div>
                    <jet-section-border />

                    <jet-button :type="'button'" @click="createCutSimple">
                        حفظ
                    </jet-button>
                </form>
            </div>
        </div>
    </app-layout>

</template>

<script>

import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import JetSectionBorder from '@/Jetstream/SectionBorder.vue'
import JetLabel from '@/Jetstream/Label.vue'
import Multiselect from "@suadelabs/vue3-multiselect"
import JetInput from '@/Jetstream/Input'
import JetInputError from '@/Jetstream/InputError'
import JetButton from '@/Jetstream/Button.vue'
import {MeeForm, MeeFile} from "@/Shared/Ui";
import {computed} from "vue";
import {usePage} from "@inertiajs/inertia-vue3";

export default defineComponent({
    components: {
        AppLayout,
        JetSectionBorder,
        JetLabel,
        JetInput,
        JetInputError,
        Multiselect,
        MeeForm,
        JetButton,
        MeeFile
    },
    data() {
        return {
            form: this.$inertia.form({
                _method: 'POST',
                size: '',
                name: '',
                total: '',
                date: '',
                image: null,
                sizesOptions: [],
                sizes: [],
                colorsOptions: [],
                colors: [],
            }),

        }
    },

    methods: {
        createCutSimple() {

            if (this.$refs.image) {
                this.form.image = this.$refs.image.files[0]
            }

            this.form.post(route('cuts.store'), {
                errorBag: 'createCutSimple',
                preserveScroll: true
            });
        },
        addSize (newTag) {
            const tag = {
                name: newTag,
            }
            this.form.sizesOptions.push(tag)
            this.form.sizes.push(tag)
        },
        addColor (newTag) {
            const tag = {
                name: newTag,
            }
            this.form.colorsOptions.push(tag)
            this.form.colors.push(tag)
        },
    },
    setup() {
        const admin = computed(() => usePage().props.value.auth.user)
        return { admin }
    }
})
</script>
<style scoped>
.multiselect__input{
    padding: 0 !important;
}
</style>
