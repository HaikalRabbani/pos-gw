<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Penarikan Saldo</h1>
        <p class="text-sm text-slate-500 mt-1">Kelola saldo QRIS dan penarikan dana otomatis</p>
      </div>
    </div>

    <!-- Outlet Selector -->
    <div v-if="!selectedOutletId" class="bg-white rounded-2xl border border-slate-200 p-12 flex flex-col items-center justify-center text-center">
      <i class="pi pi-building text-4xl text-slate-200 mb-3"></i>
      <p class="text-slate-600 font-semibold">Pilih outlet terlebih dahulu</p>
      <p class="text-slate-400 text-sm mt-1">Pilih outlet untuk melihat saldo dan riwayat penarikan.</p>
    </div>

    <template v-else>
      <!-- Loading -->
      <div v-if="loading" class="animate-pulse space-y-4">
        <div class="h-32 bg-slate-200 rounded-2xl"></div>
        <div class="h-64 bg-slate-100 rounded-2xl"></div>
      </div>

      <template v-else>
        <!-- Balance Card -->
        <div class="bg-gradient-to-br from-teal-500 to-teal-700 rounded-2xl p-6 text-white shadow-lg">
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
              <Select v-model="selectedOutletId" :options="outlets" optionLabel="name" optionValue="id"
                class="w-44 bg-white/20 border-white/30 text-white [&_.p-select-label]:text-white [&_.p-select-trigger]:text-white/70"
                @change="fetchData" />
            </div>
            <i class="pi pi-credit-card text-2xl text-white/60"></i>
          </div>
          <p class="text-sm text-teal-100 mb-1">Saldo QRIS Tersedia</p>
          <p class="text-4xl font-bold mb-3">{{ formatPrice(balance?.balance || 0) }}</p>
          <div class="flex gap-6 text-sm text-teal-100">
            <span>Total ditarik: <strong class="text-white">{{ formatPrice(balance?.total_withdrawn || 0) }}</strong></span>
          </div>
        </div>

        <!-- Withdraw Form -->
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
          <div class="p-4 border-b border-slate-100">
            <h2 class="font-semibold text-slate-900">Tarik Saldo</h2>
            <p class="text-xs text-slate-500 mt-0.5">Dana akan otomatis dikirim ke rekening bank Anda</p>
          </div>
          <form @submit.prevent="submitWithdraw" class="p-4 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Jumlah (Rp)</label>
                <InputNumber v-model="withdrawForm.amount" class="w-full" :min="10000" :max="balance?.balance || 0"
                  :minFractionDigits="0" placeholder="100000" />
                <p class="text-xs text-slate-400 mt-1">Minimal Rp 10.000 | Saldo: {{ formatPrice(balance?.balance || 0) }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Bank</label>
                <Select v-model="withdrawForm.bank_name" :options="bankOptions" class="w-full" placeholder="Pilih bank" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">No. Rekening</label>
                <InputText v-model="withdrawForm.bank_account" class="w-full" placeholder="1234567890" required />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Atas Nama</label>
                <InputText v-model="withdrawForm.account_holder" class="w-full" placeholder="PT Maju Jaya" required />
              </div>
            </div>
            <div class="flex justify-end">
              <Button type="submit" label="Tarik Saldo" icon="pi pi-send" :loading="saving"
                :disabled="!withdrawForm.amount || !withdrawForm.bank_account || !withdrawForm.account_holder" />
            </div>
          </form>
        </div>

        <!-- Transaction History -->
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
          <div class="p-4 border-b border-slate-100">
            <h2 class="font-semibold text-slate-900">Riwayat Transaksi</h2>
          </div>
          <DataTable :value="transactions" size="small" stripedRows class="text-sm">
            <Column header="#">
              <template #body="{ index }">
                <span class="text-slate-400 text-xs font-mono">{{ index + 1 }}</span>
              </template>
            </Column>
            <template #empty>
              <div class="flex flex-col items-center justify-center py-12 text-center">
                <i class="pi pi-history text-3xl text-slate-200 mb-2"></i>
                <p class="text-slate-500 text-sm">Belum ada transaksi</p>
              </div>
            </template>
            <Column header="Tipe">
              <template #body="{ data }">
                <Tag :value="data.type === 'qris_payment' ? 'Pembayaran QRIS' : 'Penarikan'"
                  :severity="data.type === 'qris_payment' ? 'success' : 'info'" rounded />
              </template>
            </Column>
            <Column header="Jumlah">
              <template #body="{ data }">
                <span :class="data.amount > 0 ? 'text-teal-600' : 'text-red-500'"
                  class="font-semibold">
                  {{ data.amount > 0 ? '+' : '' }}{{ formatPrice(data.amount) }}
                </span>
              </template>
            </Column>
            <Column field="description" header="Keterangan" />
            <Column field="created_at" header="Tanggal">
              <template #body="{ data }">
                <span class="text-xs text-slate-500">{{ formatDate(data.created_at) }}</span>
              </template>
            </Column>
          </DataTable>
        </div>

        <!-- Withdrawal History -->
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
          <div class="p-4 border-b border-slate-100">
            <h2 class="font-semibold text-slate-900">Riwayat Penarikan</h2>
          </div>
          <DataTable :value="withdrawals" size="small" stripedRows class="text-sm">
            <Column header="#">
              <template #body="{ index }">
                <span class="text-slate-400 text-xs font-mono">{{ index + 1 }}</span>
              </template>
            </Column>
            <template #empty>
              <div class="flex flex-col items-center justify-center py-12 text-center">
                <i class="pi pi-inbox text-3xl text-slate-200 mb-2"></i>
                <p class="text-slate-500 text-sm">Belum ada penarikan</p>
              </div>
            </template>
            <Column header="Jumlah">
              <template #body="{ data }">
                <span class="font-semibold text-slate-900">{{ formatPrice(data.amount) }}</span>
              </template>
            </Column>
            <Column header="Rekening Tujuan">
              <template #body="{ data }">
                <span class="text-sm">{{ data.bank_name }} {{ data.bank_account }}</span>
                <br>
                <span class="text-xs text-slate-500">a.n. {{ data.account_holder }}</span>
              </template>
            </Column>
            <Column header="Status">
              <template #body="{ data }">
                <Tag :value="data.status === 'completed' ? 'Berhasil' : 'Gagal'"
                  :severity="data.status === 'completed' ? 'success' : 'danger'" rounded />
              </template>
            </Column>
            <Column header="Tanggal">
              <template #body="{ data }">
                <span class="text-xs text-slate-500">{{ formatDate(data.created_at) }}</span>
              </template>
            </Column>
          </DataTable>
        </div>
      </template>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import client from '../../api/client'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()

const outlets = ref([])
const selectedOutletId = ref(null)
const loading = ref(false)
const saving = ref(false)

const balance = ref(null)
const transactions = ref([])
const withdrawals = ref([])

const withdrawForm = ref({
  amount: null,
  bank_name: '',
  bank_account: '',
  account_holder: '',
})

const bankOptions = [
  { label: 'BCA', value: 'BCA' },
  { label: 'Mandiri', value: 'MANDIRI' },
  { label: 'BNI', value: 'BNI' },
  { label: 'BRI', value: 'BRI' },
  { label: 'BSI', value: 'BSI' },
  { label: 'CIMB Niaga', value: 'CIMB' },
  { label: 'Permata', value: 'PERMATA' },
  { label: 'Danamon', value: 'DANAMON' },
  { label: 'Maybank', value: 'MAYBANK' },
  { label: 'Bank Lain', value: 'OTHER' },
]

function formatPrice(cents) {
  return 'Rp ' + Math.abs(cents / 100).toLocaleString('id-ID')
}

function formatDate(date) {
  if (!date) return ''
  const d = new Date(date)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

async function fetchData() {
  if (!selectedOutletId.value) return
  loading.value = true
  try {
    const [balRes, txRes, wdRes] = await Promise.all([
      client.get('/withdraw/balance', { params: { outlet_id: selectedOutletId.value } }),
      client.get('/withdraw/transactions', { params: { outlet_id: selectedOutletId.value } }),
      client.get('/withdraw/withdrawals', { params: { outlet_id: selectedOutletId.value } }),
    ])
    balance.value = balRes.data.data
    transactions.value = txRes.data.data
    withdrawals.value = wdRes.data.data
  } catch (_) {}
  finally { loading.value = false }
}

async function submitWithdraw() {
  saving.value = true
  try {
    const { data } = await client.post('/withdraw/withdraw', {
      outlet_id: selectedOutletId.value,
      amount: withdrawForm.value.amount,
      bank_name: withdrawForm.value.bank_name,
      bank_account: withdrawForm.value.bank_account,
      account_holder: withdrawForm.value.account_holder,
    })
    if (data.success) {
      withdrawForm.value = { amount: null, bank_name: '', bank_account: '', account_holder: '' }
      fetchData()
    }
  } catch (e) {
    alert(e.response?.data?.message || 'Gagal memproses penarikan')
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  try {
    const { data } = await client.get('/outlets')
    outlets.value = data.data
    if (data.data.length > 0) {
      selectedOutletId.value = data.data[0].id
      fetchData()
    }
  } catch (_) {}
})
</script>
