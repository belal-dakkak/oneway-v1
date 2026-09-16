<template>
  <app-layout title="الصندوق">
    <template #header><h2 class="font-semibold text-xl text-gray-800" dir="rtl">الصندوق حسب العملة <span class="text-xs text-gray-500">#{{ walletOwnerId }}</span></h2></template>
    <div class="py-10 max-w-6xl mx-auto px-4" dir="rtl">
      <div class="grid md:grid-cols-2 gap-5 mb-8">
        <div v-for="code in cashboxCodes" :key="code" class="bg-white rounded-xl shadow p-6">
          <div class="text-gray-500">صندوق {{ code }}</div>
          <div class="text-3xl font-bold mt-2">{{ money(wallets?.[code]?.balance || 0, code) }}</div>
        </div>
      </div>

      <form v-if="canExchange" @submit.prevent="submitExchange" class="bg-white rounded-xl shadow p-6 grid md:grid-cols-2 gap-4 mb-8">
        <h3 class="md:col-span-2 text-xl font-bold">تحويل بين الصندوقين</h3>
        <label>من
          <select v-model="form.from" @change="syncTarget" class="block w-full mt-1 rounded-md border-gray-300"><option>USD</option><option>SYP</option></select>
        </label>
        <label>إلى
          <select v-model="form.to" class="block w-full mt-1 rounded-md border-gray-300"><option>USD</option><option>SYP</option></select>
        </label>
        <label>المبلغ<input v-model="form.amount" type="number" :min="form.from === 'SYP' ? 1 : 0.01" :step="form.from === 'SYP' ? 1 : 0.01" class="block w-full mt-1 rounded-md border-gray-300" /></label>
        <label>عدد الليرات مقابل 1 USD<input v-model="form.rate" type="number" min="0.000001" step="0.000001" class="block w-full mt-1 rounded-md border-gray-300" /></label>
        <label class="md:col-span-2">ملاحظة<input v-model="form.note" class="block w-full mt-1 rounded-md border-gray-300" /></label>
        <div v-if="Object.keys(form.errors).length" class="md:col-span-2 text-red-600">{{ Object.values(form.errors)[0] }}</div>
        <button class="bg-primary text-white rounded-md px-6 py-3 md:col-span-2" :disabled="form.processing">تسجيل التحويل</button>
      </form>

      <div class="bg-white rounded-xl shadow overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead><tr class="bg-gray-50"><th class="p-3">التاريخ</th><th class="p-3">النوع</th><th class="p-3">المبلغ</th><th class="p-3">الرصيد بعد الحركة</th><th class="p-3">المصدر</th></tr></thead>
          <tbody><tr v-for="item in movements.data" :key="item.id" class="border-t"><td class="p-3">{{ item.created_at }}</td><td class="p-3">{{ item.direction }}</td><td class="p-3">{{ money(item.amount, item.currency_code) }}</td><td class="p-3">{{ money(item.balance_after, item.currency_code) }}</td><td class="p-3">{{ item.note || '-' }}</td></tr></tbody>
        </table>
      </div>
    </div>
  </app-layout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue'
export default {
  components: { AppLayout },
  props: { wallets: Object, movements: Object, walletOwnerId: Number, canExchange: Boolean, exchangeRate: [Number, String], defaultCurrency: { type: String, default: 'USD' } },
  data() { return { form: this.$inertia.form({ from: 'SYP', to: 'USD', amount: null, rate: this.exchangeRate, note: '' }), refreshing: false } },
  mounted() { window.addEventListener('focus', this.refreshBalances) },
  beforeUnmount() { window.removeEventListener('focus', this.refreshBalances) },
  computed: {
    cashboxCodes() {
      const codes = Object.keys(this.wallets || {})
      if (!codes.includes(this.defaultCurrency)) codes.unshift(this.defaultCurrency)
      if (this.canExchange) ['USD', 'SYP'].forEach(code => { if (!codes.includes(code)) codes.push(code) })
      return [...new Set(codes)]
    },
  },
  methods: {
    money(value, code) { const decimals = code === 'SYP' ? 0 : 2; return `${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: decimals, maximumFractionDigits: decimals })} ${code}` },
    refreshBalances() {
      if (this.refreshing) return
      this.refreshing = true
      this.$inertia.reload({ only: ['wallets', 'movements', 'auth'], preserveScroll: true, onFinish: () => { this.refreshing = false } })
    },
    submitExchange() { this.form.post(route('cashboxes.exchange'), { preserveScroll: true, onSuccess: () => this.form.reset('amount', 'note') }) },
    syncTarget() { this.form.to = this.form.from === 'USD' ? 'SYP' : 'USD' },
  },
}
</script>
