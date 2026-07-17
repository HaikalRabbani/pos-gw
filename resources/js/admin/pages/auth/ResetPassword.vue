<template>
  <div class="min-h-screen flex items-center justify-center p-6 relative overflow-hidden"
    style="background: linear-gradient(135deg, #f8fafc 0%, #f0fdfa 50%, #ecfdf5 100%);">
    <div class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-teal-100/40 blur-3xl"></div>
    <div class="absolute -bottom-32 -left-20 w-96 h-96 rounded-full bg-emerald-100/30 blur-3xl"></div>

    <div class="w-full max-w-md relative z-10">
      <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-700 flex items-center justify-center mx-auto mb-3 shadow-lg shadow-teal-200/50">
          <KeyRound class="w-7 h-7 text-white" stroke-width="1.5" />
        </div>
        <h1 class="text-xl font-bold text-slate-900">Reset Password</h1>
        <p class="text-sm text-slate-500 mt-1">Buat password baru untuk akun Anda</p>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xl shadow-slate-200/50 p-7 sm:p-8">
        <form @submit.prevent="handleReset" class="space-y-5">

          <transition name="slide-fade">
            <div v-if="errorMsg"
              class="px-4 py-3 rounded-xl bg-red-50 border border-red-100 text-xs text-red-700 flex items-start gap-2.5">
              <AlertCircle class="w-4 h-4 text-red-500 shrink-0 mt-0.5" stroke-width="1.5" />
              <span>{{ errorMsg }}</span>
            </div>
          </transition>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
            <InputText :value="routeEmail" disabled class="w-full opacity-60" />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Password Baru</label>
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                <Lock class="w-5 h-5 text-slate-400" stroke-width="1.5" />
              </div>
              <InputText v-model="password" type="password" placeholder="Minimal 6 karakter" required
                class="w-full" style="padding-left: 2.75rem !important" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Konfirmasi Password Baru</label>
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                <Lock class="w-5 h-5 text-slate-400" stroke-width="1.5" />
              </div>
              <InputText v-model="passwordConfirmation" type="password" placeholder="Ulangi password" required
                class="w-full" style="padding-left: 2.75rem !important" />
            </div>
          </div>

          <Button type="submit" label="Reset Password" class="w-full" :loading="loading" size="large" />

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
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import { KeyRound, Lock, AlertCircle } from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()
const toast = useToastStore()

const loading = ref(false)
const errorMsg = ref('')
const password = ref('')
const passwordConfirmation = ref('')

const routeEmail = computed(() => route.query.email || '')
const routeToken = computed(() => route.query.token || '')

onMounted(() => {
  if (!routeEmail.value || !routeToken.value) {
    toast.error('Tautan Tidak Valid', 'Link reset password tidak valid. Silakan minta ulang.')
    router.push('/forgot-password')
  }
})

async function handleReset() {
  errorMsg.value = ''

  if (password.value !== passwordConfirmation.value) {
    errorMsg.value = 'Konfirmasi password tidak cocok.'
    return
  }

  if (password.value.length < 6) {
    errorMsg.value = 'Password minimal 6 karakter.'
    return
  }

  loading.value = true
  try {
    const { data } = await client.post('/auth/reset-password', {
      email: routeEmail.value,
      token: routeToken.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    toast.success('Password Diubah', data.message || 'Password berhasil direset. Silakan login.')
    router.push('/login')
  } catch (err) {
    errorMsg.value = err.response?.data?.errors?.token?.[0]
      || err.response?.data?.message
      || 'Gagal mereset password. Silakan coba lagi.'
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
