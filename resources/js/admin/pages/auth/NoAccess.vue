<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200">
    <div class="w-full max-w-sm mx-4 text-center">
      <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-red-100 mb-6">
        <i class="pi pi-lock text-red-500 text-3xl"></i>
      </div>
      <h1 class="text-2xl font-bold text-slate-900 mb-2">Akses Dibatasi</h1>
      <p class="text-slate-500 text-sm mb-2">
        Akun Anda ({{ auth.roleLabel }}) tidak memiliki akses ke panel admin.
      </p>
      <p class="text-slate-400 text-xs mb-6">
        Silakan gunakan aplikasi POS mobile untuk melakukan transaksi.
      </p>
      <Button label="Kembali ke Login" icon="pi pi-arrow-left" @click="logout" />
    </div>
  </div>
</template>

<script setup>
import { useAuthStore } from '../../stores/auth'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'

const auth = useAuthStore()
const router = useRouter()

function logout() {
  auth.logout()
  router.push('/login')
}
</script>
