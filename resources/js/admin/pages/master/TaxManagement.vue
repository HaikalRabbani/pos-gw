<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Manajemen Pajak</h1>
        <p class="text-sm text-slate-500 mt-1">Kelola tarif pajak untuk setiap outlet</p>
      </div>

    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center">
            <i class="pi pi-percentage text-orange-600 text-lg"></i>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-orange-600">Total Pajak</p>
            <p class="text-2xl font-bold text-slate-900 mt-0.5">{{ taxes.length }}</p>
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
          <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center">
            <i class="pi pi-ban text-red-400 text-lg"></i>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-red-500">Non-Aktif</p>
            <p class="text-2xl font-bold text-slate-400 mt-0.5">{{ inactiveCount }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Outlet Selector (when no outlet selected) -->
    <div v-if="!selectedOutletId" class="bg-white rounded-2xl border border-slate-200 p-12 flex flex-col items-center justify-center text-center">
      <i class="pi pi-building text-4xl text-slate-200 mb-3"></i>
      <p class="text-slate-600 font-semibold">Pilih outlet terlebih dahulu</p>
      <p class="text-slate-400 text-sm mt-1">Pilih outlet untuk melihat daftar pajak yang tersedia.</p>
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
          placeholder="Pilih outlet" class="w-44" @change="fetchTaxes" />
        <Button label="Tambah Pajak" icon="pi pi-plus" size="small" @click="openAddDialog" :disabled="!selectedOutletId" />
      </div>
      <DataTable :value="taxes" stripedRows size="small" class="text-sm">
        <Column header="#" style="width: 50px">
          <template #body="{ index }">
            <span class="text-slate-400 text-xs font-mono">{{ index + 1 }}</span>
          </template>
        </Column>
        <template #empty>
          <div class="flex flex-col items-center justify-center py-16 text-center">
            <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
              <i class="pi pi-shield text-2xl text-slate-300"></i>
            </div>
            <p class="text-slate-500 font-medium">Belum ada pajak</p>
            <p class="text-slate-400 text-xs mt-1">Tambahkan pajak untuk outlet ini</p>
          </div>
        </template>
        <Column field="name" header="Nama Pajak" sortable>
          <template #body="{ data }">
            <span class="font-medium text-slate-900">{{ data.name }}</span>
          </template>
        </Column>
        <Column header="Tarif" sortable>
          <template #body="{ data }">
            <div class="flex items-center gap-2">
              <span class="text-lg font-bold text-slate-900">{{ data.rate }}%</span>
              <div class="w-24 bg-slate-100 rounded-full h-2 overflow-hidden">
                <div class="h-full rounded-full bg-gradient-to-r from-orange-400 to-orange-500"
                  :style="{ width: Math.min(data.rate, 100) + '%' }"></div>
              </div>
            </div>
          </template>
        </Column>
        <Column header="Urutan" style="width: 80px" sortable>
          <template #body="{ data }">
            <span class="text-xs font-mono text-slate-500">#{{ data.sort_order }}</span>
          </template>
        </Column>
        <Column header="Status" sortable>
          <template #body="{ data }">
            <Tag :value="data.is_active ? 'Aktif' : 'Non-Aktif'"
              :severity="data.is_active ? 'success' : 'danger'" rounded />
          </template>
        </Column>
        <Column header="Aksi" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" text rounded size="small"
                v-tooltip.top="'Edit'" @click="openEditDialog(data)" />
              <Button :icon="data.is_active ? 'pi pi-ban' : 'pi pi-check-circle'"
                text rounded size="small"
                :class="data.is_active ? 'text-amber-600' : 'text-teal-600'"
                v-tooltip.top="data.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                @click="toggleActive(data)" />
              <Button icon="pi pi-trash" text rounded severity="danger" size="small"
                v-tooltip.top="'Hapus'" @click="confirmDelete(data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Add/Edit Dialog -->
    <Dialog v-model:visible="showDialog" :header="editing ? 'Edit Pajak' : 'Tambah Pajak'" modal class="w-md">
      <form @submit.prevent="saveTax" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Nama Pajak <span class="text-red-400">*</span></label>
          <InputText v-model="form.name" class="w-full" placeholder="Contoh: PPN 11%" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Tarif (%) <span class="text-red-400">*</span></label>
          <div class="flex items-center gap-2">
            <InputNumber v-model="form.rate" class="flex-1" :min="0" :max="100"
              :minFractionDigits="1" :maxFractionDigits="1" suffix="%" required />
            <span class="text-xs text-slate-400">Maks 100%</span>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Urutan Perhitungan</label>
          <div class="flex items-center gap-3">
            <InputNumber v-model="form.sort_order" class="w-24" :min="0" :max="255"
              placeholder="0" />
            <span class="text-xs text-slate-400">Semakin kecil, semakin pertama dihitung</span>
          </div>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Batal" severity="secondary" @click="showDialog = false" />
          <Button type="submit" :label="editing ? 'Simpan' : 'Tambah'" :loading="saving" />
        </div>
      </form>
    </Dialog>

    <!-- Delete Confirmation -->
    <Dialog v-model:visible="showDeleteDialog" header="Hapus Pajak" modal class="w-sm">
      <div class="space-y-3">
        <div class="flex items-center gap-3 p-3 rounded-xl bg-red-50 border border-red-100">
          <i class="pi pi-exclamation-triangle text-red-500 text-xl"></i>
          <p class="text-sm text-red-700">
            Yakin ingin menghapus pajak <strong>{{ deletingTax?.name }}</strong>?
          </p>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Batal" severity="secondary" @click="showDeleteDialog = false" />
          <Button label="Hapus" severity="danger" :loading="deleting" @click="deleteTax" />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import client from '../../api/client'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Tooltip from 'primevue/tooltip'

const taxes = ref([])
const outlets = ref([])
const selectedOutletId = ref(null)
const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)
const showDialog = ref(false)
const showDeleteDialog = ref(false)
const editing = ref(false)
const deletingTax = ref(null)

const form = ref({ name: '', rate: 11, sort_order: 0 })

const activeCount = computed(() => taxes.value.filter((t) => t.is_active).length)
const inactiveCount = computed(() => taxes.value.filter((t) => !t.is_active).length)

async function fetchOutlets() {
  try {
    const { data } = await client.get('/outlets')
    outlets.value = data.data
    if (data.data.length > 0 && !selectedOutletId.value) {
      selectedOutletId.value = data.data[0].id
      fetchTaxes()
    }
  } catch (_) {}
}

async function fetchTaxes() {
  if (!selectedOutletId.value) return
  loading.value = true
  try {
    const { data } = await client.get('/taxes', { params: { outlet_id: selectedOutletId.value } })
    taxes.value = data.data
  } catch (_) {}
  finally { loading.value = false }
}

function openAddDialog() {
  editing.value = false
  form.value = { name: '', rate: 11, sort_order: 0 }
  showDialog.value = true
}

function openEditDialog(tax) {
  editing.value = true
  form.value = { name: tax.name, rate: tax.rate, sort_order: tax.sort_order ?? 0, id: tax.id }
  showDialog.value = true
}

async function saveTax() {
  saving.value = true
  try {
    const payload = {
      outlet_id: selectedOutletId.value,
      name: form.value.name,
      rate: form.value.rate,
      sort_order: form.value.sort_order ?? 0,
    }
    if (editing.value) {
      await client.put(`/taxes/${form.value.id}`, payload)
    } else {
      await client.post('/taxes', payload)
    }
    showDialog.value = false
    fetchTaxes()
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menyimpan pajak')
  } finally {
    saving.value = false
  }
}

async function toggleActive(tax) {
  try {
    await client.put(`/taxes/${tax.id}`, { is_active: !tax.is_active })
    fetchTaxes()
  } catch (_) {}
}

function confirmDelete(tax) {
  deletingTax.value = tax
  showDeleteDialog.value = true
}

async function deleteTax() {
  deleting.value = true
  try {
    await client.delete(`/taxes/${deletingTax.value.id}`)
    showDeleteDialog.value = false
    fetchTaxes()
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menghapus pajak')
  } finally {
    deleting.value = false
  }
}

onMounted(fetchOutlets)
</script>
