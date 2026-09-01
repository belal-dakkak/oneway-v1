<template>
    <jet-form-section @submitted="createSliderInformation">
        <template #form>

            <div>
                <div class="col-span-8 sm:col-span-4" dir="rtl">
                    <jet-label for="place" :value="__('Position')" />
                    <Multiselect v-model="form.place" :options="[{ name: 'Left', value: '1' },{ name: 'Center', value: '2' },{ name: 'Right', value: '3' }]" :multiple="false" :close-on-select="true" placeholder="اختر مكان التموضع" label="name" track-by="id" />
                    <jet-input-error :message="form.errors.place" class="mt-2" />
                </div>
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
                    place: null,
                    image: ''
                }),
                all_products: false,
            }
        },

        methods: {
            createSliderInformation() {

                if (this.$refs.image) {
                    this.form.image = this.$refs.image.files[0]
                }
                this.form.post(route('sliders.store'), {
                    errorBag: 'createSliderInformation',
                    preserveScroll: true,
                    onSuccess: () => (undefined),
                });
            }
        },
    })
</script>
