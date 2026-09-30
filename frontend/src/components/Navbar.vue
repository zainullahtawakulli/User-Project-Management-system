<script setup>
import { onBeforeUnmount, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { searchUsers } from '@/services/userapi/user'

const showMenu = ref(false)
const searchText = ref('')
const searchResults = ref([])
const searchFocused = ref(false)
const searchLoading = ref(false)
const searchError = ref('')
const auth = useAuthStore()
const router = useRouter()
let searchRequestId = 0

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
      const response = await searchUsers(search)
      if (requestId === searchRequestId) {
        searchResults.value = response.data.users
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

const openSearchResult = async (user) => {
  searchText.value = ''
  searchFocused.value = false
  await router.push(`/user/${user.id}`)
}

onBeforeUnmount(() => {
  searchRequestId++
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
    class="sticky top-0 z-40 flex h-20 items-center justify-between border-b border-gray-200 bg-white px-6 shadow-sm"
  >
    <!-- Left side -->
    <div>
      <h1 class="text-xl font-semibold text-gray-800">Dashboard</h1>

      <p class="text-sm text-gray-500">Welcome back!</p>
    </div>

    <!-- Right side -->
    <div class="flex items-center gap-5">
      <!-- Search -->
      <div class="hidden md:block">
        <div class="relative">
          <input
            v-model="searchText"
            type="search"
            placeholder="Search name or email"
            aria-label="Search users by name or email"
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
            aria-label="User search results"
          >
            <p v-if="searchLoading" class="px-4 py-3 text-sm text-gray-500">Searching...</p>
            <p v-else-if="searchError" class="px-4 py-3 text-sm text-red-600">
              {{ searchError }}
            </p>
            <p v-else-if="searchResults.length === 0" class="px-4 py-3 text-sm text-gray-500">
              No matching users.
            </p>
            <button
              v-for="user in searchResults"
              :key="user.id"
              type="button"
              role="option"
              class="block w-full border-b border-gray-100 px-4 py-3 text-left last:border-0 hover:bg-gray-50"
              @mousedown.prevent
              @click="openSearchResult(user)"
            >
              <span class="block truncate text-sm font-medium text-gray-800">{{ user.name }}</span>
              <span class="block truncate text-xs text-gray-500">{{ user.email }}</span>
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
