<script setup>
import { onMounted } from 'vue'
import Navbar from '@/components/Navbar.vue'
import SideBar from '@/components/SideBar.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

onMounted(() => {
  if (auth.token && !auth.user) {
    auth.fetchUser().catch((error) => console.error('Failed to load current user:', error))
  }
})
</script>

<template>
  <div class="min-h-screen bg-gray-100">
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
