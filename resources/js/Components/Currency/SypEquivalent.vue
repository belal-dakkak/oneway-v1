<template>
    <small v-if="visible" class="block mt-1 text-xs font-medium text-amber-700" dir="rtl">
        ≈ {{ formattedAmount }} {{ resolvedCurrency.code }} (للعرض فقط)
    </small>
    <small v-else-if="missingSyriaRate" class="block mt-1 text-xs font-medium text-rose-600" dir="rtl">
        سعر عرض الليرة السورية غير محدد. يبقى السعر الرسمي بالدولار.
    </small>
</template>

<script>
import Currency from '@/Utils/Currency.js'

export default {
    name: 'SypEquivalent',
    props: {
        usd: { type: [Number, String], default: null },
        displayCurrency: { type: Object, default: null },
    },
    computed: {
        resolvedCurrency() {
            return this.displayCurrency || this.$page?.props?.display_currency || null
        },
        visible() {
            return this.resolvedCurrency?.code === 'SYP'
                && Number(this.resolvedCurrency?.rate) > 0
                && this.usd !== null
                && this.usd !== ''
                && Number.isFinite(Currency.number(this.usd))
        },
        missingSyriaRate() {
            return this.$page?.props?.country === 'SY'
                && (!this.resolvedCurrency || Number(this.resolvedCurrency.rate) <= 0)
        },
        formattedAmount() {
            return Currency.formatFromUsd(
                this.usd,
                this.resolvedCurrency.rate,
                this.resolvedCurrency.code
            )
        },
    },
}
</script>
