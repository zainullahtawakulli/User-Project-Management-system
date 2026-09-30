<script setup>
defineOptions({ name: 'RegisterView' })

import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const auth = useAuthStore()

const route = useRouter()

const form = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
})

const errors = ref({})

const register = async () => {
    errors.value = {}

    // Frontend validation
    if (!form.value.name) {
        errors.value.name = 'Name is required.'
    }

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

    if (!form.value.password_confirmation) {
        errors.value.password_confirmation = 'Please confirm your password.'
    } else if (
        form.value.password !== form.value.password_confirmation
    ) {
        errors.value.password_confirmation = 'Passwords do not match.'
    }

    // Stop if frontend validation has errors
    if (Object.keys(errors.value).length > 0) {
        return
    }

    try {
        await auth.register(form.value)

        form.value = {
            name : '',
            email : '' ,
            password: '',
            password_confirmation: ''
        }
        route.push('/login')



        console.log('Registered successfully')
        console.log('User:', auth.user)
        console.log('Token:', auth.token)

    } catch {
        // Laravel validation errors
        errors.value = auth.errors
    }
}
</script>

<template>
    <div class="min-h-screen bg-gray-100 flex items-center justify-center px-4">

        <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8">

            <!-- Heading -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">
                    Create Account
                </h1>

                <p class="text-gray-500 mt-2">
                    Register to get started
                </p>
            </div>

            <form @submit.prevent="register" class="space-y-5">

                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Name
                    </label>

                    <input
                        v-model="form.name"
                        type="text"
                        placeholder="Enter your name"
                        class="w-full px-4 py-3 border rounded-lg outline-none transition
                               focus:ring-2 focus:ring-blue-500
                               focus:border-blue-500"
                        :class="{
                            'border-red-500': errors.name
                        }"
                    />

                    <p
                        v-if="errors.name"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ Array.isArray(errors.name) ? errors.name[0] : errors.name }}
                    </p>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Email
                    </label>

                    <input
                        v-model="form.email"
                        type="email"
                        placeholder="Enter your email"
                        class="w-full px-4 py-3 border rounded-lg outline-none transition
                               focus:ring-2 focus:ring-blue-500
                               focus:border-blue-500"
                        :class="{
                            'border-red-500': errors.email
                        }"
                    />

                    <p
                        v-if="errors.email"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ Array.isArray(errors.email) ? errors.email[0] : errors.email }}
                    </p>
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Password
                    </label>

                    <input
                        v-model="form.password"
                        type="password"
                        placeholder="Enter your password"
                        class="w-full px-4 py-3 border rounded-lg outline-none transition
                               focus:ring-2 focus:ring-blue-500
                               focus:border-blue-500"
                        :class="{
                            'border-red-500': errors.password
                        }"
                    />

                    <p
                        v-if="errors.password"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ Array.isArray(errors.password) ? errors.password[0] : errors.password }}
                    </p>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Confirm Password
                    </label>

                    <input
                        v-model="form.password_confirmation"
                        type="password"
                        placeholder="Confirm your password"
                        class="w-full px-4 py-3 border rounded-lg outline-none transition
                               focus:ring-2 focus:ring-blue-500
                               focus:border-blue-500"
                        :class="{
                            'border-red-500': errors.password_confirmation
                        }"
                    />

                    <p
                        v-if="errors.password_confirmation"
                        class="text-red-500 text-sm mt-1"
                    >
                        {{ Array.isArray(errors.password_confirmation)
                            ? errors.password_confirmation[0]
                            : errors.password_confirmation
                        }}
                    </p>
                </div>

                <!-- Register Button -->
                <button
                    type="submit"
                    :disabled="auth.loading"
                    class="w-full bg-blue-600 text-white py-3 rounded-lg
                           font-semibold transition
                           hover:bg-blue-700
                           disabled:bg-blue-400
                           disabled:cursor-not-allowed"
                >
                    {{ auth.loading ? 'Creating Account...' : 'Register' }}
                </button>

            </form>
            <p class="text-center text-gray-500 text-sm mt-6">
                Do you already have account?

                <RouterLink
                    to="login"
                    class="text-blue-600 font-semibold hover:text-blue-700"
                >
                    Login
                </RouterLink>
            </p>

            <!-- Success message -->
            <p
                v-if="auth.user"
                class="mt-5 text-center text-green-600 font-medium"
            >
                Registration successful!
            </p>

        </div>

    </div>
</template>