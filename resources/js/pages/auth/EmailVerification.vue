<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'

import { ref, inject } from 'vue'
import { Head, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library } from '@fortawesome/fontawesome-svg-core'
import { faSpinner } from '@fortawesome/free-solid-svg-icons'

library.add(faSpinner)

defineOptions({ layout: Layout })

const resending = ref(false)

function handleResendVerification() {
    if (resending.value) return
    resending.value = true

    intertiaJsRoute.post(route('email-verify.resend'), {}, {
        onFinish: () => {
            resending.value = false
        }
    })
}

</script>

<template>

    <Head title="Email verification" />

    <div class="w-full min-h-[70vh] flex flex-col md:flex-row items-center justify-center gap-6 p-4">

        <div class="hidden md:flex items-center justify-center bg-blue-100 rounded-2xl p-6 w-full md:w-1/2">
            <img src="/public/uploads/icons/auth/user-check.png" alt="User check" class="w-3/4 max-w-xs object-contain" />
        </div>

        <div class="w-full md:w-1/2">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 text-center">

                <h2 class="text-xl font-semibold text-blue-600 mb-2">
                    Verify Your Email
                </h2>

                <p class="text-gray-600 text-sm mb-4">
                    Before continuing, please check your email for a verification link.
                </p>

                <form @submit.prevent="handleResendVerification">

                    <button type="submit" :disabled="resending"
                        class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white px-4 py-2 rounded flex items-center justify-center gap-2">
                        <FontAwesomeIcon v-if="resending" icon="spinner" spin />
                        {{ resending ? 'Resending...' : 'Resend Verification Email' }}
                    </button>

                </form>

            </div>
        </div>

    </div>
</template>
