<script setup>
import layout from '@/pages/layouts/PublicLayout.vue'

import { Head, useForm, usePage } from '@inertiajs/vue3'
import { useTranslate } from '@/composables/useTranslate'

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

const { t } = useTranslate()

const page = usePage()
const token = page.props.token
const email = page.props.email

const resetPasswordForm = useForm({
    email: email || '',
    password: '',
    password_confirmation: '',
    token: token || '',
})

function validateForm() {
    resetPasswordForm.clearErrors()

    let valid = true

    if (!resetPasswordForm.password || resetPasswordForm.password.trim() === '') {
        resetPasswordForm.setError('password', t('common.validation.passwordIsRequired'))
        valid = false
    }

    if (!resetPasswordForm.password_confirmation || resetPasswordForm.password_confirmation.trim() === '') {
        resetPasswordForm.setError('password_confirmation', t('common.validation.passwordConfirmationIsRequired'))
        valid = false
    } else if (resetPasswordForm.password !== resetPasswordForm.password_confirmation) {
        resetPasswordForm.setError('password_confirmation', t('common.validation.passwordConfirmationDoesNotMatch'))
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
        }
    })
}
</script>

<template>

    <Head :title="t('common.messages.resetPassword')" />

    <div class="auth-entry min-h-screen flex items-center justify-center bg-gray-100 px-4">
        <div class="auth-card w-full max-w-md bg-white shadow-md rounded-2xl p-6 border border-gray-200">

            <div class="text-center mb-6">
                <img :src="'/uploads/icons/auth/forgot-password.png'" :alt="t('common.messages.resetPassword')"
                    class="mx-auto mb-3 w-16 h-16 object-contain" />

                <h2 class="text-xl font-semibold text-blue-600">
                    {{ t('common.messages.resetPassword') }}
                </h2>

                <p class="text-sm text-gray-500">
                    {{ t('auth.resetPassword.description') }}
                </p>
            </div>

            <form @submit.prevent="handleResetPassword">

                <input v-model="resetPasswordForm.email" type="hidden" />
                <input v-model="resetPasswordForm.token" type="hidden" />

                <div class="mb-4 relative">
                    <input id="password" v-model="resetPasswordForm.password" :type="showPassword ? 'text' : 'password'"
                        :placeholder="t('common.placeholders.enterNewPassword')"
                        class="w-full px-3 py-2 border rounded-lg pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :class="resetPasswordForm.errors.password ? 'border-red-500' : 'border-gray-300'" />

                    <button type="button" @click="togglePasswordVisibility"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500"
                        :aria-label="showPassword ? t('common.messages.hidePassword') : t('common.messages.showPassword')">
                        <FontAwesomeIcon :icon="showPassword ? 'eye-slash' : 'eye'" />
                    </button>

                    <p v-if="resetPasswordForm.errors.password" class="text-sm text-red-500 mt-1">
                        {{ resetPasswordForm.errors.password }}
                    </p>
                </div>

                <div class="mb-5 relative">
                    <input id="password_confirmation" v-model="resetPasswordForm.password_confirmation"
                        :type="showConfirmPassword ? 'text' : 'password'"
                        :placeholder="t('common.placeholders.confirmPassword')"
                        class="w-full px-3 py-2 border rounded-lg pr-10 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :class="resetPasswordForm.errors.password_confirmation ? 'border-red-500' : 'border-gray-300'" />

                    <button type="button" @click="toggleConfirmPasswordVisibility"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500"
                        :aria-label="showConfirmPassword ? t('common.messages.hideConfirmPassword') : t('common.messages.showConfirmPassword')">
                        <FontAwesomeIcon :icon="showConfirmPassword ? 'eye-slash' : 'eye'" />
                    </button>

                    <p v-if="resetPasswordForm.errors.password_confirmation" class="text-sm text-red-500 mt-1">
                        {{ resetPasswordForm.errors.password_confirmation }}
                    </p>
                </div>

                <button type="submit" :disabled="resetPasswordForm.processing"
                    class="w-full flex items-center justify-center gap-2 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50">
                    <FontAwesomeIcon v-if="resetPasswordForm.processing" icon="spinner" spin />

                    {{
                        resetPasswordForm.processing
                            ? t('auth.resetPassword.resettingButton')
                            : t('common.messages.resetPassword')
                    }}
                </button>

            </form>

            <div class="mt-4 text-center text-sm">
                {{ t('common.messages.alreadyHaveAnAccount') }}

                <a :href="route('login')" class="text-blue-600 hover:underline ml-1">
                    {{ t('common.messages.backToLogin') }}
                </a>
            </div>

        </div>
    </div>
</template>

<style scoped>
.auth-entry {
    background: linear-gradient(135deg, #f8fafc 0%, #ffffff 56%, #dbeafe 140%);
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
