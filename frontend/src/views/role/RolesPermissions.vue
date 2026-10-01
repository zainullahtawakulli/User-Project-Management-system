<script setup>
import { computed, onMounted, ref } from 'vue'
import {
  createRole,
  deleteRole,
  getPermissions,
  getRoles,
  updateRole,
} from '@/services/roleService'

import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/auth'
import { useAppConfirm } from '@/composables/useAppConfirm'

const auth = useAuthStore()
const confirmAction = useAppConfirm()
const roles = ref([])
const permissions = ref([])

const selectedRole = ref(null)

const loading = ref(true)
const saving = ref(false)

const showCreate = ref(false)
const protectedRole = computed(() => selectedRole.value?.slug === 'super_admin')
const canEditRole = computed(() =>
  selectedRole.value ? auth.can('roles.update') : auth.can('roles.create'),
)

const form = ref({
  name: '',
  slug: '',
  description: '',
  permission_ids: [],
})

const loadData = async () => {
  loading.value = true

  try {
    const [rolesResponse, permissionsResponse] = await Promise.all([getRoles(), getPermissions()])

    roles.value = rolesResponse.data.roles
    permissions.value = permissionsResponse.data.permissions

    if (roles.value.length) {
      selectRole(roles.value[0])
    }
  } catch (error) {
    console.error(error)

    toast.error(error.response?.data?.message || 'Failed to load roles.')
  } finally {
    loading.value = false
  }
}

const selectRole = (role) => {
  selectedRole.value = role

  form.value = {
    name: role.name,
    slug: role.slug,
    description: role.description || '',
    permission_ids: role.permissions.map((permission) => permission.id),
  }

  showCreate.value = false
}

const groupedPermissions = computed(() => {
  return permissions.value.reduce((groups, permission) => {
    const group = permission.group || 'Other'

    if (!groups[group]) {
      groups[group] = []
    }

    groups[group].push(permission)

    return groups
  }, {})
})

const togglePermission = (permissionId) => {
  const index = form.value.permission_ids.indexOf(permissionId)

  if (index === -1) {
    form.value.permission_ids.push(permissionId)
  } else {
    form.value.permission_ids.splice(index, 1)
  }
}

const hasPermission = (permissionId) => {
  return form.value.permission_ids.includes(permissionId)
}

const saveRole = () => {
  if (!selectedRole.value) {
    persistRole()
    return
  }

  confirmAction({
    header: 'Save role changes?',
    message: `Update the permissions assigned to ${selectedRole.value.name}?`,
    acceptLabel: 'Save changes',
    accept: persistRole,
  })
}

const persistRole = async () => {
  saving.value = true

  try {
    let response

    if (selectedRole.value) {
      response = await updateRole(selectedRole.value.id, form.value)
    } else {
      response = await createRole(form.value)
    }

    toast.success(response.data.message || 'Role saved successfully.')

    await loadData()
    await auth.fetchUser()

    if (response.data.role) {
      const updatedRole = roles.value.find((role) => role.id === response.data.role.id)

      if (updatedRole) {
        selectRole(updatedRole)
      }
    }
  } catch (error) {
    console.error(error)

    const validationMessage = Object.values(error.response?.data?.errors || {}).flat()[0]

    toast.error(validationMessage || error.response?.data?.message || 'Failed to save role.')
  } finally {
    saving.value = false
  }
}

const createNewRole = () => {
  selectedRole.value = null

  form.value = {
    name: '',
    slug: '',
    description: '',
    permission_ids: [],
  }

  showCreate.value = true
}

const removeRole = async () => {
  if (!selectedRole.value) {
    return
  }

  const role = selectedRole.value
  confirmAction({
    header: 'Delete role?',
    message: `Delete “${role.name}”? Users assigned to this role must be reassigned first.`,
    acceptLabel: 'Delete role',
    accept: async () => {
      try {
        await deleteRole(role.id)
        toast.success('Role deleted successfully.')
        selectedRole.value = null
        await loadData()
      } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to delete role.')
      }
    },
  })
}

onMounted(loadData)
</script>

<template>
  <section class="p-4 sm:p-6">
    <div class="mx-auto max-w-7xl">
      <!-- Header -->
      <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Roles & Permissions</h1>

          <p class="mt-1 text-sm text-gray-500">
            Manage roles and control what each role can access.
          </p>
        </div>

        <button
          type="button"
          v-if="auth.can('roles.create')"
          class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
          @click="createNewRole"
        >
          + Add Role
        </button>
      </div>

      <div v-if="loading" class="rounded-xl border bg-white p-10 text-center text-gray-500">
        Loading roles...
      </div>

      <div v-else class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Roles -->
        <div class="rounded-xl border border-gray-200 bg-white">
          <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="font-semibold text-gray-900">Roles</h2>
          </div>

          <div class="p-3">
            <button
              v-for="role in roles"
              :key="role.id"
              type="button"
              class="mb-2 w-full rounded-lg border p-3 text-left transition"
              :class="
                selectedRole?.id === role.id
                  ? 'border-blue-500 bg-blue-50'
                  : 'border-gray-200 hover:bg-gray-50'
              "
              @click="selectRole(role)"
            >
              <div class="font-medium text-gray-900">
                {{ role.name }}
              </div>

              <div class="mt-1 text-xs text-gray-500">
                {{ role.slug }}
              </div>

              <div class="mt-2 text-xs text-gray-400">
                {{ role.users_count || 0 }}
                users
              </div>
            </button>
          </div>
        </div>

        <!-- Permissions -->
        <div class="rounded-xl border border-gray-200 bg-white lg:col-span-2">
          <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="font-semibold text-gray-900">
              {{ selectedRole ? `Permissions for ${selectedRole.name}` : 'Create Role' }}
            </h2>
          </div>

          <div class="space-y-6 p-5">
            <div
              v-if="protectedRole"
              class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800"
            >
              The Super Admin role is protected so it cannot accidentally lose access to the
              administration tools.
            </div>

            <!-- Role form -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700"> Role name </label>

                <input
                  v-model="form.name"
                  type="text"
                  placeholder="Administrator"
                  :disabled="protectedRole || !canEditRole"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                />
              </div>

              <div>
                <label class="mb-1 block text-sm font-medium text-gray-700"> Slug </label>

                <input
                  v-model="form.slug"
                  type="text"
                  placeholder="admin"
                  :disabled="protectedRole || !canEditRole"
                  class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                />
              </div>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700"> Description </label>

              <textarea
                v-model="form.description"
                rows="2"
                :disabled="protectedRole || !canEditRole"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
              ></textarea>
            </div>

            <!-- Permission groups -->
            <div v-for="(groupPermissions, group) in groupedPermissions" :key="group">
              <h3 class="mb-3 text-sm font-semibold text-gray-900">
                {{ group }}
              </h3>

              <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <label
                  v-for="permission in groupPermissions"
                  :key="permission.id"
                  class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 p-3 hover:bg-gray-50"
                >
                  <input
                    type="checkbox"
                    :checked="hasPermission(permission.id)"
                    :disabled="protectedRole || !canEditRole"
                    class="h-4 w-4"
                    @change="togglePermission(permission.id)"
                  />

                  <div>
                    <div class="text-sm font-medium text-gray-900">
                      {{ permission.name }}
                    </div>

                    <div class="text-xs text-gray-500">
                      {{ permission.slug }}
                    </div>
                  </div>
                </label>
              </div>
            </div>

            <!-- Buttons -->
            <div class="flex justify-between border-t border-gray-100 pt-5">
              <button
                v-if="selectedRole && !protectedRole && auth.can('roles.delete')"
                type="button"
                class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
                @click="removeRole"
              >
                Delete Role
              </button>

              <div v-else></div>

              <button
                v-if="!protectedRole && canEditRole"
                type="button"
                :disabled="saving"
                class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
                @click="saveRole"
              >
                {{ saving ? 'Saving...' : selectedRole ? 'Save Permissions' : 'Create Role' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
