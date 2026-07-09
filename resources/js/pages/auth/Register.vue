<script setup>
import layout from '@/pages/layouts/PublicLayout.vue'

import { Head, useForm } from '@inertiajs/vue3'
import { useTranslate } from '@/composables/useTranslate'

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

const { t } = useTranslate()

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
        registerForm.setError('name', t('common.validation.nameIsRequired'))
        valid = false
    } else if (registerForm.name.length > 200) {
        registerForm.setError('name', t('auth.register.validation.nameMustNotExceed200Characters'))
        valid = false
    }

    if (!registerForm.email || registerForm.email.trim() === '') {
        registerForm.setError('email', t('common.validation.emailIsRequired'))
        valid = false
    } else if (registerForm.email.length > 200) {
        registerForm.setError('email', t('common.validation.emailMustNotExceed200Characters'))
        valid = false
    }

    if (!registerForm.password || registerForm.password.trim() === '') {
        registerForm.setError('password', t('common.validation.passwordIsRequired'))
        valid = false
    }

    if (!registerForm.password_confirmation || registerForm.password_confirmation.trim() === '') {
        registerForm.setError('password_confirmation', t('common.validation.passwordConfirmationIsRequired'))
        valid = false
    } else if (registerForm.password !== registerForm.password_confirmation) {
        registerForm.setError('password_confirmation', t('common.validation.passwordConfirmationDoesNotMatch'))
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
        }
    })
}
</script>

<template>

    <Head :title="t('common.messages.register')" />

    <div class="auth-entry min-h-screen flex items-center justify-center px-4">
        <div class="auth-card w-full max-w-md bg-white shadow rounded-2xl p-6 border border-gray-200">

            <div class="text-center mb-6">
                <img :src="'/uploads/icons/auth/register.png'" :alt="t('common.messages.register')"
                    class="mx-auto mb-3 w-16 h-16 object-contain" />

                <h2 class="text-xl font-semibold text-green-600">
                    {{ t('auth.register.title') }}
                </h2>

                <p class="text-sm text-gray-500">
                    {{ t('auth.register.description') }}
                </p>
            </div>

            <form @submit.prevent="handleRegister">

                <div class="mb-4">
                    <input id="name" v-model="registerForm.name" type="text"
                        :placeholder="t('auth.register.namePlaceholder')" autofocus
                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                        :class="registerForm.errors.name ? 'border-red-500' : 'border-gray-300'" />

                    <p v-if="registerForm.errors.name" class="text-sm text-red-500 mt-1">
                        {{ registerForm.errors.name }}
                    </p>
                </div>

                <div class="mb-4">
                    <input id="email" v-model="registerForm.email" type="email"
                        :placeholder="t('common.placeholders.exampleEmailCom')"
                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                        :class="registerForm.errors.email ? 'border-red-500' : 'border-gray-300'" />

                    <p v-if="registerForm.errors.email" class="text-sm text-red-500 mt-1">
                        {{ registerForm.errors.email }}
                    </p>
                </div>

                <div class="mb-4 relative">
                    <input id="password" v-model="registerForm.password" :type="showPassword ? 'text' : 'password'"
                        :placeholder="t('auth.register.passwordPlaceholder')"
                        class="w-full px-3 py-2 border rounded-lg pr-10 focus:outline-none focus:ring-2 focus:ring-green-500"
                        :class="registerForm.errors.password ? 'border-red-500' : 'border-gray-300'" />

                    <button type="button" @click="togglePasswordVisibility"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500"
                        :aria-label="showPassword ? t('common.messages.hidePassword') : t('common.messages.showPassword')">
                        <FontAwesomeIcon :icon="showPassword ? 'eye-slash' : 'eye'" />
                    </button>

                    <p v-if="registerForm.errors.password" class="text-sm text-red-500 mt-1">
                        {{ registerForm.errors.password }}
                    </p>
                </div>

                <div class="mb-5 relative">
                    <input id="passwordConfirmation" v-model="registerForm.password_confirmation"
                        :type="showConfirmPassword ? 'text' : 'password'"
                        :placeholder="t('common.placeholders.confirmPassword')"
                        class="w-full px-3 py-2 border rounded-lg pr-10 focus:outline-none focus:ring-2 focus:ring-green-500"
                        :class="registerForm.errors.password_confirmation ? 'border-red-500' : 'border-gray-300'" />

                    <button type="button" @click="toggleConfirmPasswordVisibility"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500"
                        :aria-label="showConfirmPassword ? t('common.messages.hideConfirmPassword') : t('common.messages.showConfirmPassword')">
                        <FontAwesomeIcon :icon="showConfirmPassword ? 'eye-slash' : 'eye'" />
                    </button>

                    <p v-if="registerForm.errors.password_confirmation" class="text-sm text-red-500 mt-1">
                        {{ registerForm.errors.password_confirmation }}
                    </p>
                </div>

                <button type="submit" :disabled="registerForm.processing"
                    class="w-full flex items-center justify-center gap-2 bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 disabled:opacity-50">
                    <FontAwesomeIcon v-if="registerForm.processing" icon="spinner" spin />

                    {{
                        registerForm.processing
                            ? t('auth.register.registeringButton')
                            : t('common.messages.register')
                    }}
                </button>

            </form>

            <div class="mt-4 text-center text-sm">
                {{ t('common.messages.alreadyHaveAnAccount') }}

                <a :href="route('login')" class="text-blue-600 hover:underline ml-1">
                    {{ t('auth.register.loginHere') }}
                </a>
            </div>

        </div>
    </div>
</template>

<style scoped>
.auth-entry {
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 56%, #dcfce7 140%);
}

.auth-card {
    border-color: var(--news-border);
    box-shadow: var(--news-shadow);
}

.auth-card input {
    min-height: 2.75rem;
    border-radius: var(--news-radius-sm);
}
</style>
