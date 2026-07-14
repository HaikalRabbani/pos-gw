<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-teal-800 via-teal-900 to-teal-950 relative overflow-hidden">
    <!-- Animated background orbs -->
    <div class="absolute inset-0">
      <div class="absolute top-1/3 right-1/3 w-80 h-80 bg-amber-400 rounded-full blur-3xl opacity-[0.08] animate-pulse-slow"></div>
      <div class="absolute bottom-1/3 left-1/4 w-72 h-72 bg-teal-300 rounded-full blur-3xl opacity-[0.06] animate-pulse-slow" style="animation-delay: 1.5s"></div>
    </div>

    <!-- Decorative grid pattern -->
    <div class="absolute inset-0 opacity-[0.03]" style="background-image: linear-gradient(rgba(255,255,255,.1) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.1) 1px, transparent 1px); background-size: 60px 60px;"></div>

    <div class="w-full max-w-sm mx-4 relative">
      <!-- Logo & Title -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-amber-400 to-amber-500 mb-4 shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 transition-shadow duration-300">
          <i class="pi pi-key text-teal-950 text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-white">Login PIN</h1>
        <p class="text-teal-300/80 text-sm mt-1">Masukkan PIN 4 digit Anda</p>
      </div>

      <!-- PIN Card -->
      <div class="relative">
        <div class="absolute inset-0 bg-white/5 rounded-2xl blur-sm"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl p-8">
          <form @submit.prevent="handleLogin" class="space-y-6">
            <!-- PIN Input -->
            <div class="text-center">
              <label class="block text-sm font-medium text-slate-700 mb-3">PIN</label>
              <InputMask v-model="pin" mask="9999" placeholder="****" class="w-full text-center text-2xl tracking-[0.5em] font-bold" required />
            </div>

            <!-- Submit -->
            <Button type="submit" label="Masuk" class="w-full" :loading="loading" />
          </form>

          <!-- Divider -->
          <div class="relative my-6">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-slate-100"></div>
            </div>
            <div class="relative flex justify-center text-xs">
              <span class="bg-white px-3 text-slate-400">atau</span>
            </div>
          </div>

          <!-- Email Login Link -->
          <router-link to="/login"
            class="flex items-center justify-center gap-2 w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:border-slate-300 transition-all duration-200 group">
            <i class="pi pi-arrow-left text-xs text-slate-300 group-hover:text-teal-500 group-hover:-translate-x-0.5 transition-all"></i>
            Login dengan Email
          </router-link>
        </div>
      </div>

      <!-- Footer -->
      <p class="text-center text-xs text-teal-500/50 mt-6">© 2026 POS System. All rights reserved.</p>
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
