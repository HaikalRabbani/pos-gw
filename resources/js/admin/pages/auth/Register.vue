<template>
  <div class="min-h-screen flex overflow-hidden">
    <!-- ===== LEFT PANEL ===== -->
    <div class="hidden lg:flex lg:w-[480px] xl:w-[540px] relative flex-col justify-between p-12 overflow-hidden"
      style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 50%, #115e59 100%);">
      <div class="absolute -top-32 -right-32 w-96 h-96 rounded-full bg-white/5 blur-3xl"></div>
      <div class="absolute -bottom-40 -left-20 w-80 h-80 rounded-full bg-amber-400/10 blur-3xl"></div>
      <svg class="absolute inset-0 w-full h-full opacity-[0.04]" xmlns="http://www.w3.org/2000/svg">
        <defs><pattern id="grid-r" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="0.5"/></pattern></defs>
        <rect width="100%" height="100%" fill="url(#grid-r)"/>
      </svg>
      <div class="relative z-10">
        <div class="flex items-center gap-3 mb-12">
          <div class="w-11 h-11 rounded-2xl bg-white flex items-center justify-center shadow-lg shadow-black/10">
            <UserPlus class="w-6 h-6 text-teal-700" stroke-width="1.5" />
          </div>
          <span class="text-xl font-bold text-white tracking-tight">POS Admin</span>
        </div>
        <div class="space-y-4">
          <h2 class="text-3xl font-bold text-white leading-tight">Daftar Akun Baru</h2>
          <p class="text-teal-200 text-sm leading-relaxed max-w-sm">
            Buat akun untuk mulai mengelola bisnis Anda. Kami akan mengirimkan kode aktivasi ke email Anda.
          </p>
        </div>
      </div>
      <div class="relative z-10">
        <div class="flex flex-wrap gap-2">
          <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/10 text-teal-100 text-xs font-medium backdrop-blur-sm">
            <CheckCircle2 class="w-3.5 h-3.5 text-emerald-300" stroke-width="1.5" />
            Gratis Daftar
          </span>
          <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/10 text-teal-100 text-xs font-medium backdrop-blur-sm">
            <CheckCircle2 class="w-3.5 h-3.5 text-emerald-300" stroke-width="1.5" />
            Aktivasi Email
          </span>
        </div>
        <p class="text-teal-300/60 text-xs mt-6">&copy; 2026 POS Admin. All rights reserved.</p>
      </div>
    </div>

    <!-- ===== RIGHT PANEL ===== -->
    <div class="flex-1 flex items-center justify-center p-6 relative overflow-hidden"
      style="background: linear-gradient(135deg, #f8fafc 0%, #f0fdfa 50%, #ecfdf5 100%);">
      <div class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-teal-100/40 blur-3xl"></div>
      <div class="absolute -bottom-32 -left-20 w-96 h-96 rounded-full bg-emerald-100/30 blur-3xl"></div>

      <div class="w-full max-w-md relative z-10">
        <div class="lg:hidden text-center mb-8">
          <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-700 flex items-center justify-center mx-auto mb-3 shadow-lg shadow-teal-200/50">
            <UserPlus class="w-7 h-7 text-white" stroke-width="1.5" />
          </div>
          <h1 class="text-xl font-bold text-slate-900">Daftar Akun</h1>
          <p class="text-xs text-slate-500 mt-0.5">Buat akun baru Anda</p>
        </div>

        <div class="hidden lg:block mb-8">
          <h1 class="text-2xl font-bold text-slate-900">Daftar Akun</h1>
          <p class="text-sm text-slate-500 mt-1">Isi data Anda untuk membuat akun baru</p>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xl shadow-slate-200/50 p-7 sm:p-8">
          <form @submit.prevent="handleRegister" class="space-y-5">

            <transition name="slide-fade">
              <div v-if="errorMsg"
                class="px-4 py-3 rounded-xl bg-red-50 border border-red-100 text-xs text-red-700 flex items-start gap-2.5">
                <AlertCircle class="w-4 h-4 text-red-500 shrink-0 mt-0.5" stroke-width="1.5" />
                <span>{{ errorMsg }}</span>
              </div>
            </transition>

            <!-- Nama -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama</label>
              <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                  <User class="w-5 h-5 text-slate-400" stroke-width="1.5" />
                </div>
                <InputText v-model="form.name" type="text" placeholder="Nama lengkap" required
                  class="w-full" style="padding-left: 2.75rem !important" />
              </div>
            </div>

            <!-- Email -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
              <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                  <Mail class="w-5 h-5 text-slate-400" stroke-width="1.5" />
                </div>
                <InputText v-model="form.email" type="email" placeholder="nama@email.com" required
                  class="w-full" style="padding-left: 2.75rem !important" />
              </div>
            </div>

            <!-- Password -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
              <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                  <Lock class="w-5 h-5 text-slate-400" stroke-width="1.5" />
                </div>
                <InputText v-model="form.password" type="password" placeholder="Minimal 6 karakter" required
                  class="w-full" style="padding-left: 2.75rem !important" />
              </div>
            </div>

            <!-- Konfirmasi Password -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Konfirmasi Password</label>
              <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                  <Lock class="w-5 h-5 text-slate-400" stroke-width="1.5" />
                </div>
                <InputText v-model="form.passwordConfirmation" type="password" placeholder="Ulangi password" required
                  class="w-full" style="padding-left: 2.75rem !important" />
              </div>
            </div>

            <Button type="submit" label="Daftar" class="w-full" :loading="loading" size="large" />

            <div class="text-center pt-1">
              <span class="text-sm text-slate-500">Sudah punya akun? </span>
              <router-link to="/login" class="text-sm text-teal-600 hover:text-teal-700 font-medium transition-colors duration-150">
                Masuk
              </router-link>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import client from '../../api/client'
import { useToastStore } from '../../stores/toast'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import { UserPlus, User, Mail, Lock, AlertCircle, CheckCircle2 } from '@lucide/vue'

const router = useRouter()
const toast = useToastStore()

const loading = ref(false)
const errorMsg = ref('')

const form = ref({
  name: '',
  email: '',
  password: '',
  passwordConfirmation: '',
})

async function handleRegister() {
  errorMsg.value = ''

  if (form.value.password !== form.value.passwordConfirmation) {
    errorMsg.value = 'Konfirmasi password tidak cocok.'
    return
  }

  if (form.value.password.length < 6) {
    errorMsg.value = 'Password minimal 6 karakter.'
    return
  }

  loading.value = true
  try {
    const { data } = await client.post('/auth/register', {
      name: form.value.name,
      email: form.value.email,
      password: form.value.password,
    })
    toast.success('Berhasil Daftar', data.message || 'Silakan cek email untuk kode aktivasi.')
    router.push({ path: '/verify-email', query: { email: form.value.email } })
  } catch (err) {
    errorMsg.value = err.response?.data?.message
      || err.response?.data?.errors?.email?.[0]
      || 'Gagal mendaftar. Silakan coba lagi.'
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
