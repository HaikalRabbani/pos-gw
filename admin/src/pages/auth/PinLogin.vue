<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="w-full max-w-sm bg-white rounded-xl shadow-lg p-8">
      <h1 class="text-2xl font-bold text-center mb-6">PIN Login</h1>
      <form @submit.prevent="handleLogin">
        <div class="mb-6">
          <label class="block text-sm font-medium mb-1">PIN</label>
          <InputMask v-model="pin" mask="9999" placeholder="****" class="w-full text-center text-2xl" required />
        </div>
        <Button type="submit" label="Login" class="w-full" :loading="loading" />
      </form>
      <p class="text-center mt-4 text-sm">
        <router-link to="/login" class="text-blue-600 hover:underline">Login Email</router-link>
      </p>
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
