import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import PrimeVue from 'primevue/config'
import MyPreset from '../admin/theme/preset'
import App from './App.vue'

const routes = [
  {
    path: '/order/:qrToken',
    name: 'Menu',
    component: () => import('./pages/MenuPage.vue'),
  },
]

const router = createRouter({ history: createWebHistory(), routes })

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.use(PrimeVue, {
  theme: {
    preset: MyPreset,
    options: {
      darkModeSelector: false,
    },
  },
})
app.mount('#app')
