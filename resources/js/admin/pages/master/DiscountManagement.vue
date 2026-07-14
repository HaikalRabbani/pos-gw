<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Manajemen Diskon</h1>
        <p class="text-sm text-slate-500 mt-1">Kelola diskon dan promosi</p>
      </div>
      <div class="flex items-center gap-2">
        <Select v-model="selectedOutletId" :options="outlets" optionLabel="name" optionValue="id"
          placeholder="Pilih outlet" class="w-48" @change="fetchDiscounts" />
        <Button label="Tambah Diskon" icon="pi pi-plus" @click="openAddDialog" :disabled="!selectedOutletId" />
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
            <i class="pi pi-percentage text-blue-600 text-lg"></i>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-blue-600">Total Diskon</p>
            <p class="text-2xl font-bold text-slate-900 mt-0.5">{{ discounts.length }}</p>
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
          <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center">
            <i class="pi pi-percentage text-amber-600 text-lg"></i>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-amber-600">Persen</p>
            <p class="text-2xl font-bold text-slate-900 mt-0.5">{{ percentCount }}</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-violet-100 flex items-center justify-center">
            <i class="pi pi-money-bill text-violet-600 text-lg"></i>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-violet-600">Nominal</p>
            <p class="text-2xl font-bold text-slate-900 mt-0.5">{{ nominalCount }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Outlet Selector (when no outlet selected) -->
    <div v-if="!selectedOutletId" class="bg-white rounded-2xl border border-slate-200 p-12 flex flex-col items-center justify-center text-center">
      <i class="pi pi-building text-4xl text-slate-200 mb-3"></i>
      <p class="text-slate-600 font-semibold">Pilih outlet terlebih dahulu</p>
      <p class="text-slate-400 text-sm mt-1">Pilih outlet untuk melihat daftar diskon yang tersedia.</p>
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
      <DataTable :value="discounts" class="text-sm">
        <template #empty>
          <div class="flex flex-col items-center justify-center py-12 text-center">
            <i class="pi pi-percentage text-4xl text-slate-200 mb-3"></i>
            <p class="text-slate-400 text-sm">Belum ada diskon untuk outlet ini.</p>
          </div>
        </template>
        <Column field="name" header="Nama Diskon" sortable>
          <template #body="{ data }">
            <span class="font-medium text-slate-900">{{ data.name }}</span>
          </template>
        </Column>
        <Column header="Tipe" sortable>
          <template #body="{ data }">
            <Tag :value="data.type === 'percent' ? 'Persen' : 'Nominal'"
              :severity="data.type === 'percent' ? 'info' : 'warn'" rounded />
          </template>
        </Column>
        <Column field="value" header="Nilai" sortable>
          <template #body="{ data }">
            <span class="font-semibold text-slate-900">
              {{ data.type === 'percent' ? data.value + '%' : 'Rp ' + (data.value / 100).toLocaleString('id-ID') }}
            </span>
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
    <Dialog v-model:visible="showDialog" :header="editing ? 'Edit Diskon' : 'Tambah Diskon'" modal class="w-md">
      <form @submit.prevent="saveDiscount" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Nama Diskon <span class="text-red-400">*</span></label>
          <InputText v-model="form.name" class="w-full" placeholder="Contoh: Diskon Akhir Pekan" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Tipe <span class="text-red-400">*</span></label>
          <Select v-model="form.type" :options="typeOptions" class="w-full" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">
            Nilai <span class="text-red-400">*</span>
            <span v-if="form.type === 'percent'" class="text-xs text-slate-400 ml-1">(dalam %)</span>
            <span v-else class="text-xs text-slate-400 ml-1">(dalam Rupiah)</span>
          </label>
          <InputNumber v-model="form.value" class="w-full" :min="0"
            :suffix="form.type === 'percent' ? '%' : ''"
            :prefix="form.type !== 'percent' ? 'Rp ' : ''"
            :minFractionDigits="0" :maxFractionDigits="0" required />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Batal" severity="secondary" @click="showDialog = false" />
          <Button type="submit" :label="editing ? 'Simpan' : 'Tambah'" :loading="saving" />
        </div>
      </form>
    </Dialog>

    <!-- Delete Confirmation -->
    <Dialog v-model:visible="showDeleteDialog" header="Hapus Diskon" modal class="w-sm">
      <div class="space-y-3">
        <div class="flex items-center gap-3 p-3 rounded-xl bg-red-50 border border-red-100">
          <i class="pi pi-exclamation-triangle text-red-500 text-xl"></i>
          <p class="text-sm text-red-700">
            Yakin ingin menghapus diskon <strong>{{ deletingDiscount?.name }}</strong>?
          </p>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Batal" severity="secondary" @click="showDeleteDialog = false" />
          <Button label="Hapus" severity="danger" :loading="deleting" @click="deleteDiscount" />
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

const discounts = ref([])
const outlets = ref([])
const selectedOutletId = ref(null)
const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)
const showDialog = ref(false)
const showDeleteDialog = ref(false)
const editing = ref(false)
const deletingDiscount = ref(null)

const typeOptions = [
  { label: 'Persen (%)', value: 'percent' },
  { label: 'Nominal (Rp)', value: 'nominal' },
]

const form = ref({ name: '', type: 'percent', value: 0 })

const activeCount = computed(() => discounts.value.filter((d) => d.is_active).length)
const percentCount = computed(() => discounts.value.filter((d) => d.type === 'percent').length)
const nominalCount = computed(() => discounts.value.filter((d) => d.type === 'nominal').length)

async function fetchOutlets() {
  try {
    const { data } = await client.get('/outlets')
    outlets.value = data.data
    if (data.data.length > 0 && !selectedOutletId.value) {
      selectedOutletId.value = data.data[0].id
      fetchDiscounts()
    }
  } catch (_) {}
}

async function fetchDiscounts() {
  if (!selectedOutletId.value) return
  loading.value = true
  try {
    const { data } = await client.get('/discounts', { params: { outlet_id: selectedOutletId.value } })
    discounts.value = data.data
  } catch (_) {}
  finally { loading.value = false }
}

function openAddDialog() {
  editing.value = false
  form.value = { name: '', type: 'percent', value: 0 }
  showDialog.value = true
}

function openEditDialog(discount) {
  editing.value = true
  form.value = {
    name: discount.name,
    type: discount.type,
    value: discount.value,
    id: discount.id,
  }
  showDialog.value = true
}

async function saveDiscount() {
  saving.value = true
  try {
    const payload = {
      outlet_id: selectedOutletId.value,
      name: form.value.name,
      type: form.value.type,
      value: form.value.value,
    }
    if (editing.value) {
      await client.put(`/discounts/${form.value.id}`, payload)
    } else {
      await client.post('/discounts', payload)
    }
    showDialog.value = false
    fetchDiscounts()
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menyimpan diskon')
  } finally {
    saving.value = false
  }
}

async function toggleActive(discount) {
  try {
    await client.put(`/discounts/${discount.id}`, { is_active: !discount.is_active })
    fetchDiscounts()
  } catch (_) {}
}

function confirmDelete(discount) {
  deletingDiscount.value = discount
  showDeleteDialog.value = true
}

async function deleteDiscount() {
  deleting.value = true
  try {
    await client.delete(`/discounts/${deletingDiscount.value.id}`)
    showDeleteDialog.value = false
    fetchDiscounts()
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menghapus diskon')
  } finally {
    deleting.value = false
  }
}

onMounted(fetchOutlets)
</script>
