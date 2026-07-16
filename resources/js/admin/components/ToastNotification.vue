<template>
  <Teleport to="body">
    <div class="fixed top-4 right-4 z-[9999] flex flex-col gap-2.5 pointer-events-none max-w-sm w-full">
      <TransitionGroup name="toast-slide">
        <ToastItem
          v-for="toast in toasts"
          :key="toast.id"
          :toast="toast"
          @close="remove(toast.id)"
        />
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'
import { useToastStore } from '../stores/toast'
import ToastItem from './ToastItem.vue'

const store = useToastStore()
const toasts = computed(() => store.toasts)

function remove(id) {
  store.remove(id)
}
</script>

<style scoped>
.toast-slide-enter-active {
  transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}
.toast-slide-leave-active {
  transition: all 0.25s ease-in;
}
.toast-slide-enter-from {
  opacity: 0;
  transform: translateX(60px) scale(0.9);
}
.toast-slide-leave-to {
  opacity: 0;
  transform: translateX(100%) scale(0.9);
}
.toast-slide-move {
  transition: transform 0.3s ease;
}
</style>
