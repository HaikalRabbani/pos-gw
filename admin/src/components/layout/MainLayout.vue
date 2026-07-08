<template>
  <div class="min-h-screen flex">
    <aside class="w-64 bg-gray-900 text-white p-4 flex flex-col">
      <h1 class="text-xl font-bold mb-6">POS Admin</h1>
      <nav class="flex flex-col gap-2 flex-1">
        <router-link v-for="item in menu" :key="item.path" :to="item.path"
          class="px-3 py-2 rounded hover:bg-gray-700 transition"
          active-class="bg-gray-700">
          <i :class="item.icon" class="mr-2"></i>{{ item.label }}
        </router-link>
      </nav>
      <button @click="logout" class="mt-auto px-3 py-2 bg-red-700 rounded hover:bg-red-600">
        Logout
      </button>
    </aside>
    <main class="flex-1 p-6 bg-gray-100 overflow-auto">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const router = useRouter()
const auth = useAuthStore()

const menu = [
  { path: '/dashboard', label: 'Dashboard', icon: 'pi pi-home' },
  { path: '/pos', label: 'POS Kasir', icon: 'pi pi-shopping-cart' },
  { path: '/kitchen', label: 'Kitchen', icon: 'pi pi-box' },
  { path: '/menu', label: 'Menu', icon: 'pi pi-list' },
  { path: '/orders', label: 'Orders', icon: 'pi pi-receipt' },
  { path: '/report', label: 'Report', icon: 'pi pi-chart-bar' },
]

function logout() {
  auth.logout()
  router.push('/login')
}
</script>
