<script setup>
import { ref, onMounted } from 'vue'
import { getUsers } from '@/services/userapi/user'

const users = ref([])
const loading = ref(false)
const error = ref(null)

const fetchUsers = async () => {
  loading.value = true
  error.value = null

  try {
    const response = await getUsers()

    users.value = response.data.users
  } catch (err) {
    console.error(err)

    error.value = 'Failed to load users'
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
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Users</h1>

        <p class="mt-1 text-gray-500">Manage all users</p>
      </div>

      <RouterLink
        to="/user/create"
        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
      >
        + Add User
      </RouterLink>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="rounded-xl bg-white p-6 text-center text-gray-500 shadow-sm">
      Loading users...
    </div>

    <!-- Error -->
    <div v-else-if="error" class="rounded-xl bg-white p-6 text-center text-red-500 shadow-sm">
      {{ error }}
    </div>

    <!-- Table -->
    <div v-else class="overflow-hidden rounded-xl bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="border-b bg-gray-50 text-sm text-gray-500">
              <th class="px-6 py-4">ID</th>
              <th class="px-6 py-4">Name</th>
              <th class="px-6 py-4">Email</th>
              <th class="px-6 py-4">Created</th>
              <th class="px-6 py-4 text-right">Actions</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="user in users"
              :key="user.id"
              class="border-b last:border-0 hover:bg-gray-50"
            >
              <td class="px-6 py-4 font-medium text-gray-700">
                {{ user.id }}
              </td>

              <td class="px-6 py-4 text-gray-700">
                {{ user.name }}
              </td>

              <td class="px-6 py-4 text-gray-600">
                {{ user.email }}
              </td>

              <td class="px-6 py-4 text-gray-600">
                {{ user.created_at }}
              </td>

              <td class="px-6 py-4">
                <div class="flex justify-end gap-3 text-sm">
                  <RouterLink :to="`/user/${user.id}`" class="text-gray-600 hover:text-gray-800">
                    Show
                  </RouterLink>

                  <RouterLink
                    :to="`/user/${user.id}/edit`"
                    class="text-blue-600 hover:text-blue-800"
                  >
                    Update
                  </RouterLink>

                  <button type="button" class="text-red-600 hover:text-red-800">Delete</button>
                </div>
              </td>
            </tr>

            <!-- No users -->
            <tr v-if="users.length === 0">
              <td colspan="5" class="px-6 py-8 text-center text-gray-500">No users found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
