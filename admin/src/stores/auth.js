import { defineStore } from 'pinia'
import { ref } from 'vue'
import client from '../api/client'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('pos_token'))
  const user = ref(JSON.parse(localStorage.getItem('pos_user') || 'null'))

  function save(t, u) {
    token.value = t
    user.value = u
    localStorage.setItem('pos_token', t)
    localStorage.setItem('pos_user', JSON.stringify(u))
  }

  async function login(email, password) {
    const { data } = await client.post('/auth/login', { email, password })
    save(data.data.token, data.data.user)
    return data.data
  }

  async function loginPin(pin) {
    const { data } = await client.post('/auth/login-pin', { pin })
    save(data.data.token, data.data.user)
    return data.data
  }

  function logout() {
    token.value = null
    user.value = null
    localStorage.removeItem('pos_token')
    localStorage.removeItem('pos_user')
  }

  return { token, user, login, loginPin, logout }
})
