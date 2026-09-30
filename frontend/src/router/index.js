import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  // Authentication
  {
    path: '/',
    component: () => import('@/layouts/AuthLayout.vue'),

    children: [
      {
        path: 'login',
        component: () => import('@/views/auth/login.vue'),
      },

      {
        path: 'register',
        component: () => import('@/views/auth/Register.vue'),
      },
    ],
  },

  // Admin
  {
    path: '/',
    component: () => import('@/layouts/AdminLayout.vue'),

    meta: {
      requireAuth: true,
    },

    children: [
      {
        path: 'dashboard',
        component: () => import('@/views/dashboard/Dashboard.vue'),
      },

      {
        path: 'user',
        component: () => import('@/views/dashboard/Users.vue'),
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.beforeEach((to) => {
  const token = localStorage.getItem('token')

  if (to.meta.requireAuth && !token) {
    return {
      path: '/login',
      replace: true,
    }
  }

  return true
})

export default router
