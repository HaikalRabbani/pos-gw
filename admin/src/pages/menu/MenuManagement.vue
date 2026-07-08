<template>
  <div>
    <h1 class="text-2xl font-bold mb-4">Menu Management</h1>
    <DataTable :value="products" stripedRows class="bg-white rounded-xl shadow">
      <Column field="name" header="Name" />
      <Column field="price" header="Price">
        <template #body="{ data }">Rp {{ (data.price / 100).toLocaleString('id-ID') }}</template>
      </Column>
      <Column field="is_active" header="Active">
        <template #body="{ data }">{{ data.is_active ? 'Yes' : 'No' }}</template>
      </Column>
    </DataTable>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import client from '../../api/client'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

const products = ref([])

onMounted(async () => {
  try {
    const { data: outletRes } = await client.get('/outlets')
    const outletId = outletRes.data[0]?.id
    if (!outletId) return
    const { data } = await client.get('/products', { params: { outlet_id: outletId } })
    products.value = data.data
  } catch (_) {}
})
</script>
