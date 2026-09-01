<template>
    <jet-form-section @submitted="createUserInformation">
        <template #form>

            <!-- Name -->
            <div class="col-span-8 sm:col-span-4" dir="rtl">
                <jet-label for="name" :value="__('Name')" />
                <jet-input id="name" type="text" class="mt-1 block w-full" v-model="form.name" autocomplete="name" />
                <jet-input-error :message="form.errors.name" class="mt-2" />
            </div>



            <div class="col-span-8 sm:col-span-4" dir="rtl" v-if="type === '1' || type === '2' || type === '3'">
                <jet-label for="email" :value="__('Email')" />
                <jet-input id="email" type="text" class="mt-1 block w-full" v-model="form.email" autocomplete="email" />
                <jet-input-error :message="form.errors.email" class="mt-2" />
            </div>



            <div class="col-span-8 sm:col-span-4" dir="rtl" v-if="type === '4' || type === '5' || type === '6'">
                <jet-label for="phone" :value="__('Phone Number')" />
                <jet-input id="phone" type="text" class="mt-1 block w-full" v-model="form.phone" autocomplete="phone" />
                <jet-input-error :message="form.errors.phone" class="mt-2" />
            </div>

            <div class="col-span-8 sm:col-span-4" dir="rtl" v-if="type === '4' || type === '5' || type ==='6'">
                <jet-label for="address" :value="__('Address')" />
                <jet-input id="address" type="text" class="mt-1 block w-full" v-model="form.address" autocomplete="address" />
                <jet-input-error :message="form.errors.address" class="mt-2" />
            </div>

            <div class="col-span-8 sm:col-span-4" dir="rtl">
                <jet-label for="country" :value="__('Country')" />
                <Multiselect v-model="form.country_id" :options="[{ name: 'Lebanon', value: '1' },{ name: 'United Arab Emirates', value: '2' }]" :multiple="false" :close-on-select="true" placeholder="اختر الدولة" label="name"
                             track-by="value" />
                <jet-input-error :message="form.errors.country" class="mt-2" />
            </div>

            <div class="col-span-8 sm:col-span-4" dir="rtl"  v-if="type === '2' || type === '3'">
                <jet-label for="enable_tax" :value="__('EnableTax')" />

                <select class="form-control" required  v-model="form.enable_tax">
                    <option value=""> أختر نعم / لا </option>
                    <option value="yes"> نعم </option>
                    <option value="no"> لا </option>
                </select>

                <jet-input-error :message="form.errors.enable_tax" class="mt-2" />
            </div>

            <div class="col-span-8 sm:col-span-4" dir="rtl"  v-if="type === '2' || type === '3'">
                <jet-label for="tax_ratio" :value="__('TaxRatio')" />
                <jet-input id="tax_ratio" type="text" class="mt-1 block w-full" v-model="form.tax_ratio" autocomplete="tax_ratio" />
                <jet-input-error :message="form.errors.tax_ratio" class="mt-2" />
            </div>

            <div class="col-span-8 sm:col-span-4" dir="rtl">
                <jet-label for="trn" :value="__('TRN')" />
                <jet-input id="trn" type="text" class="mt-1 block w-full" v-model="form.trn" autocomplete="trn" />
                <jet-input-error :message="form.errors.trn" class="mt-2" />
            </div>

            <input type="hidden" name="role_id" v-model="form.role_id">

            <div class="col-span-6 sm:col-span-4" v-if="type === '1' || type === '2' || type === '3'">
                <JetLabel for="password" :value="__('Password')" />
                <JetInput
                    dir="ltr"
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                />
                <JetInputError :message="form.errors.password" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4" v-if="type === '1' || type === '2' || type === '3'">
                <JetLabel for="password_confirmation" :value="__('Confirm Password')" />
                <JetInput
                    dir="ltr"

                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                />
                <JetInputError :message="form.errors.password_confirmation" class="mt-2" />
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
    import { MeeTextarea, MeeRadio, MeeStatus } from "@/Shared/Ui";
    import Multiselect from '@suadelabs/vue3-multiselect'
    import JetCheckbox from '@/Jetstream/Checkbox.vue'
    import Input from "@/Jetstream/Input";

    export default defineComponent({
        components: {
            Input,
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
        },
        props: {
            type: String,
            countries: Array,
        },
        data() {
            return {
                form: this.$inertia.form({
                    _method: 'POST',
                    name: '',
                    email: '',
                    password: '',
                    password_confirmation: '',
                    address: '',
                    tax_ratio: '',
                    trn: '',
                    enable_tax: '',
                    phone: '',
                    country_id: '',
                    role_id: this.type,
                }),
            }
        },

        methods: {
            createUserInformation() {
                this.form.post(route('users.store'), {
                    errorBag: 'createUserInformation',
                    preserveScroll: true,
                    onSuccess: () => (undefined),
                });
            }
        },
    })
</script>


<style scoped>
    /* If using scoped CSS, ensure that the form-control class is scoped */
    .form-control {
        display: block;
        width: 100%;
        border-radius: 4px;
        border: 1px solid #DDD;
    }
</style>
