<script setup>
import { onMounted } from 'vue'
import Navbar from '@/components/Navbar.vue'
import SideBar from '@/components/SideBar.vue'
import { useAuthStore } from '@/stores/auth'
import ConfirmDialog from 'primevue/confirmdialog'

const auth = useAuthStore()

onMounted(() => {
  if (auth.token && !auth.user) {
    auth.fetchUser().catch((error) => console.error('Failed to load current user:', error))
  }
})
</script>

<template>
  <div class="min-h-screen bg-gray-100">
    <ConfirmDialog group="app">
      <template #container="{ message, acceptCallback, rejectCallback }">
        <section class="w-[min(92vw,28rem)] rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl">
          <div class="flex items-start gap-4">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-100 text-xl font-bold text-amber-700">
              !
            </div>
            <div class="min-w-0 pt-1">
              <h2 class="text-lg font-semibold text-slate-900">{{ message.header }}</h2>
              <p class="mt-2 text-sm leading-6 text-slate-600">{{ message.message }}</p>
            </div>
          </div>
          <div class="mt-6 flex justify-end gap-3">
            <button
              type="button"
              class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
              @click="rejectCallback"
            >
              {{ message.rejectLabel || 'Cancel' }}
            </button>
            <button
              type="button"
              class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
              @click="acceptCallback"
            >
              {{ message.acceptLabel || 'Confirm' }}
            </button>
          </div>
        </section>
      </template>
    </ConfirmDialog>
    <!-- Sidebar -->
    <SideBar />

    <!-- Content -->
    <div class="ml-64">
      <!-- Navbar -->
      <Navbar />

      <!-- Page -->
      <main class="p-6">
        <RouterView />
      </main>
    </div>
  </div>
</template>
