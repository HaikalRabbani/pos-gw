<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-slate-900">Sales Report</h1>
      <p class="text-sm text-slate-500 mt-1">Ringkasan penjualan dan produk terlaris</p>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
            <i class="pi pi-dollar text-emerald-600 text-lg"></i>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600">Total Penjualan</p>
            <p class="text-2xl font-bold text-slate-900 mt-0.5">Rp 0</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
            <i class="pi pi-receipt text-blue-600 text-lg"></i>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-blue-600">Total Transaksi</p>
            <p class="text-2xl font-bold text-slate-900 mt-0.5">0</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center">
            <i class="pi pi-star text-amber-600 text-lg"></i>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-amber-600">Produk Terlaris</p>
            <p class="text-2xl font-bold text-slate-900 mt-0.5">—</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Chart Placeholder -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6">
      <h3 class="font-semibold text-slate-900 mb-4">Grafik Penjualan</h3>
      <div class="relative h-64 rounded-xl bg-gradient-to-b from-slate-50 to-white border border-slate-100 overflow-hidden">
        <!-- Placeholder Chart Bars -->
        <div class="absolute inset-0 flex items-end justify-around px-8 pb-8">
          <div v-for="(bar, i) in chartBars" :key="i"
            class="w-8 rounded-t-lg transition-all duration-500"
            :class="bar.active ? 'bg-gradient-to-t from-teal-500 to-teal-400 opacity-40' : 'bg-slate-200'"
            :style="{ height: bar.height + '%' }">
          </div>
        </div>
        <!-- Overlay Message -->
        <div class="absolute inset-0 flex flex-col items-center justify-center bg-white/60 backdrop-blur-sm">
          <i class="pi pi-chart-line text-4xl text-slate-300 mb-3"></i>
          <p class="text-slate-500 text-sm font-medium">Coming Soon</p>
          <p class="text-slate-400 text-xs mt-1">Grafik penjualan harian akan tersedia segera.</p>
        </div>
      </div>
    </div>

    <!-- Coming Soon Features -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center gap-4 hover:shadow-md transition-all duration-200 hover:-translate-y-0.5">
        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-50 to-purple-100 flex items-center justify-center shrink-0">
          <i class="pi pi-download text-purple-600 text-xl"></i>
        </div>
        <div>
          <p class="font-semibold text-slate-900">Export Data</p>
          <p class="text-xs text-slate-400 mt-0.5">Export laporan ke Excel, PDF, atau CSV</p>
        </div>
      </div>
      <div class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center gap-4 hover:shadow-md transition-all duration-200 hover:-translate-y-0.5">
        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-50 to-amber-100 flex items-center justify-center shrink-0">
          <i class="pi pi-filter text-amber-600 text-xl"></i>
        </div>
        <div>
          <p class="font-semibold text-slate-900">Filter Periode</p>
          <p class="text-xs text-slate-400 mt-0.5">Filter laporan berdasarkan rentang tanggal</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const chartBars = [
  { height: 60, active: true },
  { height: 40, active: true },
  { height: 80, active: true },
  { height: 55, active: true },
  { height: 90, active: true },
  { height: 45, active: true },
  { height: 70, active: true },
  { height: 85, active: true },
  { height: 50, active: true },
  { height: 75, active: true },
  { height: 65, active: true },
  { height: 95, active: true },
]
</script>
