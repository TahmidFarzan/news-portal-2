<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import ModelPagination from '@/components/common/model/Pagination.vue'
import SelectInfinityLoadingApi from '@/components/common/multi-select/SelectInfinityLoadingApi.vue'

import { ref, computed, onMounted, nextTick, inject } from 'vue'
import { Head, useForm, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faTrash, faFilter, faInfo,
    faPlus, faPen, faEye, faEyeSlash, faSpinner
} from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'
import { itemListFilterParameters } from '@/composables/useDataTable'
import { fetchFromApi } from '@/composables/useApiClient'

import { canCreateCategory, canUpdateCategory, canDeleteCategory } from '@/composables/useUserPermissions'
import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faTrash, faFilter, faInfo, faPlus, faPen, faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const authUser = inject("authUser")

const deletingRow = ref(null)
const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const { categories } = defineProps({
    categories: {
        type: Object,
        default: () => ({})
    },
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
const canUpdate = (category) => canUpdateCategory(authUser?.value, category)
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

        filterForm.language_id = rLanguage || null
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
                { text: t('common.messages.categories'), active: true },
            ],
        })
    )
})
</script>

<template>

    <Head :title="t('common.messages.categories')" />

    <div class="w-full space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">
                {{ t('common.messages.categories') }}
            </h2>

            <a v-if="canCreate()" :href="route('back-office.categories.create')"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                <FontAwesomeIcon icon="plus" />
                {{ t('common.actions.create') }}
            </a>
        </div>

        <form @submit.prevent="applyFilter" class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <SelectInfinityLoadingApi :form="filterForm" fieldName="per_page"
                    :selectedItem="filterForm.per_page" :apiUrl="route('search.per-pages')" :multiple="false"
                    :placeholder="t('common.labels.perPage')" />

                <SelectInfinityLoadingApi :form="filterForm" fieldName="created_by_id"
                    :selectedItem="filterForm.created_by_id" :apiUrl="route('search.users')" :multiple="false"
                    :placeholder="t('common.labels.createdBy')" />

                <SelectInfinityLoadingApi :form="filterForm" fieldName="language_id"
                    :selectedItem="filterForm.language_id" :apiUrl="route('search.languages')" :multiple="false"
                    :placeholder="t('common.labels.language')" />

                <SelectInfinityLoadingApi :form="filterForm" fieldName="parent_id"
                    selectedLabelKey="indentation_name" selectedValueKey="id" :selectedItem="filterForm.parent_id"
                    apiLabelKey="indentation_name" apiValueKey="id" :apiUrl="route('search.category-tree')"
                    :multiple="false" :placeholder="t('common.placeholders.parent')" />

                <input type="date" v-model="filterForm.date"
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />

                <input type="search" v-model="filterForm.search" :placeholder="t('admin.categories.index.searchPlaceholder')"
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />

            </div>

            <div class="flex justify-end">
                <button type="submit" :disabled="filterForm.processing"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md flex items-center gap-2 transition disabled:opacity-70 disabled:cursor-not-allowed">
                    <FontAwesomeIcon v-if="filterForm.processing" icon="spinner" spin />
                    <FontAwesomeIcon v-else icon="filter" />

                    {{ filterForm.processing ? t('common.actions.applyingFilter') :
                        t('common.actions.applyFilter') }}
                </button>
            </div>
        </form>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">

                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">#</th>
                            <th class="px-4 py-3 text-left">{{ t('common.labels.name') }}</th>
                            <th class="px-4 py-3 text-left">{{ t('common.placeholders.parent') }}</th>
                            <th class="px-4 py-3 text-left">{{ t('common.labels.createdAt') }}</th>
                            <th class="px-4 py-3 text-right">{{ t('common.labels.action') }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <tr v-for="(item, index) in categories?.data || []" :key="item.id"
                            class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">{{ index + 1 }}</td>

                            <td class="px-4 py-3 font-medium">
                                {{ item.name }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ item.parent ? item.parent.name : t('common.labels.notAvailable') }}
                            </td>

                            <td class="px-4 py-3 text-gray-500">
                                {{ item.created_at ? formatDateTime(item.created_at) : t('common.labels.notAvailable') }}
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">

                                    <a :href="route('back-office.categories.details', { slug: item.slug })"
                                        class="p-2 rounded-md text-blue-600 hover:bg-blue-50 border"
                                        :title="t('common.actions.details')">
                                        <FontAwesomeIcon icon="info" />
                                    </a>

                                    <a v-if="canUpdate(item)"
                                        :href="route('back-office.categories.edit', { slug: item.slug })"
                                        class="p-2 rounded-md text-yellow-600 hover:bg-yellow-50 border"
                                        :title="t('common.actions.edit')">
                                        <FontAwesomeIcon icon="pen" />
                                    </a>

                                    <button v-if="canDelete(item)" @click="confirmDelete(item)"
                                        class="p-2 rounded-md text-red-600 hover:bg-red-50 border"
                                        :title="t('common.actions.delete')">
                                        <FontAwesomeIcon icon="trash" />
                                    </button>

                                </div>
                            </td>
                        </tr>

                        <tr v-if="!categories?.data?.length">
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                {{ t('common.labels.noRecordsFound') }}
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
                    class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50">

                    <Transition enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-4">
                        <div v-if="showDeleteModal" class="bg-white rounded-xl shadow-lg w-[380px] p-6 space-y-4">
                            <h3 class="text-lg font-semibold text-red-600">
                                {{ t('common.modals.deleteCategory') }}
                            </h3>

                            <p class="text-sm font-medium">
                                {{ deletingRow?.name }}
                            </p>

                            <p class="text-sm text-gray-500">
                                {{ t('common.modals.thisActionCannotBeUndone') }}
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button @click="showDeleteModal = false"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    {{ t('common.actions.cancel') }}
                                </button>

                                <button @click="handleDelete(deletingRow)" :disabled="deleteProcessing"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
                                    <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />
                                    {{ deleteProcessing ? t('common.actions.deleting') : t('common.actions.delete') }}
                                </button>
                            </div>
                        </div>
                    </Transition>

                </div>
            </Transition>
        </Teleport>
    </div>
</template>
