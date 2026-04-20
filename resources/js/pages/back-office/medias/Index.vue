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

const { medias} = defineProps({
    medias: Object,
})

const pageReady = inject("pageReady")

const deletingRow = ref(null)
const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const filterForm = useForm({
    per_page: null,
    created_by_id: null,
    subject_type: null,
    date: null,
    search: null
})

const tableFields = [
    { key: 'sl', label: 'SL' },
    { key: 'name', label: 'Name' },
    { key: 'collection_name', label: 'Collection name' },
    { key: 'created_at', label: 'Created At' },
]

const paginationOnly = computed(() => {
    if (!medias) return {}
    const { data, ...rest } = medias
    return rest
})

const applyFilter = () => {
    if (filterForm.processing) return

    const cleanParams = itemListFilterParameters(filterForm.data())
    intertiaJsRoute.get(route('back-office.medias.index'), cleanParams, {
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
    intertiaJsRoute.delete(route('back-office.medias.delete', { slug: activityLog?.slug }), {
        onFinish: () => {
            deleteProcessing.value = false
            showDeleteModal.value = false
            deletingRow.value = null
        }
    })
}

onMounted(async () => {
    const urlParams = new URLSearchParams(window.location.search)

    filterForm.created_by_id = urlParams.get('created_by_id') || null
    filterForm.date = urlParams.get('date') || null
    filterForm.search = urlParams.get('search') || null
    filterForm.per_page = urlParams.get('per_page') || null

    if (filterForm.created_by_id) {
        const rCauserBy = await fetchFromApi(route('search.user', { slugOrId: filterForm.created_by_id }))
        filterForm.created_by_id = rCauserBy || null
    }

    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: 'Dashboard', href: route('auth-user.dashboard.index') },
                { text: 'Medias', active: true }
            ],
        })
    )

    pageReady.value = true
})
</script>

<template>

    <Head title="Medias" />

    <div class="w-full">

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6 space-y-6">

            <form @submit.prevent="applyFilter" class="grid md:grid-cols-4 gap-4">

                <div>
                    <label class="block text-sm font-medium mb-1">Per Page</label>
                    <MultiSelectInfinityLoadingApi :form="filterForm" fieldName="per_page"
                        :selectedItem="filterForm.per_page" :apiUrl="route('search.per-pages')" :multiple="false"
                        selectedLabelKey="name" selectedValueKey="id" apiLabelKey="name" apiValueKey="id"
                        placeholder="Select" :error="filterForm.errors.per_page" v-if="pageReady" />
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Created by</label>
                    <MultiSelectInfinityLoadingApi :form="filterForm" fieldName="created_by_id"
                        :selectedItem="filterForm.created_by_id" :apiUrl="route('search.users')" :multiple="false"
                        selectedLabelKey="name_with_user_role" selectedValueKey="id" apiLabelKey="name_with_user_role"
                        apiValueKey="id" placeholder="Select" :error="filterForm.errors.created_by_id" v-if="pageReady" />
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Date</label>
                    <input type="date" v-model="filterForm.date" class="w-full border rounded px-3 py-2"
                        :class="filterForm.errors.date ? 'border-red-500' : 'border-gray-300'" />
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Search</label>
                    <input type="search" v-model="filterForm.search" placeholder="Search logs..."
                        class="w-full border rounded px-3 py-2"
                        :class="filterForm.errors.search ? 'border-red-500' : 'border-gray-300'" />
                </div>

                <div class="md:col-span-4 flex justify-center">
                    <button type="submit" :disabled="filterForm.processing"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded flex items-center gap-2">

                        <FontAwesomeIcon v-if="filterForm.processing" icon="spinner" spin />
                        <FontAwesomeIcon icon="filter" />
                        Filter

                    </button>
                </div>

            </form>

            <div class="overflow-x-auto border border-gray-200 rounded-xl">

                <table class="min-w-full text-sm">

                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">#</th>
                            <th class="px-4 py-3 text-left">Name</th>
                            <th class="px-4 py-3 text-left">Collection name</th>
                            <th class="px-4 py-3 text-left">Created At</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <tr v-for="(item, index) in medias?.data" :key="item.id" class="hover:bg-gray-50">

                            <td class="px-4 py-3">{{ index + 1 }}</td>
                            <td class="px-4 py-3">{{ item.name ?? "N/A" }}</td>
                            <td class="px-4 py-3">{{ item.collection_name ?? "N/A" }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ formatDateTime(item.created_at) }}</td>

                            <td class="px-4 py-3 text-right flex justify-end gap-2">

                                <a :href="route('back-office.medias.details', { slug: item.slug })"
                                    class="px-3 py-1 text-xs border border-blue-500 text-blue-600 rounded hover:bg-blue-50 flex items-center gap-1">
                                    <FontAwesomeIcon icon="info" />
                                    Details
                                </a>

                                <button @click="confirmDelete(item)"
                                    class="px-3 py-1 text-xs border border-red-500 text-red-600 rounded hover:bg-red-50 flex items-center gap-1">
                                    <FontAwesomeIcon icon="trash" />
                                    Trash
                                </button>

                            </td>

                        </tr>
                    </tbody>

                </table>

            </div>

            <ModelPagination :pagination="paginationOnly" />

        </div>

        <transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition-opacity duration-150"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showDeleteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">

                <transition enter-active-class="transition transform duration-200" enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100" leave-active-class="transition transform duration-150"
                    leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                    <div class="bg-white p-6 rounded-xl shadow-lg w-96">

                        <div class="font-semibold mb-2">Delete Confirmation</div>

                        <p class="text-sm text-gray-600 mb-4">
                            <strong>{{ deletingRow?.log_name }}</strong>
                        </p>

                        <p class="text-sm mb-4">
                            Are you sure you want to delete this activity log?
                        </p>

                        <div class="flex justify-end gap-2">

                            <button @click="showDeleteModal = false" class="px-3 py-1 bg-gray-200 rounded">
                                Cancel
                            </button>

                            <button @click="handleDelete(deletingRow)" :disabled="deleteProcessing"
                                class="px-3 py-1 bg-red-500 text-white rounded flex items-center gap-1">

                                <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />
                                Delete

                            </button>

                        </div>

                    </div>
                </transition>

            </div>
        </transition>

    </div>
</template>

