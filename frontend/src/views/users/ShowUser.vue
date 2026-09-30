<script setup>
import { ref, onMounted } from 'vue'
import { ArrowLeft, Pencil } from '@lucide/vue'
import { useRoute, useRouter } from 'vue-router'
import { getUser } from '@/services/userapi/user'
import EditUser from '@/views/users/EditUser.vue'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const user = ref(null)
const loading = ref(true)
const error = ref(null)
const showEditUser = ref(false)

const fetchUser = async () => {
  loading.value = true
  error.value = null

  try {
    const response = await getUser(route.params.id)

    user.value = response.data.user
  } catch (err) {
    console.error('Get User error:', err)

    error.value = err.response?.data?.message || 'Failed to load user.'
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push('/user')
}

const handleUserUpdated = (updatedUser) => {
  user.value = updatedUser
  showEditUser.value = false
}

onMounted(() => {
  fetchUser()
})
</script>

<template>
  <div>
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">User Details</h1>

        <p class="mt-1 text-sm text-gray-500">View user information</p>
      </div>

      <button
        type="button"
        @click="goBack"
        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
      >
        <ArrowLeft :size="16" aria-hidden="true" />
        Back
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="rounded-xl bg-white p-8 text-center text-gray-500 shadow-sm">
      Loading user...
    </div>

    <!-- Error -->
    <div v-else-if="error" class="rounded-xl bg-white p-8 text-center shadow-sm">
      <p class="text-red-500">
        {{ error }}
      </p>

      <button
        type="button"
        @click="fetchUser"
        class="mt-4 text-sm font-medium text-blue-600 hover:text-blue-800"
      >
        Try Again
      </button>
    </div>

    <!-- User -->
    <div v-else-if="user" class="rounded-xl bg-white shadow-sm">
      <!-- User Header -->
      <div class="border-b border-gray-100 px-6 py-6">
        <div class="flex items-center gap-4">
          <div
            class="flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 text-2xl font-bold text-blue-600"
          >
            {{ user.name?.charAt(0).toUpperCase() }}
          </div>

          <div>
            <h2 class="text-xl font-semibold text-gray-800">
              {{ user.name }}
            </h2>

            <p class="text-sm text-gray-500">
              {{ user.email }}
            </p>
          </div>
        </div>
      </div>

      <!-- User Information -->
      <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">
        <!-- ID -->
        <div>
          <p class="text-sm font-medium text-gray-500">User ID</p>

          <p class="mt-1 text-gray-800">#{{ user.id }}</p>
        </div>

        <!-- Name -->
        <div>
          <p class="text-sm font-medium text-gray-500">Name</p>

          <p class="mt-1 text-gray-800">
            {{ user.name }}
          </p>
        </div>

        <!-- Email -->
        <div>
          <p class="text-sm font-medium text-gray-500">Email</p>

          <p class="mt-1 text-gray-800">
            {{ user.email }}
          </p>
        </div>

        <!-- Status -->
        <div>
          <p class="text-sm font-medium text-gray-500">Status</p>

          <span
            class="mt-1 inline-block rounded-full px-3 py-1 text-xs font-medium"
            :class="{
              'bg-green-100 text-green-700': user.status === 'active',
              'bg-red-100 text-red-700': user.status === 'inactive',
              'bg-yellow-100 text-yellow-700': user.status === 'pending',
            }"
          >
            {{ user.status }}
          </span>
        </div>

        <!-- Created -->
        <div>
          <p class="text-sm font-medium text-gray-500">Created At</p>

          <p class="mt-1 text-gray-800">
            {{ user.created_at?.slice(0, 10) }}
          </p>
        </div>

        <!-- Updated -->
        <div>
          <p class="text-sm font-medium text-gray-500">Updated At</p>

          <p class="mt-1 text-gray-800">
            {{ user.updated_at?.slice(0, 10) }}
          </p>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end gap-3 border-t border-gray-100 px-6 py-4">
        <button
          v-if="auth.hasPermission('users.update')"
          type="button"
          @click="showEditUser = true"
          class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700"
        >
          <Pencil :size="16" aria-hidden="true" />
          Update User
        </button>

        <button
          type="button"
          @click="goBack"
          class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
        >
          Back
        </button>
      </div>
    </div>
    <EditUser
      :show="showEditUser"
      :user-id="user?.id"
      @close="showEditUser = false"
      @updated="handleUserUpdated"
    />
  </div>
</template>
