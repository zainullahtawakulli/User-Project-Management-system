<script setup>
import { computed, ref, onMounted, watch } from 'vue'
import { ChevronLeft, ChevronRight, Eye, Pencil, Trash2 } from '@lucide/vue'
import { getUsers, deleteUser } from '@/services/userapi/user'
import { toast } from 'vue-sonner'

import CreateUser from '@/views/users/CreateUser.vue'
import EditUser from '@/views/users/EditUser.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const users = ref([])
const loading = ref(false)
const error = ref(null)
const showAddUser = ref(false)
const showEditUser = ref(false)
const editingUserId = ref(null)
const currentPage = ref(1)
const rowsPerPage = 7

const pageCount = computed(() => Math.max(1, Math.ceil(users.value.length / rowsPerPage)))
const paginatedUsers = computed(() => {
  const start = (currentPage.value - 1) * rowsPerPage
  return users.value.slice(start, start + rowsPerPage)
})
const pageNumbers = computed(() => {
  const start = Math.max(1, Math.min(currentPage.value - 2, pageCount.value - 6))
  const end = Math.min(pageCount.value, start + 6)

  return Array.from({ length: end - start + 1 }, (_, index) => start + index)
})

watch(pageCount, (count) => {
  if (currentPage.value > count) currentPage.value = count
})

const fetchUsers = async () => {
  loading.value = true
  error.value = null

  try {
    const response = await getUsers()

    users.value = response.data.users
  } catch (err) {
    console.error('Fetch Users error:', err)

    error.value = err.response?.data?.message || 'Failed to load users.'
  } finally {
    loading.value = false
  }
}

const removeUser = async (user) => {
  const confirmed = window.confirm(`Are you sure you want to delete ${user.name}?`)

  if (!confirmed) {
    return
  }

  try {
    const response = await deleteUser(user.id)
    toast.success(response.data.message || `${user.name} deleted successfully.`)

    await fetchUsers()
  } catch (err) {
    console.error('Delete User error:', err)

    toast.error(err.response?.data?.message || 'Failed to delete user.')
  }
}

const handleUserCreated = async () => {
  showAddUser.value = false

  await fetchUsers()
}

const openEditUser = (user) => {
  editingUserId.value = user.id
  showEditUser.value = true
}

const goToPage = (page) => {
  currentPage.value = page
}

onMounted(() => {
  fetchUsers()
})
</script>

<template>
  <div>
    <!-- Header -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Users</h1>

        <p class="mt-1 text-gray-500">Manage all users</p>
      </div>

      <button
        type="button"
        v-if="auth.hasPermission('users.create')"
        @click="showAddUser = true"
        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700"
      >
        + Add User
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="rounded-xl bg-white p-6 text-center text-gray-500 shadow-sm">
      Loading users...
    </div>

    <!-- Error -->
    <div v-else-if="error" class="rounded-xl bg-white p-6 text-center text-red-500 shadow-sm">
      <p>{{ error }}</p>

      <button
        type="button"
        @click="fetchUsers"
        class="mt-3 text-sm font-medium text-blue-600 hover:text-blue-800"
      >
        Try Again
      </button>
    </div>

    <!-- Table -->
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
              <td class="px-6 py-4 font-medium text-gray-700">{{ user.id }}</td>

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
                    user.role === 'admin'
                      ? 'bg-blue-100 text-blue-700'
                      : 'bg-gray-100 text-gray-700'
                  "
                >
                  {{ user.role || 'user' }}
                </span>
              </td>

              <!-- Created -->
              <td class="px-6 py-4 text-gray-600">
                {{ user.created_at?.slice(0, 10) }}
              </td>

              <!-- Actions -->
              <td class="px-6 py-4">
                <div class="flex justify-end gap-1">
                  <!-- Show -->
                  <RouterLink
                    :to="`/user/${user.id}`"
                    :aria-label="`Show ${user.name}`"
                    :title="`Show ${user.name}`"
                    class="inline-flex h-8 w-8 items-center justify-center rounded text-gray-500 transition hover:bg-gray-100 hover:text-gray-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                  >
                    <Eye :size="16" aria-hidden="true" />
                  </RouterLink>

                  <!-- Update -->
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

                  <!-- Delete -->
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
                </div>
              </td>
            </tr>

            <!-- No Users -->
            <tr v-if="users.length === 0">
              <td colspan="6" class="px-6 py-10 text-center text-gray-500">No users found.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="users.length > 0" class="flex justify-center border-t border-gray-100 px-4 py-4">
        <nav
          class="flex items-center gap-1 rounded-lg bg-gray-50 p-1"
          aria-label="Users pagination"
        >
          <button
            type="button"
            aria-label="Previous page"
            title="Previous page"
            :disabled="currentPage === 1"
            class="inline-flex h-9 w-9 items-center justify-center rounded border border-gray-200 text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
            @click="goToPage(currentPage - 1)"
          >
            <ChevronLeft :size="16" aria-hidden="true" />
          </button>

          <button
            v-for="page in pageNumbers"
            :key="page"
            type="button"
            :aria-label="`Page ${page}`"
            :aria-current="currentPage === page ? 'page' : undefined"
            class="h-9 min-w-9 rounded border px-2 text-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
            :class="
              currentPage === page
                ? 'border-blue-600 bg-blue-600 font-semibold text-white'
                : 'border-gray-200 text-gray-600 hover:bg-gray-50'
            "
            @click="goToPage(page)"
          >
            {{ page }}
          </button>

          <button
            type="button"
            aria-label="Next page"
            title="Next page"
            :disabled="currentPage === pageCount"
            class="inline-flex h-9 w-9 items-center justify-center rounded border border-gray-200 text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
            @click="goToPage(currentPage + 1)"
          >
            <ChevronRight :size="16" aria-hidden="true" />
          </button>
        </nav>
      </div>
    </div>

    <!-- Add User Modal -->
    <CreateUser :show="showAddUser" @close="showAddUser = false" @created="handleUserCreated" />
    <EditUser
      :show="showEditUser"
      :user-id="editingUserId"
      @close="showEditUser = false"
      @updated="fetchUsers"
    />
  </div>
</template>
