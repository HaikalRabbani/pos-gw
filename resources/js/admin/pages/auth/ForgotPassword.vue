<template>
  <div class="min-h-screen flex items-center justify-center p-6 relative overflow-hidden"
    style="background: linear-gradient(135deg, #f8fafc 0%, #f0fdfa 50%, #ecfdf5 100%);">
    <div class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-teal-100/40 blur-3xl"></div>
    <div class="absolute -bottom-32 -left-20 w-96 h-96 rounded-full bg-emerald-100/30 blur-3xl"></div>

    <div class="w-full max-w-md relative z-10">
      <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center mx-auto mb-3 shadow-lg shadow-amber-200/50">
          <KeyRound class="w-7 h-7 text-white" stroke-width="1.5" />
        </div>
        <h1 class="text-xl font-bold text-slate-900">Lupa Password</h1>
        <p class="text-sm text-slate-500 mt-1">Masukkan email Anda untuk menerima tautan reset password</p>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xl shadow-slate-200/50 p-7 sm:p-8">
        <form @submit.prevent="handleForgotPassword" class="space-y-5">

          <transition name="slide-fade">
            <div v-if="successMsg"
              class="px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-100 text-xs text-emerald-700 flex items-start gap-2.5">
              <CheckCircle2 class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" stroke-width="1.5" />
              <span>{{ successMsg }}</span>
            </div>
          </transition>

          <transition name="slide-fade">
            <div v-if="errorMsg"
              class="px-4 py-3 rounded-xl bg-red-50 border border-red-100 text-xs text-red-700 flex items-start gap-2.5">
              <AlertCircle class="w-4 h-4 text-red-500 shrink-0 mt-0.5" stroke-width="1.5" />
              <span>{{ errorMsg }}</span>
            </div>
          </transition>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                <Mail class="w-5 h-5 text-slate-400" stroke-width="1.5" />
              </div>
              <InputText v-model="email" type="email" placeholder="nama@email.com" required
                class="w-full" style="padding-left: 2.75rem !important" />
            </div>
          </div>

          <Button type="submit" label="Kirim Tautan Reset" class="w-full" :loading="loading" size="large" />

          <div class="text-center pt-1 border-t border-slate-100">
            <router-link to="/login" class="text-sm text-teal-600 hover:text-teal-700 font-medium transition-colors duration-150">
              Kembali ke Login
            </router-link>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import client from '../../api/client'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import { KeyRound, Mail, AlertCircle, CheckCircle2 } from '@lucide/vue'

const loading = ref(false)
const errorMsg = ref('')
const successMsg = ref('')
const email = ref('')

async function handleForgotPassword() {
  errorMsg.value = ''
  successMsg.value = ''
  loading.value = true
  try {
    const { data } = await client.post('/auth/forgot-password', { email: email.value })
    successMsg.value = data.message || 'Jika email terdaftar, tautan reset password akan dikirim.'
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Gagal mengirim tautan reset.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.slide-fade-enter-active { transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-fade-leave-active { transition: all 0.2s ease; }
.slide-fade-enter-from { opacity: 0; transform: translateY(-8px) scale(0.97); }
.slide-fade-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
