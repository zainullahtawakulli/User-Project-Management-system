<script setup>
import { onMounted, ref } from 'vue'
import { toast } from 'vue-sonner'

import { getProfile, updateProfile, updatePassword } from '@/services/profileService'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const loading = ref(true)
const savingProfile = ref(false)
const changingPassword = ref(false)

const profileError = ref(null)
const passwordError = ref(null)

const profileErrors = ref({})
const passwordErrors = ref({})

const form = ref({
  name: '',
  email: '',
})

const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
})

const fetchProfile = async () => {
  loading.value = true
  profileError.value = null

  try {
    const response = await getProfile()

    const user = response.data.user

    form.value = {
      name: user.name || '',
      email: user.email || '',
    }
  } catch (error) {
    console.error('Fetch profile error:', error)

    profileError.value = error.response?.data?.message || 'Failed to load profile.'
  } finally {
    loading.value = false
  }
}

const saveProfile = async () => {
  savingProfile.value = true
  profileError.value = null
  profileErrors.value = {}

  try {
    const response = await updateProfile({
      name: form.value.name,
      email: form.value.email,
    })

    form.value = {
      name: response.data.user.name,
      email: response.data.user.email,
    }
    auth.user = response.data.user

    toast.success(response.data.message || 'Profile updated successfully.')
  } catch (error) {
    console.error('Update profile error:', error)

    profileErrors.value = error.response?.data?.errors || {}

    profileError.value = error.response?.data?.message || 'Failed to update profile.'
  } finally {
    savingProfile.value = false
  }
}

const changePassword = async () => {
  changingPassword.value = true
  passwordError.value = null
  passwordErrors.value = {}

  try {
    const response = await updatePassword({
      current_password: passwordForm.value.current_password,

      new_password: passwordForm.value.new_password,

      new_password_confirmation: passwordForm.value.new_password_confirmation,
    })

    passwordForm.value = {
      current_password: '',
      new_password: '',
      new_password_confirmation: '',
    }

    toast.success(response.data.message || 'Password changed successfully.')
  } catch (error) {
    console.error('Change password error:', error)

    passwordErrors.value = error.response?.data?.errors || {}

    passwordError.value = error.response?.data?.message || 'Failed to change password.'
  } finally {
    changingPassword.value = false
  }
}

const getFieldError = (errors, field) => {
  if (!errors[field]) {
    return null
  }

  return errors[field][0]
}

onMounted(() => {
  fetchProfile()
})
</script>

<template>
  <div class="p-6">
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">Profile</h1>

      <p class="mt-1 text-sm text-gray-500">Manage your account information and password.</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="rounded-xl bg-white p-12 text-center shadow-sm">
      <p class="text-gray-500">Loading profile...</p>
    </div>

    <!-- Profile -->
    <div v-else class="mx-auto max-w-4xl space-y-6">
      <!-- Profile Information -->
      <div class="rounded-xl bg-white shadow-sm">
        <div class="border-b px-6 py-5">
          <h2 class="text-lg font-semibold text-gray-900">Personal Information</h2>

          <p class="mt-1 text-sm text-gray-500">Update your name and email address.</p>
        </div>

        <form @submit.prevent="saveProfile" class="space-y-5 p-6">
          <!-- General Error -->
          <div v-if="profileError" class="rounded-lg bg-red-50 p-3 text-sm text-red-600">
            {{ profileError }}
          </div>

          <!-- Name -->
          <div>
            <label for="name" class="mb-1 block text-sm font-medium text-gray-700"> Name </label>

            <input
              id="name"
              v-model="form.name"
              type="text"
              required
              autocomplete="name"
              class="w-full rounded-lg border border-gray-300 px-3 py-2.5 outline-none transition focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
              :class="{
                'border-red-500': getFieldError(profileErrors, 'name'),
              }"
            />

            <p v-if="getFieldError(profileErrors, 'name')" class="mt-1 text-sm text-red-600">
              {{ getFieldError(profileErrors, 'name') }}
            </p>
          </div>

          <!-- Email -->
          <div>
            <label for="email" class="mb-1 block text-sm font-medium text-gray-700"> Email </label>

            <input
              id="email"
              v-model="form.email"
              type="email"
              required
              autocomplete="email"
              class="w-full rounded-lg border border-gray-300 px-3 py-2.5 outline-none transition focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
              :class="{
                'border-red-500': getFieldError(profileErrors, 'email'),
              }"
            />

            <p v-if="getFieldError(profileErrors, 'email')" class="mt-1 text-sm text-red-600">
              {{ getFieldError(profileErrors, 'email') }}
            </p>
          </div>

          <!-- Save -->
          <div class="flex justify-end border-t pt-5">
            <button
              type="submit"
              :disabled="savingProfile"
              class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
              {{ savingProfile ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Change Password -->
      <div class="rounded-xl bg-white shadow-sm">
        <div class="border-b px-6 py-5">
          <h2 class="text-lg font-semibold text-gray-900">Change Password</h2>

          <p class="mt-1 text-sm text-gray-500">
            Use a strong password that you do not use elsewhere.
          </p>
        </div>

        <form @submit.prevent="changePassword" class="space-y-5 p-6">
          <!-- General Error -->
          <div v-if="passwordError" class="rounded-lg bg-red-50 p-3 text-sm text-red-600">
            {{ passwordError }}
          </div>

          <!-- Current Password -->
          <div>
            <label for="current_password" class="mb-1 block text-sm font-medium text-gray-700">
              Current Password
            </label>

            <input
              id="current_password"
              v-model="passwordForm.current_password"
              type="password"
              required
              autocomplete="current-password"
              class="w-full rounded-lg border border-gray-300 px-3 py-2.5 outline-none transition focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
              :class="{
                'border-red-500': getFieldError(passwordErrors, 'current_password'),
              }"
            />

            <p
              v-if="getFieldError(passwordErrors, 'current_password')"
              class="mt-1 text-sm text-red-600"
            >
              {{ getFieldError(passwordErrors, 'current_password') }}
            </p>
          </div>

          <!-- New Password -->
          <div>
            <label for="new_password" class="mb-1 block text-sm font-medium text-gray-700">
              New Password
            </label>

            <input
              id="new_password"
              v-model="passwordForm.new_password"
              type="password"
              required
              minlength="8"
              autocomplete="new-password"
              class="w-full rounded-lg border border-gray-300 px-3 py-2.5 outline-none transition focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
              :class="{
                'border-red-500': getFieldError(passwordErrors, 'new_password'),
              }"
            />

            <p class="mt-1 text-xs text-gray-500">Password must be at least 8 characters.</p>

            <p
              v-if="getFieldError(passwordErrors, 'new_password')"
              class="mt-1 text-sm text-red-600"
            >
              {{ getFieldError(passwordErrors, 'new_password') }}
            </p>
          </div>

          <!-- Confirm Password -->
          <div>
            <label
              for="new_password_confirmation"
              class="mb-1 block text-sm font-medium text-gray-700"
            >
              Confirm New Password
            </label>

            <input
              id="new_password_confirmation"
              v-model="passwordForm.new_password_confirmation"
              type="password"
              required
              minlength="8"
              autocomplete="new-password"
              class="w-full rounded-lg border border-gray-300 px-3 py-2.5 outline-none transition focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
              :class="{
                'border-red-500': getFieldError(passwordErrors, 'new_password'),
              }"
            />
          </div>

          <!-- Change Password -->
          <div class="flex justify-end border-t pt-5">
            <button
              type="submit"
              :disabled="changingPassword"
              class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
            >
              {{ changingPassword ? 'Changing...' : 'Change Password' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
