<script setup>
import { routeLoading } from '@/router'
import { Toaster } from 'vue-sonner'
import 'vue-sonner/style.css'
</script>

<template>
  <RouterView />

  <Toaster position="top-right" rich-colors close-button />

  <Transition
    enter-active-class="transition-opacity duration-150 ease-out"
    leave-active-class="transition-opacity duration-150 ease-in"
    enter-from-class="opacity-0"
    leave-to-class="opacity-0"
  >
    <div
      v-if="routeLoading"
      class="fixed inset-0 z-100 grid place-items-center bg-white/60 backdrop-blur-[2px]"
      role="status"
      aria-live="polite"
      aria-label="Loading page"
    >
      <div class="flex flex-col items-center gap-3">
        <span
          class="h-12 w-12 animate-spin rounded-full border-4 border-blue-100 border-t-blue-600"
          aria-hidden="true"
        ></span>
        <span class="text-sm font-medium text-gray-600">Loading page...</span>
      </div>
    </div>
  </Transition>
</template>
