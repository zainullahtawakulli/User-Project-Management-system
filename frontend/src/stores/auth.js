import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  // =========================================================
  // State
  // =========================================================

  const user = ref(null)
  const token = ref(localStorage.getItem('token') || null)
  const loading = ref(false)
  const errors = ref({})
  const initialized = ref(false)

  // =========================================================
  // Authentication
  // =========================================================

  const isAuthenticated = computed(() => {
    return Boolean(user.value && token.value)
  })

  // Alias
  const isLoggedIn = computed(() => {
    return Boolean(user.value && token.value)
  })

  // =========================================================
  // User / Role
  // =========================================================

  const role = computed(() => {
    return user.value?.role || null
  })

  // =========================================================
  // Permissions
  // =========================================================

  const permissions = computed(() => {
    return user.value?.permissions || []
  })

  // =========================================================
  // Roles
  // =========================================================

  const isSuperAdmin = computed(() => {
    return user.value?.role === 'super_admin'
  })

  const isAdmin = computed(() => {
    return user.value?.role === 'admin'
  })

  const isUser = computed(() => {
    return user.value?.role === 'user'
  })

  // Alias for existing components
  const isNormalUser = computed(() => {
    return user.value?.role === 'user'
  })

  // =========================================================
  // Dynamic Permissions
  // =========================================================
  //
  // Permissions come from Laravel:
  //
  // {
  //   user: {
  //     id: 1,
  //     name: "Admin User",
  //     email: "admin@example.com",
  //     role: "admin",
  //     permissions: [
  //       "users.view",
  //       "users.create",
  //       "users.update",
  //       "users.delete",
  //       "roles.view",
  //       "roles.create"
  //     ]
  //   }
  // }
  //
  // No permissions are hardcoded in Vue.
  // =========================================================

  const hasPermission = (permission) => {
    if (!user.value) {
      return false
    }

    const userPermissions = user.value.permissions || []

    // Wildcard permission
    if (userPermissions.includes('*')) {
      return true
    }

    return userPermissions.includes(permission)
  }

  // Alias
  const can = (permission) => {
    return hasPermission(permission)
  }

  // =========================================================
  // Register
  // =========================================================

  const register = async (form) => {
    loading.value = true
    errors.value = {}

    try {
      const response = await api.post('/register', form)

      user.value = response.data.user
      token.value = response.data.token

      localStorage.setItem('token', token.value)

      return response.data
    } catch (error) {
      errors.value = error.response?.data?.errors || {
        general: error.response?.data?.message || 'Registration failed.',
      }

      throw error
    } finally {
      loading.value = false
    }
  }

  // =========================================================
  // Login
  // =========================================================

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
      errors.value = error.response?.data?.errors || {
        email: error.response?.data?.message || 'Login failed.',
      }

      throw error
    } finally {
      loading.value = false
    }
  }

  // =========================================================
  // Fetch Current User
  // =========================================================

  const fetchUser = async () => {
    if (!token.value) {
      initialized.value = true
      return null
    }

    try {
      const response = await api.get('/user')

      user.value = response.data.user

      return user.value
    } catch (error) {
      console.error('Fetch authenticated user error:', error)

      // Token is invalid / expired
      if (error.response?.status === 401) {
        user.value = null
        token.value = null

        localStorage.removeItem('token')
      }

      throw error
    } finally {
      initialized.value = true
    }
  }

  // =========================================================
  // Logout
  // =========================================================

  const logout = async () => {
    try {
      if (token.value) {
        await api.post('/logout')
      }
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      user.value = null
      token.value = null
      errors.value = {}

      localStorage.removeItem('token')
    }
  }

  // =========================================================
  // Return Store
  // =========================================================

  return {
    // -------------------------------------------------------
    // State
    // -------------------------------------------------------

    user,
    token,
    loading,
    errors,
    initialized,

    // -------------------------------------------------------
    // Authentication
    // -------------------------------------------------------

    isAuthenticated,
    isLoggedIn,

    // -------------------------------------------------------
    // User / Role
    // -------------------------------------------------------

    role,

    // -------------------------------------------------------
    // Permissions
    // -------------------------------------------------------

    permissions,

    // -------------------------------------------------------
    // Roles
    // -------------------------------------------------------

    isSuperAdmin,
    isAdmin,
    isUser,
    isNormalUser,

    // -------------------------------------------------------
    // Permission Methods
    // -------------------------------------------------------

    hasPermission,
    can,

    // -------------------------------------------------------
    // Actions
    // -------------------------------------------------------

    register,
    login,
    fetchUser,
    logout,
  }
})
