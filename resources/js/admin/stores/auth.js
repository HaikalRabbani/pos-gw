import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import client from '../api/client'

const ROLE_HIERARCHY = ['developer', 'admin', 'manager', 'cashier', 'kitchen']

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)

  function setUser(u) {
    user.value = u
  }

  function setSession(data) {
    if (data?.user) {
      user.value = data.user
    } else {
      user.value = data
    }
  }

  /**
   * Gets the highest role the user has across all outlets.
   * Order: developer > admin > manager > cashier > kitchen
   */
  const highestRole = computed(() => {
    if (!user.value?.outlets) return null
    const roles = user.value.outlets.map((o) => o.pivot?.role)
    for (const role of ROLE_HIERARCHY) {
      if (roles.includes(role)) return role
    }
    return null
  })

  /** Display label for the user's role */
  const roleLabel = computed(() => {
    const labels = {
      developer: 'Developer',
      admin: 'Owner',
      manager: 'Manager',
      cashier: 'Kasir',
      kitchen: 'Dapur',
    }
    return labels[highestRole.value] || '—'
  })

  /** Whether user can access the admin panel at all */
  const canAccessAdmin = computed(() => {
    return ['developer', 'admin', 'manager'].includes(highestRole.value)
  })

  /** Whether user has full system access (developer or admin) */
  const isSuper = computed(() => {
    return ['developer', 'admin'].includes(highestRole.value)
  })

  /** Whether user is a manager */
  const isManager = computed(() => highestRole.value === 'manager')

  /** Whether user is mobile-only staff (cashier/kitchen) */
  const isStaff = computed(() => {
    return ['cashier', 'kitchen'].includes(highestRole.value)
  })

  /** IDs of outlets the user is assigned to */
  const outletIds = computed(() => {
    return user.value?.outlets?.map((o) => o.id) || []
  })

  async function login(email, password) {
    const { data } = await client.post('/auth/login', { email, password })
    user.value = data.data.user
    return data.data
  }

  async function loginPin(pin) {
    const { data } = await client.post('/auth/login-pin', { pin })
    user.value = data.data.user
    return data.data
  }

  async function checkSession() {
    try {
      const { data } = await client.get('/auth/me')
      if (data.data) {
        user.value = data.data
        return true
      }
    } catch (_) {}
    user.value = null
    return false
  }

  async function logout() {
    try {
      await client.post('/auth/logout')
    } catch (_) {}
    user.value = null
  }

  return {
    user, setUser, setSession,
    login, loginPin, logout, checkSession,
    highestRole, roleLabel, canAccessAdmin, isSuper, isManager, isStaff, outletIds,
  }
})
