import { createApp } from 'vue'
import App from './App.vue'
import router from './router';

import { IonicVue } from '@ionic/vue';
import axios from 'axios';

import { Capacitor } from '@capacitor/core';

// Configuramos la URL base. Como usarás localtunnel con el subdominio cervezas-app-1234
// usamos esa URL tanto para el APK como para la web, así evitas problemas de CORS en ambos lados.
// OJO: Esta es la URL de tu API Laravel a través de localtunnel.
axios.defaults.baseURL = 'https://cervezas-app-1234.loca.lt';
axios.defaults.headers.common['Bypass-Tunnel-Reminder'] = 'true';

// Suprimir advertencias molestas de terceros en la consola
const originalWarn = console.warn;
console.warn = (...args) => {
  if (args[0] && typeof args[0] === 'string') {
    if (args[0].includes("OSRM's demo server") || args[0].includes("aria-hidden")) {
      return; // Ignorar estas advertencias
    }
  }
  originalWarn(...args);
};

/* Core CSS required for Ionic components to work properly */
import '@ionic/vue/css/core.css';

/* Basic CSS for apps built with Ionic */
import '@ionic/vue/css/normalize.css';
import '@ionic/vue/css/structure.css';
import '@ionic/vue/css/typography.css';

/* Optional CSS utils that can be commented out */
import '@ionic/vue/css/padding.css';
import '@ionic/vue/css/float-elements.css';
import '@ionic/vue/css/text-alignment.css';
import '@ionic/vue/css/text-transformation.css';
import '@ionic/vue/css/flex-utils.css';
import '@ionic/vue/css/display.css';

/**
 * Ionic Dark Mode
 * -----------------------------------------------------
 * For more info, please see:
 * https://ionicframework.com/docs/theming/dark-mode
 */

/* @import '@ionic/vue/css/palettes/dark.always.css'; */
import '@ionic/vue/css/palettes/dark.class.css';
/* import '@ionic/vue/css/palettes/dark.system.css'; */

/* Theme variables */
import './theme/variables.css';

const app = createApp(App)
  .use(IonicVue)
  .use(router);

router.isReady().then(() => {
  app.mount('#app');
});
