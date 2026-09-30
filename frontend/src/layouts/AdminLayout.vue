<script setup>
import Navbar from '@/components/Navbar.vue'
import SideBar from '@/components/SideBar.vue'
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
