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
    faPlus, faPen, faEye, faEyeSlash, faSpinner,
    faList
} from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'
import { itemListFilterParameters } from '@/composables/useDataTable'
import { fetchFromApi } from '@/composables/useApiClient'

FontAwesomeLibrary.add(faTrash, faFilter, faInfo, faPlus, faPen, faEye, faEyeSlash, faSpinner, faList)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const authUser = inject('authUser')

const deletingRow = ref(null)
const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const { quiz, quizResults } = defineProps({
    quiz: Object,
    quizResults: Object,
})

const paginationOnly = computed(() => {
    if (!quizResults) return {}

    const { data, ...rest } = quizResults

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

    inertiaJsRoute.get(route('back-office.quiz-results.index'), cleanParams, {
        replace: true,
        preserveScroll: true,
        preserveState: true,
    })
}

onMounted(async () => {
    const urlParams = new URLSearchParams(window.location.search)

    filterForm.per_page = urlParams.get('per_page') || ''
    filterForm.created_by_id = urlParams.get('created_by_id') || ''
    filterForm.date = urlParams.get('date') || ''
    filterForm.search = urlParams.get('search') || ''

    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: t('common.labels.quizzes'), href: route('back-office.quizzes.index') },
                { text: `${quiz?.name} ${t('common.actions.details')}`, href: route('back-office.quizzes.details', { slug: quiz?.slug }) },
                { text: t('common.labels.quizResults'), active: true },
            ],
        })
    )
})
</script>

<template>

    <Head :title="t('common.labels.quizResults')" />

    <div class="w-full space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">
                {{ t('common.labels.quizResults') }}
            </h2>
        </div>

        <form @submit.prevent="applyFilter" class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <SelectInfinityLoadingApi :form="filterForm" fieldName="per_page" :selectedItem="filterForm.per_page"
                    :apiUrl="route('search.per-pages')" :multiple="false" :placeholder="t('common.labels.perPage')" />

                <SelectInfinityLoadingApi :form="filterForm" fieldName="created_by_id"
                    :selectedItem="filterForm.created_by_id" :apiUrl="route('search.users')" :multiple="false"
                    :placeholder="t('common.labels.createdBy')" />

                <input v-model="filterForm.date" type="date"
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />

                <input v-model="filterForm.search" type="search" :placeholder="t('common.placeholders.searchQuizResult')"
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />

            </div>

            <div class="flex justify-end">
                <button type="submit" :disabled="filterForm.processing"
                    class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white px-5 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon v-if="filterForm.processing" icon="spinner" spin />
                    <FontAwesomeIcon icon="filter" />

                    {{
                        filterForm.processing
                            ? t('common.actions.applyingFilter')
                            : t('common.actions.applyFilter')
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
                                {{ t('common.labels.quizParticipant') }}
                            </th>

                            <th class="px-4 py-3 text-left">
                                {{ t('common.labels.date') }}
                            </th>

                            <th class="px-4 py-3 text-right">
                                {{ t('common.labels.actions') }}
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <tr v-for="(item, index) in quizResults?.data" :key="item.id"
                            class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">
                                {{ index + 1 }}
                            </td>
                            <td class="px-4 py-3 font-medium">
                                {{ item?.quiz_participant?.name || t('common.labels.notAvailable') }}
                            </td>

                            <td class="px-4 py-3 text-gray-500">
                                {{ formatDateTime(item.created_at) }}
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">

                                    <a :href="route('back-office.quizzes.quiz-results.details', { slug: quiz?.slug, quizResultSlug: item.slug })"
                                        class="p-2 rounded-md text-blue-600 hover:bg-blue-50 border"
                                        :title="t('common.actions.details')">
                                        <FontAwesomeIcon icon="info" />
                                    </a>

                                </div>
                            </td>
                        </tr>

                        <tr v-if="!quizResults?.data?.length">
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                {{ t('common.labels.noRecordsFound') }}
                            </td>
                        </tr>
                    </tbody>

                </table>
            </div>
        </div>

        <ModelPagination :pagination="paginationOnly" />

    </div>
</template>
