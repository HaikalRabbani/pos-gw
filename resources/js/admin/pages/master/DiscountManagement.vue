<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Manajemen Diskon</h1>
        <p class="text-sm text-slate-500 mt-1">Kelola diskon dan promosi</p>
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
      <div class="p-3 border-b border-slate-100 flex flex-wrap items-center justify-end gap-2">
        <Select v-if="outlets.length > 1" v-model="selectedOutletId" :options="outlets" optionLabel="name" optionValue="id"
          placeholder="Pilih outlet" class="w-44" @change="fetchDiscounts" />
        <Button v-if="perm.can('manageDiscounts')" label="Tambah Diskon" icon="pi pi-plus" size="small" @click="openAddDialog" :disabled="!selectedOutletId" />
      </div>
      <div class="overflow-x-auto">
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
                {{ data.type === 'percent' ? data.value + '%' : formatRupiah(data.value) }}
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
    </div>

    <!-- Add/Edit Dialog -->
    <Dialog v-model:visible="showDialog" :header="editing ? 'Edit Diskon' : 'Tambah Diskon'" modal class="w-2xl">
      <form @submit.prevent="saveDiscount" class="space-y-6">

        <!-- ═══ SECTION 1: Informasi Dasar ═══ -->
        <div>
          <div class="flex items-center gap-2 mb-3">
            <div class="w-6 h-6 rounded-lg bg-teal-100 flex items-center justify-center">
              <i class="pi pi-tag text-xs text-teal-700"></i>
            </div>
            <h3 class="text-sm font-semibold text-slate-800">Informasi Dasar</h3>
          </div>
          <div class="bg-slate-50 rounded-xl p-4 space-y-3">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">
                Nama Diskon <span class="text-red-400">*</span>
              </label>
              <InputText v-model="form.name" class="w-full" placeholder="Contoh: Diskon Akhir Pekan" required />
            </div>
          </div>
        </div>

        <!-- ═══ SECTION 2: Jenis Diskon ═══ -->
        <div>
          <div class="flex items-center gap-2 mb-3">
            <div class="w-6 h-6 rounded-lg bg-orange-100 flex items-center justify-center">
              <i class="pi pi-percentage text-xs text-orange-700"></i>
            </div>
            <h3 class="text-sm font-semibold text-slate-800">Jenis Diskon</h3>
          </div>

          <!-- Card Selector -->
          <div class="grid grid-cols-3 gap-3 mb-3">
            <button type="button"
              v-for="opt in typeOptions" :key="opt.value"
              class="relative flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition-all duration-200 text-center"
              :class="form.discountType === opt.value
                ? 'border-teal-500 bg-teal-50 shadow-sm ring-1 ring-teal-200'
                : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-sm'"
              @click="selectDiscountType(opt.value)">
              <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg transition-colors duration-200"
                :class="form.discountType === opt.value ? 'bg-teal-200 text-teal-700' : 'bg-slate-100 text-slate-500'">
                <i :class="opt.icon"></i>
              </div>
              <div>
                <p class="text-sm font-semibold" :class="form.discountType === opt.value ? 'text-teal-800' : 'text-slate-700'">
                  {{ opt.label }}
                </p>
                <p class="text-[10px] text-slate-400 mt-0.5 leading-tight">{{ opt.desc }}</p>
              </div>
              <div v-if="form.discountType === opt.value"
                class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-teal-500 flex items-center justify-center">
                <i class="pi pi-check text-white text-[8px]"></i>
              </div>
            </button>
          </div>

          <!-- Nilai fields (Persen / Nominal) -->
          <div v-if="form.discountType === 'percent' || form.discountType === 'nominal'"
            class="bg-slate-50 rounded-xl p-4 space-y-3 transition-all duration-200">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">
                Nilai Diskon <span class="text-red-400">*</span>
                <span v-if="form.discountType === 'percent'" class="text-xs text-slate-400 ml-1">(dalam %)</span>
                <span v-else class="text-xs text-slate-400 ml-1">(dalam Rupiah)</span>
              </label>
              <InputNumber v-model="form.value" class="w-full" :min="0"
                :suffix="form.discountType === 'percent' ? '%' : ''"
                :prefix="form.discountType !== 'percent' ? 'Rp ' : ''"
                :minFractionDigits="0" :maxFractionDigits="0" required
                :placeholder="form.discountType === 'percent' ? '10' : '5000'" />
              <p v-if="form.discountType === 'percent'" class="text-xs text-slate-400 mt-1">Persentase potongan dari total belanja.</p>
              <p v-else class="text-xs text-slate-400 mt-1">Potongan harga tetap dalam Rupiah.</p>
            </div>
          </div>

          <!-- Nilai fields (BOGO) -->
          <div v-if="form.discountType === 'bogo'"
            class="bg-slate-50 rounded-xl p-4 space-y-3 transition-all duration-200">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Beli <span class="text-red-400">*</span></label>
                <InputNumber v-model="form.buy_x" class="w-full" :min="1" placeholder="2" required />
                <p class="text-xs text-slate-400 mt-1">Jumlah item yang harus dibeli.</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Gratis <span class="text-red-400">*</span></label>
                <InputNumber v-model="form.buy_y" class="w-full" :min="1" placeholder="1" required />
                <p class="text-xs text-slate-400 mt-1">Jumlah item yang gratis.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- ═══ SECTION 3: Sasaran Diskon ═══ -->
        <div>
          <div class="flex items-center gap-2 mb-3">
            <div class="w-6 h-6 rounded-lg bg-blue-100 flex items-center justify-center">
              <i class="pi pi-bullseye text-xs text-blue-700"></i>
            </div>
            <h3 class="text-sm font-semibold text-slate-800">Sasaran Diskon</h3>
          </div>
          <div class="bg-slate-50 rounded-xl p-4 space-y-3">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Sasaran</label>
              <div class="flex flex-wrap gap-2">
                <button type="button" v-for="t in targetTypeOptions" :key="t.value"
                  class="px-3.5 py-2 rounded-lg text-sm font-medium border transition-all duration-200"
                  :class="form.target_type === t.value
                    ? 'border-blue-400 bg-blue-50 text-blue-700 shadow-sm'
                    : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                  @click="form.target_type = t.value; form.target_id = []">
                  <i :class="t.icon" class="mr-1.5"></i>
                  {{ t.label }}
                </button>
              </div>
            </div>
            <div v-if="form.target_type === 'product'" class="pt-1">
              <label class="block text-sm font-medium text-slate-700 mb-1">Pilih Produk <span class="text-red-400">*</span></label>
              <MultiSelect v-model="form.target_id" :options="products" optionLabel="name" optionValue="id"
                placeholder="Cari dan pilih produk..." class="w-full" filter display="chip"
                :maxSelectedLabels="3" selectedItemsLabel="{0} produk dipilih" />
              <p class="text-xs text-slate-400 mt-1">Klik produk untuk memilih. Klik <i class="pi pi-times"></i> pada pill untuk menghapus.</p>
            </div>
            <div v-else-if="form.target_type === 'category'" class="pt-1">
              <label class="block text-sm font-medium text-slate-700 mb-1">Pilih Kategori <span class="text-red-400">*</span></label>
              <MultiSelect v-model="form.target_id" :options="categories" optionLabel="name" optionValue="id"
                placeholder="Cari dan pilih kategori..." class="w-full" filter display="chip"
                :maxSelectedLabels="2" selectedItemsLabel="{0} kategori dipilih" />
              <p class="text-xs text-slate-400 mt-1">Klik kategori untuk memilih. Klik <i class="pi pi-times"></i> pada pill untuk menghapus.</p>
            </div>
          </div>
        </div>

        <!-- ═══ SECTION 4: Ketentuan Tambahan ═══ -->
        <div>
          <div class="flex items-center gap-2 mb-3">
            <div class="w-6 h-6 rounded-lg bg-violet-100 flex items-center justify-center">
              <i class="pi pi-sliders-h text-xs text-violet-700"></i>
            </div>
            <h3 class="text-sm font-semibold text-slate-800">Ketentuan Tambahan</h3>
          </div>
          <div class="bg-slate-50 rounded-xl p-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Min. Pembelian (Rp)</label>
                <InputNumber v-model="form.min_purchase" class="w-full" :min="0" placeholder="0 (tanpa minimal)" />
                <p class="text-xs text-slate-400 mt-1">Isi jika diskon hanya berlaku di atas nominal tertentu.</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Maks. Potongan (Rp)</label>
                <InputNumber v-model="form.max_discount" class="w-full" :min="0" placeholder="0 (tanpa batas)" />
                <p class="text-xs text-slate-400 mt-1">Batas maksimal nominal diskon yang diberikan.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- ═══ SECTION 5: Masa Berlaku ═══ -->
        <div>
          <div class="flex items-center gap-2 mb-3">
            <div class="w-6 h-6 rounded-lg bg-amber-100 flex items-center justify-center">
              <i class="pi pi-calendar text-xs text-amber-700"></i>
            </div>
            <h3 class="text-sm font-semibold text-slate-800">Masa Berlaku</h3>
            <span class="text-[10px] text-slate-400 font-normal">(opsional)</span>
          </div>
          <div class="bg-slate-50 rounded-xl p-4">
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
          </div>
        </div>

        <!-- Preview Summary -->
        <div v-if="form.name || form.value || form.buy_x"
          class="rounded-xl border border-dashed border-slate-300 bg-white p-4">
          <div class="flex items-center gap-2 mb-2">
            <i class="pi pi-eye text-slate-400 text-xs"></i>
            <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Pratinjau</span>
          </div>
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-teal-100 to-teal-200 flex items-center justify-center text-sm">
              <i class="pi pi-percentage text-teal-700"></i>
            </div>
            <div>
              <p class="text-sm font-semibold text-slate-800">{{ form.name || 'Diskon Baru' }}</p>
              <p class="text-xs text-slate-500">
                <template v-if="form.discountType === 'bogo' && form.buy_x && form.buy_y">
                  Beli {{ form.buy_x }} Gratis {{ form.buy_y }}
                </template>
                <template v-else-if="form.discountType && form.discountType !== 'bogo'">
                  {{ form.discountType === 'percent' ? form.value + '%' : 'Rp ' + (form.value || 0).toLocaleString('id-ID') }}
                </template>
                <span class="mx-1.5 text-slate-300">•</span>
                {{ targetPreviewLabel }}
                <span v-if="form.min_purchase" class="ml-1">• Min. Rp {{ form.min_purchase.toLocaleString('id-ID') }}</span>
              </p>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
          <Button label="Batal" severity="secondary" @click="showDialog = false" />
          <Button type="submit" :label="editing ? 'Simpan Perubahan' : 'Buat Diskon'" :loading="saving"
            icon="pi pi-check" />
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
import { usePermission } from '../../utils/usePermission'
import { formatRupiah } from '../../utils/format'
import { useToastStore } from '../../stores/toast'
import client from '../../api/client'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import MultiSelect from 'primevue/multiselect'
import Calendar from 'primevue/calendar'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Tooltip from 'primevue/tooltip'

const perm = usePermission()
const toast = useToastStore()

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
  {
    value: 'percent',
    label: 'Persentase',
    icon: 'pi pi-percentage',
    desc: 'Potongan berdasarkan % dari total',
  },
  {
    value: 'nominal',
    label: 'Nominal',
    icon: 'pi pi-money-bill',
    desc: 'Potongan harga tetap dalam Rp',
  },
  {
    value: 'bogo',
    label: 'Beli Gratis',
    icon: 'pi pi-gift',
    desc: 'Beli X Gratis Y (BOGO)',
  },
]

const targetTypeOptions = [
  { label: 'Semua Transaksi', value: 'transaction', icon: 'pi pi-shopping-cart' },
  { label: 'Produk Tertentu', value: 'product', icon: 'pi pi-box' },
  { label: 'Kategori Tertentu', value: 'category', icon: 'pi pi-tags' },
]

const form = ref({
  name: '',
  discountType: 'percent',
  type: 'percent',
  value: 0,
  target_type: 'transaction',
  target_id: [],
  min_purchase: null,
  max_discount: null,
  buy_x: null,
  buy_y: null,
  start_date: null,
  end_date: null,
})

const targetPreviewLabel = computed(() => {
  const t = form.value.target_type
  const ids = form.value.target_id
  if (t === 'product' && ids?.length) {
    const names = ids.map(id => {
      const found = products.value.find(p => p.id === id)
      return found?.name
    }).filter(Boolean)
    return names.length ? `Produk: ${names.join(', ')}` : `${ids.length} produk`
  }
  if (t === 'category' && ids?.length) {
    const names = ids.map(id => {
      const found = categories.value.find(c => c.id === id)
      return found?.name
    }).filter(Boolean)
    return names.length ? `Kategori: ${names.join(', ')}` : `${ids.length} kategori`
  }
  return 'Semua transaksi'
})

function selectDiscountType(val) {
  form.value.discountType = val
  form.value.type = val === 'bogo' ? 'percent' : val
  form.value.value = 0
  form.value.buy_x = null
  form.value.buy_y = null
}

function discountTypeLabel(d) {
  if (d.buy_x && d.buy_y) return `Beli ${d.buy_x} Gratis ${d.buy_y}`
  return d.type === 'percent' ? 'Persen' : 'Nominal'
}

function discountTypeSeverity(d) {
  if (d.buy_x && d.buy_y) return 'contrast'
  return d.type === 'percent' ? 'info' : 'warn'
}

function targetLabel(d) {
  if (d.target_type === 'product' && d.target_products?.length) {
    const names = d.target_products.map(p => p.name).join(', ')
    return `Produk: ${names}`
  }
  if (d.target_type === 'category' && d.target_categories?.length) {
    const names = d.target_categories.map(c => c.name).join(', ')
    return `Kategori: ${names}`
  }
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
    discountType: 'percent',
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

  const isBogo = discount.buy_x && discount.buy_y
  form.value = {
    id: discount.id,
    name: discount.name,
    discountType: isBogo ? 'bogo' : discount.type,
    type: discount.type,
    value: discount.value,
    target_type: discount.target_type || 'transaction',
    target_id: Array.isArray(discount.target_id) ? discount.target_id : (discount.target_id ? [discount.target_id] : []),
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
    const isBogo = form.value.discountType === 'bogo'

    const payload = {
      outlet_id: selectedOutletId.value,
      name: form.value.name,
      type: isBogo ? 'percent' : form.value.discountType,
      value: isBogo ? 0 : form.value.value,
      target_type: form.value.target_type || 'transaction',
      target_id: form.value.target_type !== 'transaction' ? (Array.isArray(form.value.target_id) ? form.value.target_id : []) : null,
      min_purchase: form.value.min_purchase || null,
      max_discount: form.value.max_discount || null,
      buy_x: isBogo ? form.value.buy_x : null,
      buy_y: isBogo ? form.value.buy_y : null,
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
    toast.error('Gagal Simpan Diskon', e.response?.data?.message || 'Gagal menyimpan diskon')
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
    toast.error('Gagal Hapus Diskon', e.response?.data?.message || 'Gagal menghapus diskon')
  } finally {
    deleting.value = false
  }
}

onMounted(fetchOutlets)
</script>
