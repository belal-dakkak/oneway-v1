<template>
    <jet-form-section @submitted="createBranchInformation">
        <template #form>

            <!-- Name -->
            <div class="col-span-8 sm:col-span-4" dir="rtl">
                <jet-label for="name_ar" :value="__('Name')" />
                <jet-input id="name_ar" type="text" class="mt-1 block w-full" v-model="form.name_ar" autocomplete="name_ar" />
                <jet-input-error :message="form.errors.name_ar" class="mt-2" />
            </div>

            <div class="col-span-8 sm:col-span-4" dir="rtl">
                <jet-label for="name_en" :value="__('Name EN')" />
                <jet-input id="name_en" type="text" class="mt-1 block w-full" v-model="form.name_en" autocomplete="name_en" />
                <jet-input-error :message="form.errors.name_en" class="mt-2" />
            </div>

            <div class="col-span-8 sm:col-span-4" dir="rtl">
                <jet-label for="address_ar" :value="__('Address')" />
                <jet-input id="address_ar" type="text" class="mt-1 block w-full" v-model="form.address_ar" autocomplete="address_ar" />
                <jet-input-error :message="form.errors.address_ar" class="mt-2" />
            </div>

            <div class="col-span-8 sm:col-span-4" dir="rtl">
                <jet-label for="address_en" :value="__('Address EN')" />
                <jet-input id="address_en" type="text" class="mt-1 block w-full" v-model="form.address_en" autocomplete="address_en" />
                <jet-input-error :message="form.errors.address_en" class="mt-2" />
            </div>

            <div class="col-span-8 sm:col-span-4" dir="rtl">
                <jet-label for="latitude" :value="__('Latitude')" />
                <jet-input id="latitude" type="text" class="mt-1 block w-full" v-model="form.latitude" autocomplete="latitude" />
                <jet-input-error :message="form.errors.latitude" class="mt-2" />
            </div>

            <div class="col-span-8 sm:col-span-4" dir="rtl">
                <jet-label for="longitude" :value="__('Longitude')" />
                <jet-input id="longitude" type="text" class="mt-1 block w-full" v-model="form.longitude" autocomplete="longitude" />
                <jet-input-error :message="form.errors.longitude" class="mt-2" />
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
        data() {
            return {
                form: this.$inertia.form({
                    _method: 'POST',
                    name_en: '',
                    name_ar: '',
                    address_en: '',
                    address_ar: '',
                    latitude: '',
                    longitude: '',
                })
            }
        },
        methods: {
            createBranchInformation() {
                this.form.post(route('branches.store'), {
                    errorBag: 'createBranchInformation',
                    preserveScroll: true,
                    onSuccess: () => (undefined),
                });
            }
        },
    })
</script>
