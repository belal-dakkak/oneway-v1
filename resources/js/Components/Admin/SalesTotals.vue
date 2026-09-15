<template>
  <section v-if="entries.length" class="mt-5 w-full" dir="rtl">
    <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
      <article v-for="([code, values]) in entries" :key="code" class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <header class="bg-primary px-4 py-2 text-lg font-bold text-white">{{ code }}</header>
        <dl class="divide-y divide-gray-100 text-sm">
          <div v-for="row in moneyRows(values)" :key="row.label" class="flex items-center justify-between gap-4 px-4 py-2">
            <dt class="font-medium text-gray-600">{{ row.label }}</dt>
            <dd class="font-bold text-gray-900" dir="ltr">{{ money(row.value, code) }}</dd>
          </div>
          <div class="flex items-center justify-between gap-4 px-4 py-2">
            <dt class="font-medium text-gray-600">القطع (مباعة / مرتجعة / صافي)</dt>
            <dd class="font-bold text-gray-900" dir="ltr">{{ values.gross_qty || 0 }} / {{ values.refund_qty || 0 }} / {{ values.net_qty || 0 }}</dd>
          </div>
        </dl>
      </article>
    </div>
  </section>
</template>

<script>
import Currency from '@/Utils/Currency.js'

export default {
  name: 'SalesTotals',
  props: {
    totals: { type: Object, default: () => ({}) },
  },
  computed: {
    entries() {
      return Object.entries(this.totals || {}).sort(([left], [right]) => left.localeCompare(right))
    },
  },
  methods: {
    money(value, code) {
      return `${Currency.formatAmount(Number(value || 0), code)} ${code}`
    },
    moneyRows(values) {
      return [
        { label: 'إجمالي المبيعات الأصلي', value: values.gross_sales },
        { label: 'الإجمالي الأصلي غير شامل الضريبة', value: values.gross_net },
        { label: 'إجمالي الضريبة الأصلي', value: values.gross_tax },
        { label: 'إجمالي المرتجعات', value: values.refund_total },
        { label: 'صافي المبيعات', value: values.net_sales },
      ]
    },
  },
}
</script>
