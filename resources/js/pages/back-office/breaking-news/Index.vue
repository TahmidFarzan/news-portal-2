<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import ModelPagination from '@/components/common/model/Pagination.vue'
import SelectInfinityLoadingApi from '@/components/common/multi-select/SelectInfinityLoadingApi.vue'

import { ref, computed, onMounted, nextTick, inject } from 'vue'
import { Head, useForm, router as inertiaJsRoute } from '@inertiajs/vue3'
import { useTranslate } from '@/composables/useTranslate'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faTrash, faFilter, faInfo,
    faPlus, faPen, faEye, faEyeSlash, faSpinner
} from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'
import { itemListFilterParameters } from '@/composables/useDataTable'
import { fetchFromApi } from '@/composables/useSystemApi'

import {
    canCreateBreakingNews,
    canEditBreakingNews,
    canDeleteBreakingNews,
    canTrashBreakingNews,
    canRestoreBreakingNews
} from '@/composables/useUserPermissions'

FontAwesomeLibrary.add(faTrash, faFilter, faInfo, faPlus, faPen, faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const authUser = inject('authUser')

const deletingRow = ref(null)
const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const restoringRow = ref(null)
const showRestoreModal = ref(false)
const restoreProcessing = ref(false)

const trashingRow = ref(null)
const showTrashModal = ref(false)
const trashProcessing = ref(false)

const { breakingNewsItems } = defineProps({
    breakingNewsItems: Object,
})

const paginationOnly = computed(() => {
    if (!breakingNewsItems) return {}

    const { data, ...rest } = breakingNewsItems

    return rest
})

const filterForm = useForm({
    per_page: null,
    created_by_id: null,
    news_type_id: '',
    category_id: '',
    language_id: '',
    location_id: '',
    event_id: '',
    date: '',
    search: '',
})

const applyFilter = () => {
    if (filterForm.processing) return

    const cleanParams = itemListFilterParameters(filterForm.data())

    inertiaJsRoute.get(route('back-office.breaking-news.index'), cleanParams, {
        replace: true,
        preserveScroll: true,
        preserveState: true,
    })
}

const confirmDelete = (breakingNews) => {
    deletingRow.value = breakingNews
    showDeleteModal.value = true
}

const confirmRestore = (breakingNews) => {
    restoringRow.value = breakingNews
    showRestoreModal.value = true
}

const confirmTrash = (breakingNews) => {
    trashingRow.value = breakingNews
    showTrashModal.value = true
}

const canCreate = () => canCreateBreakingNews(authUser?.value)
const canEdit = (breakingNews) => canEditBreakingNews(authUser?.value, breakingNews)
const canDelete = (breakingNews) => canDeleteBreakingNews(authUser?.value, breakingNews)
const canTrash = (breakingNews) => canTrashBreakingNews(authUser?.value, breakingNews)
const canRestore = (breakingNews) => canRestoreBreakingNews(authUser?.value, breakingNews)

const handleDelete = (breakingNews) => {
    if (!breakingNews || deleteProcessing.value) return

    deleteProcessing.value = true

    inertiaJsRoute.delete(route('back-office.breaking-news.delete', { slug: breakingNews?.slug }), {
        onFinish: () => {
            showDeleteModal.value = false
            deletingRow.value = null
            deleteProcessing.value = false
        }
    })
}

const handleRestore = (breakingNews) => {
    if (!breakingNews || restoreProcessing.value) return

    restoreProcessing.value = true

    inertiaJsRoute.patch(route('back-office.breaking-news.restore', { slug: breakingNews?.slug }), {}, {
        onFinish: () => {
            restoringRow.value = null
            restoreProcessing.value = false
            showRestoreModal.value = false
        }
    })
}

const handleTrash = (breakingNews) => {
    if (!breakingNews || trashProcessing.value) return

    trashProcessing.value = true

    inertiaJsRoute.patch(route('back-office.breaking-news.trash', { slug: breakingNews?.slug }), {}, {
        onFinish: () => {
            showTrashModal.value = false
            trashingRow.value = null
            trashProcessing.value = false
        }
    })
}

onMounted(async () => {
    const urlParams = new URLSearchParams(window.location.search)

    filterForm.per_page = urlParams.get('per_page') || ''
    filterForm.news_type_id = urlParams.get('news_type_id') || ''
    filterForm.created_by_id = urlParams.get('created_by_id') || ''
    filterForm.category_id = urlParams.get('category_id') || ''
    filterForm.language_id = urlParams.get('language_id') || ''
    filterForm.location_id = urlParams.get('location_id') || ''
    filterForm.event_id = urlParams.get('event_id') || ''
    filterForm.date = urlParams.get('date') || ''
    filterForm.search = urlParams.get('search') || ''

    if (filterForm.news_type_id) {
        const rNewsType = await fetchFromApi(
            route('search.news-type', { slugOrId: filterForm.news_type_id })
        )

        filterForm.news_type_id = rNewsType || null
    }

    if (filterForm.category_id) {
        const rCategory = await fetchFromApi(
            route('search.category', { slugOrId: filterForm.category_id })
        )

        filterForm.category_id = rCategory || null
    }

    if (filterForm.language_id) {
        const rLanguage = await fetchFromApi(
            route('search.language', { slugOrId: filterForm.language_id })
        )

        filterForm.language_id = rLanguage || null
    }

    if (filterForm.location_id) {
        const rLocation = await fetchFromApi(
            route('search.location', { slugOrId: filterForm.location_id })
        )

        filterForm.location_id = rLocation || null
    }

    if (filterForm.event_id) {
        const rEvent = await fetchFromApi(
            route('search.event', { slugOrId: filterForm.event_id })
        )

        filterForm.event_id = rEvent || null
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
                { text: t('pages.back_office.breaking_news.index.navigation.breaking_news'), active: true },
            ],
        })
    )
})
</script>

<template>

    <Head :title="t('pages.back_office.breaking_news.index.navigation.breaking_news')" />

    <div class="w-full space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">
                {{ t('pages.back_office.breaking_news.index.navigation.breaking_news') }}
            </h2>

            <a v-if="canCreate()" :href="route('back-office.breaking-news.create')"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                <FontAwesomeIcon icon="plus" />
                {{ t('pages.back_office.breaking_news.index.actions.create') }}
            </a>
        </div>

        <form @submit.prevent="applyFilter" class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <SelectInfinityLoadingApi :form="filterForm" fieldName="per_page"
                    :selectedItem="filterForm.per_page" :apiUrl="route('search.per-pages')" :multiple="false"
                    :placeholder="t('pages.back_office.breaking_news.index.labels.per_page')" />

                <SelectInfinityLoadingApi :form="filterForm" fieldName="created_by_id"
                    :selectedItem="filterForm.created_by_id" :apiUrl="route('search.users')" :multiple="false"
                    :placeholder="t('pages.back_office.breaking_news.index.labels.created_by')" />

                <SelectInfinityLoadingApi :form="filterForm" fieldName="news_type_id"
                    :selectedItem="filterForm.news_type_id" :apiUrl="route('search.news-types')" :multiple="false"
                    :placeholder="t('pages.back_office.breaking_news.index.labels.news_type')" />

                <SelectInfinityLoadingApi :form="filterForm" fieldName="language_id"
                    :selectedItem="filterForm.language_id" :apiUrl="route('search.languages')" :multiple="false"
                    :placeholder="t('pages.back_office.breaking_news.index.labels.language')" />

                <SelectInfinityLoadingApi :form="filterForm" fieldName="category_id"
                    selectedLabelKey="indentation_name" selectedValueKey="id" :selectedItem="filterForm.category_id"
                    apiLabelKey="indentation_name" apiValueKey="id" :apiUrl="route('search.category-tree')"
                    :multiple="false" :placeholder="t('pages.back_office.breaking_news.index.navigation.categories')" />

                <SelectInfinityLoadingApi :form="filterForm" fieldName="location_id"
                    selectedLabelKey="indentation_name" selectedValueKey="id" :selectedItem="filterForm.location_id"
                    apiLabelKey="indentation_name" apiValueKey="id" :apiUrl="route('search.location-tree')"
                    :multiple="false" :placeholder="t('pages.back_office.breaking_news.index.navigation.locations')" />

                <SelectInfinityLoadingApi :form="filterForm" fieldName="event_id"
                    :selectedItem="filterForm.event_id" :apiUrl="route('search.events')" :multiple="false"
                    :placeholder="t('pages.back_office.breaking_news.index.navigation.events')" />

                <input v-model="filterForm.date" type="date"
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />

                <input v-model="filterForm.search" type="search"
                    :placeholder="t('pages.back_office.breaking_news.index.search_placeholder')"
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />

            </div>

            <div class="flex justify-end">
                <button type="submit" :disabled="filterForm.processing"
                    class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white px-5 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon v-if="filterForm.processing" icon="spinner" spin />
                    <FontAwesomeIcon icon="filter" />

                    {{
                        filterForm.processing
                            ? t('pages.back_office.breaking_news.index.activity_logs.index.applying_filter')
                            : t('pages.back_office.breaking_news.index.activity_logs.index.apply_filter')
                    }}
                </button>
            </div>
        </form>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">

                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">
                                #
                            </th>

                            <th class="px-4 py-3 text-left">
                                {{ t('pages.back_office.breaking_news.index.labels.title') }}
                            </th>

                            <th class="px-4 py-3 text-left">
                                {{ t('pages.back_office.breaking_news.index.labels.language') }}
                            </th>

                            <th class="px-4 py-3 text-left">
                                {{ t('pages.back_office.breaking_news.index.activity_logs.index.created') }}
                            </th>

                            <th class="px-4 py-3 text-left">
                                {{ t('pages.back_office.breaking_news.index.labels.is_published') }}
                            </th>

                            <th class="px-4 py-3 text-right">
                                {{ t('pages.back_office.breaking_news.index.table.columns.action') }}
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <tr v-for="(item, index) in breakingNewsItems?.data" :key="item.id"
                            class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">
                                {{ index + 1 }}
                            </td>

                            <td class="px-4 py-3 font-medium">
                                {{ item.title }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ item.language ? item.language.name : t('pages.back_office.breaking_news.index.labels.not_available') }}
                            </td>

                            <td class="px-4 py-3 text-gray-500">
                                {{ formatDateTime(item.created_at) }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ item.is_published ? t('pages.back_office.breaking_news.index.labels.yes') : t('pages.back_office.breaking_news.index.labels.no') }}
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">

                                    <a :href="route('back-office.breaking-news.details', { slug: item.slug })"
                                        class="p-2 rounded-md text-blue-600 hover:bg-blue-50 border"
                                        :title="t('pages.back_office.breaking_news.index.table.menus.details')">
                                        <FontAwesomeIcon icon="info" />
                                    </a>

                                    <a v-if="canEdit(item)"
                                        :href="route('back-office.breaking-news.edit', { slug: item.slug })"
                                        class="p-2 rounded-md text-yellow-600 hover:bg-yellow-50 border"
                                        :title="t('pages.back_office.breaking_news.index.table.menus.edit')">
                                        <FontAwesomeIcon icon="pen" />
                                    </a>

                                    <button v-if="canDelete(item)" @click="confirmDelete(item)"
                                        class="p-2 rounded-md text-red-600 hover:bg-red-50 border"
                                        :title="t('pages.back_office.breaking_news.index.actions.delete')">
                                        <FontAwesomeIcon icon="trash" />
                                    </button>

                                    <button v-if="canRestore(item)" @click="confirmRestore(item)"
                                        class="p-2 rounded-md text-green-600 hover:bg-green-50 border"
                                        :title="t('pages.back_office.breaking_news.index.actions.restore')">
                                        <FontAwesomeIcon icon="eye" />
                                    </button>

                                    <button v-if="canTrash(item)" @click="confirmTrash(item)"
                                        class="p-2 rounded-md text-orange-600 hover:bg-orange-50 border"
                                        :title="t('pages.back_office.breaking_news.index.actions.trash')">
                                        <FontAwesomeIcon icon="eye-slash" />
                                    </button>

                                </div>
                            </td>
                        </tr>

                        <tr v-if="!breakingNewsItems?.data?.length">
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                {{ t('pages.back_office.breaking_news.index.labels.no_record_found') }}
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
                                {{ t('pages.back_office.breaking_news.index.delete_modal.title') }}
                            </h3>

                            <p class="text-sm font-medium">
                                {{ deletingRow?.title }}
                            </p>

                            <p class="text-sm text-gray-500">
                                {{ t('pages.back_office.breaking_news.index.modals.delete_confirmation_modal.irreversible_body') }}
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button @click="showDeleteModal = false"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    {{ t('pages.back_office.breaking_news.index.actions.cancel') }}
                                </button>

                                <button @click="handleDelete(deletingRow)" :disabled="deleteProcessing"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2">
                                    <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />

                                    {{
                                        deleteProcessing
                                            ? t('pages.back_office.breaking_news.index.actions.deleting')
                                            : t('pages.back_office.breaking_news.index.actions.delete')
                                    }}
                                </button>
                            </div>
                        </div>
                    </Transition>

                </div>
            </Transition>

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
                            <h3 class="text-lg font-semibold text-orange-600">
                                {{ t('pages.back_office.breaking_news.index.trash_modal.title') }}
                            </h3>

                            <p class="text-sm font-medium">
                                {{ trashingRow?.title }}
                            </p>

                            <p class="text-sm text-gray-500">
                                {{ t('pages.back_office.breaking_news.index.trash_modal.body') }}
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button @click="showTrashModal = false"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    {{ t('pages.back_office.breaking_news.index.actions.cancel') }}
                                </button>

                                <button @click="handleTrash(trashingRow)" :disabled="trashProcessing"
                                    class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-md text-sm flex items-center gap-2">
                                    <FontAwesomeIcon v-if="trashProcessing" icon="spinner" spin />

                                    {{
                                        trashProcessing
                                            ? t('pages.back_office.breaking_news.index.actions.trashing')
                                            : t('pages.back_office.breaking_news.index.actions.trash')
                                    }}
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
                                {{ t('pages.back_office.breaking_news.index.restore_modal.title') }}
                            </h3>

                            <p class="text-sm font-medium">
                                {{ restoringRow?.title }}
                            </p>

                            <p class="text-sm text-gray-500">
                                {{ t('pages.back_office.breaking_news.index.restore_modal.body') }}
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button @click="showRestoreModal = false"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    {{ t('pages.back_office.breaking_news.index.actions.cancel') }}
                                </button>

                                <button @click="handleRestore(restoringRow)" :disabled="restoreProcessing"
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm flex items-center gap-2">
                                    <FontAwesomeIcon v-if="restoreProcessing" icon="spinner" spin />

                                    {{
                                        restoreProcessing
                                            ? t('pages.back_office.breaking_news.index.actions.restoring')
                                            : t('pages.back_office.breaking_news.index.actions.restore')
                                    }}
                                </button>
                            </div>
                        </div>
                    </Transition>

                </div>
            </Transition>

        </Teleport>

    </div>
</template>
