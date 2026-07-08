<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="w-full max-w-sm bg-white rounded-xl shadow-lg p-8">
      <h1 class="text-2xl font-bold text-center mb-6">POS Login</h1>
      <form @submit.prevent="handleLogin">
        <div class="mb-4">
          <label class="block text-sm font-medium mb-1">Email</label>
          <InputText v-model="email" type="email" class="w-full" placeholder="admin@pos.com" required />
        </div>
        <div class="mb-6">
          <label class="block text-sm font-medium mb-1">Password</label>
          <InputText v-model="password" type="password" class="w-full" placeholder="password" required />
        </div>
        <Button type="submit" label="Login" class="w-full" :loading="loading" />
      </form>
      <p class="text-center mt-4 text-sm">
        <router-link to="/pin-login" class="text-blue-600 hover:underline">Login PIN</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'

const router = useRouter()
const auth = useAuthStore()
const email = ref('')
const password = ref('')
const loading = ref(false)

async function handleLogin() {
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    router.push('/dashboard')
  } catch (e) {
    alert(e.response?.data?.message || 'Login failed')
  } finally {
    loading.value = false
  }
}
</script>
