<template>
  <div class="min-h-screen flex overflow-hidden">
    <!-- ===== LEFT PANEL: Branding & Visual ===== -->
    <div class="hidden lg:flex lg:w-[480px] xl:w-[540px] relative flex-col justify-between p-12 overflow-hidden"
      style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 50%, #115e59 100%);">
      <!-- Decorative blobs -->
      <div class="absolute -top-32 -right-32 w-96 h-96 rounded-full bg-white/5 blur-3xl"></div>
      <div class="absolute -bottom-40 -left-20 w-80 h-80 rounded-full bg-amber-400/10 blur-3xl"></div>
      <div class="absolute top-1/3 -left-16 w-64 h-64 rounded-full bg-teal-300/10 blur-3xl"></div>
      <div class="absolute bottom-1/4 right-8 w-48 h-48 rounded-full bg-emerald-300/8 blur-2xl"></div>

      <!-- Grid pattern overlay -->
      <svg class="absolute inset-0 w-full h-full opacity-[0.04]" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
            <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="0.5" />
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#grid)" />
      </svg>

      <!-- Top section -->
      <div class="relative z-10">
        <div class="flex items-center gap-3 mb-12">
          <div class="w-11 h-11 rounded-2xl bg-white flex items-center justify-center shadow-lg shadow-black/10">
            <ShoppingBag class="w-6 h-6 text-teal-700" stroke-width="1.5" />
          </div>
          <span class="text-xl font-bold text-white tracking-tight">POS Admin</span>
        </div>

        <div class="space-y-4">
          <h2 class="text-3xl font-bold text-white leading-tight">Kelola Bisnis<br />Minuman Anda</h2>
          <p class="text-teal-200 text-sm leading-relaxed max-w-sm">
            Platform POS lengkap untuk mengelola pesanan, stok, karyawan, dan laporan keuangan dalam satu tempat.
          </p>
        </div>
      </div>

      <!-- Bottom section -->
      <div class="relative z-10 space-y-6">
        <!-- Feature pills -->
        <div class="flex flex-wrap gap-2">
          <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/10 text-teal-100 text-xs font-medium backdrop-blur-sm">
            <CheckCircle2 class="w-3.5 h-3.5 text-emerald-300" stroke-width="1.5" />
            Pesanan Real-time
          </span>
          <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/10 text-teal-100 text-xs font-medium backdrop-blur-sm">
            <CheckCircle2 class="w-3.5 h-3.5 text-emerald-300" stroke-width="1.5" />
            Multi-outlet
          </span>
          <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/10 text-teal-100 text-xs font-medium backdrop-blur-sm">
            <CheckCircle2 class="w-3.5 h-3.5 text-emerald-300" stroke-width="1.5" />
            Laporan Otomatis
          </span>
        </div>

        <p class="text-teal-300/60 text-xs">
          &copy; 2026 POS Admin. All rights reserved.
        </p>
      </div>
    </div>

    <!-- ===== RIGHT PANEL: Login Form ===== -->
    <div class="flex-1 flex items-center justify-center p-6 relative overflow-hidden"
      style="background: linear-gradient(135deg, #f8fafc 0%, #f0fdfa 50%, #ecfdf5 100%);">
      <!-- Decorative blobs (mobile/right side) -->
      <div class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-teal-100/40 blur-3xl"></div>
      <div class="absolute -bottom-32 -left-20 w-96 h-96 rounded-full bg-emerald-100/30 blur-3xl"></div>

      <div class="w-full max-w-md relative z-10">
        <!-- Mobile Logo -->
        <div class="lg:hidden text-center mb-8">
          <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-700 flex items-center justify-center mx-auto mb-3 shadow-lg shadow-teal-200/50">
            <ShoppingBag class="w-7 h-7 text-white" stroke-width="1.5" />
          </div>
          <h1 class="text-xl font-bold text-slate-900">POS Admin</h1>
          <p class="text-xs text-slate-500 mt-0.5">Masuk ke akun Anda</p>
        </div>

        <!-- Desktop Header -->
        <div class="hidden lg:block mb-8">
          <h1 class="text-2xl font-bold text-slate-900">Selamat Datang</h1>
          <p class="text-sm text-slate-500 mt-1">Masuk ke akun Anda untuk melanjutkan</p>
        </div>

        <!-- Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xl shadow-slate-200/50 p-7 sm:p-8">
          <form @submit.prevent="handleLogin" class="space-y-5">

            <!-- Error Alert -->
            <transition name="slide-fade">
              <div v-if="errorMsg"
                class="px-4 py-3 rounded-xl bg-red-50 border border-red-100 text-xs text-red-700 flex items-start gap-2.5">
                <AlertCircle class="w-4 h-4 text-red-500 shrink-0 mt-0.5" stroke-width="1.5" />
                <span>{{ errorMsg }}</span>
              </div>
            </transition>

            <!-- Email -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
              <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                  <Mail class="w-5 h-5 text-slate-400" stroke-width="1.5" />
                </div>
                <InputText
                  v-model="form.email"
                  type="email"
                  placeholder="admin@example.com"
                  required
                  class="w-full"
                  style="padding-left: 2.75rem !important"
                  @input="errorMsg = ''"
                />
              </div>
            </div>

            <!-- Password -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
              <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                  <Lock class="w-5 h-5 text-slate-400" stroke-width="1.5" />
                </div>
                <InputText
                  v-model="form.password"
                  type="password"
                  placeholder="••••••••"
                  required
                  class="w-full"
                  style="padding-left: 2.75rem !important"
                  @input="errorMsg = ''"
                />
              </div>
            </div>

            <!-- Outlet Selector -->
            <div v-if="outlets.length > 1">
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Outlet</label>
              <Select
                v-model="form.outlet_id"
                :options="outlets"
                optionLabel="name"
                optionValue="id"
                placeholder="Pilih outlet"
                class="w-full"
              />
              <p v-if="!form.outlet_id" class="text-xs text-amber-600 mt-1.5 flex items-center gap-1">
                <Info class="w-3.5 h-3.5" stroke-width="1.5" />
                Pilih outlet untuk login
              </p>
            </div>

            <!-- Submit -->
            <Button
              type="submit"
              label="Masuk"
              class="w-full"
              :loading="loading"
              size="large"
            />

            <!-- PIN Login & Links -->
            <div class="space-y-3 pt-1">
              <div class="text-center">
                <button
                  type="button"
                  class="text-sm text-teal-600 hover:text-teal-700 font-medium transition-colors duration-150"
                  @click="switchToPin"
                >
                  <span class="flex items-center justify-center gap-1.5">
                    <KeyRound class="w-4 h-4" stroke-width="1.5" />
                    Login dengan PIN
                  </span>
                </button>
              </div>

              <div class="flex items-center justify-center gap-4 pt-1 border-t border-slate-100">
                <router-link to="/register" class="text-xs text-teal-600 hover:text-teal-700 font-medium transition-colors duration-150">
                  Daftar Akun
                </router-link>
                <span class="text-slate-300">|</span>
                <router-link to="/forgot-password" class="text-xs text-teal-600 hover:text-teal-700 font-medium transition-colors duration-150">
                  Lupa Password
                </router-link>
              </div>
            </div>
          </form>
        </div>

        <!-- Desktop copyright -->
        <p class="hidden lg:block text-xs text-slate-400 text-center mt-6">
          &copy; 2026 POS Admin. All rights reserved.
        </p>
      </div>
    </div>

    <!-- ===== PIN LOGIN DIALOG ===== -->
    <Dialog v-model:visible="showPinDialog" header="Login dengan PIN" modal class="w-sm">
      <form @submit.prevent="handlePinLogin" class="space-y-5 pt-1">
        <p class="text-sm text-slate-600">Masukkan PIN 6 digit Anda untuk login cepat.</p>


        <div>
          <InputMask
            v-model="pinForm.pin"
            mask="999999"
            placeholder="******"
            class="w-full text-center text-2xl tracking-[0.3em]"
            required
          />
        </div>

        <div v-if="outlets.length > 1">
          <Select
            v-model="pinForm.outlet_id"
            :options="outlets"
            optionLabel="name"
            optionValue="id"
            placeholder="Pilih outlet"
            class="w-full"
          />
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <Button label="Batal" severity="secondary" @click="showPinDialog = false" />
          <Button type="submit" label="Login" :loading="pinLoading" />
        </div>
      </form>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useToastStore } from '../../stores/toast'
import client from '../../api/client'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputMask from 'primevue/inputmask'
import Select from 'primevue/select'
import Dialog from 'primevue/dialog'
import {
  ShoppingBag, Mail, Lock, AlertCircle, KeyRound,
  CheckCircle2, Info
} from 'lucide-vue-next'

const router = useRouter()
const auth = useAuthStore()
const toast = useToastStore()

const loading = ref(false)
const pinLoading = ref(false)
const errorMsg = ref('')
const outlets = ref([])
const showPinDialog = ref(false)

const form = ref({ email: '', password: '', outlet_id: null })
const pinForm = ref({ pin: '', outlet_id: null })

onMounted(async () => {
  try {
    const { data } = await client.get('/outlets')
    outlets.value = data.data || []
    if (outlets.value.length === 1) {
      form.value.outlet_id = outlets.value[0].id
      pinForm.value.outlet_id = outlets.value[0].id
    }
  } catch (_) {}
})

async function handleLogin() {
  errorMsg.value = ''
  loading.value = true
  try {
    const payload = {
      email: form.value.email,
      password: form.value.password,
    }
    if (outlets.value.length > 1 && form.value.outlet_id) {
      payload.outlet_id = form.value.outlet_id
    }
    const { data } = await client.post('/auth/login', payload)
    auth.setSession(data.data)
    router.replace('/dashboard')
  } catch (err) {
    errorMsg.value = err.response?.data?.message || 'Gagal login. Periksa email dan password Anda.'
  } finally {
    loading.value = false
  }
}

function switchToPin() {
  pinForm.value.pin = ''
  pinForm.value.outlet_id = outlets.value.length === 1 ? outlets.value[0].id : null
  showPinDialog.value = true
}

async function handlePinLogin() {
  pinLoading.value = true
  try {
    const payload = { pin: pinForm.value.pin }
    if (outlets.value.length > 1 && pinForm.value.outlet_id) {
      payload.outlet_id = pinForm.value.outlet_id
    }
    const { data } = await client.post('/auth/pin-login', payload)
    auth.setSession(data.data)
    router.replace('/dashboard')
  } catch (err) {
    const message = err.response?.data?.message || 'PIN salah. Silakan coba lagi.'
    toast.error('Login Gagal', message)
  } finally {
    pinLoading.value = false
  }
}
</script>

<style scoped>
/* Error alert animation */
.slide-fade-enter-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-fade-leave-active {
  transition: all 0.2s ease;
}
.slide-fade-enter-from {
  opacity: 0;
  transform: translateY(-8px) scale(0.97);
}
.slide-fade-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
