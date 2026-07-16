<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-teal-800 via-teal-900 to-teal-950 relative overflow-hidden">
    <!-- Animated background orbs -->
    <div class="absolute inset-0">
      <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-amber-400 rounded-full blur-3xl opacity-[0.08] animate-pulse-slow"></div>
      <div class="absolute bottom-1/4 right-1/4 w-80 h-80 bg-teal-300 rounded-full blur-3xl opacity-[0.06] animate-pulse-slow" style="animation-delay: 2s"></div>
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-teal-600 rounded-full blur-3xl opacity-[0.04]"></div>
    </div>

    <!-- Decorative grid pattern -->
    <div class="absolute inset-0 opacity-[0.03]" style="background-image: linear-gradient(rgba(255,255,255,.1) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.1) 1px, transparent 1px); background-size: 60px 60px;"></div>

    <div class="w-full max-w-sm mx-4 relative">
      <!-- Logo & Title -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-amber-400 to-amber-500 mb-4 shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 transition-shadow duration-300">
          <i class="pi pi-shopping-bag text-teal-950 text-2xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-white">POS System</h1>
        <p class="text-teal-300/80 text-sm mt-1">Masuk ke akun admin Anda</p>
      </div>

      <!-- Login Card -->
      <div class="relative">
        <div class="absolute inset-0 bg-white/5 rounded-2xl blur-sm"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl p-8">
          <form @submit.prevent="handleLogin" class="space-y-5">
            <!-- Email -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none z-10">
                  <i class="pi pi-envelope text-slate-400 text-sm"></i>
                </div>
                <input
                  v-model="email"
                  type="email"
                  class="w-full rounded-lg border border-slate-300 bg-white pl-9 pr-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 transition-all duration-200"
                  placeholder="admin@pos.com"
                  required
                />
              </div>
            </div>

            <!-- Password -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none z-10">
                  <i class="pi pi-lock text-slate-400 text-sm"></i>
                </div>
                <input
                  v-model="password"
                  type="password"
                  class="w-full rounded-lg border border-slate-300 bg-white pl-9 pr-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 transition-all duration-200"
                  placeholder="Masukkan password"
                  required
                />
              </div>
            </div>

            <!-- Submit -->
            <Button type="submit" label="Masuk" class="w-full !py-2.5 !text-sm font-semibold" :loading="loading" />
          </form>
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
import { useToastStore } from '../../stores/toast'
import Button from 'primevue/button'

const router = useRouter()
const auth = useAuthStore()
const toast = useToastStore()
const email = ref('')
const password = ref('')
const loading = ref(false)

async function handleLogin() {
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    toast.success('Berhasil Masuk', `Selamat datang kembali, ${auth.user?.name || 'Admin'}!`)
    router.push('/dashboard')
  } catch (e) {
    const msg = e.response?.data?.errors?.email?.[0] || e.response?.data?.message || 'Login gagal. Periksa email dan password Anda.'
    toast.error('Login Gagal', msg)
  } finally {
    loading.value = false
  }
}
</script>
