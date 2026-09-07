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
        invoice_url: String,
    },
    mounted() {
        let url = this.invoice_url || route('download.invoice.typed', { source: 'order', id: this.id });
        window.open(url, '_blank').focus();
        window.location.replace(route('orders.complex'))
    }
}
</script>
