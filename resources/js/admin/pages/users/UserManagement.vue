<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Manajemen Pengguna</h1>
        <p class="text-sm text-slate-500 mt-1">Kelola karyawan dan akses outlet</p>
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
      <div class="p-3 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2">
          <span class="text-sm text-slate-500">Tampilkan</span>
          <Select v-model="rowsPerPage" :options="[5, 10, 20, 50]" class="w-20" />
          <span class="text-sm text-slate-500">data</span>
        </div>
        <div class="flex items-center gap-2">
          <Select v-model="filterOutletId" :options="myOutlets"
            optionLabel="name" optionValue="id" placeholder="Semua outlet" class="w-44" showClear
            @change="fetchUsers" />
          <Button v-if="auth.isSuper" label="Tambah User" size="small"
            @click="showAddDialog = true">
            <template #icon>
              <Plus class="w-4 h-4" stroke-width="1.5" />
            </template>
          </Button>
        </div>
      </div>
      <div class="overflow-x-auto">
      <DataTable :value="users" paginator :rows="rowsPerPage" stripedRows size="small"
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
              <Users class="w-6 h-6 text-slate-300" stroke-width="1.5" />
            </div>
            <p class="text-slate-500 font-medium">Belum ada data user</p>
            <p class="text-slate-400 text-xs mt-1">Tambahkan user baru untuk memulai</p>
          </div>
        </template>
        <Column header="Foto" style="width: 60px">
          <template #body="{ data }">
            <img v-if="data.photo" :src="data.photo" class="w-8 h-8 rounded-full object-cover border border-slate-200" />
            <div v-else class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center">
              <User class="w-4 h-4 text-slate-400" stroke-width="1.5" />
            </div>
          </template>
        </Column>
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
            <div class="flex gap-3">
              <Button text rounded size="small"
                v-tooltip.top="'Edit'"
                @click="openEditDialog(data)">
                <template #icon>
                  <Pencil class="w-4 h-4" stroke-width="1.5" />
                </template>
              </Button>
              <Button v-if="auth.isSuper" text rounded size="small"
                v-tooltip.top="'Atur Role'" @click="openRoleDialog(data)">
                <template #icon>
                  <UserPen class="w-4 h-4" stroke-width="1.5" />
                </template>
              </Button>
              <Button text rounded size="small" class="text-amber-600"
                v-tooltip.top="'Set PIN'" @click="openPinDialog(data)">
                <template #icon>
                  <KeyRound class="w-4 h-4" stroke-width="1.5" />
                </template>
              </Button>
              <Button text rounded size="small"
                :class="data.is_active ? 'text-red-500' : 'text-teal-600'"
                v-tooltip.top="data.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                @click="toggleActive(data)">
                <template #icon>
                  <component :is="data.is_active ? Ban : CheckCircle" class="w-4 h-4" stroke-width="1.5" />
                </template>
              </Button>
            </div>
          </template>
        </Column>
      </DataTable>
      </div>
    </div>

    <!-- Edit User Dialog (Nama + Foto) -->
    <Dialog v-model:visible="showEditDialog" header="Edit User" modal class="w-md">
      <form @submit.prevent="saveEdit" class="space-y-4">
        <div v-if="selectedUser" class="space-y-4">
          <!-- Foto & Preview -->
          <div class="flex flex-col items-center gap-3 pb-2">
            <div class="relative">
              <img v-if="photoPreview || selectedUser.photo" :src="photoPreview || selectedUser.photo"
                class="w-20 h-20 rounded-full object-cover border-2 border-slate-200" />
              <div v-else class="w-20 h-20 rounded-full bg-slate-100 border-2 border-slate-200 flex items-center justify-center">
                <User class="w-6 h-6 text-slate-400" stroke-width="1.5" />
              </div>
            </div>
            <p class="text-sm text-slate-600 font-medium">{{ selectedUser.name }}</p>
            <input type="file" accept="image/*" class="text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100"
              @change="onFileSelect" />
            <div v-if="uploadError" class="text-xs text-red-500">{{ uploadError }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nama</label>
            <InputText v-model="editForm.name" class="w-full" required />
          </div>
          <div class="flex justify-end gap-2 pt-2">
            <Button label="Batal" severity="secondary" @click="showEditDialog = false" />
            <Button type="submit" label="Simpan" :loading="savingEdit" />
          </div>
        </div>
      </form>
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
          <Select v-model="roleForm.role" :options="roleOptions" optionLabel="label" optionValue="value" class="w-full" />
        </div>
        <div class="flex justify-between pt-2">
          <Button label="Hapus akses" severity="danger" text
            @click="showRemoveConfirm = true" :disabled="!roleForm.outlet_id" />
          <div class="flex gap-2">
            <Button label="Batal" severity="secondary" @click="showRoleDialog = false" />
            <Button label="Simpan" @click="assignRole" :loading="savingRole" />
          </div>
        </div>
      </div>
    </Dialog>

    <!-- Remove Role Confirm Dialog -->
    <Dialog v-model:visible="showRemoveConfirm" header="Hapus Akses" modal class="w-sm">
      <div class="space-y-3">
        <div class="flex items-center gap-3 p-3 rounded-xl bg-red-50 border border-red-100">
          <AlertTriangle class="w-5 h-5 text-red-500 shrink-0" stroke-width="1.5" />
          <p class="text-sm text-red-700">
            Yakin ingin menghapus akses <strong>{{ selectedUser?.name }}</strong> dari outlet ini?
          </p>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Batal" severity="secondary" @click="showRemoveConfirm = false" />
          <Button label="Hapus" severity="danger" @click="removeRole" />
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
import { useToastStore } from '../../stores/toast'
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
import { roleLabel, roleBadgeClass } from '../../utils/roleBadge'
import {
  Plus, Users, User, Pencil, UserPen, KeyRound,
  Ban, CheckCircle, AlertTriangle
} from 'lucide-vue-next'

const auth = useAuthStore()
const toast = useToastStore()

const loading = ref(true)
const users = ref([])
const outlets = ref([])
const rowsPerPage = ref(10)
const filterOutletId = ref(null)
const showAddDialog = ref(false)
const showEditDialog = ref(false)
const showRoleDialog = ref(false)
const showRemoveConfirm = ref(false)
const showPinDialog = ref(false)
const selectedUser = ref(null)
const saving = ref(false)
const savingEdit = ref(false)
const savingRole = ref(false)
const savingPin = ref(false)
const selectedFile = ref(null)
const photoPreview = ref(null)
const uploadError = ref('')

const form = ref({ name: '', email: '', password: '' })
const editForm = ref({ name: '' })
const roleForm = ref({ outlet_id: null, role: 'cashier' })
const pinForm = ref({ pin: '' })

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
  if (auth.highestRole === 'developer') {
    base.unshift({ label: 'Owner (Admin)', value: 'admin' })
    base.unshift({ label: 'Developer', value: 'developer' })
  } else if (auth.isSuper) {
    base.unshift({ label: 'Owner (Admin)', value: 'admin' })
  }
  return base
})

async function fetchUsers() {
  loading.value = true
  try {
    const params = { per_page: 50 }
    if (filterOutletId.value) {
      params.outlet_id = filterOutletId.value
    }
    const { data } = await client.get('/users', { params })
    users.value = data.data.filter(u => u.id !== auth.user?.id)
  } catch (_) {
  } finally {
    loading.value = false
  }
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
    toast.error('Gagal Tambah User', e.response?.data?.message || 'Gagal tambah user')
  } finally {
    saving.value = false
  }
}

function openEditDialog(user) {
  selectedUser.value = user
  editForm.value = { name: user.name }
  selectedFile.value = null
  photoPreview.value = null
  uploadError.value = ''
  showEditDialog.value = true
}

async function saveEdit() {
  savingEdit.value = true
  try {
    let photoUrl = selectedUser.value.photo
    if (selectedFile.value) {
      const formData = new FormData()
      formData.append('file', selectedFile.value)
      const { data } = await client.post('/upload', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      photoUrl = data.url
    }
    await client.put(`/users/${selectedUser.value.id}`, {
      name: editForm.value.name,
      photo: photoUrl,
    })
    showEditDialog.value = false
    fetchUsers()
  } catch (e) {
    toast.error('Gagal Edit User', e.response?.data?.message || 'Gagal edit user')
  } finally {
    savingEdit.value = false
  }
}

function onFileSelect(e) {
  const file = e.target.files[0]
  if (!file) return
  if (!file.type.startsWith('image/')) {
    uploadError.value = 'File harus berupa gambar'
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    uploadError.value = 'Maksimal ukuran file 2MB'
    return
  }
  selectedFile.value = file
  uploadError.value = ''
  const reader = new FileReader()
  reader.onload = (ev) => { photoPreview.value = ev.target.result }
  reader.readAsDataURL(file)
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
    toast.error('Gagal Assign Role', e.response?.data?.message || 'Gagal assign role')
  } finally {
    savingRole.value = false
  }
}

async function removeRole() {
  showRemoveConfirm.value = false
  showRoleDialog.value = false
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
    toast.error('Gagal Set PIN', e.response?.data?.message || 'Gagal set PIN')
  } finally {
    savingPin.value = false
  }
}

onMounted(() => {
  fetchUsers()
  fetchOutlets()
})
</script>
