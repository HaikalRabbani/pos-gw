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
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Loading Skeleton -->
      <template v-if="loading">
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
      <div v-for="card in stats" :key="card.label"
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

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <!-- Quick Action Shortcuts -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <h3 class="font-semibold text-slate-900 mb-3 flex items-center gap-2">
          <i class="pi pi-bolt text-amber-500"></i>
          Aksi Cepat
        </h3>
        <div class="grid grid-cols-3 gap-3">
          <router-link to="/orders"
            class="flex flex-col items-center gap-2 p-4 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100/50 border border-blue-200/60 hover:from-blue-100 hover:to-blue-200/50 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5">
            <i class="pi pi-receipt text-2xl text-blue-600"></i>
            <span class="text-xs font-semibold text-blue-700">Pesanan</span>
          </router-link>
          <router-link to="/kitchen"
            class="flex flex-col items-center gap-2 p-4 rounded-xl bg-gradient-to-br from-amber-50 to-amber-100/50 border border-amber-200/60 hover:from-amber-100 hover:to-amber-200/50 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5">
            <i class="pi pi-box text-2xl text-amber-600"></i>
            <span class="text-xs font-semibold text-amber-700">Dapur</span>
          </router-link>
          <router-link to="/menu"
            class="flex flex-col items-center gap-2 p-4 rounded-xl bg-gradient-to-br from-emerald-50 to-emerald-100/50 border border-emerald-200/60 hover:from-emerald-100 hover:to-emerald-200/50 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5">
            <i class="pi pi-list text-2xl text-emerald-600"></i>
            <span class="text-xs font-semibold text-emerald-700">Menu</span>
          </router-link>
          <router-link to="/users"
            class="flex flex-col items-center gap-2 p-4 rounded-xl bg-gradient-to-br from-violet-50 to-violet-100/50 border border-violet-200/60 hover:from-violet-100 hover:to-violet-200/50 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5">
            <i class="pi pi-users text-2xl text-violet-600"></i>
            <span class="text-xs font-semibold text-violet-700">Pengguna</span>
          </router-link>
          <router-link to="/report"
            class="flex flex-col items-center gap-2 p-4 rounded-xl bg-gradient-to-br from-purple-50 to-purple-100/50 border border-purple-200/60 hover:from-purple-100 hover:to-purple-200/50 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5">
            <i class="pi pi-chart-bar text-2xl text-purple-600"></i>
            <span class="text-xs font-semibold text-purple-700">Laporan</span>
          </router-link>
          <!-- Placeholder for future: Diskon/Pajak -->
          <div
            class="flex flex-col items-center gap-2 p-4 rounded-xl border border-dashed border-slate-200 bg-slate-50/50 opacity-60 cursor-not-allowed">
            <i class="pi pi-plus text-2xl text-slate-300"></i>
            <span class="text-xs font-semibold text-slate-400">Lainnya</span>
          </div>
        </div>
      </div>

      <!-- Info Card -->
      <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <h3 class="font-semibold text-slate-900 mb-3 flex items-center gap-2">
          <i class="pi pi-info-circle text-blue-500"></i>
          Informasi Sistem
        </h3>
        <div class="space-y-3">
          <div class="flex items-center justify-between py-2 border-b border-slate-100">
            <span class="text-sm text-slate-500">Outlet Aktif</span>
            <span class="text-sm font-semibold text-slate-900">{{ stats[2]?.value || '-' }}</span>
          </div>
          <div class="flex items-center justify-between py-2 border-b border-slate-100">
            <span class="text-sm text-slate-500">Total Produk</span>
            <span class="text-sm font-semibold text-slate-900">{{ stats[3]?.value || '-' }}</span>
          </div>
          <div class="flex items-center justify-between py-2 border-b border-slate-100">
            <span class="text-sm text-slate-500">Status</span>
            <span class="inline-flex items-center gap-1 text-sm font-semibold text-emerald-600">
              <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
              Online
            </span>
          </div>
          <div class="flex items-center justify-between py-2">
            <span class="text-sm text-slate-500">Versi Aplikasi</span>
            <span class="text-sm font-semibold text-slate-900">1.0.0</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Orders Preview -->
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
      <div v-else class="space-y-2">
        <div v-for="order in recentOrders" :key="order.id"
          class="flex items-center justify-between py-2 px-3 rounded-xl hover:bg-slate-50 transition cursor-pointer">
          <div class="flex items-center gap-3">
            <span class="text-sm font-semibold text-slate-900">#{{ order.id }}</span>
            <Tag :value="statusLabel(order.status)" :severity="statusSeverity(order.status)" rounded />
          </div>
          <span class="text-xs text-slate-400">{{ formatTime(order.created_at) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import client from '../../api/client'
import Tag from 'primevue/tag'

const auth = useAuthStore()
const loading = ref(true)

const stats = ref([
  { label: 'Penjualan Hari Ini', value: '-', icon: 'pi pi-dollar', iconClass: 'text-emerald-600', bgClass: 'bg-emerald-100', labelClass: 'text-emerald-600', trend: '+0% dari kemarin', trendIcon: 'pi pi-arrow-up', trendClass: 'text-emerald-500' },
  { label: 'Total Pesanan', value: '-', icon: 'pi pi-receipt', iconClass: 'text-blue-600', bgClass: 'bg-blue-100', labelClass: 'text-blue-600', trend: 'Hari ini', trendIcon: 'pi pi-calendar', trendClass: 'text-slate-400' },
  { label: 'Outlet Aktif', value: '-', icon: 'pi pi-building', iconClass: 'text-violet-600', bgClass: 'bg-violet-100', labelClass: 'text-violet-600', trend: 'Semua outlet', trendIcon: 'pi pi-check-circle', trendClass: 'text-slate-400' },
  { label: 'Total Produk', value: '-', icon: 'pi pi-box', iconClass: 'text-amber-600', bgClass: 'bg-amber-100', labelClass: 'text-amber-600', trend: 'Tersedia', trendIcon: 'pi pi-database', trendClass: 'text-slate-400' },
])

const recentOrders = ref([])

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

onMounted(async () => {
  try {
    const { data: outletRes } = await client.get('/outlets')
    const outlets = outletRes.data
    stats.value[2].value = outlets.length

    if (outlets.length > 0) {
      const outletId = outlets[0]?.id
      const [prodRes, orderRes] = await Promise.all([
        client.get('/products', { params: { outlet_id: outletId } }),
        client.get('/orders', { params: { outlet_id: outletId, per_page: 5 } }).catch(() => null),
      ])
      stats.value[3].value = prodRes.data.length
      if (orderRes?.data?.data) {
        recentOrders.value = orderRes.data.data
        stats.value[1].value = orderRes.data.total || orderRes.data.data.length
      }
    }
  } catch (_) {}
  finally {
    loading.value = false
  }
})
</script>
