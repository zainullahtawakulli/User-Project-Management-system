<script setup>
import { computed, ref, watch } from 'vue'
import MultiSelect from 'primevue/multiselect'
import DatePicker from 'primevue/datepicker'
import FloatLabel from 'primevue/floatlabel'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { toast } from 'vue-sonner'

import { getTask, updateTask } from '@/services/taskService'
import { getProject } from '@/services/projectService'
import { useAuthStore } from '@/stores/auth'
import { useAppConfirm } from '@/composables/useAppConfirm'
import { formatDateInput, parseDateInput } from '@/utils/dateInput'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const confirmAction = useAppConfirm()
const canManageTask = computed(() => auth.can('tasks.create'))
const task = ref(null)
const project = ref(null)
const loading = ref(true)
const saving = ref(false)
const error = ref(null)

const form = ref({
  title: '',
  description: '',
  assignee_ids: [],
  status: 'todo',
  priority: 'medium',
  due_date: null,
})

const loadTask = async () => {
  loading.value = true
  error.value = null

  try {
    const response = await getTask(route.params.id)
    task.value = response.data.task
    const taskProject = task.value.project
    const projectResponse = await getProject(taskProject.id)
    project.value = projectResponse.data.project

    form.value = {
      title: task.value.title || '',
      description: task.value.description || '',
      assignee_ids: task.value.assignees?.length
        ? task.value.assignees.map((user) => user.id)
        : (task.value.assigned_to ? [task.value.assigned_to] : []),
      status: task.value.status || 'todo',
      priority: task.value.priority || 'medium',
      due_date: parseDateInput(task.value.due_date),
    }
  } catch (err) {
    console.error('Load task for editing error:', err)
    error.value = err.response?.data?.message || 'Failed to load task.'
  } finally {
    loading.value = false
  }
}

const submit = () => {
  confirmAction({
    header: canManageTask.value ? 'Save task changes?' : 'Update task status?',
    message: canManageTask.value
      ? 'Your task details and assignees will be updated.'
      : 'The task status will be updated.',
    acceptLabel: canManageTask.value ? 'Save changes' : 'Update status',
    accept: saveTask,
  })
}

const saveTask = async () => {
  saving.value = true
  error.value = null

  try {
    const payload = canManageTask.value
      ? {
          project_id: task.value.project.id,
          assignee_ids: (Array.isArray(form.value.assignee_ids) ? form.value.assignee_ids : []).map(Number),
          title: form.value.title,
          description: form.value.description || null,
          status: form.value.status,
          priority: form.value.priority,
          due_date: formatDateInput(form.value.due_date),
        }
      : { status: form.value.status }
    const response = await updateTask(task.value.id, payload)

    toast.success(response.data.message || 'Task updated successfully.')
    await router.push(`/tasks/${task.value.id}`)
  } catch (err) {
    console.error('Update task error:', err)
    const validationMessage = Object.values(err.response?.data?.errors || {}).flat()[0]
    error.value = validationMessage || err.response?.data?.message || 'Failed to update task.'
    toast.error(error.value)
  } finally {
    saving.value = false
  }
}

watch(() => route.params.id, loadTask, { immediate: true })
</script>

<template>
  <section class="mx-auto max-w-3xl">
    <div v-if="loading" class="py-12 text-center text-gray-500">Loading task...</div>

    <div v-else-if="error && (!task || !project)" class="rounded-lg bg-red-50 p-4 text-red-700">
      {{ error }}
    </div>

    <template v-else-if="task">
      <RouterLink
        :to="`/tasks/${task.id}`"
        class="mb-5 inline-flex text-sm font-medium text-blue-700 hover:text-blue-900"
      >
        ← Back to task
      </RouterLink>

      <div class="rounded-xl bg-white p-6 shadow-sm">
        <div class="mb-6">
          <p class="text-sm text-gray-500">{{ project?.name || task.project?.name }}</p>
          <h1 class="text-2xl font-bold text-gray-900">
            {{ canManageTask ? 'Edit Task' : 'Update Task Status' }}
          </h1>
        </div>

        <div v-if="error" class="mb-5 rounded-lg bg-red-50 p-3 text-sm text-red-700">
          {{ error }}
        </div>

        <form class="space-y-5" @submit.prevent="submit">
          <div v-if="canManageTask">
            <label for="task-title" class="mb-1 block text-sm font-medium text-gray-700"
              >Task title</label
            >
            <input
              id="task-title"
              v-model="form.title"
              required
              maxlength="255"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            />
          </div>

          <div v-if="canManageTask">
            <label for="task-description" class="mb-1 block text-sm font-medium text-gray-700"
              >Description</label
            >
            <textarea
              id="task-description"
              v-model="form.description"
              rows="4"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            ></textarea>
          </div>

          <div v-if="canManageTask">
            <label class="mb-1 block text-sm font-medium text-gray-700">Assign To</label>
            <MultiSelect
              v-model="form.assignee_ids"
              :options="project?.users || []"
              optionLabel="name"
              optionValue="id"
              placeholder="Select project members"
              display="chip"
              :maxSelectedLabels="3"
              filter
              showClear
              class="w-full"
              :disabled="!project?.users?.length"
            >
              <template #option="{ option }">
                <div class="flex flex-col">
                  <span class="font-medium">{{ option.name }}</span>
                  <span class="text-xs text-gray-500">{{ option.email }}</span>
                </div>
              </template>
            </MultiSelect>
            <p v-if="!project?.users?.length" class="mt-1 text-xs text-gray-500">
              No project members available.
            </p>
            <p class="mt-1 text-xs text-gray-500">
              Select everyone assigned to this task. Only project members can be assigned.
            </p>
          </div>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label for="task-status" class="mb-1 block text-sm font-medium text-gray-700"
                >Status</label
              >
              <select
                id="task-status"
                v-model="form.status"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 outline-none focus:border-blue-500"
              >
                <option value="todo">To Do</option>
                <option value="in_progress">In Progress</option>
                <option value="review">Review</option>
                <option value="completed">Completed</option>
              </select>
              <p v-if="!canManageTask" class="mt-2 text-xs leading-5 text-blue-700">
                This task is shared with its assignees. Changing its status updates it for everyone assigned.
              </p>
            </div>

            <div v-if="canManageTask">
              <label for="task-priority" class="mb-1 block text-sm font-medium text-gray-700"
                >Priority</label
              >
              <select
                id="task-priority"
                v-model="form.priority"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 outline-none focus:border-blue-500"
              >
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>
          </div>

          <div v-if="canManageTask">
            <FloatLabel variant="on" class="w-full">
              <DatePicker
                v-model="form.due_date"
                inputId="task-due-date"
                dateFormat="yy-mm-dd"
                :manualInput="false"
                showIcon
                iconDisplay="input"
                fluid
              />
              <label for="task-due-date">Due date</label>
            </FloatLabel>
          </div>

          <div class="flex justify-end gap-3 border-t pt-5">
            <RouterLink
              :to="`/tasks/${task.id}`"
              class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
              Cancel
            </RouterLink>
            <button
              type="submit"
              :disabled="saving"
              class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
              {{ saving ? 'Saving...' : (canManageTask ? 'Save changes' : 'Update status') }}
            </button>
          </div>
        </form>
      </div>
    </template>
  </section>
</template>
