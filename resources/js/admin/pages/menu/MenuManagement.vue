<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Menu Management</h1>
        <p class="text-sm text-slate-500 mt-1">Kelola produk dan kategori</p>
      </div>
      <Button label="Tambah Produk" icon="pi pi-plus" />
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
          <span class="p-input-icon-left flex-1">
            <i class="pi pi-search text-slate-400"></i>
            <InputText v-model="search" placeholder="Cari produk..." class="w-full" />
          </span>
          <Select v-model="selectedCategory" :options="categories" optionLabel="name" optionValue="id"
            placeholder="Semua kategori" class="w-48" :showClear="true" />
          <Select v-model="rowsPerPage" :options="[5, 10, 20, 50]" class="w-20" />
        </div>
      </div>
      <DataTable :value="filteredProducts" paginator :rows="rowsPerPage"
        paginatorTemplate="CurrentPageReport PrevPageLink NextPageLink"
        currentPageReportTemplate="Halaman {currentPage} dari {totalPages}"
        class="text-sm">
        <template #empty>Belum ada produk.</template>
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
        <Column header="Status" sortable>
          <template #body="{ data }">
            <Tag :value="data.is_active ? 'Aktif' : 'Non-Aktif'"
              :severity="data.is_active ? 'success' : 'danger'" rounded />
          </template>
        </Column>
        <Column header="Aksi" :exportable="false" style="width: 140px">
          <template #body>
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" text severity="secondary" rounded size="small"
                v-tooltip.top="'Edit'" />
              <Button icon="pi pi-power-off" text rounded size="small"
                :class="'text-amber-600'" v-tooltip.top="'Aktifkan/Nonaktifkan'" />
              <Button icon="pi pi-trash" text severity="danger" rounded size="small"
                v-tooltip.top="'Hapus'" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import client from '../../api/client'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Tooltip from 'primevue/tooltip'

const products = ref([])
const categories = ref([])
const search = ref('')
const selectedCategory = ref(null)
const rowsPerPage = ref(10)

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

onMounted(async () => {
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
})
</script>
