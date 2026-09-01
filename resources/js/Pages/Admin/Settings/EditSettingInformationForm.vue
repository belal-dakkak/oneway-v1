<template>
    <jet-form-section @submitted="editSettingInformation">
        <template #form>
            <div class="col-span-8 sm:col-span-4" dir="rtl" >
                <jet-label for="title" :value="__('Title')" />
                <jet-input id="title" type="text" class="mt-1 block w-full" v-model="form.title" autocomplete="title" />
                <jet-input-error :message="form.errors.title" class="mt-2" />
            </div>
            <div class="col-span-8 sm:col-span-4" dir="rtl" >
                <jet-label for="phone" :value="__('Phone')" />
                <jet-input id="phone" type="text" class="mt-1 block w-full" v-model="form.phone" autocomplete="phone" />
                <jet-input-error :message="form.errors.phone" class="mt-2" />
            </div>
            <div class="col-span-8 sm:col-span-4" dir="rtl" >
                <jet-label for="tiktok" :value="__('Tiktok')" />
                <jet-input id="tiktok" type="text" class="mt-1 block w-full" v-model="form.tiktok" autocomplete="tiktok" />
                <jet-input-error :message="form.errors.tiktok" class="mt-2" />
            </div>
            <div class="col-span-8 sm:col-span-4" dir="rtl" >
                <jet-label for="facebook" :value="__('Facebook')" />
                <jet-input id="facebook" type="text" class="mt-1 block w-full" v-model="form.facebook" autocomplete="facebook" />
                <jet-input-error :message="form.errors.facebook" class="mt-2" />
            </div>
            <div class="col-span-8 sm:col-span-4" dir="rtl" >
                <jet-label for="instagram" :value="__('Instagram')" />
                <jet-input id="instagram" type="text" class="mt-1 block w-full" v-model="form.instagram" autocomplete="instagram" />
                <jet-input-error :message="form.errors.instagram" class="mt-2" />
            </div>
            <div class="col-span-8 sm:col-span-4" dir="rtl" >
                <jet-label for="whatsapp" :value="__('Whatsapp')" />
                <jet-input id="whatsapp" type="text" class="mt-1 block w-full" v-model="form.whatsapp" autocomplete="whatsapp" />
                <jet-input-error :message="form.errors.whatsapp" class="mt-2" />
            </div>
            <div class="col-span-8 sm:col-span-4" dir="rtl" >
                <jet-label for="email" :value="__('Email')" />
                <jet-input id="email" type="text" class="mt-1 block w-full" v-model="form.email" autocomplete="email" />
                <jet-input-error :message="form.errors.email" class="mt-2" />
            </div>
            <div class="col-span-8 sm:col-span-4" dir="rtl" >
                <jet-label for="address" :value="__('Address')" />
                <jet-input id="address" type="text" class="mt-1 block w-full" v-model="form.address" autocomplete="address" />
                <jet-input-error :message="form.errors.address" class="mt-2" />
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
        title: String,
        phone: String,
        tiktok: String,
        facebook: String,
        instagram: String,
        whatsapp: String,
        address: String,
        email: String,
        settinglanguage: String,
    },
    data() {
        return {
            form: this.$inertia.form({
                _method: 'POST',
                title: this.title,
                phone: this.phone,
                tiktok: this.tiktok,
                facebook: this.facebook,
                instagram: this.instagram,
                whatsapp: this.whatsapp,
                address: this.address,
                email: this.email,
                settinglanguage: this.settinglanguage,
            }),
        }
    },

    methods: {
        editSettingInformation() {

            this.form.post(route('settings.store'), {
                errorBag: 'editSettingInformation',
                preserveScroll: true,
                onSuccess: () => (undefined),
            });
        }
    },
})
</script>
