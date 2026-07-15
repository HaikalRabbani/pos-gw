<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Menu Management</h1>
        <p class="text-sm text-slate-500 mt-1">Kelola produk dan kategori</p>
      </div>
    </div>

    <div class="grid grid-cols-3 gap-4">
      <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-sm text-slate-500">Total Produk</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ products.length }}</p>
      </div>
      <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-sm text-slate-500">Aktif</p>
        <p class="text-2xl font-bold text-teal-600 mt-1">{{ activeCount }}</p>
      </div>
      <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-sm text-slate-500">Non-Aktif</p>
        <p class="text-2xl font-bold text-slate-400 mt-1">{{ inactiveCount }}</p>
      </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
      <div class="p-3 border-b border-slate-100">
        <div class="flex items-center gap-3">
          <span class="flex-1">
            <InputText v-model="search" placeholder="🔍 Cari produk..." class="w-full" />
          </span>
          <Select v-model="selectedCategory" :options="categories" optionLabel="name" optionValue="id"
            placeholder="Semua kategori" class="w-48" :showClear="true" />
          <Button label="Tambah Produk" icon="pi pi-plus" size="small" class="shrink-0"
            @click="openProductDialog()" />
        </div>
      </div>
      <DataTable :value="filteredProducts" paginator :rows="rowsPerPage" stripedRows size="small"
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
              <i class="pi pi-box text-2xl text-slate-300"></i>
            </div>
            <p class="text-slate-500 font-medium">Belum ada produk</p>
            <p class="text-slate-400 text-xs mt-1">Tambahkan produk untuk mulai menjual</p>
          </div>
        </template>
        <Column field="name" header="Nama Produk" sortable />
        <Column header="Kategori">
          <template #body="{ data }">
            <Tag :value="data.category?.name || '-'" severity="info" rounded />
          </template>
        </Column>
        <Column field="price" header="Harga" sortable>
          <template #body="{ data }">
            <span class="font-medium">Rp {{ (data.price / 100).toLocaleString('id-ID') }}</span>
          </template>
        </Column>
        <Column header="Deskripsi">
          <template #body="{ data }">
            <span v-if="data.description" class="text-xs text-slate-500 line-clamp-2 max-w-xs">
              {{ data.description }}
            </span>
            <span v-else class="text-xs text-slate-300 italic">—</span>
          </template>
        </Column>
        <Column header="Status" sortable>
          <template #body="{ data }">
            <Tag :value="data.is_active ? 'Aktif' : 'Non-Aktif'"
              :severity="data.is_active ? 'success' : 'danger'" rounded />
          </template>
        </Column>
        <Column header="Aksi" :exportable="false" style="width: 140px">
          <template #body="{ data }">
            <div class="flex gap-3">
              <Button icon="pi pi-pencil" text severity="secondary" rounded size="small"
                v-tooltip.top="'Edit'"
                @click="openProductDialog(data)" />
              <Button icon="pi pi-power-off" text rounded size="small"
                :class="data.is_active ? 'text-amber-600' : 'text-teal-600'"
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

    <!-- Product Add/Edit Dialog -->
    <Dialog v-model:visible="showProductDialog" :header="editingProduct ? 'Edit Produk' : 'Tambah Produk'" modal class="w-lg">
      <form @submit.prevent="saveProduct" class="space-y-4">
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
            <label class="block text-sm font-medium text-slate-700 mb-1">Harga (Rp) <span class="text-red-400">*</span></label>
            <InputNumber v-model="productForm.price" class="w-full" :min="0" :minFractionDigits="0"
              placeholder="35000" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Harga Modal (Rp)</label>
            <InputNumber v-model="productForm.cost" class="w-full" :min="0" :minFractionDigits="0"
              placeholder="0" />
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

    <!-- Delete Confirmation -->
    <Dialog v-model:visible="showDeleteDialog" header="Hapus Produk" modal class="w-sm">
      <div class="space-y-3">
        <div class="flex items-center gap-3 p-3 rounded-xl bg-red-50 border border-red-100">
          <i class="pi pi-exclamation-triangle text-red-500 text-xl"></i>
          <p class="text-sm text-red-700">
            Yakin ingin menghapus <strong>{{ deletingProduct?.name }}</strong>?
          </p>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Batal" severity="secondary" @click="showDeleteDialog = false" />
          <Button label="Hapus" severity="danger" :loading="deleting" @click="deleteProduct" />
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
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Tooltip from 'primevue/tooltip'

const products = ref([])
const categories = ref([])
const search = ref('')
const selectedCategory = ref(null)
const rowsPerPage = ref(10)
const showProductDialog = ref(false)
const showDeleteDialog = ref(false)
const editingProduct = ref(false)
const savingProduct = ref(false)
const deleting = ref(false)
const deletingProduct = ref(null)

const productForm = ref({ name: '', category_id: null, price: 0, cost: 0, description: '' })

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
  try {
    const { data: outletRes } = await client.get('/outlets')
    const outletId = outletRes.data[0]?.id
    if (!outletId) return
    const [prodRes, catRes] = await Promise.all([
      client.get('/products', { params: { outlet_id: outletId } }),
      client.get('/categories', { params: { outlet_id: outletId } }),
    ])
    products.value = prodRes.data.data
    categories.value = catRes.data.data
  } catch (_) {}
}

function openProductDialog(product) {
  if (product) {
    editingProduct.value = true
    productForm.value = {
      id: product.id,
      name: product.name,
      category_id: product.category_id,
      price: product.price,
      cost: product.cost || 0,
      description: product.description || '',
    }
  } else {
    editingProduct.value = false
    productForm.value = { name: '', category_id: null, price: 0, cost: 0, description: '' }
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
      price: productForm.value.price,
      cost: productForm.value.cost || 0,
      description: productForm.value.description || null,
    }
    if (editingProduct.value && productForm.value.id) {
      await client.put(`/products/${productForm.value.id}`, { ...payload, outlet_id: outletId })
    } else {
      await client.post('/products', { ...payload, outlet_id: outletId })
    }
    showProductDialog.value = false
    fetchData()
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menyimpan produk')
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

function confirmDelete(product) {
  deletingProduct.value = product
  showDeleteDialog.value = true
}

async function deleteProduct() {
  deleting.value = true
  try {
    await client.delete(`/products/${deletingProduct.value.id}`)
    showDeleteDialog.value = false
    fetchData()
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal menghapus produk')
  } finally {
    deleting.value = false
  }
}

onMounted(fetchData)
</script>
