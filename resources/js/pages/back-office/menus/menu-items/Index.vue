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
} from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'
import { itemListFilterParameters } from '@/composables/useDataTable'
import { fetchFromApi } from '@/composables/useSystemApi'
import { canCreateMenuItem, canEditMenuItem, canDeleteMenuItem } from '@/composables/useAuthUserAccessPermissions'
import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faTrash, faFilter, faInfo, faPlus, faPen, faSpinner)

defineOptions({ layout: Layout })

const authUser = inject('authUser')
const { t } = useTranslate()

const deletingRow = ref(null)
const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const { menuItems, menu } = defineProps({
    menu: {
        type: Object,
        default: null,
    },
    menuItems: {
        type: Object,
        default: null,
    },
})

const paginationOnly = computed(() => {
    if (!menuItems) return {}

    const { data, ...rest } = menuItems

    return rest
})

const filterForm = useForm({
    per_page: null,
    created_by_id: null,
    parent_id: null,
    language_id: null,
    model_type: null,
    date: null,
    search: null,
})

const applyFilter = () => {
    if (filterForm.processing) return

    const cleanParams = itemListFilterParameters(filterForm.data())

    inertiaJsRouter.get(route('back-office.menu-items.index'), cleanParams, {
        replace: true,
        preserveScroll: true,
        preserveState: true,
        onFinish: () => filterForm.processing = false,
    })
}

const confirmDelete = (menuItem) => {
    deletingRow.value = menuItem
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    if (deleteProcessing.value) return

    showDeleteModal.value = false
    deletingRow.value = null
}

const canCreate = () => canCreateMenuItem(authUser?.value)
const canEdit = (menuItem) => canEditMenuItem(authUser?.value, menuItem)
const canDelete = (menuItem) => canDeleteMenuItem(authUser?.value, menuItem)

const handleDelete = (menuItem) => {
    if (!menuItem || deleteProcessing.value) return

    deleteProcessing.value = true

    inertiaJsRouter.delete(
        route('back-office.menus.menu-items.delete', {
            slug: menu?.slug,
            menuItemSlug: menuItem?.slug,
        }),
        {
            onFinish: () => {
                showDeleteModal.value = false
                deletingRow.value = null
                deleteProcessing.value = false
            },
        }
    )
}

onMounted(async () => {
    const urlParams = new URLSearchParams(window.location.search)

    filterForm.per_page = urlParams.get('per_page') || null
    filterForm.created_by_id = urlParams.get('created_by_id') || null
    filterForm.language_id = urlParams.get('language_id') || null
    filterForm.date = urlParams.get('date') || null
    filterForm.search = urlParams.get('search') || null
    filterForm.model_type = urlParams.get('model_type') || null

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
                { text: t('menus.menus'), href: route('back-office.menus.index') },
                {
                    text: `${menu?.name} ${t('labels.details')}`,
                    href: route('back-office.menus.details', { slug: menu?.slug }),
                },
                { text: t('menus.details.menu_items'), active: true },
            ],
        })
    )
})
</script>

<template>
    <Head :title="t('menu_items.index.title')" />

    <div class="w-full space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">
                {{ t('menu_items.index.title') }}
            </h2>

            <a
                v-if="canCreate()"
                :href="route('back-office.menus.menu-items.create', { slug: menu?.slug })"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition"
            >
                <FontAwesomeIcon icon="plus" />
                {{ t('buttons.create') }}
            </a>
        </div>

        <form
            @submit.prevent="applyFilter"
            class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4"
        >
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <MultiSelectInfinityLoadingApi
                    :form="filterForm"
                    fieldName="per_page"
                    :selectedItem="filterForm.per_page"
                    :apiUrl="route('search.per-pages')"
                    :multiple="false"
                    :placeholder="t('labels.per_page')"
                />

                <MultiSelectInfinityLoadingApi
                    :form="filterForm"
                    fieldName="created_by_id"
                    :selectedItem="filterForm.created_by_id"
                    :apiUrl="route('search.users')"
                    :multiple="false"
                    :placeholder="t('labels.created_by')"
                />

                <MultiSelectInfinityLoadingApi
                    :form="filterForm"
                    fieldName="language_id"
                    :selectedItem="filterForm.language_id"
                    :apiUrl="route('search.languages')"
                    :multiple="false"
                    :placeholder="t('labels.language')"
                />

                <MultiSelectInfinityLoadingApi
                    :form="filterForm"
                    fieldName="model_type"
                    :selectedItem="filterForm.model_type"
                    :apiUrl="route('search.menu-item-models')"
                    :multiple="false"
                    :placeholder="t('menu_items.form.model')"
                />

                <input
                    type="date"
                    v-model="filterForm.date"
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                />

                <input
                    type="search"
                    v-model="filterForm.search"
                    :placeholder="t('menu_items.index.search_placeholder')"
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                />

            </div>

            <div class="flex justify-end">
                <button
                    type="submit"
                    :disabled="filterForm.processing"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md flex items-center gap-2 transition"
                >
                    <FontAwesomeIcon v-if="filterForm.processing" icon="spinner" spin />
                    <FontAwesomeIcon v-else icon="filter" />

                    {{ filterForm.processing ? t('menu_items.index.applying_filter') : t('menu_items.index.apply_filter') }}
                </button>
            </div>
        </form>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">

                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">
                                {{ t('table.columns.sl') }}
                            </th>

                            <th class="px-4 py-3 text-left">
                                {{ t('labels.name') }}
                            </th>

                            <th class="px-4 py-3 text-left">
                                {{ t('labels.language') }}
                            </th>

                            <th class="px-4 py-3 text-left">
                                {{ t('categories.form.parent') }}
                            </th>

                            <th class="px-4 py-3 text-left">
                                {{ t('table.columns.position') }}
                            </th>

                            <th class="px-4 py-3 text-left">
                                {{ t('table.columns.created_at') }}
                            </th>

                            <th class="px-4 py-3 text-right">
                                {{ t('table.columns.action') }}
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <tr
                            v-for="(item, index) in menuItems?.data"
                            :key="item.id"
                            class="hover:bg-gray-50 transition"
                        >
                            <td class="px-4 py-3">
                                {{ index + 1 }}
                            </td>

                            <td class="px-4 py-3 font-medium">
                                {{ item.name || t('labels.not_available') }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ item?.language?.name || t('labels.not_available') }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ item?.parent?.name || t('labels.not_available') }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ item?.position || t('labels.not_available') }}
                            </td>

                            <td class="px-4 py-3 text-gray-500">
                                {{ item.created_at ? formatDateTime(item.created_at) : t('labels.not_available') }}
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">

                                    <a
                                        :href="route('back-office.menus.menu-items.details', {
                                            slug: menu?.slug,
                                            menuItemSlug: item?.slug,
                                        })"
                                        class="p-2 rounded-md text-blue-600 hover:bg-blue-50 border"
                                        :title="t('table.menus.details')"
                                    >
                                        <FontAwesomeIcon icon="info" />
                                    </a>

                                    <a
                                        v-if="canEdit(item)"
                                        :href="route('back-office.menus.menu-items.edit', {
                                            slug: menu?.slug,
                                            menuItemSlug: item?.slug,
                                        })"
                                        class="p-2 rounded-md text-yellow-600 hover:bg-yellow-50 border"
                                        :title="t('table.menus.edit')"
                                    >
                                        <FontAwesomeIcon icon="pen" />
                                    </a>

                                    <button
                                        v-if="canDelete(item)"
                                        @click="confirmDelete(item)"
                                        class="p-2 rounded-md text-red-600 hover:bg-red-50 border"
                                        :title="t('buttons.delete')"
                                    >
                                        <FontAwesomeIcon icon="trash" />
                                    </button>

                                </div>
                            </td>
                        </tr>

                        <tr v-if="!menuItems?.data?.length">
                            <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                                {{ t('labels.no_record_found') }}
                            </td>
                        </tr>
                    </tbody>

                </table>
            </div>
        </div>

        <ModelPagination :pagination="paginationOnly" />

        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showDeleteModal"
                    class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50"
                >

                    <Transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-4"
                    >
                        <div
                            v-if="showDeleteModal"
                            class="bg-white rounded-xl shadow-lg w-[380px] p-6 space-y-4"
                        >
                            <h3 class="text-lg font-semibold text-red-600">
                                {{ t('menu_items.delete_modal.title') }}
                            </h3>

                            <p class="text-sm font-medium">
                                {{ deletingRow?.name }}
                            </p>

                            <p class="text-sm text-gray-500">
                                {{ t('delete_confirmation_modal.irreversible_body') }}
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button
                                    @click="closeDeleteModal"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm"
                                >
                                    {{ t('buttons.cancel') }}
                                </button>

                                <button
                                    @click="handleDelete(deletingRow)"
                                    :disabled="deleteProcessing"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2"
                                >
                                    <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />

                                    {{ deleteProcessing ? t('buttons.deleting') : t('buttons.delete') }}
                                </button>
                            </div>
                        </div>
                    </Transition>

                </div>
            </Transition>
        </Teleport>
    </div>
</template>
