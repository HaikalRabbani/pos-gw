<template>
  <div class="min-h-screen flex bg-slate-50">
    <aside :class="[collapsed ? 'w-16' : 'w-64']" class="bg-teal-900 text-white flex flex-col shrink-0 transition-all duration-200 relative">
      <div class="px-5 py-4 border-b border-teal-800 flex items-center justify-between gap-2.5">
        <div class="flex items-center gap-2.5 overflow-hidden">
          <i class="pi pi-shopping-bag text-amber-400 text-xl shrink-0"></i>
          <span v-if="!collapsed" class="text-lg font-bold tracking-tight whitespace-nowrap">POS<span class="text-amber-400">Admin</span></span>
        </div>
        <button @click="collapsed = !collapsed"
          class="w-7 h-7 rounded flex items-center justify-center text-teal-400 hover:text-white hover:bg-teal-800 transition shrink-0">
          <i class="pi text-xs" :class="collapsed ? 'pi-chevron-right' : 'pi-chevron-left'"></i>
        </button>
      </div>
      <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
        <template v-for="group in menuGroups" :key="group.title">
          <p v-if="!collapsed" class="px-3 text-xs font-semibold text-teal-400 uppercase tracking-wider mt-2 mb-1">{{ group.title }}</p>
          <router-link v-for="item in group.items" :key="item.path" :to="item.path"
            :title="collapsed ? item.label : ''"
            :class="[collapsed ? 'justify-center' : 'gap-3']"
            class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition text-teal-100 hover:bg-teal-800 hover:text-white"
            active-class="!bg-teal-700 !text-white shadow-sm">
            <i :class="item.icon" class="w-5 text-center text-base shrink-0"></i>
            <span v-if="!collapsed">{{ item.label }}</span>
          </router-link>
          <div v-if="!collapsed" class="border-t border-teal-800 my-3"></div>
        </template>
      </nav>
      <div class="p-3 border-t border-teal-800">
        <div :class="[collapsed ? 'justify-center' : 'gap-3']" class="flex items-center px-3 py-2">
          <div class="w-8 h-8 rounded-full bg-amber-500 flex items-center justify-center text-sm font-bold text-teal-950 shrink-0">
            {{ initials }}
          </div>
          <div v-if="!collapsed" class="flex-1 min-w-0">
            <p class="text-sm font-medium truncate">{{ auth.user?.name }}</p>
            <p class="text-xs text-teal-400 truncate">{{ auth.user?.email }}</p>
          </div>
        </div>
        <button @click="logout"
          :class="[collapsed ? 'justify-center' : 'gap-3']"
          class="mt-1 w-full flex items-center px-3 py-2.5 rounded-lg text-sm text-teal-400 hover:bg-teal-800 hover:text-white transition">
          <i class="pi pi-sign-out text-base w-5 text-center shrink-0"></i>
          <span v-if="!collapsed">Keluar</span>
        </button>
      </div>
    </aside>
    <main class="flex-1 p-6 overflow-auto">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const router = useRouter()
const auth = useAuthStore()
const collapsed = ref(false)

const menuGroups = [
  {
    title: 'Operasional',
    items: [
      { path: '/dashboard', label: 'Dashboard', icon: 'pi pi-chart-pie' },
      { path: '/pos', label: 'POS Kasir', icon: 'pi pi-shopping-cart' },
      { path: '/orders', label: 'Pesanan', icon: 'pi pi-receipt' },
      { path: '/kitchen', label: 'Dapur', icon: 'pi pi-box' },
    ],
  },
  {
    title: 'Pengaturan',
    items: [
      { path: '/menu', label: 'Menu', icon: 'pi pi-list' },
      { path: '/users', label: 'Pengguna', icon: 'pi pi-users' },
    ],
  },
  {
    title: 'Laporan',
    items: [
      { path: '/report', label: 'Laporan', icon: 'pi pi-chart-bar' },
    ],
  },
]

const initials = computed(() => {
  const name = auth.user?.name || ''
  return name
    .split(' ')
    .map((w) => w[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

function logout() {
  auth.logout()
  router.push('/login')
}
</script>
