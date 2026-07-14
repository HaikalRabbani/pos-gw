<template>
  <div class="min-h-screen flex bg-slate-50">
    <!-- Sidebar -->
    <aside
      :class="[collapsed ? 'w-16' : 'w-64']"
      class="bg-gradient-to-b from-teal-900 via-teal-900 to-teal-950 text-white flex flex-col shrink-0 transition-all duration-300 ease-[cubic-bezier(0.4,0,0.2,1)] relative z-10 overflow-hidden"
    >
      <!-- Decorative gradient blobs -->
      <div class="absolute -top-20 -right-20 w-40 h-40 bg-teal-700/20 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-20 -left-20 w-48 h-48 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

      <!-- Logo -->
      <div class="px-5 py-4 border-b border-white/10 flex items-center justify-between gap-2.5 relative z-10">
        <div class="flex items-center gap-2.5 overflow-hidden">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-400 to-amber-500 flex items-center justify-center shadow-lg shadow-amber-500/25 shrink-0">
            <i class="pi pi-shopping-bag text-teal-950 text-lg"></i>
          </div>
          <transition name="slide-text">
            <div v-if="!collapsed" class="flex flex-col">
              <span class="text-base font-bold tracking-tight leading-tight">POS<span class="text-amber-400">Admin</span></span>
              <span class="text-[10px] text-teal-400 font-medium tracking-wider uppercase">Management</span>
            </div>
          </transition>
        </div>
        <button
          @click="collapsed = !collapsed"
          class="w-7 h-7 rounded-lg flex items-center justify-center text-teal-400 hover:text-white hover:bg-white/10 transition shrink-0"
        >
          <i class="pi text-xs transition-transform duration-300" :class="collapsed ? 'pi-chevron-right' : 'pi-chevron-left'"></i>
        </button>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 p-3 space-y-0.5 overflow-y-auto scrollbar-thin relative z-10">
        <div v-if="auth.isManager && !collapsed" class="px-3 py-2 mb-1">
          <div class="text-[10px] text-teal-400/50 uppercase tracking-wider mb-1">Akses Outlet</div>
          <div class="flex flex-wrap gap-1">
            <span v-for="id in auth.outletIds" :key="id"
              class="text-[10px] px-1.5 py-0.5 rounded bg-teal-700/50 text-teal-200">
              Outlet #{{ id }}
            </span>
          </div>
        </div>

        <template v-for="group in visibleGroups" :key="group.title">
          <div v-if="!collapsed" class="flex items-center gap-2 px-3 pt-4 pb-1">
            <div class="h-px flex-1 bg-white/5"></div>
            <p class="text-[10px] font-semibold text-teal-400/70 uppercase tracking-[0.15em]">{{ group.title }}</p>
            <div class="h-px flex-1 bg-white/5"></div>
          </div>
          <router-link
            v-for="item in group.items" :key="item.path" :to="item.path"
            :title="collapsed ? item.label : ''"
            :class="[collapsed ? 'justify-center' : 'gap-3']"
            class="group relative flex items-center px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 text-teal-100/80 hover:text-white overflow-hidden"
            active-class="!text-white"
            v-slot="{ isActive }"
          >
            <!-- Active background -->
            <div
              v-if="isActive"
              class="absolute inset-0 bg-gradient-to-r from-teal-700/80 to-teal-600/40 rounded-xl shadow-inner"
            ></div>
            <!-- Hover background -->
            <div
              v-else
              class="absolute inset-0 bg-white/0 group-hover:bg-white/[0.06] rounded-xl transition-all duration-200"
            ></div>
            <!-- Active indicator bar -->
            <div
              v-if="isActive && !collapsed"
              class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-5 bg-amber-400 rounded-r-full shadow-sm shadow-amber-400/50"
            ></div>
            <!-- Icon -->
            <i
              :class="[item.icon, isActive ? 'text-amber-400' : 'text-teal-300/70 group-hover:text-teal-200']"
              class="relative z-10 w-5 text-center text-base shrink-0 transition-colors duration-200"
            ></i>
            <!-- Label -->
            <span v-if="!collapsed" class="relative z-10 font-medium">{{ item.label }}</span>
            <!-- Active dot indicator when collapsed -->
            <div
              v-if="isActive && collapsed"
              class="absolute -right-0.5 top-1/2 -translate-y-1/2 w-1.5 h-1.5 rounded-full bg-amber-400 shadow-sm shadow-amber-400/50"
            ></div>
          </router-link>
        </template>
      </nav>

      <!-- User Profile -->
      <div class="p-3 border-t border-white/10 relative z-10">
        <div
          :class="[collapsed ? 'justify-center' : 'gap-3']"
          class="flex items-center px-2 py-2 rounded-xl bg-white/[0.04]"
        >
          <div class="relative shrink-0">
            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-amber-400 to-amber-500 flex items-center justify-center text-sm font-bold text-teal-950 shadow-lg">
              {{ initials }}
            </div>
            <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-400 border-2 border-teal-900"></div>
          </div>
          <div v-if="!collapsed" class="flex-1 min-w-0">
            <p class="text-sm font-semibold truncate text-white">{{ auth.user?.name }}</p>
            <div class="flex items-center gap-1">
              <span class="text-[11px] text-teal-400/70 truncate">{{ auth.user?.email }}</span>
              <span class="text-[10px] px-1.5 py-0.5 rounded-full font-semibold"
                :class="roleBadgeClass">
                {{ auth.roleLabel }}
              </span>
            </div>
          </div>
        </div>
        <button
          @click="logout"
          :class="[collapsed ? 'justify-center' : 'gap-3']"
          class="mt-1.5 w-full flex items-center px-3 py-2.5 rounded-xl text-sm text-teal-400/70 hover:text-red-300 hover:bg-red-500/10 transition-all duration-200"
        >
          <i class="pi pi-sign-out text-base w-5 text-center shrink-0"></i>
          <span v-if="!collapsed">Keluar</span>
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-auto relative">
      <!-- Top bar -->
      <div class="sticky top-0 z-20 bg-slate-50/80 backdrop-blur-md border-b border-slate-200/60 px-6 py-3 flex items-center justify-between">
        <div>
          <h2 class="text-sm font-semibold text-slate-700">{{ currentPageTitle }}</h2>
          <p class="text-[11px] text-slate-400">{{ currentDate }}</p>
        </div>
        <div class="flex items-center gap-3">
          <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold"
            :class="topBarBadgeClass">
            {{ auth.roleLabel }}
          </span>
          <div class="text-xs text-slate-400">
            <i class="pi pi-user mr-1"></i>
            {{ auth.user?.name }}
          </div>
        </div>
      </div>

      <!-- Page Content -->
      <div class="p-6">
        <router-view />
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const collapsed = ref(false)

const menuGroups = [
  {
    title: 'Ringkasan',
    items: [
      { path: '/dashboard', label: 'Dashboard', icon: 'pi pi-chart-pie' },
    ],
  },
  {
    title: 'Transaksi',
    items: [
      { path: '/orders', label: 'Pesanan', icon: 'pi pi-receipt' },
      { path: '/kitchen', label: 'Dapur', icon: 'pi pi-box' },
    ],
  },
  {
    title: 'Master Data',
    items: [
      { path: '/menu', label: 'Menu', icon: 'pi pi-list' },
      { path: '/discounts', label: 'Diskon', icon: 'pi pi-percentage' },
      { path: '/taxes', label: 'Pajak', icon: 'pi pi-shield' },
    ],
  },
  {
    title: 'Pengaturan',
    items: [
      { path: '/users', label: 'Pengguna', icon: 'pi pi-users' },
      { path: '/outlets', label: 'Outlet', icon: 'pi pi-building' },
    ],
  },
  {
    title: 'Laporan',
    items: [
      { path: '/report', label: 'Laporan', icon: 'pi pi-chart-bar' },
    ],
  },
]

/**
 * Filter menu groups based on user role.
 * - Developer / Admin (Owner): sees everything
 * - Manager: hides Pengaturan group (Users & Outlets)
 * - Cashier/Kitchen (Staff): redirected away by route guard
 */
const visibleGroups = computed(() => {
  if (auth.isSuper) return menuGroups
  if (auth.isManager) return menuGroups.filter((_, i) => i !== 3) // hide Pengaturan
  return [] // staff shouldn't reach here
})

const roleBadgeClass = computed(() => {
  const map = {
    developer: 'bg-purple-500/20 text-purple-300',
    admin: 'bg-amber-500/20 text-amber-300',
    manager: 'bg-blue-500/20 text-blue-300',
  }
  return map[auth.highestRole] || 'bg-teal-500/20 text-teal-300'
})

const topBarBadgeClass = computed(() => {
  const map = {
    developer: 'bg-purple-100 text-purple-700',
    admin: 'bg-amber-100 text-amber-700',
    manager: 'bg-blue-100 text-blue-700',
  }
  return map[auth.highestRole] || 'bg-teal-100 text-teal-700'
})

const pageTitles = {
  '/dashboard': 'Dashboard',
  '/orders': 'Pesanan',
  '/kitchen': 'Dapur',
  '/menu': 'Menu Management',
  '/discounts': 'Manajemen Diskon',
  '/taxes': 'Manajemen Pajak',
  '/users': 'Manajemen Pengguna',
  '/outlets': 'Manajemen Outlet',
  '/report': 'Laporan',
}

const currentPageTitle = computed(() => pageTitles[route.path] || 'Dashboard')

const currentDate = computed(() => {
  const d = new Date()
  return d.toLocaleDateString('id-ID', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
})

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

<style scoped>
.slide-text-enter-active,
.slide-text-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.slide-text-enter-from {
  opacity: 0;
  transform: translateX(-8px);
}
.slide-text-leave-to {
  opacity: 0;
  transform: translateX(8px);
}
</style>
