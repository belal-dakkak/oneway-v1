<template>
    <AppLayout title="Dashboard">

        <div class="py-12 flex self-center justify-center justify-items-center">
            رجاءً الانتظار
        </div>
    </AppLayout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import Welcome from '@/Jetstream/Welcome.vue';
const components = { AppLayout, Welcome }
export default {
    name: 'Dashboard',
    components,
    props: {
        id: Number,
        number: String,
        invoice_url: String
    },
    mounted() {
        let number = this.number;
        let url = encodeURIComponent(this.invoice_url || route('download.invoice.typed', { source: 'order', id: this.id }));
        window.open(`https://wa.me/${number}/?text=${url}`, '_blank').focus();
        window.location.replace(route('orders.complex'))
    }
}
</script>
