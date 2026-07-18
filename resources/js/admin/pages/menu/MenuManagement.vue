<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Menu Management</h1>
        <p class="text-sm text-slate-500 mt-1">Kelola produk dan kategori</p>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white rounded-2xl border border-slate-200 p-4">
        <p class="text-sm text-slate-500">Total Produk</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ products.length }}</p>
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

    <div v-else class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
      <div class="p-3 border-b border-slate-100">
        <div class="flex items-center gap-3">
          <span class="flex-1">
            <InputText v-model="search" placeholder="Cari produk..." class="w-full" />
          </span>
          <Select v-model="selectedCategory" :options="categories" optionLabel="name" optionValue="id"
            placeholder="Semua kategori" class="w-48" :showClear="true" />

          <!-- Inline Kategori & Station Management -->
          <template v-if="perm.can('manageCategories')">
            <Button label="Kategori" severity="secondary" text size="small"
              v-tooltip.top="'Kelola kategori'" @click="openCategoryModal">
              <template #icon>
                <Tags class="w-4 h-4" stroke-width="1.5" />
              </template>
            </Button>
            <Button label="Station" severity="secondary" text size="small"
              v-tooltip.top="'Kelola station produksi'" @click="openStationModal">
              <template #icon>
                <Printer class="w-4 h-4" stroke-width="1.5" />
              </template>
            </Button>
          </template>

          <Button v-if="perm.can('manageProducts')" label="Tambah Produk" size="small" class="shrink-0"
            @click="openProductDialog()">
            <template #icon>
              <Plus class="w-4 h-4" stroke-width="1.5" />
            </template>
          </Button>
        </div>
      </div>
      <DataTable :value="filteredProducts" paginator :rows="rowsPerPage" stripedRows size="small"
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
              <Package class="w-6 h-6 text-slate-300" stroke-width="1.5" />
            </div>
            <p class="text-slate-500 font-medium">Belum ada produk</p>
            <p class="text-slate-400 text-xs mt-1">Tambahkan produk untuk mulai menjual</p>
          </div>
        </template>
        <Column header="Foto" style="width: 60px">
          <template #body="{ data }">
            <img v-if="data.image" :src="data.image" class="w-9 h-9 rounded-lg object-cover border border-slate-200" />
            <div v-else class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center">
              <Image class="w-4 h-4 text-slate-300" stroke-width="1.5" />
            </div>
          </template>
        </Column>
        <Column field="name" header="Nama Produk" sortable />
        <Column header="Kategori">
          <template #body="{ data }">
            <Tag :value="data.category?.name || '-'" severity="info" rounded />
          </template>
        </Column>
        <Column header="Station">
          <template #body="{ data }">
            <Tag :value="data.station?.name || '-'" severity="warn" rounded />
          </template>
        </Column>
        <Column field="price" header="Harga" sortable>
          <template #body="{ data }">
            <span class="font-medium">{{ formatRupiah(data.price) }}</span>
          </template>
        </Column>
        <!-- Stock Column (product mode) — read-only, edit di modal -->
        <Column v-if="outletStockMode === 'product'" field="stock" header="Stok" style="width: 80px" sortable>
          <template #body="{ data }">
            <span class="font-medium" :class="data.stock > 0 ? 'text-slate-800' : 'text-red-500'">{{ data.stock ?? 0 }}</span>
          </template>
        </Column>
        <Column header="Aksi" :exportable="false" style="width: 180px">
          <template #body="{ data }">
            <div class="flex gap-1.5">
              <Button text rounded severity="secondary" size="small"
                v-tooltip.top="'Lihat detail'"
                @click="openDetail(data)">
                <template #icon>
                  <Eye class="w-4 h-4" stroke-width="1.5" />
                </template>
              </Button>
              <Button v-if="perm.can('manageProducts')" text rounded severity="secondary" size="small"
                v-tooltip.top="'Edit'"
                @click="openProductDialog(data)">
                <template #icon>
                  <Pencil class="w-4 h-4" stroke-width="1.5" />
                </template>
              </Button>
              <Button v-if="perm.can('manageProducts')" text rounded size="small"
                :severity="data.is_featured ? 'warn' : 'secondary'"
                v-tooltip.top="data.is_featured ? 'Batal jadikan Rekomendasi di Self-Order' : 'Jadikan Rekomendasi di Self-Order'"
                @click="toggleFeatured(data)">
                <template #icon>
                  <Star class="w-4 h-4" :fill="data.is_featured ? 'currentColor' : 'none'" stroke-width="1.5" />
                </template>
              </Button>
              <Button v-if="perm.can('manageProducts')" text rounded severity="info" size="small"
                v-tooltip.top="'Atur Bahan & Add-on'"
                @click="openProductIngredients(data)">
                <template #icon>
                  <Palette class="w-4 h-4" stroke-width="1.5" />
                </template>
              </Button>
              <Button v-if="perm.can('manageProducts')" text severity="danger" rounded size="small"
                v-tooltip.top="'Hapus'"
                @click="confirmDelete(data)">
                <template #icon>
                  <Trash2 class="w-4 h-4" stroke-width="1.5" />
                </template>
              </Button>
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Product Add/Edit Dialog -->
    <Dialog v-model:visible="showProductDialog" :header="editingProduct ? 'Edit Produk' : 'Tambah Produk'" modal class="w-lg">
      <form @submit.prevent="saveProduct" class="space-y-4">
        <!-- Image Upload -->
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Foto Produk</label>
          <div class="flex items-center gap-4">
            <div class="relative w-20 h-20 rounded-xl border-2 border-dashed border-slate-300 flex items-center justify-center overflow-hidden bg-slate-50">
              <img v-if="productForm.image" :src="productForm.image" class="w-full h-full object-cover" />
              <Image v-else class="w-6 h-6 text-slate-300" stroke-width="1.5" />
            </div>
            <div class="flex-1">
              <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="uploadImage" />
              <Button type="button" label="Pilih Foto" severity="secondary" size="small"
                @click="fileInput?.click()" :loading="uploading">
                <template #icon>
                  <Upload class="w-4 h-4" stroke-width="1.5" />
                </template>
              </Button>
              <p class="text-xs text-slate-400 mt-1">Maks 2MB. Format: JPG, PNG.</p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nama <span class="text-red-400">*</span></label>
            <InputText v-model="productForm.name" class="w-full" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Kategori</label>
            <Select v-model="productForm.category_id" :options="categories" optionLabel="name" optionValue="id"
              placeholder="Pilih kategori" class="w-full" :showClear="true" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
              Station <Printer class="w-3 h-3 text-orange-400 inline ml-1" stroke-width="1.5" />
            </label>
            <Select v-model="productForm.station_id" :options="stations" optionLabel="name" optionValue="id"
              placeholder="Pilih station" class="w-full" :showClear="true" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Harga (Rp) <span class="text-red-400">*</span></label>
            <InputNumber v-model="productForm.price" class="w-full" :min="0" :minFractionDigits="0"
              placeholder="35000" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Harga Modal (Rp)</label>
            <InputNumber v-model="productForm.cost" class="w-full" :min="0" :minFractionDigits="0"
              placeholder="0" />
          </div>
          <!-- Stock (product mode) -->
          <div v-if="outletStockMode === 'product'">
            <label class="block text-sm font-medium text-slate-700 mb-1">Stok</label>
            <InputNumber v-model="productForm.stock" :min="0" class="w-full" placeholder="0" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
            <Select v-model="productForm.is_active" :options="statusOptions" optionLabel="label" optionValue="value"
              placeholder="Aktif" class="w-full" />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
          <Textarea v-model="productForm.description" class="w-full" rows="3"
            placeholder="Deskripsi produk (opsional)" :autoResize="true" />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Batal" severity="secondary" @click="showProductDialog = false" />
          <Button type="submit" :label="editingProduct ? 'Simpan' : 'Tambah'" :loading="savingProduct" />
        </div>
      </form>
    </Dialog>

    <!-- Product Detail Dialog -->
    <Dialog v-model:visible="showDetailDialog" :header="viewingProduct?.name" modal class="w-lg">
      <div v-if="viewingProduct" class="space-y-5">
        <!-- Image + Basic Info -->
        <div class="flex items-start gap-5">
          <div class="w-24 h-24 rounded-xl border border-slate-200 overflow-hidden shrink-0 bg-slate-50">
            <img v-if="viewingProduct.image" :src="viewingProduct.image" class="w-full h-full object-cover" />
            <div v-else class="w-full h-full flex items-center justify-center">
              <Image class="w-8 h-8 text-slate-300" stroke-width="1.5" />
            </div>
          </div>
          <div class="flex-1 min-w-0">
            <h3 class="text-lg font-bold text-slate-900">{{ viewingProduct.name }}</h3>
            <p class="text-xs text-slate-400 mt-0.5">
              {{ viewingProduct.category?.name || 'Tanpa kategori' }}
              <span class="mx-1">•</span>
              {{ viewingProduct.station?.name || 'Tanpa station' }}
            </p>
            <div class="flex items-center gap-3 mt-2">
              <span class="text-2xl font-bold text-teal-600">{{ formatRupiah(viewingProduct.price) }}</span>
              <Tag :value="viewingProduct.is_active ? 'Aktif' : 'Non-Aktif'"
                :severity="viewingProduct.is_active ? 'success' : 'danger'" rounded />
            </div>
          </div>
        </div>

        <!-- Detail Grid -->
        <div class="grid grid-cols-2 gap-3">
          <div class="p-3 rounded-xl bg-teal-50 border border-teal-200">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-teal-600">Harga Modal</p>
            <p class="text-sm font-bold text-slate-800 mt-0.5">{{ formatRupiah(viewingProduct.cost || 0) }}</p>
          </div>
          <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-emerald-600">Laba per Item</p>
            <p class="text-sm font-bold" :class="profitClass(viewingProduct)">
              {{ formatRupiah((viewingProduct.price || 0) - (viewingProduct.cost || 0)) }}
              <span class="text-xs font-normal text-slate-400 ml-1">
                ({{ profitPercent(viewingProduct) }}%)
              </span>
            </p>
          </div>
        </div>

        <!-- Description -->
        <div v-if="viewingProduct.description">
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Deskripsi</p>
          <p class="text-sm text-slate-700 leading-relaxed">{{ viewingProduct.description }}</p>
        </div>

        <!-- Timestamps -->
        <div class="flex items-center gap-4 text-[10px] text-slate-400 border-t border-slate-100 pt-3">
          <span>ID Produk: #{{ viewingProduct.id }}</span>
          <span>Dibuat: {{ formatDate(viewingProduct.created_at) }}</span>
          <span v-if="viewingProduct.updated_at !== viewingProduct.created_at">Diupdate: {{ formatDate(viewingProduct.updated_at) }}</span>
        </div>
      </div>
    </Dialog>

    <!-- Kategori Modal (inline) -->
    <Dialog v-model:visible="showCategoryModal" header="Manajemen Kategori" modal class="w-md">
      <div class="space-y-4">
        <form @submit.prevent="addCategory" class="flex gap-2">
          <InputText v-model="newCategoryName" placeholder="Nama kategori baru..." class="flex-1" required />
          <Button type="submit" label="Tambah" :loading="savingCategory" :disabled="!newCategoryName.trim()" />
        </form>
        <div class="border-t border-slate-200 pt-3">
          <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Daftar Kategori</p>
          <div v-if="categories.length === 0" class="text-sm text-slate-400 text-center py-4">Belum ada kategori.</div>
          <ul v-else class="divide-y divide-slate-100">
            <li v-for="cat in categories" :key="cat.id"
              class="flex items-center justify-between py-2.5">
              <div class="flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-teal-400"></div>
                <span class="text-sm text-slate-800">{{ cat.name }}</span>
              </div>
              <Button text rounded severity="danger" size="small"
                v-tooltip.top="'Hapus kategori'"
                @click="confirmDeleteCategory(cat)">
                <template #icon>
                  <Trash2 class="w-4 h-4" stroke-width="1.5" />
                </template>
              </Button>
            </li>
          </ul>
        </div>
      </div>
    </Dialog>

    <!-- Product Ingredients Dialog -->
    <Dialog v-model:visible="showProductIngredientsDialog" :header="'Atur Bahan: ' + (editingIngredientProduct?.name || '')" modal class="w-lg">
      <div class="space-y-4">
        <p class="text-sm text-slate-500">Centang bahan yang tersedia untuk produk ini. Atur apakah bisa dimatiin pelanggan, atau sebagai add-on berbayar.</p>

        <!-- Loading -->
        <div v-if="loadingIngredients" class="flex items-center justify-center py-8">
          <LoaderCircle class="w-6 h-6 text-slate-400 animate-spin" stroke-width="1.5" />
        </div>

        <div v-else-if="availableIngredients.length === 0" class="text-sm text-slate-400 text-center py-4">
          Belum ada bahan. Tambah bahan dulu lewat tombol "Bahan" di atas.
        </div>

        <div v-else class="space-y-2 max-h-96 overflow-y-auto">
          <div v-for="ing in availableIngredients" :key="ing.id"
            class="flex items-center gap-3 p-3 rounded-xl border"
            :class="productIngredientMap[ing.id] ? 'border-violet-200 bg-violet-50' : 'border-slate-200 bg-white'">
            <Checkbox :binary="true" v-model="productIngredientMap[ing.id]" @change="toggleProductIngredient(ing)" />
            <span class="flex-1 text-sm font-medium" :class="productIngredientMap[ing.id] ? 'text-violet-800' : 'text-slate-600'">
              {{ ing.name }}
            </span>
            <template v-if="productIngredientMap[ing.id]">
              <div class="flex items-center gap-2">
                <label class="text-xs text-slate-500 whitespace-nowrap">Harga tambahan</label>
                <InputNumber v-model="productIngredientPrices[ing.id]" :min="0" :minFractionDigits="0" class="w-24" placeholder="0" size="small" />
              </div>
              <div class="flex items-center gap-1.5">
                <label class="text-xs text-slate-500">Bisa dimatiin</label>
                <Checkbox :binary="true" v-model="productIngredientRemovable[ing.id]" />
              </div>
              <div class="flex items-center gap-1.5">
                <label class="text-xs text-slate-500">Default</label>
                <Checkbox :binary="true" v-model="productIngredientDefault[ing.id]" />
              </div>
            </template>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
          <Button label="Batal" severity="secondary" @click="showProductIngredientsDialog = false" />
          <Button label="Simpan Bahan" :loading="savingProductIngredients" @click="saveProductIngredients" />
        </div>
      </div>
    </Dialog>

    <!-- Station Modal (inline) -->
    <Dialog v-model:visible="showStationModal" header="Manajemen Station" modal class="w-md">
      <div class="space-y-4">
        <form @submit.prevent="addStation" class="flex gap-2">
          <InputText v-model="newStationName" placeholder="Nama station baru... (contoh: Dapur, Bar, Grill)" class="flex-1" required />
          <Button type="submit" label="Tambah" :loading="savingStation" :disabled="!newStationName.trim()" />
        </form>
        <div class="border-t border-slate-200 pt-3">
          <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Daftar Station</p>
          <p class="text-[11px] text-slate-400 mb-3">Station digunakan untuk routing cetak struk ke thermal printer masing-masing area produksi.</p>
          <div v-if="stations.length === 0" class="text-sm text-slate-400 text-center py-4">Belum ada station.</div>
          <ul v-else class="divide-y divide-slate-100">
            <li v-for="st in stations" :key="st.id"
              class="flex items-center justify-between py-2.5">
              <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-orange-100 flex items-center justify-center text-xs font-bold text-orange-700">
                  <Printer class="w-3 h-3" stroke-width="1.5" />
                </div>
                <span class="text-sm text-slate-800">{{ st.name }}</span>
              </div>
              <Button text rounded severity="danger" size="small"
                v-tooltip.top="'Hapus station'"
                @click="confirmDeleteStation(st)">
                <template #icon>
                  <Trash2 class="w-4 h-4" stroke-width="1.5" />
                </template>
              </Button>
            </li>
          </ul>
        </div>
      </div>
    </Dialog>

    <!-- Delete Confirmation (general) -->
    <Dialog v-model:visible="showDeleteDialog" :header="'Hapus ' + deleteTarget?.type" modal class="w-sm">
      <div class="space-y-3">
        <div class="flex items-center gap-3 p-3 rounded-xl bg-red-50 border border-red-100">
          <AlertTriangle class="w-5 h-5 text-red-500 shrink-0" stroke-width="1.5" />
          <p class="text-sm text-red-700">
            Yakin ingin menghapus <strong>{{ deleteTarget?.name }}</strong>?
          </p>
        </div>
        <p v-if="deleteTarget?.type === 'Kategori'" class="text-xs text-slate-500">Produk dengan kategori ini tidak akan terhapus, hanya kategori-nya yang hilang.</p>
        <p v-else-if="deleteTarget?.type === 'Station'" class="text-xs text-slate-500">Produk dengan station ini akan otomatis kehilangan station-nya.</p>
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
import { formatRupiah } from '../../utils/format'
import { useToastStore } from '../../stores/toast'
import client from '../../api/client'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Checkbox from 'primevue/checkbox'
import Tooltip from 'primevue/tooltip'
import {
  Package, Image, Tags, Printer, Plus, Eye, Pencil, Palette,
  Trash2, AlertTriangle, Upload, LoaderCircle, Star
} from '@lucide/vue'

const perm = usePermission()
const toast = useToastStore()

const loading = ref(true)
const products = ref([])
const categories = ref([])
const stations = ref([])
const search = ref('')
const selectedCategory = ref(null)
const rowsPerPage = ref(10)
const showProductDialog = ref(false)
const showDetailDialog = ref(false)
const showDeleteDialog = ref(false)
const showCategoryModal = ref(false)
const showStationModal = ref(false)
const showProductIngredientsDialog = ref(false)
const editingProduct = ref(false)
const savingProduct = ref(false)
const savingCategory = ref(false)
const savingStation = ref(false)
const deleting = ref(false)
const uploading = ref(false)
const newCategoryName = ref('')
const newStationName = ref('')
const deleteTarget = ref(null)
const viewingProduct = ref(null)
const editingIngredientProduct = ref(null)
const fileInput = ref(null)

// Ingredients state
const outletStockMode = ref('product')
const availableIngredients = ref([])
const loadingIngredients = ref(false)
const savingProductIngredients = ref(false)
const productIngredientMap = ref({})
const productIngredientPrices = ref({})
const productIngredientRemovable = ref({})
const productIngredientDefault = ref({})

const statusOptions = [
  { label: 'Aktif', value: true },
  { label: 'Non-Aktif', value: false },
]

const productForm = ref({ name: '', category_id: null, station_id: null, price: 0, cost: 0, stock: 0, description: '', is_active: true, image: null })

const activeCount = computed(() => products.value.filter((p) => p.is_active).length)
const inactiveCount = computed(() => products.value.filter((p) => !p.is_active).length)

const filteredProducts = computed(() => {
  let result = products.value
  if (search.value) {
    const q = search.value.toLowerCase()
    result = result.filter((p) => p.name.toLowerCase().includes(q))
  }
  if (selectedCategory.value) {
    result = result.filter((p) => p.category_id === selectedCategory.value)
  }
  return result
})

async function fetchData() {
  loading.value = true
  try {
    const { data: outletRes } = await client.get('/outlets')
    const outletId = outletRes.data[0]?.id
    if (!outletId) return
    const [outletRes2, prodRes, catRes, staRes] = await Promise.all([
      client.get(`/outlets/${outletId}`),
      client.get('/products', { params: { outlet_id: outletId } }),
      client.get('/categories', { params: { outlet_id: outletId } }),
      client.get('/stations', { params: { outlet_id: outletId } }),
    ])
    outletStockMode.value = outletRes2.data.data?.stock_mode || 'product'
    products.value = prodRes.data.data
    categories.value = catRes.data.data
    stations.value = staRes.data.data
  } catch (_) {
  } finally {
    loading.value = false
  }
}

function openProductDialog(product) {
  if (product) {
    editingProduct.value = true
    productForm.value = {
      id: product.id,
      name: product.name,
      category_id: product.category_id,
      station_id: product.station_id,
      price: product.price,
      cost: product.cost || 0,
      stock: product.stock ?? 0,
      description: product.description || '',
      is_active: product.is_active ?? true,
      image: product.image || null,
    }
  } else {
    editingProduct.value = false
    productForm.value = { name: '', category_id: null, station_id: null, price: 0, cost: 0, stock: 0, description: '', is_active: true, image: null }
  }
  showProductDialog.value = true
}

async function saveProduct() {
  savingProduct.value = true
  try {
    const { data: outletRes } = await client.get('/outlets')
    const outletId = outletRes.data[0]?.id
    const payload = {
      name: productForm.value.name,
      category_id: productForm.value.category_id || null,
      station_id: productForm.value.station_id || null,
      price: productForm.value.price,
      cost: productForm.value.cost || 0,
      stock: productForm.value.stock ?? 0,
      description: productForm.value.description || null,
      is_active: productForm.value.is_active,
      image: productForm.value.image || null,
    }
    if (editingProduct.value && productForm.value.id) {
      await client.put(`/products/${productForm.value.id}`, { ...payload, outlet_id: outletId })
    } else {
      await client.post('/products', { ...payload, outlet_id: outletId })
    }
    showProductDialog.value = false
    fetchData()
  } catch (e) {
    toast.error('Gagal Simpan', e.response?.data?.message || 'Gagal menyimpan produk')
  } finally {
    savingProduct.value = false
  }
}

async function toggleActive(product) {
  try {
    await client.put(`/products/${product.id}`, { is_active: !product.is_active })
    fetchData()
  } catch (_) {}
}

async function toggleFeatured(product) {
  try {
    await client.put(`/products/${product.id}`, { is_featured: !product.is_featured })
    fetchData()
  } catch (_) {}
}

function confirmDelete(product) {
  deleteTarget.value = { id: product.id, name: product.name, type: 'Produk' }
  showDeleteDialog.value = true
}

async function executeDelete() {
  deleting.value = true
  try {
    const target = deleteTarget.value
    if (target.type === 'Produk') {
      await client.delete(`/products/${target.id}`)
    } else if (target.type === 'Kategori') {
      await client.delete(`/categories/${target.id}`)
      await fetchCategories()
    } else if (target.type === 'Station') {
      await client.delete(`/stations/${target.id}`)
      await fetchStations()
    }
    showDeleteDialog.value = false
    await fetchData()
  } catch (e) {
    toast.error('Gagal Hapus', e.response?.data?.message || 'Gagal menghapus')
  } finally {
    deleting.value = false
  }
}

// ── Inline Kategori CRUD ──
function openCategoryModal() {
  newCategoryName.value = ''
  showCategoryModal.value = true
}

async function fetchCategories() {
  try {
    const { data: outletRes } = await client.get('/outlets')
    const outletId = outletRes.data[0]?.id
    if (!outletId) return
    const { data } = await client.get('/categories', { params: { outlet_id: outletId } })
    categories.value = data.data
  } catch (_) {}
}

async function addCategory() {
  if (!newCategoryName.value.trim()) return
  savingCategory.value = true
  try {
    const { data: outletRes } = await client.get('/outlets')
    const outletId = outletRes.data[0]?.id
    await client.post('/categories', { outlet_id: outletId, name: newCategoryName.value })
    newCategoryName.value = ''
    await fetchCategories()
  } catch (e) {
    toast.error('Gagal Tambah Kategori', e.response?.data?.message || 'Gagal tambah kategori')
  } finally {
    savingCategory.value = false
  }
}

function confirmDeleteCategory(cat) {
  deleteTarget.value = { id: cat.id, name: cat.name, type: 'Kategori' }
  showDeleteDialog.value = true
}

// ── Inline Station CRUD ──
function openStationModal() {
  newStationName.value = ''
  showStationModal.value = true
}

async function fetchStations() {
  try {
    const { data: outletRes } = await client.get('/outlets')
    const outletId = outletRes.data[0]?.id
    if (!outletId) return
    const { data } = await client.get('/stations', { params: { outlet_id: outletId } })
    stations.value = data.data
  } catch (_) {}
}

async function addStation() {
  if (!newStationName.value.trim()) return
  savingStation.value = true
  try {
    const { data: outletRes } = await client.get('/outlets')
    const outletId = outletRes.data[0]?.id
    await client.post('/stations', { outlet_id: outletId, name: newStationName.value })
    newStationName.value = ''
    await fetchStations()
  } catch (e) {
    toast.error('Gagal Tambah Station', e.response?.data?.message || 'Gagal tambah station')
  } finally {
    savingStation.value = false
  }
}

function confirmDeleteStation(st) {
  deleteTarget.value = { id: st.id, name: st.name, type: 'Station' }
  showDeleteDialog.value = true
}

// ── Product Ingredients ──
async function openProductIngredients(product) {
  editingIngredientProduct.value = product
  showProductIngredientsDialog.value = true
  loadingIngredients.value = true

  productIngredientMap.value = {}
  productIngredientPrices.value = {}
  productIngredientRemovable.value = {}
  productIngredientDefault.value = {}

  try {
    const { data: outletRes } = await client.get('/outlets')
    const outletId = outletRes.data[0]?.id
    if (!outletId) return

    const [ingRes, prodIngRes] = await Promise.all([
      client.get('/ingredients', { params: { outlet_id: outletId } }),
      client.get(`/products/${product.id}/ingredients`),
    ])

    availableIngredients.value = ingRes.data.data

    if (prodIngRes.data.success) {
      const assigned = prodIngRes.data.data.assigned
      assigned.forEach((i) => {
        productIngredientMap.value[i.id] = true
        productIngredientPrices.value[i.id] = i.extra_price
        productIngredientRemovable.value[i.id] = i.is_removable
        productIngredientDefault.value[i.id] = i.is_default
      })
    }
  } catch (e) {
    toast.error('Gagal', 'Gagal memuat data bahan')
  } finally {
    loadingIngredients.value = false
  }
}

function toggleProductIngredient(ing) {
  const checked = productIngredientMap.value[ing.id]
  if (checked) {
    if (productIngredientPrices.value[ing.id] === undefined) {
      productIngredientPrices.value[ing.id] = 0
    }
    if (productIngredientRemovable.value[ing.id] === undefined) {
      productIngredientRemovable.value[ing.id] = true
    }
    if (productIngredientDefault.value[ing.id] === undefined) {
      productIngredientDefault.value[ing.id] = true
    }
  }
}

async function saveProductIngredients() {
  if (!editingIngredientProduct.value) return
  savingProductIngredients.value = true
  try {
    const ingredients = []
    for (const ing of availableIngredients.value) {
      if (productIngredientMap.value[ing.id]) {
        ingredients.push({
          ingredient_id: ing.id,
          is_removable: productIngredientRemovable.value[ing.id] ?? true,
          extra_price: productIngredientPrices.value[ing.id] ?? 0,
          is_default: productIngredientDefault.value[ing.id] ?? true,
          sort_order: 0,
        })
      }
    }
    await client.post(`/products/${editingIngredientProduct.value.id}/ingredients`, { ingredients })
    showProductIngredientsDialog.value = false
    toast.success('Berhasil', 'Bahan produk berhasil disimpan')
  } catch (e) {
    toast.error('Gagal Simpan', e.response?.data?.message || 'Gagal menyimpan bahan produk')
  } finally {
    savingProductIngredients.value = false
  }
}

function openDetail(product) {
  viewingProduct.value = product
  showDetailDialog.value = true
}

async function uploadImage(e) {
  const file = e.target.files[0]
  if (!file) return
  uploading.value = true
  try {
    const formData = new FormData()
    formData.append('file', file)
    const { data } = await client.post('/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    if (data.success && data.url) {
      productForm.value.image = data.url
    }
  } catch (_) {
    toast.error('Gagal Upload', 'Gagal mengupload foto')
  } finally {
    uploading.value = false
    e.target.value = ''
  }
}

function profitClass(product) {
  const profit = (product.price || 0) - (product.cost || 0)
  return profit >= 0 ? 'text-emerald-600' : 'text-red-600'
}

function profitPercent(product) {
  const cost = product.cost || 0
  if (cost === 0) return '—'
  return Math.round((((product.price || 0) - cost) / cost) * 100)
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

onMounted(fetchData)
</script>
