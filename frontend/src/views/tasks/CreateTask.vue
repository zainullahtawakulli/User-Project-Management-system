<script setup>
import { ref } from 'vue'
import MultiSelect from 'primevue/multiselect'
import { createTask } from '@/services/taskService'
import { toast } from 'vue-sonner'

const props = defineProps({
  project: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['close', 'created'])

const loading = ref(false)
const error = ref(null)

const form = ref({
  project_id: props.project.id,
  assignee_ids: [],
  title: '',
  description: '',
  status: 'todo',
  priority: 'medium',
  due_date: '',
})

const submit = async () => {
  loading.value = true
  error.value = null

  try {
    const response = await createTask({
      project_id: props.project.id,
      assignee_ids: (Array.isArray(form.value.assignee_ids) ? form.value.assignee_ids : []).map(Number),
      title: form.value.title,
      description: form.value.description || null,
      status: form.value.status,
      priority: form.value.priority,
      due_date: form.value.due_date || null,
    })

    toast.success(response.data.message || 'Task created successfully.')
    emit('created')
  } catch (err) {
    console.error('Create task error:', err)

    const validationMessage = Object.values(err.response?.data?.errors || {}).flat()[0]
    toast.error(validationMessage || err.response?.data?.message || 'Failed to create task.')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div class="w-full max-w-2xl rounded-xl bg-white shadow-xl">
        <!-- Header -->

        <div class="flex items-center justify-between border-b px-6 py-4">
          <div>
            <h2 class="text-xl font-bold text-gray-900">Create Task</h2>

            <p class="text-sm text-gray-500">
              {{ project.name }}
            </p>
          </div>

          <button
            type="button"
            @click="emit('close')"
            class="text-2xl text-gray-400 hover:text-gray-600"
          >
            ×
          </button>
        </div>

        <!-- Body -->

        <form @submit.prevent="submit" class="space-y-5 p-6">
          <div v-if="error" class="rounded-lg bg-red-50 p-3 text-sm text-red-600">
            {{ error }}
          </div>

          <!-- Title -->

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700"> Task Title </label>

            <input
              v-model="form.title"
              type="text"
              required
              placeholder="Enter task title"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 outline-none focus:border-blue-500"
            />
          </div>

          <!-- Description -->

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700"> Description </label>

            <textarea
              v-model="form.description"
              rows="4"
              placeholder="Enter task description"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 outline-none focus:border-blue-500"
            ></textarea>
          </div>

          <!-- Assignees -->

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Assign To</label>
            <MultiSelect
              v-model="form.assignee_ids"
              :options="project.users || []"
              optionLabel="name"
              optionValue="id"
              placeholder="Select project members"
              display="chip"
              :maxSelectedLabels="3"
              filter
              showClear
              class="w-full"
              :disabled="!project.users?.length"
            >
              <template #option="{ option }">
                <div class="flex flex-col">
                  <span class="font-medium">{{ option.name }}</span>
                  <span class="text-xs text-gray-500">{{ option.email }}</span>
                </div>
              </template>
            </MultiSelect>
            <p v-if="!project.users?.length" class="mt-1 text-xs text-gray-500">No project members available.</p>
            <p class="mt-1 text-xs text-gray-500">Select everyone assigned to this task. Only project members can be assigned.</p>
          </div>

          <!-- Status / Priority -->

          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700"> Status </label>

              <select
                v-model="form.status"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 outline-none focus:border-blue-500"
              >
                <option value="todo">To Do</option>

                <option value="in_progress">In Progress</option>

                <option value="review">Review</option>

                <option value="completed">Completed</option>
              </select>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700"> Priority </label>

              <select
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

          <!-- Due date -->

          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700"> Due Date </label>

            <input
              v-model="form.due_date"
              type="date"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 outline-none focus:border-blue-500"
            />
          </div>

          <!-- Buttons -->

          <div class="flex justify-end gap-3 border-t pt-5">
            <button
              type="button"
              @click="emit('close')"
              class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
              Cancel
            </button>

            <button
              type="submit"
              :disabled="loading"
              class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50"
            >
              {{ loading ? 'Creating...' : 'Create Task' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>
