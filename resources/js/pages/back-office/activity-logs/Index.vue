<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import ModelPagination from '@/components/common/model/Pagination.vue'
import MultiSelectInfinityLoadingApi from '@/components/common/multi-select/InfinityLoadingApi.vue'

import { ref, computed, onMounted, nextTick, inject } from 'vue'
import { Head, useForm, router as intertiaJsRoute } from '@inertiajs/vue3'

import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { faTrash, faFilter, faInfo, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'
import { itemListFilterParameters } from '@/composables/useUtil'
import { fetchFromApi } from '@/composables/useSystemApi'

library.add(faTrash, faFilter, faInfo, faSpinner)

defineOptions({ layout: Layout })

const { activityLogs, showSubjectType } = defineProps({
    activityLogs: Object,
    showSubjectType: Boolean,
})

const pageReady = inject("pageReady")

const deletingRow = ref(null)
const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const filterForm = useForm({
    per_page: null,
    causer_id: null,
    subject_type: null,
    date: null,
    search: null
})

const tableFields = [
    { key: 'sl', label: 'SL' },
    { key: 'log_name', label: 'Log Name' },
    { key: 'event', label: 'Event' },
    { key: 'created_at', label: 'Created At' },
]

const paginationOnly = computed(() => {
    if (!activityLogs) return {}
    const { data, ...rest } = activityLogs
    return rest
})

const applyFilter = () => {
    if (filterForm.processing) return

    const cleanParams = itemListFilterParameters(filterForm.data())
    intertiaJsRoute.get(route('back-office.activity-logs.index'), cleanParams, {
        replace: true,
        preserveScroll: true,
        preserveState: true,
        onFinish: () => filterForm.processing = false
    })
}

const confirmDelete = (activityLog) => {
    deletingRow.value = activityLog
    showDeleteModal.value = true
}

const handleDelete = (activityLog) => {
    if (!activityLog || deleteProcessing.value) return

    deleteProcessing.value = true
    intertiaJsRoute.delete(route('back-office.activity-logs.delete', { slug: activityLog?.slug }), {
        onFinish: () => {
            deleteProcessing.value = false
            showDeleteModal.value = false
            deletingRow.value = null
        }
    })
}

onMounted(async () => {
    const urlParams = new URLSearchParams(window.location.search)

    filterForm.causer_id = urlParams.get('causer_id') || null
    filterForm.date = urlParams.get('date') || null
    filterForm.search = urlParams.get('search') || null
    filterForm.per_page = urlParams.get('per_page') || null
    filterForm.subject_type = urlParams.get('subject_type') || null

    if (filterForm.causer_id) {
        const rCauserBy = await fetchFromApi(route('search.user', { slugOrId: filterForm.causer_id }))
        filterForm.causer_id = rCauserBy || null
    }

    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: 'Activity logs', active: true }
            ],
        })
    )

    pageReady.value = true
})
</script>

<template>

    <Head title="Activity Logs" />

    <div class="w-full space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">Activity Logs</h2>
        </div>

        <form @submit.prevent="applyFilter" class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <MultiSelectInfinityLoadingApi v-if="pageReady" :form="filterForm" fieldName="per_page"
                    :selectedItem="filterForm.per_page" :apiUrl="route('search.per-pages')" :multiple="false"
                    selectedLabelKey="name" selectedValueKey="id" apiLabelKey="name" apiValueKey="id"
                    placeholder="Per page" />

                <MultiSelectInfinityLoadingApi v-if="showSubjectType && pageReady" :form="filterForm"
                    fieldName="subject_type" :selectedItem="filterForm.subject_type"
                    :apiUrl="route('search.activity-log-subject-types')" :multiple="false" selectedLabelKey="name"
                    selectedValueKey="id" apiLabelKey="name" apiValueKey="id" placeholder="Subject" />

                <MultiSelectInfinityLoadingApi v-if="pageReady" :form="filterForm" fieldName="causer_id"
                    :selectedItem="filterForm.causer_id" :apiUrl="route('search.users')" :multiple="false"
                    selectedLabelKey="name_with_user_role" selectedValueKey="id" apiLabelKey="name_with_user_role"
                    apiValueKey="id" placeholder="Causer" />

                <input type="date" v-model="filterForm.date"
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />

                <input type="search" v-model="filterForm.search" placeholder="Search logs..."
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none md:col-span-2" />

            </div>

            <div class="flex justify-end">
                <button type="submit" :disabled="filterForm.processing"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon v-if="filterForm.processing" icon="spinner" spin />
                    <FontAwesomeIcon icon="filter" />
                    Apply Filter
                </button>
            </div>
        </form>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">

                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">#</th>
                            <th class="px-4 py-3 text-left">Log Name</th>
                            <th class="px-4 py-3 text-left">Event</th>
                            <th class="px-4 py-3 text-left">Created</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <tr v-for="(item, index) in activityLogs?.data" :key="item.id"
                            class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">{{ index + 1 }}</td>
                            <td class="px-4 py-3 font-medium">{{ item.log_name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ item.event }}</td>
                            <td class="px-4 py-3 text-gray-500">
                                {{ formatDateTime(item.created_at) }}
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">

                                    <a :href="route('back-office.activity-logs.details', { slug: item.slug })"
                                        class="p-2 rounded-md text-blue-600 hover:bg-blue-50 border">
                                        <FontAwesomeIcon icon="info" />
                                    </a>

                                    <button @click="confirmDelete(item)"
                                        class="p-2 rounded-md text-red-600 hover:bg-red-50 border">
                                        <FontAwesomeIcon icon="trash" />
                                    </button>

                                </div>
                            </td>
                        </tr>
                    </tbody>

                </table>
            </div>
        </div>

        <ModelPagination :pagination="paginationOnly" />

        <Teleport to="body">
            <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showDeleteModal"
                    class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50"
                    @click.self="showDeleteModal = false">
                    <Transition enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-4">
                        <div v-if="showDeleteModal" class="bg-white rounded-xl shadow-lg w-[380px] p-6 space-y-4">
                            <h3 class="text-lg font-semibold text-red-600">
                                Delete Activity Log
                            </h3>

                            <p class="text-sm font-medium">
                                {{ deletingRow?.log_name }}
                            </p>

                            <p class="text-sm text-gray-500">
                                This action cannot be undone.
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button @click="showDeleteModal = false"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    Cancel
                                </button>

                                <button @click="handleDelete(deletingRow)" :disabled="deleteProcessing || !deletingRow"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 disabled:opacity-60 disabled:cursor-not-allowed text-white rounded-md text-sm flex items-center gap-2">
                                    <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />
                                    Delete
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

    </div>
</template>
