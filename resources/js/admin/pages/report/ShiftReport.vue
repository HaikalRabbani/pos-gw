<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Laporan Shift</h1>
        <p class="text-sm text-slate-500 mt-1">Rekap shift karyawan dan total kas</p>
      </div>
      <div class="flex items-center gap-2">
        <Button label="Export Excel" icon="pi pi-file-excel" severity="success" outlined @click="exportExcel" />
        <Button label="Export PDF" icon="pi pi-file-pdf" severity="danger" outlined @click="exportPdf" />
      </div>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 flex flex-col sm:flex-row items-start sm:items-center gap-3">
      <div class="flex items-center gap-2">
        <i class="pi pi-calendar text-slate-400"></i>
        <span class="text-sm font-medium text-slate-700">Periode:</span>
      </div>
      <DatePicker v-model="dateRange" selectionMode="range" :manualInput="false" dateFormat="dd/mm/yy"
        class="w-full sm:w-auto" @update:modelValue="onFilterChange" />
      <Select v-model="outletId" :options="outlets" optionLabel="name" optionValue="id"
        placeholder="Pilih outlet" class="w-full sm:w-48" @change="fetchData" />
    </div>

    <!-- Loading -->
    <template v-if="loading">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div v-for="i in 4" :key="i" class="bg-white rounded-2xl border border-slate-200 p-4 animate-pulse">
          <div class="h-3 w-16 bg-slate-200 rounded mb-3"></div>
          <div class="h-7 w-24 bg-slate-200 rounded mb-1"></div>
        </div>
      </div>
    </template>

    <!-- Content -->
    <template v-else>
      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div v-for="card in summaryCards" :key="card.label"
          class="bg-white rounded-2xl border border-slate-200 p-4">
          <div class="flex items-center gap-2 mb-2">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center" :class="card.bgClass">
              <i :class="[card.icon, card.iconClass]"></i>
            </div>
            <span class="text-[10px] font-semibold uppercase tracking-wider" :class="card.labelClass">{{ card.label }}</span>
          </div>
          <p class="text-lg font-bold text-slate-900">{{ card.value }}</p>
          <p class="text-[10px] text-slate-400 mt-0.5">{{ card.subtext }}</p>
        </div>
      </div>

      <!-- Shift Table -->
      <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
          <h3 class="font-semibold text-slate-900 flex items-center gap-2">
            <i class="pi pi-history text-slate-400"></i>
            Riwayat Shift
          </h3>
        </div>
        <DataTable :value="shifts" stripedRows size="small" class="text-sm">
          <template #empty>
            <div class="flex flex-col items-center justify-center py-16 text-center">
              <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
                <i class="pi pi-clock text-2xl text-slate-300"></i>
              </div>
              <p class="text-slate-500 font-medium">Belum ada data shift</p>
              <p class="text-slate-400 text-xs mt-1">Data akan muncul setelah ada shift yang dimulai</p>
            </div>
          </template>
          <Column header="Karyawan">
            <template #body="{ data }">
              <span class="font-medium text-slate-900">{{ data.user?.name || '-' }}</span>
            </template>
          </Column>
          <Column header="Outlet">
            <template #body="{ data }">
              <span class="text-slate-600">{{ data.outlet?.name || '-' }}</span>
            </template>
          </Column>
          <Column field="start_at" header="Mulai">
            <template #body="{ data }">
              <span class="text-slate-600">{{ formatDate(data.start_at) }}</span>
            </template>
          </Column>
          <Column field="end_at" header="Selesai">
            <template #body="{ data }">
              <span v-if="data.end_at" class="text-slate-600">{{ formatDate(data.end_at) }}</span>
              <Tag v-else value="Aktif" severity="success" rounded />
            </template>
          </Column>
          <Column field="cash_begin" header="Kas Awal">
            <template #body="{ data }">
              <span class="font-medium">{{ formatRupiah(data.cash_begin) }}</span>
            </template>
          </Column>
          <Column field="cash_expected" header="Kas Diharapkan">
            <template #body="{ data }">
              <span class="font-medium">{{ formatRupiah(data.cash_expected) }}</span>
            </template>
          </Column>
          <Column field="cash_actual" header="Kas Aktual">
            <template #body="{ data }">
              <span class="font-medium">{{ formatRupiah(data.cash_actual) }}</span>
            </template>
          </Column>
          <Column field="cash_diff" header="Selisih">
            <template #body="{ data }">
              <span class="font-semibold" :class="diffClass(data.cash_diff)">
                {{ formatRupiah(data.cash_diff) }}
              </span>
            </template>
          </Column>
        </DataTable>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import client from '../../api/client'
import { formatRupiah } from '../../utils/format'
import { useAuthStore } from '../../stores/auth'
import { useToastStore } from '../../stores/toast'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'

const auth = useAuthStore()
const toast = useToastStore()
const loading = ref(true)
const dateRange = ref(null)
const outletId = ref(null)
const outlets = ref([])
const shifts = ref([])

// ── Outlets ──
async function fetchOutlets() {
  try {
    const { data: res } = await client.get('/outlets')
    outlets.value = res.data || []
    if (outlets.value.length > 0) {
      outletId.value = outlets.value[0].id
    }
  } catch (_) {}
}

// ── Summary ──
const summaryCards = computed(() => {
  const list = shifts.value
  const total = list.length
  const active = list.filter(s => !s.end_at).length
  const totalCashExpected = list.reduce((a, s) => a + (s.cash_expected || 0), 0)
  const totalCashActual = list.reduce((a, s) => a + (s.cash_actual || 0), 0)
  const totalDiff = totalCashActual - totalCashExpected

  return [
    {
      label: 'Total Shift',
      value: String(total),
      icon: 'pi pi-sync',
      iconClass: 'text-blue-600',
      bgClass: 'bg-blue-100',
      labelClass: 'text-blue-600',
      subtext: active > 0 ? active + ' sedang aktif' : 'Semua selesai',
    },
    {
      label: 'Kas Diharapkan',
      value: formatRupiah(totalCashExpected),
      icon: 'pi pi-calculator',
      iconClass: 'text-teal-600',
      bgClass: 'bg-teal-100',
      labelClass: 'text-teal-600',
      subtext: 'Total ' + total + ' shift',
    },
    {
      label: 'Kas Aktual',
      value: formatRupiah(totalCashActual),
      icon: 'pi pi-money-bill',
      iconClass: 'text-emerald-600',
      bgClass: 'bg-emerald-100',
      labelClass: 'text-emerald-600',
      subtext: 'Uang fisik diterima',
    },
    {
      label: 'Selisih Kas',
      value: formatRupiah(totalDiff),
      icon: 'pi pi-chart-line',
      iconClass: totalDiff >= 0 ? 'text-violet-600' : 'text-red-600',
      bgClass: totalDiff >= 0 ? 'bg-violet-100' : 'bg-red-100',
      labelClass: totalDiff >= 0 ? 'text-violet-600' : 'text-red-600',
      subtext: totalDiff >= 0 ? 'Lebih' : 'Kurang',
    },
  ]
})

function diffClass(val) {
  if (val > 0) return 'text-emerald-600'
  if (val < 0) return 'text-red-600'
  return 'text-slate-600'
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' })
}

// ── Fetch ──
async function fetchData() {
  if (!outletId.value) return
  loading.value = true
  try {
    const params = { outlet_id: outletId.value, per_page: 100 }
    if (dateRange.value?.length === 2) {
      params.start_date = dateRange.value[0].toISOString().split('T')[0]
      params.end_date = dateRange.value[1].toISOString().split('T')[0]
    }
    const { data: res } = await client.get('/shifts', { params })
    shifts.value = res.data || []
  } catch (_) {
    shifts.value = []
  } finally {
    loading.value = false
  }
}

function onFilterChange() {
  if (dateRange.value?.length === 2) {
    fetchData()
  }
}

async function exportExcel() {
  if (!outletId.value) return
  try {
    const params = { outlet_id: outletId.value }
    if (dateRange.value?.length === 2) {
      params.start_date = dateRange.value[0].toISOString().split('T')[0]
      params.end_date = dateRange.value[1].toISOString().split('T')[0]
    }
    const res = await client.get('/reports/export-shift-excel', {
      params,
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href = url
    const start = params.start_date || 'all'
    const end = params.end_date || 'all'
    link.setAttribute('download', `laporan-shift-${start}_${end}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (err) {
    toast.error('Gagal Export Excel', 'Terjadi kesalahan saat export')
  }
}

async function exportPdf() {
  if (!outletId.value) return
  try {
    const params = { outlet_id: outletId.value }
    if (dateRange.value?.length === 2) {
      params.start_date = dateRange.value[0].toISOString().split('T')[0]
      params.end_date = dateRange.value[1].toISOString().split('T')[0]
    }
    const res = await client.get('/reports/export-shift-pdf', { params })
    const win = window.open('', '_blank')
    if (win) {
      win.document.write(res.data)
      win.document.close()
    }
  } catch (err) {
    toast.error('Gagal Export PDF', 'Terjadi kesalahan saat export')
  }
}

onMounted(async () => {
  await fetchOutlets()
  if (outletId.value) fetchData()
})
</script>
