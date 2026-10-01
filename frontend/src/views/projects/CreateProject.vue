<script setup>
import { ref, watch } from 'vue'
import { createProject } from '@/services/projectService'
import { getUsers } from '@/services/userapi/user'
import { toast } from 'vue-sonner'
import MultiSelect from 'primevue/multiselect'
import DatePicker from 'primevue/datepicker'
import FloatLabel from 'primevue/floatlabel'
import { formatDateInput } from '@/utils/dateInput'

const props = defineProps({
  show: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'created'])

const loading = ref(false)
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

const fetchUsers = async () => {
  loadingUsers.value = true

  try {
    const response = await getUsers()

    users.value = response.data.users
  } catch (err) {
    console.error('Fetch users error:', err)

    error.value = err.response?.data?.message || 'Failed to load users.'
  } finally {
    loadingUsers.value = false
  }
}

const submit = async () => {
  loading.value = true
  error.value = null

  try {
    const response = await createProject({
      name: form.value.name,
      description: form.value.description || null,
      status: form.value.status,
      start_date: formatDateInput(form.value.start_date),
      due_date: formatDateInput(form.value.due_date),
      user_ids: form.value.user_ids,
    })

    toast.success(response.data.message || 'Project created successfully.')
    form.value = { name: '', description: '', status: 'active', start_date: null, due_date: null, user_ids: [] }
    emit('created')
  } catch (err) {
    console.error('Create project error:', err)

    const validationMessage = Object.values(err.response?.data?.errors || {}).flat()[0]
    toast.error(validationMessage || err.response?.data?.message || 'Failed to create project.')
  } finally {
    loading.value = false
  }
}

fetchUsers()

watch(() => props.show, (visible) => {
  if (visible) error.value = null
})
</script>

<template>
  <Teleport to="body">
    <div
      class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/45 p-4 backdrop-blur-sm"
      v-if="show"
      @click.self="emit('close')"
    >
      <section
        role="dialog"
        aria-modal="true"
        aria-labelledby="create-project-title"
        class="max-h-[88vh] w-full max-w-2xl overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-2xl"
      >
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 sm:px-6">
          <div>
            <h2 id="create-project-title" class="text-xl font-semibold text-gray-900">
              Create project
            </h2>
            <p class="mt-1 text-sm text-gray-500">Set project details and choose its members.</p>
          </div>

          <button
            type="button"
            aria-label="Close create project dialog"
            class="inline-flex h-9 w-9 items-center justify-center rounded-md text-xl text-gray-500 hover:bg-gray-100"
            @click="emit('close')"
          >
            ×
          </button>
        </div>

        <form class="space-y-5 p-5 sm:p-6" @submit.prevent="submit">
          <div
            v-if="error"
            class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700"
          >
            {{ error }}
          </div>

          <div class="space-y-1.5">
            <label for="project-name" class="block text-sm font-medium text-gray-700">
              Project name
            </label>
            <input
              id="project-name"
              v-model="form.name"
              type="text"
              required
              placeholder="Enter project name"
              class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
            />
          </div>

          <div class="space-y-1.5">
            <label for="project-description" class="block text-sm font-medium text-gray-700">
              Description
            </label>
            <textarea
              id="project-description"
              v-model="form.description"
              rows="3"
              placeholder="Enter project description"
              class="w-full resize-y rounded-md border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
            ></textarea>
          </div>

          <div class="space-y-1.5">
            <label for="project-status" class="block text-sm font-medium text-gray-700">
              Status
            </label>
            <select
              id="project-status"
              v-model="form.status"
              class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
            >
              <option value="active">Active</option>
              <option value="completed">Completed</option>
              <option value="on_hold">On Hold</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="space-y-1.5">
              <FloatLabel variant="on" class="w-full">
                <DatePicker
                  v-model="form.start_date"
                  inputId="project-start-date"
                  dateFormat="yy-mm-dd"
                  :manualInput="false"
                  showIcon
                  iconDisplay="input"
                  fluid
                />
                <label for="project-start-date">Start date</label>
              </FloatLabel>
            </div>
            <div class="space-y-1.5">
              <FloatLabel variant="on" class="w-full">
                <DatePicker
                  v-model="form.due_date"
                  inputId="project-due-date"
                  dateFormat="yy-mm-dd"
                  :minDate="form.start_date || undefined"
                  :manualInput="false"
                  showIcon
                  iconDisplay="input"
                  fluid
                />
                <label for="project-due-date">Due date</label>
              </FloatLabel>
            </div>
          </div>

          <div class="space-y-1.5">
            <label for="project-members" class="block text-sm font-medium text-gray-700">
              Project members
            </label>

            <div class="card flex justify-content-center">
              <MultiSelect
                v-model="form.user_ids"
                :options="users"
                filter
                optionLabel="name"
                optionValue="id"
                placeholder="Select Members"
                :maxSelectedLabels="3"
                class="w-full md:w-20rem"
              />
            </div>

            <p v-if="loadingUsers" class="text-xs text-gray-500">Loading users...</p>

            <p v-else-if="users.length === 0" class="text-xs text-gray-500">
              No users available to add.
            </p>

            <p v-else class="text-xs text-gray-500">Select one or more project members.</p>
          </div>

          <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
            <button
              type="button"
              class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
              @click="emit('close')"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="loading"
            >
              {{ loading ? 'Creating...' : 'Create Project' }}
            </button>
          </div>
        </form>
      </section>
    </div>
  </Teleport>
</template>
