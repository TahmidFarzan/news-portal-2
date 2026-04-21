<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import MultiSelectInfinityLoadingApi from '@/components/common/multi-select/InfinityLoadingApi.vue'

import { computed, onMounted, nextTick, inject } from 'vue'
import { Head, useForm, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faSave, faEye, faEyeSlash, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { VueTelInput } from 'vue-tel-input'

import { formatDate } from '@/composables/useDateTime'
import {
    showPassword,
    showConfirmPassword,
    togglePasswordVisibility,
    toggleConfirmPasswordVisibility,
} from '@/composables/usePassword'

import 'vue-tel-input/vue-tel-input.css'

FontAwesomeLibrary.add(faSave, faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const pageReady = inject("pageReady")

const { language } = defineProps({
    language: Object,
})

const isUpdate = computed(() => !!language?.slug)

const saveForm = useForm({
    name: language?.name || '',
    code: language?.code || '',
    details: language?.details || '',
})

function validateForm() {
    saveForm.clearErrors()
    let valid = true

    if (!saveForm.name) {
        saveForm.setError('name', 'Name is required.')
        valid = false
    }

    if (!saveForm.code) {
        saveForm.setError('code', 'Code is required.')
        valid = false
    }

    return valid
}


function handleSave() {
    if (saveForm.processing) return

    if (!validateForm()) return

    saveForm.processing = true

    const requestConfig = {
        preserveScroll: true,
        preserveState: true,
        forceFormData: true,
        onSuccess: () => {
            saveForm.reset()
            saveForm.clearErrors()
        },
        onError: (errors) => {
            saveForm.clearErrors()
            saveForm.setError(errors)
        },
        onFinish: () => {
            saveForm.processing = false
        }
    }

    if (isUpdate.value) {
        intertiaJsRoute.post(
            route('back-office.languages.update', { slug: language?.slug }),
            { ...saveForm.data(), _method: 'patch' },
            requestConfig
        )
    } else {
        saveForm.post(route('back-office.languages.save'), requestConfig)
    }
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: 'Dashboard', href: route('auth-user.dashboard.index') },
                { text: 'Languages', href: route('back-office.languages.index') },
                { text: isUpdate.value ? `${language?.name} edit` : 'Language create', active: true }
            ],
        })
    )

    pageReady.value = true
})
</script>

<template>

    <Head :title="isUpdate ? `${language?.name} edit` : 'Language create'" />

    <div class="w-full">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6">

            <form @submit.prevent="handleSave" class="space-y-4">

                <div class="grid md:grid-cols-2 gap-4">

                    <div>
                        <label class="block text-sm font-medium mb-1">Name *</label>
                        <input v-model="saveForm.name" class="border rounded px-3 py-2 w-full"
                            :class="saveForm.errors.name ? 'border-red-500' : 'border-gray-300'" />
                        <p v-if="saveForm.errors.name" class="text-red-500 text-sm">{{ saveForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Code *</label>
                        <input v-model="saveForm.code" type="text" class="border rounded px-3 py-2 w-full"
                            :class="saveForm.errors.code ? 'border-red-500' : 'border-gray-300'" />
                        <p v-if="saveForm.errors.code" class="text-red-500 text-sm">{{ saveForm.errors.code }}</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1">Details</label>
                        <textarea v-model="saveForm.details" rows="3"
                            class="border rounded px-3 py-2 w-full resize-none"></textarea>
                    </div>

                    <div class="text-center pt-2">
                        <button type="submit" :disabled="saveForm.processing"
                            class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded flex items-center gap-2 mx-auto">
                            <FontAwesomeIcon v-if="saveForm.processing" icon="spinner" spin />
                            <FontAwesomeIcon v-else icon="save" />
                            Save
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>
</template>
