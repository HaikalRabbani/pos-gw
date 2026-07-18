<template>
  <div
    class="pointer-events-auto relative flex items-start gap-3 px-4 py-3 rounded-2xl shadow-xl border backdrop-blur-md transition-all duration-300 overflow-hidden"
    :class="toastClass(toast.type)"
  >
    <!-- Icon -->
    <div class="shrink-0 mt-0.5">
      <component :is="iconComponent(toast.type)" class="w-5 h-5" stroke-width="1.5" />
    </div>

    <!-- Content -->
    <div class="flex-1 min-w-0">
      <p class="text-sm font-semibold leading-tight">{{ toast.title }}</p>
      <p v-if="toast.message" class="text-xs mt-0.5 opacity-80 leading-relaxed">{{ toast.message }}</p>
    </div>

    <!-- Close button -->
    <button
      @click="$emit('close')"
      class="shrink-0 -mr-1 -mt-1 w-6 h-6 rounded-full flex items-center justify-center hover:bg-black/10 transition-colors duration-150"
    >
      <X class="w-3 h-3" stroke-width="1.5" />
    </button>

    <!-- Progress bar (auto-dismiss countdown) -->
    <div class="absolute bottom-0 left-0 right-0 h-0.5 rounded-full overflow-hidden bg-black/10">
      <div
        class="h-full rounded-full transition-all ease-linear"
        :class="progressClass(toast.type)"
        :style="{ width: progressWidth, transitionDuration: progressDuration + 'ms' }"
      ></div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { CheckCircle, AlertCircle, Info, AlertTriangle, X } from '@lucide/vue'

const props = defineProps({
  toast: { type: Object, required: true },
})

defineEmits(['close'])

const progressWidth = ref('100%')
const progressDuration = ref(props.toast.duration || 4000)

onMounted(async () => {
  await nextTick()
  progressWidth.value = '0%'
})

function toastClass(type) {
  const map = {
    success: 'bg-emerald-50 border-emerald-200 text-emerald-800',
    error: 'bg-red-50 border-red-200 text-red-800',
    info: 'bg-sky-50 border-sky-200 text-sky-800',
    warning: 'bg-amber-50 border-amber-200 text-amber-800',
  }
  return map[type] || map.info
}

function iconComponent(type) {
  const map = {
    success: CheckCircle,
    error: AlertCircle,
    info: Info,
    warning: AlertTriangle,
  }
  return map[type] || Info
}

function progressClass(type) {
  const map = {
    success: 'bg-emerald-400',
    error: 'bg-red-400',
    info: 'bg-sky-400',
    warning: 'bg-amber-400',
  }
  return map[type] || map.info
}
</script>
