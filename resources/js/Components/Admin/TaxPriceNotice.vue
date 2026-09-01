<template>
    <div dir="rtl" class="mb-5 rounded-lg border p-4 shadow-sm" :class="isExclusive ? 'border-amber-400 bg-amber-50' : 'border-blue-400 bg-blue-50'">
        <div class="flex items-start gap-3">
            <div class="text-2xl">⚠️</div>
            <div>
                <h3 class="font-bold text-lg" :class="isExclusive ? 'text-amber-900' : 'text-blue-900'">{{ title }}</h3>
                <p class="mt-1 text-sm text-gray-800">{{ description }}</p>
                <p v-if="taxEnabled" class="mt-2 rounded bg-white/80 px-3 py-2 text-sm font-semibold">{{ example }}</p>
                <p v-else class="mt-2 text-sm font-semibold text-gray-600">الضريبة معطلة حاليًا لهذا المتجر، لكن طريقة إدخال السعر تبقى كما هي عند تفعيلها.</p>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        orderType: { type: String, required: true },
        taxRatio: { type: [Number, String], default: 0 },
        enabled: { type: String, default: 'no' },
        currency: { type: [Object, String], default: 'USD' },
    },
    computed: {
        isExclusive() { return this.orderType === 'complex_from_multi' },
        taxEnabled() { return this.enabled === 'yes' && Number(this.taxRatio) > 0 },
        currencyCode() {
            if (typeof this.currency === 'string') return this.currency.toUpperCase()
            return String(this.currency?.code || this.currency?.value || this.currency?.name || 'USD').toUpperCase()
        },
        decimals() { return this.currencyCode === 'SYP' ? 0 : 2 },
        title() { return this.isExclusive ? 'طلبية الجملة: السعر غير شامل الضريبة' : 'السعر المدخل شامل الضريبة' },
        description() {
            return this.isExclusive
                ? 'أدخل سعر الصنف قبل الضريبة، وسيضيف النظام الضريبة فوقه تلقائيًا.'
                : 'أدخل السعر النهائي الشامل للضريبة، وسيستخرج النظام صافي المبيعات والضريبة منه تلقائيًا.'
        },
        example() {
            const ratio = Number(this.taxRatio) || 0
            const entered = this.isExclusive ? 50 : 100
            const net = this.isExclusive ? entered : entered / (1 + ratio / 100)
            const tax = this.isExclusive ? entered * ratio / 100 : entered - net
            const total = this.isExclusive ? net + tax : entered
            const fmt = value => Number(value).toLocaleString(undefined, { minimumFractionDigits: this.decimals, maximumFractionDigits: this.decimals })
            return `مثال بنسبة ${ratio}%: المدخل ${fmt(entered)} ${this.currencyCode} — الصافي ${fmt(net)} — الضريبة ${fmt(tax)} — الإجمالي ${fmt(total)}.`
        },
    },
}
</script>
