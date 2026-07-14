<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-teal-800 via-teal-900 to-teal-950 relative overflow-hidden">
    <div class="absolute inset-0 opacity-5">
      <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-amber-400 rounded-full blur-3xl"></div>
      <div class="absolute bottom-1/4 right-1/4 w-80 h-80 bg-teal-300 rounded-full blur-3xl"></div>
    </div>
    <div class="w-full max-w-sm mx-4">
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-amber-500 mb-4 shadow-lg shadow-amber-500/25">
          <i class="pi pi-shopping-bag text-teal-950 text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-white">POS System</h1>
        <p class="text-teal-300 text-sm mt-1">Masuk ke akun admin Anda</p>
      </div>
      <div class="bg-white rounded-2xl shadow-xl p-8">
        <form @submit.prevent="handleLogin" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
            <InputText v-model="email" type="email" class="w-full" placeholder="admin@pos.com" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
            <InputText v-model="password" type="password" class="w-full" placeholder="Masukkan password" required />
          </div>
          <Button type="submit" label="Masuk" class="w-full" :loading="loading" />
        </form>
        <div class="mt-5 pt-5 border-t border-slate-100 text-center">
          <router-link to="/pin-login"
            class="text-sm text-amber-600 hover:text-amber-700 font-medium transition">
            Login dengan PIN <i class="pi pi-arrow-right ml-1 text-xs"></i>
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
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'

const router = useRouter()
const auth = useAuthStore()
const email = ref('')
const password = ref('')
const loading = ref(false)

async function handleLogin() {
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    router.push('/dashboard')
  } catch (e) {
    alert(e.response?.data?.message || 'Login failed')
  } finally {
    loading.value = false
  }
}
</script>
