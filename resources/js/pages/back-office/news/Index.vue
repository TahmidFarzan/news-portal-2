<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import ModelPagination from '@/components/common/model/Pagination.vue'
import SelectInfinityLoadingApi from '@/components/common/multi-select/SelectInfinityLoadingApi.vue'

import { ref, computed, onMounted, nextTick, inject } from 'vue'
import { Head, useForm, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faTrash, faFilter, faInfo,
    faPlus, faPen, faEye, faEyeSlash, faSpinner,
} from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'
import { itemListFilterParameters } from '@/composables/useDataTable'
import { fetchFromApi } from '@/composables/useSystemApi'
import { canCreateNews, canUpdateNews, canDeleteNews, canRestoreNews } from '@/composables/useUserPermissions'
import { useTranslate } from '@/composables/useTranslate'

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

const { newsItems = {} } = defineProps({
    newsItems: {
        type: Object,
        default: () => ({}),
    },
})

const notAvailable = computed(() => t('common.labels.notAvailable'))

const paginationOnly = computed(() => {
    if (!newsItems) return {}

    const { data, ...rest } = newsItems

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

    intertiaJsRoute.get(route('back-office.news.index'), cleanParams, {
        replace: true,
        preserveScroll: true,
        preserveState: true,
        onFinish: () => filterForm.processing = false,
    })
}

const confirmDelete = news => {
    deletingRow.value = news
    showDeleteModal.value = true
}

const confirmRestore = news => {
    restoringRow.value = news
    showRestoreModal.value = true
}

const canCreate = () => canCreateNews(authUser?.value)
const canUpdate = news => canUpdateNews(authUser?.value, news)
const canDelete = news => canDeleteNews(authUser?.value, news)
const canRestore = news => canRestoreNews(authUser?.value, news)

const handleDelete = news => {
    if (!news || deleteProcessing.value) return

    deleteProcessing.value = true

    intertiaJsRoute.patch(route('back-office.news.delete', { slug: news?.slug }), {}, {
        onFinish: () => {
            showDeleteModal.value = false
            deletingRow.value = null
            deleteProcessing.value = false
        },
    })
}

const handleRestore = news => {
    if (!news || restoreProcessing.value) return

    restoreProcessing.value = true

    intertiaJsRoute.patch(route('back-office.news.restore', { slug: news?.slug }), {}, {
        onFinish: () => {
            restoringRow.value = null
            restoreProcessing.value = false
            showRestoreModal.value = false
        },
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
                { text: t('common.labels.news'), active: true },
            ],
        })
    )
})
</script>

<template>

    <Head :title="t('common.labels.news')" />

    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">
                {{ t('common.labels.news') }}
            </h2>

            <a v-if="canCreate()" :href="route('back-office.news.create')"
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
                    :placeholder="t('admin.news.index.createdByPlaceholder')" />

                <SelectInfinityLoadingApi :form="filterForm" fieldName="news_type_id"
                    :selectedItem="filterForm.news_type_id" :apiUrl="route('search.news-types')" :multiple="false"
                    :placeholder="t('common.labels.newsType')" />

                <SelectInfinityLoadingApi :form="filterForm" fieldName="language_id"
                    :selectedItem="filterForm.language_id" :apiUrl="route('search.languages')" :multiple="false"
                    :placeholder="t('common.labels.language')" />

                <SelectInfinityLoadingApi :form="filterForm" fieldName="category_id"
                    selectedLabelKey="indentation_name" selectedValueKey="id" :selectedItem="filterForm.category_id"
                    apiLabelKey="indentation_name" apiValueKey="id" :apiUrl="route('search.category-tree')"
                    :multiple="false" :placeholder="t('common.labels.category')" />

                <SelectInfinityLoadingApi :form="filterForm" fieldName="location_id"
                    selectedLabelKey="indentation_name" selectedValueKey="id" :selectedItem="filterForm.location_id"
                    apiLabelKey="indentation_name" apiValueKey="id" :apiUrl="route('search.location-tree')"
                    :multiple="false" :placeholder="t('common.labels.location')" />

                <SelectInfinityLoadingApi :form="filterForm" fieldName="event_id"
                    :selectedItem="filterForm.event_id" :apiUrl="route('search.events')" :multiple="false"
                    :placeholder="t('common.labels.event')" />

                <input type="date" v-model="filterForm.date"
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />

                <input type="search" v-model="filterForm.search" :placeholder="t('common.placeholders.searchNews')"
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />
            </div>

            <div class="flex justify-end">
                <button type="submit" :disabled="filterForm.processing"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md flex items-center gap-2 transition disabled:opacity-60">
                    <FontAwesomeIcon v-if="filterForm.processing" icon="spinner" spin />
                    <FontAwesomeIcon v-else icon="filter" />
                    {{ filterForm.processing ? t('common.actions.applyingFilter') : t('common.actions.applyFilter') }}
                </button>
            </div>
        </form>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">#</th>
                            <th class="px-4 py-3 text-left">{{ t('common.labels.newsType') }}</th>
                            <th class="px-4 py-3 text-left">{{ t('common.labels.title') }}</th>
                            <th class="px-4 py-3 text-left">{{ t('common.labels.language') }}</th>
                            <th class="px-4 py-3 text-left">{{ t('common.labels.category') }}</th>
                            <th class="px-4 py-3 text-left">{{ t('common.labels.event') }}</th>
                            <th class="px-4 py-3 text-left">{{ t('common.labels.location') }}</th>
                            <th class="px-4 py-3 text-left">{{ t('common.labels.createdAt') }}</th>
                            <th class="px-4 py-3 text-left">{{ t('common.labels.isPublished') }}</th>
                            <th class="px-4 py-3 text-right">{{ t('common.labels.actions') }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <tr v-for="(item, index) in newsItems?.data" :key="item.id" class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">
                                {{ index + 1 }}
                            </td>

                            <td class="px-4 py-3">
                                {{ item?.news_type?.name || notAvailable }}
                            </td>

                            <td class="px-4 py-3 font-medium">
                                {{ item?.title || notAvailable }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ item?.language?.name || notAvailable }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ item?.category?.name || notAvailable }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ item?.event?.name || notAvailable }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ item?.location?.name || notAvailable }}
                            </td>

                            <td class="px-4 py-3 text-gray-500">
                                {{ item?.created_at ? formatDateTime(item.created_at) : notAvailable }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ item?.is_published ? t('common.boolean.yes') : t('common.boolean.no') }}
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <a :href="route('back-office.news.details', { slug: item.slug })"
                                        class="p-2 rounded-md text-blue-600 hover:bg-blue-50 border"
                                        :title="t('common.actions.details')">
                                        <FontAwesomeIcon icon="info" />
                                    </a>

                                    <a v-if="canUpdate(item)" :href="route('back-office.news.edit', { slug: item.slug })"
                                        class="p-2 rounded-md text-yellow-600 hover:bg-yellow-50 border"
                                        :title="t('common.actions.edit')">
                                        <FontAwesomeIcon icon="pen" />
                                    </a>

                                    <button v-if="canDelete(item)" @click="confirmDelete(item)"
                                        class="p-2 rounded-md text-red-600 hover:bg-red-50 border"
                                        :title="t('common.actions.delete')">
                                        <FontAwesomeIcon icon="trash" />
                                    </button>

                                    <button v-if="canRestore(item)" @click="confirmRestore(item)"
                                        class="p-2 rounded-md text-green-600 hover:bg-green-50 border"
                                        :title="t('common.actions.restore')">
                                        <FontAwesomeIcon icon="eye" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="!newsItems?.data || newsItems.data.length === 0">
                            <td colspan="10" class="px-4 py-6 text-center text-gray-500">
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
                                {{ t('common.modals.deleteNews') }}
                            </h3>

                            <p class="text-sm font-medium">
                                {{ deletingRow?.title }}
                            </p>

                            <p class="text-sm text-gray-500">
                                {{ t('common.modals.thisActionCanBeUndoneByRestoringNews') }}
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button @click="showDeleteModal = false"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    {{ t('common.actions.cancel') }}
                                </button>

                                <button @click="handleDelete(deletingRow)" :disabled="deleteProcessing"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2 disabled:opacity-60">
                                    <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />
                                    {{ deleteProcessing ? t('common.actions.deleting') : t('common.actions.delete') }}
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
                                {{ t('common.modals.restoreNews') }}
                            </h3>

                            <p class="text-sm font-medium">
                                {{ restoringRow?.title }}
                            </p>

                            <p class="text-sm text-gray-500">
                                {{ t('common.modals.thisActionCanBeUndoneByDeletingNews') }}
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button @click="showRestoreModal = false"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    {{ t('common.actions.cancel') }}
                                </button>

                                <button @click="handleRestore(restoringRow)" :disabled="restoreProcessing"
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm flex items-center gap-2 disabled:opacity-60">
                                    <FontAwesomeIcon v-if="restoreProcessing" icon="spinner" spin />
                                    {{ restoreProcessing ? t('common.messages.restoring') : t('common.actions.restore') }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
