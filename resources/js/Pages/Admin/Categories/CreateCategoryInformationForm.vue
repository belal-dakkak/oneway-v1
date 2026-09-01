<template>
    <jet-form-section @submitted="createCategoryInformation">
        <template #form>

            <!-- Name -->
            <div class="col-span-8 sm:col-span-4" dir="rtl">
                <jet-label for="name" :value="__('Name')" />
                <jet-input id="name" type="text" class="mt-1 block w-full" v-model="form.name" autocomplete="name" />
                <jet-input-error :message="form.errors.name" class="mt-2" />
            </div>

            <div class="col-span-8 sm:col-span-4" dir="rtl">
                <jet-label for="name_en" :value="__('Name EN')" />
                <jet-input id="name_en" type="text" class="mt-1 block w-full" v-model="form.name_en" autocomplete="name_en" />
                <jet-input-error :message="form.errors.name_en" class="mt-2" />
            </div>

            <div>
                <MeeFile
                    :title="__('Drag files here to add image')"
                    :name="'image'"
                    v-model="form.image"
                />
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
    import { MeeTextarea, MeeRadio, MeeStatus, MeeFile } from "@/Shared/Ui";
    import Multiselect from '@suadelabs/vue3-multiselect'
    import JetCheckbox from '@/Jetstream/Checkbox.vue'

    export default defineComponent({
        components: {
            JetActionMessage,
            JetButton,
            JetFormSection,
            JetInput,
            JetInputError,
            JetLabel,
            JetSecondaryButton,
            JetCheckbox,
            MeeTextarea,
            MeeRadio,
            MeeStatus,
            Multiselect,
            MeeFile
        },
        data() {
            return {
                form: this.$inertia.form({
                    _method: 'POST',
                    name: '',
                    name_en: '',
                    code: '',
                }),
            }
        },

        methods: {
            createCategoryInformation() {
                if (this.$refs.image) {
                    this.form.image = this.$refs.image.files[0]
                }

                this.form.post(route('categories.store'), {
                    errorBag: 'createCategoryInformation',
                    preserveScroll: true,
                    onSuccess: () => (undefined),
                });
            }
        },
    })
</script>
