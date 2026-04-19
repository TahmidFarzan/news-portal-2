<script setup>
import layout from '@/pages/layouts/PublicLayout.vue'

import { inject, onMounted } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faSpinner } from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faSpinner)

defineOptions({ layout })

const pageReady = inject("pageReady")

const resetRequestForm = useForm({
    email: '',
})

function validateForm() {
    resetRequestForm.clearErrors()
    let valid = true

    if (!resetRequestForm.email || resetRequestForm.email.trim() === '') {
        resetRequestForm.setError('email', 'Email is required.')
        valid = false
    } else if (resetRequestForm.email.length > 200) {
        resetRequestForm.setError('email', 'Email must not exceed 200 characters.')
        valid = false
    }

    return valid
}

function handleForgotPassword() {
    if (resetRequestForm.processing) return
    if (!validateForm()) return
    resetRequestForm.post(route('forgot-password.submit'), {
        preserveScroll: true,
        onSuccess: () => {
            resetRequestForm.reset()
            resetRequestForm.clearErrors()
        },
        onError: (errors) => {
            resetRequestForm.clearErrors()
            resetRequestForm.setError(errors)
        },
        onFinish: () => {
            resetRequestForm.processing = false
        }
    })
}

onMounted(() => {
    pageReady.value = true
})
</script>

<template>

    <Head title="Forget password" />

    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-md bg-white shadow rounded-2xl p-6 border border-gray-200">

            <div class="text-center mb-6">
                <img src="/public/uploads/icons/auth/forgot-password.png" alt="Forgot Password"
                    class="mx-auto mb-3 w-16 h-16 object-contain" />
                <h2 class="text-xl font-semibold text-blue-600">Forgot Password</h2>
                <p class="text-sm text-gray-500">Enter your email to receive a reset link.</p>
            </div>

            <form @submit.prevent="handleForgotPassword">

                <div class="mb-4">
                    <input id="email" v-model="resetRequestForm.email" type="email" placeholder="example@email.com"
                        autofocus
                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :class="resetRequestForm.errors.email ? 'border-red-500' : 'border-gray-300'" />
                    <p v-if="resetRequestForm.errors.email" class="text-sm text-red-500 mt-1">
                        {{ resetRequestForm.errors.email }}
                    </p>
                </div>

                <button type="submit" :disabled="resetRequestForm.processing"
                    class="w-full flex items-center justify-center gap-2 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50">
                    <FontAwesomeIcon v-if="resetRequestForm.processing" icon="spinner" spin />
                    {{ resetRequestForm.processing ? 'Sending...' : 'Send Reset Link' }}
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
