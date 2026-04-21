<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import ModelPagination from '@/components/common/model/Pagination.vue'
import MultiSelectInfinityLoadingApi from '@/components/common/multi-select/InfinityLoadingApi.vue'

import { ref, computed, onMounted, nextTick, inject } from 'vue'
import { Head, useForm, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faTrash, faFilter, faInfo,
    faPlus, faPen, faEye, faEyeSlash, faSpinner
} from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'
import { itemListFilterParameters } from '@/composables/useUtil'
import { fetchFromApi } from '@/composables/useSystemApi'

import { canCreateCategory, canEditCategory, canDeleteCategory, } from '@/composables/useAuthUserAccessPermissions'

FontAwesomeLibrary.add(faTrash, faFilter, faInfo, faPlus, faPen, faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const pageReady = inject("pageReady")
const authUser = inject("authUser")

const deletingRow = ref(null)
const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const { categories } = defineProps({
    categories: Object,
})

const paginationOnly = computed(() => {
    if (!categories) return {}
    const { data, ...rest } = categories
    return rest
})

const filterForm = useForm({
    per_page: null,
    created_by_id: null,
    parent_id: '',
    language_id: '',
    date: '',
    search: '',
})

const applyFilter = () => {
    if (filterForm.processing) return

    const cleanParams = itemListFilterParameters(filterForm.data())
    intertiaJsRoute.get(route('back-office.categories.index'), cleanParams, {
        replace: true,
        preserveScroll: true,
        preserveState: true,
        onFinish: () => filterForm.processing = false,
    })
}

const confirmDelete = (category) => {
    deletingRow.value = category
    showDeleteModal.value = true
}

const canCreate = () => canCreateCategory(authUser?.value)
const canEdit = (category) => canEditCategory(authUser?.value, category)
const canDelete = (category) => canDeleteCategory(authUser?.value, category)

const handleDelete = (category) => {
    if (!category || deleteProcessing.value) return

    deleteProcessing.value = true
    intertiaJsRoute.delete(route('back-office.categories.delete', { slug: category?.slug }), {
        onFinish: () => {
            showDeleteModal.value = false
            deletingRow.value = null
            deleteProcessing.value = false
        }
    })
}

onMounted(async () => {
    const urlParams = new URLSearchParams(window.location.search)

    filterForm.per_page = urlParams.get('per_page') || ''
    filterForm.created_by_id = urlParams.get('created_by_id') || ''
    filterForm.parent_id = urlParams.get('parent_id') || ''
    filterForm.language_id = urlParams.get('language_id') || ''
    filterForm.date = urlParams.get('date') || ''
    filterForm.search = urlParams.get('search') || ''

    if (filterForm.parent_id) {
        const rParent = await fetchFromApi(
            route('search.category', { slugOrId: filterForm.parent_id })
        )

        filterForm.parent_id = rParent || null
    }

    if (filterForm.language_id) {
        const rLanguage = await fetchFromApi(
            route('search.language', { slugOrId: filterForm.language_id })
        )

        filterForm.parent_id = rLanguage || null
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
                { text: 'Dashboard', href: route('auth-user.dashboard.index') },
                { text: 'Categories', active: true },
            ],
        })
    )

    pageReady.value = true
})
</script>

<template>

    <Head title="Categories" />

    <div class="w-full space-y-6">

        <div v-if="canCreate()" class="flex justify-end">
            <a :href="route('back-office.categories.create')"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded flex items-center gap-2">
                <FontAwesomeIcon icon="plus" />
                Create
            </a>
        </div>

        <form @submit.prevent="applyFilter"
            class="bg-white border border-gray-200 rounded-2xl p-4 md:p-6 grid md:grid-cols-3 gap-4">

            <MultiSelectInfinityLoadingApi :form="filterForm" fieldName="per_page" :selectedItem="filterForm.per_page"
                :apiUrl="route('search.per-pages')" :multiple="false" placeholder="Per page" v-if="pageReady" />

            <MultiSelectInfinityLoadingApi :form="filterForm" fieldName="created_by_id"
                :selectedItem="filterForm.created_by_id" :apiUrl="route('search.users')" :multiple="false"
                placeholder="Created by" v-if="pageReady" />

            <MultiSelectInfinityLoadingApi :form="filterForm" fieldName="language_id"
                :selectedItem="filterForm.language_id" :apiUrl="route('search.languages')" :multiple="false"
                placeholder="Language" v-if="pageReady" />

            <MultiSelectInfinityLoadingApi :form="filterForm" fieldName="parent_id" selectedLabelKey="indentation_name"
                selectedValueKey="id" :selectedItem="filterForm.parent_id" apiLabelKey="indentation_name"
                apiValueKey="id" :apiUrl="route('search.category-tree')" :multiple="false" placeholder="Parent"
                v-if="pageReady" />

            <input type="date" v-model="filterForm.date" class="border rounded px-3 py-2 w-full" />

            <input type="search" v-model="filterForm.search" placeholder="Search..."
                class="border rounded px-3 py-2 w-full" />


            <div class="md:col-span-3 flex justify-center">
                <button type="submit" :disabled="filterForm.processing"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded flex items-center gap-2">

                    <FontAwesomeIcon v-if="filterForm.processing" icon="spinner" spin />
                    <FontAwesomeIcon icon="filter" />
                    Filter

                </button>
            </div>

        </form>

        <div class="bg-white border border-gray-200 rounded-2xl overflow-x-auto">

            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Parent</th>
                        <th class="px-4 py-3 text-left">Created</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    <tr v-for="(item, index) in categories?.data" :key="item.id" class="hover:bg-gray-50">
                        <td class="px-4 py-3">{{ index + 1 }}</td>
                        <td class="px-4 py-3">{{ item.name }}</td>
                        <td class="px-4 py-2">{{ item.parent ? item.parent.name : 'N/A' }}</td>
                        <td class="px-4 py-3">{{ formatDateTime(item.created_at) }}</td>
                        <td class="px-4 py-3 text-right flex justify-end gap-2">

                            <a :href="route('back-office.categories.details', { slug: item.slug })"
                                class="px-3 py-1 text-xs border border-blue-500 text-blue-600 rounded hover:bg-blue-50 flex items-center gap-1">
                                <FontAwesomeIcon icon="info" /> Details
                            </a>

                            <a v-if="canEdit(item)" :href="route('back-office.categories.edit', { slug: item.slug })"
                                class="px-3 py-1 text-xs border border-blue-600 text-blue-700 rounded hover:bg-blue-50 flex items-center gap-1">
                                <FontAwesomeIcon icon="pen" /> Edit
                            </a>

                            <button v-if="canDelete(item)" @click="confirmDelete(item)"
                                class="px-3 py-1 text-xs border border-red-500 text-red-600 rounded hover:bg-red-50 flex items-center gap-1">
                                <FontAwesomeIcon icon="trash" /> Delete
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

        </div>

        <ModelPagination :pagination="paginationOnly" />

        <div v-if="showDeleteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-xl shadow-lg w-96">
                <div class="font-semibold mb-2">Delete Confirmation</div>
                <p class="mb-2">{{ deletingRow?.name }}</p>
                <p class="text-sm text-gray-600 mb-4">
                    This action cannot be undone.
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
        </div>
    </div>
</template>
