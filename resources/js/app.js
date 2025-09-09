import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { createPinia } from 'pinia';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
// toast
// import toast from 'vue3-toastify'
import  Vue3Toastify  from 'vue3-toastify'; // ✅ Correct import

import 'vue3-toastify/dist/index.css';

// Import Vuetify
import 'vuetify/styles';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import { aliases, mdi } from 'vuetify/iconsets/mdi';
import '@mdi/font/css/materialdesignicons.css';


// echo 
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';


window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    authEndpoint: '/broadcasting/auth',
});

const vuetify = createVuetify({
  components,
  directives,
  icons: {
    defaultSet: 'mdi',
    aliases,
    sets: { mdi },
  },
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        dark: false,
        colors: {
          background: '#FFFFFF',
          surface: '#FFFFFF',
          primary: '#2563EB',
          secondary: '#64748B',
          error: '#EF4444',
          info: '#0EA5E9',
          success: '#22C55E',
          warning: '#F59E0B',
          'on-background': '#1E293B',
          'on-surface': '#1E293B',
          'on-primary': '#FFFFFF',
          'on-secondary': '#FFFFFF',
        }
      },
      dark: {
        dark: true,
        colors: {
          background: '#0F172A',
          surface: '#1E293B',
          primary: '#3B82F6',
          secondary: '#94A3B8',
          error: '#F87171',
          info: '#38BDF8',
          success: '#34D399',
          warning: '#FBBF24',
          'on-background': '#F1F5F9',
          'on-surface': '#F1F5F9',
          'on-primary': '#FFFFFF',
          'on-secondary': '#000000',
        }
      }
    },
  },
  defaults: {
    VNavigationDrawer: { color: 'surface' },
    VAppBar: { color: 'surface' },
    VCard: { color: 'surface' },
    VSheet: { color: 'surface' },

    // 👇 ADD THESE
    VTextField: {
      variant: 'outlined',
      color: 'primary',
      class: 'text-black',
    },
    VSelect: {
      variant: 'outlined',
      color: 'primary',
      class: 'text-black',
    },
    VTextarea: {
      variant: 'outlined',
      color: 'primary',
      class: 'text-black',
    },
  }
});

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  setup({ el, App, props, plugin }) {

    const pinia = createPinia();

    return createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(ZiggyVue)
      .use(vuetify)
      .use(pinia)
      .use(Vue3Toastify, {
      position: 'top-center', // Changed from 'top-right' to 'top-center'
      autoClose: 5000,
      hideProgressBar: false,
      closeOnClick: true,
      pauseOnHover: true,
      draggable: true,
      progress: undefined,
      })
      .mount(el);
  },
  progress: {
    color: '#4B5563',
  },
});