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

import { canCreateNews, canEditNews, canDeleteNews, canRestoreNews } from '@/composables/useAuthUserAccessPermissions'

FontAwesomeLibrary.add(faTrash, faFilter, faInfo, faPlus, faPen, faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const authUser = inject("authUser")

const deletingRow = ref(null)
const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const restoringRow = ref(null)
const showRestoreModal = ref(false)
const restoreProcessing = ref(false)

const { newses } = defineProps({
    newses: Object,
})

const paginationOnly = computed(() => {
    if (!newses) return {}
    const { data, ...rest } = newses
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
    intertiaJsRoute.get(route('back-office.newses.index'), cleanParams, {
        replace: true,
        preserveScroll: true,
        preserveState: true,
        onFinish: () => filterForm.processing = false,
    })
}

const confirmDelete = (news) => {
    deletingRow.value = news
    showDeleteModal.value = true
}

const confirmRestore = (news) => {
    restoringRow.value = news
    showRestoreModal.value = true
}

const canCreate = () => canCreateNews(authUser?.value)
const canEdit = (news) => canEditNews(authUser?.value, news)
const canDelete = (news) => canDeleteNews(authUser?.value, news)
const canRestore = (news) => canRestoreNews(authUser?.value, news)

const handleDelete = (news) => {
    if (!news || deleteProcessing.value) return

    deleteProcessing.value = true
    intertiaJsRoute.patch(route('back-office.newses.delete', { slug: news?.slug }), {
        onFinish: () => {
            showDeleteModal.value = false
            deletingRow.value = null
            deleteProcessing.value = false
        }
    })
}

const handleRestore = (news) => {
    if (!news || restoreProcessing.value) return

    restoreProcessing.value = true
    intertiaJsRoute.patch(route('back-office.newses.restore', { slug: news?.slug }), {
        onFinish: () => {
            restoringRow.value = null
            restoreProcessing.value = false
            showRestoreModal.value = false
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
                { text: 'Newses', active: true },
            ],
        })
    )

})
</script>

<template>

    <Head title="Newses" />

    <div class="w-full space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">Newses</h2>

            <a v-if="canCreate()" :href="route('back-office.newses.create')"
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

                <MultiSelectInfinityLoadingApi :form="filterForm" fieldName="news_type_id"
                    :selectedItem="filterForm.news_type_id" :apiUrl="route('search.news-types')" :multiple="false"
                    placeholder="News type" />

                <MultiSelectInfinityLoadingApi :form="filterForm" fieldName="language_id"
                    :selectedItem="filterForm.language_id" :apiUrl="route('search.languages')" :multiple="false"
                    placeholder="Language" />

                <MultiSelectInfinityLoadingApi :form="filterForm" fieldName="category_id"
                    selectedLabelKey="indentation_name" selectedValueKey="id" :selectedItem="filterForm.category_id"
                    apiLabelKey="indentation_name" apiValueKey="id" :apiUrl="route('search.category-tree')"
                    :multiple="false" placeholder="Category" />

                <MultiSelectInfinityLoadingApi :form="filterForm" fieldName="location_id"
                    selectedLabelKey="indentation_name" selectedValueKey="id" :selectedItem="filterForm.location_id"
                    apiLabelKey="indentation_name" apiValueKey="id" :apiUrl="route('search.location-tree')"
                    :multiple="false" placeholder="Location" />

                <MultiSelectInfinityLoadingApi :form="filterForm" fieldName="event_id"
                    :selectedItem="filterForm.event_id" :apiUrl="route('search.events')" :multiple="false"
                    placeholder="Event" />

                <input type="date" v-model="filterForm.date"
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />

                <input type="search" v-model="filterForm.search" placeholder="Search news..."
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />

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
                            <th class="px-4 py-3 text-left">News type</th>
                            <th class="px-4 py-3 text-left">Title</th>
                            <th class="px-4 py-3 text-left">Language</th>
                            <th class="px-4 py-3 text-left">Category</th>
                            <th class="px-4 py-3 text-left">Event</th>
                            <th class="px-4 py-3 text-left">Location</th>
                            <th class="px-4 py-3 text-left">Created</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <tr v-for="(item, index) in newses?.data" :key="item.id" class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">{{ index + 1 }}</td>
                            <td class="px-4 py-3">{{ item?.news_type?.name }}</td>
                            <td class="px-4 py-3 font-medium">{{ item.title }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ item.language ? item.language.name : 'N/A' }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ item.category ? item.category.name : 'N/A' }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ item.event ? item.event.name : 'N/A' }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ item.location ? item.location.name : 'N/A' }}
                            </td>
                            <td class="px-4 py-3 text-gray-500">
                                {{ formatDateTime(item.created_at) }}
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">

                                    <a :href="route('back-office.newses.details', { slug: item.slug })"
                                        class="p-2 rounded-md text-blue-600 hover:bg-blue-50 border">
                                        <FontAwesomeIcon icon="info" />
                                    </a>

                                    <a v-if="canEdit(item)"
                                        :href="route('back-office.newses.edit', { slug: item.slug })"
                                        class="p-2 rounded-md text-yellow-600 hover:bg-yellow-50 border">
                                        <FontAwesomeIcon icon="pen" />
                                    </a>

                                    <button v-if="canDelete(item)" @click="confirmDelete(item)"
                                        class="p-2 rounded-md text-red-600 hover:bg-red-50 border">
                                        <FontAwesomeIcon icon="trash" />
                                    </button>

                                    <button v-if="canRestore(item)" @click="confirmRestore(item)"
                                        class="p-2 rounded-md text-red-600 hover:bg-blue-50 border">
                                        <FontAwesomeIcon icon="eye" />
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
                    class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50">

                    <Transition enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-4">
                        <div v-if="showDeleteModal" class="bg-white rounded-xl shadow-lg w-[380px] p-6 space-y-4">
                            <h3 class="text-lg font-semibold text-red-600">
                                Delete News
                            </h3>

                            <p class="text-sm font-medium">
                                {{ deletingRow?.name }}
                            </p>

                            <p class="text-sm text-gray-500">
                                This action can be undone by restoring news.
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button @click="showDeleteModal = false"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    Cancel
                                </button>

                                <button @click="handleDelete(deletingRow)" :disabled="deleteProcessing"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2">
                                    <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />
                                    Delete
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
                            <h3 class="text-lg font-semibold text-red-600">
                                Restore News
                            </h3>

                            <p class="text-sm font-medium">
                                {{ restoringRow?.name }}
                            </p>

                            <p class="text-sm text-gray-500">
                                This action can be undone by deleting news.
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button @click="showRestoreModal = false"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    Cancel
                                </button>

                                <button @click="handleRestore(restoringRow)" :disabled="restoreProcessing"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2">
                                    <FontAwesomeIcon v-if="restoreProcessing" icon="spinner" spin />
                                    Restore
                                </button>
                            </div>
                        </div>
                    </Transition>

                </div>
            </Transition>
        </Teleport>

    </div>
</template>
