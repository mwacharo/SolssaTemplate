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

// Create Vuetify instance with proper theming
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
          // Main backgrounds - pure white for light theme
          background: '#FFFFFF',
          surface: '#FFFFFF',
          'surface-variant': '#F8FAFC',
          'surface-bright': '#FFFFFF',
          'surface-light': '#FFFFFF',
          
          // Primary colors
          primary: '#2563EB',
          'primary-darken-1': '#1D4ED8',
          secondary: '#64748B',
          'secondary-darken-1': '#475569',
          
          // Accent and status colors
          accent: '#3B82F6',
          error: '#EF4444',
          info: '#0EA5E9',
          success: '#22C55E',
          warning: '#F59E0B',
          
          // Text colors for light theme
          'on-background': '#1E293B',
          'on-surface': '#1E293B',
          'on-primary': '#FFFFFF',
          'on-secondary': '#FFFFFF',
          
          // Navigation specific
          'nav-background': '#FFFFFF',
          'nav-surface': '#FFFFFF',
        }
      },
      dark: {
        dark: true,
        colors: {
          // Main backgrounds - dark theme
          background: '#0F172A',
          surface: '#1E293B',
          'surface-variant': '#334155',
          'surface-bright': '#475569',
          'surface-light': '#334155',
          
          // Primary colors adjusted for dark theme
          primary: '#3B82F6',
          'primary-darken-1': '#2563EB',
          secondary: '#94A3B8',
          'secondary-darken-1': '#64748B',
          
          // Accent and status colors for dark theme
          accent: '#60A5FA',
          error: '#F87171',
          info: '#38BDF8',
          success: '#34D399',
          warning: '#FBBF24',
          
          // Text colors for dark theme
          'on-background': '#F1F5F9',
          'on-surface': '#F1F5F9',
          'on-primary': '#FFFFFF',
          'on-secondary': '#000000',
          
          // Navigation specific for dark theme
          'nav-background': '#1E293B',
          'nav-surface': '#334155',
        }
      }
    },
  },
  defaults: {
    VNavigationDrawer: {
      color: 'surface',
    },
    VAppBar: {
      color: 'surface',
    },
    VCard: {
      color: 'surface',
    },
    VSheet: {
      color: 'surface',
    }
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