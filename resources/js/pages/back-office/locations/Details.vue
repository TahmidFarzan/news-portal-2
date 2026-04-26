<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import RecentActivities from '@/components/back-office/activity-log/RecentModelActivityLogs.vue'

import { ref, onMounted, nextTick, inject } from 'vue'
import { Head, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faTrash, faPen, faEye, faEyeSlash, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { formatDate, formatDateTime } from '@/composables/useDateTime'
import { canEditLocation, canDeleteLocation } from '@/composables/useAuthUserAccessPermissions'

FontAwesomeLibrary.add(faTrash, faPen, faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const pageReady = inject("pageReady")
const authUser = inject("authUser")

const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const { location } = defineProps({
    location: Object,
})

const canEdit = (location) => canEditLocation(authUser?.value, location)
const canDelete = (location) => canDeleteLocation(authUser?.value, location)

const handleDelete = () => {
    if (deleteProcessing.value) return
    deleteProcessing.value = true

    intertiaJsRoute.delete(route('back-office.locations.delete', { slug: location?.slug }), {
        onFinish: () => deleteProcessing.value = false
    })
}


onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: 'Dashboard', href: route('auth-user.dashboard.index') },
                { text: 'Locations', href: route('back-office.locations.index') },
                { text: `${location?.name} details`, active: true }
            ],
        })
    )

    pageReady.value = true
})
</script>

<template>

    <Head :title="`${location?.name} details`" />

    <div class="w-full space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">Location Details</h2>

            <div class="flex gap-2">
                <a v-if="canEdit(location)" :href="route('back-office.locations.edit', { slug: location?.slug })"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="pen" />
                    Edit
                </a>

                <button v-if="canDelete(location)" @click="showDeleteModal = true"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="trash" />
                    Delete
                </button>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">Basic Information</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Name</span>
                        <span class="font-medium">{{ location?.name || 'N/A' }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Language</span>
                        <span class="font-medium">{{ location?.language?.name || 'N/A' }}</span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Parent</span>
                        <span class="font-medium">{{ location?.parent?.name || 'N/A' }}</span>
                    </div>

                    <div>
                        <div class="text-gray-500 mb-1">Details</div>
                        <div class="text-gray-700">{{ location?.details || 'N/A' }}</div>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-3">
                    <div class="text-gray-500">Tree</div>

                    <div class="flex flex-wrap gap-2">
                        <span v-for="node in location?.bloodline || []" :key="node.id"
                            class="bg-blue-600 text-white text-xs px-3 py-1 rounded-md">
                            {{ node.name }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="text-gray-500 mb-2">Category</div>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Title</span>
                            <span class="font-medium">{{ location?.category?.name || 'N/A' }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-500">Parent</span>
                            <span class="font-medium">{{ location?.category?.parent?.name || 'N/A' }}</span>
                        </div>

                        <div>
                            <div class="text-gray-500 mb-1">Details</div>
                            <div class="text-gray-700">
                                {{ location?.category?.details || 'N/A' }}
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">System Information</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Created At</span>
                        <span class="font-medium">
                            {{ location?.created_at ? formatDateTime(location.created_at) : 'N/A' }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Created By</span>
                        <span class="font-medium">
                            {{ location?.created_by?.name || 'N/A' }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Updated At</span>
                        <span class="font-medium">
                            {{ location?.updated_at ? formatDateTime(location.updated_at) : 'N/A' }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Updated By</span>
                        <span class="font-medium">
                            {{ location?.latest_activity_log?.causer?.name || 'N/A' }}
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">Activity Logs</h3>
            <RecentActivities :model-slug="'location'" :model="location" />
        </div>

        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showDeleteModal"
                class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50">

                <Transition enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-4">
                    <div v-if="showDeleteModal" class="bg-white rounded-xl shadow-lg w-[380px] p-6 space-y-4">
                        <h3 class="text-lg font-semibold text-red-600">
                            Delete Location
                        </h3>

                        <p class="text-sm font-medium">
                            {{ location?.name }}
                        </p>

                        <p class="text-sm text-gray-500">
                            This action cannot be undone.
                        </p>

                        <div class="flex justify-end gap-2 pt-2">
                            <button @click="showDeleteModal = false"
                                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                Cancel
                            </button>

                            <button @click="handleDelete" :disabled="deleteProcessing"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2">
                                <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />
                                Delete
                            </button>
                        </div>
                    </div>
                </Transition>

            </div>
        </Transition>

    </div>
</template>
