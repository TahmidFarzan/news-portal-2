<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import ModelPropertieAttributes from '@/components/back-office/activity-log/ModelPropertieAttributes.vue'

import { ref, onMounted, computed, nextTick, inject } from 'vue'
import { Head, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faTrash, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { titleFormat } from '@/composables/useUtil'
import { formatDateTime } from '@/composables/useDateTime'

FontAwesomeLibrary.add(faTrash, faSpinner)

defineOptions({ layout: Layout })

const { activityLog } = defineProps({
    activityLog: Object,
})

const deleting = ref(false)
const showDeleteModal = ref(false)
const pageReady = inject('pageReady')

const parseJson = (value) => {
    try {
        return JSON.parse(value ?? '{}')
    } catch {
        return {}
    }
}

function handleDelete() {
    if (deleting.value) return

    deleting.value = true

    intertiaJsRoute.delete(route('back-office.activity-logs.delete', { slug: activityLog?.slug }), {
        onFinish: () => {
            deleting.value = false
            showDeleteModal.value = false
        },
    })
}

onMounted(async () => {
    await nextTick()
    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: 'Dashboard', href: route('auth-user.dashboard.index') },
                { text: 'Activity logs', href: route('back-office.activity-logs.index') },
                { text: 'Activity log details', active: true },
            ],
        })
    )

    pageReady.value = true
})
</script>

<template>

    <Head title="Activity log details" />

    <div class="w-full space-y-6">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6">
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">Basic information</h3>

            <div class="grid md:grid-cols-2 gap-3 text-sm">
                <div><span class="font-medium text-gray-600">Log name:</span> <span class="ml-1">{{
                    activityLog?.log_name || 'N/A' }}</span></div>
                <div><span class="font-medium text-gray-600">Description:</span> <span class="ml-1">{{
                    activityLog?.description || 'N/A' }}</span></div>
                <div><span class="font-medium text-gray-600">Causer:</span> <span class="ml-1">{{
                    activityLog?.causer?.name || 'System' }}</span></div>
                <div><span class="font-medium text-gray-600">Batch uuid:</span> <span class="ml-1">{{
                    activityLog?.batch_uuid || 'N/A' }}</span></div>
            </div>
        </div>


        <div v-if="Object.keys(activityLog?.properties).length"
            class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6">

            <div class="grid md:grid-cols-2 gap-4">

                <div v-for="(property, propertyIndex) in activityLog?.properties" :key="propertyIndex"
                    class="border rounded-xl p-3 bg-gray-50">

                    <h4 class="text-center font-medium mb-2 border-b pb-1">
                        {{ titleFormat(propertyIndex) }}
                    </h4>

                    <ModelPropertieAttributes :property="property" />

                </div>

            </div>
        </div>

        <div v-if="Object.keys(activityLog?.attribute_changes).length"
            class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6">

            <div class="grid md:grid-cols-2 gap-4">

                <div v-for="(property, propertyIndex) in activityLog?.attribute_changes" :key="propertyIndex"
                    class="border rounded-xl p-3 bg-gray-50">

                    <h4 class="text-center font-medium mb-2 border-b pb-1">
                        {{ titleFormat(propertyIndex) }}
                    </h4>

                    <ModelPropertieAttributes :property="property" />

                </div>

            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6">
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">System information</h3>
            <div class="text-sm">
                <span class="font-medium text-gray-600">Created at:</span>
                <span class="ml-1">{{ activityLog?.created_at ? formatDateTime(activityLog?.created_at) : 'N/A'
                    }}</span>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 flex justify-end">
            <button @click="showDeleteModal = true"
                class="px-4 py-2 bg-red-500 text-white rounded flex items-center gap-2">
                <FontAwesomeIcon icon="trash" />
                Delete
            </button>
        </div>

        <div v-if="showDeleteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-xl shadow-lg w-96">
                <div class="font-semibold mb-2">Delete Confirmation</div>
                <p class="text-sm text-gray-600 mb-4">Are you sure you want to delete this activity log?</p>
                <div class="flex justify-end gap-2">
                    <button @click="showDeleteModal = false" class="px-3 py-1 bg-gray-200 rounded">Cancel</button>
                    <button @click="handleDelete" :disabled="deleting" class="px-3 py-1 bg-red-500 text-white rounded">
                        <FontAwesomeIcon v-if="deleting" icon="spinner" spin />
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
