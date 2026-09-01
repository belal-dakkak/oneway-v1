<template>
    <jet-form-section @submitted="createBannerInformation">
        <template #form>

            <!-- Name -->
            <div class="col-span-8 sm:col-span-4" dir="rtl">
                <jet-label for="name" :value="__('Name')" />
                <div class="col-span-8 sm:col-span-4" dir="rtl">
                    <jet-label for="products" :value="__('Product')" />
                    <Multiselect v-model="form.selected_product" :options="products" :multiple="false" :close-on-select="true" placeholder="اختر صنف" label="name"
                                 track-by="id" />
                    <jet-input-error :message="form.errors.selected_product" class="mt-2" />
                </div>
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
            products: Array,
        },
        data() {
            return {
                form: this.$inertia.form({
                    _method: 'POST',
                    selected_product: null,
                    image: ''
                }),
                all_products: false,
            }
        },

        methods: {
            createBannerInformation() {

                if (this.$refs.image) {
                    this.form.image = this.$refs.image.files[0]
                }
                this.form.post(route('banners.store'), {
                    errorBag: 'createBannerInformation',
                    preserveScroll: true,
                    onSuccess: () => (undefined),
                });
            }
        },
    })
</script>
