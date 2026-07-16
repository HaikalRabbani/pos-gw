<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Manajemen Outlet</h1>
        <p class="text-sm text-slate-500 mt-1">Kelola cabang dan lokasi bisnis</p>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
            <i class="pi pi-building text-blue-600 text-lg"></i>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-blue-600">Total Outlet</p>
            <p class="text-2xl font-bold text-slate-900 mt-0.5">{{ outlets.length }}</p>
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
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="bg-white rounded-2xl border border-slate-200 p-6">
      <div class="animate-pulse space-y-3">
        <div class="h-10 bg-slate-200 rounded-lg w-full"></div>
        <div class="h-10 bg-slate-100 rounded-lg w-full"></div>
        <div class="h-10 bg-slate-100 rounded-lg w-full"></div>
      </div>
    </div>

    <!-- Table -->
    <div v-else class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
      <div class="p-3 border-b border-slate-100 flex flex-wrap items-center gap-2">
        <span class="flex-1 min-w-[200px]">
          <InputText v-model="search" placeholder="Cari outlet..." class="w-full" />
        </span>
        <Button v-if="perm.can('manageOutlets')" label="Tambah Outlet" icon="pi pi-plus" size="small" @click="openAddDialog" />
      </div>
      <div class="overflow-x-auto">
      <DataTable :value="filteredOutlets" stripedRows size="small" class="text-sm">
        <Column field="id" header="ID Outlet" style="width: 100px" sortable>
          <template #body="{ data }">
            <span class="font-mono text-xs font-bold text-teal-600 bg-teal-50 px-2 py-1 rounded-md">#{{ data.id }}</span>
          </template>
        </Column>
        <template #empty>
          <div class="flex flex-col items-center justify-center py-16 text-center">
            <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
              <i class="pi pi-building text-2xl text-slate-300"></i>
            </div>
            <p class="text-slate-500 font-medium">Belum ada outlet</p>
            <p class="text-slate-400 text-xs mt-1">Tambahkan outlet pertama Anda</p>
          </div>
        </template>
        <Column field="name" header="Nama Outlet" sortable>
          <template #body="{ data }">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-teal-100 to-teal-200 flex items-center justify-center text-sm font-bold text-teal-700">
                {{ data.name.charAt(0).toUpperCase() }}
              </div>
              <div>
                <p class="font-medium text-slate-900">{{ data.name }}</p>
                <p v-if="data.address" class="text-xs text-slate-400 truncate max-w-[200px]">{{ data.address }}</p>
              </div>
            </div>
          </template>
        </Column>
        <Column field="phone" header="Telepon">
          <template #body="{ data }">
            <span class="text-slate-600">{{ data.phone || '—' }}</span>
          </template>
        </Column>
        <Column header="Status" sortable>
          <template #body="{ data }">
            <Tag :value="data.is_active ? 'Aktif' : 'Non-Aktif'"
              :severity="data.is_active ? 'success' : 'danger'" rounded />
          </template>
        </Column>
        <Column header="Aksi" style="width: 160px">
          <template #body="{ data }">
            <div class="flex gap-3">
              <Button v-if="perm.can('manageOutlets')" icon="pi pi-pencil" text rounded size="small"
                v-tooltip.top="'Edit'" @click="openEditDialog(data)" />
              <Button v-if="perm.can('manageOutlets')" :icon="data.is_active ? 'pi pi-ban' : 'pi pi-check-circle'"
                text rounded size="small"
                :class="data.is_active ? 'text-amber-600' : 'text-teal-600'"
                v-tooltip.top="data.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                @click="toggleActive(data)" />
              <Button v-if="perm.can('manageOutlets')" icon="pi pi-trash" text rounded severity="danger" size="small"
                v-tooltip.top="'Hapus'" @click="confirmDelete(data)" />
            </div>
          </template>
        </Column>
      </DataTable>
      </div>
    </div>

    <!-- Add/Edit Dialog -->
    <Dialog v-model:visible="showDialog" :header="editing ? 'Edit Outlet' : 'Tambah Outlet'" modal class="w-lg">
      <form @submit.prevent="saveOutlet" class="space-y-6">
        <!-- Section: Informasi Outlet -->
        <div>
          <div class="flex items-center gap-2 mb-3">
            <div class="w-6 h-6 rounded-lg bg-teal-100 flex items-center justify-center">
              <i class="pi pi-building text-xs text-teal-700"></i>
            </div>
            <h3 class="text-sm font-semibold text-slate-800">Informasi Outlet</h3>
          </div>
          <div class="bg-slate-50 rounded-xl p-4 space-y-3">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Nama Outlet <span class="text-red-400">*</span></label>
              <InputText v-model="form.name" class="w-full" placeholder="Nama cabang" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Alamat</label>
              <Textarea v-model="form.address" class="w-full" placeholder="Alamat lengkap" :rows="2" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Telepon</label>
              <InputText v-model="form.phone" class="w-full" placeholder="Nomor telepon" />
            </div>
          </div>
        </div>

        <!-- Section: Manajemen Stok (hanya saat edit) -->
        <div v-if="editing">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-6 h-6 rounded-lg bg-emerald-100 flex items-center justify-center">
              <i class="pi pi-box text-xs text-emerald-700"></i>
            </div>
            <h3 class="text-sm font-semibold text-slate-800">Manajemen Stok</h3>
          </div>
          <div class="bg-slate-50 rounded-xl p-4 space-y-3">
            <div class="flex items-center gap-3 p-3 rounded-xl bg-emerald-50 border border-emerald-100">
              <i class="pi pi-info-circle text-emerald-400 text-lg shrink-0"></i>
              <p class="text-xs text-emerald-700">
                <strong>Per Produk:</strong> stok diatur langsung di tiap menu. Cocok untuk usaha simpel.<br>
                <strong>Per Bahan (BOM):</strong> stok diatur per ingredient, otomatis berkurang pas order. Cocok untuk restoran dengan resep kompleks.
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Mode Stok</label>
              <Select v-model="form.stock_mode" :options="stockModeOptions" optionLabel="label" optionValue="value"
                class="w-full" />
            </div>
          </div>
        </div>

        <!-- Section: Pembayaran QRIS (hanya saat edit) -->
        <div v-if="editing">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-6 h-6 rounded-lg bg-blue-100 flex items-center justify-center">
              <i class="pi pi-credit-card text-xs text-blue-700"></i>
            </div>
            <h3 class="text-sm font-semibold text-slate-800">Akun Midtrans</h3>
            <span class="text-[10px] text-slate-400 font-normal">(opsional — default pake Xendit)</span>
          </div>
          <div class="bg-slate-50 rounded-xl p-4">
            <div class="flex items-center gap-3 p-3 rounded-xl bg-blue-50 border border-blue-100 mb-3">
              <i class="pi pi-info-circle text-blue-400 text-lg shrink-0"></i>
              <p class="text-xs text-blue-700">Default pembayaran via <strong>Xendit</strong>. Isi Midtrans key hanya jika outlet punya akun Midtrans sendiri.</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">
                Midtrans Server Key
                <span class="text-xs text-slate-400 font-normal ml-1">— isi jika punya akun sendiri</span>
              </label>
              <div class="relative">
                <InputText v-model="form.midtrans_server_key" class="w-full pr-10"
                  :type="showMidtransKey ? 'text' : 'password'"
                  placeholder="SB-Mid-server-xxxxxxxxxx" autocomplete="off" />
                <button type="button"
                  class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                  @click="showMidtransKey = !showMidtransKey">
                  <i :class="showMidtransKey ? 'pi pi-eye-slash' : 'pi pi-eye'"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
          <Button label="Batal" severity="secondary" @click="showDialog = false" />
          <Button type="submit" :label="editing ? 'Simpan' : 'Tambah'" :loading="saving" />
        </div>
      </form>
    </Dialog>

    <!-- Delete Confirmation -->
    <Dialog v-model:visible="showDeleteDialog" header="Hapus Outlet" modal class="w-sm">
      <div class="space-y-3">
        <div class="flex items-center gap-3 p-3 rounded-xl bg-red-50 border border-red-100">
          <i class="pi pi-exclamation-triangle text-red-500 text-xl"></i>
          <p class="text-sm text-red-700">
            Yakin ingin menghapus <strong>{{ deletingOutlet?.name }}</strong>? Tindakan ini tidak bisa dibatalkan.
          </p>
        </div>
        <p class="text-xs text-slate-500">Outlet dengan pesanan yang sudah ada tidak bisa dihapus. Nonaktifkan saja sebagai gantinya.</p>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Batal" severity="secondary" @click="showDeleteDialog = false" />
          <Button label="Hapus" severity="danger" :loading="deleting" @click="deleteOutlet" />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePermission } from '../../utils/usePermission'
import { useToastStore } from '../../stores/toast'
import client from '../../api/client'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'

import Textarea from 'primevue/textarea'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import Tooltip from 'primevue/tooltip'

const perm = usePermission()
const toast = useToastStore()

const outlets = ref([])
const loading = ref(true)
const saving = ref(false)
const search = ref('')
const deleting = ref(false)
const showDialog = ref(false)
const showDeleteDialog = ref(false)
const editing = ref(false)
const deletingOutlet = ref(null)

const form = ref({ name: '', address: '', phone: '', stock_mode: 'product', midtrans_server_key: '' })
const showMidtransKey = ref(false)

const stockModeOptions = [
  { label: 'Per Produk — stok diatur langsung di tiap menu', value: 'product' },
  { label: 'Per Bahan (BOM) — stok di ingredient, otomatis terpotong', value: 'ingredient' },
]

const activeCount = computed(() => outlets.value.filter((o) => o.is_active).length)
const inactiveCount = computed(() => outlets.value.filter((o) => !o.is_active).length)

const filteredOutlets = computed(() => {
  if (!search.value) return outlets.value
  const q = search.value.toLowerCase()
  return outlets.value.filter(o => o.name.toLowerCase().includes(q) || (o.address || '').toLowerCase().includes(q))
})

async function fetchOutlets() {
  loading.value = true
  try {
    const { data } = await client.get('/outlets')
    outlets.value = data.data
  } catch (_) {}
  finally { loading.value = false }
}

function openAddDialog() {
  editing.value = false
  form.value = { name: '', address: '', phone: '', midtrans_server_key: '' }
  showMidtransKey.value = false
  showDialog.value = true
}

function openEditDialog(outlet) {
  editing.value = true
  form.value = {
    id: outlet.id,
    name: outlet.name,
    address: outlet.address || '',
    phone: outlet.phone || '',
    stock_mode: outlet.stock_mode || 'product',
    midtrans_server_key: outlet.midtrans_server_key || '',
  }
  showMidtransKey.value = false
  showDialog.value = true
}

async function saveOutlet() {
  saving.value = true
  try {
    if (editing.value) {
      await client.put(`/outlets/${form.value.id}`, {
        name: form.value.name,
        address: form.value.address,
        phone: form.value.phone,
        stock_mode: form.value.stock_mode,
        midtrans_server_key: form.value.midtrans_server_key || null,
      })
    } else {
      await client.post('/outlets', {
        name: form.value.name,
        address: form.value.address,
        phone: form.value.phone,
      })
    }
    showDialog.value = false
    fetchOutlets()
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal menyimpan outlet')
  } finally {
    saving.value = false
  }
}

async function toggleActive(outlet) {
  try {
    await client.put(`/outlets/${outlet.id}`, { is_active: !outlet.is_active })
    fetchOutlets()
  } catch (_) {}
}

function confirmDelete(outlet) {
  deletingOutlet.value = outlet
  showDeleteDialog.value = true
}

async function deleteOutlet() {
  deleting.value = true
  try {
    await client.delete(`/outlets/${deletingOutlet.value.id}`)
    showDeleteDialog.value = false
    fetchOutlets()
  } catch (e) {
    toast.error('Gagal', e.response?.data?.message || 'Gagal menghapus outlet')
  } finally {
    deleting.value = false
  }
}

onMounted(fetchOutlets)
</script>
