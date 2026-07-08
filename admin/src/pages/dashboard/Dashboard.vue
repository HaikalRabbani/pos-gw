<template>
  <div>
    <h1 class="text-2xl font-bold mb-4">Dashboard</h1>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div v-for="card in stats" :key="card.label"
        class="bg-white rounded-xl shadow p-6">
        <p class="text-gray-500 text-sm">{{ card.label }}</p>
        <p class="text-3xl font-bold mt-1">{{ card.value }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import client from '../../api/client'

const stats = ref([
  { label: 'Today Sales', value: '-' },
  { label: 'Orders', value: '-' },
  { label: 'Active Outlets', value: '-' },
  { label: 'Products', value: '-' },
])

onMounted(async () => {
  try {
    const { data } = await client.get('/outlets')
    const outlets = data.data
    stats.value[2].value = outlets.length
    const { data: prod } = await client.get('/products?outlet_id=' + (outlets[0]?.id || ''))
    stats.value[3].value = prod.data.length
  } catch (_) {}
})
</script>
