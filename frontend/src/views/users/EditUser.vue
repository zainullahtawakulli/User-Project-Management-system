<script setup>
import { ref, watch } from 'vue'
import { getUser, updateUser } from '@/services/userapi/user'
import { getRoles } from '@/services/roleService'
import { useAuthStore } from '@/stores/auth'
import { toast } from 'vue-sonner'

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  userId: {
    type: [Number, String],
    default: null,
  },
})

const emit = defineEmits(['close', 'updated'])
const auth = useAuthStore()
const roles = ref([])
const loadingRoles = ref(false)

const user = ref(null)

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  status: 'active',
  role_id: '',
})

const loading = ref(true)
const saving = ref(false)
const error = ref(null)
const errors = ref({})

const fetchUser = async () => {
  if (!props.userId) return

  loading.value = true
  error.value = null

  try {
    const response = await getUser(props.userId)

    user.value = response.data.user

    form.value = {
      name: user.value.name,
      email: user.value.email,
      password: '',
      password_confirmation: '',
      status: user.value.status,
      role_id: user.value.role_id || user.value.roleRelation?.id || '',
    }
  } catch (err) {
    console.error('Get User error:', err)

    error.value = err.response?.data?.message || 'Failed to load user.'
  } finally {
    loading.value = false
  }
}

const fetchRoles = async () => {
  if (!auth.can('roles.view')) return

  loadingRoles.value = true
  try {
    const response = await getRoles()
    roles.value = response.data.roles
  } catch (err) {
    console.error('Fetch roles error:', err)
    error.value = err.response?.data?.message || 'Failed to load roles.'
  } finally {
    loadingRoles.value = false
  }
}

const closeModal = () => {
  if (saving.value) return

  emit('close')
}

const submit = async () => {
  errors.value = {}
  error.value = null
  saving.value = true

  try {
    const data = {
      name: form.value.name,
      email: form.value.email,
      status: form.value.status,
    }
    if (auth.can('roles.view')) {
      data.role_id = Number(form.value.role_id)
    }

    // Only send password if user entered one
    if (form.value.password) {
      data.password = form.value.password
      data.password_confirmation = form.value.password_confirmation
    }

    const response = await updateUser(props.userId, data)

    user.value = response.data.user

    toast.success(response.data.message || 'User updated successfully.')
    emit('updated', response.data.user)

    // Clear password fields
    form.value.password = ''
    form.value.password_confirmation = ''

    setTimeout(() => {
      closeModal()
    }, 800)
  } catch (err) {
    console.error('Update User error:', err)

    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {}
      toast.error('Please correct the highlighted fields.')
    } else {
      toast.error(err.response?.data?.message || 'Failed to update user.')
    }
  } finally {
    saving.value = false
  }
}

watch(
  () => [props.show, props.userId],
  async ([show]) => {
    if (show) {
      await Promise.all([fetchUser(), fetchRoles()])

      // Older accounts may still have the role stored in the legacy `role`
      // column instead of `role_id`.
      if (auth.can('roles.view') && !form.value.role_id) {
        form.value.role_id = roles.value.find((role) => role.slug === user.value?.role)?.id || ''
      }
    }
  },
  { immediate: true },
)
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
      @keydown.esc="closeModal"
    >
      <div class="absolute inset-0 bg-black/50" @click="closeModal"></div>
      <section
        role="dialog"
        aria-modal="true"
        aria-labelledby="edit-user-title"
        class="relative z-10 w-full max-w-2xl overflow-hidden rounded-xl bg-white shadow-xl"
      >
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
          <div>
            <h1 id="edit-user-title" class="text-xl font-semibold text-gray-800">Edit User</h1>

            <p class="mt-1 text-sm text-gray-500">Update user information.</p>
          </div>

          <button
            type="button"
            :disabled="saving"
            @click="closeModal"
            class="flex h-9 w-9 items-center justify-center rounded-lg text-xl text-gray-500 hover:bg-gray-100 disabled:opacity-50"
            aria-label="Close edit user dialog"
          >
            ×
          </button>
        </div>

        <div class="max-h-[80vh] overflow-y-auto px-6 py-6">
          <!-- Loading -->
          <div v-if="loading" class="p-8 text-center text-gray-500">Loading user...</div>

          <!-- Error Loading User -->
          <div v-else-if="error && !user" class="p-8 text-center">
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

          <!-- Form -->
          <div v-else-if="user">
            <!-- Form Header -->
            <div class="border-b border-gray-100 px-6 py-5">
              <h2 class="text-lg font-semibold text-gray-800">User Information</h2>

              <p class="mt-1 text-sm text-gray-500">Update the information below.</p>
            </div>

            <form class="space-y-6 p-6" @submit.prevent="submit">
              <!-- General Error -->
              <div
                v-if="error"
                class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-600"
              >
                {{ error }}
              </div>

              <!-- Name -->
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700"> Name </label>

                <input
                  v-model="form.name"
                  type="text"
                  placeholder="Enter name"
                  autocomplete="name"
                  class="w-full rounded-lg border px-4 py-3 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                  :class="{
                    'border-red-500': errors.name,
                  }"
                />

                <p v-if="errors.name" class="mt-1 text-sm text-red-500">
                  {{ errors.name[0] }}
                </p>
              </div>

              <!-- Email -->
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700"> Email </label>

                <input
                  v-model="form.email"
                  type="email"
                  placeholder="Enter email"
                  autocomplete="email"
                  class="w-full rounded-lg border px-4 py-3 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                  :class="{
                    'border-red-500': errors.email,
                  }"
                />

                <p v-if="errors.email" class="mt-1 text-sm text-red-500">
                  {{ errors.email[0] }}
                </p>
              </div>

              <!-- Status -->
              <div v-if="auth.can('roles.view')">
                <label class="mb-1 block text-sm font-medium text-gray-700"> Role </label>
                <select
                  v-model="form.role_id"
                  required
                  :disabled="loadingRoles"
                  class="w-full rounded-lg border px-4 py-3 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:bg-gray-100"
                >
                  <option v-for="role in roles" :key="role.id" :value="role.id">
                    {{ role.name }}
                  </option>
                </select>
                <p v-if="errors.role_id" class="mt-1 text-sm text-red-500">
                  {{ errors.role_id[0] }}
                </p>
              </div>

              <!-- Status -->
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700"> Status </label>

                <select
                  v-model="form.status"
                  class="w-full rounded-lg border px-4 py-3 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                  :class="{
                    'border-red-500': errors.status,
                  }"
                >
                  <option value="pending">Pending</option>

                  <option value="active">Active</option>

                  <option value="inactive">Inactive</option>
                </select>

                <p v-if="errors.status" class="mt-1 text-sm text-red-500">
                  {{ errors.status[0] }}
                </p>
              </div>

              <!-- Password -->
              <div class="border-t border-gray-100 pt-6">
                <h3 class="text-base font-semibold text-gray-800">Change Password</h3>

                <p class="mt-1 mb-4 text-sm text-gray-500">
                  Leave the password fields empty if you don't want to change the password.
                </p>

                <div class="space-y-5">
                  <!-- Password -->
                  <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                      New Password
                    </label>

                    <input
                      v-model="form.password"
                      type="password"
                      placeholder="Leave empty to keep current password"
                      autocomplete="new-password"
                      class="w-full rounded-lg border px-4 py-3 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                      :class="{
                        'border-red-500': errors.password,
                      }"
                    />

                    <p v-if="errors.password" class="mt-1 text-sm text-red-500">
                      {{ errors.password[0] }}
                    </p>
                  </div>

                  <!-- Confirm Password -->
                  <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                      Confirm New Password
                    </label>

                    <input
                      v-model="form.password_confirmation"
                      type="password"
                      placeholder="Confirm new password"
                      autocomplete="new-password"
                      class="w-full rounded-lg border px-4 py-3 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                      :class="{
                        'border-red-500': errors.password,
                      }"
                    />
                  </div>
                </div>
              </div>

              <!-- Buttons -->
              <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
                <button
                  type="button"
                  :disabled="saving"
                  @click="closeModal"
                  class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  Cancel
                </button>

                <button
                  type="submit"
                  :disabled="saving"
                  class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-400"
                >
                  {{ saving ? 'Updating...' : 'Update User' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </section>
    </div>
  </Teleport>
</template>
