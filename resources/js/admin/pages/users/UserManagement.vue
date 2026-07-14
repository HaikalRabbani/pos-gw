<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Manajemen User</h1>
        <p class="text-sm text-slate-500 mt-1">Kelola karyawan dan akses outlet</p>
      </div>
      <Button label="Tambah User" icon="pi pi-plus" @click="showAddDialog = true" />
    </div>

    <div class="grid grid-cols-3 gap-4">
      <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-sm text-slate-500">Total User</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ users.length }}</p>
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
      <div class="p-3 border-b border-slate-100 flex items-center gap-2">
        <span class="text-sm text-slate-500">Tampilkan</span>
        <Select v-model="rowsPerPage" :options="[5, 10, 20, 50]" class="w-20" />
        <span class="text-sm text-slate-500">data</span>
      </div>
      <DataTable :value="users" paginator :rows="rowsPerPage"
        paginatorTemplate="CurrentPageReport PrevPageLink NextPageLink"
        currentPageReportTemplate="Halaman {currentPage} dari {totalPages}"
        class="text-sm">
        <template #empty>Belum ada data user.</template>
        <Column field="name" header="Nama" sortable />
        <Column field="email" header="Email" sortable />
        <Column header="Outlet & Role">
          <template #body="{ data }">
            <div class="flex flex-wrap gap-1">
              <Tag v-if="!data.outlets?.length" value="-" severity="info" rounded />
              <Tag v-for="o in data.outlets" :key="o.id" rounded>
                {{ o.name }}: <span class="font-semibold">{{ o.pivot.role }}</span>
              </Tag>
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
        <Column header="Aksi" style="width: 200px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-user-edit" text rounded size="small"
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

    <!-- Add User Dialog -->
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

    <!-- Assign Role Dialog -->
    <Dialog v-model:visible="showRoleDialog" header="Atur Role" modal class="w-md">
      <div v-if="selectedUser" class="space-y-4">
        <p class="text-sm text-slate-600">User: <strong>{{ selectedUser.name }}</strong></p>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Outlet</label>
          <Select v-model="roleForm.outlet_id" :options="outlets" optionLabel="name" optionValue="id"
            placeholder="Pilih outlet" class="w-full" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Role</label>
          <Select v-model="roleForm.role" :options="roles" class="w-full" />
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

const users = ref([])
const outlets = ref([])
const rowsPerPage = ref(10)
const showAddDialog = ref(false)
const showRoleDialog = ref(false)
const showPinDialog = ref(false)
const selectedUser = ref(null)
const saving = ref(false)
const savingRole = ref(false)
const savingPin = ref(false)

const form = ref({ name: '', email: '', password: '' })
const roleForm = ref({ outlet_id: null, role: 'cashier' })
const pinForm = ref({ pin: '' })
const roles = ['admin', 'cashier', 'kitchen']

const activeCount = computed(() => users.value.filter((u) => u.is_active).length)
const inactiveCount = computed(() => users.value.filter((u) => !u.is_active).length)

async function fetchUsers() {
  try {
    const { data } = await client.get('/users', { params: { per_page: 50 } })
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
