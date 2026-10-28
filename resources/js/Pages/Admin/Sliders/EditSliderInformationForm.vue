<template>
    <jet-form-section @submitted="editSliderInformation">
        <template #form>
            <div class="col-span-8 sm:col-span-4" dir="rtl">
                <div>
                    <div class="col-span-8 sm:col-span-4" dir="rtl">
                        <jet-label for="place" :value="__('Position')" />
                        <Multiselect v-model="form.place" :options="positions" :multiple="false" :close-on-select="true" placeholder="اختر مكان التموضع" label="name" track-by="value" />
                        <jet-input-error :message="form.errors.type || form.errors.place" class="mt-2" />
                    </div>
                    <div class="col-span-8 sm:col-span-4 mt-4" dir="rtl">
                        <jet-label for="country" value="Country (الدولة)" />
                        <Multiselect v-model="form.country" :options="countries" :multiple="false" :close-on-select="true" placeholder="اختر الدولة" label="name" track-by="value" />
                        <jet-input-error :message="form.errors.country_id || form.errors.country" class="mt-2" />
                    </div>
                    <MeeFile
                        :title="__('Drag files here to add image')"
                        :name="'image'"
                        v-model="form.image"
                    />
                    <jet-input-error :message="form.errors.image_path || form.errors.image" class="mt-2" />
                </div>
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
        slider: Object,
        countries: { type: Array, default: () => [] }
    },
    data() {
        const positions = [
            { name: 'Left', value: '1' },
            { name: 'Center', value: '2' },
            { name: 'Right', value: '3' },
        ]
        return {
            positions,
            form: this.$inertia.form({
                place: positions.find(position => Number(position.value) === Number(this.slider.type)) || null,
                image: this.slider.image,
                country: this.countries.find(country => Number(country.value) === Number(this.slider.country_id)) || this.countries[0] || null,
            }),
            all_products: false,
        }
    },

    methods: {
        editSliderInformation() {
            this.form.put(route('sliders.update', this.slider.id), {
                errorBag: 'editSliderInformation',
                preserveScroll: true,
            });
        }
    },
})
</script>
