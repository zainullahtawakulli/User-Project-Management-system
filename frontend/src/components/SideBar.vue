<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()

const logout = () => {
  localStorage.removeItem('token')
  router.push('/login')
}
</script>

<template>
  <aside class="fixed left-0 top-0 flex h-screen w-64 flex-col bg-blue-700 text-white">

    <!-- Logo -->
    <div class="flex h-16 items-center border-b border-blue-600 px-6">
      <h1 class="text-xl font-bold">
        MyAdmin
      </h1>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 space-y-2 px-4 py-6">

      <!-- Dashboard -->
      <RouterLink
        to="/dashboard"
        class="flex items-center gap-3 rounded-lg px-4 py-3 text-blue-100 transition hover:bg-blue-600 hover:text-white"
        active-class="bg-white text-blue-700 font-semibold"
      >
        <svg
          class="h-5 w-5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10"
          />
        </svg>

        <span>Dashboard</span>
      </RouterLink>

      <!-- Users -->
      <RouterLink
        to="/user"
        class="flex items-center gap-3 rounded-lg px-4 py-3 text-blue-100 transition hover:bg-blue-600 hover:text-white"
        active-class="bg-white text-blue-700 font-semibold"
      >
        <svg
          class="h-5 w-5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 100-8 4 4 0 000 8zm6 4a4 4 0 10-8 0"
          />
        </svg>

        <span>Users</span>
      </RouterLink>

    </nav>

    <!-- Logout -->
    <div class="border-t border-blue-600 p-4">

      <button
        @click="logout"
        class="flex w-full items-center gap-3 rounded-lg px-4 py-3 text-blue-100 transition hover:bg-red-500 hover:text-white"
      >
        <svg
          class="h-5 w-5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M17 16l4-4m0 0l-4-4m4 4H9m4 4v1a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2h5a2 2 0 012 2v1"
          />
        </svg>

        <span>Logout</span>
      </button>

    </div>

  </aside>
</template>