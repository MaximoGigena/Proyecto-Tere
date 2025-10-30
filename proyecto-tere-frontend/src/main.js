import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import './assets/styles.css'
import './assets/tailwind.css'
import '@fortawesome/fontawesome-free/css/all.css'
import axios from 'axios';

// Font Awesome
import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { fas } from '@fortawesome/free-solid-svg-icons'
import { fab } from '@fortawesome/free-brands-svg-icons'

library.add(fas,fab)
const app = createApp(App)
app.component('font-awesome-icon', FontAwesomeIcon)

app.use(createPinia())
app.use(router)

axios.defaults.withCredentials = true;
axios.defaults.baseURL = 'http://localhost:8000';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Obtener el token CSRF del meta tag
// Obtener CSRF token solo si existe el meta tag
const meta = document.querySelector('meta[name="csrf-token"]')
if (meta) {
  axios.defaults.headers.common['X-CSRF-TOKEN'] = meta.content;
}

app.mount('#app')
