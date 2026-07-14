<template>
  <div>
    <h1 class="text-2xl font-bold mb-4">Kitchen Display</h1>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div v-for="order in orders" :key="order.id"
        class="bg-white rounded-xl shadow p-4 border-l-4"
        :class="orderBorder(order.status)">
        <div class="flex justify-between items-start">
          <div>
            <p class="font-bold text-lg">#{{ order.id }}</p>
            <p class="text-sm text-gray-500">{{ order.created_at }}</p>
          </div>
          <Button v-if="order.status === 'confirmed'" label="Accept" size="small"
            @click="accept(order.id)" />
          <Button v-else-if="order.status === 'preparing'" label="Done" size="small"
            severity="success" @click="done(order.id)" />
        </div>
        <div class="mt-2">
          <div v-for="item in order.items" :key="item.id"
            class="text-sm">- {{ item.product_name }} x{{ item.qty }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import client from '../../api/client'
import { useAuthStore } from '../../stores/auth'
import Button from 'primevue/button'

const orders = ref([])
let echo = null

function orderBorder(s) {
  return { 'border-yellow-500': s === 'confirmed', 'border-blue-500': s === 'preparing' }
}

async function fetchOrders() {
  try {
    const { data: outletRes } = await client.get('/outlets')
    const outletId = outletRes.data[0]?.id
    if (!outletId) return
    const { data } = await client.get('/orders', {
      params: { outlet_id: outletId, status: 'confirmed,preparing' },
    })
    orders.value = data.data
  } catch (_) {}
}

async function accept(id) {
  await client.put(`/orders/${id}/status`, { status: 'preparing' })
  fetchOrders()
}

async function done(id) {
  await client.put(`/orders/${id}/status`, { status: 'done' })
  fetchOrders()
}

onMounted(async () => {
  await fetchOrders()
  const auth = useAuthStore()
  if (!auth.token) return
  try {
    const { default: Echo } = await import('laravel-echo')
    window.Pusher = (await import('pusher-js')).default
    const { data: outletRes } = await client.get('/outlets')
    const outletId = outletRes.data[0]?.id
    if (!outletId) return
    echo = new Echo({
      broadcaster: 'reverb',
      key: import.meta.env.VITE_REVERB_APP_KEY || 'pos-key',
      wsHost: import.meta.env.VITE_REVERB_HOST || 'localhost',
      wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
      wssPort: import.meta.env.VITE_REVERB_PORT || 8080,
      forceTLS: false,
      authEndpoint: '/api/broadcasting/auth',
      enabledTransports: ['ws', 'wss'],
    })
    echo.private(`outlet.${outletId}`).listen('OrderStatusUpdated', () => fetchOrders())
  } catch (_) {}
})

onUnmounted(() => {
  if (echo) echo.disconnect()
})
</script>
