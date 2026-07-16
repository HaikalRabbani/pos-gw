<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Penarikan Dana</h1>
        <p class="text-sm text-slate-500 mt-1">Tarik saldo pembayaran ke rekening bank outlet</p>
      </div>
    </div>

    <!-- Outlet Selector -->
    <div v-if="!selectedOutletId" class="bg-white rounded-2xl border border-slate-200 p-12 flex flex-col items-center justify-center text-center">
      <i class="pi pi-building text-4xl text-slate-200 mb-3"></i>
      <p class="text-slate-600 font-semibold">Pilih outlet terlebih dahulu</p>
      <p class="text-slate-400 text-sm mt-1">Pilih outlet untuk melihat saldo dan riwayat penarikan.</p>
    </div>    <template v-else>
        <!-- Outlet Selector -->
        <div class="flex items-center justify-between gap-3">
          <Select v-if="outlets.length > 1" v-model="selectedOutletId" :options="outlets" optionLabel="name" optionValue="id"
            class="w-56" @change="fetchData" />
        </div>

        <!-- Loading -->
        <div v-if="loading" class="animate-pulse space-y-4">
          <div class="h-32 bg-slate-200 rounded-2xl"></div>
          <div class="h-64 bg-slate-100 rounded-2xl"></div>
        </div>

        <template v-else>
          <!-- Balance Card (sembunyikan kalo own key) -->
          <div v-if="outletPaymentMode !== 'own_key'" class="bg-gradient-to-br from-teal-500 to-teal-700 rounded-2xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center gap-2">
                <span class="text-sm text-teal-100">{{ currentOutletName }}</span>
              </div>
              <i class="pi pi-credit-card text-2xl text-white/60"></i>
            </div>
            <p class="text-sm text-teal-100 mb-1">Saldo QRIS Tersedia</p>
            <p class="text-4xl font-bold mb-3">{{ formatRupiah(balance?.balance || 0) }}</p>
            <div class="flex gap-6 text-sm text-teal-100">
              <span>Total ditarik: <strong class="text-white">{{ formatRupiah(balance?.total_withdrawn || 0) }}</strong></span>
            </div>
          </div>          <!-- Own Key Mode Info -->
          <div v-if="outletPaymentMode === 'own_key'" class="bg-white rounded-2xl border border-emerald-200 overflow-hidden shadow-sm">
            <div class="p-6 flex flex-col items-center text-center">
              <div class="w-14 h-14 rounded-2xl bg-emerald-100 flex items-center justify-center mb-4">
                <i class="pi pi-check-circle text-2xl text-emerald-600"></i>
              </div>
              <h3 class="text-lg font-bold text-slate-900 mb-1">Akun Pembayaran Sendiri</h3>
              <p class="text-sm text-slate-500 max-w-md">
                Outlet ini punya akun pembayaran sendiri (diatur di Pengaturan Outlet).
                Pembayaran pelanggan masuk <strong>langsung ke akun tersebut</strong> —
                <strong class="text-emerald-600">tidak perlu ditarik</strong>.
              </p>
              <p class="text-xs text-slate-400 mt-3">
                Riwayat transaksi di bawah untuk referensi saja.
              </p>
            </div>
          </div>

          <!-- Withdraw Form (platform key only) -->
          <div v-if="outletPaymentMode !== 'own_key'" class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <div class="p-4 border-b border-slate-100">
              <h2 class="font-semibold text-slate-900">Tarik Saldo</h2>
              <p class="text-xs text-slate-500 mt-0.5">Pembayaran pelanggan masuk ke saldo ini. Tarik ke rekening bank kapan saja.</p>
            </div>
            <form @submit.prevent="submitWithdraw" class="p-4 space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1">Jumlah (Rp)</label>
                  <InputNumber v-model="withdrawForm.amount" class="w-full" :min="10000" :max="balance?.balance || 0"
                    :minFractionDigits="0" />
                  <p class="text-xs text-slate-400 mt-1">Minimal Rp 10.000 | Saldo: {{ formatRupiah(balance?.balance || 0) }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1">Bank</label>
                  <Select v-model="withdrawForm.bank_name" :options="bankOptions" optionLabel="label" optionValue="value" class="w-full" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1">No. Rekening</label>
                  <InputText v-model="withdrawForm.bank_account" class="w-full" required />
                </div>
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1">Atas Nama</label>
                  <InputText v-model="withdrawForm.account_holder" class="w-full" required />
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
            <Column header="No.">
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
                  {{ data.amount > 0 ? '+' : '' }}{{ formatRupiah(data.amount) }}
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
            <Column header="No.">
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
                <span class="font-semibold text-slate-900">{{ formatRupiah(data.amount) }}</span>
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
import { formatRupiah } from '../../utils/format'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()

const outlets = ref([])
const selectedOutletId = ref(null)
const loading = ref(false)
const saving = ref(false)
const outletMap = ref({})

const balance = ref(null)
const transactions = ref([])
const withdrawals = ref([])

const outletPaymentMode = computed(() => {
  if (!selectedOutletId.value) return null
  const outlet = outletMap.value[selectedOutletId.value]
  if (!outlet) return null
  return outlet.midtrans_server_key ? 'own_key' : 'platform_key'
})

const currentOutletName = computed(() => {
  const outlet = outletMap.value[selectedOutletId.value]
  return outlet?.name || 'Outlet'
})

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
    // Build outlet map for quick lookup
    data.data.forEach(o => { outletMap.value[o.id] = o })
    if (data.data.length > 0) {
      selectedOutletId.value = data.data[0].id
      fetchData()
    }
  } catch (_) {}
})
</script>
