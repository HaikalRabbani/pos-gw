<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-slate-900">Riwayat Shift</h1>
      <p class="text-sm text-slate-500 mt-1">Daftar shift kasir dan rekonsiliasi kas</p>
    </div>

    <!-- Loading -->
    <template v-if="loading">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div v-for="i in 3" :key="i" class="bg-white rounded-2xl border border-slate-200 p-5 animate-pulse">
          <div class="h-4 w-20 bg-slate-200 rounded mb-3"></div>
          <div class="h-8 w-32 bg-slate-200 rounded mb-1"></div>
          <div class="h-3 w-24 bg-slate-100 rounded"></div>
        </div>
      </div>
      <div class="bg-white rounded-2xl border border-slate-200 p-6 animate-pulse">
        <div class="h-5 w-40 bg-slate-200 rounded mb-4"></div>
        <div class="space-y-3">
          <div v-for="i in 5" :key="i" class="h-10 bg-slate-100 rounded"></div>
        </div>
      </div>
    </template>

    <template v-else>
      <!-- Active Shift Info -->
      <div v-if="activeShift"
        class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-800 via-emerald-900 to-emerald-950 p-5">
        <div class="absolute top-0 right-0 w-48 h-48 bg-emerald-400/10 rounded-full -translate-y-1/2 translate-x-1/4 blur-3xl"></div>
        <div class="relative z-10 flex items-start gap-4">
          <div class="w-10 h-10 rounded-xl bg-emerald-700/50 flex items-center justify-center shrink-0 mt-0.5">
            <i class="pi pi-user text-emerald-200 text-lg"></i>
          </div>
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-1">
              <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
              <span class="text-[10px] font-semibold uppercase tracking-wider text-emerald-300">Shift Aktif</span>
            </div>
            <p class="text-lg font-bold text-white">{{ activeShift.user?.name }}</p>
            <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1 text-sm text-emerald-200/80">
              <span><i class="pi pi-clock mr-1"></i>Mulai: {{ formatDateTime(activeShift.start_at) }}</span>
              <span><i class="pi pi-money-bill mr-1"></i>Kas Awal: {{ formatRupiah(activeShift.cash_begin) }}</span>
            </div>
          </div>
          <Tag value="Aktif" severity="success" rounded class="shrink-0" />
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-4">
          <div class="flex items-center gap-2 mb-1">
            <div class="w-8 h-8 rounded-xl bg-blue-100 flex items-center justify-center">
              <i class="pi pi-calendar text-blue-600 text-sm"></i>
            </div>
            <span class="text-[10px] font-semibold uppercase tracking-wider text-blue-600">Total Shift</span>
          </div>
          <p class="text-xl font-bold text-slate-900">{{ totalShifts }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4">
          <div class="flex items-center gap-2 mb-1">
            <div class="w-8 h-8 rounded-xl bg-emerald-100 flex items-center justify-center">
              <i class="pi pi-check-circle text-emerald-600 text-sm"></i>
            </div>
            <span class="text-[10px] font-semibold uppercase tracking-wider text-emerald-600">Selesai</span>
          </div>
          <p class="text-xl font-bold text-slate-900">{{ completedShifts }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4">
          <div class="flex items-center gap-2 mb-1">
            <div class="w-8 h-8 rounded-xl bg-amber-100 flex items-center justify-center">
              <i class="pi pi-exclamation-triangle text-amber-600 text-sm"></i>
            </div>
            <span class="text-[10px] font-semibold uppercase tracking-wider text-amber-600">Selisih Kas</span>
          </div>
          <p class="text-xl font-bold text-slate-900">{{ formatRupiah(totalCashDiff) }}</p>
        </div>
      </div>

      <!-- Shift History Table -->
      <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
          <h3 class="font-semibold text-slate-900 flex items-center gap-2">
            <i class="pi pi-history text-slate-400"></i>
            Riwayat Shift
          </h3>
          <Button icon="pi pi-refresh" severity="secondary" text rounded size="small"
            @click="fetchShifts" :loading="loading" />
        </div>
        <DataTable :value="shifts" paginator :rows="10" stripedRows size="small"
          paginatorTemplate="CurrentPageReport PrevPageLink NextPageLink"
          currentPageReportTemplate="Halaman {currentPage} dari {totalPages}"
          class="text-sm">
          <Column header="#" style="width: 50px">
            <template #body="{ index }">
              <span class="text-slate-400 text-xs font-mono">{{ index + 1 }}</span>
            </template>
          </Column>
          <template #empty>
            <div class="flex flex-col items-center justify-center py-16 text-center">
              <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
                <i class="pi pi-clock text-2xl text-slate-300"></i>
              </div>
              <p class="text-slate-500 font-medium">Belum ada riwayat shift</p>
              <p class="text-slate-400 text-xs mt-1">Riwayat shift akan muncul setelah kasir memulai shift</p>
            </div>
          </template>
          <Column field="start_at" header="Tanggal" sortable>
            <template #body="{ data }">
              <span class="text-slate-900 font-medium">{{ formatDate(data.start_at) }}</span>
            </template>
          </Column>
          <Column field="user.name" header="Kasir" sortable />
          <Column field="start_at" header="Mulai" sortable>
            <template #body="{ data }">
              {{ formatTime(data.start_at) }}
            </template>
          </Column>
          <Column field="end_at" header="Selesai" sortable>
            <template #body="{ data }">
              {{ data.end_at ? formatTime(data.end_at) : '-' }}
            </template>
          </Column>
          <Column field="cash_begin" header="Kas Awal" sortable>
            <template #body="{ data }">
              <span class="font-medium">{{ formatRupiah(data.cash_begin) }}</span>
            </template>
          </Column>
          <Column field="cash_expected" header="Kas Harapan" sortable>
            <template #body="{ data }">
              <span class="font-medium">{{ data.cash_expected !== null ? formatRupiah(data.cash_expected) : '-' }}</span>
            </template>
          </Column>
          <Column field="cash_actual" header="Kas Aktual" sortable>
            <template #body="{ data }">
              <span class="font-medium">{{ data.cash_actual !== null ? formatRupiah(data.cash_actual) : '-' }}</span>
            </template>
          </Column>
          <Column field="cash_diff" header="Selisih" sortable>
            <template #body="{ data }">
              <span v-if="data.cash_diff !== null" class="font-semibold"
                :class="data.cash_diff === 0 ? 'text-slate-700' : data.cash_diff > 0 ? 'text-emerald-600' : 'text-red-600'">
                {{ data.cash_diff > 0 ? '+' : '' }}{{ formatRupiah(data.cash_diff) }}
              </span>
              <span v-else class="text-slate-300">—</span>
            </template>
          </Column>
          <Column field="note" header="Catatan" sortable />
          <Column field="status" header="Status" sortable>
            <template #body="{ data }">
              <Tag :value="data.end_at ? 'Selesai' : 'Aktif'" :severity="data.end_at ? 'success' : 'info'" rounded />
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
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'

const loading = ref(true)
const shifts = ref([])
const activeShift = ref(null)

// Summary
const totalShifts = ref(0)
const completedShifts = ref(0)
const totalCashDiff = ref(0)

function formatRupiah(cents) {
  if (cents === null || cents === undefined) return 'Rp 0'
  return 'Rp ' + (Math.round(cents)).toLocaleString('id-ID')
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

function formatTime(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
}

function formatDateTime(dateStr) {
  if (!dateStr) return '-'
  return formatDate(dateStr) + ' ' + formatTime(dateStr)
}

async function fetchShifts() {
  loading.value = true
  try {
    const { data: outletRes } = await client.get('/outlets')
    const outlet = outletRes.data[0]
    if (!outlet) return

    const { data } = await client.get('/shifts', {
      params: { outlet_id: outlet.id, per_page: 50 }
    })

    shifts.value = data.data || []
    activeShift.value = shifts.value.find(s => !s.end_at) || null

    totalShifts.value = data.meta?.total || 0
    const completed = shifts.value.filter(s => s.end_at)
    completedShifts.value = completed.length
    totalCashDiff.value = completed.reduce((sum, s) => sum + (s.cash_diff || 0), 0)
  } catch (err) {
    console.error('Failed to fetch shifts:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchShifts()
})
</script>
