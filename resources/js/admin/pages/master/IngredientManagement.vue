<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Bahan & Add-on</h1>
        <p class="text-sm text-slate-500 mt-1">Kelola bahan baku dan add-on untuk kustomisasi produk</p>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white rounded-2xl border border-slate-200 p-4">
        <p class="text-sm text-slate-500">Total Bahan</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ ingredients.length }}</p>
      </div>
      <div class="bg-white rounded-2xl border border-slate-200 p-4">
        <p class="text-sm text-slate-500">Aktif</p>
        <p class="text-2xl font-bold text-teal-600 mt-1">{{ activeCount }}</p>
      </div>
      <div class="bg-white rounded-2xl border border-slate-200 p-4">
        <p class="text-sm text-slate-500">Non-Aktif</p>
        <p class="text-2xl font-bold text-slate-400 mt-1">{{ inactiveCount }}</p>
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
      <div class="p-3 border-b border-slate-100 flex items-center gap-3">
        <span class="flex-1">
          <InputText v-model="search" placeholder="Cari bahan..." class="w-full" />
        </span>
        <Button v-if="perm.can('manageCategories')" label="Tambah Bahan" icon="pi pi-plus" size="small"
          @click="openAddDialog" />
      </div>
      <DataTable :value="filteredIngredients" paginator :rows="rowsPerPage" stripedRows size="small"
        paginatorTemplate="CurrentPageReport PrevPageLink NextPageLink"
        currentPageReportTemplate="Halaman {currentPage} dari {totalPages}"
        class="text-sm">
        <Column header="No." style="width: 50px">
          <template #body="{ index }">
            <span class="text-slate-400 text-xs font-mono">{{ index + 1 }}</span>
          </template>
        </Column>
        <template #empty>
          <div class="flex flex-col items-center justify-center py-16 text-center">
            <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
              <i class="pi pi-list text-2xl text-slate-300"></i>
            </div>
            <p class="text-slate-500 font-medium">Belum ada bahan</p>
            <p class="text-slate-400 text-xs mt-1">Tambahkan bahan/add-on untuk kustomisasi produk</p>
          </div>
        </template>
        <Column field="name" header="Nama Bahan" sortable>
          <template #body="{ data }">
            <div class="flex items-center gap-2">
              <div class="w-7 h-7 rounded-lg bg-violet-100 flex items-center justify-center text-xs font-bold text-violet-700">
                <i class="pi pi-list text-[10px]"></i>
              </div>
              <span class="font-medium text-slate-800">{{ data.name }}</span>
            </div>
          </template>
        </Column>
        <!-- Stock Column (ingredient mode) -->
        <Column v-if="outletStockMode === 'ingredient'" header="Stok" style="width: 120px" sortable>
          <template #body="{ data }">
            <div class="flex items-center gap-2">
              <InputNumber v-model="data.stock" :min="0" class="w-20" size="small" @blur="updateStock(data)" />
            </div>
          </template>
        </Column>
        <Column header="Status" sortable>
          <template #body="{ data }">
            <Tag :value="data.is_active ? 'Aktif' : 'Non-Aktif'"
              :severity="data.is_active ? 'success' : 'danger'" rounded />
          </template>
        </Column>
        <Column header="Dibuat" style="width: 130px">
          <template #body="{ data }">
            <span class="text-xs text-slate-400">{{ formatDate(data.created_at) }}</span>
          </template>
        </Column>
        <Column header="Aksi" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1.5">
              <Button icon="pi pi-pencil" text rounded severity="secondary" size="small"
                v-tooltip.top="'Edit'"
                @click="openEditDialog(data)" />
              <Button icon="pi pi-check-circle" text rounded severity="secondary" size="small"
                v-tooltip.top="data.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                @click="toggleActive(data)" />
              <Button icon="pi pi-trash" text severity="danger" rounded size="small"
                v-tooltip.top="'Hapus'"
                @click="confirmDelete(data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Add/Edit Dialog -->
    <Dialog v-model:visible="showDialog" :header="editing ? 'Edit Bahan' : 'Tambah Bahan'" modal class="w-sm">
      <form @submit.prevent="saveIngredient" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Nama Bahan <span class="text-red-400">*</span></label>
          <InputText v-model="form.name" class="w-full" placeholder="Misal: Telur, Keju, Sosis" required />
        </div>
        <div v-if="outletStockMode === 'ingredient'">
          <label class="block text-sm font-medium text-slate-700 mb-1">Stok Awal</label>
          <InputNumber v-model="form.stock" :min="0" class="w-full" placeholder="0" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
          <Select v-model="form.is_active" :options="statusOptions" optionLabel="label" optionValue="value"
            class="w-full" />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Batal" severity="secondary" @click="showDialog = false" />
          <Button type="submit" :label="editing ? 'Simpan' : 'Tambah'" :loading="saving" />
        </div>
      </form>
    </Dialog>

    <!-- Delete Confirmation -->
    <Dialog v-model:visible="showDeleteDialog" header="Hapus Bahan" modal class="w-sm">
      <div class="space-y-3">
        <div class="flex items-center gap-3 p-3 rounded-xl bg-red-50 border border-red-100">
          <i class="pi pi-exclamation-triangle text-red-500 text-xl"></i>
          <p class="text-sm text-red-700">
            Yakin ingin menghapus <strong>{{ deletingIngredient?.name }}</strong>?
          </p>
        </div>
        <p class="text-xs text-slate-500">Produk yang menggunakan bahan ini tidak akan terhapus, hanya bahan-nya yang dihapus dari pivot.</p>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Batal" severity="secondary" @click="showDeleteDialog = false" />
          <Button label="Hapus" severity="danger" :loading="deleting" @click="executeDelete" />
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
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Tooltip from 'primevue/tooltip'

const perm = usePermission()
const toast = useToastStore()

const loading = ref(true)
const ingredients = ref([])
const search = ref('')
const rowsPerPage = ref(10)
const showDialog = ref(false)
const showDeleteDialog = ref(false)
const editing = ref(false)
const saving = ref(false)
const deleting = ref(false)
const deletingIngredient = ref(null)
const outletStockMode = ref('product')

const statusOptions = [
  { label: 'Aktif', value: true },
  { label: 'Non-Aktif', value: false },
]

const form = ref({ name: '', stock: 0, is_active: true })

const activeCount = computed(() => ingredients.value.filter((i) => i.is_active).length)
const inactiveCount = computed(() => ingredients.value.filter((i) => !i.is_active).length)

const filteredIngredients = computed(() => {
  if (!search.value) return ingredients.value
  const q = search.value.toLowerCase()
  return ingredients.value.filter((i) => i.name.toLowerCase().includes(q))
})

async function fetchData() {
  loading.value = true
  try {
    const { data: outletRes } = await client.get('/outlets')
    const outletId = outletRes.data[0]?.id
    if (!outletId) return
    const [outletRes2, ingRes] = await Promise.all([
      client.get(`/outlets/${outletId}`),
      client.get('/ingredients', { params: { outlet_id: outletId } }),
    ])
    outletStockMode.value = outletRes2.data.data?.stock_mode || 'product'
    ingredients.value = ingRes.data.data
  } catch (_) {
  } finally {
    loading.value = false
  }
}

function openAddDialog() {
  editing.value = false
  form.value = { name: '', stock: 0, is_active: true }
  showDialog.value = true
}

function openEditDialog(ing) {
  editing.value = true
  form.value = {
    id: ing.id,
    name: ing.name,
    stock: ing.stock ?? 0,
    is_active: ing.is_active,
  }
  showDialog.value = true
}

async function saveIngredient() {
  saving.value = true
  try {
    const { data: outletRes } = await client.get('/outlets')
    const outletId = outletRes.data[0]?.id
    const payload = {
      name: form.value.name,
      is_active: form.value.is_active,
    }
    if (outletStockMode.value === 'ingredient') {
      payload.stock = form.value.stock ?? 0
    }
    if (editing.value && form.value.id) {
      await client.put(`/ingredients/${form.value.id}`, payload)
    } else {
      await client.post('/ingredients', { ...payload, outlet_id: outletId })
    }
    showDialog.value = false
    fetchData()
  } catch (e) {
    toast.error('Gagal Simpan', e.response?.data?.message || 'Gagal menyimpan bahan')
  } finally {
    saving.value = false
  }
}

async function toggleActive(ing) {
  try {
    await client.put(`/ingredients/${ing.id}`, { is_active: !ing.is_active })
    fetchData()
  } catch (_) {}
}

async function updateStock(ing) {
  try {
    await client.put(`/ingredients/${ing.id}`, { stock: ing.stock ?? 0 })
  } catch (_) {}
}

function confirmDelete(ing) {
  deletingIngredient.value = ing
  showDeleteDialog.value = true
}

async function executeDelete() {
  deleting.value = true
  try {
    await client.delete(`/ingredients/${deletingIngredient.value.id}`)
    showDeleteDialog.value = false
    fetchData()
  } catch (e) {
    toast.error('Gagal Hapus', e.response?.data?.message || 'Gagal menghapus bahan')
  } finally {
    deleting.value = false
  }
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

onMounted(fetchData)
</script>
