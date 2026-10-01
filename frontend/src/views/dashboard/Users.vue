<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Eye, Pencil, Trash2, LogIn, LogOut } from 'lucide-vue-next'
import Paginator from 'primevue/paginator'
import { toast } from 'vue-sonner'

import { getUsers, deleteUser } from '@/services/userapi/user'
import api from '@/services/api'

import CreateUser from '@/views/users/CreateUser.vue'
import EditUser from '@/views/users/EditUser.vue'

import { useAuthStore } from '@/stores/auth'
import { useAppConfirm } from '@/composables/useAppConfirm'

const auth = useAuthStore()
const router = useRouter()
const confirmAction = useAppConfirm()

const users = ref([])
const loading = ref(false)
const error = ref(null)

const showAddUser = ref(false)
const showEditUser = ref(false)
const editingUserId = ref(null)

const first = ref(0)
const rowsPerPage = ref(10)

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

const paginatedUsers = computed(() => {
  return users.value.slice(first.value, first.value + rowsPerPage.value)
})

/*
|--------------------------------------------------------------------------
| Fetch Users
|--------------------------------------------------------------------------
*/

const fetchUsers = async () => {
  loading.value = true
  error.value = null

  try {
    const response = await getUsers()

    users.value = response.data?.users || []

    // Reset pagination if current page is no longer valid
    if (first.value >= users.value.length && users.value.length > 0) {
      first.value = Math.floor((users.value.length - 1) / rowsPerPage.value) * rowsPerPage.value
    }
  } catch (err) {
    console.error('Fetch Users error:', err)

    error.value = err.response?.data?.message || 'Failed to load users.'

    users.value = []
  } finally {
    loading.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Create User
|--------------------------------------------------------------------------
*/

const handleUserCreated = async () => {
  showAddUser.value = false
  await fetchUsers()
}

/*
|--------------------------------------------------------------------------
| Edit User
|--------------------------------------------------------------------------
*/

const openEditUser = (user) => {
  editingUserId.value = user.id
  showEditUser.value = true
}

const handleUserUpdated = async () => {
  showEditUser.value = false
  editingUserId.value = null

  await fetchUsers()
}

/*
|--------------------------------------------------------------------------
| Delete User
|--------------------------------------------------------------------------
*/

const removeUser = (user) => {
  confirmAction({
    header: 'Delete user?',
    message: `Delete ${user.name}? This action cannot be undone.`,
    acceptLabel: 'Delete user',

    accept: async () => {
      try {
        const response = await deleteUser(user.id)

        toast.success(response.data?.message || `${user.name} deleted successfully.`)

        await fetchUsers()
      } catch (err) {
        console.error('Delete User error:', err)

        toast.error(err.response?.data?.message || 'Failed to delete user.')
      }
    },
  })
}

/*
|--------------------------------------------------------------------------
| Force Logout
|--------------------------------------------------------------------------
*/

const forceLogout = (user) => {
  confirmAction({
    header: 'Force logout?',
    message: `Force ${user.name} to logout from all active sessions?`,
    acceptLabel: 'Force Logout',

    accept: async () => {
      try {
        await api.post(`/users/${user.id}/force-logout`)

        toast.success(`${user.name} has been logged out.`)

        await fetchUsers()
      } catch (err) {
        console.error('Force Logout error:', err)

        toast.error(err.response?.data?.message || 'Failed to force logout.')
      }
    },
  })
}

/*
|--------------------------------------------------------------------------
| Force Login
|--------------------------------------------------------------------------
|
| This replaces the current authentication token with the
| token returned by the backend.
|
*/

const forceLogin = async (user) => {
  try {
    const adminToken = auth.token
    const adminUser = auth.user
    const response = await api.post(`/users/${user.id}/force-login`)

    const data = response.data

    if (!data?.token || !data?.user || !data?.expires_at || !adminToken || !adminUser?.id) {
      throw new Error('Invalid force-login response from server.')
    }

    auth.startImpersonation({
      adminToken,
      adminUser: { id: adminUser.id, name: adminUser.name },
      expiresAt: data.expires_at,
      targetUser: { id: data.user.id, name: data.user.name },
    })
    localStorage.setItem('token', data.token)
    auth.token = data.token
    await auth.fetchUser()

    toast.success(`You are now signed in as ${data.user.name}.`)
    await router.replace('/dashboard')
  } catch (err) {
    console.error('Force Login error:', err)

    toast.error(err.response?.data?.message || err.message || 'Failed to force login.')
  }
}

/*
|--------------------------------------------------------------------------
| Format Date
|--------------------------------------------------------------------------
*/

const formatDate = (date) => {
  if (!date) {
    return '-'
  }

  return new Date(date).toLocaleDateString()
}

/*
|--------------------------------------------------------------------------
| Mounted
|--------------------------------------------------------------------------
*/

onMounted(() => {
  fetchUsers()
})
</script>

<template>
  <div>
    <!-- ========================================================= -->
    <!-- Header -->
    <!-- ========================================================= -->

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Users</h1>

        <p class="mt-1 text-gray-500">Manage all users</p>
      </div>

      <!-- Create User -->
      <button
        v-if="auth.hasPermission('users.create')"
        type="button"
        @click="showAddUser = true"
        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
      >
        + Add User
      </button>
    </div>

    <!-- ========================================================= -->
    <!-- Loading -->
    <!-- ========================================================= -->

    <div v-if="loading" class="rounded-xl bg-white p-6 text-center text-gray-500 shadow-sm">
      Loading users...
    </div>

    <!-- ========================================================= -->
    <!-- Error -->
    <!-- ========================================================= -->

    <div v-else-if="error" class="rounded-xl bg-white p-6 text-center shadow-sm">
      <p class="text-red-500">
        {{ error }}
      </p>

      <button
        type="button"
        @click="fetchUsers"
        class="mt-3 text-sm font-medium text-blue-600 hover:text-blue-800"
      >
        Try Again
      </button>
    </div>

    <!-- ========================================================= -->
    <!-- Users Table -->
    <!-- ========================================================= -->

    <div v-else class="overflow-hidden rounded-xl bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <!-- Table Header -->
          <thead>
            <tr class="border-b bg-gray-50 text-sm text-gray-500">
              <th class="px-6 py-4 font-medium">ID</th>

              <th class="px-6 py-4 font-medium">Name</th>

              <th class="px-6 py-4 font-medium">Email</th>

              <th class="px-6 py-4 font-medium">Role</th>

              <th class="px-6 py-4 font-medium">Created</th>

              <th class="px-6 py-4 text-right font-medium">Actions</th>
            </tr>
          </thead>

          <!-- Table Body -->
          <tbody>
            <tr
              v-for="user in paginatedUsers"
              :key="user.id"
              class="border-b last:border-0 hover:bg-gray-50"
            >
              <!-- ID -->
              <td class="px-6 py-4 font-medium text-gray-700">
                {{ user.id }}
              </td>

              <!-- Name -->
              <td class="px-6 py-4 text-gray-700">
                {{ user.name }}
              </td>

              <!-- Email -->
              <td class="px-6 py-4 text-gray-600">
                {{ user.email }}
              </td>

              <!-- Role -->
              <td class="px-6 py-4">
                <span
                  class="rounded-full px-3 py-1 text-xs font-medium capitalize"
                  :class="
                    user.role === 'super_admin'
                      ? 'bg-purple-100 text-purple-700'
                      : user.role === 'admin'
                        ? 'bg-blue-100 text-blue-700'
                        : 'bg-gray-100 text-gray-700'
                  "
                >
                  {{ user.role || 'user' }}
                </span>
              </td>

              <!-- Created -->
              <td class="px-6 py-4 text-gray-600">
                {{ formatDate(user.created_at) }}
              </td>

              <!-- Actions -->
              <td class="px-6 py-4">
                <div class="flex flex-wrap justify-end gap-1">
                  <!-- ======================================= -->
                  <!-- Show -->
                  <!-- ======================================= -->

                  <RouterLink
                    v-if="auth.hasPermission('users.view')"
                    :to="`/user/${user.id}`"
                    :aria-label="`Show ${user.name}`"
                    :title="`Show ${user.name}`"
                    class="inline-flex h-8 w-8 items-center justify-center rounded text-gray-500 transition hover:bg-gray-100 hover:text-gray-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                  >
                    <Eye :size="16" aria-hidden="true" />
                  </RouterLink>

                  <!-- ======================================= -->
                  <!-- Edit -->
                  <!-- ======================================= -->

                  <button
                    v-if="auth.hasPermission('users.update')"
                    type="button"
                    @click="openEditUser(user)"
                    :aria-label="`Edit ${user.name}`"
                    :title="`Edit ${user.name}`"
                    class="inline-flex h-8 w-8 items-center justify-center rounded text-blue-600 transition hover:bg-blue-50 hover:text-blue-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                  >
                    <Pencil :size="16" aria-hidden="true" />
                  </button>

                  <!-- ======================================= -->
                  <!-- Delete -->
                  <!-- ======================================= -->

                  <button
                    v-if="auth.hasPermission('users.delete')"
                    type="button"
                    @click="removeUser(user)"
                    :aria-label="`Delete ${user.name}`"
                    :title="`Delete ${user.name}`"
                    class="inline-flex h-8 w-8 items-center justify-center rounded text-red-600 transition hover:bg-red-50 hover:text-red-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                  >
                    <Trash2 :size="16" aria-hidden="true" />
                  </button>

                  <!-- ======================================= -->
                  <!-- Force Login -->
                  <!-- ======================================= -->

                  <button
                    v-if="auth.isSuperAdmin"
                    type="button"
                    @click="forceLogin(user)"
                    :aria-label="`Force login as ${user.name}`"
                    :title="`Force login as ${user.name}`"
                    class="inline-flex h-8 w-8 items-center justify-center rounded text-green-600 transition hover:bg-green-50 hover:text-green-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-500"
                  >
                    <LogIn :size="16" aria-hidden="true" />
                  </button>

                  <!-- ======================================= -->
                  <!-- Force Logout -->
                  <!-- ======================================= -->

                  <button
                    v-if="auth.isSuperAdmin && user.id !== auth.user?.id"
                    type="button"
                    @click="forceLogout(user)"
                    :aria-label="`Force logout ${user.name}`"
                    :title="`Force logout ${user.name}`"
                    class="inline-flex h-8 w-8 items-center justify-center rounded text-orange-600 transition hover:bg-orange-50 hover:text-orange-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-500"
                  >
                    <LogOut :size="16" aria-hidden="true" />
                  </button>
                </div>
              </td>
            </tr>

            <!-- ================================================= -->
            <!-- No Users -->
            <!-- ================================================= -->

            <tr v-if="users.length === 0">
              <td colspan="6" class="px-6 py-10 text-center text-gray-500">No users found.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ======================================================= -->
      <!-- Pagination -->
      <!-- ======================================================= -->

      <Paginator
        v-if="users.length > 0"
        v-model:first="first"
        v-model:rows="rowsPerPage"
        :totalRecords="users.length"
        :rowsPerPageOptions="[10, 20, 30]"
        :alwaysShow="false"
        template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown CurrentPageReport"
        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} users"
        aria-label="Users pagination"
        class="border-t border-gray-100"
      />
    </div>

    <!-- ========================================================= -->
    <!-- Create User Modal -->
    <!-- ========================================================= -->

    <CreateUser :show="showAddUser" @close="showAddUser = false" @created="handleUserCreated" />

    <!-- ========================================================= -->
    <!-- Edit User Modal -->
    <!-- ========================================================= -->

    <EditUser
      :show="showEditUser"
      :user-id="editingUserId"
      @close="((showEditUser = false), (editingUserId = null))"
      @updated="handleUserUpdated"
    />
  </div>
</template>
