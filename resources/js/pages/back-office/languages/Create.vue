<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'

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

const { language } = defineProps({
    language: Object,
})

const isUpdate = computed(() => !!language?.slug)

const saveForm = useForm({
    name: language?.name || '',
    code: language?.code || '',
    brief: language?.brief || '',
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
                { text: 'Languages', href: route('back-office.languages.index') },
                { text: isUpdate.value ? `${language?.name} edit` : 'Language create', active: true }
            ],
        })
    )
})
</script>

<template>

    <Head :title="isUpdate ? `${language?.name} edit` : 'Language create'" />

    <div class="w-full">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6">

            <form @submit.prevent="handleSave" class="space-y-6">

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">Basic Information</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Name <span class="text-red-500">*</span>
                            </label>

                            <input v-model="saveForm.name" placeholder="Enter name"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.name ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.name" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Code <span class="text-red-500">*</span>
                            </label>

                            <input v-model="saveForm.code" type="text" placeholder="Enter code"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.code ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.code" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.code }}
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">
                                Brief
                            </label>

                            <textarea v-model="saveForm.brief" rows="4" placeholder="Enter brief"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none border-gray-300"></textarea>
                        </div>

                    </div>
                </div>

                <div class="flex justify-center">
                    <button type="submit" :disabled="saveForm.processing"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md flex items-center gap-2 transition">
                        <FontAwesomeIcon v-if="saveForm.processing" icon="spinner" spin />
                        <FontAwesomeIcon v-else icon="save" />
                        Save
                    </button>
                </div>

            </form>

        </div>
    </div>
</template>
