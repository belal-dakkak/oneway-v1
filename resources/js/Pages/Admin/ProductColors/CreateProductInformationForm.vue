<template>
    <jet-form-section @submit.prevent v-on:submit.prevent>
        <template #form>
            <!-- Name -->
            <div class="col-span-8 sm:col-span-4" dir="rtl">
                <jet-label for="details_en" :value="__('Details (EN)')" />
                <mee-textarea
                    id="details_en"
                    class="mt-1 block w-full"
                    v-model="form.details_en"
                />
                <jet-input-error :message="form.errors.details_en" class="mt-2" />
            </div>

        </template>

        <template #title>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight" dir="rtl">
                {{ __('Add Product')}}
            </h2>
        </template>

        <template #actions>
            <jet-action-message :on="form.recentlySuccessful" class="mr-3">
                تم الحفظ.
            </jet-action-message>

            <jet-button class="px-16 py-4" @click="createProductInformation" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
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
    import { MeeTextarea, MeeRadio, MeeStatus, MeeFile, TextField } from "@/Shared/Ui";
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
            TextField,
            MeeFile
        },

        props: {
            categories: {
                type: Array,
                default: () => []
            },
            colors: Array,
            sizes: Array,
        },

        data() {
            return {
                form: this.$inertia.form({
                    _method: 'POST',
                    barcode: '',
                    name: '',
                    details: '',
                    details_en: '',
                    stock: '',
                    cost_price: '',
                    photo: null,
                    selected_category: null,
                    selected_sizes: null,
                    selected_products: null,
                }),
                photoPreview: null,
                all_cities: false,
                all_categories: false,
                all_nationalities: false,
                all_types: false,
                products: [{ image: '', stock: '', color: '', barcode: '' }]
            }
        },

        methods: {
            AddField: function () {
                this.products.push({ image: '', stock: '', color: '', barcode: '' });
            },
            createProductInformation() {
                if (this.$refs.photo) {
                    this.form.photo = this.$refs.photo.files[0]
                }

                this.form.selected_products = this.products;

                this.form.post(route('productColors.store'), {
                    errorBag: 'createProductInformation',
                    preserveScroll: true,
                    onSuccess: () => (this.clearPhotoFileInput()),
                });
            },
            selectNewPhoto() {
                this.$refs.photo.click();
            },

            updatePhotoPreview() {
                const photo = this.$refs.photo.files[0];

                if (! photo) return;

                const reader = new FileReader();

                reader.onload = (e) => {
                    this.photoPreview = e.target.result;
                };

                reader.readAsDataURL(photo);
            },

            clearPhotoFileInput() {
                if (this.$refs.photo?.value) {
                    this.$refs.photo.value = null;
                }
            },
        },
    })
</script>
