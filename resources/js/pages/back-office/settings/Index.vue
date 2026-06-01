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
    faPlus, faPen, faEye, faEyeSlash, faSpinner,
    faList
} from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'
import { itemListFilterParameters } from '@/composables/useDataTable'
import { fetchFromApi } from '@/composables/useSystemApi'

import { canCreateSetting, canEditSetting,} from '@/composables/useAuthUserAccessPermissions'

FontAwesomeLibrary.add(faTrash, faFilter, faInfo, faPlus, faPen, faEye, faEyeSlash, faSpinner, faList)

defineOptions({ layout: Layout })

const authUser = inject("authUser")

const { settings } = defineProps({
    settings: Object,
})

const paginationOnly = computed(() => {
    if (!settings) return {}
    const { data, ...rest } = settings
    return rest
})

const filterForm = useForm({
    per_page: null,
    date: '',
    search: '',
})

const applyFilter = () => {
    if (filterForm.processing) return

    const cleanParams = itemListFilterParameters(filterForm.data())
    intertiaJsRoute.get(route('back-office.settings.index'), cleanParams, {
        replace: true,
        preserveScroll: true,
        preserveState: true,
        onFinish: () => filterForm.processing = false,
    })
}

const canCreate = () => canCreateSetting(authUser?.value)
const canEdit = (setting) => canEditSetting(authUser?.value, setting)

onMounted(async () => {
    const urlParams = new URLSearchParams(window.location.search)

    filterForm.per_page = urlParams.get('per_page') || ''
    filterForm.date = urlParams.get('date') || ''
    filterForm.search = urlParams.get('search') || ''

    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: 'Settings', active: true },
            ],
        })
    )
})
</script>

<template>

    <Head title="Settings" />

    <div class="w-full space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">Settings</h2>
        </div>

        <form @submit.prevent="applyFilter" class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <MultiSelectInfinityLoadingApi :form="filterForm" fieldName="per_page"
                    :selectedItem="filterForm.per_page" :apiUrl="route('search.per-pages')" :multiple="false"
                    placeholder="Per page" />

                <input type="date" v-model="filterForm.date"
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />

                <input type="search" v-model="filterForm.search" placeholder="Search setting..."
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
                            <th class="px-4 py-3 text-left">Group</th>
                            <th class="px-4 py-3 text-left">Label</th>
                            <th class="px-4 py-3 text-left">Created</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <tr v-for="(item, index) in settings?.data" :key="item.id" class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">{{ index + 1 }}</td>
                            <td class="px-4 py-3 font-medium">{{ item.group }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ item?.label}}
                            </td>
                            <td class="px-4 py-3 text-gray-500">
                                {{ formatDateTime(item.created_at) }}
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">

                                    <a :href="route('back-office.settings.details', { slug: item.slug })"
                                        class="p-2 rounded-md text-blue-600 hover:bg-blue-50 border">
                                        <FontAwesomeIcon icon="info" />
                                    </a>

                                    <a v-if="canEdit(item)" :href="route('back-office.settings.edit', { slug: item.slug })"
                                        class="p-2 rounded-md text-yellow-600 hover:bg-yellow-50 border">
                                        <FontAwesomeIcon icon="pen" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <ModelPagination :pagination="paginationOnly" />
    </div>
</template>
