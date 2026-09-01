<template>
    <div class="font-sans-arabic">
        <Head :title="title" />

        <JetBanner />

        <div class="min-h-screen" >
            <nav class="py-3 bg-white border-b shadow-xl border-gray-100">
                <!-- Primary Navigation Menu -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-48">
                        <div class="grid grid-cols-6 justify-center items-center">
                            <!-- Logo -->

                            <div class="ml-4">
                                <inertia-link :href="route('language', [selectable_locale])">
                                    <icon class="w-5 h-5" :name="selectable_locale" />
                                </inertia-link>

                            </div>
                            <div class="ml-1">
                                <inertia-link :href="route('country', [selectable_country])">
                                    <icon class="w-5 h-5" :name="selectable_country" />
                                </inertia-link>

                            </div>

                            <!-- Navigation Links -->

                            <JetNavLink :href="route('dashboard')" :active="route().current('dashboard')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex">
                                {{ __('Home')}}
                            </JetNavLink>
                            <JetNavLink :href="route('categories.index')" :active="route().current('categories.index')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" v-if="user.role === 1">
                                {{ __('Categories')}}
                            </JetNavLink>

                            <JetNavLink :href="route('banners.index')" :active="route().current('banners.index')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" v-if="user.role === 1">
                                {{ __('Banners')}}
                            </JetNavLink>

                            <JetNavLink :href="route('branches.index')" :active="route().current('branches.index')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" v-if="user.role === 1">
                                {{ __('Branches')}}
                            </JetNavLink>

                            <JetNavLink :href="route('sliders.index')" :active="route().current('sliders.index')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" v-if="user.role === 1">
                                {{ __('Sliders')}}
                            </JetNavLink>

                            <JetNavLink :href="route('mobile-sliders.index')" :active="route().current('mobile-sliders.index')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" v-if="user.role === 1">
                                {{ __('Mobile Sliders')}}
                            </JetNavLink>

                            <JetNavLink :href="route('settings.index')" :active="route().current('settings.index')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" v-if="user.role === 1">
                                {{ __('Settings')}}
                            </JetNavLink>

                            <JetNavLink :href="route('currencies.index')" :active="route().current('currencies.index')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" v-if="user.role === 1">
                                {{ __('Currencies')}}
                            </JetNavLink>

                            <JetNavLink :href="route('colors.index')" :active="route().current('colors.index')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" v-if="user.role === 1 || user.role === 2">
                                {{ __('Colors')}}
                            </JetNavLink>

                            <JetNavLink :href="route('productColors.index')" :active="route().current('productColors.index')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" v-if="user.role === 1 || user.role === 2">
                                {{ __('Products')}}
                            </JetNavLink>

                            <JetNavLink :href="route('products.index')" :active="route().current('products.index')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" v-if="user.role === 1 || user.role === 2">
                                {{ __('Models')}}
                            </JetNavLink>

                            <JetNavLink :href="route('users.index', {'role': [3], 'type': 3})" :active="route().current('users.index', {'type': 3})" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" v-if="user.role === 1">
                                {{ __('Shops')}}
                            </JetNavLink>

                            <JetNavLink :href="route('users.index', {'role': [4], 'type': 4})" :active="route().current('users.index', {'type': 4})" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" v-if="user.role === 1 || user.role === 3 || user.role === 2">
                                {{ __('Clients')}}
                            </JetNavLink>

                            <JetNavLink :href="route('users.index', {'role': [5], 'type': 5})" :active="route().current('users.index', {'type': 5})" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" v-if="user.role === 1 || user.role === 2 || user.role === 3">
                                {{ __('Merchants')}}
                            </JetNavLink>

                            <JetNavLink :href="route('users.index', {'role': [6], 'type': 6})" :active="route().current('users.index', {'type': 6})" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" v-if="user.role === 1 || user.role === 3 || user.role === 2">
                                {{ __('Shipping Companies')}}
                            </JetNavLink>

                            <JetNavLink :href="route('users.index', {'role': [2], 'type': 2})" :active="route().current('users.index', {'type': 2})" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" v-if="user.role === 1">
                                {{ __('Warehouses')}}
                            </JetNavLink>

                            <JetNavLink :href="route('userProducts.all')" :active="route().current('userProducts.all')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" v-if="user.role !== 1">
                                {{ __('Products in all shops')}}
                            </JetNavLink>

                            <JetNavLink :href="route('userProducts.index')" :active="route().current('userProducts.index')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex">
                                {{ __('Stock')}}
                            </JetNavLink>

                            <JetNavLink :href="route('contactMessages.index')" :active="route().current('contactMessages.index')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" v-if="user.role === 1">
                                {{ __('Messages')}}
                            </JetNavLink>

                            <JetNavLink :href="route('admin.merchantCodes.index')" :active="route().current('admin.merchantCodes.index')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" v-if="user.role === 1">
                                {{ __('Merchant Codes')}}
                            </JetNavLink>

                            <JetNavLink :href="route('orders.index')" :active="route().current('orders.index')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex">
                                {{ __('Orders')}}
                            </JetNavLink>

                            <JetNavLink :href="route('orders.monthly_orders')" :active="route().current('orders.monthly_orders')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" v-if="user.role === 1">
                                {{ __('Monthly Orders')}}
                            </JetNavLink>

                            <JetNavLink :href="route('orders.appOrders')" :active="route().current('orders.appOrders')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" v-if="user.role === 1 || user.id == 184 || user.id == 189">
                                {{ __('App Orders')}}
                            </JetNavLink>

                            <JetNavLink :href="route('orders.websiteOrders')" :active="route().current('orders.websiteOrders')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex relative">
                                {{ __('Website Orders')}}
                                <div v-if="websiteOrdersCount > 0" class="absolute -top-2 -right-2 h-4 w-5 rounded-full bg-red-600 text-white text-center notification-count text-xs">
                                    {{ websiteOrdersCount > 9 ? '9+' : websiteOrdersCount }}
                                </div>
                            </JetNavLink>

                            <JetNavLink class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" :href="route('expenses.index')" :active="route().current('expenses.index')">
                                {{ __('Costs & Payments')}}
                            </JetNavLink>

                            <JetNavLink class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" :href="route('cuts.index')" :active="route().current('cuts.index')" v-if="user.role === 1 || user.role === 2">
                                {{ __('Cuts')}}
                            </JetNavLink>

                            <JetNavLink :href="route('refunds.index')" :active="route().current('refunds.index')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex">
                                {{ __('Refunds')}}
                            </JetNavLink>

                            <JetNavLink :href="route('orders.debts')" :active="route().current('orders.debts')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex">
                                {{ __('Unpaid Orders')}}
                            </JetNavLink>

                            <JetNavLink :href="route('clientDebit.clients')" :active="route().current('clientDebit.clients')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex">
                                {{ __('Clients Debits')}}
                            </JetNavLink>

                            <JetNavLink :href="route('debits.merchants')" :active="route().current('debits.merchants')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex">
                                {{ __('Debits for Merchants')}}
                            </JetNavLink>

                            <JetNavLink :href="route('orders.profits')" :active="route().current('orders.profits')" v-if="user.role === 1" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex">
                                {{ __('Profits')}}
                            </JetNavLink>

                            <JetNavLink :href="route('orders.simple')" :active="route().current('orders.simple')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" v-if="user.role === 3 || user.role === 2">
                                {{ __('Quick Order')}}
                            </JetNavLink>

                            <JetNavLink :href="route('orders.complex')" :active="route().current('orders.complex')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" v-if="user.role === 3 || user.role === 2">
                                {{ __('Client Order')}}
                            </JetNavLink>

                            <JetNavLink :href="route('orders.multi')" :active="route().current('orders.multi')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" v-if="user.role === 3 || user.role === 2">
                                {{ __('Multi Order')}}
                            </JetNavLink>

                            <JetNavLink :href="route('accessories.index')" :active="route().current('accessories.index')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" v-if="user.role === 1 || user.role === 2">
                                {{ __('Accessories')}}
                            </JetNavLink>

                            <JetNavLink :href="route('fabrics.index')" :active="route().current('fabrics.index')" class="hidden border border-gray-300 rounded-md shadow-md space-x-8 sm:-my-px sm:ml-10 sm:flex" v-if="user.role === 1 || user.role === 2">
                                {{ __('Fabrics')}}
                            </JetNavLink>

                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ml-6" dir="rtl">

                            <div class="ml-3 mr-6">
                                <jet-dropdown align="right" width="96">
                                    <template #trigger>
                                        <button class="flex text-sm relative">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="28" viewBox="0 0 29.466 32.514">
                                                <path id="bell" d="M30.2,18.506,27.63,9.246a12.626,12.626,0,0,0-24.49.642L1.147,18.848a6.773,6.773,0,0,0,6.612,8.243H9.268a6.773,6.773,0,0,0,13.275,0h1.135A6.773,6.773,0,0,0,30.2,18.506ZM15.906,29.8a4.064,4.064,0,0,1-3.815-2.709H19.72A4.064,4.064,0,0,1,15.906,29.8Zm11.007-7.024a4.033,4.033,0,0,1-3.236,1.605H7.759a4.064,4.064,0,0,1-3.966-4.946l1.991-8.962a9.917,9.917,0,0,1,19.235-.5l2.574,9.26a4.033,4.033,0,0,1-.68,3.546Z" transform="translate(-0.986 -0.002)" fill="#014758"/>
                                            </svg>
                                            <div v-if="otherNotificationsCount > 0" class="absolute -top-1.5 -right-1.5 h-4 w-5 rounded-full bg-red-600 text-white text-center notification-count">
                                                {{ otherNotificationsCount > 9 ? '9+' : otherNotificationsCount }}
                                            </div>
                                        </button>
                                    </template>
                                    <template #content>
                                        <div class="block px-4 py-2 text-xs text-gray-400">
                                            {{ __('Notifications')}}
                                        </div>

                                        <div class="block px-4 py-2 text-sm text-gray-500" v-for="notification in $page.props.auth.user.notifications" v-if="otherNotificationsCount > 0">
                                            <div v-if="notification.data['table'].product_color" @click="readNotification(notification.data['table'], notification.id, event)" class="mx-auto max-w-xl p-6 text-sm leading-6 sm:text-base sm:leading-7 cursor-pointer" :class="notification.read_at?'bg-white':'bg-gray-200'">
                                                <div class="flex items-center">
                                                    <div class="text-sm font-medium">{{ notification.data['message'] }}</div>
                                                    <img class="w-24 rounded-full" :src="notification.data['table'].product_color?.photo_url" alt="">
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else class="block px-4 py-2 text-sm text-gray-300">
                                            {{ __('No Notifications')}}
                                        </div>
                                        <inertia-link class="block px-4 py-2 text-sm text-gray-500" :href="route('notification.index')">
                                            <div class="mx-auto max-w-xl p-6 text-sm leading-6 sm:text-base sm:leading-7 cursor-pointer">
                                                <div class="flex justify-center">
                                                    <div class="text-sm text-center font-medium">{{ __('Load More')}}</div>
                                                </div>
                                            </div>
                                        </inertia-link>


                                    </template>

                                </jet-dropdown>
                            </div>

                            <!-- Settings Dropdown -->
                            <div class="ml-3 relative">
                                <JetDropdown align="right" width="48">
                                    <template #trigger>
                                        <button v-if="$page.props.jetstream.managesProfilePhotos" class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition">
                                            <img class="h-8 w-8 rounded-full object-cover" :src="$page.props.user.profile_photo_url" :alt="$page.props.user.name">
                                        </button>

                                        <span v-else class="inline-flex rounded-md">
                                            <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition">
                                                {{ $page.props.user.name }}

                                                <svg
                                                    class="ml-2 -mr-0.5 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <!-- Account Management -->
                                        <div class="block px-4 py-2 text-xs text-gray-400">
                                            {{ __('Account Managemens')}}
                                        </div>

                                        <JetDropdownLink :href="user.role === 4 ? route('website.profile') : route('profile.show')">
                                            {{ __('Profile')}}
                                        </JetDropdownLink>

                                        <JetDropdownLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')">
                                            API Tokens
                                        </JetDropdownLink>

                                        <div class="border-t border-gray-100" />

                                        <!-- Authentication -->
                                        <form @submit.prevent="logout">
                                            <JetDropdownLink as="button">
                                                {{ __('Logout')}}
                                            </JetDropdownLink>
                                        </form>
                                    </template>
                                </JetDropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-mr-2 flex items-center sm:hidden">
                            <button class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition" @click="showingNavigationDropdown = ! showingNavigationDropdown">
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{'hidden': showingNavigationDropdown, 'inline-flex': ! showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{'hidden': ! showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{'block': showingNavigationDropdown, 'hidden': ! showingNavigationDropdown}" class="sm:hidden">
                    <div class="pt-2 pb-3 space-y-1">
                        <JetResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            {{ __('Dashboard')}}
                        </JetResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="pt-4 pb-1 border-t border-gray-200">
                        <div class="flex items-center px-4">
                            <div v-if="$page.props.jetstream.managesProfilePhotos" class="shrink-0 mr-3">
                                <img class="h-10 w-10 rounded-full object-cover" :src="$page.props.user.profile_photo_url" :alt="$page.props.user.name">
                            </div>

                            <div>
                                <div class="font-medium text-base text-gray-800">
                                    {{ $page.props.user.name }}
                                </div>
                                <div class="font-medium text-sm text-gray-500">
                                    {{ $page.props.user.email }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <JetResponsiveNavLink :href="route('profile.show')" :active="route().current('profile.show')">
                                {{ __('Profile')}}
                            </JetResponsiveNavLink>

                            <JetResponsiveNavLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')" :active="route().current('api-tokens.index')">
                                API Tokens
                            </JetResponsiveNavLink>

                            <!-- Authentication -->
                            <form method="POST" @submit.prevent="logout">
                                <JetResponsiveNavLink as="button">
                                    {{ __('Logout')}}
                                </JetResponsiveNavLink>
                            </form>

                        </div>
                    </div>
                </div>
            </nav>

            <toast :flash="$page.props.flash" :popstate="$page.props.popstate"></toast>

            <!-- Page Heading -->
            <header v-if="$slots.header" class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-center">
                        <img  :src="'/custom/logo-icon.png'" class="rounded-2xl" style="width: 27rem;">
                    </div>

                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
<script>
import { defineComponent } from 'vue'
import JetApplicationMark from '@/Jetstream/ApplicationMark.vue'
import JetBanner from '@/Jetstream/Banner.vue'
import { Head, Link } from '@inertiajs/inertia-vue3';
import JetDropdown from '@/Jetstream/Dropdown.vue';
import JetDropdownLink from '@/Jetstream/DropdownLink.vue';
import JetNavLink from '@/Jetstream/NavLink.vue';
import JetResponsiveNavLink from '@/Jetstream/ResponsiveNavLink.vue';
import { computed } from 'vue'
import { usePage } from '@inertiajs/inertia-vue3'
import Icon from "../Shared/Icons/Icon.vue";


import Toast from "@/Shared/toast";
import JetAuthenticationCardLogo from '@/Jetstream/AuthenticationCardLogo.vue';

export default defineComponent({
    props: {
        title: String,
    },

    components: {
        Icon,
        Toast,
        Head,
        Link,
        JetBanner,
        JetApplicationMark,
        JetDropdown,
        JetDropdownLink,
        JetNavLink,
        JetResponsiveNavLink,
        JetAuthenticationCardLogo
    },
    created() {
        console.log('Admin Echo listener setting up for user:', this.$page.props.user.id);
        console.log('Echo object available:', typeof window.Echo !== 'undefined');

        window.Echo.private(`App.Models.User.${this.$page.props.user.id}`).notification((notification) => {
            console.log('Admin received notification:', notification);
            console.log('Notification type:', notification.type);
            console.log('Notification data:', notification.data);
            console.log('Current notifications count:', this.$page.props.auth.user.notifications_count);
            console.log('Current notifications:', this.$page.props.auth.user.notifications);

            // Handle all notifications regardless of type
            let newNotification = {};
            newNotification.data = notification;
            newNotification.id = notification.id; // Ensure ID is set
            newNotification.read_at = null; // Mark as unread
            newNotification.type = notification.type; // Ensure type is set

            // Check if notification already exists by ID
            const exists = this.$page.props.auth.user.notifications.some(e => e.id === notification.id);
            console.log('Notification already exists:', exists);

            if (!exists) {
                console.log('Adding new notification to list');
                let audio = new Audio('/custom/ringtone.mp3');
                audio.play().catch(e => console.log('Audio play failed:', e));

                // Add to beginning of array
                this.$page.props.auth.user.notifications.unshift(newNotification);
                this.$page.props.auth.user.notifications_count++;

                console.log('Notification count updated:', this.$page.props.auth.user.notifications_count);
                console.log('Notifications array updated:', this.$page.props.auth.user.notifications);

                // Force Vue reactivity
                this.$forceUpdate();
            }
        }).error((error) => {
            console.error('Admin Echo subscription error:', error);
        });
    },
    data() {
        return {
            showingNavigationDropdown: false,
        }
    },
    methods: {
        logout() {
            this.$inertia.post(route('logout'));
        },
        readNotification(table, notificationId, event) {
            this.$page.props.auth.user.notifications_count--;
            this.$page.props.auth.user.notifications.splice(this.$page.props.auth.user.notifications.indexOf(event), 1);
            this.$inertia.get(this.route('notification.show'), {'notification': notificationId})
        },
        readOrderNotification(table, notificationId, event) {
            this.$page.props.auth.user.notifications_count--;
            this.$page.props.auth.user.notifications.splice(this.$page.props.auth.user.notifications.indexOf(event), 1);
            this.$inertia.get(this.route('notification.order.show'), {'table': table, 'notification': notificationId})
        },
    },
    setup() {
        const user = computed(() => usePage().props.value.auth.user)
        return { user }
    },
    beforeCreate() {
        window.addEventListener('popstate', () => {
            this.$page.props.popstate = true;
        })
    },
    computed: {
        selectable_locale() {
            if(this.$page.props.locale == 'ar') {
                return 'en';
            }
            return 'ar';
        },
        selectable_country() {
            if(this.$page.props.auth.user.role == 1)
                return Math.abs(3 - this.$page.props.auth.user.country_id);
            else
                return this.$page.props.auth.user.country_id;
        },
        websiteOrdersCount() {
            if (!this.$page.props.auth.user.notifications) return 0;
            return this.$page.props.auth.user.notifications.filter(notification => {
                return notification.data && (notification.read_at === null) && notification.data.table && notification.data.table.barcode && !notification.data.table.product_color;
            }).length;
        },
        otherNotificationsCount() {
            if (!this.$page.props.auth.user.notifications) return 0;
            return this.$page.props.auth.user.notifications.filter(notification => {
                return notification.data && notification.data.table && notification.data.table.product_color;
            }).length;
        }
    },
})
</script>
