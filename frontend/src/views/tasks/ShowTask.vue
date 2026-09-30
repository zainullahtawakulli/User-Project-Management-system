<script setup>
import { onMounted, ref } from 'vue'
import { ArrowLeft } from '@lucide/vue'
import { RouterLink, useRoute } from 'vue-router'

import { getTask } from '@/services/taskService'

const route = useRoute()

const task = ref(null)
const loading = ref(true)
const error = ref(null)

const fetchTask = async () => {
  loading.value = true
  error.value = null

  try {
    const response = await getTask(route.params.id)

    task.value = response.data.task
  } catch (err) {
    console.error('Fetch task error:', err)

    error.value = err.response?.data?.message || 'Failed to load task.'
  } finally {
    loading.value = false
  }
}

const formatDate = (date) => {
  if (!date) {
    return '-'
  }

  return new Date(date).toLocaleDateString()
}

const getStatusLabel = (status) => {
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

onMounted(() => {
  fetchTask()
})
</script>

<template>
  <div class="p-6">
    <!-- Loading -->

    <div v-if="loading" class="py-12 text-center text-gray-500">Loading task...</div>

    <!-- Error -->

    <div v-else-if="error" class="rounded-lg bg-red-50 p-4 text-red-600">
      {{ error }}
    </div>

    <!-- Task -->

    <div v-else-if="task">
      <!-- Back -->

      <RouterLink
        :to="`/projects/${task.project.id}`"
        class="group mb-6 inline-flex items-center gap-2 text-sm font-medium text-blue-700 transition hover:text-blue-900"
      >
        <span
          class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-50 transition group-hover:bg-blue-100"
        >
          <ArrowLeft :size="16" aria-hidden="true" />
        </span>
        <span>Back to project</span>
      </RouterLink>

      <!-- Header -->

      <div class="mb-6 rounded-xl bg-white p-6 shadow-sm">
        <div class="flex flex-col justify-between gap-4 md:flex-row">
          <div>
            <p class="mb-1 text-sm text-gray-500">
              {{ task.project.name }}
            </p>

            <h1 class="text-2xl font-bold text-gray-900">
              {{ task.title }}
            </h1>
          </div>

          <RouterLink
            :to="`/tasks/${task.id}/edit`"
            class="rounded-lg bg-blue-600 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-blue-700"
          >
            Edit Task
          </RouterLink>
        </div>
      </div>

      <!-- Details -->

      <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <!-- Description -->

        <div class="rounded-xl bg-white p-6 shadow-sm md:col-span-2">
          <h2 class="mb-3 text-lg font-semibold text-gray-900">Description</h2>

          <p class="whitespace-pre-wrap text-gray-600">
            {{ task.description || 'No description.' }}
          </p>
        </div>

        <!-- Status -->

        <div class="rounded-xl bg-white p-6 shadow-sm">
          <p class="text-sm text-gray-500">Status</p>

          <p class="mt-1 font-semibold text-gray-900">
            {{ getStatusLabel(task.status) }}
          </p>
        </div>

        <!-- Priority -->

        <div class="rounded-xl bg-white p-6 shadow-sm">
          <p class="text-sm text-gray-500">Priority</p>

          <p class="mt-1 font-semibold text-gray-900">
            {{ getPriorityLabel(task.priority) }}
          </p>
        </div>

        <!-- Assignee -->

        <div class="rounded-xl bg-white p-6 shadow-sm">
          <p class="text-sm text-gray-500">Assigned To</p>

          <div v-if="task.assignees?.length || task.assignee" class="mt-1">
            <div v-for="user in (task.assignees?.length ? task.assignees : [task.assignee])" :key="user.id" class="mb-2 last:mb-0">
              <p class="font-semibold text-gray-900">{{ user.name }}</p>
              <p class="text-sm text-gray-500">{{ user.email }}</p>
            </div>
          </div>

          <p v-else class="mt-1 text-gray-400">Unassigned</p>
        </div>

        <!-- Due date -->

        <div class="rounded-xl bg-white p-6 shadow-sm">
          <p class="text-sm text-gray-500">Due Date</p>

          <p class="mt-1 font-semibold text-gray-900">
            {{ formatDate(task.due_date) }}
          </p>
        </div>

        <!-- Creator -->

        <div class="rounded-xl bg-white p-6 shadow-sm md:col-span-2">
          <p class="text-sm text-gray-500">Created By</p>

          <p class="mt-1 font-semibold text-gray-900">
            {{ task.creator?.name || '-' }}
          </p>

          <p v-if="task.creator?.email" class="text-sm text-gray-500">
            {{ task.creator.email }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
