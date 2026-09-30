<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { ArrowRight, Eye, Pencil, Trash2 } from '@lucide/vue'
import { toast } from 'vue-sonner'

import { getDashboard } from '@/services/dashboard/dashboard'
import { deleteUser } from '@/services/userapi/user'
import { useAuthStore } from '@/stores/auth'
import CreateUser from '@/views/users/CreateUser.vue'
import EditUser from '@/views/users/EditUser.vue'

const auth = useAuthStore()
const users = ref([])
const dashboardRole = ref(null)
const stats = ref({
  total: 0,
  active: 0,
  inactive: 0,
  total_projects: 0,
  total_tasks: 0,
  completed_tasks: 0,
})
const showAddUser = ref(false)
const showEditUser = ref(false)
const editingUserId = ref(null)
const loading = ref(true)
const error = ref(null)

const isSuperAdmin = computed(() => dashboardRole.value === 'super_admin' || auth.isSuperAdmin)
const canCreateUsers = computed(() => auth.can('users.create'))
const dashboardCards = computed(() => isSuperAdmin.value
  ? [
      { label: 'Total Users', value: stats.value.total, color: 'blue' },
      { label: 'Active Users', value: stats.value.active, color: 'green' },
      { label: 'Inactive Users', value: stats.value.inactive, color: 'red' },
    ]
  : [
      { label: 'Total Projects', value: stats.value.total_projects, color: 'blue' },
      { label: 'Total Tasks', value: stats.value.total_tasks, color: 'amber' },
      { label: 'Completed Tasks', value: stats.value.completed_tasks, color: 'green' },
    ])

const fetchDashboard = async () => {
  loading.value = true
  error.value = null

  try {
    const response = await getDashboard()
    dashboardRole.value = response.data.role
    stats.value = { ...stats.value, ...response.data.stats }
    users.value = response.data.recent_users || []
  } catch (err) {
    console.error('Dashboard error:', err)
    error.value = err.response?.data?.message || 'Failed to load dashboard.'
  } finally {
    loading.value = false
  }
}

const removeUser = async (user) => {
  if (!window.confirm(`Are you sure you want to delete ${user.name}?`)) return

  try {
    const response = await deleteUser(user.id)
    toast.success(response.data.message || `${user.name} deleted successfully.`)
    await fetchDashboard()
  } catch (err) {
    console.error('Delete user error:', err)
    toast.error(err.response?.data?.message || 'Failed to delete user.')
  }
}

const openEditUser = (user) => {
  editingUserId.value = user.id
  showEditUser.value = true
}

onMounted(fetchDashboard)
</script>

<template>
  <section class="space-y-6">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
        <p class="mt-1 text-sm text-gray-500">Welcome back! Here's what's happening today.</p>
      </div>
      <button
        v-if="canCreateUsers"
        type="button"
        class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700"
        @click="showAddUser = true"
      >
        + Add User
      </button>
    </header>

    <div
      v-if="error"
      class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-600"
    >
      {{ error }}
      <button type="button" class="ml-3 font-semibold underline" @click="fetchDashboard">
        Retry
      </button>
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
      <article
        v-for="card in dashboardCards"
        :key="card.label"
        class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm"
      >
        <p class="text-sm font-medium text-gray-500">{{ card.label }}</p>
        <p v-if="loading" class="mt-2 h-8 w-16 animate-pulse rounded bg-gray-200"></p>
        <p v-else class="mt-2 text-2xl font-bold text-gray-800">{{ card.value }}</p>
      </article>
    </div>

    <section v-if="isSuperAdmin" class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
      <header class="flex flex-col gap-3 border-b border-gray-100 p-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-lg font-semibold text-gray-800">Recent Users</h2>
          <p class="mt-1 text-sm text-gray-500">Recently registered users</p>
        </div>
        <RouterLink to="/user" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-700">
          View All <ArrowRight :size="16" aria-hidden="true" />
        </RouterLink>
      </header>

      <div v-if="loading" class="p-10 text-center text-sm text-gray-500">Loading users...</div>
      <div v-else-if="users.length" class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="border-b border-gray-100 bg-gray-50 text-sm text-gray-500">
              <th class="px-5 py-3 font-medium">ID</th>
              <th class="px-5 py-3 font-medium">Name</th>
              <th class="px-5 py-3 font-medium">Email</th>
              <th class="px-5 py-3 font-medium">Status</th>
              <th class="px-5 py-3 text-right font-medium">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id" class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
              <td class="px-5 py-4 text-sm font-medium text-gray-700">{{ user.id }}</td>
              <td class="px-5 py-4 text-sm font-medium text-gray-700">{{ user.name }}</td>
              <td class="px-5 py-4 text-sm text-gray-500">{{ user.email }}</td>
              <td class="px-5 py-4 text-sm capitalize text-gray-600">{{ user.status }}</td>
              <td class="px-5 py-4">
                <div class="flex justify-end gap-1">
                  <RouterLink :to="`/user/${user.id}`" :aria-label="`Show ${user.name}`" class="inline-flex h-8 w-8 items-center justify-center rounded text-gray-500 hover:bg-gray-100">
                    <Eye :size="16" aria-hidden="true" />
                  </RouterLink>
                  <button type="button" :aria-label="`Edit ${user.name}`" class="inline-flex h-8 w-8 items-center justify-center rounded text-blue-600 hover:bg-blue-50" @click="openEditUser(user)">
                    <Pencil :size="16" aria-hidden="true" />
                  </button>
                  <button type="button" :aria-label="`Delete ${user.name}`" class="inline-flex h-8 w-8 items-center justify-center rounded text-red-600 hover:bg-red-50" @click="removeUser(user)">
                    <Trash2 :size="16" aria-hidden="true" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="p-10 text-center text-sm text-gray-500">No users found.</div>
    </section>

    <CreateUser v-if="canCreateUsers" :show="showAddUser" @close="showAddUser = false" @created="fetchDashboard" />
    <EditUser
      v-if="isSuperAdmin"
      :show="showEditUser"
      :user-id="editingUserId"
      @close="showEditUser = false"
      @updated="fetchDashboard"
    />
  </section>
</template>
