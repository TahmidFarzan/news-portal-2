<script setup>
import layout from '@/pages/layouts/PublicLayout.vue'

import { onMounted, inject } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeIconLibrary } from '@fortawesome/fontawesome-svg-core'
import { faEye, faEyeSlash, faSpinner } from '@fortawesome/free-solid-svg-icons'

import {
    showPassword,
    showConfirmPassword,
    togglePasswordVisibility,
    toggleConfirmPasswordVisibility,
} from '@/composables/usePassword'

FontAwesomeIconLibrary.add(faEye, faEyeSlash, faSpinner)

defineOptions({ layout })

const pageReady = inject("pageReady")

const page = usePage()
const token = page.props.value.token
const email = page.props.value.email

const resetPasswordForm = useForm({
    email: email || '',
    password: '',
    password_confirmation: '',
    token: token,
})

function validateForm() {
    resetPasswordForm.clearErrors()
    let valid = true

    if (!resetPasswordForm.password || resetPasswordForm.password.trim() === '') {
        resetPasswordForm.setError('password', 'Password is required.')
        valid = false
    }

    if (!resetPasswordForm.password_confirmation || resetPasswordForm.password_confirmation.trim() === '') {
        resetPasswordForm.setError('password_confirmation', 'Password confirmation is required.')
        valid = false
    }

    return valid
}

function handleResetPassword() {
    if (resetPasswordForm.processing) return
    if (!validateForm()) return
    resetPasswordForm.post(route('password.reset.submit', { email, token }), {
        preserveScroll: true,
        onSuccess: () => {
            resetPasswordForm.reset()
            resetPasswordForm.clearErrors()
        },
        onError: (errors) => {
            resetPasswordForm.clearErrors()
            resetPasswordForm.setError(errors)
        },
        onFinish: () => {
            resetPasswordForm.processing = false
        }
    })
}

onMounted(() => {
    pageReady.value = true
})
</script>

<template>

    <Head title="Reset Password" />

    <div class="min-h-screen flex items-center justify-center bg-gray-100 px-4">
        <div class="w-full max-w-md bg-white shadow-md rounded-2xl p-6 border border-gray-200">

            <div class="text-center mb-6">
                <img src="/public/uploads/icons/auth/forgot-password.png" alt="Reset Password"
                    class="mx-auto mb-3 w-16 h-16 object-contain" />
                <h2 class="text-xl font-semibold text-blue-600">Reset Password</h2>
                <p class="text-sm text-gray-500">Enter a new password to reset your account.</p>
            </div>

            <form @submit.prevent="handleResetPassword">

                <input type="hidden" v-model="resetPasswordForm.email" />
                <input type="hidden" v-model="resetPasswordForm.token" />

                <div class="mb-4 relative">
                    <input id="password" :type="showPassword ? 'text' : 'password'" v-model="resetPasswordForm.password"
                        placeholder="Enter new password"
                        class="w-full px-3 py-2 border rounded-lg pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :class="resetPasswordForm.errors.password ? 'border-red-500' : 'border-gray-300'" />
                    <button type="button" @click="togglePasswordVisibility"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500">
                        <FontAwesomeIcon :icon="showPassword ? 'eye-slash' : 'eye'" />
                    </button>
                    <p v-if="resetPasswordForm.errors.password" class="text-sm text-red-500 mt-1">
                        {{ resetPasswordForm.errors.password }}
                    </p>
                </div>

                <div class="mb-5 relative">
                    <input id="password_confirmation" :type="showConfirmPassword ? 'text' : 'password'"
                        v-model="resetPasswordForm.password_confirmation" placeholder="Confirm password"
                        class="w-full px-3 py-2 border rounded-lg pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :class="resetPasswordForm.errors.password_confirmation ? 'border-red-500' : 'border-gray-300'" />
                    <button type="button" @click="toggleConfirmPasswordVisibility"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500">
                        <FontAwesomeIcon :icon="showConfirmPassword ? 'eye-slash' : 'eye'" />
                    </button>
                    <p v-if="resetPasswordForm.errors.password_confirmation" class="text-sm text-red-500 mt-1">
                        {{ resetPasswordForm.errors.password_confirmation }}
                    </p>
                </div>

                <button type="submit" :disabled="resetPasswordForm.processing"
                    class="w-full flex items-center justify-center gap-2 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50">
                    <FontAwesomeIcon v-if="resetPasswordForm.processing" icon="spinner" spin />
                    {{ resetPasswordForm.processing ? 'Resetting...' : 'Reset Password' }}
                </button>

            </form>

            <div class="mt-4 text-center text-sm">
                Already have an account?
                <a :href="route('login')" class="text-blue-600 hover:underline ml-1">
                    Back to login
                </a>
            </div>

        </div>
    </div>
</template>
