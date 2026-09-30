<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import {
  LayoutDashboard,
  Users,
  FolderKanban,
  ListTodo,
  UserCircle,
  ShieldCheck,
  LogOut,
  ChevronRight,
} from '@lucide/vue'

const router = useRouter()
const auth = useAuthStore()

const userInitial = computed(() => {
  return auth.user?.name?.charAt(0)?.toUpperCase() || 'U'
})

const formattedRole = computed(() => {
  const role = auth.user?.role || 'user'

  return role.replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase())
})

const logout = async () => {
  try {
    await auth.logout()
  } catch (error) {
    console.error('Failed to log out:', error)
  } finally {
    await router.replace('/login')
  }
}
</script>

<template>
  <aside
    class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-slate-200 bg-slate-950 text-white"
  >
    <!-- ===================================================== -->
    <!-- Logo -->
    <!-- ===================================================== -->
    <div class="flex h-20 items-center border-b border-slate-800 px-6">
      <RouterLink to="/dashboard" class="flex items-center gap-3">
        <!-- Logo Icon -->
        <div
          class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 shadow-lg shadow-blue-600/20"
        >
          <LayoutDashboard :size="21" stroke-width="2.2" />
        </div>

        <div>
          <h1 class="text-base font-bold tracking-wide text-white">MyAdmin</h1>

          <p class="text-[11px] font-medium text-slate-500">Administration</p>
        </div>
      </RouterLink>
    </div>

    <!-- ===================================================== -->
    <!-- Navigation -->
    <!-- ===================================================== -->
    <div class="flex-1 overflow-y-auto px-4 py-6">
      <!-- Main Navigation -->
      <div class="mb-3 px-2">
        <p class="text-[10px] font-semibold uppercase tracking-[0.15em] text-slate-500">
          Main Menu
        </p>
      </div>

      <nav class="space-y-1">
        <!-- Dashboard -->
        <RouterLink
          to="/dashboard"
          class="group flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium transition-all duration-200"
          :class="
            $route.path === '/dashboard'
              ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20'
              : 'text-slate-400 hover:bg-slate-900 hover:text-white'
          "
        >
          <LayoutDashboard
            :size="19"
            :class="
              $route.path === '/dashboard'
                ? 'text-white'
                : 'text-slate-500 group-hover:text-slate-300'
            "
          />

          <span class="flex-1"> Dashboard </span>

          <ChevronRight v-if="$route.path === '/dashboard'" :size="15" class="text-blue-200" />
        </RouterLink>

        <!-- Users -->
        <RouterLink
          v-if="auth.can('users.view')"
          to="/user"
          class="group flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium transition-all duration-200"
          :class="
            $route.path.startsWith('/user')
              ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20'
              : 'text-slate-400 hover:bg-slate-900 hover:text-white'
          "
        >
          <Users
            :size="19"
            :class="
              $route.path.startsWith('/user')
                ? 'text-white'
                : 'text-slate-500 group-hover:text-slate-300'
            "
          />

          <span class="flex-1"> Users </span>

          <ChevronRight v-if="$route.path.startsWith('/user')" :size="15" class="text-blue-200" />
        </RouterLink>

        <!-- Projects -->
        <RouterLink
          v-if="auth.can('projects.view')"
          to="/projects"
          class="group flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium transition-all duration-200"
          :class="
            $route.path.startsWith('/projects')
              ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20'
              : 'text-slate-400 hover:bg-slate-900 hover:text-white'
          "
        >
          <FolderKanban
            :size="19"
            :class="
              $route.path.startsWith('/projects')
                ? 'text-white'
                : 'text-slate-500 group-hover:text-slate-300'
            "
          />

          <span class="flex-1"> Projects </span>

          <ChevronRight
            v-if="$route.path.startsWith('/projects')"
            :size="15"
            class="text-blue-200"
          />
        </RouterLink>

        <!-- Tasks -->
        <RouterLink
          v-if="auth.can('tasks.view')"
          to="/tasks"
          class="group flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium transition-all duration-200"
          :class="
            $route.path.startsWith('/tasks')
              ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20'
              : 'text-slate-400 hover:bg-slate-900 hover:text-white'
          "
        >
          <ListTodo
            :size="19"
            :class="
              $route.path.startsWith('/tasks')
                ? 'text-white'
                : 'text-slate-500 group-hover:text-slate-300'
            "
          />

          <span class="flex-1"> Tasks </span>

          <ChevronRight v-if="$route.path.startsWith('/tasks')" :size="15" class="text-blue-200" />
        </RouterLink>
      </nav>

      <!-- Management -->
      <div class="mb-3 mt-8 px-2">
        <p class="text-[10px] font-semibold uppercase tracking-[0.15em] text-slate-500">
          Management
        </p>
      </div>

      <nav class="space-y-1">
        <!-- Roles & Permissions -->
        <RouterLink
          v-if="auth.can('roles.view')"
          to="/roles-permissions"
          class="group flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium transition-all duration-200"
          :class="
            $route.path.startsWith('/roles-permissions')
              ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20'
              : 'text-slate-400 hover:bg-slate-900 hover:text-white'
          "
        >
          <ShieldCheck
            :size="19"
            :class="
              $route.path.startsWith('/roles-permissions')
                ? 'text-white'
                : 'text-slate-500 group-hover:text-slate-300'
            "
          />

          <span class="flex-1"> Roles & Permissions </span>

          <ChevronRight
            v-if="$route.path.startsWith('/roles-permissions')"
            :size="15"
            class="text-blue-200"
          />
        </RouterLink>

        <!-- Profile -->
        <RouterLink
          to="/profile"
          class="group flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium transition-all duration-200"
          :class="
            $route.path.startsWith('/profile')
              ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20'
              : 'text-slate-400 hover:bg-slate-900 hover:text-white'
          "
        >
          <UserCircle
            :size="19"
            :class="
              $route.path.startsWith('/profile')
                ? 'text-white'
                : 'text-slate-500 group-hover:text-slate-300'
            "
          />

          <span class="flex-1"> Profile </span>

          <ChevronRight
            v-if="$route.path.startsWith('/profile')"
            :size="15"
            class="text-blue-200"
          />
        </RouterLink>
      </nav>
    </div>

    <!-- ===================================================== -->
    <!-- User Section -->
    <!-- ===================================================== -->
    <div v-if="auth.user" class="border-t border-slate-800 bg-slate-950 p-4">
      <!-- User Card -->
      <div class="mb-3 rounded-xl border border-slate-800 bg-slate-900/70 p-3">
        <div class="flex items-center gap-3">
          <!-- Avatar -->
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-sm font-bold text-white shadow-lg shadow-blue-600/10"
          >
            {{ userInitial }}
          </div>

          <!-- User Info -->
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-white">
              {{ auth.user.name }}
            </p>

            <p class="mt-0.5 truncate text-xs text-slate-500">
              {{ auth.user.email }}
            </p>
          </div>
        </div>

        <!-- Role Badge -->
        <div class="mt-3">
          <span
            class="inline-flex items-center rounded-md border border-blue-500/20 bg-blue-500/10 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-blue-400"
          >
            {{ formattedRole }}
          </span>
        </div>
      </div>

      <!-- Logout -->
      <button
        type="button"
        @click="logout"
        class="group flex w-full items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-slate-400 transition-all duration-200 hover:bg-red-500/10 hover:text-red-400"
      >
        <LogOut :size="19" class="text-slate-500 transition-colors group-hover:text-red-400" />

        <span> Sign out </span>
      </button>
    </div>
  </aside>
</template>
