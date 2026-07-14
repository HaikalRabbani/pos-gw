<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
      <p class="text-sm text-slate-500 mt-1">Ringkasan performa hari ini</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div v-for="card in stats" :key="card.label"
        class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-sm text-slate-500">{{ card.label }}</p>
        <p class="text-2xl font-bold text-slate-900 mt-1">{{ card.value }}</p>
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
