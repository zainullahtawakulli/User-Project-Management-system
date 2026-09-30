<script setup>
import { getUsers } from '@/services/userapi/user'
import { ref, computed, onMounted } from 'vue'

const stats = [
  {
    title: 'Total Users',
    value: 120,
    icon: '👥',
  },
  {
    title: 'Pending Users',
    value: 15,
    icon: '⏳',
  },
  {
    title: 'Active Users',
    value: 95,
    icon: '✓',
  },
  {
    title: 'Inactive Users',
    value: 10,
    icon: '○',
  },
]

const users = ref([])
const loading = ref(false)
const error = ref(null)

const recentUsers = computed(() => {
  return users.value.slice(0, 5)
})

const fetchUsers = async () => {
  loading.value = true
  error.value = null

  try {
    const response = await getUsers()

    users.value = response.data.users
  } catch (err) {
    console.error(err)
    error.value = 'Failed to load users.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchUsers()
})
</script>

<template>
  <div>
    <!-- Page Header -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>

        <p class="mt-1 text-sm text-gray-500">Welcome back! Here's what's happening today.</p>
      </div>

      <RouterLink
        to="/user/create"
        class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700"
      >
        + Add User
      </RouterLink>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="stat in stats"
        :key="stat.title"
        class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">
              {{ stat.title }}
            </p>

            <h2 class="mt-2 text-2xl font-bold text-gray-800">
              {{ stat.value }}
            </h2>
          </div>

          <div
            class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-50 text-lg text-blue-600"
          >
            {{ stat.icon }}
          </div>
        </div>
      </div>
    </div>

    <!-- Users Section -->
    <div class="mt-8 rounded-xl border border-gray-100 bg-white shadow-sm">
      <!-- Section Header -->
      <div
        class="flex flex-col gap-3 border-b border-gray-100 p-5 sm:flex-row sm:items-center sm:justify-between"
      >
        <div>
          <h2 class="text-lg font-semibold text-gray-800">Recent Users</h2>

          <p class="mt-1 text-sm text-gray-500">Recently registered users</p>
        </div>

        <RouterLink to="/user" class="text-sm font-medium text-blue-600 hover:text-blue-700">
          View All →
        </RouterLink>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="p-10 text-center text-sm text-gray-500">Loading users...</div>

      <!-- Error -->
      <div v-else-if="error" class="p-10 text-center text-sm text-red-500">
        {{ error }}
      </div>

      <!-- Table -->
      <div v-else class="overflow-x-auto">
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
            <tr
              v-for="user in recentUsers"
              :key="user.id"
              class="border-b border-gray-100 last:border-0 hover:bg-gray-50"
            >
              <!-- ID -->
              <td class="px-5 py-4 text-sm font-medium text-gray-700">#{{ user.id }}</td>

              <!-- Name -->
              <td class="px-5 py-4">
                <div class="flex items-center gap-3">
                  <div
                    class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-600"
                  >
                    {{ user.name?.charAt(0).toUpperCase() }}
                  </div>

                  <span class="text-sm font-medium text-gray-700">
                    {{ user.name }}
                  </span>
                </div>
              </td>

              <!-- Email -->
              <td class="px-5 py-4 text-sm text-gray-500">
                {{ user.email }}
              </td>

              <!-- Status -->
              <td class="px-5 py-4">
                <span
                  class="rounded-full px-3 py-1 text-xs font-medium"
                  :class="{
                    'bg-green-100 text-green-700': user.status === 'Active',

                    'bg-yellow-100 text-yellow-700': user.status === 'Pending',

                    'bg-red-100 text-red-700': user.status === 'Inactive',
                  }"
                >
                  {{ user.status }}
                </span>
              </td>

              <!-- Actions -->
              <td class="px-5 py-4">
                <div class="flex justify-end gap-3 text-sm">
                  <RouterLink :to="`/user/${user.id}`" class="text-gray-500 hover:text-gray-800">
                    Show
                  </RouterLink>

                  <RouterLink
                    :to="`/user/${user.id}/edit`"
                    class="text-blue-600 hover:text-blue-800"
                  >
                    Edit
                  </RouterLink>

                  <button type="button" class="text-red-600 hover:text-red-800">Delete</button>
                </div>
              </td>
            </tr>

            <!-- No users -->
            <tr v-if="recentUsers.length === 0">
              <td colspan="5" class="px-5 py-10 text-center text-sm text-gray-500">
                No users found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
