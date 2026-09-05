<template>
    <app-layout title="Create Order">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                إنشاء دفعة
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <form class="w-full" @submit="createExpenseSimple">

                    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8 flex justify-around">

                        <div class="w-full px-6" dir="rtl" v-if="admin.role === 1 || admin.role === 2">
                            <jet-label for="user" value="التاجر بحال كانت دفعة لتاجر" />
                            <Multiselect v-model="form.user" :options="users" :multiple="false" :close-on-select="true" placeholder="اختر تاجر من قائمة التجار" label="full_name"
                                         track-by="id" />
                            <jet-input-error :message="form.errors.user" class="mt-2" />
                        </div>

                        <div class="w-full px-6">
                            <jet-label for="amount" value="المبلغ المدفوع" dir="rtl" />
                            <jet-input ref="price" id="amount" type="number" min="0" step="0.01" class="mt-1 block w-full" v-model="form.amount" autocomplete="amount" />
                            <syp-equivalent :usd="form.amount" />
                            <jet-input-error :message="form.errors.amount" class="mt-2" />
                        </div>

                        <div class="w-full px-6">
                            <jet-label for="description" value="سبب الدفعة" dir="rtl" />
                            <jet-input ref="price" dir="rtl" id="description" type="text" class="mt-1 block w-full" v-model="form.description" autocomplete="description" />
                            <jet-input-error :message="form.errors.description" class="mt-2" />
                        </div>

                    </div>
                    <jet-section-border />

                    <jet-button :type="'button'" @click="createExpenseSimple">
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

import {MeeForm} from "@/Shared/Ui";
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
        JetButton
    },
    props: {
        users: Object,
    },
    data() {
        return {
            form: this.$inertia.form({
                _method: 'POST',
                amount: '',
                description: '',
                user: ''
            })
        }
    },

    methods: {
        createExpenseSimple() {
            this.form.post(route('expenses.store'), {
                errorBag: 'createExpenseSimple',
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
