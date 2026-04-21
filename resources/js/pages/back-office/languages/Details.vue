<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import RecentActivities from '@/components/back-office/activity-log/RecentModelActivityLogs.vue'

import { ref, onMounted, nextTick, inject } from 'vue'
import { Head, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faTrash, faPen, faEye, faEyeSlash, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { formatDate, formatDateTime } from '@/composables/useDateTime'
import { canEditLanguage, canDeleteLanguage} from '@/composables/useAuthUserAccessPermissions'

FontAwesomeLibrary.add(faTrash, faPen, faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const pageReady = inject("pageReady")
const authUser = inject("authUser")

const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const { language } = defineProps({
    language: Object,
})

const canEdit = (language) => canEditLanguage(authUser?.value, language)
const canDelete = (language) => canDeleteLanguage(authUser?.value, language)

const handleDelete = () => {
    if (deleteProcessing.value) return
    deleteProcessing.value = true

    intertiaJsRoute.delete(route('back-office.languages.delete', { slug: language?.slug }), {
        onFinish: () => deleteProcessing.value = false
    })
}


onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: 'Dashboard', href: route('auth-user.dashboard.index') },
                { text: 'Languages', href: route('back-office.languages.index') },
                { text: `${language?.name} details`, active: true }
            ],
        })
    )

    pageReady.value = true
})
</script>

<template>

    <Head :title="`${language?.name} details`" />

    <div class="w-full space-y-6">

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6">
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">Basic Information</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div class="border border-gray-200 rounded-xl p-4 space-y-2 text-sm">
                    <div>
                        <span class="font-medium text-gray-600">Name:</span>
                        {{ language?.name || 'N/A' }}
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Code:</span>
                        {{ language?.code || 'N/A' }}
                    </div>
                </div>

                <div class="border border-gray-200 rounded-xl p-4">
                    <span class="font-medium text-gray-600">Details:</span>
                        {{ language?.details || 'N/A' }}
                </div>
            </div>

        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6">
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">System Information</h3>
            <div class="grid md:grid-cols-2 gap-4 text-sm">
                <div class="border border-gray-200 rounded-xl p-4 space-y-2">
                    <div>
                        <span class="font-medium text-gray-600">Created At:</span>
                        {{ language?.created_at ? formatDateTime(language.created_at) : 'N/A' }}
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Created By:</span>
                        {{ language?.created_by?.name || 'N/A'}}
                    </div>
                </div>

                <div class="border border-gray-200 rounded-xl p-4 space-y-2">
                    <div>
                        <span class="font-medium text-gray-600">Updated At:</span>
                        {{ language?.updated_at ? formatDateTime(language.updated_at) : 'N/A' }}
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Updated By:</span>
                        {{ language?.latest_activity_log?.causer?.name || 'N/A' }}
                    </div>
                </div>
            </div>

        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6">
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">Activity Logs</h3>
            <RecentActivities :model-slug="'language'" :model="language" />
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 flex justify-end gap-2">

            <a v-if="canEdit(language)" :href="route('back-office.languages.edit', { slug: language?.slug })"
                class="px-4 py-2 border border-blue-500 text-blue-600 rounded hover:bg-blue-50 flex items-center gap-2">
                <FontAwesomeIcon icon="pen" /> Edit
            </a>

            <button v-if="canDelete(language)" @click="showDeleteModal = true"
                class="px-4 py-2 border border-red-500 text-red-600 rounded hover:bg-red-50 flex items-center gap-2">
                <FontAwesomeIcon icon="trash" /> Delete
            </button>

        </div>

        <div v-if="showDeleteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">

            <div class="bg-white p-6 rounded-xl shadow-lg w-96">
                <div class="font-semibold mb-2">Delete Confirmation</div>
                <p class="mb-2">{{ language?.name }}</p>
                <p class="text-sm text-gray-600 mb-4">
                    This action cannot be undone.
                </p>

                <div class="flex justify-end gap-2">
                    <button @click="showDeleteModal = false" class="px-3 py-1 bg-gray-200 rounded">
                        Cancel
                    </button>
                    <button @click="handleDelete" :disabled="deleteProcessing" class="px-3 py-1 bg-red-500 text-white rounded flex items-center gap-1">
                        <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
