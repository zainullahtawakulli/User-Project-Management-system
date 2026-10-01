<script setup>
import { onMounted, ref } from 'vue'
import { ArrowLeft } from '@lucide/vue'
import { useRoute, useRouter } from 'vue-router'
import MultiSelect from 'primevue/multiselect'
import DatePicker from 'primevue/datepicker'
import FloatLabel from 'primevue/floatlabel'

import { getProject, updateProject } from '@/services/projectService'
import { getUserOptions } from '@/services/userapi/user'
import { toast } from 'vue-sonner'
import { formatDateInput, parseDateInput } from '@/utils/dateInput'
import { useAppConfirm } from '@/composables/useAppConfirm'

const route = useRoute()
const router = useRouter()
const confirmAction = useAppConfirm()

const loading = ref(true)
const saving = ref(false)
const loadingUsers = ref(false)

const error = ref(null)

const users = ref([])

const form = ref({
  name: '',
  description: '',
  status: 'active',
  start_date: null,
  due_date: null,
  user_ids: [],
})

const loadUsers = async () => {
  loadingUsers.value = true

  try {
    const response = await getUserOptions()

    users.value = response.data
  } catch (err) {
    console.error('Fetch users error:', err)

    error.value = err.response?.data?.message || 'Failed to load users.'
  } finally {
    loadingUsers.value = false
  }
}

const loadProject = async () => {
  loading.value = true
  error.value = null

  try {
    const response = await getProject(route.params.id)

    const project = response.data.project

    form.value = {
      name: project.name || '',
      description: project.description || '',
      status: project.status || 'active',
      start_date: parseDateInput(project.start_date),
      due_date: parseDateInput(project.due_date),
      user_ids: project.users ? project.users.map((user) => user.id) : [],
    }
  } catch (err) {
    console.error('Fetch project error:', err)

    error.value = err.response?.data?.message || 'Failed to load project.'
  } finally {
    loading.value = false
  }
}

const submit = () => {
  confirmAction({
    header: 'Save project changes?',
    message: 'Your project details and member assignments will be updated.',
    acceptLabel: 'Save changes',
    accept: saveProject,
  })
}

const saveProject = async () => {
  saving.value = true
  error.value = null

  try {
    const response = await updateProject(route.params.id, {
      name: form.value.name,
      description: form.value.description || null,
      status: form.value.status,
      start_date: formatDateInput(form.value.start_date),
      due_date: formatDateInput(form.value.due_date),
      user_ids: form.value.user_ids,
    })

    toast.success(response.data.message || 'Project updated successfully.')

    await router.push(`/projects/${route.params.id}`)
  } catch (err) {
    console.error('Update project error:', err)

    const validationMessage = Object.values(err.response?.data?.errors || {}).flat()[0]

    toast.error(validationMessage || err.response?.data?.message || 'Failed to update project.')
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadProject(), loadUsers()])
})
</script>

<template>
  <section class="mx-auto max-w-4xl space-y-5 p-4 sm:p-6">
    <div
      v-if="loading"
      class="rounded-lg border border-gray-200 bg-white p-10 text-center text-sm text-gray-500"
    >
      Loading project...
    </div>

    <div v-else class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
      <header
        class="flex flex-col gap-4 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"
      >
        <div>
          <h1 class="text-xl font-semibold text-gray-900">Edit project</h1>

          <p class="mt-1 text-sm text-gray-500">Update project details and members.</p>
        </div>

        <button
          type="button"
          class="inline-flex self-start items-center gap-2 rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 sm:self-auto"
          @click="router.push(`/projects/${route.params.id}`)"
        >
          <ArrowLeft :size="16" aria-hidden="true" />

          Back to project
        </button>
      </header>

      <div
        v-if="error"
        class="mx-5 mt-5 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700 sm:mx-6"
      >
        {{ error }}
      </div>

      <form class="space-y-5 p-5 sm:p-6" @submit.prevent="submit">
        <!-- Project name -->
        <div class="space-y-1.5">
          <label for="edit-project-name" class="block text-sm font-medium text-gray-700">
            Project name
          </label>

          <input
            id="edit-project-name"
            v-model="form.name"
            type="text"
            required
            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
          />
        </div>

        <!-- Description -->
        <div class="space-y-1.5">
          <label for="edit-project-description" class="block text-sm font-medium text-gray-700">
            Description
          </label>

          <textarea
            id="edit-project-description"
            v-model="form.description"
            rows="4"
            class="w-full resize-y rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
          ></textarea>
        </div>

        <!-- Status -->
        <div class="space-y-1.5">
          <label for="edit-project-status" class="block text-sm font-medium text-gray-700">
            Status
          </label>

          <select
            id="edit-project-status"
            v-model="form.status"
            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
          >
            <option value="active">Active</option>

            <option value="completed">Completed</option>

            <option value="on_hold">On Hold</option>

            <option value="cancelled">Cancelled</option>
          </select>
        </div>

        <!-- Dates -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div class="space-y-1.5">
            <FloatLabel variant="on" class="w-full">
              <DatePicker
                v-model="form.start_date"
                inputId="edit-project-start"
                dateFormat="yy-mm-dd"
                :manualInput="false"
                showIcon
                iconDisplay="input"
                fluid
              />
              <label for="edit-project-start">Start date</label>
            </FloatLabel>
          </div>

          <div class="space-y-1.5">
            <FloatLabel variant="on" class="w-full">
              <DatePicker
                v-model="form.due_date"
                inputId="edit-project-due"
                dateFormat="yy-mm-dd"
                :minDate="form.start_date || undefined"
                :manualInput="false"
                showIcon
                iconDisplay="input"
                fluid
              />
              <label for="edit-project-due">Due date</label>
            </FloatLabel>
          </div>
        </div>

        <!-- Project members -->
        <div class="space-y-1.5">
          <label for="edit-project-members" class="block text-sm font-medium text-gray-700">
            Project members
          </label>

          <MultiSelect
            id="edit-project-members"
            v-model="form.user_ids"
            :options="users"
            optionLabel="name"
            optionValue="id"
            filter
            filterPlaceholder="Search users..."
            placeholder="Select project members"
            :maxSelectedLabels="3"
            :disabled="loadingUsers"
            class="w-full"
          >
            <template #option="{ option }">
              <div class="flex flex-col">
                <span class="font-medium text-gray-900">
                  {{ option.name }}
                </span>

                <span class="text-xs text-gray-500">
                  {{ option.email }}
                </span>
              </div>
            </template>

            <template #value="{ value }">
              <div v-if="value?.length" class="flex flex-wrap gap-1">
                <span
                  v-for="userId in value.slice(0, 3)"
                  :key="userId"
                  class="rounded bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700"
                >
                  {{ users.find((user) => user.id === userId)?.name }}
                </span>

                <span v-if="value.length > 3" class="px-1 py-1 text-xs text-gray-500">
                  +{{ value.length - 3 }} more
                </span>
              </div>

              <span v-else class="text-gray-400"> Select project members </span>
            </template>
          </MultiSelect>

          <p v-if="loadingUsers" class="text-xs text-gray-500">Loading users...</p>

          <p v-else-if="users.length === 0" class="text-xs text-gray-500">
            No users available to add.
          </p>

          <p v-else class="text-xs text-gray-500">Select one or more project members.</p>
        </div>

        <!-- Buttons -->
        <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
          <button
            type="button"
            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            @click="router.push(`/projects/${route.params.id}`)"
          >
            Cancel
          </button>

          <button
            type="submit"
            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="saving"
          >
            {{ saving ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </form>
    </div>
  </section>
</template>
