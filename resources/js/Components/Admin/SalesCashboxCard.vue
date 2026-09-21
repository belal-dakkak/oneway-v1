<template>
    <div class="flex justify-between block bg-white text-black-500 hover:text-white hover:bg-gray-800 max-w-xs mx-auto rounded-lg p-6 m-8 ring-1 ring-black-400">
        <div>
            <div class="flex items-center space-x-3">
                <h3 class="p-2 text-2xl font-bold">{{ title }}</h3>
            </div>
            <p class="p-2 text-4xl font-bold" dir="ltr">
                <span v-for="code in cashboxCodes" :key="code" class="block text-xl">
                    {{ money(balances?.[code]?.balance, code) }} {{ code }}
                </span>
            </p>
        </div>
        <div>
            <vue-feather :type="'credit-card'" stroke-width="2" class="h-12 w-24 p-1 place-self-center inline-block"></vue-feather>
        </div>
    </div>
</template>

<script>
export default {
    name: 'SalesCashboxCard',
    props: {
        title: { type: String, default: 'صندوق المبيعات' },
        refreshInterval: { type: Number, default: 5000 },
    },
    data() {
        const user = this.$page.props.auth?.user || {}
        return {
            balances: { ...(user.cashboxes || {}) },
            defaultCurrency: this.countryDefaultCurrency(user.country_id),
            refreshing: false,
            refreshTimer: null,
        }
    },
    computed: {
        canRefresh() {
            return [1, 2, 3].includes(Number(this.$page.props.auth?.user?.role))
        },
        cashboxCodes() {
            const codes = Object.keys(this.balances || {})
            if (this.defaultCurrency && !codes.includes(this.defaultCurrency)) {
                codes.unshift(this.defaultCurrency)
            }
            return [...new Set(codes)]
        },
    },
    mounted() {
        if (!this.canRefresh) return
        this.refreshBalances()
        this.refreshTimer = window.setInterval(this.refreshBalances, this.refreshInterval)
        window.addEventListener('focus', this.refreshBalances)
        document.addEventListener('visibilitychange', this.refreshWhenVisible)
    },
    beforeUnmount() {
        window.clearInterval(this.refreshTimer)
        window.removeEventListener('focus', this.refreshBalances)
        document.removeEventListener('visibilitychange', this.refreshWhenVisible)
    },
    methods: {
        countryDefaultCurrency(countryId) {
            if (Number(countryId) === 2) return 'AED'
            return 'USD'
        },
        money(value, code) {
            const decimals = code === 'SYP' ? 0 : 2
            return Number(value || 0).toLocaleString(undefined, {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
            })
        },
        refreshWhenVisible() {
            if (!document.hidden) this.refreshBalances()
        },
        refreshBalances() {
            if (document.hidden || this.refreshing) return
            this.refreshing = true
            axios.get(route('cashboxes.balances'))
                .then(({ data }) => {
                    this.balances = data.balances || {}
                    this.defaultCurrency = data.defaultCurrency || this.defaultCurrency
                })
                .catch(() => {})
                .finally(() => {
                    this.refreshing = false
                })
        },
    },
}
</script>
