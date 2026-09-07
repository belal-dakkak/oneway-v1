<template>
    <small v-if="visible" class="block mt-1 text-xs font-medium text-amber-700" dir="rtl">
        ≈ {{ formattedAmount }} {{ target.code }} (للعرض فقط)
    </small>
    <small v-else-if="missingRate" class="block mt-1 text-xs font-medium text-rose-600" dir="rtl">
        سعر الصرف غير محدد؛ بقي المبلغ الرسمي دون تغيير.
    </small>
</template>

<script>
import Currency from '@/Utils/Currency.js'

export default {
    name: 'MoneyEquivalent',
    props: {
        amount: { type: [Number, String], default: null },
        // Backwards compatibility for the former SypEquivalent component.
        usd: { type: [Number, String], default: null },
        currency: { type: String, default: 'USD' },
        exchangeRate: { type: [Number, String], default: null },
        displayCurrency: { type: Object, default: null },
    },
    computed: {
        hasAmount() {
            const value = this.amount !== null ? this.amount : this.usd
            return value !== null && value !== '' && Number.isFinite(Number(String(value).replace(/,/g, '')))
        },
        numericAmount() {
            return Currency.number(this.amount !== null ? this.amount : this.usd)
        },
        sourceCode() {
            return String(this.currency || 'USD').toUpperCase()
        },
        target() {
            return this.displayCurrency || this.$page?.props?.display_currency || null
        },
        sourceRate() {
            if (this.sourceCode === 'USD') return 1
            return Number(this.exchangeRate || this.optionRate(this.sourceCode) || 0)
        },
        targetRate() {
            const code = String(this.target?.code || '').toUpperCase()
            if (code === 'USD') return 1
            return Number(this.target?.rate || this.optionRate(code) || 0)
        },
        visible() {
            const targetCode = String(this.target?.code || '').toUpperCase()
            return Boolean(targetCode)
                && targetCode !== this.sourceCode
                && this.hasAmount
                && this.sourceRate > 0
                && this.targetRate > 0
        },
        missingRate() {
            const targetCode = String(this.target?.code || '').toUpperCase()
            return Boolean(targetCode)
                && targetCode !== this.sourceCode
                && (this.sourceRate <= 0 || this.targetRate <= 0)
        },
        convertedAmount() {
            return (this.numericAmount / this.sourceRate) * this.targetRate
        },
        formattedAmount() {
            return Currency.formatAmount(this.convertedAmount, this.target.code)
        },
    },
    methods: {
        optionRate(code) {
            const option = (this.$page?.props?.currency_options || [])
                .find(item => String(item.code).toUpperCase() === code)
            return option?.rate
        },
    },
}
</script>
