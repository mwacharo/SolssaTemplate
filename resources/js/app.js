import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { createPinia } from 'pinia'; // ✅ Import Pinia
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

// Import Vuetify (not the Vite plugin)
import 'vuetify/styles';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';
import { aliases, mdi } from 'vuetify/iconsets/mdi';
import '@mdi/font/css/materialdesignicons.css';

// Create Vuetify instance
// const vuetify = createVuetify({
//   components,
//   directives,
//   icons: {
//     defaultSet: 'mdi',
//     aliases,
//     sets: {
//       mdi,
//     },
//   },
//   theme: {
//     defaultTheme: 'light',                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               


//   }
// });



// Create Vuetify instance
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
          background: '#F9FAFB',
          surface: '#FFFFFF',
          primary: '#2563EB',
          secondary: '#64748B',
          accent: '#3B82F6',
          error: '#EF4444',
          info: '#0EA5E9',
          success: '#22C55E',
          warning: '#F59E0B',
        }
      },
      dark: {
        dark: true,
        colors: {
          background: '#0F172A',
          surface: '#1E293B',
          primary: '#3B82F6',
          secondary: '#94A3B8',
          accent: '#60A5FA',
          error: '#F87171',
          info: '#38BDF8',
          success: '#34D399',
          warning: '#FBBF24',
        }
      }
    },
  },
});


const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  setup({ el, App, props, plugin }) {

    const pinia = createPinia(); // ✅ Create Pinia instance here

    return createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(ZiggyVue)
      .use(vuetify) // Now using the properly created Vuetify instance
      .use(pinia) // ✅ Use Pinia
      .mount(el);
  },
  progress: {
    color: '#4B5563',
  },
});