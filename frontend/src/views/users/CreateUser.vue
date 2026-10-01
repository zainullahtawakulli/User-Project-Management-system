<script setup>
import { ref, watch } from 'vue'
import { createUser } from '@/services/userapi/user'
import { getRoles } from '@/services/roleService'
import { useAuthStore } from '@/stores/auth'
import { toast } from 'vue-sonner'

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close', 'created'])
const auth = useAuthStore()
const roles = ref([])
const loadingRoles = ref(false)

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  status: 'active',
  role_id: '',
})

const errors = ref({})
const loading = ref(false)

const resetForm = () => {
  form.value = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    status: 'active',
    role_id: roles.value.find((role) => role.slug === 'user')?.id || '',
  }

  errors.value = {}
}

const closeModal = () => {
  if (loading.value) {
    return
  }

  emit('close')
}

const submit = async () => {
  errors.value = {}
  loading.value = true

  try {
    const payload = { ...form.value }
    if (!auth.can('roles.view')) {
      delete payload.role_id
    } else if (payload.role_id) {
      payload.role_id = Number(payload.role_id)
    }
    const response = await createUser(payload)

    toast.success(response.data.message || 'User created successfully.')

    emit('created', response.data.user)
    resetForm()

    setTimeout(() => {
      emit('close')
    }, 700)
  } catch (error) {
    console.error(error)

    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
      toast.error('Please correct the highlighted fields.')
    } else {
      toast.error(error.response?.data?.message || 'Failed to create user.')
    }
  } finally {
    loading.value = false
  }
}

watch(
  () => props.show,
  async (value) => {
    if (value) {
      errors.value = {}
      if (auth.can('roles.view') && roles.value.length === 0) {
        loadingRoles.value = true
        try {
          const response = await getRoles()
          roles.value = response.data.roles
        } catch (error) {
          toast.error(error.response?.data?.message || 'Failed to load roles.')
        } finally {
          loadingRoles.value = false
        }
      }
      if (auth.can('roles.view') && !form.value.role_id) {
        form.value.role_id = roles.value.find((role) => role.slug === 'user')?.id || ''
      }
    }
  },
)
</script>

<template>
  <!-- Modal -->
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
      @keydown.escape="closeModal"
    >
      <!-- Background -->
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal"></div>

      <!-- Modal -->
      <div class="relative z-10 w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
          <div>
            <h2 class="text-xl font-semibold text-gray-800">Add User</h2>

            <p class="mt-1 text-sm text-gray-500">Create a new user account.</p>
          </div>

          <!-- Close -->
          <button
            type="button"
            :disabled="loading"
            class="flex h-9 w-9 items-center justify-center rounded-lg text-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 disabled:cursor-not-allowed"
            @click="closeModal"
          >
            ×
          </button>
        </div>

        <!-- Body -->
        <div class="max-h-[80vh] overflow-y-auto px-6 py-6">
          <form class="space-y-5" @submit.prevent="submit">
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

            <!-- Password -->
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700"> Password </label>

              <input
                v-model="form.password"
                type="password"
                placeholder="Minimum 8 characters"
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
              <label class="mb-1 block text-sm font-medium text-gray-700"> Confirm Password </label>

              <input
                v-model="form.password_confirmation"
                type="password"
                placeholder="Confirm password"
                autocomplete="new-password"
                class="w-full rounded-lg border px-4 py-3 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                :class="{
                  'border-red-500': errors.password,
                }"
              />
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
                <option value="active">Active</option>

                <option value="inactive">Inactive</option>
              </select>

              <p v-if="errors.status" class="mt-1 text-sm text-red-500">
                {{ errors.status[0] }}
              </p>
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
              <button
                type="button"
                :disabled="loading"
                class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                @click="closeModal"
              >
                Cancel
              </button>

              <button
                type="submit"
                :disabled="loading"
                class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-400"
              >
                {{ loading ? 'Creating...' : 'Create User' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Teleport>
</template>
