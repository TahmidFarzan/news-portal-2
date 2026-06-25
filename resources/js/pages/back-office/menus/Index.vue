<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import ModelPagination from '@/components/common/model/Pagination.vue'
import MultiSelectInfinityLoadingApi from '@/components/common/multi-select/InfinityLoadingApi.vue'

import { ref, computed, onMounted, nextTick, inject } from 'vue'
import { Head, useForm, router as inertiaJsRouter } from '@inertiajs/vue3'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faTrash,
    faFilter,
    faInfo,
    faPlus,
    faPen,
    faSpinner,
    faList,
} from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'
import { itemListFilterParameters } from '@/composables/useDataTable'
import { fetchFromApi } from '@/composables/useSystemApi'
import { canCreateMenu, canEditMenu, canDeleteMenu, canAccessMenuItem, canCreateMenuItem } from '@/composables/useUserPermissions'
import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faTrash, faFilter, faInfo, faPlus, faPen, faSpinner, faList)

defineOptions({ layout: Layout })

const authUser = inject('authUser')
const { t } = useTranslate()

const deletingRow = ref(null)
const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const { menus } = defineProps({
    menus: {
        type: Object,
        default: null,
    },
})

const paginationOnly = computed(() => {
    if (!menus) return {}

    const { data, ...rest } = menus

    return rest
})

const filterForm = useForm({
    per_page: null,
    created_by_id: null,
    parent_id: null,
    language_id: null,
    date: null,
    search: null,
})

const applyFilter = () => {
    if (filterForm.processing) return

    const cleanParams = itemListFilterParameters(filterForm.data())

    inertiaJsRouter.get(route('back-office.menus.index'), cleanParams, {
        replace: true,
        preserveScroll: true,
        preserveState: true,
        onFinish: () => filterForm.processing = false,
    })
}

const confirmDelete = (menu) => {
    deletingRow.value = menu
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    if (deleteProcessing.value) return

    showDeleteModal.value = false
    deletingRow.value = null
}

const canCreate = () => canCreateMenu(authUser?.value)
const canEdit = (menu) => canEditMenu(authUser?.value, menu)
const canDelete = (menu) => canDeleteMenu(authUser?.value, menu)
const canAccessMenuItem = () => canAccessMenuItem(authUser?.value)
const canCreateMenuItem = () => canCreateMenuItem(authUser?.value)

const handleDelete = (menu) => {
    if (!menu || deleteProcessing.value) return

    deleteProcessing.value = true

    inertiaJsRouter.delete(route('back-office.menus.delete', { slug: menu?.slug }), {
        onFinish: () => {
            showDeleteModal.value = false
            deletingRow.value = null
            deleteProcessing.value = false
        },
    })
}

onMounted(async () => {
    const urlParams = new URLSearchParams(window.location.search)

    filterForm.per_page = urlParams.get('per_page') || null
    filterForm.created_by_id = urlParams.get('created_by_id') || null
    filterForm.language_id = urlParams.get('language_id') || null
    filterForm.date = urlParams.get('date') || null
    filterForm.search = urlParams.get('search') || null

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
                { text: t('pages.back_office.menus.index.menus'), active: true },
            ],
        })
    )
})
</script>

<template>

    <Head :title="t('pages.back_office.menus.index.menus')" />

    <div class="w-full space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">
                {{ t('pages.back_office.menus.index.menus') }}
            </h2>

            <a v-if="canCreate()" :href="route('back-office.menus.create')"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                <FontAwesomeIcon icon="plus" />
                {{ t('pages.back_office.menus.index.actions.create') }}
            </a>
        </div>

        <form @submit.prevent="applyFilter" class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <MultiSelectInfinityLoadingApi :form="filterForm" fieldName="per_page"
                    :selectedItem="filterForm.per_page" :apiUrl="route('search.per-pages')" :multiple="false"
                    :placeholder="t('pages.back_office.menus.index.labels.per_page')" />

                <MultiSelectInfinityLoadingApi :form="filterForm" fieldName="created_by_id"
                    :selectedItem="filterForm.created_by_id" :apiUrl="route('search.users')" :multiple="false"
                    :placeholder="t('pages.back_office.menus.index.labels.created_by')" />

                <MultiSelectInfinityLoadingApi :form="filterForm" fieldName="language_id"
                    :selectedItem="filterForm.language_id" :apiUrl="route('search.languages')" :multiple="false"
                    :placeholder="t('pages.back_office.menus.index.labels.language')" />

                <input type="date" v-model="filterForm.date"
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />

                <input type="search" v-model="filterForm.search" :placeholder="t('pages.back_office.menus.index.search_placeholder')"
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />

            </div>

            <div class="flex justify-end">
                <button type="submit" :disabled="filterForm.processing"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon v-if="filterForm.processing" icon="spinner" spin />
                    <FontAwesomeIcon v-else icon="filter" />

                    {{ filterForm.processing ? t('pages.back_office.menus.index.applying_filter') :
                        t('pages.back_office.menus.index.apply_filter') }}
                </button>
            </div>
        </form>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">

                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">
                                {{ t('pages.back_office.menus.index.table.columns.sl') }}
                            </th>

                            <th class="px-4 py-3 text-left">
                                {{ t('pages.back_office.menus.index.labels.name') }}
                            </th>

                            <th class="px-4 py-3 text-left">
                                {{ t('pages.back_office.menus.index.labels.language') }}
                            </th>

                            <th class="px-4 py-3 text-left">
                                {{ t('pages.back_office.menus.index.table.columns.created_at') }}
                            </th>

                            <th class="px-4 py-3 text-right">
                                {{ t('pages.back_office.menus.index.table.columns.action') }}
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <tr v-for="(item, index) in menus?.data" :key="item.id" class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">
                                {{ index + 1 }}
                            </td>

                            <td class="px-4 py-3 font-medium">
                                {{ item.name || t('pages.back_office.menus.index.labels.not_available') }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ item?.language?.name || t('pages.back_office.menus.index.labels.not_available') }}
                            </td>

                            <td class="px-4 py-3 text-gray-500">
                                {{ item.created_at ? formatDateTime(item.created_at) : t('pages.back_office.menus.index.labels.not_available') }}
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">

                                    <a :href="route('back-office.menus.details', { slug: item.slug })"
                                        class="p-2 rounded-md text-blue-600 hover:bg-blue-50 border"
                                        :title="t('pages.back_office.menus.index.table.menus.details')">
                                        <FontAwesomeIcon icon="info" />
                                    </a>

                                    <a v-if="canEdit(item)" :href="route('back-office.menus.edit', { slug: item.slug })"
                                        class="p-2 rounded-md text-yellow-600 hover:bg-yellow-50 border"
                                        :title="t('pages.back_office.menus.index.table.menus.edit')">
                                        <FontAwesomeIcon icon="pen" />
                                    </a>

                                    <button v-if="canDelete(item)" @click="confirmDelete(item)"
                                        class="p-2 rounded-md text-red-600 hover:bg-red-50 border"
                                        :title="t('pages.back_office.menus.index.actions.delete')">
                                        <FontAwesomeIcon icon="trash" />
                                    </button>

                                    <a v-if="canAccessMenuItem()" :href="route('back-office.menus.menu-items.index', { slug: item.slug })"
                                        class="p-2 rounded-md text-gray-600 hover:bg-gray-50 border inline-flex items-center gap-1"
                                        :title="t('pages.back_office.menus.index.details.menu_items')">
                                        <FontAwesomeIcon icon="list" />
                                        {{ t('pages.back_office.menus.index.details.item') }}
                                    </a>

                                    <a v-if="canCreateMenuItem()" :href="route('back-office.menus.menu-items.create', { slug: item.slug })"
                                        class="p-2 rounded-md text-green-600 hover:bg-green-50 border inline-flex items-center gap-1"
                                        :title="t('pages.back_office.menus.index.details.add_menu_item')">
                                        <FontAwesomeIcon icon="plus" />
                                        {{ t('pages.back_office.menus.index.details.item') }}
                                    </a>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="!menus?.data?.length">
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                {{ t('pages.back_office.menus.index.labels.no_record_found') }}
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
                                {{ t('pages.back_office.menus.index.delete_modal.title') }}
                            </h3>

                            <p class="text-sm font-medium">
                                {{ deletingRow?.name }}
                            </p>

                            <p class="text-sm text-gray-500">
                                {{ t('pages.back_office.menus.index.modals.delete_confirmation_modal.irreversible_body') }}
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button @click="closeDeleteModal"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    {{ t('pages.back_office.menus.index.actions.cancel') }}
                                </button>

                                <button @click="handleDelete(deletingRow)" :disabled="deleteProcessing"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2">
                                    <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />

                                    {{ deleteProcessing ? t('pages.back_office.menus.index.actions.deleting') : t('pages.back_office.menus.index.actions.delete') }}
                                </button>
                            </div>
                        </div>
                    </Transition>

                </div>
            </Transition>
        </Teleport>
    </div>
</template>
