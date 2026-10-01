<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { ArrowLeft, Eye, Pencil, Trash2 } from '@lucide/vue'

import { getProject } from '@/services/projectService'
import CreateTask from '@/views/tasks/CreateTask.vue'

import { deleteTask } from '@/services/taskService'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/auth'
import { useAppConfirm } from '@/composables/useAppConfirm'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const confirmAction = useAppConfirm()
const canUpdateProjects = computed(() => auth.can('projects.update'))
const canCreateTasks = computed(() => auth.can('tasks.create'))
const canDeleteTasks = computed(() => auth.can('tasks.delete'))

const project = ref(null)

const loading = ref(true)
const error = ref(null)
const showCreateTask = ref(false)

const fetchProject = async () => {
  loading.value = true
  error.value = null

  try {
    const response = await getProject(route.params.id)

    project.value = response.data.project
  } catch (err) {
    console.error('Fetch project error:', err)

    error.value = err.response?.data?.message || 'Failed to load project.'
  } finally {
    loading.value = false
  }
}

const handleTaskCreated = async () => {
  showCreateTask.value = false

  await fetchProject()
}

const removeTask = async (task) => {
  confirmAction({
    header: 'Delete task?',
    message: `Delete “${task.title}”? This action cannot be undone.`,
    acceptLabel: 'Delete task',
    accept: async () => {
      try {
        const response = await deleteTask(task.id)
        toast.success(response.data.message || 'Task deleted successfully.')
        await fetchProject()
      } catch (err) {
        console.error('Delete task error:', err)
        toast.error(err.response?.data?.message || 'Failed to delete task.')
      }
    },
  })
}

const getStatusLabel = (status) => {
  const labels = {
    active: 'Active',
    completed: 'Completed',
    on_hold: 'On Hold',
    cancelled: 'Cancelled',
  }

  return labels[status] || status
}

const getTaskStatusLabel = (status) => {
  const labels = {
    todo: 'To Do',
    in_progress: 'In Progress',
    review: 'Review',
    completed: 'Completed',
  }

  return labels[status] || status
}

const getPriorityLabel = (priority) => {
  const labels = {
    low: 'Low',
    medium: 'Medium',
    high: 'High',
    urgent: 'Urgent',
  }

  return labels[priority] || priority
}

const formatDate = (date) => {
  if (!date) {
    return '-'
  }

  return new Date(date).toLocaleDateString()
}

const getStatusClass = (status) => {
  const classes = {
    todo: 'bg-gray-100 text-gray-700',
    in_progress: 'bg-blue-100 text-blue-700',
    review: 'bg-yellow-100 text-yellow-700',
    completed: 'bg-green-100 text-green-700',
  }

  return classes[status] || 'bg-gray-100 text-gray-700'
}

const getPriorityClass = (priority) => {
  const classes = {
    low: 'bg-gray-100 text-gray-700',
    medium: 'bg-blue-100 text-blue-700',
    high: 'bg-orange-100 text-orange-700',
    urgent: 'bg-red-100 text-red-700',
  }

  return classes[priority] || 'bg-gray-100 text-gray-700'
}

onMounted(() => {
  fetchProject()
})
</script>

<template>
  <div class="p-6">
    <!-- Loading -->

    <div v-if="loading" class="py-12 text-center text-gray-500">Loading project...</div>

    <!-- Error -->

    <div v-else-if="error && !project" class="rounded-lg bg-red-50 p-4 text-red-600">
      {{ error }}
    </div>

    <!-- Project -->

    <div v-else-if="project">
      <!-- Header -->

      <div class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
          <RouterLink
            to="/projects"
            class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-800"
          >
            <ArrowLeft :size="16" aria-hidden="true" />
            Projects
          </RouterLink>

          <h1 class="mt-2 text-3xl font-bold text-gray-900">
            {{ project.name }}
          </h1>

          <p class="mt-1 text-gray-500">
            {{ project.description || 'No description.' }}
          </p>
        </div>

        <RouterLink
          v-if="canUpdateProjects"
          :to="`/projects/${project.id}/edit`"
          class="rounded-lg bg-blue-600 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-blue-700"
        >
          Edit Project
        </RouterLink>
      </div>

      <!-- Project information -->

      <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-xl bg-white p-5 shadow-sm">
          <p class="text-sm text-gray-500">Status</p>

          <p class="mt-1 font-semibold text-gray-900">
            {{ getStatusLabel(project.status) }}
          </p>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm">
          <p class="text-sm text-gray-500">Start Date</p>

          <p class="mt-1 font-semibold text-gray-900">
            {{ formatDate(project.start_date) }}
          </p>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm">
          <p class="text-sm text-gray-500">Due Date</p>

          <p class="mt-1 font-semibold text-gray-900">
            {{ formatDate(project.due_date) }}
          </p>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm">
          <p class="text-sm text-gray-500">Tasks</p>

          <p class="mt-1 font-semibold text-gray-900">
            {{ project.tasks?.length || 0 }}
          </p>
        </div>
      </div>

      <!-- Members -->

      <section class="mb-8">
        <div class="mb-4">
          <h2 class="text-xl font-bold text-gray-900">Project Members</h2>

          <p class="text-sm text-gray-500">{{ project.users?.length || 0 }} members</p>
        </div>

        <div
          v-if="!project.users?.length"
          class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-gray-500"
        >
          No members assigned.
        </div>

        <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="user in project.users"
            :key="user.id"
            class="rounded-lg bg-white p-4 shadow-sm"
          >
            <p class="font-semibold text-gray-900">
              {{ user.name }}
            </p>

            <p class="text-sm text-gray-500">
              {{ user.email }}
            </p>
          </div>
        </div>
      </section>

      <!-- Tasks -->

      <section>
        <div class="mb-4 flex items-center justify-between">
          <div>
            <h2 class="text-xl font-bold text-gray-900">Project Tasks</h2>

            <p class="text-sm text-gray-500">{{ project.tasks?.length || 0 }} tasks</p>
          </div>
          <button
            v-if="canCreateTasks"
            type="button"
            @click="showCreateTask = true"
            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
          >
            + Add Task
          </button>
        </div>

        <!-- Error -->

        <div v-if="error" class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-600">
          {{ error }}
        </div>

        <!-- Empty -->

        <div
          v-if="!project.tasks?.length"
          class="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center"
        >
          <h3 class="font-semibold text-gray-900">No tasks yet</h3>

          <p class="mt-1 text-sm text-gray-500">Create the first task for this project.</p>

          <button
            v-if="canCreateTasks"
            type="button"
            @click="showCreateTask = true"
            class="mt-4 text-sm font-semibold text-blue-600 hover:text-blue-800"
          >
            Create Task
          </button>
        </div>

        <!-- Task table -->

        <div v-else class="overflow-hidden rounded-xl bg-white shadow-sm">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th
                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                  >
                    Task
                  </th>

                  <th
                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                  >
                    Assigned To
                  </th>

                  <th
                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                  >
                    Status
                  </th>

                  <th
                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                  >
                    Priority
                  </th>

                  <th
                    class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                  >
                    Due Date
                  </th>

                  <th
                    class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500"
                  >
                    Actions
                  </th>
                </tr>
              </thead>

              <tbody class="divide-y divide-gray-200">
                <tr v-for="task in project.tasks" :key="task.id" class="hover:bg-gray-50">
                  <!-- Task -->

                  <td class="px-6 py-4">
                    <RouterLink
                      :to="`/tasks/${task.id}`"
                      class="font-semibold text-blue-600 hover:text-blue-800"
                    >
                      {{ task.title }}
                    </RouterLink>

                    <p v-if="task.description" class="mt-1 max-w-md truncate text-sm text-gray-500">
                      {{ task.description }}
                    </p>
                  </td>

                  <!-- Assignee -->

                  <td class="px-6 py-4">
                    <div v-if="task.assignees?.length || task.assignee">
                      <div v-for="user in (task.assignees?.length ? task.assignees : [task.assignee])" :key="user.id" class="mb-1 last:mb-0">
                        <p class="font-medium text-gray-900">{{ user.name }}</p>
                        <p class="text-sm text-gray-500">{{ user.email }}</p>
                      </div>
                    </div>

                    <span v-else class="text-gray-400"> Unassigned </span>
                  </td>

                  <!-- Status -->

                  <td class="px-6 py-4">
                    <span
                      class="rounded-full px-2.5 py-1 text-xs font-semibold"
                      :class="getStatusClass(task.status)"
                    >
                      {{ getTaskStatusLabel(task.status) }}
                    </span>
                  </td>

                  <!-- Priority -->

                  <td class="px-6 py-4">
                    <span
                      class="rounded-full px-2.5 py-1 text-xs font-semibold"
                      :class="getPriorityClass(task.priority)"
                    >
                      {{ getPriorityLabel(task.priority) }}
                    </span>
                  </td>

                  <!-- Due date -->

                  <td class="px-6 py-4 text-sm text-gray-600">
                    {{ formatDate(task.due_date) }}
                  </td>

                  <!-- Actions -->

                  <td class="px-6 py-4">
                    <div class="flex justify-end gap-1">
                      <RouterLink
                        :to="`/tasks/${task.id}`"
                        :aria-label="`View ${task.title}`"
                        :title="`View ${task.title}`"
                        class="inline-flex h-8 w-8 items-center justify-center rounded text-gray-500 transition hover:bg-gray-100 hover:text-gray-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                      >
                        <Eye :size="16" aria-hidden="true" />
                      </RouterLink>

                      <RouterLink
                        :to="`/tasks/${task.id}/edit`"
                        :aria-label="`Edit ${task.title}`"
                        :title="`Edit ${task.title}`"
                        class="inline-flex h-8 w-8 items-center justify-center rounded text-blue-600 transition hover:bg-blue-50 hover:text-blue-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                      >
                        <Pencil :size="16" aria-hidden="true" />
                      </RouterLink>

                      <button
                        v-if="canDeleteTasks"
                        type="button"
                        :aria-label="`Delete ${task.title}`"
                        :title="`Delete ${task.title}`"
                        @click="removeTask(task)"
                        class="inline-flex h-8 w-8 items-center justify-center rounded text-red-600 transition hover:bg-red-50 hover:text-red-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                      >
                        <Trash2 :size="16" aria-hidden="true" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </div>

    <!-- Create Task Modal -->

    <CreateTask
      v-if="canCreateTasks && showCreateTask && project"
      :project="project"
      @close="showCreateTask = false"
      @created="handleTaskCreated"
    />
  </div>
</template>
