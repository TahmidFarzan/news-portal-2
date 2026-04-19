<template>
    <Head title="Email verification" />
    <BRow :gutter-x="2" :gutter-y="2">
        <BCol md="6" class="d-flex align-items-center justify-content-center bg-info bg-opacity-25 p-4">
            <BImg src="/uploads/icons/auth/user-check.png" alt="User check" fluid class="w-75" />
        </BCol>

        <BCol md="12">
            <BCard class="shadow-sm">
                <h2 class="text-primary mb-2">Verify Your Email</h2>
                <p class="text-muted mb-2">
                    Before continuing, please check your email for a verification link.
                </p>

                <BForm @submit.prevent="handleResendVerification">
                    <BButton type="submit" variant="primary"
                        class="w-100 d-flex justify-content-center align-items-center" :disabled="resending" @click="handleResendVerification">
                        <b-spinner small class="me-2" v-if="resending" label="Sending..." />
                        {{ resending ? 'Resending...' : 'Resend Verification Email' }}
                    </BButton>
                </BForm>

            </Bcard>
        </BCol>
    </BRow>
</template>

<script setup>
import layout from '@/pages/layouts/AuthLayout.vue'

import { ref, onMounted, inject  } from 'vue'
import { Head , router as intertiaJsRoute} from '@inertiajs/vue3'

import { BCard, BCol, BForm } from 'bootstrap-vue-next'

defineOptions({ layout })

const pageReady = inject("pageReady")
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

onMounted(async () => {
    pageReady.value = true
})
</script>
