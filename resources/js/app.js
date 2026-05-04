import './bootstrap';
import axios from 'axios';

// Handle CSRF token expiry (419) globally — reload to get a fresh token
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 419) {
            window.location.reload();
        }
        return Promise.reject(error);
    }
);

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { createPinia } from 'pinia';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

let appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => title ? `${title} - ${appName}` : appName,
    // Reload on CSRF expiry (419) so Inertia gets a fresh token
    onError: (error) => {
        if (error?.response?.status === 419) {
            window.location.reload();
        }
    },
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        const page = pages[`./Pages/${name}.vue`];
        if (!page) {
            throw new Error(`Page not found: ${name}. Make sure the file exists at ./Pages/${name}.vue`);
        }
        return page;
    },
    setup({ el, App, props, plugin }) {
        const restaurantName = props.initialPage?.props?.tenant?.name;
        if (restaurantName) {
            appName = restaurantName;
        }

        const app = createApp({ render: () => h(App, props) });

        app.use(plugin);
        app.use(createPinia());
        app.use(ZiggyVue);

        app.mount(el);

        return app;
    },
    progress: {
        color: '#4B5563',
    },
});

