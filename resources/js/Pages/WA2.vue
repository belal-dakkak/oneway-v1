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
        number: String
    },
    mounted() {
        let number = this.number;
        //let url = encodeURIComponent(route('invoice.show', this.id));
        let url = encodeURIComponent("*قم بفتح هذا الرابط للاطلاع على فاتورتك من محلات وان واي*\n"+"*Open this link to view your bill from One Way stores*\n"+route('download.invoice.show', this.id));
        window.open(`https://wa.me/${number}/?text=${url}`, '_blank').focus();
        window.location.replace(route('orders.complex'))
    }
}
</script>
