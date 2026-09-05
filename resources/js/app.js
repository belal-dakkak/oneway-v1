require('./bootstrap');

import { createApp, h } from 'vue';
import { createInertiaApp, Link } from '@inertiajs/inertia-vue3';
import { InertiaProgress } from '@inertiajs/progress';
import VueFeather from 'vue-feather';
import VueSweetalert2 from 'vue-sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import Vue3ColorPicker from "vue3-colorpicker";
import "vue3-colorpicker/style.css";
import { createPinia } from 'pinia';
import SypEquivalent from './Components/Currency/SypEquivalent.vue';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel';
const pinia = createPinia();

import BrandedSwal from './plugins/sweetalert';
window.Swal = BrandedSwal;

// Notification sound function
function playNotificationSound() {
    // Try to play the notification sound
    const audio = new Audio('/custom/ringtone.mp3');
    audio.play().catch(error => {
        console.log('Audio play failed, trying alternative sound:', error);
        // Fallback to a simple beep using Web Audio API
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.value = 800;
            oscillator.type = 'sine';
            gainNode.gain.value = 0.3;

            oscillator.start();
            oscillator.stop(audioContext.currentTime + 0.2);
        } catch (e) {
            console.log('Web Audio API also failed:', e);
        }
    });
}

// Request notification permission
if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => require(`./Pages/${name}.vue`),
    setup({ el, app, props, plugin }) {
        const vueApp = createApp({ render: () => h(app, props) })
            .use(plugin)
            .use(pinia)
            .use(VueSweetalert2, {
                confirmButtonColor: '#c20000',
                cancelButtonColor: '#d33'
            })
            .use(Vue3ColorPicker)
            .component(VueFeather.name, VueFeather)
            .component('inertia-link', Link)
            .component('syp-equivalent', SypEquivalent)
            .mixin({ methods: { route } })
            .mixin(require('./base'))
            .mount(el);

        // Set up real-time notifications for logged-in users
        if (props.initialPage.props.auth?.user) {
            const userId = props.initialPage.props.auth.user.id;

            console.log('Setting up Echo listener for user:', userId);

            // Listen for order notifications
            window.Echo.private(`App.Models.User.${userId}`)
                .notification((notification) => {
                    console.log('Received notification via Echo:', notification);
                    // Play notification sound
                    playNotificationSound();

                    // Show browser notification if permission granted
                    if (Notification.permission === 'granted') {
                        new Notification('New Order', {
                            body: notification.message || 'You have a new notification',
                            icon: '/favicon.ico'
                        });
                    }

                    // Refresh the page or update UI as needed
                    // You can emit an event or call a method to update the UI
                    window.dispatchEvent(new CustomEvent('new-notification', { detail: notification }));
                })
                .error((error) => {
                    console.error('Echo subscription error:', error);
                });
        }

        return vueApp;
    },
});

InertiaProgress.init({ color: '#4B5563', showSpinner: true });
console.log('Echo initialized with key:', process.env.MIX_PUSHER_APP_KEY ?? 'dwqdqw');
console.log('Echo cluster:', process.env.MIX_PUSHER_APP_CLUSTER);
