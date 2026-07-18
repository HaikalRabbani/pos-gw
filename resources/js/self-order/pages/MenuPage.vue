<template>
  <div class="max-w-lg mx-auto px-4 py-6 pb-28 space-y-5">

    <!-- Loading awal -->
    <div v-if="loading" class="text-center py-20">
      <LoaderCircle class="w-6 h-6 text-slate-400 mx-auto animate-spin" stroke-width="1.5" />
      <p class="text-sm text-slate-500 mt-2">Memuat menu...</p>
    </div>

    <!-- Error (qr invalid, outlet nonaktif, dll) -->
    <div v-else-if="errorMsg" class="text-center py-20 px-4">
      <div class="w-14 h-14 rounded-2xl bg-red-50 flex items-center justify-center mx-auto mb-3">
        <TriangleAlert class="w-7 h-7 text-red-500" stroke-width="1.5" />
      </div>
      <p class="text-sm text-slate-600">{{ errorMsg }}</p>
    </div>

    <!-- Status pesanan (setelah submit) -->
    <div v-else-if="view === 'status'" class="space-y-4">
      <div class="text-center">
        <div class="w-14 h-14 rounded-2xl bg-teal-100 flex items-center justify-center mx-auto mb-3">
          <component :is="statusIcon" class="w-7 h-7 text-teal-700" stroke-width="1.5" />
        </div>
        <h1 class="text-lg font-bold text-slate-900">{{ statusLabel }}</h1>
        <p class="text-xs text-slate-500 mt-1">Meja {{ table?.name }} — Pesanan #{{ activeOrder?.id }}</p>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 divide-y divide-slate-100">
        <div v-for="item in activeOrder?.items" :key="item.id" class="p-3.5 flex justify-between items-start gap-3">
          <div>
            <p class="text-sm font-medium text-slate-800">{{ item.qty }}x {{ item.product_name }}</p>
            <p v-if="item.notes" class="text-xs text-slate-400 mt-0.5">{{ item.notes }}</p>
          </div>
          <p class="text-sm text-slate-600 shrink-0">{{ formatRupiah(item.total_price) }}</p>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 p-3.5 flex justify-between font-semibold text-slate-800">
        <span>Total</span>
        <span>{{ formatRupiah(activeOrder?.grand_total) }}</span>
      </div>

      <button
        v-if="isOrderFinished"
        class="w-full py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-600"
        @click="startNewOrder">
        Pesan lagi
      </button>
    </div>

    <!-- Browsing menu + cart -->
    <template v-else>
      <div class="text-center">
        <h1 class="text-lg font-bold text-slate-900">{{ outlet?.name }}</h1>
        <p class="text-xs text-slate-500 mt-1">Meja {{ table?.name }}</p>
      </div>

      <div v-for="category in categories" :key="category.id" class="space-y-2">
        <h2 class="text-sm font-semibold text-slate-700">{{ category.name }}</h2>
        <div
          v-for="product in productsByCategory(category.id)"
          :key="product.id"
          class="bg-white rounded-2xl border border-slate-200 p-3.5 flex items-center gap-3">
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-slate-800 truncate">{{ product.name }}</p>
            <p class="text-sm text-teal-700 font-semibold mt-0.5">{{ formatRupiah(product.price) }}</p>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <button v-if="cartQty(product.id) > 0" class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center"
              @click="decrementCart(product)">
              <Minus class="w-3.5 h-3.5 text-slate-600" />
            </button>
            <span v-if="cartQty(product.id) > 0" class="text-sm font-semibold w-4 text-center">{{ cartQty(product.id) }}</span>
            <button class="w-7 h-7 rounded-lg bg-teal-600 flex items-center justify-center"
              @click="incrementCart(product)">
              <Plus class="w-3.5 h-3.5 text-white" />
            </button>
          </div>
        </div>
      </div>

      <!-- Produk tanpa kategori -->
      <div v-if="productsByCategory(null).length" class="space-y-2">
        <h2 class="text-sm font-semibold text-slate-700">Lainnya</h2>
        <div
          v-for="product in productsByCategory(null)"
          :key="product.id"
          class="bg-white rounded-2xl border border-slate-200 p-3.5 flex items-center gap-3">
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-slate-800 truncate">{{ product.name }}</p>
            <p class="text-sm text-teal-700 font-semibold mt-0.5">{{ formatRupiah(product.price) }}</p>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <button v-if="cartQty(product.id) > 0" class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center"
              @click="decrementCart(product)">
              <Minus class="w-3.5 h-3.5 text-slate-600" />
            </button>
            <span v-if="cartQty(product.id) > 0" class="text-sm font-semibold w-4 text-center">{{ cartQty(product.id) }}</span>
            <button class="w-7 h-7 rounded-lg bg-teal-600 flex items-center justify-center"
              @click="incrementCart(product)">
              <Plus class="w-3.5 h-3.5 text-white" />
            </button>
          </div>
        </div>
      </div>

      <!-- Bar keranjang, sticky di bawah -->
      <div v-if="cartTotalQty > 0" class="fixed bottom-0 left-0 right-0 px-4 pb-4">
        <button
          class="max-w-lg mx-auto w-full bg-teal-600 text-white rounded-2xl py-3.5 px-4 flex items-center justify-between shadow-lg disabled:opacity-60"
          :disabled="submitting"
          @click="checkout">
          <span class="flex items-center gap-2 text-sm font-semibold">
            <ShoppingBag class="w-4 h-4" />
            {{ cartTotalQty }} item
          </span>
          <span class="text-sm font-semibold">
            {{ submitting ? 'Memproses...' : formatRupiah(cartTotalPrice) }}
          </span>
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { ShoppingBag, LoaderCircle, TriangleAlert, Plus, Minus, ClipboardList, ChefHat, CheckCheck } from 'lucide-vue-next'
import client from '../api/client'
import { formatRupiah } from '../../admin/utils/format'

const route = useRoute()
const qrToken = route.params.qrToken

const loading = ref(true)
const errorMsg = ref('')
const outlet = ref(null)
const table = ref(null)
const categories = ref([])
const products = ref([])

const cart = ref({}) // { [product_id]: { product, qty } }
const submitting = ref(false)

const view = ref('menu') // 'menu' | 'status'
const activeOrder = ref(null)
let pollTimer = null

const storageKey = `pos-gw-self-order:${qrToken}`

function productsByCategory(categoryId) {
  return products.value.filter((p) => p.category_id === categoryId)
}

function cartQty(productId) {
  return cart.value[productId]?.qty || 0
}

function incrementCart(product) {
  const existing = cart.value[product.id]
  cart.value = {
    ...cart.value,
    [product.id]: { product, qty: (existing?.qty || 0) + 1 },
  }
}

function decrementCart(product) {
  const existing = cart.value[product.id]
  if (!existing) return
  const nextQty = existing.qty - 1
  const next = { ...cart.value }
  if (nextQty <= 0) {
    delete next[product.id]
  } else {
    next[product.id] = { product, qty: nextQty }
  }
  cart.value = next
}

const cartTotalQty = computed(() => Object.values(cart.value).reduce((sum, c) => sum + c.qty, 0))
const cartTotalPrice = computed(() =>
  Object.values(cart.value).reduce((sum, c) => sum + c.qty * c.product.price, 0),
)

const statusMeta = {
  draft: { label: 'Menyiapkan pesanan...', icon: ClipboardList },
  confirmed: { label: 'Pesanan diterima', icon: ClipboardList },
  preparing: { label: 'Sedang diproses di dapur', icon: ChefHat },
  done: { label: 'Pesanan selesai', icon: CheckCheck },
  cancelled: { label: 'Pesanan dibatalkan', icon: TriangleAlert },
  voided: { label: 'Pesanan dibatalkan', icon: TriangleAlert },
}
const statusLabel = computed(() => statusMeta[activeOrder.value?.status]?.label || 'Memuat status...')
const statusIcon = computed(() => statusMeta[activeOrder.value?.status]?.icon || ClipboardList)
const isOrderFinished = computed(() => ['done', 'cancelled', 'voided'].includes(activeOrder.value?.status))

async function loadMenu() {
  loading.value = true
  errorMsg.value = ''
  try {
    const { data } = await client.get(`/tables/${qrToken}/menu`)
    outlet.value = data.data.outlet
    table.value = data.data.table
    categories.value = data.data.categories
    products.value = data.data.products
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Gagal memuat menu. Coba scan ulang QR meja.'
  } finally {
    loading.value = false
  }
}

async function checkout() {
  if (cartTotalQty.value === 0 || submitting.value) return
  submitting.value = true
  try {
    const { data: orderRes } = await client.post(`/tables/${qrToken}/orders`)
    const orderId = orderRes.data.id

    for (const { product, qty } of Object.values(cart.value)) {
      await client.post(`/tables/${qrToken}/orders/${orderId}/items`, {
        product_id: product.id,
        product_name: product.name,
        qty,
        unit_price: product.price,
      })
    }

    const { data: submitRes } = await client.post(`/tables/${qrToken}/orders/${orderId}/submit`)
    activeOrder.value = submitRes.data
    cart.value = {}
    view.value = 'status'
    localStorage.setItem(storageKey, String(orderId))
    startPolling(orderId)
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Gagal membuat pesanan, silakan coba lagi.'
  } finally {
    submitting.value = false
  }
}

function startPolling(orderId) {
  stopPolling()
  pollTimer = setInterval(async () => {
    try {
      const { data } = await client.get(`/tables/${qrToken}/orders/${orderId}`)
      activeOrder.value = data.data
      if (isOrderFinished.value) stopPolling()
    } catch {
      stopPolling()
    }
  }, 5000)
}

function stopPolling() {
  if (pollTimer) clearInterval(pollTimer)
  pollTimer = null
}

function startNewOrder() {
  localStorage.removeItem(storageKey)
  activeOrder.value = null
  view.value = 'menu'
  stopPolling()
}

onMounted(async () => {
  await loadMenu()
  if (errorMsg.value) return

  // Kalau tab ditutup/refresh saat pesanan sudah submit, lanjutin nge-track
  const savedOrderId = localStorage.getItem(storageKey)
  if (savedOrderId) {
    try {
      const { data } = await client.get(`/tables/${qrToken}/orders/${savedOrderId}`)
      if (data.data.status !== 'draft') {
        activeOrder.value = data.data
        view.value = 'status'
        if (!isOrderFinished.value) startPolling(savedOrderId)
      }
    } catch {
      localStorage.removeItem(storageKey)
    }
  }
})

onUnmounted(stopPolling)
</script>
