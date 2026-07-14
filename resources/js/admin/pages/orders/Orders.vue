<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-slate-900">Pesanan</h1>
      <p class="text-sm text-slate-500 mt-1">Daftar transaksi</p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
      <div class="p-3 border-b border-slate-100 flex items-center gap-2">
        <span class="text-sm text-slate-500">Tampilkan</span>
        <Select v-model="rowsPerPage" :options="[5, 10, 20, 50]" class="w-20" />
        <span class="text-sm text-slate-500">data</span>
      </div>
      <DataTable :value="orders" paginator :rows="rowsPerPage"
        paginatorTemplate="CurrentPageReport PrevPageLink NextPageLink"
        currentPageReportTemplate="Halaman {currentPage} dari {totalPages}"
        class="text-sm">
        <template #empty>Belum ada pesanan.</template>
        <Column field="id" header="ID" sortable />
        <Column field="customer_name" header="Pelanggan" />
        <Column field="status" header="Status" sortable>
          <template #body="{ data }">
            <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" rounded />
          </template>
        </Column>
        <Column field="grand_total" header="Total" sortable>
          <template #body="{ data }">
            <span class="font-medium">Rp {{ (data.grand_total / 100).toLocaleString('id-ID') }}</span>
          </template>
        </Column>
        <Column field="payment_status" header="Pembayaran" sortable>
          <template #body="{ data }">
            <Tag :value="data.payment_status" :severity="data.payment_status === 'paid' ? 'success' : 'warning'" rounded />
          </template>
        </Column>
        <Column field="created_at" header="Tanggal" sortable />
      </DataTable>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import client from '../../api/client'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Select from 'primevue/select'

const orders = ref([])
const rowsPerPage = ref(10)

function statusLabel(s) {
  const map = { draft: 'Draft', confirmed: 'Dikonfirmasi', preparing: 'Dimasak', done: 'Selesai', cancelled: 'Batal', voided: 'Void' }
  return map[s] || s
}

function statusSeverity(s) {
  const map = { draft: 'info', confirmed: 'warn', preparing: 'warn', done: 'success', cancelled: 'danger', voided: 'danger' }
  return map[s] || 'info'
}

onMounted(async () => {
  try {
    const { data: outletRes } = await client.get('/outlets')
    const outletId = outletRes.data[0]?.id
    if (!outletId) return
    const { data } = await client.get('/orders', { params: { outlet_id: outletId, per_page: 20 } })
    orders.value = data.data
  } catch (_) {}
})
</script>
