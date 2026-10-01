<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import {
  Ban,
  Eye,
  FolderPlus,
  Pencil,
  ListPlus,
  Play,
  Search,
  Trash2,
} from '@lucide/vue'
import Paginator from 'primevue/paginator'
import { getProject, getProjects, deleteProject, updateProject } from '@/services/projectService'
import CreateProject from './CreateProject.vue'
import CreateTask from '@/views/tasks/CreateTask.vue'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/auth'
import { useAppConfirm } from '@/composables/useAppConfirm'

const auth = useAuthStore()
const confirmAction = useAppConfirm()
const canCreateProjects = computed(() => auth.can('projects.create'))
const canUpdateProjects = computed(() => auth.can('projects.update'))
const canDeleteProjects = computed(() => auth.can('projects.delete'))
const canCreateTasks = computed(() => auth.can('tasks.create'))
const projects = ref([])

const loading = ref(false)
const error = ref(null)

const search = ref('')

const currentPage = ref(1)
const totalProjects = ref(0)
const first = ref(0)
const rowsPerPage = ref(10)

const showCreateModal = ref(false)
const showCreateTask = ref(false)
const selectedProject = ref(null)
const loadingTaskProjectId = ref(null)
const updatingProjectId = ref(null)

let searchTimeout = null

const fetchProjects = async (page = 1, perPage = rowsPerPage.value) => {
  loading.value = true
  error.value = null

  try {
    const response = await getProjects(search.value, page, perPage)

    projects.value = response.data.data
    currentPage.value = response.data.current_page
    totalProjects.value = response.data.total
    rowsPerPage.value = perPage
    first.value = (response.data.current_page - 1) * perPage
  } catch (err) {
    console.error('Fetch projects error:', err)

    error.value = err.response?.data?.message || 'Failed to load projects.'
  } finally {
    loading.value = false
  }
}

const searchProjects = () => {
  clearTimeout(searchTimeout)

  searchTimeout = setTimeout(() => {
    first.value = 0
    fetchProjects(1, rowsPerPage.value)
  }, 300)
}

watch(search, searchProjects)

const handlePage = (event) => {
  rowsPerPage.value = event.rows
  fetchProjects(event.page + 1, event.rows)
}

const handleProjectCreated = () => {
  showCreateModal.value = false
  fetchProjects(1)
}

const openCreateTask = async (project) => {
  loadingTaskProjectId.value = project.id

  try {
    const response = await getProject(project.id)
    selectedProject.value = response.data.project
    showCreateTask.value = true
  } catch (err) {
    console.error('Load project for task creation error:', err)
    toast.error(err.response?.data?.message || 'Could not load project members.')
  } finally {
    loadingTaskProjectId.value = null
  }
}

const handleTaskCreated = async () => {
  showCreateTask.value = false
  selectedProject.value = null
  await fetchProjects(currentPage.value)
}

const changeProjectStatus = async (project, status) => {
  updatingProjectId.value = project.id

  try {
    const response = await getProject(project.id)
    const currentProject = response.data.project

    await updateProject(project.id, {
      name: currentProject.name,
      description: currentProject.description || null,
      status,
      start_date: currentProject.start_date?.slice(0, 10) || null,
      due_date: currentProject.due_date?.slice(0, 10) || null,
      user_ids: currentProject.users?.map((user) => user.id) || [],
    })

    toast.success(status === 'active' ? 'Project started.' : 'Project rejected.')
    await fetchProjects(currentPage.value)
  } catch (err) {
    console.error('Update project status error:', err)
    toast.error(err.response?.data?.message || 'Could not update project status.')
  } finally {
    updatingProjectId.value = null
  }
}

const removeProject = async (project) => {
  confirmAction({
    header: 'Delete project?',
    message: `Delete “${project.name}”? This action cannot be undone.`,
    acceptLabel: 'Delete project',
    accept: async () => {
      try {
        const response = await deleteProject(project.id)
        toast.success(response.data.message || 'Project deleted successfully.')

        await fetchProjects(currentPage.value)
      } catch (err) {
        console.error('Delete project error:', err)
        toast.error(err.response?.data?.message || 'Failed to delete project.')
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

const getStatusClasses = (status) => {
  const classes = {
    active: 'bg-emerald-50 text-emerald-700',
    completed: 'bg-blue-50 text-blue-700',
    on_hold: 'bg-amber-50 text-amber-700',
    cancelled: 'bg-red-50 text-red-700',
  }

  return classes[status] || 'bg-gray-100 text-gray-700'
}

const formatDate = (date) => {
  if (!date) {
    return '-'
  }

  return new Date(date).toLocaleDateString()
}

onMounted(() => {
  fetchProjects()
})
</script>

<template>
  <section class="mx-auto max-w-[100rem] space-y-5 p-4 sm:p-6">
    <!-- Header -->
    <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Projects</h1>
        <p class="mt-1 text-sm text-gray-500">
          {{ canCreateProjects ? 'Manage projects, members, and tasks.' : 'Projects assigned to you.' }}
        </p>
      </div>

      <button
        v-if="canCreateProjects"
        type="button"
        class="inline-flex items-center justify-center gap-2 rounded-md bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
        @click="showCreateModal = true"
      >
        <FolderPlus :size="17" aria-hidden="true" />
        <span>Add project</span>
      </button>
    </header>

    <!-- Search -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <label class="relative block w-full sm:max-w-md">
        <span class="sr-only">Search projects</span>
        <Search
          :size="16"
          aria-hidden="true"
          class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
        />
        <input
          v-model="search"
          type="search"
          placeholder="Search projects..."
          class="w-full rounded-md border border-gray-300 py-2 pl-9 pr-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
        />
      </label>

      <span class="text-sm font-medium text-gray-500">{{ totalProjects }} projects</span>
    </div>

    <!-- Error -->
    <div v-if="error" class="rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-700">
      {{ error }}
    </div>

    <!-- Loading -->
    <div
      v-if="loading"
      class="rounded-lg border border-gray-200 bg-white p-10 text-center text-sm text-gray-500"
    >
      Loading projects...
    </div>

    <!-- Empty -->
    <div
      v-else-if="projects.length === 0"
      class="rounded-lg border border-gray-200 bg-white p-10 text-center"
    >
      <h2 class="font-semibold text-gray-900">No projects found</h2>
      <p class="mt-1 text-sm text-gray-500">
        {{ canCreateProjects ? 'Create a project or adjust your search.' : 'Try adjusting your search.' }}
      </p>
    </div>

    <!-- Projects Table -->
    <div v-else class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
      <div class="hidden overflow-x-auto 2xl:block">
        <table class="w-full min-w-[70rem] border-collapse text-left">
          <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-500">
            <tr>
              <th class="px-4 py-3">ID</th>
              <th class="px-4 py-3">Project</th>
              <th class="px-4 py-3">Status</th>
              <th class="px-4 py-3">Members</th>
              <th class="px-4 py-3">Tasks</th>
              <th class="px-4 py-3">Start date</th>
              <th class="px-4 py-3">Due date</th>
              <th class="px-4 py-3">Created by</th>
              <th class="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-100 text-sm">
            <tr v-for="project in projects" :key="project.id" class="hover:bg-gray-50">
              <td class="px-4 py-4 text-gray-500">#{{ project.id }}</td>

              <td class="max-w-xs px-4 py-4">
                <RouterLink
                  :to="`/projects/${project.id}`"
                  class="block truncate font-semibold text-gray-900 hover:text-blue-700"
                >
                  {{ project.name }}
                </RouterLink>

                <p v-if="project.description" class="mt-1 truncate text-xs text-gray-500">
                  {{ project.description }}
                </p>
              </td>

              <td class="px-4 py-4">
                <span
                  class="rounded-full px-2.5 py-1 text-xs font-medium"
                  :class="getStatusClasses(project.status)"
                >
                  {{ getStatusLabel(project.status) }}
                </span>
              </td>

              <td class="px-4 py-4 text-gray-700">{{ project.users_count }}</td>

              <td class="px-4 py-4 text-gray-700">{{ project.tasks_count }}</td>

              <td class="px-4 py-4 text-gray-600">{{ formatDate(project.start_date) }}</td>

              <td class="px-4 py-4 text-gray-600">{{ formatDate(project.due_date) }}</td>

              <td class="px-4 py-4 text-gray-600">{{ project.creator?.name || '-' }}</td>

              <td class="px-4 py-4">
                <div class="flex justify-end gap-1">
                  <button
                    v-if="canCreateTasks"
                    type="button"
                    :disabled="loadingTaskProjectId === project.id"
                    :aria-label="`Add task to ${project.name}`"
                    :title="`Add task to ${project.name}`"
                    class="inline-flex h-8 items-center gap-1 rounded px-2 text-xs font-medium text-emerald-700 hover:bg-emerald-50 disabled:cursor-wait disabled:opacity-50"
                    @click="openCreateTask(project)"
                  >
                    <ListPlus :size="15" aria-hidden="true" />
                    <span>{{ loadingTaskProjectId === project.id ? 'Loading' : 'Task' }}</span>
                  </button>
                  <button
                    v-if="canUpdateProjects && project.status === 'on_hold'"
                    type="button"
                    :disabled="updatingProjectId === project.id"
                    :aria-label="`Start ${project.name}`"
                    :title="`Start ${project.name}`"
                    class="inline-flex h-8 w-8 items-center justify-center rounded text-emerald-700 hover:bg-emerald-50 disabled:opacity-50"
                    @click="changeProjectStatus(project, 'active')"
                  >
                    <Play :size="16" aria-hidden="true" />
                  </button>
                  <button
                    v-if="canUpdateProjects && ['active', 'on_hold'].includes(project.status)"
                    type="button"
                    :disabled="updatingProjectId === project.id"
                    :aria-label="`Reject ${project.name}`"
                    :title="`Reject ${project.name}`"
                    class="inline-flex h-8 w-8 items-center justify-center rounded text-amber-700 hover:bg-amber-50 disabled:opacity-50"
                    @click="changeProjectStatus(project, 'cancelled')"
                  >
                    <Ban :size="16" aria-hidden="true" />
                  </button>
                  <RouterLink
                    :to="`/projects/${project.id}`"
                    :aria-label="`View ${project.name}`"
                    :title="`View ${project.name}`"
                    class="inline-flex h-8 w-8 items-center justify-center rounded text-gray-500 hover:bg-gray-100 hover:text-gray-800"
                  >
                    <Eye :size="16" aria-hidden="true" />
                  </RouterLink>

                  <RouterLink
                    v-if="canUpdateProjects"
                    :to="`/projects/${project.id}/edit`"
                    :aria-label="`Edit ${project.name}`"
                    :title="`Edit ${project.name}`"
                    class="inline-flex h-8 w-8 items-center justify-center rounded text-blue-600 hover:bg-blue-50 hover:text-blue-800"
                  >
                    <Pencil :size="16" aria-hidden="true" />
                  </RouterLink>

                  <button
                    v-if="canDeleteProjects"
                    type="button"
                    :aria-label="`Delete ${project.name}`"
                    :title="`Delete ${project.name}`"
                    class="inline-flex h-8 w-8 items-center justify-center rounded text-red-600 hover:bg-red-50 hover:text-red-800"
                    @click="removeProject(project)"
                  >
                    <Trash2 :size="16" aria-hidden="true" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="divide-y divide-gray-100 2xl:hidden">
        <article v-for="project in projects" :key="project.id" class="space-y-4 p-4 sm:p-5">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <RouterLink
                :to="`/projects/${project.id}`"
                class="block truncate font-semibold text-gray-900 hover:text-blue-700"
              >
                {{ project.name }}
              </RouterLink>
              <p v-if="project.description" class="mt-1 line-clamp-2 text-sm text-gray-500">
                {{ project.description }}
              </p>
            </div>
            <span
              class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium"
              :class="getStatusClasses(project.status)"
            >
              {{ getStatusLabel(project.status) }}
            </span>
          </div>

          <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm sm:grid-cols-4">
            <div>
              <dt class="text-xs font-medium text-gray-500">Tasks</dt>
              <dd class="mt-1 font-semibold text-gray-800">{{ project.tasks_count }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium text-gray-500">Members</dt>
              <dd class="mt-1 font-semibold text-gray-800">{{ project.users_count }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium text-gray-500">Start date</dt>
              <dd class="mt-1 text-gray-700">{{ formatDate(project.start_date) }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium text-gray-500">Due date</dt>
              <dd class="mt-1 text-gray-700">{{ formatDate(project.due_date) }}</dd>
            </div>
          </dl>

          <div class="flex flex-wrap items-center justify-end gap-1 border-t border-gray-100 pt-3">
            <button
              v-if="canCreateTasks"
              type="button"
              :disabled="loadingTaskProjectId === project.id"
              :aria-label="`Add task to ${project.name}`"
              :title="`Add task to ${project.name}`"
              class="inline-flex h-8 items-center gap-1 rounded px-2 text-xs font-medium text-emerald-700 hover:bg-emerald-50 disabled:cursor-wait disabled:opacity-50"
              @click="openCreateTask(project)"
            >
              <ListPlus :size="15" aria-hidden="true" />
              <span>{{ loadingTaskProjectId === project.id ? 'Loading' : 'Add task' }}</span>
            </button>
            <button
              v-if="canUpdateProjects && project.status === 'on_hold'"
              type="button"
              :disabled="updatingProjectId === project.id"
              :aria-label="`Start ${project.name}`"
              :title="`Start ${project.name}`"
              class="inline-flex h-8 w-8 items-center justify-center rounded text-emerald-700 hover:bg-emerald-50 disabled:opacity-50"
              @click="changeProjectStatus(project, 'active')"
            >
              <Play :size="16" aria-hidden="true" />
            </button>
            <button
              v-if="canUpdateProjects && ['active', 'on_hold'].includes(project.status)"
              type="button"
              :disabled="updatingProjectId === project.id"
              :aria-label="`Reject ${project.name}`"
              :title="`Reject ${project.name}`"
              class="inline-flex h-8 w-8 items-center justify-center rounded text-amber-700 hover:bg-amber-50 disabled:opacity-50"
              @click="changeProjectStatus(project, 'cancelled')"
            >
              <Ban :size="16" aria-hidden="true" />
            </button>
            <RouterLink
              :to="`/projects/${project.id}`"
              :aria-label="`View ${project.name}`"
              :title="`View ${project.name}`"
              class="inline-flex h-8 w-8 items-center justify-center rounded text-gray-500 hover:bg-gray-100 hover:text-gray-800"
            >
              <Eye :size="16" aria-hidden="true" />
            </RouterLink>
            <RouterLink
              v-if="canUpdateProjects"
              :to="`/projects/${project.id}/edit`"
              :aria-label="`Edit ${project.name}`"
              :title="`Edit ${project.name}`"
              class="inline-flex h-8 w-8 items-center justify-center rounded text-blue-600 hover:bg-blue-50 hover:text-blue-800"
            >
              <Pencil :size="16" aria-hidden="true" />
            </RouterLink>
            <button
              v-if="canDeleteProjects"
              type="button"
              :aria-label="`Delete ${project.name}`"
              :title="`Delete ${project.name}`"
              class="inline-flex h-8 w-8 items-center justify-center rounded text-red-600 hover:bg-red-50 hover:text-red-800"
              @click="removeProject(project)"
            >
              <Trash2 :size="16" aria-hidden="true" />
            </button>
          </div>
        </article>
      </div>
    </div>

    <!-- Pagination -->
    <Paginator
      v-model:first="first"
      v-model:rows="rowsPerPage"
      :totalRecords="totalProjects"
      :rowsPerPageOptions="[10, 20, 30]"
      :alwaysShow="false"
      template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown CurrentPageReport"
      currentPageReportTemplate="Showing {first} to {last} of {totalRecords} projects"
      aria-label="Project pagination"
      class="rounded-lg border border-gray-200 bg-white"
      @page="handlePage"
    />

    <!-- Create Modal -->
    <CreateProject
      :show="showCreateModal"
      @close="showCreateModal = false"
      @created="handleProjectCreated"
    />
    <CreateTask
      v-if="selectedProject"
      :project="selectedProject"
      :show="showCreateTask"
      @close="showCreateTask = false"
      @created="handleTaskCreated"
    />
  </section>
</template>
