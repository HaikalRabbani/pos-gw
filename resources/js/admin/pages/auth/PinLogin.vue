<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-teal-800 via-teal-900 to-teal-950 relative overflow-hidden">
    <div class="absolute inset-0 opacity-5">
      <div class="absolute top-1/3 right-1/3 w-80 h-80 bg-amber-400 rounded-full blur-3xl"></div>
    </div>
    <div class="w-full max-w-sm mx-4">
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-amber-500 mb-4 shadow-lg shadow-amber-500/25">
          <i class="pi pi-key text-teal-950 text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-white">Login PIN</h1>
        <p class="text-teal-300 text-sm mt-1">Masukkan PIN 4 digit</p>
      </div>
      <div class="bg-white rounded-2xl shadow-xl p-8">
        <form @submit.prevent="handleLogin" class="space-y-6">
          <div>
            <InputMask v-model="pin" mask="9999" placeholder="****" class="w-full text-center text-2xl" required />
          </div>
          <Button type="submit" label="Masuk" class="w-full" :loading="loading" />
        </form>
        <div class="mt-5 pt-5 border-t border-slate-100 text-center">
          <router-link to="/login"
            class="text-sm text-amber-600 hover:text-amber-700 font-medium transition">
            <i class="pi pi-arrow-left mr-1 text-xs"></i> Login dengan Email
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import InputMask from 'primevue/inputmask'
import Button from 'primevue/button'

const router = useRouter()
const auth = useAuthStore()
const pin = ref('')
const loading = ref(false)

async function handleLogin() {
  loading.value = true
  try {
    await auth.loginPin(pin.value)
    router.push('/dashboard')
  } catch (e) {
    alert(e.response?.data?.message || 'Invalid PIN')
  } finally {
    loading.value = false
  }
}
</script>
