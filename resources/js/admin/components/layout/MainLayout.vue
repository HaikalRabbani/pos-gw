<template>
  <div class="relative h-screen flex bg-slate-50 overflow-hidden">
    <!-- Sidebar -->
    <aside
      :class="[collapsed ? 'w-16' : 'w-64']"
      class="relative h-full bg-gradient-to-b from-teal-900 via-teal-900 to-teal-950 text-white flex flex-col shrink-0 transition-all duration-300 ease-[cubic-bezier(0.4,0,0.2,1)] z-10"
    >
      <!-- Inner wrapper: decorative blobs + clip -->
      <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-20 -right-20 w-40 h-40 bg-teal-700/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-20 -left-20 w-48 h-48 bg-amber-500/10 rounded-full blur-3xl"></div>
      </div>

      <!-- Logo -->
      <div :class="[collapsed ? 'px-[14px]' : 'px-5']" class="relative py-4 border-b border-white/10 flex items-center gap-2.5 z-10 shrink-0">
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
      </div>

      <!-- Navigation (scrollable dengan thin scrollbar) -->
      <nav class="flex-1 p-3 space-y-0.5 overflow-y-auto relative z-10 scrollbar-sidebar">
        <div v-if="auth.isManager && !collapsed" class="px-3 py-2 mb-1">
          <div class="text-[10px] text-teal-400/50 uppercase tracking-wider mb-1">Akses Outlet</div>
          <div class="flex flex-wrap gap-1">
            <span v-for="outlet in auth.user?.outlets" :key="outlet.id"
              class="text-[10px] px-1.5 py-0.5 rounded bg-teal-700/50 text-teal-200">
              {{ outlet.name }}
            </span>
          </div>
        </div>

        <template v-for="group in visibleGroups" :key="group.title">
          <!-- ====== EXPANDED MODE ====== -->
          <template v-if="!collapsed">
            <!-- Group Header (clickable toggle) -->
            <button
              type="button"
              @click="toggleGroup(group.title)"
              class="w-full flex items-center justify-between mt-3 mb-0.5 px-2.5 py-1.5 rounded-lg text-left select-none transition-colors duration-150 hover:bg-white/[0.06]"
            >
              <span class="text-[10px] font-semibold text-teal-300/80 uppercase tracking-[0.08em]">{{ group.title }}</span>
              <span class="flex items-center justify-center w-[18px] h-[18px] rounded-md bg-white/10 shrink-0">
                <i
                  class="pi pi-chevron-down text-[9px] text-teal-100 transition-transform duration-200"
                  :class="{ 'rotate-180': expandedGroups[group.title] }"
                ></i>
              </span>
            </button>

            <!-- Submenu Items (with slide transition) -->
            <Transition name="submenu-slide">
              <div v-if="expandedGroups[group.title]" class="space-y-0.5 overflow-hidden">
                <router-link
                  v-for="item in group.items" :key="item.path" :to="item.path"
                  class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 text-teal-100/80 hover:text-white overflow-hidden"
                  active-class="!text-white"
                  v-slot="{ isActive }"
                >
                  <div v-if="isActive" class="absolute inset-0 bg-gradient-to-r from-teal-700/80 to-teal-600/40 rounded-xl shadow-inner"></div>
                  <div v-else class="absolute inset-0 bg-white/0 group-hover:bg-white/[0.06] rounded-xl transition-all duration-200"></div>
                  <div v-if="isActive" class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-5 bg-amber-400 rounded-r-full shadow-sm shadow-amber-400/50"></div>
                  <i :class="[item.icon, isActive ? 'text-amber-400' : 'text-teal-300/70 group-hover:text-teal-200']" class="relative z-10 w-5 text-center text-base shrink-0 transition-colors duration-200"></i>
                  <span class="relative z-10 font-medium">{{ item.label }}</span>
                </router-link>
              </div>
            </Transition>
          </template>

          <!-- ====== COLLAPSED MODE ====== -->
          <template v-else>
            <router-link
              v-for="item in group.items" :key="item.path" :to="item.path"
              :title="item.label"
              class="group relative flex items-center justify-center px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 text-teal-100/80 hover:text-white overflow-hidden"
              active-class="!text-white"
              v-slot="{ isActive }"
            >
              <div v-if="isActive" class="absolute inset-0 bg-gradient-to-r from-teal-700/80 to-teal-600/40 rounded-xl shadow-inner"></div>
              <div v-else class="absolute inset-0 bg-white/0 group-hover:bg-white/[0.06] rounded-xl transition-all duration-200"></div>
              <i :class="[item.icon, isActive ? 'text-amber-400' : 'text-teal-300/70 group-hover:text-teal-200']" class="relative z-10 text-base shrink-0 transition-colors duration-200"></i>
              <div v-if="isActive" class="absolute -right-0.5 top-1/2 -translate-y-1/2 w-1.5 h-1.5 rounded-full bg-amber-400 shadow-sm shadow-amber-400/50"></div>
            </router-link>
          </template>
        </template>
      </nav>

      <!-- Bottom — hanya logout -->
      <div class="p-3 border-t border-white/10 relative z-10 shrink-0">
        <button
          @click="logout"
          :class="[collapsed ? 'justify-center' : 'gap-3']"
          class="w-full flex items-center px-3 py-2.5 rounded-xl text-sm text-teal-400/70 hover:text-red-300 hover:bg-red-500/10 transition-all duration-200"
        >
          <i class="pi pi-sign-out text-base w-5 text-center shrink-0"></i>
          <span v-if="!collapsed">Keluar</span>
        </button>
      </div>
    </aside>

    <!-- Minimize Toggle Button — diluar sidebar & main (biar ga kena stacking context) -->
    <button
      @click="toggleCollapsed"
      :style="{ left: collapsed ? '52px' : '244px', top: '68px' }"
      class="absolute -translate-y-1/2 z-40 w-6 h-6 rounded-full bg-white border border-slate-200 shadow-sm flex items-center justify-center hover:bg-slate-50 transition-all duration-200 hover:shadow-md hover:scale-110"
    >
      <i
        class="pi text-[10px] text-slate-400"
        :class="collapsed ? 'pi-chevron-right' : 'pi-chevron-left'"
      ></i>
    </button>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto relative">
      <!-- Top bar — dengan info akun di pojok kanan -->
      <div class="sticky top-0 z-20 bg-slate-50/80 backdrop-blur-md border-b border-slate-200/60 px-6 py-3 flex items-center justify-between">
        <div>
          <h2 class="text-sm font-semibold text-slate-700">{{ currentPageTitle }}</h2>
          <p class="text-[11px] text-slate-400">{{ currentDate }}</p>
        </div>
        <div class="flex items-center gap-3 relative">
          <!-- User Info / Profile Dropdown -->
          <div class="relative" ref="profileDropdownRef">
            <button @click="toggleDropdown"
              class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-xl hover:bg-white/70 transition cursor-pointer">
              <div class="text-right">
                <p class="text-xs font-semibold text-slate-700 leading-tight">{{ auth.user?.name }}</p>
                <span class="text-[10px] px-1.5 py-0.5 rounded-full font-semibold inline-block mt-0.5"
                  :class="topBarBadgeClass">
                  {{ auth.roleLabel }}
                </span>
              </div>
              <div class="relative shrink-0">
                <img v-if="auth.user?.photo" :src="auth.user.photo"
                  class="w-8 h-8 rounded-full object-cover border-2 border-slate-200" />
                <div v-else
                  class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-400 to-amber-500 flex items-center justify-center text-xs font-bold text-teal-950 shadow-sm">
                  {{ initials }}
                </div>
                <div class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-400 border-2 border-slate-50"></div>
              </div>
            </button>

            <!-- Dropdown Menu -->
            <transition name="dropdown-slide">
              <div v-if="profileDropdown"
                class="absolute right-0 top-full mt-1 w-48 bg-white rounded-xl shadow-lg border border-slate-200 py-1 z-50">
                <div class="px-3 py-2 border-b border-slate-100">
                  <p class="text-xs font-semibold text-slate-800">{{ auth.user?.name }}</p>
                  <p class="text-[10px] text-slate-400">{{ auth.user?.email }}</p>
                </div>
                <router-link to="/profile" @click="profileDropdown = false"
                  class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition">
                  <i class="pi pi-user-edit text-sm"></i>
                  Pengaturan Profil
                </router-link>
                <button @click="logout"
                  class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-red-500 hover:bg-red-50 transition">
                  <i class="pi pi-sign-out text-sm"></i>
                  Keluar
                </button>
              </div>
            </transition>
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
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { roleBadgeClass } from '../../utils/roleBadge'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

// Profile dropdown
const profileDropdown = ref(false)
const profileDropdownRef = ref(null)

function toggleDropdown() {
  profileDropdown.value = !profileDropdown.value
}

function closeDropdown(e) {
  if (profileDropdownRef.value && !profileDropdownRef.value.contains(e.target)) {
    profileDropdown.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', closeDropdown)
  initExpandedGroups()
  expandActiveGroup()
})

onUnmounted(() => {
  document.removeEventListener('click', closeDropdown)
})

// Sidebar collapsed state — persisted in localStorage
const collapsed = ref(localStorage.getItem('sidebar_collapsed') === 'true')

function toggleCollapsed() {
  collapsed.value = !collapsed.value
  localStorage.setItem('sidebar_collapsed', collapsed.value)
}

const menuGroups = [
  {
    title: 'Dashboard',
    items: [
      { path: '/dashboard', label: 'Dashboard', icon: 'pi pi-chart-pie' },
    ],
  },
  {
    title: 'Transaksi',
    items: [
      { path: '/orders', label: 'Pesanan', icon: 'pi pi-receipt' },
    ],
  },
  {
    title: 'Laporan',
    items: [
      { path: '/report/financial', label: 'Keuangan', icon: 'pi pi-chart-bar' },
      { path: '/report/shift', label: 'Shift', icon: 'pi pi-history' },
    ],
  },
  {
    title: 'Master Data',
    items: [
      { path: '/menu', label: 'Menu', icon: 'pi pi-list' },
      { path: '/discounts', label: 'Diskon', icon: 'pi pi-percentage' },
      { path: '/taxes', label: 'Pajak', icon: 'pi pi-shield' },
      { path: '/tables', label: 'Meja', icon: 'pi pi-table' },
    ],
  },
  {
    title: 'Shift & Jadwal',
    items: [
      { path: '/shifts', label: 'Atur Shift', icon: 'pi pi-calendar-clock' },
    ],
  },
  {
    title: 'Keuangan',
    items: [
      { path: '/withdraw', label: 'Penarikan', icon: 'pi pi-credit-card' },
    ],
  },
  {
    title: 'Pengaturan',
    items: [
      { path: '/users', label: 'Pengguna', icon: 'pi pi-users' },
      { path: '/outlets', label: 'Outlet', icon: 'pi pi-building' },
    ],
  },
]

// Submenu expand/collapse state per group
const expandedGroups = reactive({})

// Initialize — semua group default expanded
function initExpandedGroups() {
  menuGroups.forEach(g => {
    expandedGroups[g.title] = true
  })
}

// Auto-expand group yang berisi route aktif
function expandActiveGroup() {
  for (const group of menuGroups) {
    if (group.items.some(item => item.path === route.path)) {
      expandedGroups[group.title] = true
      return
    }
  }
}

function toggleGroup(title) {
  expandedGroups[title] = !expandedGroups[title]
}

// Watch route changes — expand group yg sesuai
watch(() => route.path, () => {
  expandActiveGroup()
})

/**
 * Filter menu groups based on user role.
 */
const visibleGroups = computed(() => {
  if (auth.isSuper) return menuGroups
  if (auth.isManager) return menuGroups.filter(g => g.title !== 'Pengaturan') // hide Pengaturan
  return []
})

const topBarBadgeClass = computed(() => roleBadgeClass(auth.highestRole))

const pageTitles = {
  '/dashboard': 'Dashboard',
  '/orders': 'Pesanan',
  '/menu': 'Menu Management',
  '/discounts': 'Manajemen Diskon',
  '/taxes': 'Manajemen Pajak',
  '/tables': 'Manajemen Meja',
  '/users': 'Manajemen Pengguna',
  '/outlets': 'Manajemen Outlet',
  '/shifts': 'Shift & Jadwal',
  '/report/financial': 'Laporan Keuangan',
  '/report/shift': 'Laporan Shift',
  '/withdraw': 'Penarikan Saldo',
  '/profile': 'Pengaturan Profil',
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

async function logout() {
  await auth.logout()
  router.push('/login')
}


</script>

<style scoped>
/* Sidebar thin scrollbar */
.scrollbar-sidebar::-webkit-scrollbar {
  width: 3px;
}
.scrollbar-sidebar::-webkit-scrollbar-track {
  background: transparent;
}
.scrollbar-sidebar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.08);
  border-radius: 999px;
}
.scrollbar-sidebar::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.15);
}
.scrollbar-sidebar {
  scrollbar-width: thin;
  scrollbar-color: rgba(255, 255, 255, 0.08) transparent;
}

/* Slide transitions */
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

/* Submenu slide animation */
.submenu-slide-enter-active {
  transition: all 0.2s ease-out;
  max-height: 500px;
  opacity: 1;
}
.submenu-slide-leave-active {
  transition: all 0.15s ease-in;
  max-height: 500px;
  opacity: 1;
}
.submenu-slide-enter-from {
  max-height: 0;
  opacity: 0;
}
.submenu-slide-leave-to {
  max-height: 0;
  opacity: 0;
}
</style>
