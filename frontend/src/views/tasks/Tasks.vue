<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { ChevronLeft, ChevronRight, Eye, Pencil, Search, Trash2 } from '@lucide/vue'
import { toast } from 'vue-sonner'
import { deleteTask, getTasks } from '@/services/taskService'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const canDeleteTasks = computed(() => auth.can('tasks.delete'))
const isScopedUser = computed(() => auth.role === 'user')
const tasks = ref([])
const search = ref('')
const loading = ref(false)
const error = ref(null)
const currentPage = ref(1)
const lastPage = ref(1)
const totalTasks = ref(0)
const summary = ref({
  total: 0,
  todo: 0,
  in_progress: 0,
  review: 0,
  completed: 0,
})

const summaryCards = computed(() => [
  { label: 'All tasks', value: summary.value.total, accent: 'border-l-blue-500' },
  { label: 'To do', value: summary.value.todo, accent: 'border-l-gray-400' },
  { label: 'In progress', value: summary.value.in_progress, accent: 'border-l-sky-500' },
  { label: 'In review', value: summary.value.review, accent: 'border-l-amber-400' },
  { label: 'Completed', value: summary.value.completed, accent: 'border-l-emerald-500' },
])

let searchTimeout

const pageNumbers = computed(() => {
  const firstPage = Math.max(1, Math.min(currentPage.value - 2, lastPage.value - 4))
  const finalPage = Math.min(lastPage.value, firstPage + 4)

  return Array.from({ length: finalPage - firstPage + 1 }, (_, index) => firstPage + index)
})

const fetchTasks = async (page = 1) => {
  loading.value = true
  error.value = null

  try {
    const response = await getTasks(search.value.trim(), page)
    tasks.value = response.data.data
    currentPage.value = response.data.current_page
    lastPage.value = response.data.last_page
    totalTasks.value = response.data.total
    summary.value = response.data.summary
  } catch (err) {
    console.error('Fetch tasks error:', err)
    error.value = err.response?.data?.message || 'Failed to load tasks.'
  } finally {
    loading.value = false
  }
}

watch(search, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => fetchTasks(1), 300)
})

const goToPage = (page) => {
  if (page >= 1 && page <= lastPage.value) {
    fetchTasks(page)
  }
}

const removeTask = async (task) => {
  if (!window.confirm(`Delete "${task.title}"?`)) {
    return
  }

  try {
    await deleteTask(task.id)
    toast.success('Task deleted.')
    await fetchTasks(
      tasks.value.length === 1 && currentPage.value > 1 ? currentPage.value - 1 : currentPage.value,
    )
  } catch (err) {
    console.error('Delete task error:', err)
    toast.error(err.response?.data?.message || 'Failed to delete task.')
  }
}

const statusLabel = (status) =>
  ({
    todo: 'To Do',
    in_progress: 'In Progress',
    review: 'Review',
    completed: 'Completed',
  })[status] || status

const formatDate = (date) => (date ? date.slice(0, 10) : 'No due date')

onMounted(() => {
  fetchTasks()
})
</script>

<template>
  <section class="space-y-5">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Tasks</h1>
        <p class="mt-1 text-sm text-gray-500">
          {{ isScopedUser ? 'Tasks in your projects and assigned to you.' : 'Tasks across all projects.' }}
        </p>
      </div>

      <label class="relative block w-full sm:w-80">
        <span class="sr-only">Search tasks</span>
        <Search
          :size="16"
          aria-hidden="true"
          class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
        />
        <input
          v-model="search"
          type="search"
          placeholder="Search task title or description"
          class="w-full rounded-md border border-gray-300 py-2 pl-9 pr-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
        />
      </label>
    </header>

    <section
      aria-label="Task status summary"
      class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5"
    >
      <article
        v-for="card in summaryCards"
        :key="card.label"
        class="min-h-24 rounded-lg border border-l-4 border-gray-200 bg-white px-4 py-3 shadow-sm"
        :class="card.accent"
      >
        <p class="text-sm font-medium text-gray-500">{{ card.label }}</p>
        <p v-if="loading" class="mt-2 h-7 w-12 animate-pulse rounded bg-gray-100"></p>
        <p v-else class="mt-1 text-2xl font-semibold text-gray-900">{{ card.value }}</p>
      </article>
    </section>

    <div v-if="error" class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700">
      <p>{{ error }}</p>
      <button type="button" class="mt-2 font-medium underline" @click="fetchTasks(currentPage)">
        Retry
      </button>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
      <div v-if="loading" class="p-10 text-center text-sm text-gray-500">Loading tasks...</div>

      <div v-else-if="tasks.length === 0" class="p-10 text-center">
        <h2 class="font-medium text-gray-800">No tasks found</h2>
        <p class="mt-1 text-sm text-gray-500">
          {{ search ? 'Try another search.' : (isScopedUser ? 'No related tasks found.' : 'Create a task from a project page.') }}
        </p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left">
          <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-500">
            <tr>
              <th class="px-5 py-3">Task</th>
              <th class="px-5 py-3">Project</th>
              <th class="px-5 py-3">Assignee</th>
              <th class="px-5 py-3">Status</th>
              <th class="px-5 py-3">Priority</th>
              <th class="px-5 py-3">Due date</th>
              <th class="px-5 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="task in tasks" :key="task.id" class="hover:bg-gray-50">
              <td class="max-w-xs px-5 py-4">
                <RouterLink
                  :to="`/tasks/${task.id}`"
                  class="block truncate text-sm font-semibold text-gray-900 hover:text-blue-700"
                >
                  {{ task.title }}
                </RouterLink>
                <p class="mt-1 truncate text-xs text-gray-500">
                  {{ task.description || 'No description' }}
                </p>
              </td>
              <td class="px-5 py-4 text-sm text-gray-700">
                <RouterLink :to="`/projects/${task.project?.id}`" class="hover:text-blue-700">
                  {{ task.project?.name || 'Unknown project' }}
                </RouterLink>
              </td>
              <td class="px-5 py-4 text-sm text-gray-600">
                {{ task.assignees?.length ? task.assignees.map((user) => user.name).join(', ') : (task.assignee?.name || 'Unassigned') }}
              </td>
              <td class="px-5 py-4">
                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                  {{ statusLabel(task.status) }}
                </span>
              </td>
              <td class="px-5 py-4 text-sm capitalize text-gray-700">{{ task.priority }}</td>
              <td class="px-5 py-4 text-sm text-gray-600">{{ formatDate(task.due_date) }}</td>
              <td class="px-5 py-4">
                <div class="flex justify-end gap-1">
                  <RouterLink
                    :to="`/tasks/${task.id}`"
                    :aria-label="`Show ${task.title}`"
                    :title="`Show ${task.title}`"
                    class="inline-flex h-8 w-8 items-center justify-center rounded text-gray-500 hover:bg-gray-100 hover:text-gray-800"
                  >
                    <Eye :size="16" aria-hidden="true" />
                  </RouterLink>
                  <RouterLink
                    :to="`/tasks/${task.id}/edit`"
                    :aria-label="`Edit ${task.title}`"
                    :title="`Edit ${task.title}`"
                    class="inline-flex h-8 w-8 items-center justify-center rounded text-blue-600 hover:bg-blue-50 hover:text-blue-800"
                  >
                    <Pencil :size="16" aria-hidden="true" />
                  </RouterLink>
                  <button
                    v-if="canDeleteTasks"
                    type="button"
                    :aria-label="`Delete ${task.title}`"
                    :title="`Delete ${task.title}`"
                    class="inline-flex h-8 w-8 items-center justify-center rounded text-red-600 hover:bg-red-50 hover:text-red-800"
                    @click="removeTask(task)"
                  >
                    <Trash2 :size="16" aria-hidden="true" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <footer
        v-if="!loading && lastPage > 1"
        class="flex items-center justify-between border-t border-gray-100 px-4 py-3"
      >
        <p class="text-sm text-gray-500">{{ totalTasks }} tasks</p>
        <nav class="flex items-center gap-1" aria-label="Task pagination">
          <button
            type="button"
            aria-label="Previous page"
            :disabled="currentPage === 1"
            class="inline-flex h-8 w-8 items-center justify-center rounded border border-gray-200 text-gray-600 disabled:opacity-40"
            @click="goToPage(currentPage - 1)"
          >
            <ChevronLeft :size="16" aria-hidden="true" />
          </button>
          <button
            v-for="page in pageNumbers"
            :key="page"
            type="button"
            :aria-current="currentPage === page ? 'page' : undefined"
            :aria-label="`Page ${page}`"
            class="h-8 min-w-8 rounded border px-2 text-sm"
            :class="
              currentPage === page
                ? 'border-blue-600 bg-blue-600 text-white'
                : 'border-gray-200 text-gray-600'
            "
            @click="goToPage(page)"
          >
            {{ page }}
          </button>
          <button
            type="button"
            aria-label="Next page"
            :disabled="currentPage === lastPage"
            class="inline-flex h-8 w-8 items-center justify-center rounded border border-gray-200 text-gray-600 disabled:opacity-40"
            @click="goToPage(currentPage + 1)"
          >
            <ChevronRight :size="16" aria-hidden="true" />
          </button>
        </nav>
      </footer>
    </div>
  </section>
</template>
