<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Laporan Keuangan</h1>
        <p class="text-sm text-slate-500 mt-1">Ringkasan penjualan, HPP, dan laba</p>
      </div>
      <div class="flex items-center gap-2">
        <Button label="Export Excel" icon="pi pi-file-excel" severity="success" outlined @click="exportExcel" />
        <Button label="Export PDF" icon="pi pi-file-pdf" severity="danger" outlined @click="exportPdf" />
      </div>
    </div>

    <!-- Loading State -->
    <template v-if="loading">
      <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <div v-for="i in 6" :key="i" class="bg-white rounded-2xl border border-slate-200 p-4 animate-pulse">
          <div class="h-3 w-16 bg-slate-200 rounded mb-3"></div>
          <div class="h-7 w-24 bg-slate-200 rounded mb-1"></div>
          <div class="h-3 w-12 bg-slate-100 rounded"></div>
        </div>
      </div>
      <div class="bg-white rounded-2xl border border-slate-200 p-6 animate-pulse">
        <div class="h-5 w-32 bg-slate-200 rounded mb-4"></div>
        <div class="h-64 bg-slate-100 rounded-xl"></div>
      </div>
    </template>

    <!-- Content -->
    <template v-else>
      <!-- Period Filter -->
      <div class="bg-white rounded-2xl border border-slate-200 p-4 flex flex-col sm:flex-row items-start sm:items-center gap-3">
        <div class="flex items-center gap-2">
          <i class="pi pi-calendar text-slate-400"></i>
          <span class="text-sm font-medium text-slate-700">Periode:</span>
        </div>
        <DatePicker v-model="dateRange" selectionMode="range" :manualInput="false" dateFormat="dd/mm/yy"
          class="w-full sm:w-auto" @update:modelValue="onDateChange" />
        <div class="flex gap-1.5 flex-wrap">
          <Button v-for="preset in presets" :key="preset.label" :label="preset.label"
            :severity="activePreset === preset.value ? 'primary' : 'secondary'" size="small"
            @click="applyPreset(preset.value)" />
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <div v-for="card in summaryCards" :key="card.label"
          class="group relative bg-white rounded-2xl border border-slate-200 p-4 hover:shadow-lg hover:border-slate-300 transition-all duration-200 hover:-translate-y-0.5 cursor-default">
          <div class="flex items-center gap-2 mb-2">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center transition-colors duration-200"
              :class="[card.bgClass, { 'group-hover:scale-110': true }]">
              <i :class="[card.icon, card.iconClass]"></i>
            </div>
            <span class="text-[10px] font-semibold uppercase tracking-wider" :class="card.labelClass">{{ card.label }}</span>
          </div>
          <p class="text-lg font-bold text-slate-900 tracking-tight">{{ formatRupiah(card.value) }}</p>
          <p class="text-[10px] text-slate-400 mt-0.5">{{ card.subtext }}</p>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Sales Chart -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-slate-900 flex items-center gap-2">
              <i class="pi pi-chart-line text-teal-500"></i>
              Penjualan Harian
            </h3>
            <div class="flex gap-1 bg-slate-100 rounded-lg p-0.5">
              <Button v-for="t in chartTypes" :key="t.value" :label="t.label"
                :severity="chartType === t.value ? 'primary' : 'secondary'" size="small"
                @click="chartType = t.value" />
            </div>
          </div>
          <div class="relative">
            <canvas ref="salesChartRef" class="w-full" style="height: 280px;"></canvas>
          </div>
          <div v-if="dailySales.length === 0" class="text-center py-6 -mt-40 relative z-10">
            <i class="pi pi-chart-bar text-3xl text-slate-200 mb-2"></i>
            <p class="text-sm text-slate-400">Belum ada data penjualan</p>
          </div>
        </div>

        <!-- Profit Distribution -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-slate-900 flex items-center gap-2">
              <i class="pi pi-pie-chart text-violet-500"></i>
              Komposisi Penjualan
            </h3>
          </div>
          <div class="relative">
            <canvas ref="compositionChartRef" class="w-full" style="height: 280px;"></canvas>
          </div>
          <div v-if="dailySales.length === 0" class="text-center py-6 -mt-40 relative z-10">
            <i class="pi pi-chart-pie text-3xl text-slate-200 mb-2"></i>
            <p class="text-sm text-slate-400">Belum ada data keuangan</p>
          </div>
        </div>
      </div>

      <!-- Top Products -->
      <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
          <h3 class="font-semibold text-slate-900 flex items-center gap-2">
            <i class="pi pi-star text-amber-500"></i>
            Produk Terlaris
          </h3>
        </div>
        <DataTable :value="topProducts" stripedRows size="small" class="text-sm">
          <Column header="#" style="width: 50px">
            <template #body="{ index }">
              <span class="text-slate-400 text-xs font-mono">{{ index + 1 }}</span>
            </template>
          </Column>
          <template #empty>
            <div class="flex flex-col items-center justify-center py-16 text-center">
              <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
                <i class="pi pi-box text-2xl text-slate-300"></i>
              </div>
              <p class="text-slate-500 font-medium">Belum ada data penjualan</p>
              <p class="text-slate-400 text-xs mt-1">Data akan muncul setelah ada transaksi</p>
            </div>
          </template>
          <Column field="product_name" header="Produk" sortable>
            <template #body="{ data }">
              <span class="font-medium text-slate-900">{{ data.product_name }}</span>
            </template>
          </Column>
          <Column field="total_qty" header="Terjual" sortable class="text-center" />
          <Column field="total_revenue" header="Pendapatan" sortable>
            <template #body="{ data }">
              <span class="font-semibold text-slate-900">{{ formatRupiah(data.total_revenue) }}</span>
            </template>
          </Column>
          <Column field="total_hpp" header="Modal" sortable>
            <template #body="{ data }">
              <span class="text-slate-600">{{ formatRupiah(data.total_hpp) }}</span>
            </template>
          </Column>
          <Column field="gross_profit" header="Laba Kotor" sortable>
            <template #body="{ data }">
              <span class="font-semibold" :class="data.gross_profit >= 0 ? 'text-emerald-600' : 'text-red-600'">
                {{ formatRupiah(data.gross_profit) }}
              </span>
            </template>
          </Column>
          <Column field="gross_margin" header="Margin" sortable>
            <template #body="{ data }">
              <Tag :value="data.gross_margin + '%'" :severity="data.gross_margin >= 30 ? 'success' : data.gross_margin >= 10 ? 'warn' : 'danger'" rounded />
            </template>
          </Column>
        </DataTable>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import client from '../../api/client'
import { useAuthStore } from '../../stores/auth'
import Chart from 'chart.js/auto'
import Button from 'primevue/button'
import DatePicker from 'primevue/datepicker'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'

const authStore = useAuthStore()
const loading = ref(true)

const dateRange = ref(null)
const activePreset = ref('today')
const chartType = ref('bar')
const chartTypes = [
  { label: 'Batang', value: 'bar' },
  { label: 'Garis', value: 'line' },
]

const presets = [
  { label: 'Hari Ini', value: 'today' },
  { label: '7 Hari', value: '7d' },
  { label: '30 Hari', value: '30d' },
  { label: 'Bulan Ini', value: 'thisMonth' },
  { label: 'Bulan Lalu', value: 'lastMonth' },
  { label: 'Tahun Ini', value: 'thisYear' },
]

function getPresetDates(preset) {
  const now = new Date()
  const y = now.getFullYear()
  const m = now.getMonth()
  const fmt = (d) => d.toISOString().split('T')[0]

  switch (preset) {
    case 'today':
      return { start: fmt(now), end: fmt(now) }
    case '7d': {
      const d = new Date(now)
      d.setDate(d.getDate() - 6)
      return { start: fmt(d), end: fmt(now) }
    }
    case '30d': {
      const d = new Date(now)
      d.setDate(d.getDate() - 29)
      return { start: fmt(d), end: fmt(now) }
    }
    case 'thisMonth':
      return { start: fmt(new Date(y, m, 1)), end: fmt(now) }
    case 'lastMonth':
      return { start: fmt(new Date(y, m - 1, 1)), end: fmt(new Date(y, m, 0)) }
    case 'thisYear':
      return { start: fmt(new Date(y, 0, 1)), end: fmt(now) }
    default:
      return { start: fmt(now), end: fmt(now) }
  }
}

function applyPreset(value) {
  activePreset.value = value
  const { start, end } = getPresetDates(value)
  dateRange.value = [new Date(start), new Date(end)]
  fetchData()
}

function onDateChange() {
  if (dateRange.value?.length === 2) {
    activePreset.value = null
    fetchData()
  }
}

// Summary cards
const summaryData = ref({
  summary: {
    total_transactions: 0,
    gross_sales: 0,
    total_discount: 0,
    total_tax: 0,
    subtotal: 0,
    net_sales: 0,
    hpp: 0,
    gross_profit: 0,
    gross_margin: 0,
    avg_order_value: 0,
  }
})

const summaryCards = computed(() => {
  const s = summaryData.value.summary
  return [
    { label: 'Penjualan Kotor', value: s.gross_sales, icon: 'pi pi-dollar', iconClass: 'text-emerald-600', bgClass: 'bg-emerald-100', labelClass: 'text-emerald-600', subtext: 'Total ' + s.total_transactions + ' transaksi' },
    { label: 'Diskon', value: s.total_discount, icon: 'pi pi-percentage', iconClass: 'text-red-600', bgClass: 'bg-red-100', labelClass: 'text-red-600', subtext: 'Total potongan' },
    { label: 'Pajak', value: s.total_tax, icon: 'pi pi-receipt', iconClass: 'text-blue-600', bgClass: 'bg-blue-100', labelClass: 'text-blue-600', subtext: 'Total pajak' },
    { label: 'Penjualan Bersih', value: s.net_sales, icon: 'pi pi-shopping-cart', iconClass: 'text-teal-600', bgClass: 'bg-teal-100', labelClass: 'text-teal-600', subtext: 'Setelah diskon' },
    { label: 'HPP (Modal)', value: s.hpp, icon: 'pi pi-truck', iconClass: 'text-amber-600', bgClass: 'bg-amber-100', labelClass: 'text-amber-600', subtext: 'Modal barang terjual' },
    { label: 'Laba Kotor', value: s.gross_profit, icon: 'pi pi-chart-line', iconClass: 'text-violet-600', bgClass: 'bg-violet-100', labelClass: 'text-violet-600', subtext: 'Margin ' + s.gross_margin + '%' },
  ]
})

function formatRupiah(cents) {
  if (cents === null || cents === undefined) return 'Rp 0'
  return 'Rp ' + (Math.round(cents)).toLocaleString('id-ID')
}

// Chart data
const dailySales = ref([])
const topProducts = ref([])

const salesChartRef = ref(null)
const compositionChartRef = ref(null)
let salesChartInstance = null
let compositionChartInstance = null

function buildSalesChart(data) {
  if (!salesChartRef.value) return
  if (salesChartInstance) salesChartInstance.destroy()

  const labels = data.map(d => {
    const parts = d.date.split('-')
    return parts[2] + '/' + parts[1]
  })
  const grossSales = data.map(d => d.gross_sales / 1000)
  const netSales = data.map(d => d.net_sales / 1000)
  const hpp = data.map(d => d.hpp / 1000)

  const isBar = chartType.value === 'bar'

  salesChartInstance = new Chart(salesChartRef.value, {
    type: isBar ? 'bar' : 'line',
    data: {
      labels,
      datasets: [
        {
          label: 'Penjualan Kotor',
          data: grossSales,
          backgroundColor: 'rgba(20, 184, 166, 0.7)',
          borderColor: 'rgb(20, 184, 166)',
          borderWidth: 2,
          borderRadius: 4,
        },
        {
          label: 'Penjualan Bersih',
          data: netSales,
          backgroundColor: 'rgba(139, 92, 246, 0.7)',
          borderColor: 'rgb(139, 92, 246)',
          borderWidth: 2,
          borderRadius: 4,
        },
        {
          label: 'HPP',
          data: hpp,
          backgroundColor: 'rgba(245, 158, 11, 0.7)',
          borderColor: 'rgb(245, 158, 11)',
          borderWidth: 2,
          borderRadius: 4,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: { usePointStyle: true, padding: 16, font: { size: 11 } },
        },
        tooltip: {
          callbacks: {
            label: (ctx) => ctx.dataset.label + ': Rp ' + (ctx.raw * 1000).toLocaleString('id-ID'),
          },
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: (v) => 'Rp' + v + 'K',
            font: { size: 10 },
          },
          grid: { color: 'rgba(0,0,0,0.04)' },
        },
        x: {
          grid: { display: false },
          ticks: { font: { size: 10 } },
        },
      },
    },
  })
}

function buildCompositionChart(data) {
  if (!compositionChartRef.value) return
  if (compositionChartInstance) compositionChartInstance.destroy()

  const summary = summaryData.value.summary

  compositionChartInstance = new Chart(compositionChartRef.value, {
    type: 'doughnut',
    data: {
      labels: ['Laba Kotor', 'HPP (Modal)', 'Diskon', 'Pajak'],
      datasets: [{
        data: [
          Math.max(summary.gross_profit, 0),
          summary.hpp,
          summary.total_discount,
          summary.total_tax,
        ],
        backgroundColor: [
          'rgba(139, 92, 246, 0.8)',
          'rgba(245, 158, 11, 0.8)',
          'rgba(239, 68, 68, 0.8)',
          'rgba(59, 130, 246, 0.8)',
        ],
        borderWidth: 2,
        borderColor: 'white',
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '65%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: { usePointStyle: true, padding: 16, font: { size: 11 } },
        },
        tooltip: {
          callbacks: {
            label: (ctx) => {
              const total = ctx.dataset.data.reduce((a, b) => a + b, 0)
              const pct = total > 0 ? ((ctx.raw / total) * 100).toFixed(1) : 0
              return ctx.label + ': Rp ' + ctx.raw.toLocaleString('id-ID') + ' (' + pct + '%)'
            },
          },
        },
      },
    },
  })
}

async function fetchData() {
  if (!dateRange.value || dateRange.value.length !== 2) return

  loading.value = true
  try {
    const { data: outletRes } = await client.get('/outlets')
    const outlet = outletRes.data[0]
    if (!outlet) return

    const params = {
      outlet_id: outlet.id,
      start_date: dateRange.value[0].toISOString().split('T')[0],
      end_date: dateRange.value[1].toISOString().split('T')[0],
    }

    const [summaryRes, dailyRes, topRes] = await Promise.all([
      client.get('/reports/summary', { params }),
      client.get('/reports/daily-sales', { params }),
      client.get('/reports/top-products', { params }),
    ])

    summaryData.value = summaryRes.data.data
    dailySales.value = dailyRes.data.data || []
    topProducts.value = topRes.data.data || []

  } catch (err) {
    console.error('Failed to fetch report data:', err)
  } finally {
    loading.value = false
    await nextTick()
    buildSalesChart(dailySales.value)
    buildCompositionChart(dailySales.value)
  }
}

async function exportExcel() {
  if (!dateRange.value || dateRange.value.length !== 2) return
  try {
    const { data: outletRes } = await client.get('/outlets')
    const outlet = outletRes.data[0]
    if (!outlet) return

    const params = {
      outlet_id: outlet.id,
      start_date: dateRange.value[0].toISOString().split('T')[0],
      end_date: dateRange.value[1].toISOString().split('T')[0],
    }

    const res = await client.get('/reports/export-excel', {
      params,
      responseType: 'blob',
    })

    const url = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `laporan-keuangan-${params.start_date}_${params.end_date}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (err) {
    alert('Gagal export Excel')
  }
}

async function exportPdf() {
  if (!dateRange.value || dateRange.value.length !== 2) return
  try {
    const { data: outletRes } = await client.get('/outlets')
    const outlet = outletRes.data[0]
    if (!outlet) return

    const params = {
      outlet_id: outlet.id,
      start_date: dateRange.value[0].toISOString().split('T')[0],
      end_date: dateRange.value[1].toISOString().split('T')[0],
    }

    const res = await client.get('/reports/export-pdf', { params })

    // Open in new tab — user bisa Ctrl+P / Save as PDF
    const win = window.open('', '_blank')
    if (win) {
      win.document.write(res.data)
      win.document.close()
    }
  } catch (err) {
    alert('Gagal export PDF')
  }
}

watch(chartType, () => {
  if (dailySales.value.length > 0) {
    buildSalesChart(dailySales.value)
  }
})

onMounted(() => {
  applyPreset('today')
})

onUnmounted(() => {
  if (salesChartInstance) salesChartInstance.destroy()
  if (compositionChartInstance) compositionChartInstance.destroy()
})
</script>
