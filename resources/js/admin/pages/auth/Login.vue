<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-teal-50 via-white to-slate-50 p-4">
    <div class="w-full max-w-sm">
      <!-- Logo & Header -->
      <div class="text-center mb-8">
        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-700 flex items-center justify-center mx-auto mb-4 shadow-lg shadow-teal-200/50">
          <ShoppingBag class="w-8 h-8 text-white" stroke-width="1.5" />
        </div>
        <h1 class="text-2xl font-bold text-slate-900">FreeBrew POS</h1>
        <p class="text-sm text-slate-500 mt-1">Masuk untuk melanjutkan</p>
      </div>

      <!-- Login Card -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <form @submit.prevent="handleLogin" class="space-y-4">
          <!-- Alert -->
          <transition name="fade">
            <div v-if="errorMsg"
              class="px-3 py-2.5 rounded-xl bg-red-50 border border-red-100 text-xs text-red-700 flex items-center gap-2">
              <AlertCircle class="w-4 h-4 text-red-500 shrink-0" stroke-width="1.5" />
              <span>{{ errorMsg }}</span>
            </div>
          </transition>

          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <div class="relative">
              <Mail class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" stroke-width="1.5" />
              <InputText v-model="form.email" type="email" class="w-full pl-10" placeholder="admin@example.com" required />
            </div>
          </div>

          <!-- Password -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
            <div class="relative">
              <Lock class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" stroke-width="1.5" />
              <InputText v-model="form.password" type="password" class="w-full pl-10" placeholder="••••••••" required />
            </div>
          </div>

          <!-- Outlet (multi-outlet) -->
          <div v-if="outlets.length > 1">
            <label class="block text-sm font-medium text-slate-700 mb-1">Outlet</label>
            <Select v-model="form.outlet_id" :options="outlets" optionLabel="name" optionValue="id"
              placeholder="Pilih outlet" class="w-full" />
            <p v-if="outlets.length > 1 && !form.outlet_id" class="text-xs text-amber-600 mt-1">
              Pilih outlet untuk login
            </p>
          </div>

          <Button type="submit" label="Masuk" class="w-full" :loading="loading" />

          <!-- PIN Login Link -->
          <div class="text-center pt-1">
            <button type="button" class="text-xs text-teal-600 hover:text-teal-700 font-medium"
              @click="switchToPin">
              Login dengan PIN
            </button>
          </div>
        </form>
      </div>

      <p class="text-xs text-slate-400 text-center mt-6">
        &copy; 2026 FreeBrew POS. All rights reserved.
      </p>
    </div>

    <!-- PIN Login Dialog -->
    <Dialog v-model:visible="showPinDialog" header="Login dengan PIN" modal class="w-sm">
      <form @submit.prevent="handlePinLogin" class="space-y-4">
        <p class="text-sm text-slate-600">Masukkan PIN 6 digit Anda untuk login cepat.</p>
        <div v-if="pinError" class="px-3 py-2 rounded-xl bg-red-50 border border-red-100 text-xs text-red-700 flex items-center gap-2">
          <AlertCircle class="w-4 h-4 text-red-500 shrink-0" stroke-width="1.5" />
          <span>{{ pinError }}</span>
        </div>
        <div>
          <InputMask v-model="pinForm.pin" mask="999999" placeholder="******"
            class="w-full text-center text-2xl" required />
        </div>
        <div v-if="outlets.length > 1">
          <Select v-model="pinForm.outlet_id" :options="outlets" optionLabel="name" optionValue="id"
            placeholder="Pilih outlet" class="w-full" />
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
import client from '../../api/client'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputMask from 'primevue/inputmask'
import Select from 'primevue/select'
import Dialog from 'primevue/dialog'
import { ShoppingBag, Mail, Lock, AlertCircle } from 'lucide-vue-next'

const router = useRouter()
const auth = useAuthStore()

const loading = ref(false)
const pinLoading = ref(false)
const errorMsg = ref('')
const pinError = ref('')
const outlets = ref([])
const showPinDialog = ref(false)

const form = ref({ email: '', password: '', outlet_id: null })
const pinForm = ref({ pin: '', outlet_id: null })

// On mount set default outlet
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
  pinError.value = ''
  pinForm.value.pin = ''
  pinForm.value.outlet_id = outlets.value.length === 1 ? outlets.value[0].id : null
  showPinDialog.value = true
}

async function handlePinLogin() {
  pinError.value = ''
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
    pinError.value = err.response?.data?.message || 'PIN salah. Silakan coba lagi.'
  } finally {
    pinLoading.value = false
  }
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
