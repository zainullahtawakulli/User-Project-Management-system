import { createRouter, createWebHistory } from 'vue-router'
import { ref } from 'vue'

import Profile from '@/views/Profile.vue'
import { useAuthStore } from '@/stores/auth'
import ActivityLogs from '@/views/logs/ActivityLogs.vue'

/*
|--------------------------------------------------------------------------
| Route Loading
|--------------------------------------------------------------------------
*/

export const routeLoading = ref(false)

/*
|--------------------------------------------------------------------------
| Routes
|--------------------------------------------------------------------------
*/

const routes = [
  /*
  |--------------------------------------------------------------------------
  | Authentication Layout
  |--------------------------------------------------------------------------
  */

  {
    path: '/',
    component: () => import('@/layouts/AuthLayout.vue'),

    children: [
      {
        path: 'login',
        name: 'login',
        component: () => import('@/views/auth/login.vue'),

        meta: {
          guest: true,
        },
      },

      {
        path: 'register',
        name: 'register',
        component: () => import('@/views/auth/Register.vue'),

        meta: {
          guest: true,
        },
      },
    ],
  },

  /*
  |--------------------------------------------------------------------------
  | Admin / Authenticated Layout
  |--------------------------------------------------------------------------
  */

  {
    path: '/',
    component: () => import('@/layouts/AdminLayout.vue'),

    meta: {
      requiresAuth: true,
    },

    children: [
      /*
      |--------------------------------------------------------------------------
      | Dashboard
      |--------------------------------------------------------------------------
      */

      {
        path: 'dashboard',
        name: 'dashboard',
        component: () => import('@/views/dashboard/Dashboard.vue'),
        meta: {
          title: 'Dashboard',
        },
      },

      /*
      |--------------------------------------------------------------------------
      | Users
      |--------------------------------------------------------------------------
      */

      {
        path: 'user',
        name: 'users',
        component: () => import('@/views/dashboard/Users.vue'),

        meta: {
          permission: 'users.view',
          title: 'Users',
          subtitle: 'Manage accounts, access, and team members.',
        },
      },

      {
        path: 'user/:id',
        name: 'user.show',
        component: () => import('@/views/users/ShowUser.vue'),

        meta: {
          permission: 'users.view',
          title: 'User Details',
          subtitle: 'Review this account and its workspace access.',
        },
      },

      /*
      |--------------------------------------------------------------------------
      | Projects
      |--------------------------------------------------------------------------
      */

      {
        path: 'projects',
        name: 'projects',
        component: () => import('@/views/projects/Projects.vue'),

        meta: {
          permission: 'projects.view',
          title: 'Projects',
          subtitle: 'Plan work, manage project members, and track progress.',
        },
      },

      {
        path: 'projects/:id',
        name: 'project.show',
        component: () => import('@/views/projects/ShowProject.vue'),

        meta: {
          permission: 'projects.view',
          title: 'Project Details',
          subtitle: 'Review project progress, members, and related tasks.',
        },
      },

      {
        path: 'projects/:id/edit',
        name: 'project.edit',
        component: () => import('@/views/projects/EditProject.vue'),

        meta: {
          permission: 'projects.update',
          title: 'Edit Project',
          subtitle: 'Update project information and its members.',
        },
      },

      /*
      |--------------------------------------------------------------------------
      | Tasks
      |--------------------------------------------------------------------------
      */

      {
        path: 'tasks',
        name: 'tasks',
        component: () => import('@/views/tasks/Tasks.vue'),

        meta: {
          permission: 'tasks.view',
          title: 'Tasks',
          subtitle: 'Track assignments, priorities, and upcoming work.',
        },
      },

      {
        path: 'tasks/:id',
        name: 'task.show',
        component: () => import('@/views/tasks/ShowTask.vue'),

        meta: {
          permission: 'tasks.view',
          title: 'Task Details',
          subtitle: 'Review task ownership, status, and due date.',
        },
      },

      {
        path: 'tasks/:id/edit',
        name: 'task.edit',
        component: () => import('@/views/tasks/EditTask.vue'),

        meta: {
          permission: 'tasks.update',
          title: 'Edit Task',
          subtitle: 'Update task details and assignment.',
        },
      },

      {
        path: 'roles-permissions',
        name: 'roles-permissions',
        component: () => import('@/views/role/RolesPermissions.vue'),
        meta: {
          permission: 'roles.view',
          title: 'Roles & Permissions',
          subtitle: 'Define roles and control which parts of the app they can access.',
        },
      },

      /*
      |--------------------------------------------------------------------------
      | Profile
      |--------------------------------------------------------------------------
      */

      {
        path: 'profile',
        name: 'profile',
        component: Profile,
        meta: {
          title: 'My Profile',
          subtitle: 'Manage your personal details and account security.',
        },
      },
      {
        path: 'activity-logs',
        name: 'activity-logs',
        component: ActivityLogs,
        meta: {
          requiresAuth: true,
          permission: 'activity_logs.view',
          title: 'Activity Logs',
          subtitle: 'See recent actions and changes across the workspace.',
        },
      },
    ],
  },

  /*
  |--------------------------------------------------------------------------
  | Optional 404
  |--------------------------------------------------------------------------
  */

  {
    path: '/:pathMatch(.*)*',
    redirect: '/dashboard',
  },
]

/*
|--------------------------------------------------------------------------
| Router
|--------------------------------------------------------------------------
*/

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,

  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    }

    return {
      top: 0,
    }
  },
})

/*
|--------------------------------------------------------------------------
| Route Loading
|--------------------------------------------------------------------------
*/

let routeLoadingTimer = null
let routeLoadingStartedAt = 0

router.beforeEach(async (to) => {
  /*
  |--------------------------------------------------------------------------
  | Start Loading Indicator
  |--------------------------------------------------------------------------
  */

  clearTimeout(routeLoadingTimer)

  routeLoading.value = true
  routeLoadingStartedAt = Date.now()

  /*
  |--------------------------------------------------------------------------
  | Auth Store
  |--------------------------------------------------------------------------
  */

  const auth = useAuthStore()

  /*
  |--------------------------------------------------------------------------
  | Initialize Authentication
  |--------------------------------------------------------------------------
  |
  | When the application starts, fetch the authenticated user
  | from Laravel if we have a token.
  |
  */

  if (!auth.initialized) {
    try {
      await auth.fetchUser()
    } catch (error) {
      /*
      |--------------------------------------------------------------------------
      | Invalid / Expired Token
      |--------------------------------------------------------------------------
      |
      | fetchUser() already clears the token when Laravel returns 401.
      | We don't want an authentication initialization error to crash
      | Vue Router.
      |
      */

      console.error('Authentication initialization failed:', error)
    }
  }

  /*
  |--------------------------------------------------------------------------
  | Guest Routes
  |--------------------------------------------------------------------------
  |
  | If the user is already authenticated and tries to visit
  | /login or /register, send them to the dashboard.
  |
  */

  if (to.meta.guest && auth.isLoggedIn) {
    return {
      name: 'dashboard',
      replace: true,
    }
  }

  /*
  |--------------------------------------------------------------------------
  | Authentication Check
  |--------------------------------------------------------------------------
  */

  if (to.meta.requiresAuth && !auth.isLoggedIn) {
    return {
      name: 'login',
      replace: true,
    }
  }

  /*
  |--------------------------------------------------------------------------
  | Permission Check
  |--------------------------------------------------------------------------
  |
  | Example:
  |
  | meta: {
  |   permission: 'projects.create'
  | }
  |
  | The permission comes from the Laravel backend and is available
  | through auth.can().
  |
  */

  if (to.meta.permission && !auth.can(to.meta.permission)) {
    return {
      name: 'dashboard',
      replace: true,
    }
  }

  /*
  |--------------------------------------------------------------------------
  | Allow Navigation
  |--------------------------------------------------------------------------
  */

  return true
})

/*
|--------------------------------------------------------------------------
| Finish Route Loading
|--------------------------------------------------------------------------
*/

const finishRouteLoading = () => {
  clearTimeout(routeLoadingTimer)

  const minimumVisibleTime = 300

  const elapsedTime = Date.now() - routeLoadingStartedAt

  const remainingTime = Math.max(0, minimumVisibleTime - elapsedTime)

  routeLoadingTimer = setTimeout(() => {
    routeLoading.value = false
  }, remainingTime)
}

router.afterEach(() => {
  finishRouteLoading()
})

router.onError(() => {
  finishRouteLoading()
})

/*
|--------------------------------------------------------------------------
| Export Router
|--------------------------------------------------------------------------
*/

export default router
