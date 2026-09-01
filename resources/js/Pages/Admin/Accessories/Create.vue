<template>
    <app-layout title="Create Order">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                إضافة إكسسوار
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <form class="w-full" @submit.prevent v-on:submit.prevent>

                    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8 flex justify-around">

                        <div class="w-full px-6">
                            <jet-label for="color" value="اللون" dir="rtl" />
                            <jet-input ref="color" dir="rtl" id="color" type="text" class="mt-1 block w-full" v-model="form.color" autocomplete="color" />
                            <jet-input-error :message="form.errors.color" class="mt-2" />
                        </div>

                        <div class="w-full px-6">
                            <jet-label for="count" value="العدد" dir="rtl" />
                            <jet-input ref="count" dir="rtl" id="count" type="number" class="mt-1 block w-full" v-model="form.count" autocomplete="count" />
                            <jet-input-error :message="form.errors.count" class="mt-2" />
                        </div>

                        <div class="w-full px-6">
                            <jet-label for="name" value="الصنف" dir="rtl" />
                            <jet-input ref="name" dir="rtl" id="name" type="text" class="mt-1 block w-full" v-model="form.name" autocomplete="name" />
                            <jet-input-error :message="form.errors.name" class="mt-2" />
                        </div>


                        <div class="w-full px-4" dir="rtl">
                            <jet-label for="user" value="المستودع" />
                            <Multiselect ref="user" v-model="form.user" :options="users" :multiple="false" :close-on-select="true" placeholder="اختر مستودع من قائمة المستودعات" label="name"
                                         track-by="id" />
                            <jet-input-error :message="form.errors.user" class="mt-2" />
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

                    <jet-button :type="'button'" @click="createAccessorySimple">
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
    props:{
      users: Object
    },
    data() {
        return {
            form: this.$inertia.form({
                _method: 'POST',
                name: '',
                color: '',
                count: '',
                user: null,
                image: null,
            }),

        }
    },

    methods: {
        createAccessorySimple() {

            if (this.$refs.image) {
                this.form.image = this.$refs.image.files[0]
            }

            this.form.post(route('accessories.store'), {
                errorBag: 'createAccessorySimple',
                preserveScroll: true
            });
        }
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
