import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref(null)
  const token = ref(localStorage.getItem('token') || null)
  const loading = ref(false)
  const errors = ref({})

  // Getter
  const isAuthenticated = computed(() => !token.value)

  // Register
  const register = async (form) => {
    loading.value = true
    errors.value = {}

    try {
      const response = await api.post('/register', form)

      console.log('API response:', response.data)

      user.value = response.data.user
      token.value = response.data.token

      localStorage.setItem('token', token.value)

      return response.data
    } catch (error) {
      errors.value = error.response?.data?.errors || {}
      throw error
    } finally {
      loading.value = false
    }
  }

  // Login
  const login = async (form) => {
    loading.value = true
    errors.value = {}

    try {
      const response = await api.post('/login', form)

      user.value = response.data.user
      token.value = response.data.token

      localStorage.setItem('token', token.value)

      return response.data
    } catch (error) {
      errors.value = error.response?.data?.errors || {}
      throw error
    } finally {
      loading.value = false
    }
  }

  // Logout
  const logout = async () => {
    try {
      await api.post('/logout')
    } finally {
      user.value = null
      token.value = null
      localStorage.removeItem('token')
    }
  }

  return {
    user,
    token,
    loading,
    errors,
    isAuthenticated,
    register,
    login,
    logout,
  }
})
