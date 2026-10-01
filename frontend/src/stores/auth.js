import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import api from '@/services/api'

const readImpersonationSession = () => {
  try {
    return JSON.parse(sessionStorage.getItem('impersonation_session') || 'null')
  } catch {
    return null
  }
}

export const useAuthStore = defineStore('auth', () => {
  // =========================================================
  // State
  // =========================================================

  const user = ref(null)
  const token = ref(localStorage.getItem('token') || null)
  const loading = ref(false)
  const errors = ref({})
  const initialized = ref(false)
  const impersonation = ref(readImpersonationSession())
  const isImpersonating = computed(() => Boolean(
    user.value?.is_impersonating
      && impersonation.value?.adminUser?.id
      && String(user.value.id) !== String(impersonation.value.adminUser.id)
      && String(user.value.id) === String(impersonation.value.targetUser?.id),
  ))

  const startImpersonation = (session) => {
    impersonation.value = session
    sessionStorage.setItem('impersonation_session', JSON.stringify(session))
  }

  const clearImpersonation = () => {
    impersonation.value = null
    sessionStorage.removeItem('impersonation_session')
  }

  const returnToAdmin = async () => {
    const session = impersonation.value
    if (!session?.adminToken || !session?.adminUser?.id) {
      throw new Error('The original admin session is unavailable.')
    }

    const expiresAt = Date.parse(session.expiresAt)
    if (!Number.isFinite(expiresAt) || expiresAt <= Date.now()) {
      clearImpersonation()
      user.value = null
      token.value = null
      localStorage.removeItem('token')
      throw new Error('The impersonation session has expired. Please sign in again.')
    }

    const impersonationToken = token.value
    token.value = session.adminToken
    localStorage.setItem('token', session.adminToken)

    try {
      const response = await api.get('/user')
      const restoredUser = response.data.user

      if (String(restoredUser.id) !== String(session.adminUser.id)) {
        throw new Error('The saved session does not belong to the original admin.')
      }

      user.value = restoredUser
      clearImpersonation()
      return restoredUser
    } catch (error) {
      if (error.response?.status === 401) {
        clearImpersonation()
        token.value = null
        user.value = null
        localStorage.removeItem('token')
      } else {
        token.value = impersonationToken
        localStorage.setItem('token', impersonationToken)
      }

      throw error
    }
  }

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

      clearImpersonation()
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

      clearImpersonation()
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
      if (!user.value?.is_impersonating && impersonation.value) {
        clearImpersonation()
      }

      return user.value
    } catch (error) {
      console.error('Fetch authenticated user error:', error)

      // Token is invalid / expired
      if (error.response?.status === 401) {
        user.value = null
        token.value = null

        localStorage.removeItem('token')
        clearImpersonation()
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
      clearImpersonation()
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
    impersonation,
    isImpersonating,

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
    startImpersonation,
    clearImpersonation,
    returnToAdmin,
  }
})
