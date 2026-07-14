<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-slate-900">Kitchen Display</h1>
      <p class="text-sm text-slate-500 mt-1">Pesanan yang sedang diproses</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div v-for="i in 3" :key="i"
        class="bg-white rounded-2xl border border-slate-200 p-4 animate-pulse">
        <div class="flex justify-between items-start">
          <div class="space-y-2">
            <div class="h-5 w-20 bg-slate-200 rounded"></div>
            <div class="h-3 w-32 bg-slate-100 rounded"></div>
          </div>
          <div class="h-8 w-16 bg-slate-200 rounded-lg"></div>
        </div>
        <div class="mt-3 space-y-2">
          <div class="h-3 w-40 bg-slate-100 rounded"></div>
          <div class="h-3 w-24 bg-slate-100 rounded"></div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!orders.length"
      class="bg-white rounded-2xl border border-slate-200 p-12 flex flex-col items-center justify-center text-center">
      <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
        <i class="pi pi-check-circle text-3xl text-slate-300"></i>
      </div>
      <p class="text-slate-700 font-semibold">Tidak ada pesanan masuk</p>
      <p class="text-slate-400 text-sm mt-1">Pesanan baru akan muncul secara otomatis.</p>
    </div>

    <!-- Order Cards -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="order in orders" :key="order.id"
        class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-all duration-200 hover:-translate-y-0.5 relative overflow-hidden"
        :class="orderBorderClass(order.status)">
        <!-- Status bar top -->
        <div class="absolute top-0 left-0 right-0 h-1"
          :class="{ 'bg-amber-400': order.status === 'confirmed', 'bg-blue-400': order.status === 'preparing' }">
        </div>

        <!-- Header -->
        <div class="flex justify-between items-start mb-3">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-lg font-bold text-slate-900">#{{ order.id }}</span>
              <Tag :value="statusBadge(order.status)" :severity="order.status === 'confirmed' ? 'warn' : 'info'" rounded size="small" />
            </div>
            <p class="text-xs text-slate-400 mt-0.5">{{ formatTime(order.created_at) }}</p>
          </div>
          <Button v-if="order.status === 'confirmed'" label="Accept" size="small"
            icon="pi pi-check" @click="accept(order.id)" />
          <Button v-else-if="order.status === 'preparing'" label="Done" size="small"
            severity="success" icon="pi pi-check-circle" @click="done(order.id)" />
        </div>

        <!-- Items -->
        <div class="space-y-1.5">
          <div v-for="item in order.items" :key="item.id"
            class="flex items-center gap-2 text-sm">
            <span class="w-6 h-6 rounded-md bg-slate-100 flex items-center justify-center text-xs font-semibold text-slate-500 shrink-0">{{ item.qty }}</span>
            <span class="text-slate-700">{{ item.product_name }}</span>
          </div>
        </div>

        <!-- Note -->
        <p v-if="order.note" class="mt-3 text-xs text-amber-700 bg-amber-50 rounded-lg px-3 py-2 border border-amber-200/50">
          <i class="pi pi-comment mr-1"></i>
          {{ order.note }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import client from '../../api/client'
import { useAuthStore } from '../../stores/auth'
import Button from 'primevue/button'
import Tag from 'primevue/tag'

const orders = ref([])
const loading = ref(true)
let echo = null

function statusBadge(s) {
  return s === 'confirmed' ? 'Baru' : 'Dimasak'
}

function orderBorderClass(s) {
  return s === 'confirmed' ? 'border-l-4 border-l-amber-400' : 'border-l-4 border-l-blue-400'
}

function formatTime(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' - ' +
    d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
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
  finally { loading.value = false }
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
