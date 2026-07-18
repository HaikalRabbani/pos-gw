<template>
  <div class="min-h-screen flex items-center justify-center p-6 relative overflow-hidden"
    style="background: linear-gradient(135deg, #f8fafc 0%, #f0fdfa 50%, #ecfdf5 100%);">
    <div class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-teal-100/40 blur-3xl"></div>
    <div class="absolute -bottom-32 -left-20 w-96 h-96 rounded-full bg-emerald-100/30 blur-3xl"></div>

    <div class="w-full max-w-md relative z-10">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-700 flex items-center justify-center mx-auto mb-3 shadow-lg shadow-teal-200/50">
          <MailCheck class="w-7 h-7 text-white" stroke-width="1.5" />
        </div>
        <h1 class="text-xl font-bold text-slate-900">Verifikasi Email</h1>
        <p class="text-sm text-slate-500 mt-1">Masukkan kode 6 digit yang dikirim ke <strong class="text-slate-700">{{ routeEmail }}</strong></p>
      </div>

      <!-- Card -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xl shadow-slate-200/50 p-7 sm:p-8">
        <form @submit.prevent="handleVerify" class="space-y-6">

          <transition name="slide-fade">
            <div v-if="errorMsg"
              class="px-4 py-3 rounded-xl bg-red-50 border border-red-100 text-xs text-red-700 flex items-start gap-2.5">
              <AlertCircle class="w-4 h-4 text-red-500 shrink-0 mt-0.5" stroke-width="1.5" />
              <span>{{ errorMsg }}</span>
            </div>
          </transition>

          <!-- Code Input -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2 text-center">Kode Aktivasi</label>
            <InputMask
              v-model="code"
              mask="999999"
              placeholder="******"
              class="w-full text-center text-3xl tracking-[0.4em] font-mono"
              required
            />
          </div>

          <Button type="submit" label="Verifikasi" class="w-full" :loading="loading" size="large" />

          <!-- Resend -->
          <div class="text-center pt-1">
            <p class="text-sm text-slate-500 mb-1">Tidak menerima kode?</p>
            <button
              type="button"
              class="text-sm text-teal-600 hover:text-teal-700 font-medium transition-colors duration-150 disabled:opacity-40 disabled:cursor-not-allowed"
              :disabled="resendCooldown > 0"
              @click="resendCode"
            >
              Kirim Ulang Kode
              <span v-if="resendCooldown > 0" class="text-slate-400 font-normal">({{ resendCooldown }} detik)</span>
            </button>
          </div>

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
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import client from '../../api/client'
import { useToastStore } from '../../stores/toast'
import { useAuthStore } from '../../stores/auth'
import Button from 'primevue/button'
import InputMask from 'primevue/inputmask'
import { MailCheck, AlertCircle } from '@lucide/vue'

const router = useRouter()
const route = useRoute()
const toast = useToastStore()
const auth = useAuthStore()

const loading = ref(false)
const errorMsg = ref('')
const code = ref('')
const resendCooldown = ref(0)

const routeEmail = computed(() => route.query.email || 'email Anda')

onMounted(() => {
  if (!route.query.email) {
    toast.error('Email tidak ditemukan', 'Silakan daftar ulang.')
    router.push('/register')
  }
})

async function handleVerify() {
  if (code.value.length !== 6) {
    errorMsg.value = 'Masukkan kode 6 digit lengkap.'
    return
  }

  errorMsg.value = ''
  loading.value = true
  try {
    const { data } = await client.post('/auth/verify-email', {
      email: route.query.email,
      code: code.value,
    })
    auth.setSession(data.data)
    toast.success('Aktivasi Berhasil', data.message || 'Selamat datang!')
    router.replace('/dashboard')
  } catch (err) {
    errorMsg.value = err.response?.data?.errors?.code?.[0]
      || err.response?.data?.message
      || 'Kode verifikasi tidak valid.'
  } finally {
    loading.value = false
  }
}

async function resendCode() {
  resendCooldown.value = 60
  const timer = setInterval(() => {
    resendCooldown.value--
    if (resendCooldown.value <= 0) clearInterval(timer)
  }, 1000)

  try {
    await client.post('/auth/resend-verification', { email: route.query.email })
    toast.success('Kode Terkirim', 'Kode aktivasi baru telah dikirim ke email Anda.')
  } catch (err) {
    toast.error('Gagal', err.response?.data?.message || 'Gagal mengirim ulang kode.')
    clearInterval(timer)
    resendCooldown.value = 0
  }
}
</script>

<style scoped>
.slide-fade-enter-active { transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-fade-leave-active { transition: all 0.2s ease; }
.slide-fade-enter-from { opacity: 0; transform: translateY(-8px) scale(0.97); }
.slide-fade-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
