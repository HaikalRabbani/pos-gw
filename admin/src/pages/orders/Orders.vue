<template>
  <div>
    <h1 class="text-2xl font-bold mb-4">Orders</h1>
    <DataTable :value="orders" stripedRows class="bg-white rounded-xl shadow">
      <Column field="id" header="ID" />
      <Column field="customer_name" header="Customer" />
      <Column field="status" header="Status" />
      <Column field="grand_total" header="Total">
        <template #body="{ data }">Rp {{ (data.grand_total / 100).toLocaleString('id-ID') }}</template>
      </Column>
      <Column field="payment_status" header="Payment" />
      <Column field="created_at" header="Date" />
    </DataTable>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import client from '../../api/client'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

const orders = ref([])

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
