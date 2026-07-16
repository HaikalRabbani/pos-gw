<template>
  <div class="max-w-lg mx-auto px-4 py-6 space-y-6">
    <!-- Header -->
    <div class="text-center">
      <div class="w-14 h-14 rounded-2xl bg-teal-100 flex items-center justify-center mx-auto mb-3">
        <ShoppingBag class="w-7 h-7 text-teal-700" stroke-width="1.5" />
      </div>
      <h1 class="text-xl font-bold text-slate-900">Pesan dari Meja</h1>
      <p class="text-sm text-slate-500 mt-1">Scan QR meja untuk mulai pesan</p>
    </div>

    <!-- Placeholder -->
    <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center">
      <LoaderCircle class="w-6 h-6 text-slate-400 mx-auto animate-spin" stroke-width="1.5" />
      <p class="text-sm text-slate-500 mt-2">Memuat menu...</p>
    </div>
  </div>
</template>

<script setup>
import { ShoppingBag, LoaderCircle } from 'lucide-vue-next'
</script>
