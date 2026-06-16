<script setup>
import layout from '@/pages/layouts/PublicLayout.vue'

import { Head, useForm } from '@inertiajs/vue3'
import { useTranslate } from '@/composables/useTranslate'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faSpinner } from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faSpinner)

defineOptions({ layout })

const { t } = useTranslate()

const resetRequestForm = useForm({
    email: '',
})

function validateForm() {
    resetRequestForm.clearErrors()

    let valid = true

    if (!resetRequestForm.email || resetRequestForm.email.trim() === '') {
        resetRequestForm.setError('email', t('form.validation_errors.email_is_required'))
        valid = false
    } else if (resetRequestForm.email.length > 200) {
        resetRequestForm.setError('email', t('form.validation_errors.email_must_not_exceed_200_characters'))
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
        }
    })
}
</script>

<template>

    <Head :title="t('auth.forgot_password.page_title')" />

    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-md bg-white shadow rounded-2xl p-6 border border-gray-200">

            <div class="text-center mb-6">
                <img src="/uploads/icons/auth/forgot-password.png" :alt="t('auth.forgot_password.image_alt')"
                    class="mx-auto mb-3 w-16 h-16 object-contain" />

                <h2 class="text-xl font-semibold text-blue-600">
                    {{ t('auth.forgot_password.title') }}
                </h2>

                <p class="text-sm text-gray-500">
                    {{ t('auth.forgot_password.description') }}
                </p>
            </div>

            <form @submit.prevent="handleForgotPassword">

                <div class="mb-4">
                    <input id="email" v-model="resetRequestForm.email" type="email"
                        :placeholder="t('auth.forgot_password.email_placeholder')" autofocus
                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :class="resetRequestForm.errors.email ? 'border-red-500' : 'border-gray-300'" />

                    <p v-if="resetRequestForm.errors.email" class="text-sm text-red-500 mt-1">
                        {{ resetRequestForm.errors.email }}
                    </p>
                </div>

                <button type="submit" :disabled="resetRequestForm.processing"
                    class="w-full flex items-center justify-center gap-2 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50">
                    <FontAwesomeIcon v-if="resetRequestForm.processing" icon="spinner" spin />

                    {{
                        resetRequestForm.processing
                            ? t('auth.forgot_password.sending_button')
                            : t('auth.forgot_password.send_reset_link_button')
                    }}
                </button>

            </form>

            <div class="mt-4 text-center text-sm">
                {{ t('auth.forgot_password.already_have_account') }}

                <a :href="route('login')" class="text-blue-600 hover:underline ml-1">
                    {{ t('auth.forgot_password.back_to_login') }}
                </a>
            </div>

        </div>
    </div>
</template>
