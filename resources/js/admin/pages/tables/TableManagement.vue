<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Manajemen Meja</h1>
        <p class="text-sm text-slate-500 mt-1">Kelola meja restoran dan QR code untuk self-order</p>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
            <i class="pi pi-table text-blue-600 text-lg"></i>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-blue-600">Total Meja</p>
            <p class="text-2xl font-bold text-slate-900 mt-0.5">{{ tables.length }}</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
            <i class="pi pi-check-circle text-emerald-600 text-lg"></i>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600">Aktif</p>
            <p class="text-2xl font-bold text-slate-900 mt-0.5">{{ activeCount }}</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center">
            <i class="pi pi-ban text-slate-400 text-lg"></i>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Non-Aktif</p>
            <p class="text-2xl font-bold text-slate-400 mt-0.5">{{ inactiveCount }}</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-violet-100 flex items-center justify-center">
            <i class="pi pi-qrcode text-violet-600 text-lg"></i>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-violet-600">Dengan QR</p>
            <p class="text-2xl font-bold text-slate-900 mt-0.5">{{ withQrCount }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- QR Base URL Config -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 flex flex-col sm:flex-row items-start sm:items-center gap-3">
      <div class="flex items-center gap-2 shrink-0">
        <i class="pi pi-link text-slate-400"></i>
        <span class="text-sm font-medium text-slate-700">URL Order:</span>
      </div>
      <div class="flex items-center gap-2 w-full sm:w-auto flex-1">
        <InputText v-model="qrBaseUrl" placeholder="https://order.namaoutlet.com"
          class="flex-1 font-mono text-sm" @blur="saveQrBaseUrl" @keyup.enter="saveQrBaseUrl" />
        <span class="text-xs text-slate-400 shrink-0">/table/{token}</span>
      </div>
      <Button icon="pi pi-external-link" text rounded size="small"
        v-tooltip.top="'Test buka URL'" @click="testOrderUrl" />
    </div>

    <!-- Outlet Selector (when no outlet selected) -->
    <div v-if="!selectedOutletId" class="bg-white rounded-2xl border border-slate-200 p-12 flex flex-col items-center justify-center text-center">
      <i class="pi pi-building text-4xl text-slate-200 mb-3"></i>
      <p class="text-slate-600 font-semibold">Pilih outlet terlebih dahulu</p>
      <p class="text-slate-400 text-sm mt-1">Pilih outlet untuk melihat daftar meja yang tersedia.</p>
    </div>

    <!-- Loading State -->
    <div v-else-if="loading" class="bg-white rounded-2xl border border-slate-200 p-6">
      <div class="animate-pulse space-y-3">
        <div class="h-10 bg-slate-200 rounded-lg w-full"></div>
        <div class="h-10 bg-slate-100 rounded-lg w-full"></div>
      </div>
    </div>

    <!-- Table -->
    <div v-else class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
      <div class="p-3 border-b border-slate-100 flex items-center justify-end gap-2">
        <Select v-model="selectedOutletId" :options="outlets" optionLabel="name" optionValue="id"
          placeholder="Pilih outlet" class="w-44" @change="fetchTables" />
        <Button v-if="perm.can('manageTables')" label="Tambah Meja" icon="pi pi-plus" size="small" @click="openAddDialog" :disabled="!selectedOutletId" />
      </div>
      <DataTable :value="tables" stripedRows size="small" class="text-sm">
        <Column header="No." style="width: 50px">
          <template #body="{ index }">
            <span class="text-slate-400 text-xs font-mono">{{ index + 1 }}</span>
          </template>
        </Column>
        <template #empty>
          <div class="flex flex-col items-center justify-center py-16 text-center">
            <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
              <i class="pi pi-table text-2xl text-slate-300"></i>
            </div>
            <p class="text-slate-500 font-medium">Belum ada meja</p>
            <p class="text-slate-400 text-xs mt-1">Tambahkan meja untuk outlet ini</p>
          </div>
        </template>
        <Column field="name" header="Nama Meja" sortable>
          <template #body="{ data }">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-teal-100 to-teal-200 flex items-center justify-center text-sm font-bold text-teal-700">
                <i class="pi pi-table text-xs"></i>
              </div>
              <span class="font-medium text-slate-900">{{ data.name }}</span>
            </div>
          </template>
        </Column>
        <Column header="QR Code" style="width: 200px">
          <template #body="{ data }">
            <div v-if="data.qr_token" class="flex flex-col items-center gap-1.5 py-1">
              <img :src="qrCodeUrl(data.qr_token)"
                :alt="'QR ' + data.name"
                class="w-20 h-20 rounded-xl border border-slate-200 cursor-pointer hover:ring-2 hover:ring-teal-300 hover:shadow-md transition-all"
                @click="openQrPreview(data)"
                loading="lazy" />
              <div class="flex items-center gap-1">
                <Button icon="pi pi-download" text rounded size="small" class="!w-7 !h-7"
                  v-tooltip.top="'Download QR'" @click="downloadQr(data)" />
                <Button icon="pi pi-print" text rounded size="small" class="!w-7 !h-7"
                  v-tooltip.top="'Cetak QR'" @click="printQr(data)" />
                <Button icon="pi pi-refresh" text rounded size="small" class="!w-7 !h-7 text-amber-600"
                  v-tooltip.top="'Regenerate QR'" @click="regenerateQr(data)" />
              </div>
            </div>
            <div v-else class="flex flex-col items-center gap-1 py-2">
              <div class="w-16 h-16 rounded-xl bg-slate-50 border border-dashed border-slate-200 flex items-center justify-center">
                <i class="pi pi-qrcode text-slate-300 text-xl"></i>
              </div>
              <span class="text-[10px] text-slate-400">Regenerate untuk buat QR</span>
            </div>
          </template>
        </Column>
        <Column header="Status" sortable style="width: 100px">
          <template #body="{ data }">
            <Tag :value="data.is_active ? 'Aktif' : 'Non-Aktif'"
              :severity="data.is_active ? 'success' : 'danger'" rounded />
          </template>
        </Column>
        <Column header="Aksi" style="width: 140px">
          <template #body="{ data }">
            <div class="flex gap-2">
              <Button v-if="perm.can('manageTables')" icon="pi pi-pencil" text rounded size="small"
                v-tooltip.top="'Edit Nama Meja'" @click="openEditDialog(data)" />
              <Button v-if="perm.can('manageTables')" :icon="data.is_active ? 'pi pi-ban' : 'pi pi-check-circle'"
                text rounded size="small"
                :class="data.is_active ? 'text-amber-600' : 'text-teal-600'"
                v-tooltip.top="data.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                @click="toggleActive(data)" />
              <Button v-if="perm.can('manageTables')" icon="pi pi-trash" text rounded severity="danger" size="small"
                v-tooltip.top="'Hapus Meja'" @click="confirmDelete(data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Add/Edit Dialog -->
    <Dialog v-model:visible="showDialog" :header="editing ? 'Edit Meja' : 'Tambah Meja'" modal class="w-md">
      <form @submit.prevent="saveTable" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Nama Meja <span class="text-red-400">*</span></label>
          <InputText v-model="form.name" class="w-full" placeholder="Contoh: Meja 1, VIP Room, Outdoor A" required />
        </div>
        <div v-if="editing" class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200">
          <i class="pi pi-info-circle text-blue-400 text-lg"></i>
          <p class="text-xs text-slate-500">Untuk menonaktifkan/mengaktifkan meja, gunakan tombol toggle di tabel.</p>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Batal" severity="secondary" @click="showDialog = false" />
          <Button type="submit" :label="editing ? 'Simpan' : 'Tambah'" :loading="saving" />
        </div>
      </form>
    </Dialog>

    <!-- Delete Confirmation -->
    <Dialog v-model:visible="showDeleteDialog" header="Hapus Meja" modal class="w-sm">
      <div class="space-y-3">
        <div class="flex items-center gap-3 p-3 rounded-xl bg-red-50 border border-red-100">
          <i class="pi pi-exclamation-triangle text-red-500 text-xl"></i>
          <p class="text-sm text-red-700">
            Yakin ingin menghapus <strong>{{ deletingTable?.name }}</strong>?
          </p>
        </div>
        <p class="text-xs text-slate-500">Meja yang memiliki riwayat pesanan tidak bisa dihapus. Nonaktifkan saja sebagai gantinya.</p>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Batal" severity="secondary" @click="showDeleteDialog = false" />
          <Button label="Hapus" severity="danger" :loading="deleting" @click="deleteTable" />
        </div>
      </div>
    </Dialog>

    <!-- QR Preview Dialog -->
    <Dialog v-model:visible="showQrDialog" :header="'QR Code — ' + qrPreviewTable?.name" modal class="w-sm"
      :closable="true" @hide="showQrDialog = false">
      <div v-if="qrPreviewTable" class="flex flex-col items-center gap-4 py-2">
        <!-- QR Image (large) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
          <img :src="qrCodeUrl(qrPreviewTable.qr_token, 400)"
            :alt="'QR ' + qrPreviewTable.name"
            class="w-56 h-56" />
        </div>

        <!-- Table Info -->
        <div class="text-center">
          <p class="text-lg font-bold text-slate-900">{{ qrPreviewTable.name }}</p>
          <p class="text-xs text-slate-400 mt-0.5 font-mono">
            {{ qrFullUrl(qrPreviewTable.qr_token) }}
          </p>
        </div>

        <!-- Actions -->
        <div class="flex gap-2 w-full">
          <Button label="Download PNG" icon="pi pi-download" class="flex-1" severity="secondary"
            @click="downloadQr(qrPreviewTable)" />
          <Button label="Cetak" icon="pi pi-print" class="flex-1" severity="secondary"
            @click="printQr(qrPreviewTable)" />
          <Button label="Salin URL" icon="pi pi-copy" class="flex-1"
            @click="copyQrUrl(qrPreviewTable.qr_token)" />
        </div>

        <!-- Print Description -->
        <div class="flex items-center gap-2 text-xs text-slate-400 bg-slate-50 rounded-xl px-4 py-2.5 w-full">
          <i class="pi pi-info-circle text-slate-300"></i>
          <span>Cetak QR, laminating, dan tempel di meja agar pelanggan bisa scan untuk pesan sendiri.</span>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePermission } from '../../utils/usePermission'
import client from '../../api/client'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Tooltip from 'primevue/tooltip'

const perm = usePermission()

const tables = ref([])
const outlets = ref([])
const selectedOutletId = ref(null)
const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)
const showDialog = ref(false)
const showDeleteDialog = ref(false)
const showQrDialog = ref(false)
const editing = ref(false)
const deletingTable = ref(null)
const qrPreviewTable = ref(null)

const form = ref({ name: '' })

// QR base URL — persisted di localStorage
const qrBaseUrl = ref(localStorage.getItem('qr_base_url') || 'https://order.namaoutlet.com')

function saveQrBaseUrl() {
  localStorage.setItem('qr_base_url', qrBaseUrl.value)
}

function qrFullUrl(token) {
  const base = qrBaseUrl.value.replace(/\/+$/, '')
  return `${base}/table/${token}`
}

function qrCodeUrl(token, size = 250) {
  const url = encodeURIComponent(qrFullUrl(token))
  return `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&data=${url}&margin=10`
}

const activeCount = computed(() => tables.value.filter((t) => t.is_active).length)
const inactiveCount = computed(() => tables.value.filter((t) => !t.is_active).length)
const withQrCount = computed(() => tables.value.filter((t) => t.qr_token).length)

async function fetchOutlets() {
  try {
    const { data } = await client.get('/outlets')
    outlets.value = data.data
    if (data.data.length > 0 && !selectedOutletId.value) {
      selectedOutletId.value = data.data[0].id
      fetchTables()
    }
  } catch (_) {}
}

async function fetchTables() {
  if (!selectedOutletId.value) return
  loading.value = true
  try {
    const { data } = await client.get('/tables', { params: { outlet_id: selectedOutletId.value } })
    tables.value = data.data
  } catch (_) {}
  finally { loading.value = false }
}

function openAddDialog() {
  editing.value = false
  form.value = { name: '' }
  showDialog.value = true
}

function openEditDialog(table) {
  editing.value = true
  form.value = { name: table.name, id: table.id }
  showDialog.value = true
}

async function saveTable() {
  saving.value = true
  try {
    const payload = {
      outlet_id: selectedOutletId.value,
      name: form.value.name,
    }
    if (editing.value) {
      await client.put(`/tables/${form.value.id}`, payload)
    } else {
      await client.post('/tables', payload)
    }
    showDialog.value = false
    fetchTables()
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menyimpan meja')
  } finally {
    saving.value = false
  }
}

async function toggleActive(table) {
  try {
    await client.put(`/tables/${table.id}`, { is_active: !table.is_active })
    fetchTables()
  } catch (_) {}
}

async function regenerateQr(table) {
  try {
    await client.post(`/tables/${table.id}/regenerate-qr`)
    fetchTables()
  } catch (_) {}
}

function openQrPreview(table) {
  qrPreviewTable.value = table
  showQrDialog.value = true
}

/**
 * Download QR sebagai PNG
 */
async function downloadQr(table) {
  const imgUrl = qrCodeUrl(table.qr_token, 400)
  try {
    const response = await fetch(imgUrl)
    const blob = await response.blob()
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `QR-${table.name.replace(/\s+/g, '-')}.png`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
  } catch (_) {
    // Fallback: buka di tab baru
    window.open(imgUrl, '_blank')
  }
}

/**
 * Cetak QR — buka gambar di tab baru lalu print
 */
async function printQr(table) {
  const imgUrl = qrCodeUrl(table.qr_token, 500)
  try {
    const response = await fetch(imgUrl)
    const blob = await response.blob()
    const url = URL.createObjectURL(blob)
    const win = window.open('', '_blank')
    if (win) {
      win.document.write(`
        <html>
        <head><title>QR ${table.name}</title></head>
        <body style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;margin:0;font-family:sans-serif">
          <img src="${url}" style="width:80vw;max-width:400px" onload="window.print()" />
          <p style="margin-top:12px;color:#666;font-size:14px">${table.name}</p>
        </body>
        </html>
      `)
      win.document.close()
    }
  } catch (_) {
    window.open(imgUrl, '_blank')
  }
}

async function copyQrUrl(token) {
  const fullUrl = qrFullUrl(token)
  try {
    await navigator.clipboard.writeText(fullUrl)
  } catch (_) {
    alert('URL: ' + fullUrl)
  }
}

function testOrderUrl() {
  window.open(qrBaseUrl.value, '_blank')
}

function confirmDelete(table) {
  deletingTable.value = table
  showDeleteDialog.value = true
}

async function deleteTable() {
  deleting.value = true
  try {
    await client.delete(`/tables/${deletingTable.value.id}`)
    showDeleteDialog.value = false
    fetchTables()
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menghapus meja')
  } finally {
    deleting.value = false
  }
}

onMounted(fetchOutlets)
</script>
