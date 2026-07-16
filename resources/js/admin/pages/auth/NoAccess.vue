<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-50 to-white p-4">
    <div class="w-full max-w-sm text-center">
      <div class="w-16 h-16 rounded-2xl bg-red-100 flex items-center justify-center mx-auto mb-4">
        <Lock class="w-8 h-8 text-red-500" stroke-width="1.5" />
      </div>
      <h1 class="text-2xl font-bold text-slate-900 mb-2">Akses Terbatas</h1>
      <p class="text-sm text-slate-500 mb-6">
        Anda tidak memiliki izin untuk mengakses halaman ini.
      </p>
      <Button label="Kembali ke Login" class="w-full" @click="logout">
        <template #icon>
          <ArrowLeft class="w-4 h-4" stroke-width="1.5" />
        </template>
      </Button>
    </div>
  </div>
</template>

<script setup>
import { useAuthStore } from '../../stores/auth'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import { Lock, ArrowLeft } from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()

function logout() {
  auth.logout()
  router.replace('/login')
}
</script>
