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
                <jet-input id="aed" type="number" step="0.000001" class="mt-1 block w-full" v-model="form.aed" autocomplete="aed" />
                <jet-input-error :message="form.errors.aed" class="mt-2" />
            </div>
            <div class="col-span-8 sm:col-span-4" dir="rtl">
                <jet-label for="syp" value="سعر الليرة السورية مقابل 1 USD" />
                <jet-input id="syp" type="number" step="0.000001" min="0.000001" class="mt-1 block w-full" v-model="form.syp" />
                <jet-input-error :message="form.errors.syp" class="mt-2" />
            </div>
            <template v-for="country in countries" :key="country.id">
                <div class="col-span-8 border-t pt-4 mt-4 font-bold" dir="rtl">إعدادات متجر {{ country.name }}</div>
                <div class="col-span-8 sm:col-span-4" dir="rtl">
                    <jet-label :for="`shipping_fee_usd_${country.id}`" value="رسوم الشحن بالدولار" />
                    <jet-input :id="`shipping_fee_usd_${country.id}`" type="number" step="0.0001" min="0" class="mt-1 block w-full" v-model="form.commerce[country.id].shipping_fee_usd" />
                    <jet-input-error :message="form.errors[`commerce.${country.id}.shipping_fee_usd`]" class="mt-2" />
                </div>
                <div class="col-span-8 sm:col-span-4" dir="rtl">
                    <jet-label :for="`free_shipping_threshold_usd_${country.id}`" value="حد الشحن المجاني بالدولار (اختياري)" />
                    <jet-input :id="`free_shipping_threshold_usd_${country.id}`" type="number" step="0.0001" min="0" class="mt-1 block w-full" v-model="form.commerce[country.id].free_shipping_threshold_usd" />
                    <jet-input-error :message="form.errors[`commerce.${country.id}.free_shipping_threshold_usd`]" class="mt-2" />
                </div>
                <div class="col-span-8 sm:col-span-4" dir="rtl">
                    <jet-label :for="`cod_fee_percent_${country.id}`" value="نسبة رسوم الدفع عند الاستلام" />
                    <jet-input :id="`cod_fee_percent_${country.id}`" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" v-model="form.commerce[country.id].cod_fee_percent" />
                    <jet-input-error :message="form.errors[`commerce.${country.id}.cod_fee_percent`]" class="mt-2" />
                </div>
            </template>
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
        syp: [String, Number],
        commerceSettings: Object,
    },
    data() {
        const commerce = [1, 2, 4].reduce((result, countryId) => {
            const setting = this.commerceSettings?.[countryId] || {}
            result[countryId] = {
                shipping_fee_usd: setting.shipping_fee_usd ?? 0,
                free_shipping_threshold_usd: setting.free_shipping_threshold_usd ?? null,
                cod_fee_percent: setting.cod_fee_percent ?? 0,
            }
            return result
        }, {})
        return {
            form: this.$inertia.form({
                _method: 'POST',
                lp: this.lp,
                aed: this.aed,
                syp: this.syp,
                commerce,
            }),
        }
    },

    computed: {
        countries() {
            return [
                { id: 1, name: 'لبنان' },
                { id: 2, name: 'الإمارات' },
                { id: 4, name: 'سوريا' },
            ]
        },
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
