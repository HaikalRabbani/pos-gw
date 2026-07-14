<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Manajemen Outlet</h1>
        <p class="text-sm text-slate-500 mt-1">Kelola cabang dan lokasi bisnis</p>
      </div>
      <Button label="Tambah Outlet" icon="pi pi-plus" @click="openAddDialog" />
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-teal-100 flex items-center justify-center">
            <i class="pi pi-building text-teal-600 text-lg"></i>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-teal-600">Total Outlet</p>
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
      <DataTable :value="outlets" class="text-sm">
        <template #empty>
          <div class="flex flex-col items-center justify-center py-12 text-center">
            <i class="pi pi-building text-4xl text-slate-200 mb-3"></i>
            <p class="text-slate-400 text-sm">Belum ada outlet. Tambahkan outlet pertama Anda!</p>
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
        <Column header="Token Publik" style="width: 200px">
          <template #body="{ data }">
            <div class="flex items-center gap-1">
              <code class="text-xs bg-slate-100 px-2 py-1 rounded font-mono truncate max-w-[140px]">
                {{ data.token_public ? data.token_public.substring(0, 16) + '...' : '—' }}
              </code>
              <Button v-if="data.token_public" icon="pi pi-copy" text rounded size="small"
                v-tooltip.top="'Salin Token'" @click="copyToken(data.token_public)" />
            </div>
          </template>
        </Column>
        <Column header="Aksi" style="width: 160px">
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
    <Dialog v-model:visible="showDialog" :header="editing ? 'Edit Outlet' : 'Tambah Outlet'" modal class="w-md">
      <form @submit.prevent="saveOutlet" class="space-y-4">
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
        <div class="flex justify-end gap-2 pt-2">
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
import client from '../../api/client'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Tooltip from 'primevue/tooltip'

const outlets = ref([])
const loading = ref(true)
const saving = ref(false)
const deleting = ref(false)
const showDialog = ref(false)
const showDeleteDialog = ref(false)
const editing = ref(false)
const deletingOutlet = ref(null)

const form = ref({ name: '', address: '', phone: '' })

const activeCount = computed(() => outlets.value.filter((o) => o.is_active).length)
const inactiveCount = computed(() => outlets.value.filter((o) => !o.is_active).length)

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
  form.value = { name: '', address: '', phone: '' }
  showDialog.value = true
}

function openEditDialog(outlet) {
  editing.value = true
  form.value = { name: outlet.name, address: outlet.address || '', phone: outlet.phone || '' }
  form.value.id = outlet.id
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
    alert(e.response?.data?.message || 'Gagal menyimpan outlet')
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
    alert(e.response?.data?.message || 'Gagal menghapus outlet')
  } finally {
    deleting.value = false
  }
}

async function copyToken(token) {
  try {
    await navigator.clipboard.writeText(token)
  } catch (_) {
    alert('Token: ' + token)
  }
}

onMounted(fetchOutlets)
</script>
