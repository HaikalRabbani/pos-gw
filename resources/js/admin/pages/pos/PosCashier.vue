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
        <div v-for="p in filteredProducts" :key="p.id"
          class="bg-white rounded-xl border border-slate-200 p-3 cursor-pointer hover:border-teal-300 hover:shadow-md transition-all"
          @click="addToCart(p)">
          <p class="font-semibold text-slate-900">{{ p.name }}</p>
          <p v-if="p.description" class="text-xs text-slate-400 mt-0.5 line-clamp-1">{{ p.description }}</p>
          <p class="text-sm text-teal-600 font-medium mt-1">{{ formatPrice(p.price) }}</p>
        </div>
      </div>
    </div>
    <!-- Cart -->
    <div class="w-96 bg-white rounded-xl border border-slate-200 p-4 flex flex-col">
      <div class="flex items-center justify-between mb-3">
        <h2 class="font-bold text-lg text-slate-900">Pesanan</h2>
        <Button v-if="cart.length" icon="pi pi-trash" text severity="danger" size="small"
          label="Kosongkan" @click="cart = []" />
      </div>

      <!-- Customer Notes -->
      <div class="mb-3">
        <InputText v-model="customerNotes" placeholder="Catatan pesanan (opsional)" class="w-full text-sm" />
      </div>

      <div class="flex-1 overflow-auto space-y-2">
        <div v-for="(item, i) in cart" :key="i"
          class="bg-slate-50 rounded-xl p-3 border border-slate-100">
          <div class="flex justify-between items-start">
            <div class="flex-1 min-w-0">
              <p class="font-medium text-slate-900 text-sm">{{ item.name }}</p>
              <p class="text-xs text-slate-500 mt-0.5">
                {{ item.qty }} x {{ formatPrice(item.price) }}
                <span class="font-semibold text-slate-700 ml-2">= {{ formatPrice(item.qty * item.price) }}</span>
              </p>
            </div>
            <div class="flex items-center gap-1 ml-2">
              <Button icon="pi pi-plus" text rounded size="small" @click="item.qty++" />
              <span class="text-xs font-mono w-5 text-center">{{ item.qty }}</span>
              <Button icon="pi pi-minus" text rounded size="small"
                @click="item.qty > 1 ? item.qty-- : cart.splice(i, 1)" />
              <Button icon="pi pi-trash" text severity="danger" rounded size="small"
                @click="cart.splice(i, 1)" />
            </div>
          </div>
          <!-- Item Notes -->
          <div class="mt-2">
            <input v-model="item.notes"
              placeholder="Catatan item (contoh: extra pedas)"
              class="w-full text-xs px-2 py-1.5 rounded-lg border border-slate-200 bg-white focus:border-teal-300 focus:outline-none"
              @click.stop />
          </div>
        </div>
        <div v-if="!cart.length" class="flex flex-col items-center justify-center py-12 text-center">
          <i class="pi pi-shopping-cart text-3xl text-slate-200 mb-2"></i>
          <p class="text-slate-400 text-sm">Belum ada item</p>
          <p class="text-slate-300 text-xs">Klik produk untuk menambah</p>
        </div>
      </div>

      <div class="border-t border-slate-200 pt-3 mt-3 space-y-2">
        <div class="flex justify-between text-sm text-slate-500">
          <span>Subtotal</span>
          <span>{{ formatPrice(subtotal) }}</span>
        </div>
        <div class="flex justify-between font-bold text-lg text-slate-900">
          <span>Total</span>
          <span class="text-teal-600">{{ formatPrice(subtotal) }}</span>
        </div>
        <Button label="Bayar" class="w-full mt-2" @click="checkout" :disabled="!cart.length" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import client from '../../api/client'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'

const categories = ref([])
const products = ref([])
const activeCategory = ref(null)
const cart = ref([])
const customerNotes = ref('')

const filteredProducts = computed(() => {
  if (!activeCategory.value) return products.value
  return products.value.filter((p) => p.category_id === activeCategory.value)
})

const subtotal = computed(() => cart.value.reduce((s, i) => s + i.price * i.qty, 0))

function formatPrice(cents) {
  return 'Rp ' + (cents / 100).toLocaleString('id-ID')
}

function addToCart(p) {
  const existing = cart.value.find(i => i.id === p.id)
  if (existing) { existing.qty++; return }
  cart.value.push({ id: p.id, name: p.name, price: p.price, qty: 1, notes: '' })
}

async function checkout() {
  const orderItems = cart.value.map(i => ({
    product_id: i.id,
    product_name: i.name,
    qty: i.qty,
    unit_price: i.price,
    total_price: i.price * i.qty,
    notes: i.notes || null,
  }))
  console.log('Order items:', orderItems)
  console.log('Customer notes:', customerNotes.value)
  alert('Pesanan siap diproses! (simulasi)')
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
