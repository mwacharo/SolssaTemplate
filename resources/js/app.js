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
      // light: {
      //   dark: false,
      //   colors: {
      //     background: '#FFFFFF',
      //     surface: '#FFFFFF',
      //     primary: '#1976D2',
      //     secondary: '#424242',
      //     accent: '#82B1FF',
      //     error: '#FF5252',
      //     info: '#2196F3',
      //     success: '#4CAF50',
      //     warning: '#FB8C00',
      //   },
      // },
      light: {
        dark: false,
        colors: {
          background: '#F9FAFB',         // Soft off-white for less strain
          surface: '#FFFFFF',            // Clean white for cards/surfaces
          primary: '#2563EB',            // Strong blue for primary actions
          secondary: '#64748B',          // Muted blue-gray for secondary elements
          accent: '#3B82F6',             // Bright accent for highlights
          error: '#EF4444',              // Clear red for errors
          info: '#0EA5E9',               // Light blue for info
          success: '#22C55E',            // Green for success
          warning: '#F59E0B',            // Orange for warnings
        }
      }
      ,
      dark: {
        dark: true,
        colors: {
          background: '#0F172A',         // Deep slate (almost black)
          surface: '#1E293B',            // Darker slate for card/surface contrast
          primary: '#3B82F6',            // Vivid blue for action
          secondary: '#94A3B8',          // Light slate for secondary elements
          accent: '#60A5FA',             // Soft accent for highlights
          error: '#F87171',              // Light red
          info: '#38BDF8',               // Aqua blue for info
          success: '#34D399',            // Light green for success
          warning: '#FBBF24',            // Lighter yellow-orange
        }
      }
      // ,
      //       dark: {
      //         dark: true,
      //         colors: {
      //           background: '#121212',
      //           surface: '#1E1E1E',
      //           primary: '#BB86FC',
      //           secondary: '#03DAC6',
      //           accent: '#03DAC6',
      //           error: '#CF6679',
      //           info: '#2196F3',
      //           success: '#4CAF50',
      //           warning: '#FB8C00',
      //         },
      //       },
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