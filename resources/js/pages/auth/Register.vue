<script setup>
import layout from '@/pages/layouts/PublicLayout.vue'

import { onMounted, inject } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faEye, faEyeSlash, faSpinner } from '@fortawesome/free-solid-svg-icons'

import {
    showPassword,
    showConfirmPassword,
    togglePasswordVisibility,
    toggleConfirmPasswordVisibility,
} from '@/composables/usePassword'

FontAwesomeLibrary.add(faEye, faEyeSlash, faSpinner)

defineOptions({ layout })

const registerForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
})

function validateForm() {
    registerForm.clearErrors()
    let valid = true

    if (!registerForm.name || registerForm.name.trim() === '') {
        registerForm.setError('name', 'Name is required.')
        valid = false
    } else if (registerForm.name.length > 200) {
        registerForm.setError('name', 'Name must not exceed 200 characters.')
        valid = false
    }

    if (!registerForm.email || registerForm.email.trim() === '') {
        registerForm.setError('email', 'Email is required.')
        valid = false
    } else if (registerForm.email.length > 200) {
        registerForm.setError('email', 'Email must not exceed 200 characters.')
        valid = false
    }

    if (!registerForm.password || registerForm.password.trim() === '') {
        registerForm.setError('password', 'Password is required.')
        valid = false
    }

    return valid
}

function handleRegister() {
    if (registerForm.processing) return
    if (!validateForm()) return
    registerForm.post(route('register.submit'), {
        preserveScroll: true,
        onSuccess: () => {
            registerForm.reset()
            registerForm.clearErrors()
        },
        onError: (errors) => {
            registerForm.clearErrors()
            registerForm.setError(errors)
        },
        onFinish: () => {
            registerForm.processing = false
        }
    })
}
</script>

<template>

    <Head title="Register" />

    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-md bg-white shadow rounded-2xl p-6 border border-gray-200">

            <div class="text-center mb-6">
                <img src="/public/uploads/icons/auth/register.png" alt="Register"
                    class="mx-auto mb-3 w-16 h-16 object-contain" />
                <h2 class="text-xl font-semibold text-green-600">Create Account</h2>
                <p class="text-sm text-gray-500">Fill in your details to get started.</p>
            </div>

            <form @submit.prevent="handleRegister">

                <div class="mb-4">
                    <input id="name" v-model="registerForm.name" type="text" placeholder="Your full name" autofocus
                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                        :class="registerForm.errors.name ? 'border-red-500' : 'border-gray-300'" />
                    <p v-if="registerForm.errors.name" class="text-sm text-red-500 mt-1">
                        {{ registerForm.errors.name }}
                    </p>
                </div>

                <div class="mb-4">
                    <input id="email" v-model="registerForm.email" type="email" placeholder="example@email.com"
                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                        :class="registerForm.errors.email ? 'border-red-500' : 'border-gray-300'" />
                    <p v-if="registerForm.errors.email" class="text-sm text-red-500 mt-1">
                        {{ registerForm.errors.email }}
                    </p>
                </div>

                <div class="mb-4 relative">
                    <input id="password" :type="showPassword ? 'text' : 'password'" v-model="registerForm.password"
                        placeholder="Enter password"
                        class="w-full px-3 py-2 border rounded-lg pr-10 focus:outline-none focus:ring-2 focus:ring-green-500"
                        :class="registerForm.errors.password ? 'border-red-500' : 'border-gray-300'" />
                    <button type="button" @click="togglePasswordVisibility"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500">
                        <FontAwesomeIcon :icon="showPassword ? 'eye-slash' : 'eye'" />
                    </button>
                    <p v-if="registerForm.errors.password" class="text-sm text-red-500 mt-1">
                        {{ registerForm.errors.password }}
                    </p>
                </div>

                <div class="mb-5 relative">
                    <input id="passwordConfirmation" :type="showConfirmPassword ? 'text' : 'password'"
                        v-model="registerForm.password_confirmation" placeholder="Confirm password"
                        class="w-full px-3 py-2 border rounded-lg pr-10 focus:outline-none focus:ring-2 focus:ring-green-500"
                        :class="registerForm.errors.password_confirmation ? 'border-red-500' : 'border-gray-300'" />
                    <button type="button" @click="toggleConfirmPasswordVisibility"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500">
                        <FontAwesomeIcon :icon="showConfirmPassword ? 'eye-slash' : 'eye'" />
                    </button>
                    <p v-if="registerForm.errors.password_confirmation" class="text-sm text-red-500 mt-1">
                        {{ registerForm.errors.password_confirmation }}
                    </p>
                </div>

                <button type="submit" :disabled="registerForm.processing"
                    class="w-full flex items-center justify-center gap-2 bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 disabled:opacity-50">
                    <FontAwesomeIcon v-if="registerForm.processing" icon="spinner" spin />
                    {{ registerForm.processing ? 'Registering...' : 'Register' }}
                </button>

            </form>

            <div class="mt-4 text-center text-sm">
                Already have an account?
                <a :href="route('login')" class="text-blue-600 hover:underline ml-1">
                    Log in here
                </a>
            </div>

        </div>
    </div>
</template>
