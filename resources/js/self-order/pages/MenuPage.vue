<template>
  <div class="min-h-screen bg-slate-50 pb-32">

    <!-- Loading awal -->
    <div v-if="loading" class="text-center py-24">
      <LoaderCircle class="w-6 h-6 text-slate-400 mx-auto animate-spin" stroke-width="1.5" />
      <p class="text-sm text-slate-500 mt-2">Memuat menu...</p>
    </div>

    <!-- Error (qr invalid, outlet nonaktif, dll) -->
    <div v-else-if="errorMsg" class="text-center py-24 px-4">
      <div class="w-14 h-14 rounded-2xl bg-red-50 flex items-center justify-center mx-auto mb-3">
        <TriangleAlert class="w-7 h-7 text-red-500" stroke-width="1.5" />
      </div>
      <p class="text-sm text-slate-600">{{ errorMsg }}</p>
    </div>

    <!-- Status pesanan (setelah submit) -->
    <div v-else-if="view === 'status'" class="max-w-lg mx-auto px-4 py-6 space-y-4">
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
        class="w-full py-2.5 rounded-xl border border-slate-200 text-sm font-medium text-slate-600 bg-white"
        @click="startNewOrder">
        Pesan lagi
      </button>
    </div>

    <!-- Browsing menu + cart -->
    <template v-else>
      <div class="max-w-lg mx-auto px-4 pt-6 space-y-6">

        <!-- Header banner -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-teal-700 to-teal-600 px-5 py-5 flex items-center justify-between">
          <div>
            <p class="text-white font-bold text-base">Halo, selamat datang!</p>
            <p class="text-teal-100 text-xs mt-0.5">Pesan menu favoritmu dari sini.</p>
          </div>
          <div class="text-right shrink-0 pl-3">
            <p class="text-teal-100 text-[10px] font-semibold tracking-wide">MEJA</p>
            <p class="text-white font-bold text-2xl leading-tight">{{ table?.name }}</p>
          </div>
        </div>

        <!-- Category chips -->
        <div v-if="categories.length" class="flex gap-2 overflow-x-auto no-scrollbar -mx-4 px-4">
          <button
            v-for="category in categories"
            :key="category.id"
            class="shrink-0 px-4 py-1.5 rounded-full text-sm font-medium border transition-colors"
            :class="activeCategory === category.id
              ? 'border-teal-600 bg-teal-50 text-teal-700'
              : 'border-slate-200 text-slate-600 bg-white'"
            @click="scrollToCategory(category.id)">
            {{ category.name }}
          </button>
        </div>

        <!-- Rekomendasi -->
        <div v-if="featuredProducts.length" class="space-y-3">
          <h2 class="text-base font-bold text-slate-900 pb-2 border-b border-dashed border-slate-200">Rekomendasi</h2>
          <div class="grid grid-cols-2 gap-3">
            <div v-for="product in featuredProducts" :key="product.id"
              class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
              <div class="relative aspect-square bg-teal-50 flex items-center justify-center">
                <span class="absolute top-2 left-2 bg-amber-400 text-amber-950 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1">
                  <Star class="w-2.5 h-2.5" fill="currentColor" /> Best Seller
                </span>
                <img v-if="product.image" :src="product.image" :alt="product.name" class="w-full h-full object-cover" />
                <span v-else class="text-teal-300 text-sm font-semibold">No Image</span>
              </div>
              <div class="p-3">
                <p class="text-xs text-slate-400">{{ categoryName(product.category_id) }}</p>
                <p class="text-sm font-semibold text-slate-800 truncate">{{ product.name }}</p>
                <div class="flex items-center justify-between mt-1.5">
                  <div class="min-w-0">
                    <p class="text-sm font-bold text-teal-700">{{ formatRupiah(product.discounted_price ?? product.price) }}</p>
                    <p v-if="product.discounted_price" class="text-[11px] text-slate-400 line-through">{{ formatRupiah(product.price) }}</p>
                  </div>
                  <CartStepper :product="product" :qty="cartQty(product.id)" @inc="incrementCart(product)" @dec="decrementCart(product)" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Per kategori -->
        <div v-for="category in categories" :key="category.id" :id="`cat-${category.id}`" class="space-y-2 scroll-mt-4">
          <div class="flex items-center justify-between pb-2 border-b border-dashed border-slate-200">
            <h2 class="text-base font-bold text-slate-900">{{ category.name }}</h2>
            <span class="text-xs text-slate-400">{{ productsByCategory(category.id).length }} Produk</span>
          </div>
          <div v-for="product in productsByCategory(category.id)" :key="product.id"
            class="bg-white rounded-2xl border border-slate-200 p-3 flex items-center gap-3">
            <div class="w-16 h-16 rounded-xl bg-teal-50 flex items-center justify-center shrink-0 overflow-hidden">
              <img v-if="product.image" :src="product.image" :alt="product.name" class="w-full h-full object-cover" />
              <span v-else class="text-teal-300 text-[10px] font-semibold text-center px-1">No Image</span>
            </div>
            <div class="flex-1 min-w-0">
              <span v-if="product.is_featured" class="inline-flex items-center gap-1 bg-amber-400 text-amber-950 text-[10px] font-bold px-2 py-0.5 rounded-full mb-1">
                <Star class="w-2.5 h-2.5" fill="currentColor" /> Best Seller
              </span>
              <p class="text-sm font-semibold text-slate-800 truncate">{{ product.name }}</p>
              <div class="flex items-baseline gap-1.5 mt-0.5">
                <p class="text-sm font-bold text-teal-700">{{ formatRupiah(product.discounted_price ?? product.price) }}</p>
                <p v-if="product.discounted_price" class="text-xs text-slate-400 line-through">{{ formatRupiah(product.price) }}</p>
              </div>
            </div>
            <CartStepper :product="product" :qty="cartQty(product.id)" @inc="incrementCart(product)" @dec="decrementCart(product)" />
          </div>
        </div>

        <!-- Produk tanpa kategori -->
        <div v-if="productsByCategory(null).length" class="space-y-2">
          <div class="flex items-center justify-between pb-2 border-b border-dashed border-slate-200">
            <h2 class="text-base font-bold text-slate-900">Lainnya</h2>
            <span class="text-xs text-slate-400">{{ productsByCategory(null).length }} Produk</span>
          </div>
          <div v-for="product in productsByCategory(null)" :key="product.id"
            class="bg-white rounded-2xl border border-slate-200 p-3 flex items-center gap-3">
            <div class="w-16 h-16 rounded-xl bg-teal-50 flex items-center justify-center shrink-0 overflow-hidden">
              <img v-if="product.image" :src="product.image" :alt="product.name" class="w-full h-full object-cover" />
              <span v-else class="text-teal-300 text-[10px] font-semibold text-center px-1">No Image</span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-slate-800 truncate">{{ product.name }}</p>
              <div class="flex items-baseline gap-1.5 mt-0.5">
                <p class="text-sm font-bold text-teal-700">{{ formatRupiah(product.discounted_price ?? product.price) }}</p>
                <p v-if="product.discounted_price" class="text-xs text-slate-400 line-through">{{ formatRupiah(product.price) }}</p>
              </div>
            </div>
            <CartStepper :product="product" :qty="cartQty(product.id)" @inc="incrementCart(product)" @dec="decrementCart(product)" />
          </div>
        </div>
      </div>

      <!-- Bar keranjang, sticky di bawah -->
      <div v-if="cartTotalQty > 0" class="fixed bottom-0 left-0 right-0 px-4 pb-4">
        <button
          class="max-w-lg mx-auto w-full bg-teal-700 text-white rounded-2xl py-3.5 px-4 flex items-center justify-between shadow-lg disabled:opacity-60"
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
import { ref, computed, onMounted, onUnmounted, h } from 'vue'
import { useRoute } from 'vue-router'
import { ShoppingBag, LoaderCircle, TriangleAlert, Plus, Minus, Star, ClipboardList, ChefHat, CheckCheck } from 'lucide-vue-next'
import client from '../api/client'
import { formatRupiah } from '../../admin/utils/format'

// Tombol qty kecil (+ / - / angka), dipakai di kartu Rekomendasi & list kategori
const CartStepper = {
  props: ['product', 'qty'],
  emits: ['inc', 'dec'],
  setup(props, { emit }) {
    return () => h('div', { class: 'flex items-center gap-2 shrink-0' }, [
      props.qty > 0 && h('button', {
        class: 'w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center',
        onClick: () => emit('dec'),
      }, [h(Minus, { class: 'w-3.5 h-3.5 text-slate-600' })]),
      props.qty > 0 && h('span', { class: 'text-sm font-semibold w-4 text-center' }, String(props.qty)),
      h('button', {
        class: 'w-7 h-7 rounded-full bg-teal-700 flex items-center justify-center',
        onClick: () => emit('inc'),
      }, [h(Plus, { class: 'w-3.5 h-3.5 text-white' })]),
    ])
  },
}

const route = useRoute()
const qrToken = route.params.qrToken

const loading = ref(true)
const errorMsg = ref('')
const outlet = ref(null)
const table = ref(null)
const categories = ref([])
const products = ref([])
const activeCategory = ref(null)

const cart = ref({}) // { [product_id]: { product, qty } }
const submitting = ref(false)

const view = ref('menu') // 'menu' | 'status'
const activeOrder = ref(null)
let pollTimer = null

const storageKey = `pos-gw-self-order:${qrToken}`

const featuredProducts = computed(() => products.value.filter((p) => p.is_featured))

function productsByCategory(categoryId) {
  return products.value.filter((p) => p.category_id === categoryId)
}

function categoryName(categoryId) {
  return categories.value.find((c) => c.id === categoryId)?.name || ''
}

function scrollToCategory(categoryId) {
  activeCategory.value = categoryId
  document.getElementById(`cat-${categoryId}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
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
  Object.values(cart.value).reduce((sum, c) => sum + c.qty * (c.product.discounted_price ?? c.product.price), 0),
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
        unit_price: product.discounted_price ?? product.price,
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
  if (categories.value.length) activeCategory.value = categories.value[0].id

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

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
