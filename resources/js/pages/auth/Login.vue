<script setup>
import layout from '@/pages/layouts/PublicLayout.vue'

import { Head, useForm } from '@inertiajs/vue3'
import { useTranslate } from '@/composables/useTranslate'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faEye, faEyeSlash, faSpinner } from '@fortawesome/free-solid-svg-icons'

import {
    showPassword,
    togglePasswordVisibility,
} from '@/composables/usePassword'

FontAwesomeLibrary.add(faEye, faEyeSlash, faSpinner)

defineOptions({ layout })

const { t } = useTranslate()

const appEnv = import.meta.env.VITE_APP_ENV

const loginForm = useForm({
    email: '',
    password: '',
    remember: false,
})

function validateForm() {
    loginForm.clearErrors()

    let valid = true

    if (!loginForm.email || loginForm.email.trim() === '') {
        loginForm.setError('email', t('form.validation_errors.email_is_required'))
        valid = false
    } else if (loginForm.email.length > 200) {
        loginForm.setError('email', t('form.validation_errors.email_must_not_exceed_200_characters'))
        valid = false
    }

    if (!loginForm.password || loginForm.password.trim() === '') {
        loginForm.setError('password', t('form.validation_errors.password_is_required'))
        valid = false
    }

    return valid
}

function handleLogin() {
    if (loginForm.processing) return
    if (!validateForm()) return

    loginForm.post(route('login.submit'), {
        preserveScroll: true,

        onSuccess: () => {
            loginForm.reset()
            loginForm.clearErrors()
        },

        onError: (errors) => {
            loginForm.clearErrors()
            loginForm.setError(errors)
        }
    })
}
</script>

<template>

    <Head :title="t('auth.login.page_title')" />

    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-md bg-white shadow rounded-2xl p-6 border border-gray-200">

            <div class="text-center mb-6">
                <img src="/uploads/icons/auth/login.png" :alt="t('auth.login.image_alt')"
                    class="mx-auto mb-3 w-16 h-16 object-contain" />

                <h2 class="text-xl font-semibold text-blue-600">
                    {{ t('auth.login.title') }}
                </h2>

                <p class="text-sm text-gray-500">
                    {{ t('auth.login.description') }}
                </p>
            </div>

            <form @submit.prevent="handleLogin">

                <div class="mb-4">
                    <input id="email" v-model="loginForm.email" type="email"
                        :placeholder="t('auth.login.email_placeholder')" autofocus
                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :class="loginForm.errors.email ? 'border-red-500' : 'border-gray-300'" />

                    <p v-if="loginForm.errors.email" class="text-sm text-red-500 mt-1">
                        {{ loginForm.errors.email }}
                    </p>
                </div>

                <div class="mb-4 relative">
                    <input id="password" v-model="loginForm.password" :type="showPassword ? 'text' : 'password'"
                        :placeholder="t('auth.login.password_placeholder')"
                        class="w-full px-3 py-2 border rounded-lg pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :class="loginForm.errors.password ? 'border-red-500' : 'border-gray-300'" />

                    <button type="button" @click="togglePasswordVisibility"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500"
                        :aria-label="showPassword ? t('auth.login.hide_password') : t('auth.login.show_password')">
                        <FontAwesomeIcon :icon="showPassword ? 'eye-slash' : 'eye'" />
                    </button>

                    <p v-if="loginForm.errors.password" class="text-sm text-red-500 mt-1">
                        {{ loginForm.errors.password }}
                    </p>
                </div>

                <div class="mb-4 flex items-center">
                    <input id="remember" v-model="loginForm.remember" type="checkbox" class="mr-2" />

                    <label for="remember" class="text-sm text-gray-600">
                        {{ t('auth.login.remember_me') }}
                    </label>
                </div>

                <button type="submit" :disabled="loginForm.processing"
                    class="w-full flex items-center justify-center gap-2 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50">
                    <FontAwesomeIcon v-if="loginForm.processing" icon="spinner" spin />

                    {{
                        loginForm.processing
                            ? t('auth.login.logging_in_button')
                            : t('auth.login.login_button')
                    }}
                </button>

            </form>

            <div class="flex justify-between mt-4 text-sm">
                <a v-if="appEnv == 'local'" :href="route('register')" class="text-blue-600 hover:underline">
                    {{ t('auth.login.create_account') }}
                </a>

                <a :href="route('forgot-password')" class="text-blue-600 hover:underline">
                    {{ t('auth.login.forgot_password') }}
                </a>
            </div>

        </div>
    </div>
</template>
