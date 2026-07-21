<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import ModelPagination from '@/components/common/model/Pagination.vue'
import SelectInfinityLoadingApi from '@/components/common/multi-select/SelectInfinityLoadingApi.vue'

import { ref, computed, onMounted, nextTick, inject } from 'vue'
import { Head, useForm, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faTrash, faFilter, faStar, faSpinner
} from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'
import { itemListFilterParameters } from '@/composables/useDataTable'
import { fetchFromApi } from '@/composables/useApiClient'

import { canUpdateLanguage } from '@/composables/useUserPermissions'
import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faTrash, faFilter, faStar, faSpinner)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const authUser = inject("authUser")

const setingAsDefaultRow = ref(null)
const showSetAsDefaultModal = ref(false)
const setAsDefaultProcessing = ref(false)

const { languages } = defineProps({
    languages: {
        type: Object,
        default: () => ({})
    },
})

const paginationOnly = computed(() => {
    if (!languages) return {}

    const { data, ...rest } = languages
    return rest
})

const filterForm = useForm({
    per_page: null,
    created_by_id: null,
    date: '',
    search: '',
})

const applyFilter = () => {
    if (filterForm.processing) return

    const cleanParams = itemListFilterParameters(filterForm.data())

    intertiaJsRoute.get(route('back-office.languages.index'), cleanParams, {
        replace: true,
        preserveScroll: true,
        preserveState: true,
        onFinish: () => filterForm.processing = false,
    })
}

const confirmSetAsDefault = (language) => {
    setingAsDefaultRow.value = language
    showSetAsDefaultModal.value = true
}

const canSetAsDefault = (language) => canUpdateLanguage(authUser?.value, language)

const handleSetAsDefault = (language) => {
    if (!language || setAsDefaultProcessing.value) return

    setAsDefaultProcessing.value = true

    intertiaJsRoute.patch(route('back-office.languages.update', { slug: language?.slug }), {}, {
        onFinish: () => {
            showSetAsDefaultModal.value = false
            setingAsDefaultRow.value = null
            setAsDefaultProcessing.value = false
        }
    })
}

onMounted(async () => {
    const urlParams = new URLSearchParams(window.location.search)

    filterForm.per_page = urlParams.get('per_page') || ''
    filterForm.created_by_id = urlParams.get('created_by_id') || ''
    filterForm.date = urlParams.get('date') || ''
    filterForm.search = urlParams.get('search') || ''

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
                { text: t('common.messages.languages'), active: true },
            ],
        })
    )
})
</script>

<template>

    <Head :title="t('common.messages.languages')" />

    <div class="w-full space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">
                {{ t('common.messages.languages') }}
            </h2>

        </div>

        <form @submit.prevent="applyFilter" class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <SelectInfinityLoadingApi :form="filterForm" fieldName="per_page" :selectedItem="filterForm.per_page"
                    :apiUrl="route('search.per-pages')" :multiple="false" :placeholder="t('common.labels.perPage')" />

                <SelectInfinityLoadingApi :form="filterForm" fieldName="created_by_id"
                    :selectedItem="filterForm.created_by_id" :apiUrl="route('search.users')" :multiple="false"
                    :placeholder="t('common.labels.createdBy')" />

                <input type="date" v-model="filterForm.date"
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />

                <input type="search" v-model="filterForm.search"
                    :placeholder="t('admin.languages.index.searchPlaceholder')"
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />
            </div>

            <div class="flex justify-end">
                <button type="submit" :disabled="filterForm.processing"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md flex items-center gap-2 transition disabled:opacity-70 disabled:cursor-not-allowed">
                    <FontAwesomeIcon v-if="filterForm.processing" icon="spinner" spin />
                    <FontAwesomeIcon v-else icon="filter" />

                    {{
                        filterForm.processing ?
                            t('common.actions.applyingFilter') : t('common.actions.applyFilter')
                    }}
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
                            <th class="px-4 py-3 text-left">{{ t('common.labels.default') }}</th>
                            <th class="px-4 py-3 text-left">{{ t('common.labels.createdAt') }}</th>
                            <th class="px-4 py-3 text-right">{{ t('common.labels.action') }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <tr v-for="(item, index) in languages?.data || []" :key="item.id"
                            class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">{{ index + 1 }}</td>

                            <td class="px-4 py-3 font-medium">
                                {{ item.name }}
                            </td>

                            <td class="px-4 py-3 font-medium">
                                <span :class="item.is_default
                                    ? 'text-blue-600 hover:bg-blue-50'
                                    : 'text-gray-600 hover:bg-gray-50'" class="inline-flex items-center gap-1 rounded px-2 py-1 transition-colors">
                                    <FontAwesomeIcon v-if="item.is_default" icon="star" />
                                    {{ item.is_default ? t('common.boolean.yes') : t('common.boolean.no') }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-gray-500">
                                {{
                                    item.created_at ?
                                        formatDateTime(item.created_at) : t('common.labels.notAvailable')
                                }}
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">

                                    <button v-if="canSetAsDefault(item) && !item.is_default"
                                        @click="confirmSetAsDefault(item)"
                                        class="p-2 rounded-md text-blue-600 hover:bg-blue-50 border"
                                        :title="t('common.actions.setAsDefault')">
                                        <FontAwesomeIcon icon="star" />
                                    </button>

                                </div>
                            </td>
                        </tr>

                        <tr v-if="!languages?.data?.length">
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
                <div v-if="showSetAsDefaultModal"
                    class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50">

                    <Transition enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-4">
                        <div v-if="showSetAsDefaultModal" class="bg-white rounded-xl shadow-lg w-[380px] p-6 space-y-4">
                            <h3 class="text-lg font-semibold text-blue-600">
                                {{ t('common.modals.setAsDefaultLanguage') }}
                            </h3>

                            <p class="text-sm font-medium">
                                {{ setingAsDefaultRow?.name }}
                            </p>

                            <p class="text-sm text-gray-500">
                                {{ t('common.modals.setAsDefaultLanguageMessage') }}
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button @click="showSetAsDefaultModal = false"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    {{ t('common.actions.cancel') }}
                                </button>

                                <button @click="handleSetAsDefault(setingAsDefaultRow)"
                                    :disabled="setAsDefaultProcessing"
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
                                    <FontAwesomeIcon v-if="setAsDefaultProcessing" icon="spinner" spin />
                                    {{ setAsDefaultProcessing ? t('common.actions.deleting') :
                                    t('common.actions.delete') }}
                                </button>
                            </div>
                        </div>
                    </Transition>

                </div>
            </Transition>
        </Teleport>
    </div>
</template>
