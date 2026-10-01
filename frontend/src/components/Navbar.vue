<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { searchWorkspace } from '@/services/searchService'
import { toast } from 'vue-sonner'

const showMenu = ref(false)
const searchText = ref('')
const searchResults = ref([])
const searchFocused = ref(false)
const searchLoading = ref(false)
const searchError = ref('')
const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const currentTime = ref(Date.now())
const returningToAdmin = ref(false)
let searchRequestId = 0
let impersonationTimer = null

const pageTitle = computed(() => route.meta.title || 'Workspace')
const pageSubtitle = computed(() => {
  if (route.name === 'dashboard') {
    const firstName = auth.user?.name?.trim().split(/\s+/)[0]
    return firstName ? `Welcome back, ${firstName}. Here’s your workspace at a glance.` : 'Welcome back. Here’s your workspace at a glance.'
  }

  return route.meta.subtitle || 'Manage your workspace and keep everything moving.'
})

const impersonationSecondsLeft = computed(() => {
  currentTime.value
  const expiresAt = Date.parse(auth.impersonation?.expiresAt || '')
  return Number.isFinite(expiresAt) ? Math.max(0, Math.ceil((expiresAt - currentTime.value) / 1000)) : 0
})

const impersonationCountdown = computed(() => {
  const minutes = Math.floor(impersonationSecondsLeft.value / 60)
  const seconds = impersonationSecondsLeft.value % 60
  return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
})

const returnToAdmin = async () => {
  returningToAdmin.value = true
  try {
    const admin = await auth.returnToAdmin()
    toast.success(`Returned to ${admin.name}'s admin session.`)
    await router.replace('/dashboard')
  } catch (error) {
    toast.error(error.response?.data?.message || error.message || 'Could not return to the admin session.')
  } finally {
    returningToAdmin.value = false
  }
}

const expireImpersonation = () => {
  if (!auth.isImpersonating || impersonationSecondsLeft.value > 0) return

  auth.clearImpersonation()
  localStorage.removeItem('token')
  auth.token = null
  auth.user = null
  toast.error('The temporary user session expired. Sign in again to continue.')
  router.replace('/login')
}

onMounted(() => {
  impersonationTimer = setInterval(() => {
    currentTime.value = Date.now()
    expireImpersonation()
  }, 1000)
})

watch(searchText, (value, _, onCleanup) => {
  const search = value.trim()
  const requestId = ++searchRequestId

  searchResults.value = []
  searchError.value = ''

  if (search.length < 2) {
    searchLoading.value = false
    return
  }

  searchLoading.value = true
  const timeoutId = setTimeout(async () => {
    try {
      const response = await searchWorkspace(search)
      if (requestId === searchRequestId) {
        searchResults.value = response.data.results || []
      }
    } catch (error) {
      if (requestId === searchRequestId) {
        searchError.value = error.response?.data?.message || 'Search failed.'
      }
    } finally {
      if (requestId === searchRequestId) {
        searchLoading.value = false
      }
    }
  }, 250)

  onCleanup(() => clearTimeout(timeoutId))
})

const openSearchResult = async (result) => {
  searchText.value = ''
  searchFocused.value = false
  await router.push(result.url)
}

onBeforeUnmount(() => {
  searchRequestId++
  clearInterval(impersonationTimer)
})

const initials = () => {
  return (
    auth.user?.name
      ?.split(/\s+/)
      .map((part) => part[0])
      .join('')
      .slice(0, 2)
      .toUpperCase() || 'U'
  )
}

const logout = async () => {
  try {
    await auth.logout()
  } catch (error) {
    console.error('Failed to log out:', error)
  } finally {
    showMenu.value = false
    await router.replace('/login')
  }
}
</script>

<template>
  <header
    class="sticky top-0 z-40 flex min-h-20 flex-wrap items-center justify-between gap-y-2 border-b border-slate-200/80 bg-white/95 px-5 shadow-sm shadow-slate-900/[0.03] backdrop-blur sm:px-8"
  >
    <!-- Left side -->
    <div>
      <div class="mb-1 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-blue-600">
        <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
        Workspace
      </div>
      <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">{{ pageTitle }}</h1>
      <p class="mt-0.5 hidden text-sm text-slate-500 sm:block">{{ pageSubtitle }}</p>
    </div>

    <!-- Right side -->
    <div class="flex items-center gap-5">
      <div
        v-if="auth.isImpersonating"
        class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2"
        role="status"
        aria-live="polite"
      >
        <div class="hidden min-w-0 sm:block">
          <p class="text-[10px] font-semibold uppercase tracking-wide text-amber-700">Viewing as</p>
          <p class="max-w-32 truncate text-xs font-semibold text-amber-950">{{ auth.user?.name }}</p>
        </div>
        <span class="font-mono text-sm font-bold tabular-nums text-amber-900">{{ impersonationCountdown }}</span>
        <button
          type="button"
          :disabled="returningToAdmin"
          class="whitespace-nowrap rounded-lg bg-amber-700 px-3 py-2 text-xs font-semibold text-white transition hover:bg-amber-800 disabled:opacity-60"
          @click="returnToAdmin"
        >
          {{ returningToAdmin ? 'Returning...' : 'Return to admin' }}
        </button>
      </div>

      <!-- Search -->
      <div class="hidden md:block">
        <div class="relative">
          <input
            v-model="searchText"
            type="search"
            placeholder="Search users, projects, tasks..."
            aria-label="Search workspace"
            autocomplete="off"
            @focus="searchFocused = true"
            @blur="searchFocused = false"
            @keydown.esc="searchFocused = false"
            class="w-64 rounded-lg border border-gray-300 bg-gray-50 py-2 pl-10 pr-4 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
          />

          <svg
            class="absolute left-3 top-2.5 h-5 w-5 text-gray-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="m21 21-4.35-4.35m2.35-5.65a8 8 0 11-16 0 8 8 0 0116 0z"
            />
          </svg>

          <div
            v-if="searchFocused && searchText.trim().length >= 2"
            class="absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg"
            role="listbox"
            aria-label="Workspace search results"
          >
            <p v-if="searchLoading" class="px-4 py-3 text-sm text-gray-500">Searching...</p>
            <p v-else-if="searchError" class="px-4 py-3 text-sm text-red-600">
              {{ searchError }}
            </p>
            <p v-else-if="searchResults.length === 0" class="px-4 py-3 text-sm text-gray-500">
              No matching results.
            </p>
            <button
              v-for="result in searchResults"
              :key="`${result.type}-${result.id}`"
              type="button"
              role="option"
              class="block w-full border-b border-gray-100 px-4 py-3 text-left last:border-0 hover:bg-gray-50"
              @mousedown.prevent
              @click="openSearchResult(result)"
            >
              <span class="flex items-center justify-between gap-3">
                <span class="block truncate text-sm font-medium text-gray-800">{{ result.title }}</span>
                <span class="shrink-0 rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700">{{ result.type }}</span>
              </span>
              <span class="mt-0.5 block truncate text-xs text-gray-500">{{ result.subtitle }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Notification -->
      <button
        class="relative rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-blue-600"
      >
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 00-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 01-6 0v-1m6 0H9"
          />
        </svg>

        <!-- Notification badge -->
        <span class="absolute right-1 top-1 h-2 w-2 rounded-full bg-red-500"></span>
      </button>

      <!-- User -->
      <div class="relative">
        <button
          @click="showMenu = !showMenu"
          class="flex items-center gap-3 rounded-lg p-1.5 transition hover:bg-gray-100"
        >
          <!-- Avatar -->
          <div
            class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-600 text-sm font-semibold text-white"
          >
            {{ initials() }}
          </div>

          <!-- User information -->
          <div class="hidden text-left sm:block">
            <p class="text-sm font-semibold text-gray-800">
              {{ auth.user?.name || 'Loading profile...' }}
            </p>

            <p class="text-xs text-gray-500">
              {{ auth.user?.email || '' }}
            </p>

            <span
              v-if="auth.user?.role"
              class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase"
              :class="
                auth.user.role === 'admin'
                  ? 'bg-blue-100 text-blue-700'
                  : 'bg-gray-100 text-gray-600'
              "
            >
              {{ auth.user.role === 'admin' ? 'Admin' : 'User' }}
            </span>
          </div>

          <!-- Arrow -->
          <svg
            class="hidden h-4 w-4 text-gray-500 sm:block"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M19 9l-7 7-7-7"
            />
          </svg>
        </button>

        <!-- Dropdown -->
        <div
          v-if="showMenu"
          class="absolute right-0 mt-2 w-48 rounded-lg border border-gray-200 bg-white py-2 shadow-lg"
        >
          <RouterLink to="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
            Profile
          </RouterLink>

          <div class="my-1 border-t border-gray-100"></div>

          <button
            @click="logout"
            class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50"
          >
            Logout
          </button>
        </div>
      </div>
    </div>
  </header>
</template>
