import { defineStore } from 'pinia'
import { ref } from 'vue'

let nextId = 0

export const useToastStore = defineStore('toast', () => {
  const toasts = ref([])

  function add(type, title, message, duration = 4000) {
    const id = ++nextId
    toasts.value.push({ id, type, title, message, duration })

    if (duration > 0) {
      setTimeout(() => remove(id), duration)
    }

    return id
  }

  function success(title, message, duration) {
    return add('success', title, message, duration)
  }

  function error(title, message, duration) {
    return add('error', title, message, duration)
  }

  function info(title, message, duration) {
    return add('info', title, message, duration)
  }

  function warning(title, message, duration) {
    return add('warning', title, message, duration)
  }

  function remove(id) {
    const idx = toasts.value.findIndex((t) => t.id === id)
    if (idx !== -1) {
      toasts.value.splice(idx, 1)
    }
  }

  function clear() {
    toasts.value.splice(0)
  }

  return { toasts, add, success, error, info, warning, remove, clear }
})
