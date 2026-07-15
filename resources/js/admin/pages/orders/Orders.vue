<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-slate-900">Pesanan</h1>
      <p class="text-sm text-slate-500 mt-1">Daftar dan kelola transaksi</p>
    </div>

    <!-- Filter Bar -->
    <div class="flex flex-wrap items-center gap-3">
      <Select v-model="filterStatus" :options="statusOptions" optionLabel="label" optionValue="value"
        placeholder="Semua Status" class="w-44" @change="fetchOrders" />
      <Select v-model="filterPayment" :options="paymentOptions" optionLabel="label" optionValue="value"
        placeholder="Semua Pembayaran" class="w-48" @change="fetchOrders" />
      <Button label="Refresh" icon="pi pi-refresh" severity="secondary" text size="small" @click="fetchOrders" />
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
      <DataTable :value="orders" paginator :rows="rowsPerPage" stripedRows size="small"
        paginatorTemplate="CurrentPageReport PrevPageLink NextPageLink"
        currentPageReportTemplate="Halaman {currentPage} dari {totalPages}"
        sortField="created_at" :sortOrder="-1"
        class="text-sm" @row-click="openDetail">
        <template #empty>
          <div class="flex flex-col items-center justify-center py-16 text-center">
            <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
              <i class="pi pi-receipt text-2xl text-slate-300"></i>
            </div>
            <p class="text-slate-500 font-medium">Belum ada pesanan</p>
            <p class="text-slate-400 text-xs mt-1">Pesanan baru akan muncul di sini</p>
          </div>
        </template>
        <Column header="#" style="width: 50px">
          <template #body="{ index }">
            <span class="text-slate-400 text-xs font-mono">{{ index + 1 }}</span>
          </template>
        </Column>
        <Column field="id" header="ID" sortable style="width: 80px" />
        <Column field="customer_name" header="Pelanggan" sortable>
          <template #body="{ data }">
            <span>{{ data.customer_name || '—' }}</span>
          </template>
        </Column>
        <Column field="status" header="Status" sortable style="width: 130px">
          <template #body="{ data }">
            <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" rounded />
          </template>
        </Column>
        <Column field="grand_total" header="Total" sortable style="width: 140px">
          <template #body="{ data }">
            <span class="font-semibold text-slate-900">Rp {{ formatRupiah(data.grand_total) }}</span>
          </template>
        </Column>
        <Column field="payment_status" header="Pembayaran" sortable style="width: 130px">
          <template #body="{ data }">
            <Tag :value="paymentLabel(data)" :severity="paymentSeverity(data)" rounded />
          </template>
        </Column>
        <Column field="created_at" header="Tanggal" sortable style="width: 170px">
          <template #body="{ data }">
            <span class="text-slate-500 text-xs">{{ formatDate(data.created_at) }}</span>
          </template>
        </Column>
        <Column header="Aksi" style="width: 60px">
          <template #body="{ data }">
            <div @click.stop>
              <Button icon="pi pi-eye" severity="secondary" text rounded size="small"
                v-tooltip.top="'Detail Pesanan'" @click="openDetail(data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Detail + Edit Dialog (gabungan) -->
    <Dialog v-model:visible="dialogVisible" :header="'Pesanan #' + selectedOrder?.id" modal
      class="w-full max-w-3xl" :closable="true">
      <div v-if="selectedOrder" class="space-y-5 max-h-[70vh] overflow-y-auto pr-1">

        <!-- Info cards — dari kiri, masing-masing card sendiri -->
        <div class="flex flex-wrap items-start gap-3">
          <div class="bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-sm min-w-[130px]">
            <p class="text-xs text-slate-400 mb-0.5">Pelanggan</p>
            <p class="font-medium text-slate-800">{{ selectedOrder.customer_name || '—' }}</p>
          </div>
          <div class="bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-sm min-w-[100px]">
            <p class="text-xs text-slate-400 mb-0.5">Meja</p>
            <p class="font-medium text-slate-800">{{ selectedOrder.table?.name || '—' }}</p>
          </div>
          <div class="bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-sm min-w-[130px]">
            <p class="text-xs text-slate-400 mb-0.5">Kasir</p>
            <p class="font-medium text-slate-800">{{ selectedOrder.user?.name || '—' }}</p>
          </div>
          <template v-for="payment in selectedOrder.payments" :key="payment.id">
            <div class="bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-sm min-w-[130px]">
              <p class="text-xs text-slate-400 capitalize mb-0.5">{{ payment.method }}</p>
              <p class="font-semibold text-slate-900">Rp {{ formatRupiah(payment.amount) }}</p>
              <p v-if="payment.refunded_amount > 0" class="text-[10px] text-orange-500 mt-0.5">
                refund Rp {{ formatRupiah(payment.refunded_amount) }}
              </p>
            </div>
          </template>
          <div v-if="!selectedOrder.payments?.length && selectedOrder.payment_status === 'pending'" class="bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-sm min-w-[130px]">
            <p class="text-xs font-medium text-amber-600">Belum dibayar</p>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-2">
          <Button v-if="canVoid(selectedOrder)" label="Void" icon="pi pi-ban" severity="danger" text size="small"
            :loading="voidLoading" @click="voidOrder" />
          <Button v-if="canRefund(selectedOrder) && !refundMode" label="Refund" icon="pi pi-undo" severity="warning" text size="small"
            @click="enableRefundMode" />
          <Button v-if="refundMode" label="Batal Refund" icon="pi pi-times" severity="secondary" text size="small"
            @click="disableRefundMode" />
        </div>

        <!-- Items (read-only) — sembunyi kalo refund mode aktif -->
        <div v-if="!refundMode">
          <h3 class="text-sm font-semibold text-slate-800 mb-2">Item Pesanan</h3>
          <div class="space-y-2">
            <div v-for="item in selectedOrder.items" :key="item.id"
              class="border border-slate-100 rounded-xl p-3">
              <p class="text-sm font-medium text-slate-800">{{ item.product_name }}</p>
              <p v-if="item.notes" class="text-xs text-slate-500 italic mt-0.5">{{ item.notes }}</p>
              <div class="flex items-center gap-1.5 mt-1 text-xs text-slate-500">
                <span>x{{ item.qty }}</span>
                <span>@ Rp {{ formatRupiah(item.unit_price) }}</span>
                <span class="font-medium text-slate-700">= Rp {{ formatRupiah(item.total_price) }}</span>
                <span v-if="item.refunded_qty > 0" class="ml-auto text-orange-500">Refund {{ item.refunded_qty }}x</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Refund Controls (muncul setelah klik tombol Refund) -->
        <div v-if="refundMode" class="space-y-3 pt-2 border-t border-slate-200">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-800">Refund Item</h3>
            <span class="text-xs text-slate-400">Total dibayar: Rp {{ formatRupiah(totalPaid) }}</span>
          </div>

          <div class="space-y-2">
            <div v-for="item in refundableItems" :key="item.id"
              class="flex items-center gap-3 p-3 rounded-xl border border-slate-200"
              :class="{ 'bg-blue-50 border-blue-200': refundQtys[item.id] > 0 }">
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-800 truncate">{{ item.product_name }}</p>
                <p class="text-xs text-slate-400">
                  Pesan {{ item.qty }}x @ Rp {{ formatRupiah(item.unit_price) }}
                  <span v-if="item.refunded_qty > 0" class="text-orange-500">
                    — sudah refund {{ item.refunded_qty }}
                  </span>
                </p>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <button @click="adjustRefund(item.id, -1)"
                  :disabled="(refundQtys[item.id] || 0) <= 0"
                  class="w-7 h-7 rounded-lg flex items-center justify-center text-sm font-bold transition-all"
                  :class="(refundQtys[item.id] || 0) > 0
                    ? 'bg-red-100 text-red-600 hover:bg-red-200 active:scale-95'
                    : 'bg-slate-100 text-slate-300 cursor-not-allowed'">
                  -
                </button>
                <span class="w-6 text-center font-bold text-base tabular-nums text-slate-800">
                  {{ refundQtys[item.id] || 0 }}
                </span>
                <button @click="adjustRefund(item.id, 1)"
                  :disabled="(refundQtys[item.id] || 0) >= item.refundable_qty"
                  class="w-7 h-7 rounded-lg flex items-center justify-center text-sm font-bold transition-all"
                  :class="(refundQtys[item.id] || 0) < item.refundable_qty
                    ? 'bg-emerald-100 text-emerald-600 hover:bg-emerald-200 active:scale-95'
                    : 'bg-slate-100 text-slate-300 cursor-not-allowed'">
                  +
                </button>
                <span class="text-xs text-slate-400 w-8 text-right">/{{ item.refundable_qty }}</span>
              </div>
            </div>
          </div>

          <!-- Total Refund + Alasan + Proses -->
          <div v-if="totalRefundAmount > 0" class="bg-teal-50 border border-teal-200 rounded-xl p-3">
            <div class="flex items-center justify-between mb-3">
              <span class="text-sm font-semibold text-teal-800">Total Refund</span>
              <span class="text-lg font-bold text-teal-700">Rp {{ formatRupiah(totalRefundAmount) }}</span>
            </div>
            <div class="space-y-1">
              <label class="text-xs font-medium text-teal-700">Alasan Refund</label>
              <Textarea v-model="refundReason" rows="1" class="w-full text-sm" placeholder="Alasan refund (wajib diisi)" />
            </div>
            <div class="flex justify-end gap-2 mt-3">
              <Button label="Proses Refund" icon="pi pi-undo" severity="danger" size="small"
                :loading="refundLoading" @click="processRefund" />
            </div>
          </div>
        </div>



        <!-- Aktivitas (log + refund info, semuanya di sini) -->
        <div>
          <h3 class="text-sm font-semibold text-slate-800 mb-2">Aktivitas</h3>
          <div class="space-y-1.5">
            <div v-for="log in selectedOrder.logs" :key="log.id"
              class="flex items-start gap-2 text-xs text-slate-500">
              <i class="pi pi-circle-fill text-[6px] mt-1.5 text-slate-300 shrink-0"></i>
              <div class="flex-1">
                <span class="text-slate-700">{{ log.user?.name || 'System' }}</span>
                <span v-if="log.note?.startsWith('refund:')" class="text-orange-600 ml-1">{{ log.note }}</span>
                <span v-else class="ml-1">
                  {{ statusLabel(log.to_status) }}
                  <span v-if="log.from_status">(dari {{ statusLabel(log.from_status) }})</span>
                </span>
                <span class="ml-2 text-slate-400">{{ formatDateTime(log.created_at) }}</span>
              </div>
            </div>
            <!-- Tidak ada log sama sekali? -->
            <div v-if="!selectedOrder.logs?.length" class="text-xs text-slate-400 italic">
              Belum ada aktivitas
            </div>
          </div>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import client from '../../api/client'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'

const orders = ref([])
const rowsPerPage = ref(10)

// Filters
const filterStatus = ref('')
const filterPayment = ref('')
const statusOptions = [
  { label: 'Semua Status', value: '' },
  { label: 'Draft', value: 'draft' },
  { label: 'Dikonfirmasi', value: 'confirmed' },
  { label: 'Dimasak', value: 'preparing' },
  { label: 'Selesai', value: 'done' },
  { label: 'Dibatalkan', value: 'cancelled' },
  { label: 'Void', value: 'voided' },
]
const paymentOptions = [
  { label: 'Semua Pembayaran', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Lunas', value: 'paid' },
  { label: 'Refund', value: 'refunded' },
]

// Dialog gabungan (detail + refund)
const dialogVisible = ref(false)
const selectedOrder = ref(null)
const refundMode = ref(false)
const refundQtys = ref({})
const refundReason = ref('')
const refundLoading = ref(false)
const voidLoading = ref(false)

const totalPaid = computed(() => {
  if (!selectedOrder.value?.payments) return 0
  return selectedOrder.value.payments.reduce((sum, p) => sum + p.amount, 0)
})

const refundableItems = computed(() => {
  return (selectedOrder.value?.items || []).filter(i => i.refundable_qty > 0)
})

const totalRefundAmount = computed(() => {
  if (!selectedOrder.value?.items) return 0
  let total = 0
  for (const item of selectedOrder.value.items) {
    const qty = refundQtys.value[item.id] || 0
    if (qty > 0) {
      total += Math.round((item.total_price / item.qty) * qty)
    }
  }
  return total
})

function canRefund(order) {
  return order.payment_status === 'paid' && order.refund_status !== 'full'
}

function canVoid(order) {
  return ['draft', 'confirmed'].includes(order.status)
}

function enableRefundMode() {
  // Init refund qty = refundable (full all)
  const qtyMap = {}
  for (const item of selectedOrder.value.items) {
    qtyMap[item.id] = item.refundable_qty
  }
  refundQtys.value = qtyMap
  refundReason.value = ''
  refundMode.value = true
}

function disableRefundMode() {
  refundMode.value = false
  refundQtys.value = {}
  refundReason.value = ''
}

async function voidOrder() {
  if (!confirm('Yakin ingin void pesanan ini?')) return
  voidLoading.value = true
  try {
    await client.put(`/orders/${selectedOrder.value.id}/status`, { status: 'voided' })
    alert('Pesanan telah di-void')
    dialogVisible.value = false
    await fetchOrders()
  } catch (err) {
    alert(err.response?.data?.message || 'Gagal void pesanan')
  } finally {
    voidLoading.value = false
  }
}

function adjustRefund(itemId, delta) {
  const current = refundQtys.value[itemId] || 0
  const item = selectedOrder.value?.items?.find(i => i.id === itemId)
  if (!item) return
  const max = item.refundable_qty
  const next = Math.max(0, Math.min(max, current + delta))
  refundQtys.value = { ...refundQtys.value, [itemId]: next }
}

function statusLabel(s) {
  const map = { draft: 'Draft', confirmed: 'Dikonfirmasi', preparing: 'Dimasak', done: 'Selesai', cancelled: 'Batal', voided: 'Void' }
  return map[s] || s
}

function statusSeverity(s) {
  const map = { draft: 'info', confirmed: 'warn', preparing: 'warn', done: 'success', cancelled: 'danger', voided: 'danger' }
  return map[s] || 'info'
}

function paymentLabel(order) {
  if (order.refund_status === 'full') return 'Refund Full'
  if (order.refund_status === 'partial') return 'Refund Sebagian'
  if (order.payment_status === 'paid') return 'Lunas'
  return 'Pending'
}

function paymentSeverity(order) {
  if (order.refund_status === 'full') return 'danger'
  if (order.refund_status === 'partial') return 'warn'
  if (order.payment_status === 'paid') return 'success'
  return 'warning'
}

function formatRupiah(val) {
  if (!val && val !== 0) return '0'
  return Math.round(val / 100).toLocaleString('id-ID')
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

function formatDateTime(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit', second: '2-digit',
  })
}

async function fetchOrders() {
  try {
    const { data: outletRes } = await client.get('/outlets')
    const outletId = outletRes.data[0]?.id
    if (!outletId) return

    const params = { outlet_id: outletId, per_page: 50 }
    if (filterStatus.value) params.status = filterStatus.value
    if (filterPayment.value) params.payment_status = filterPayment.value

    if (filterPayment.value === 'refunded') {
      delete params.payment_status
    }

    const { data } = await client.get('/orders', { params })
    let result = data.data
    if (filterPayment.value === 'refunded') {
      result = result.filter(o => o.refund_status)
    }
    orders.value = result
  } catch (_) {}
}

async function openDetail(order) {
  try {
    const { data } = await client.get(`/orders/${order.id}`)
    const fullOrder = data.data
    selectedOrder.value = fullOrder

    refundMode.value = false
    refundQtys.value = {}
    refundReason.value = ''
    refundLoading.value = false
    dialogVisible.value = true
  } catch (_) {
    alert('Gagal memuat detail pesanan')
  }
}

async function processRefund() {
  const items = []
  for (const item of selectedOrder.value.items) {
    const qty = refundQtys.value[item.id] || 0
    if (qty > 0 && qty <= item.refundable_qty) {
      items.push({ order_item_id: item.id, qty })
    }
  }
  if (items.length === 0) {
    alert('Pilih minimal 1 item untuk di-refund')
    return
  }

  refundLoading.value = true
  try {
    await client.post(`/orders/${selectedOrder.value.id}/refund`, {
      items,
      reason: refundReason.value,
    })
    alert(`Refund berhasil — ${items.length} item`)
    dialogVisible.value = false
    await fetchOrders()
  } catch (err) {
    const msg = err.response?.data?.message || err.response?.data?.errors?.reason?.[0] || err.response?.data?.errors?.items?.[0] || 'Gagal memproses refund'
    alert(msg)
  } finally {
    refundLoading.value = false
  }
}

onMounted(fetchOrders)
</script>
