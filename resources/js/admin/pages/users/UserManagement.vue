<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Manajemen Pengguna</h1>
        <p class="text-sm text-slate-500 mt-1">Kelola karyawan dan akses outlet</p>
      </div>
      <div class="flex items-center gap-2">
        <!-- Outlet Filter (for all roles) -->
        <Select v-model="filterOutletId" :options="myOutlets"
          optionLabel="name" optionValue="id" placeholder="Semua outlet" class="w-48" showClear
          @change="fetchUsers" />
        <Button v-if="auth.isSuper" label="Tambah User" icon="pi pi-plus" @click="showAddDialog = true" />
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
            <i class="pi pi-users text-blue-600 text-lg"></i>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-blue-600">Total User</p>
            <p class="text-2xl font-bold text-slate-900 mt-0.5">{{ users.length }}</p>
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

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
      <div class="p-3 border-b border-slate-100 flex items-center gap-2 bg-slate-50/50">
        <span class="text-sm text-slate-500">Tampilkan</span>
        <Select v-model="rowsPerPage" :options="[5, 10, 20, 50]" class="w-20" />
        <span class="text-sm text-slate-500">data</span>
      </div>
      <DataTable :value="users" paginator :rows="rowsPerPage"
        paginatorTemplate="CurrentPageReport PrevPageLink NextPageLink"
        currentPageReportTemplate="Halaman {currentPage} dari {totalPages}"
        class="text-sm">
        <template #empty>
          <div class="flex flex-col items-center justify-center py-12 text-center">
            <i class="pi pi-users text-4xl text-slate-200 mb-3"></i>
            <p class="text-slate-400 text-sm">Belum ada data user.</p>
          </div>
        </template>
        <Column field="name" header="Nama" sortable />
        <Column field="email" header="Email" sortable />
        <Column header="Outlet & Role">
          <template #body="{ data }">
            <div class="flex flex-wrap gap-1">
              <span v-if="!data.outlets?.length" class="text-xs text-slate-400">—</span>
              <span v-for="o in data.outlets" :key="o.id"
                class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-md font-medium"
                :class="roleBadgeClass(o.pivot.role)">
                {{ o.name }}: {{ roleLabel(o.pivot.role) }}
              </span>
            </div>
          </template>
        </Column>
        <Column header="Status" sortable>
          <template #body="{ data }">
            <Tag :value="data.is_active ? 'Aktif' : 'Non-Aktif'"
              :severity="data.is_active ? 'success' : 'danger'" rounded />
          </template>
        </Column>
        <Column header="PIN">
          <template #body="{ data }">
            <span :class="data.pin ? 'text-slate-600' : 'text-slate-300'">
              {{ data.pin ? '••••••' : '—' }}
            </span>
          </template>
        </Column>
        <Column :header="auth.isSuper ? 'Aksi' : 'Kelola'" :style="{ width: auth.isSuper ? '200px' : '160px' }">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" text rounded size="small"
                v-tooltip.top="'Edit Nama'" @click="openEditDialog(data)" />
              <Button v-if="auth.isSuper" icon="pi pi-user-edit" text rounded size="small"
                v-tooltip.top="'Atur Role'" @click="openRoleDialog(data)" />
              <Button icon="pi pi-key" text rounded size="small" class="text-amber-600"
                v-tooltip.top="'Set PIN'" @click="openPinDialog(data)" />
              <Button :icon="data.is_active ? 'pi pi-ban' : 'pi pi-check-circle'"
                text rounded size="small"
                :class="data.is_active ? 'text-red-500' : 'text-teal-600'"
                v-tooltip.top="data.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                @click="toggleActive(data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Edit Name Dialog (Manager & Owner) -->
    <Dialog v-model:visible="showEditDialog" header="Edit Nama User" modal class="w-sm">
      <div v-if="selectedUser" class="space-y-4">
        <p class="text-sm text-slate-600">User: <strong>{{ selectedUser.name }}</strong></p>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Nama Baru</label>
          <InputText v-model="editForm.name" class="w-full" required />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Batal" severity="secondary" @click="showEditDialog = false" />
          <Button label="Simpan" @click="saveEdit" :loading="savingEdit" />
        </div>
      </div>
    </Dialog>

    <!-- Add User Dialog (Owner/Developer only) -->
    <Dialog v-model:visible="showAddDialog" header="Tambah User" modal class="w-md">
      <form @submit.prevent="addUser" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Nama</label>
          <InputText v-model="form.name" class="w-full" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
          <InputText v-model="form.email" type="email" class="w-full" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
          <InputText v-model="form.password" type="password" class="w-full" required />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Batal" severity="secondary" @click="showAddDialog = false" />
          <Button type="submit" label="Simpan" :loading="saving" />
        </div>
      </form>
    </Dialog>

    <!-- Assign Role Dialog (Owner/Developer only) -->
    <Dialog v-model:visible="showRoleDialog" header="Atur Role" modal class="w-md">
      <div v-if="selectedUser" class="space-y-4">
        <p class="text-sm text-slate-600">User: <strong>{{ selectedUser.name }}</strong></p>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Outlet</label>
          <Select v-model="roleForm.outlet_id" :options="myOutlets" optionLabel="name" optionValue="id"
            placeholder="Pilih outlet" class="w-full" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Role</label>
          <Select v-model="roleForm.role" :options="roleOptions" class="w-full" />
        </div>
        <div class="flex justify-between pt-2">
          <Button label="Hapus akses" severity="danger" text
            @click="removeRole" :disabled="!roleForm.outlet_id" />
          <div class="flex gap-2">
            <Button label="Batal" severity="secondary" @click="showRoleDialog = false" />
            <Button label="Simpan" @click="assignRole" :loading="savingRole" />
          </div>
        </div>
      </div>
    </Dialog>

    <!-- Set PIN Dialog -->
    <Dialog v-model:visible="showPinDialog" header="Set PIN" modal class="w-sm">
      <div v-if="selectedUser" class="space-y-4">
        <p class="text-sm text-slate-600">User: <strong>{{ selectedUser.name }}</strong></p>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">PIN (6 digit)</label>
          <InputMask v-model="pinForm.pin" mask="999999" placeholder="******"
            class="w-full text-center text-2xl" required />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Batal" severity="secondary" @click="showPinDialog = false" />
          <Button label="Simpan PIN" @click="savePin" :loading="savingPin" />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import client from '../../api/client'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputMask from 'primevue/inputmask'
import Select from 'primevue/select'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Tooltip from 'primevue/tooltip'

const auth = useAuthStore()

const users = ref([])
const outlets = ref([])
const rowsPerPage = ref(10)
const filterOutletId = ref(null)
const showAddDialog = ref(false)
const showEditDialog = ref(false)
const showRoleDialog = ref(false)
const showPinDialog = ref(false)
const selectedUser = ref(null)
const saving = ref(false)
const savingEdit = ref(false)
const savingRole = ref(false)
const savingPin = ref(false)

const form = ref({ name: '', email: '', password: '' })
const editForm = ref({ name: '' })
const roleForm = ref({ outlet_id: null, role: 'cashier' })
const pinForm = ref({ pin: '' })

const activeCount = computed(() => users.value.filter((u) => u.is_active).length)
const inactiveCount = computed(() => users.value.filter((u) => !u.is_active).length)

/** Outlets available for the current user's role */
const myOutlets = computed(() => {
  if (auth.isSuper) return outlets.value
  return outlets.value.filter((o) => auth.outletIds.includes(o.id))
})

/** Role options for assignment dropdown */
const roleOptions = computed(() => {
  const base = [
    { label: 'Kasir', value: 'cashier' },
    { label: 'Dapur', value: 'kitchen' },
    { label: 'Manager', value: 'manager' },
  ]
  if (auth.isSuper) {
    base.unshift({ label: 'Owner (Admin)', value: 'admin' })
    base.unshift({ label: 'Developer', value: 'developer' })
  }
  return base
})

function roleLabel(role) {
  const map = {
    developer: 'Developer',
    admin: 'Owner',
    manager: 'Manager',
    cashier: 'Kasir',
    kitchen: 'Dapur',
  }
  return map[role] || role
}

function roleBadgeClass(role) {
  const map = {
    developer: 'bg-purple-100 text-purple-700',
    admin: 'bg-amber-100 text-amber-700',
    manager: 'bg-blue-100 text-blue-700',
    cashier: 'bg-teal-100 text-teal-700',
    kitchen: 'bg-slate-100 text-slate-700',
  }
  return map[role] || 'bg-slate-100 text-slate-600'
}

async function fetchUsers() {
  try {
    const params = { per_page: 50 }
    if (filterOutletId.value) {
      params.outlet_id = filterOutletId.value
    }
    const { data } = await client.get('/users', { params })
    users.value = data.data
  } catch (_) {}
}

async function fetchOutlets() {
  try {
    const { data } = await client.get('/outlets')
    outlets.value = data.data
  } catch (_) {}
}

async function addUser() {
  saving.value = true
  try {
    await client.post('/auth/register', form.value)
    form.value = { name: '', email: '', password: '' }
    showAddDialog.value = false
    fetchUsers()
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal tambah user')
  } finally {
    saving.value = false
  }
}

function openEditDialog(user) {
  selectedUser.value = user
  editForm.value = { name: user.name }
  showEditDialog.value = true
}

async function saveEdit() {
  savingEdit.value = true
  try {
    await client.put(`/users/${selectedUser.value.id}`, { name: editForm.value.name })
    showEditDialog.value = false
    fetchUsers()
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal edit user')
  } finally {
    savingEdit.value = false
  }
}

function openRoleDialog(user) {
  selectedUser.value = user
  roleForm.value = { outlet_id: null, role: 'cashier' }
  showRoleDialog.value = true
}

async function assignRole() {
  savingRole.value = true
  try {
    await client.post('/user-outlets/assign', {
      user_id: selectedUser.value.id,
      outlet_id: roleForm.value.outlet_id,
      role: roleForm.value.role,
    })
    showRoleDialog.value = false
    fetchUsers()
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal assign role')
  } finally {
    savingRole.value = false
  }
}

async function removeRole() {
  if (!confirm('Hapus akses user ini ke outlet?')) return
  try {
    await client.post('/user-outlets/assign', {
      user_id: selectedUser.value.id,
      outlet_id: roleForm.value.outlet_id,
      role: roleForm.value.role,
      remove: true,
    })
    showRoleDialog.value = false
    fetchUsers()
  } catch (_) {}
}

async function toggleActive(user) {
  try {
    await client.post(`/users/${user.id}/toggle-active`)
    fetchUsers()
  } catch (_) {}
}

function openPinDialog(user) {
  selectedUser.value = user
  pinForm.value = { pin: '' }
  showPinDialog.value = true
}

async function savePin() {
  savingPin.value = true
  try {
    await client.post(`/users/${selectedUser.value.id}/pin`, pinForm.value)
    showPinDialog.value = false
    fetchUsers()
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal set PIN')
  } finally {
    savingPin.value = false
  }
}

onMounted(() => {
  fetchUsers()
  fetchOutlets()
})
</script>
