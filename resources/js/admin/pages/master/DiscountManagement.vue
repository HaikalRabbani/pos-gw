<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Manajemen Diskon</h1>
        <p class="text-sm text-slate-500 mt-1">Kelola diskon dan promosi</p>
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
            <i class="pi pi-tag text-amber-600 text-lg"></i>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-amber-600">Beli Dapat</p>
            <p class="text-2xl font-bold text-slate-900 mt-0.5">{{ buyXGetYCount }}</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-violet-100 flex items-center justify-center">
            <i class="pi pi-target text-violet-600 text-lg"></i>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-violet-600">Terjadwal</p>
            <p class="text-2xl font-bold text-slate-900 mt-0.5">{{ scheduledCount }}</p>
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
      <div class="p-3 border-b border-slate-100 flex items-center justify-end gap-2">
        <Select v-model="selectedOutletId" :options="outlets" optionLabel="name" optionValue="id"
          placeholder="Pilih outlet" class="w-44" @change="fetchDiscounts" />
        <Button v-if="perm.can('manageDiscounts')" label="Tambah Diskon" icon="pi pi-plus" size="small" @click="openAddDialog" :disabled="!selectedOutletId" />
      </div>
      <DataTable :value="discounts" stripedRows size="small" class="text-sm">
        <Column header="No." style="width: 50px">
          <template #body="{ index }">
            <span class="text-slate-400 text-xs font-mono">{{ index + 1 }}</span>
          </template>
        </Column>
        <template #empty>
          <div class="flex flex-col items-center justify-center py-16 text-center">
            <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
              <i class="pi pi-percentage text-2xl text-slate-300"></i>
            </div>
            <p class="text-slate-500 font-medium">Belum ada diskon</p>
            <p class="text-slate-400 text-xs mt-1">Tambahkan diskon untuk outlet ini</p>
          </div>
        </template>
        <Column field="name" header="Nama Diskon" sortable>
          <template #body="{ data }">
            <span class="font-medium text-slate-900">{{ data.name }}</span>
          </template>
        </Column>
        <Column header="Tipe" sortable>
          <template #body="{ data }">
            <Tag :value="discountTypeLabel(data)" rounded
              :severity="discountTypeSeverity(data)" />
          </template>
        </Column>
        <Column header="Nilai" sortable>
          <template #body="{ data }">
            <span class="font-semibold text-slate-900">
              <template v-if="data.buy_x && data.buy_y">
                Beli {{ data.buy_x }} Gratis {{ data.buy_y }}
              </template>
              <template v-else>
                {{ data.type === 'percent' ? data.value + '%' : 'Rp ' + (data.value / 100).toLocaleString('id-ID') }}
              </template>
            </span>
          </template>
        </Column>
        <Column header="Sasaran">
          <template #body="{ data }">
            <div class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-md font-medium"
              :class="targetBadgeClass(data.target_type)">
              {{ targetLabel(data) }}
            </div>
          </template>
        </Column>
        <Column header="Masa Aktif">
          <template #body="{ data }">
            <div v-if="data.start_date || data.end_date" class="text-xs text-slate-500">
              <div v-if="data.start_date">{{ formatDate(data.start_date) }}</div>
              <div v-if="data.end_date" class="text-red-400">→ {{ formatDate(data.end_date) }}</div>
            </div>
            <span v-else class="text-xs text-slate-300">—</span>
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
            <div class="flex gap-3">
              <Button v-if="perm.can('manageDiscounts')" icon="pi pi-pencil" text rounded size="small"
                v-tooltip.top="'Edit'" @click="openEditDialog(data)" />
              <Button v-if="perm.can('manageDiscounts')" :icon="data.is_active ? 'pi pi-ban' : 'pi pi-check-circle'"
                text rounded size="small"
                :class="data.is_active ? 'text-amber-600' : 'text-teal-600'"
                v-tooltip.top="data.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                @click="toggleActive(data)" />
              <Button v-if="perm.can('manageDiscounts')" icon="pi pi-trash" text rounded severity="danger" size="small"
                v-tooltip.top="'Hapus'" @click="confirmDelete(data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Add/Edit Dialog -->
    <Dialog v-model:visible="showDialog" :header="editing ? 'Edit Diskon' : 'Tambah Diskon'" modal class="w-xl">
      <form @submit.prevent="saveDiscount" class="space-y-4">
        <!-- Nama -->
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Nama Diskon <span class="text-red-400">*</span></label>
          <InputText v-model="form.name" class="w-full" placeholder="Contoh: Diskon Akhir Pekan" required />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <!-- Tipe Diskon -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Tipe <span class="text-red-400">*</span></label>
            <Select v-model="form.type" :options="typeOptions" class="w-full" required />
          </div>
          <!-- Nilai -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
              Nilai <span class="text-red-400">*</span>
              <span v-if="form.type === 'percent'" class="text-xs text-slate-400 ml-1">(dalam %)</span>
              <span v-else class="text-xs text-slate-400 ml-1">(dalam Rupiah)</span>
            </label>
            <InputNumber v-model="form.value" class="w-full" :min="0"
              :suffix="form.type === 'percent' ? '%' : ''"
              :prefix="form.type !== 'percent' ? 'Rp ' : ''"
              :minFractionDigits="0" :maxFractionDigits="0" required
              :disabled="form.buy_x > 0" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <!-- Sasaran -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Sasaran Diskon</label>
            <Select v-model="form.target_type" :options="targetTypeOptions" class="w-full"
              @change="form.target_id = null" />
          </div>
          <!-- Target Produk/Kategori -->
          <div v-if="form.target_type === 'product'">
            <label class="block text-sm font-medium text-slate-700 mb-1">Produk <span class="text-red-400">*</span></label>
            <Select v-model="form.target_id" :options="products" optionLabel="name" optionValue="id"
              placeholder="Pilih produk" class="w-full" filter :showClear="true" />
          </div>
          <div v-else-if="form.target_type === 'category'">
            <label class="block text-sm font-medium text-slate-700 mb-1">Kategori <span class="text-red-400">*</span></label>
            <Select v-model="form.target_id" :options="categories" optionLabel="name" optionValue="id"
              placeholder="Pilih kategori" class="w-full" filter :showClear="true" />
          </div>
        </div>

        <!-- Buy X Get Y -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Beli</label>
            <InputNumber v-model="form.buy_x" class="w-full" :min="0" placeholder="0" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Gratis</label>
            <InputNumber v-model="form.buy_y" class="w-full" :min="0" placeholder="0" />
          </div>
        </div>

        <!-- Min Purchase & Max Discount -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Min. Pembelian (Rp)</label>
            <InputNumber v-model="form.min_purchase" class="w-full" :min="0" placeholder="0 (tanpa minimal)" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Maks. Potongan (Rp)</label>
            <InputNumber v-model="form.max_discount" class="w-full" :min="0" placeholder="0 (tanpa batas)" />
          </div>
        </div>

        <!-- Masa Aktif -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Mulai</label>
            <Calendar v-model="form.start_date" showTime hourFormat="24" class="w-full"
              placeholder="Pilih tanggal mulai" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Selesai</label>
            <Calendar v-model="form.end_date" showTime hourFormat="24" class="w-full"
              placeholder="Pilih tanggal selesai" />
          </div>
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
import { ref, computed, onMounted, watch } from 'vue'
import { usePermission } from '../../utils/usePermission'
import client from '../../api/client'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Calendar from 'primevue/calendar'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Tooltip from 'primevue/tooltip'

const perm = usePermission()

const discounts = ref([])
const outlets = ref([])
const products = ref([])
const categories = ref([])
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

const targetTypeOptions = [
  { label: 'Semua Transaksi', value: 'transaction' },
  { label: 'Produk Tertentu', value: 'product' },
  { label: 'Kategori Tertentu', value: 'category' },
]

const form = ref({
  name: '',
  type: 'percent',
  value: 0,
  target_type: 'transaction',
  target_id: null,
  min_purchase: null,
  max_discount: null,
  buy_x: null,
  buy_y: null,
  start_date: null,
  end_date: null,
})

const activeCount = computed(() => discounts.value.filter((d) => d.is_active).length)
const buyXGetYCount = computed(() => discounts.value.filter((d) => d.buy_x && d.buy_y).length)
const scheduledCount = computed(() => discounts.value.filter((d) => d.start_date || d.end_date).length)

function discountTypeLabel(d) {
  if (d.buy_x && d.buy_y) return `Beli ${d.buy_x} Gratis ${d.buy_y}`
  return d.type === 'percent' ? 'Persen' : 'Nominal'
}

function discountTypeSeverity(d) {
  if (d.buy_x && d.buy_y) return 'contrast'
  return d.type === 'percent' ? 'info' : 'warn'
}

function targetLabel(d) {
  if (d.target_type === 'product' && d.target_product) return `Produk: ${d.target_product.name}`
  if (d.target_type === 'category' && d.target_category) return `Kategori: ${d.target_category.name}`
  return 'Semua Transaksi'
}

function targetBadgeClass(type) {
  const map = {
    product: 'bg-blue-100 text-blue-700',
    category: 'bg-violet-100 text-violet-700',
    transaction: 'bg-slate-100 text-slate-600',
  }
  return map[type] || 'bg-slate-100 text-slate-600'
}

function formatDate(date) {
  if (!date) return ''
  const d = new Date(date)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

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

async function fetchMasterData() {
  if (!selectedOutletId.value) return
  try {
    const [prodRes, catRes] = await Promise.all([
      client.get('/products', { params: { outlet_id: selectedOutletId.value } }),
      client.get('/categories', { params: { outlet_id: selectedOutletId.value } }),
    ])
    products.value = prodRes.data.data
    categories.value = catRes.data.data
  } catch (_) {}
}

async function fetchDiscounts() {
  if (!selectedOutletId.value) return
  loading.value = true
  try {
    const { data } = await client.get('/discounts', { params: { outlet_id: selectedOutletId.value } })
    discounts.value = data.data
    await fetchMasterData()
  } catch (_) {}
  finally { loading.value = false }
}

function openAddDialog() {
  editing.value = false
  form.value = {
    name: '',
    type: 'percent',
    value: 0,
    target_type: 'transaction',
    target_id: null,
    min_purchase: null,
    max_discount: null,
    buy_x: null,
    buy_y: null,
    start_date: null,
    end_date: null,
  }
  showDialog.value = true
}

function openEditDialog(discount) {
  editing.value = true
  form.value = {
    id: discount.id,
    name: discount.name,
    type: discount.type,
    value: discount.value,
    target_type: discount.target_type || 'transaction',
    target_id: discount.target_id,
    min_purchase: discount.min_purchase,
    max_discount: discount.max_discount,
    buy_x: discount.buy_x,
    buy_y: discount.buy_y,
    start_date: discount.start_date ? new Date(discount.start_date) : null,
    end_date: discount.end_date ? new Date(discount.end_date) : null,
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
      target_type: form.value.target_type || 'transaction',
      target_id: form.value.target_type !== 'transaction' ? form.value.target_id : null,
      min_purchase: form.value.min_purchase || null,
      max_discount: form.value.max_discount || null,
      buy_x: form.value.buy_x || null,
      buy_y: form.value.buy_y || null,
      start_date: form.value.start_date || null,
      end_date: form.value.end_date || null,
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
