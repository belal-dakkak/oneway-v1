<template>
    <jet-form-section @submitted="editSettingInformation">
        <template #form>
            <div class="col-span-8 sm:col-span-4" dir="rtl" >
                <jet-label for="lp" :value="__('Lebanese Lira')" />
                <jet-input id="lp" type="text" class="mt-1 block w-full" v-model="form.lp" autocomplete="lp" />
                <jet-input-error :message="form.errors.lp" class="mt-2" />
            </div>
            <div class="col-span-8 sm:col-span-4" dir="rtl" >
                <jet-label for="aed" :value="__('Dirham')" />
                <jet-input id="aed" type="text" class="mt-1 block w-full" v-model="form.aed" autocomplete="aed" />
                <jet-input-error :message="form.errors.aed" class="mt-2" />
            </div>

        </template>
        <template #actions>
            <jet-action-message :on="form.recentlySuccessful" class="mr-3">
                تم الحفظ.
            </jet-action-message>

            <jet-button :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                {{ __('Save')}}
            </jet-button>
        </template>
    </jet-form-section>
</template>

<script>
import { defineComponent } from 'vue'
import JetButton from '@/Jetstream/Button.vue'
import JetFormSection from '@/Jetstream/FormSection.vue'
import JetInput from '@/Jetstream/Input.vue'
import JetInputError from '@/Jetstream/InputError.vue'
import JetLabel from '@/Jetstream/Label.vue'
import JetActionMessage from '@/Jetstream/ActionMessage.vue'
import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'
import Multiselect from '@suadelabs/vue3-multiselect'
import { MeeFile } from "@/Shared/Ui";

export default defineComponent({
    components: {
        JetActionMessage,
        JetButton,
        JetFormSection,
        JetInput,
        JetInputError,
        JetLabel,
        JetSecondaryButton,
        Multiselect,
        MeeFile
    },
    props: {
        lp: String,
        aed: String,
    },
    data() {
        return {
            form: this.$inertia.form({
                _method: 'POST',
                lp: this.lp,
                aed: this.aed,
            }),
        }
    },

    methods: {
        editSettingInformation() {
            this.form.post(route('currencies.store'), {
                errorBag: 'editSettingInformation',
                preserveScroll: true,
                onSuccess: () => (undefined),
            });
        }
    },
})
</script>
