<template>
  <div class="space-y-6">
    <!-- Welcome Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-teal-800 via-teal-900 to-teal-950 p-6">
      <div class="absolute top-0 right-0 w-64 h-64 bg-amber-400/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl"></div>
      <div class="absolute bottom-0 left-0 w-48 h-48 bg-teal-600/20 rounded-full translate-y-1/2 -translate-x-1/2 blur-3xl"></div>
      <div class="relative z-10">
        <h1 class="text-2xl font-bold text-white">Selamat datang kembali, {{ auth.user?.name?.split(' ')[0] || 'Admin' }}! 👋</h1>
        <p class="text-teal-200/80 text-sm mt-1">Berikut ringkasan performa bisnis Anda hari ini.</p>
      </div>
      <div class="absolute bottom-3 right-4 z-10 flex items-center gap-2 text-xs text-teal-300/60">
        <i class="pi pi-sync text-[10px]" :class="{ 'animate-spin': loading }"></i>
        <span>{{ lastUpdate }}</span>
        <button @click="fetchData" class="hover:text-teal-200 transition ml-1" :disabled="loading">
          <i class="pi pi-refresh text-sm"></i>
        </button>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Loading Skeleton (first load only) -->
      <template v-if="initialLoading">
        <div v-for="i in 4" :key="i"
          class="bg-white rounded-2xl border border-slate-200 p-5 animate-pulse">
          <div class="flex items-center justify-between mb-3">
            <div class="h-4 w-20 bg-slate-200 rounded"></div>
            <div class="h-10 w-10 bg-slate-200 rounded-xl"></div>
          </div>
          <div class="h-8 w-24 bg-slate-200 rounded mb-1"></div>
          <div class="h-3 w-16 bg-slate-100 rounded"></div>
        </div>
      </template>

      <!-- Stat Cards -->
      <div v-for="card in statCards" :key="card.label"
        class="group relative bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-lg hover:border-slate-300 transition-all duration-200 hover:-translate-y-0.5">
        <div class="flex items-center justify-between mb-3">
          <span class="text-xs font-semibold uppercase tracking-wider" :class="card.labelClass">{{ card.label }}</span>
          <div class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors duration-200"
            :class="[card.bgClass, { 'group-hover:scale-110': true }]">
            <i :class="[card.icon, card.iconClass]"></i>
          </div>
        </div>
        <p class="text-3xl font-bold text-slate-900 tracking-tight">{{ card.value }}</p>
        <div class="flex items-center gap-1 mt-1">
          <i :class="[card.trendIcon, card.trendClass, 'text-xs']"></i>
          <span class="text-xs" :class="card.trendClass">{{ card.trend }}</span>
        </div>
      </div>
    </div>

    <!-- Quick Actions + Info -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <!-- Quick Action Shortcuts -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <h3 class="font-semibold text-slate-900 mb-3 flex items-center gap-2">
          <i class="pi pi-bolt text-amber-500"></i>
          Aksi Cepat
        </h3>
        <div class="grid grid-cols-3 gap-3">
          <router-link v-for="action in quickActions" :key="action.to" :to="action.to"
            class="flex flex-col items-center gap-2 p-4 rounded-xl transition-all duration-200 hover:shadow-md hover:-translate-y-0.5"
            :class="action.bgClass">
            <i :class="[action.icon, 'text-2xl', action.iconClass]"></i>
            <span class="text-xs font-semibold" :class="action.textClass">{{ action.label }}</span>
          </router-link>
        </div>
      </div>

      <!-- Info Card -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <h3 class="font-semibold text-slate-900 mb-3 flex items-center gap-2">
          <i class="pi pi-info-circle text-blue-500"></i>
          Informasi Sistem
        </h3>
        <div class="space-y-3">
          <div v-for="info in systemInfo" :key="info.label"
            class="flex items-center justify-between py-2" :class="info.border ? 'border-b border-slate-100' : ''">
            <span class="text-sm text-slate-500">{{ info.label }}</span>
            <span v-if="info.type === 'status'" class="inline-flex items-center gap-1.5 text-sm font-semibold" :class="info.class">
              <span class="w-2 h-2 rounded-full" :class="info.dotClass"></span>
              {{ info.value }}
            </span>
            <span v-else class="text-sm font-semibold" :class="info.class">{{ info.value }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Orders -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-slate-900 flex items-center gap-2">
          <i class="pi pi-clock text-slate-400"></i>
          Pesanan Terbaru
        </h3>
        <router-link to="/orders"
          class="text-xs font-medium text-teal-600 hover:text-teal-700 transition flex items-center gap-1">
          Lihat semua
          <i class="pi pi-arrow-right text-[10px]"></i>
        </router-link>
      </div>
      <div v-if="recentOrders.length === 0" class="text-center py-6">
        <i class="pi pi-inbox text-2xl text-slate-200 mb-2"></i>
        <p class="text-sm text-slate-400">Belum ada pesanan hari ini</p>
      </div>
      <div v-else class="space-y-1">
        <div v-for="order in recentOrders" :key="order.id"
          class="flex items-center justify-between py-2.5 px-3 rounded-xl hover:bg-slate-50 transition cursor-pointer">
          <div class="flex items-center gap-3">
            <span class="text-sm font-semibold text-slate-900 w-14">#{{ order.id }}</span>
            <Tag :value="statusLabel(order.status)" :severity="statusSeverity(order.status)" rounded />
          </div>
          <div class="flex items-center gap-4">
            <span class="text-xs text-slate-500">{{ order.customer_name || '-' }}</span>
            <span class="text-xs font-medium text-slate-700">{{ formatPrice(order.grand_total) }}</span>
            <span class="text-xs text-slate-400">{{ formatTime(order.created_at) }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import client from '../../api/client'
import Tag from 'primevue/tag'

const auth = useAuthStore()
const initialLoading = ref(true)
const loading = ref(false)
const lastUpdate = ref('—')

// ── Data ──
const data = ref({
  summary: {
    outlets: 0,
    products: 0,
    total_transactions: 0,
    gross_sales: 0,
    net_sales: 0,
    hpp: 0,
    gross_profit: 0,
    gross_margin: 0,
  },
  recent_orders: [],
})

const recentOrders = computed(() => data.value.recent_orders)

// ── Stat Cards ──
const statCards = computed(() => {
  const s = data.value.summary
  return [
    {
      label: 'Penjualan Hari Ini',
      value: formatPrice(s.gross_sales),
      icon: 'pi pi-dollar',
      iconClass: 'text-emerald-600',
      bgClass: 'bg-emerald-100',
      labelClass: 'text-emerald-600',
      trend: s.total_transactions > 0 ? s.total_transactions + ' transaksi' : 'Belum ada transaksi',
      trendIcon: s.total_transactions > 0 ? 'pi pi-check-circle' : 'pi pi-clock',
      trendClass: s.total_transactions > 0 ? 'text-slate-500' : 'text-slate-400',
    },
    {
      label: 'Pesanan Hari Ini',
      value: String(s.total_transactions || 0),
      icon: 'pi pi-receipt',
      iconClass: 'text-blue-600',
      bgClass: 'bg-blue-100',
      labelClass: 'text-blue-600',
      trend: 'Hari ini',
      trendIcon: 'pi pi-calendar',
      trendClass: 'text-slate-500',
    },
    {
      label: 'Outlet Aktif',
      value: String(s.outlets || 0),
      icon: 'pi pi-building',
      iconClass: 'text-violet-600',
      bgClass: 'bg-violet-100',
      labelClass: 'text-violet-600',
      trend: s.products > 0 ? s.products + ' produk' : 'Semua outlet',
      trendIcon: 'pi pi-database',
      trendClass: 'text-slate-500',
    },
    {
      label: 'Laba Kotor',
      value: formatPrice(s.gross_profit),
      icon: 'pi pi-chart-line',
      iconClass: 'text-amber-600',
      bgClass: 'bg-amber-100',
      labelClass: 'text-amber-600',
      trend: 'Margin ' + s.gross_margin + '%',
      trendIcon: s.gross_margin >= 0 ? 'pi pi-arrow-up' : 'pi pi-arrow-down',
      trendClass: s.gross_margin >= 0 ? 'text-emerald-500' : 'text-red-500',
    },
  ]
})

// ── Quick Actions ──
const quickActions = [
  { to: '/orders', label: 'Pesanan', icon: 'pi pi-receipt', iconClass: 'text-blue-600', textClass: 'text-blue-700', bgClass: 'bg-gradient-to-br from-blue-50 to-blue-100/50 border border-blue-200/60 hover:from-blue-100 hover:to-blue-200/50' },
  { to: '/shifts', label: 'Shift', icon: 'pi pi-clock', iconClass: 'text-amber-600', textClass: 'text-amber-700', bgClass: 'bg-gradient-to-br from-amber-50 to-amber-100/50 border border-amber-200/60 hover:from-amber-100 hover:to-amber-200/50' },
  { to: '/menu', label: 'Menu', icon: 'pi pi-list', iconClass: 'text-emerald-600', textClass: 'text-emerald-700', bgClass: 'bg-gradient-to-br from-emerald-50 to-emerald-100/50 border border-emerald-200/60 hover:from-emerald-100 hover:to-emerald-200/50' },
  { to: '/users', label: 'Pengguna', icon: 'pi pi-users', iconClass: 'text-violet-600', textClass: 'text-violet-700', bgClass: 'bg-gradient-to-br from-violet-50 to-violet-100/50 border border-violet-200/60 hover:from-violet-100 hover:to-violet-200/50' },
  { to: '/report', label: 'Laporan', icon: 'pi pi-chart-bar', iconClass: 'text-purple-600', textClass: 'text-purple-700', bgClass: 'bg-gradient-to-br from-purple-50 to-purple-100/50 border border-purple-200/60 hover:from-purple-100 hover:to-purple-200/50' },
  { to: '/withdraw', label: 'Penarikan', icon: 'pi pi-credit-card', iconClass: 'text-teal-600', textClass: 'text-teal-700', bgClass: 'bg-gradient-to-br from-teal-50 to-teal-100/50 border border-teal-200/60 hover:from-teal-100 hover:to-teal-200/50' },
]

// ── System Info ──
const systemInfo = computed(() => {
  const s = data.value.summary
  return [
    { label: 'Outlet', value: String(s.outlets), class: 'text-slate-900', border: true },
    { label: 'Total Produk', value: String(s.products), class: 'text-slate-900', border: true },
    { label: 'Pesanan Hari Ini', value: String(s.total_transactions), class: 'text-slate-900', border: true },
    { label: 'Penjualan', value: formatPrice(s.gross_sales), class: 'text-teal-600 font-bold', border: true },
    { label: 'Laba', value: formatPrice(s.gross_profit), class: s.gross_profit >= 0 ? 'text-emerald-600 font-bold' : 'text-red-600 font-bold', border: true },
    { label: 'Versi Aplikasi', value: '1.0.0', class: 'text-slate-900', border: true },
    { label: 'Status', value: 'Online', type: 'status', dotClass: 'bg-emerald-500 animate-pulse', class: 'text-emerald-600', border: false },
  ]
})

// ── Helpers ──
function formatPrice(cents) {
  if (!cents && cents !== 0) return 'Rp 0'
  return 'Rp ' + Math.round(cents).toLocaleString('id-ID')
}

function statusLabel(s) {
  const map = { draft: 'Draft', confirmed: 'Baru', preparing: 'Dimasak', done: 'Selesai', cancelled: 'Batal', voided: 'Void' }
  return map[s] || s
}

function statusSeverity(s) {
  const map = { draft: 'info', confirmed: 'warn', preparing: 'warn', done: 'success', cancelled: 'danger', voided: 'danger' }
  return map[s] || 'info'
}

function formatTime(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
}

function updateTimestamp() {
  const d = new Date()
  lastUpdate.value = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
}

// ── Single optimized fetch ──
async function fetchData() {
  loading.value = true
  try {
    const { data: res } = await client.get('/dashboard')
    if (res.success && res.data) {
      data.value = res.data
    }
  } catch (_) {}
  finally {
    loading.value = false
    initialLoading.value = false
    updateTimestamp()
  }
}

onMounted(() => {
  fetchData()
})
</script>
