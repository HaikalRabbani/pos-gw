<template>
  <div class="flex gap-4 h-[calc(100vh-4rem)]">
    <!-- Products -->
    <div class="flex-1 overflow-auto">
      <div class="flex gap-2 mb-4 overflow-x-auto">
        <Button v-for="cat in categories" :key="cat.id" :label="cat.name"
          :severity="activeCategory === cat.id ? 'primary' : 'secondary'"
          @click="activeCategory = cat.id" size="small" />
      </div>
      <div class="grid grid-cols-3 md:grid-cols-4 gap-3">
        <div v-for="p in products" :key="p.id"
          class="bg-white rounded-xl border border-slate-200 p-3 cursor-pointer hover:border-teal-300 transition"
          @click="addToCart(p)">
          <p class="font-semibold text-slate-900">{{ p.name }}</p>
          <p class="text-sm text-slate-500">{{ formatPrice(p.price) }}</p>
        </div>
      </div>
    </div>
    <!-- Cart -->
    <div class="w-80 bg-white rounded-xl border border-slate-200 p-4 flex flex-col">
      <h2 class="font-bold text-lg text-slate-900 mb-2">Cart</h2>
      <div class="flex-1 overflow-auto">
        <div v-for="(item, i) in cart" :key="i"
          class="flex justify-between items-center py-2 border-b border-slate-100">
          <div>
            <p class="font-medium text-slate-900">{{ item.name }}</p>
            <p class="text-sm text-slate-500">{{ item.qty }} x {{ formatPrice(item.price) }}</p>
          </div>
          <Button icon="pi pi-trash" severity="danger" text @click="cart.splice(i, 1)" />
        </div>
      </div>
      <div class="border-t border-slate-100 pt-3 mt-3">
        <div class="flex justify-between font-bold text-lg text-slate-900">
          <span>Total</span>
          <span>{{ formatPrice(total) }}</span>
        </div>
        <Button label="Bayar" class="w-full mt-3" @click="checkout" :disabled="!cart.length" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import client from '../../api/client'
import Button from 'primevue/button'

const categories = ref([])
const products = ref([])
const activeCategory = ref(null)
const cart = ref([])

const total = computed(() => cart.value.reduce((s, i) => s + i.price * i.qty, 0))

function formatPrice(cents) {
  return 'Rp ' + (cents / 100).toLocaleString('id-ID')
}

function addToCart(p) {
  const existing = cart.value.find(i => i.id === p.id)
  if (existing) { existing.qty++; return }
  cart.value.push({ id: p.id, name: p.name, price: p.price, qty: 1 })
}

function addQuantityToCart(p) {
  addToCart(p)
}

async function checkout() {
  alert('Order created (simulated)')
}

onMounted(async () => {
  try {
    const { data: outletRes } = await client.get('/outlets')
    const outletId = outletRes.data[0]?.id
    if (!outletId) return
    const [catRes, prodRes] = await Promise.all([
      client.get('/categories', { params: { outlet_id: outletId } }),
      client.get('/products', { params: { outlet_id: outletId } }),
    ])
    categories.value = catRes.data.data
    products.value = prodRes.data.data
  } catch (_) {}
})
</script>
