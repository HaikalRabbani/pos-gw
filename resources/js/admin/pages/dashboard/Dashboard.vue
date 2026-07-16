<template>
  <div class="space-y-6">
    <!-- Outlet Selector -->
    <div class="flex flex-wrap items-center justify-between gap-3">
      <Select v-if="outlets.length > 1" v-model="selectedOutletId" :options="outlets" optionLabel="name" optionValue="id"
        class="w-56" @change="fetchData" :showClear="true" placeholder="Semua Outlet" />
      <div v-else-if="outlets.length === 1" class="text-sm font-medium text-slate-600">
        {{ outlets[0]?.name }}
      </div>
    </div>

    <!-- Welcome Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-teal-800 via-teal-900 to-teal-950 p-6">
      <div class="absolute top-0 right-0 w-64 h-64 bg-amber-400/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl"></div>
      <div class="absolute bottom-0 left-0 w-48 h-48 bg-teal-600/20 rounded-full translate-y-1/2 -translate-x-1/2 blur-3xl"></div>
      <div class="relative z-10">
        <h1 class="text-2xl font-bold text-white">Selamat datang kembali, {{ auth.user?.name?.split(' ')[0] || 'Admin' }}!</h1>
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

    <!-- Stats Grid: 4 Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
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

    <!-- Middle Row: Active Shifts + Pending Orders -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

      <!-- Active Shifts -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <h3 class="font-semibold text-slate-900 mb-3 flex items-center gap-2">
          <i class="pi pi-clock text-amber-500"></i>
          Shift Aktif
        </h3>
        <div v-if="data.active_shifts?.length === 0" class="text-center py-6">
          <i class="pi pi-hourglass text-2xl text-slate-200 mb-2"></i>
          <p class="text-sm text-slate-400">Belum ada shift aktif hari ini</p>
          <router-link to="/shifts" class="mt-2 inline-block text-xs text-teal-600 hover:text-teal-700 font-medium">
            Kelola Shift →
          </router-link>
        </div>
        <div v-else class="space-y-2">
          <div v-for="shift in data.active_shifts" :key="shift.user_name"
            class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 border border-slate-100">
            <div class="w-9 h-9 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
              <i class="pi pi-user text-amber-600 text-sm"></i>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-slate-900 truncate">{{ shift.user_name }}</p>
              <p class="text-xs text-slate-400">{{ shift.outlet_name }} • mulai {{ shift.start_at }}</p>
            </div>
            <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-full">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
              Aktif
            </span>
          </div>
        </div>
      </div>

      <!-- Pending Orders -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-semibold text-slate-900 flex items-center gap-2">
            <i class="pi pi-exclamation-triangle text-amber-500"></i>
            Pesanan Tertunda
            <span v-if="data.pending_orders?.length > 0"
              class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700">
              {{ data.pending_orders.length }}
            </span>
          </h3>
          <router-link to="/orders"
            class="text-xs font-medium text-teal-600 hover:text-teal-700 transition flex items-center gap-1">
            Lihat semua
            <i class="pi pi-arrow-right text-[10px]"></i>
          </router-link>
        </div>
        <div v-if="data.pending_orders?.length === 0" class="text-center py-6">
          <i class="pi pi-check-circle text-2xl text-emerald-200 mb-2"></i>
          <p class="text-sm text-slate-400">Semua pesanan sudah selesai</p>
        </div>
        <div v-else class="space-y-1">
          <div v-for="order in data.pending_orders" :key="order.id"
            class="flex items-center justify-between py-2.5 px-3 rounded-xl hover:bg-slate-50 transition cursor-pointer">
            <div class="flex items-center gap-3">
              <span class="text-sm font-semibold text-slate-900 w-14">#{{ order.id }}</span>
              <Tag :value="pendingStatusLabel(order.status)" :severity="pendingStatusSeverity(order.status)" rounded />
            </div>
            <div class="flex items-center gap-4">
              <span class="text-xs text-slate-500">{{ order.customer_name || '-' }}</span>
              <span class="text-xs font-medium text-slate-700">{{ formatRupiah(order.grand_total) }}</span>
              <span class="text-xs text-slate-400">{{ order.created_at }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Row: Top Menu Items + Recent Orders -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

      <!-- Top Menu Items -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <h3 class="font-semibold text-slate-900 mb-3 flex items-center gap-2">
          <i class="pi pi-star text-amber-400"></i>
          Menu Terlaris Hari Ini
        </h3>
        <div v-if="data.top_products?.length === 0" class="text-center py-6">
          <i class="pi pi-box text-2xl text-slate-200 mb-2"></i>
          <p class="text-sm text-slate-400">Belum ada data penjualan hari ini</p>
        </div>
        <div v-else class="space-y-1.5">
          <div v-for="(item, idx) in data.top_products" :key="item.product_name"
            class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-slate-50 transition">
            <!-- Ranking badge -->
            <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold shrink-0"
              :class="rankClass(idx)">
              {{ idx + 1 }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-slate-900 truncate">{{ item.product_name }}</p>
              <p class="text-xs text-slate-400">{{ formatRupiah(item.total_revenue) }}</p>
            </div>
            <div class="text-right shrink-0">
              <p class="text-sm font-bold text-slate-900">{{ item.total_qty }}</p>
              <p class="text-[10px] text-slate-400">terjual</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Orders -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-semibold text-slate-900 flex items-center gap-2">
            <i class="pi pi-receipt text-slate-400"></i>
            Pesanan Terbaru
          </h3>
          <router-link to="/orders"
            class="text-xs font-medium text-teal-600 hover:text-teal-700 transition flex items-center gap-1">
            Lihat semua
            <i class="pi pi-arrow-right text-[10px]"></i>
          </router-link>
        </div>
        <div v-if="data.recent_orders?.length === 0" class="text-center py-6">
          <i class="pi pi-inbox text-2xl text-slate-200 mb-2"></i>
          <p class="text-sm text-slate-400">Belum ada pesanan hari ini</p>
        </div>
        <div v-else class="space-y-1">
          <div v-for="order in data.recent_orders" :key="order.id"
            class="flex items-center justify-between py-2.5 px-3 rounded-xl hover:bg-slate-50 transition cursor-pointer">
            <div class="flex items-center gap-3">
              <span class="text-sm font-semibold text-slate-900 w-14">#{{ order.id }}</span>
              <Tag :value="statusLabel(order.status)" :severity="statusSeverity(order.status)" rounded />
            </div>
            <div class="flex items-center gap-4">
              <span class="text-xs text-slate-500">{{ order.customer_name || '-' }}</span>
              <span class="text-xs font-medium text-slate-700">{{ formatRupiah(order.grand_total) }}</span>
              <span class="text-xs text-slate-400">{{ formatTime(order.created_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { formatRupiah } from '../../utils/format'
import { useAuthStore } from '../../stores/auth'
import client from '../../api/client'
import Tag from 'primevue/tag'
import Select from 'primevue/select'

const auth = useAuthStore()
const initialLoading = ref(true)
const loading = ref(false)
const lastUpdate = ref('—')
const outlets = ref([])
const selectedOutletId = ref(null)

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
    avg_order_value: 0,
  },
  active_shifts: [],
  pending_orders: [],
  top_products: [],
  recent_orders: [],
})

// ── Stat Cards ──
const statCards = computed(() => {
  const s = data.value.summary
  return [
    {
      label: 'Penjualan Hari Ini',
      value: formatRupiah(s.gross_sales),
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
      label: 'Rata-rata Transaksi',
      value: formatRupiah(s.avg_order_value),
      icon: 'pi pi-chart-bar',
      iconClass: 'text-violet-600',
      bgClass: 'bg-violet-100',
      labelClass: 'text-violet-600',
      trend: 'Per pesanan',
      trendIcon: 'pi pi-calculator',
      trendClass: 'text-slate-500',
    },
    {
      label: 'Laba Kotor',
      value: formatRupiah(s.gross_profit),
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

// ── Helpers ──
function statusLabel(s) {
  const map = { draft: 'Draft', confirmed: 'Baru', preparing: 'Dimasak', done: 'Selesai', cancelled: 'Batal', voided: 'Void' }
  return map[s] || s
}

function statusSeverity(s) {
  const map = { draft: 'info', confirmed: 'warn', preparing: 'warn', done: 'success', cancelled: 'danger', voided: 'danger' }
  return map[s] || 'info'
}

function pendingStatusLabel(s) {
  const map = { confirmed: 'Baru', preparing: 'Dimasak' }
  return map[s] || s
}

function pendingStatusSeverity(s) {
  const map = { confirmed: 'warn', preparing: 'info' }
  return map[s] || 'info'
}

function rankClass(idx) {
  if (idx === 0) return 'bg-amber-100 text-amber-700'
  if (idx === 1) return 'bg-slate-100 text-slate-600'
  if (idx === 2) return 'bg-orange-100 text-orange-700'
  return 'bg-slate-50 text-slate-400'
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
    const params = {}
    if (selectedOutletId.value) {
      params.outlet_id = selectedOutletId.value
    }
    const { data: res } = await client.get('/dashboard', { params })
    if (res.success && res.data) {
      data.value = res.data
    }
  } catch (_) {
    // Tetap pakai data default klo error
  } finally {
    loading.value = false
    initialLoading.value = false
    updateTimestamp()
  }
}

async function fetchOutlets() {
  try {
    const { data } = await client.get('/outlets')
    outlets.value = data.data
  } catch (_) {}
}

onMounted(async () => {
  await fetchOutlets()
  fetchData()
})
</script>
