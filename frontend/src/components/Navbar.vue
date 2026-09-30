<script setup>
import { ref } from 'vue'

const showMenu = ref(false)

const user = {
  name: 'Admin User',
  email: 'admin@example.com',
}
</script>

<template>
  <header class="sticky top-0 z-40 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-6 shadow-sm">

    <!-- Left side -->
    <div>
      <h1 class="text-xl font-semibold text-gray-800">
        Dashboard
      </h1>

      <p class="text-sm text-gray-500">
        Welcome back!
      </p>
    </div>

    <!-- Right side -->
    <div class="flex items-center gap-5">

      <!-- Search -->
      <div class="hidden md:block">
        <div class="relative">
          <input
            type="text"
            placeholder="Search..."
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
        </div>
      </div>

      <!-- Notification -->
      <button
        class="relative rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-blue-600"
      >
        <svg
          class="h-6 w-6"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 00-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 01-6 0v-1m6 0H9"
          />
        </svg>

        <!-- Notification badge -->
        <span
          class="absolute right-1 top-1 h-2 w-2 rounded-full bg-red-500"
        ></span>
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
            AU
          </div>

          <!-- User information -->
          <div class="hidden text-left sm:block">
            <p class="text-sm font-semibold text-gray-800">
              {{ user.name }}
            </p>

            <p class="text-xs text-gray-500">
              {{ user.email }}
            </p>
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

          <RouterLink
            to="/profile"
            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
          >
            Profile
          </RouterLink>

          <RouterLink
            to="/settings"
            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
          >
            Settings
          </RouterLink>

          <div class="my-1 border-t border-gray-100"></div>

          <button
            class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50"
          >
            Logout
          </button>

        </div>

      </div>

    </div>

  </header>
</template>