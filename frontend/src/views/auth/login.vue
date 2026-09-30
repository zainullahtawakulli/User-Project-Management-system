<script setup>
defineOptions({ name: 'LoginView' })

import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const auth = useAuthStore()

const router = useRouter()

const form = ref({
  email: '',
  password: '',
})

const errors = ref({})

const login = async () => {
  errors.value = {}

  // Frontend validation
  if (!form.value.email) {
    errors.value.email = 'Email is required.'
  } else if (!/\S+@\S+\.\S+/.test(form.value.email)) {
    errors.value.email = 'Please enter a valid email.'
  }

  if (!form.value.password) {
    errors.value.password = 'Password is required.'
  } else if (form.value.password.length < 8) {
    errors.value.password = 'Password must be at least 8 characters.'
  }

  // Stop if frontend validation has errors
  if (Object.keys(errors.value).length > 0) {
    return
  }

  try {
    await auth.login(form.value)

    await router.replace('/dashboard')
    console.log('Login successful')
    console.log('User:', auth.user)
    console.log('Token:', auth.token)

    // Clear form after successful login
    form.value = {
      email: '',
      password: '',
    }

    errors.value = {}
  } catch (error) {
    // Laravel validation errors
    errors.value = auth.errors

    console.log('Login failed:', error)
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-100 flex items-center justify-center px-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8">
      <!-- Heading -->
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Welcome Back</h1>

        <p class="text-gray-500 mt-2">Login to your account</p>
      </div>

      <form @submit.prevent="login" class="space-y-5">
        <!-- Email -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1"> Email </label>

          <input
            v-model="form.email"
            type="email"
            placeholder="Enter your email"
            class="w-full px-4 py-3 border rounded-lg outline-none transition focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            :class="{
              'border-red-500': errors.email,
            }"
          />

          <p v-if="errors.email" class="text-red-500 text-sm mt-1">
            {{ Array.isArray(errors.email) ? errors.email[0] : errors.email }}
          </p>
        </div>

        <!-- Password -->
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="block text-sm font-medium text-gray-700"> Password </label>

            <a href="#" class="text-sm text-blue-600 hover:text-blue-700"> Forgot password? </a>
          </div>

          <input
            v-model="form.password"
            type="password"
            placeholder="Enter your password"
            class="w-full px-4 py-3 border rounded-lg outline-none transition focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            :class="{
              'border-red-500': errors.password,
            }"
          />

          <p v-if="errors.password" class="text-red-500 text-sm mt-1">
            {{ Array.isArray(errors.password) ? errors.password[0] : errors.password }}
          </p>
        </div>

        <!-- Login Button -->
        <button
          type="submit"
          :disabled="auth.loading"
          class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold transition hover:bg-blue-700 disabled:bg-blue-400 disabled:cursor-not-allowed"
        >
          {{ auth.loading ? 'Logging in...' : 'Login' }}
        </button>
      </form>

      <!-- Register Link -->
      <p class="text-center text-gray-500 text-sm mt-6">
        Don't have an account?

        <RouterLink to="register" class="text-blue-600 font-semibold hover:text-blue-700">
          Register
        </RouterLink>
      </p>

      <!-- Success Message -->
      <p v-if="auth.user" class="mt-5 text-center text-green-600 font-medium">Login successful!</p>
    </div>
  </div>
</template>
