import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config'
import router from './router'
import App from './App.vue'
import MyPreset from './theme/preset'
import { useAuthStore } from './stores/auth'

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

// Cek session saat aplikasi dimulai — router guard akan handle redirect
const auth = useAuthStore()
auth.checkSession()
