<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import ModelPagination from '@/components/common/model/Pagination.vue'
import MultiSelectInfinityLoadingApi from '@/components/common/multi-select/InfinityLoadingApi.vue'

import { ref, computed, onMounted, nextTick, inject } from 'vue'
import { Head, useForm, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faTrash, faTrashCan, faFilter, faInfo,
    faPlus, faPen, faEye, faEyeSlash, faSpinner
} from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'
import { itemListFilterParameters } from '@/composables/useDataTable'
import { fetchFromApi } from '@/composables/useSystemApi'

import {
    canCreatePage,
    canEditPage,
    canTrashPage,
    canRestorePage,
    canDeletePage
} from '@/composables/useAuthUserAccessPermissions'

FontAwesomeLibrary.add(faTrash, faTrashCan, faFilter, faInfo, faPlus, faPen, faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const authUser = inject("authUser")

const trashingRow = ref(null)
const showTrashModal = ref(false)
const trashProcessing = ref(false)

const restoringRow = ref(null)
const showRestoreModal = ref(false)
const restoreProcessing = ref(false)

const deletingRow = ref(null)
const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const { pages } = defineProps({
    pages: Object,
})

const paginationOnly = computed(() => {
    if (!pages) return {}
    const { data, ...rest } = pages
    return rest
})

const filterForm = useForm({
    per_page: null,
    created_by_id: null,
    language_id: '',
    date: '',
    search: '',
    parent_id: '',
})

const applyFilter = () => {
    if (filterForm.processing) return

    const cleanParams = itemListFilterParameters(filterForm.data())

    intertiaJsRoute.get(route('back-office.pages.index'), cleanParams, {
        replace: true,
        preserveScroll: true,
        preserveState: true,
        onFinish: () => filterForm.processing = false,
    })
}

const confirmTrash = (page) => {
    trashingRow.value = page
    showTrashModal.value = true
}

const confirmRestore = (page) => {
    restoringRow.value = page
    showRestoreModal.value = true
}

const confirmDelete = (page) => {
    deletingRow.value = page
    showDeleteModal.value = true
}

const closeTrashModal = () => {
    showTrashModal.value = false
    trashingRow.value = null
}

const closeRestoreModal = () => {
    showRestoreModal.value = false
    restoringRow.value = null
}

const closeDeleteModal = () => {
    showDeleteModal.value = false
    deletingRow.value = null
}

const canCreate = () => canCreatePage(authUser?.value)
const canEdit = (page) => canEditPage(authUser?.value, page)
const canTrash = (page) => canTrashPage(authUser?.value, page)
const canRestore = (page) => canRestorePage(authUser?.value, page)
const canDelete = (page) => canDeletePage(authUser?.value, page)

const handleTrash = (page) => {
    if (!page || trashProcessing.value) return

    trashProcessing.value = true

    intertiaJsRoute.patch(route('back-office.pages.trash', { slug: page?.slug }), {}, {
        preserveScroll: true,
        onFinish: () => {
            closeTrashModal()
            trashProcessing.value = false
        }
    })
}

const handleRestore = (page) => {
    if (!page || restoreProcessing.value) return

    restoreProcessing.value = true

    intertiaJsRoute.patch(route('back-office.pages.restore', { slug: page?.slug }), {}, {
        preserveScroll: true,
        onFinish: () => {
            closeRestoreModal()
            restoreProcessing.value = false
        }
    })
}

const handleDelete = (page) => {
    if (!page || deleteProcessing.value) return

    deleteProcessing.value = true

    intertiaJsRoute.delete(route('back-office.pages.delete', { slug: page?.slug }), {
        preserveScroll: true,
        onFinish: () => {
            closeDeleteModal()
            deleteProcessing.value = false
        }
    })
}

onMounted(async () => {
    const urlParams = new URLSearchParams(window.location.search)

    filterForm.per_page = urlParams.get('per_page') || ''
    filterForm.created_by_id = urlParams.get('created_by_id') || ''
    filterForm.language_id = urlParams.get('language_id') || ''
    filterForm.parent_id = urlParams.get('parent_id') || ''
    filterForm.date = urlParams.get('date') || ''
    filterForm.search = urlParams.get('search') || ''

    if (filterForm.language_id) {
        const rLanguage = await fetchFromApi(
            route('search.language', { slugOrId: filterForm.language_id })
        )

        filterForm.language_id = rLanguage || null
    }

    if (filterForm.parent_id) {
        const rParent = await fetchFromApi(
            route('search.page', { slugOrId: filterForm.parent_id })
        )

        filterForm.parent_id = rParent || null
    }

    if (filterForm.created_by_id) {
        const rCreatedBy = await fetchFromApi(
            route('search.user', { slugOrId: filterForm.created_by_id })
        )

        filterForm.created_by_id = rCreatedBy || null
    }

    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: 'Page', active: true },
            ],
        })
    )
})
</script>

<template>

    <Head title="Page" />

    <div class="w-full space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">Page</h2>

            <a v-if="canCreate()" :href="route('back-office.pages.create')"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                <FontAwesomeIcon icon="plus" />
                Create
            </a>
        </div>

        <form @submit.prevent="applyFilter" class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <MultiSelectInfinityLoadingApi :form="filterForm" fieldName="per_page"
                    :selectedItem="filterForm.per_page" :apiUrl="route('search.per-pages')" :multiple="false"
                    placeholder="Per page" />

                <MultiSelectInfinityLoadingApi :form="filterForm" fieldName="created_by_id"
                    :selectedItem="filterForm.created_by_id" :apiUrl="route('search.users')" :multiple="false"
                    placeholder="Created by" />

                <MultiSelectInfinityLoadingApi :form="filterForm" fieldName="language_id"
                    :selectedItem="filterForm.language_id" :apiUrl="route('search.languages')" :multiple="false"
                    placeholder="Language" />

                <input type="date" v-model="filterForm.date"
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />

                <input type="search" v-model="filterForm.search" placeholder="Search page..."
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />

            </div>

            <div class="flex justify-end">
                <button type="submit" :disabled="filterForm.processing"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md flex items-center gap-2 transition disabled:opacity-60 disabled:cursor-not-allowed">
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
                            <th class="px-4 py-3 text-left">Title</th>
                            <th class="px-4 py-3 text-left">Language</th>
                            <th class="px-4 py-3 text-left">Parent</th>
                            <th class="px-4 py-3 text-left">Created</th>
                            <th class="px-4 py-3 text-left">Is Default</th>
                            <th class="px-4 py-3 text-left">Is Published</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <tr v-for="(item, index) in pages?.data" :key="item.id" class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">{{ index + 1 }}</td>

                            <td class="px-4 py-3 font-medium">
                                {{ item.title }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ item.language ? item.language.name : 'N/A' }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ item.parent ? item.parent.name : 'N/A' }}
                            </td>

                            <td class="px-4 py-3 text-gray-500">
                                {{ formatDateTime(item.created_at) }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ item.is_default ? "Yes" : 'No' }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ item.is_published ? "Yes" : 'No' }}
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">

                                    <a :href="route('back-office.pages.details', { slug: item.slug })"
                                        class="p-2 rounded-md text-blue-600 hover:bg-blue-50 border">
                                        <FontAwesomeIcon icon="info" />
                                    </a>

                                    <a v-if="canEdit(item)" :href="route('back-office.pages.edit', { slug: item.slug })"
                                        class="p-2 rounded-md text-yellow-600 hover:bg-yellow-50 border">
                                        <FontAwesomeIcon icon="pen" />
                                    </a>

                                    <button v-if="canTrash(item)" type="button" @click="confirmTrash(item)"
                                        class="p-2 rounded-md text-red-600 hover:bg-red-50 border">
                                        <FontAwesomeIcon icon="trash" />
                                    </button>

                                    <button v-if="canRestore(item)" type="button" @click="confirmRestore(item)"
                                        class="p-2 rounded-md text-green-600 hover:bg-green-50 border">
                                        <FontAwesomeIcon icon="eye" />
                                    </button>

                                    <button v-if="canDelete(item)" type="button" @click="confirmDelete(item)"
                                        class="p-2 rounded-md text-red-700 hover:bg-red-50 border">
                                        <FontAwesomeIcon icon="trash-can" />
                                    </button>

                                </div>
                            </td>
                        </tr>

                        <tr v-if="!pages?.data?.length">
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                No page found.
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
                <div v-if="showTrashModal"
                    class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50">

                    <Transition enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-4">
                        <div v-if="showTrashModal" class="bg-white rounded-xl shadow-lg w-[380px] p-6 space-y-4">
                            <h3 class="text-lg font-semibold text-red-600">
                                Trash Page
                            </h3>

                            <p class="text-sm font-medium">
                                {{ trashingRow?.title }}
                            </p>

                            <p class="text-sm text-gray-500">
                                This action can be undone by restoring page.
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="closeTrashModal"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    Cancel
                                </button>

                                <button type="button" @click="handleTrash(trashingRow)" :disabled="trashProcessing"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                                    <FontAwesomeIcon v-if="trashProcessing" icon="spinner" spin />
                                    Trash
                                </button>
                            </div>
                        </div>
                    </Transition>

                </div>
            </Transition>

            <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showRestoreModal"
                    class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50">

                    <Transition enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-4">
                        <div v-if="showRestoreModal" class="bg-white rounded-xl shadow-lg w-[380px] p-6 space-y-4">
                            <h3 class="text-lg font-semibold text-green-600">
                                Restore Page
                            </h3>

                            <p class="text-sm font-medium">
                                {{ restoringRow?.title }}
                            </p>

                            <p class="text-sm text-gray-500">
                                This action can be undone by trashing page.
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="closeRestoreModal"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    Cancel
                                </button>

                                <button type="button" @click="handleRestore(restoringRow)" :disabled="restoreProcessing"
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm flex items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                                    <FontAwesomeIcon v-if="restoreProcessing" icon="spinner" spin />
                                    Restore
                                </button>
                            </div>
                        </div>
                    </Transition>

                </div>
            </Transition>

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
                            <h3 class="text-lg font-semibold text-red-700">
                                Delete Page
                            </h3>

                            <p class="text-sm font-medium">
                                {{ deletingRow?.title }}
                            </p>

                            <p class="text-sm text-gray-500">
                                This action cannot be undone.
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="closeDeleteModal"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    Cancel
                                </button>

                                <button type="button" @click="handleDelete(deletingRow)" :disabled="deleteProcessing"
                                    class="px-4 py-2 bg-red-700 hover:bg-red-800 text-white rounded-md text-sm flex items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
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
