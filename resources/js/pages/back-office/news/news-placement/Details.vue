<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import RecentActivities from '@/components/back-office/activity-log/RecentModelActivityLogs.vue'

import { ref, onMounted, nextTick, computed } from 'vue'
import { Head, router as inertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faTrash, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'
import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faTrash, faSpinner)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const { news = {}, newsPlacement = {} } = defineProps({
    news: {
        type: Object,
        default: () => ({}),
    },

    newsPlacement: {
        type: Object,
        default: () => ({}),
    },
})

const notAvailable = computed(() => t('common.labels.notAvailable'))

const pageTitle = computed(() => t('admin.news.newsPlacement.details.placementDetails.pageTitle'))

const handleDelete = () => {
    if (deleteProcessing.value) return

    deleteProcessing.value = true

    inertiaJsRoute.patch(
        route('back-office.news.news-placements.delete', {
            slug: news?.slug,
            newsPlacementSlug: newsPlacement?.slug,
        }),
        {},
        {
            onFinish: () => {
                deleteProcessing.value = false
            },
        }
    )
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: t('common.labels.news'), href: route('back-office.news.index') },
                {
                    text: `${news?.title} ${t('common.actions.details')}`,
                    href: route('back-office.news.details', { slug: news?.slug }),
                },
                { text: pageTitle.value, active: true },
            ],
        })
    )
})
</script>

<template>

    <Head :title="pageTitle" />

    <div class="w-full space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">
                {{ t('admin.news.newsPlacement.details.placementDetails.heading') }}
            </h2>

            <div class="flex gap-2">
                <button @click="showDeleteModal = true"
                    class="flex items-center gap-2 rounded-md bg-red-600 px-4 py-2 text-white transition hover:bg-red-700">
                    <FontAwesomeIcon icon="trash" />
                    {{ t('common.actions.delete') }}
                </button>
            </div>
        </div>

        <div class="space-y-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="border-b pb-2 text-base font-semibold">
                {{ t('common.labels.basicInformation') }}
            </h3>

            <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-1">
                <div class="space-y-2 rounded-lg border border-gray-200 p-4">
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">{{ t('common.labels.news') }}</span>

                        <span class="text-right font-medium">
                            {{ newsPlacement?.news?.title || news?.title || notAvailable }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-3">
                <div class="space-y-2 rounded-lg border border-gray-200 p-4">
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">{{ t('common.labels.page') }}</span>

                        <span class="text-right font-medium">
                            {{ newsPlacement?.page || notAvailable }}
                        </span>
                    </div>
                </div>

                <div class="space-y-2 rounded-lg border border-gray-200 p-4">
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">{{ t('common.labels.section') }}</span>

                        <span class="text-right font-medium">
                            {{ newsPlacement?.page_section || notAvailable }}
                        </span>
                    </div>
                </div>

                <div class="space-y-2 rounded-lg border border-gray-200 p-4">
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">{{ t('common.labels.category') }}</span>

                        <span class="text-right font-medium">
                            {{ newsPlacement?.category?.name || notAvailable }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-1">
                <div class="space-y-2 rounded-lg border border-gray-200 p-4">
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">{{ t('common.labels.position') }}</span>

                        <span class="text-right font-medium">
                            {{ newsPlacement?.position || notAvailable }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="border-b pb-2 text-base font-semibold">
                {{ t('common.labels.system') }}
            </h3>

            <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
                <div class="space-y-2 rounded-lg border border-gray-200 p-4">
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">{{ t('common.labels.createdAt') }}</span>

                        <span class="text-right font-medium">
                            {{ newsPlacement?.created_at ? formatDateTime(newsPlacement.created_at) : notAvailable }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">{{ t('common.labels.createdBy') }}</span>

                        <span class="text-right font-medium">
                            {{ newsPlacement?.created_by?.name || notAvailable }}
                        </span>
                    </div>
                </div>

                <div class="space-y-2 rounded-lg border border-gray-200 p-4">
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">{{ t('common.labels.updatedAt') }}</span>

                        <span class="text-right font-medium">
                            {{ newsPlacement?.updated_at ? formatDateTime(newsPlacement.updated_at) : notAvailable }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">{{ t('common.labels.updatedBy') }}</span>

                        <span class="text-right font-medium">
                            {{ newsPlacement?.latest_activity_log?.causer?.name || notAvailable }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="border-b pb-2 text-base font-semibold">
                {{ t('common.labels.activityLogs') }}
            </h3>

            <RecentActivities :model-slug="'news-placement'" :model="newsPlacement" />
        </div>

        <Teleport to="body">
            <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showDeleteModal"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
                    <Transition enter-active-class="transition ease-out duration-200"
                        enter-from-class="translate-y-4 scale-95 opacity-0"
                        enter-to-class="translate-y-0 scale-100 opacity-100"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="translate-y-0 scale-100 opacity-100"
                        leave-to-class="translate-y-4 scale-95 opacity-0">
                        <div v-if="showDeleteModal" class="w-[380px] space-y-4 rounded-xl bg-white p-6 shadow-lg">
                            <h3 class="text-lg font-semibold text-red-600">
                                {{ t('admin.news.newsPlacement.details.placementDetails.deleteModal.title') }}
                            </h3>

                            <p class="text-sm font-medium">
                                {{ t('admin.news.newsPlacement.details.placementDetails.deleteModal.message') }}
                            </p>

                            <p class="text-sm text-gray-500">
                                {{ t('common.modals.thisActionCannotBeUndone') }}
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button @click="showDeleteModal = false"
                                    class="rounded-md bg-gray-100 px-4 py-2 text-sm hover:bg-gray-200">
                                    {{ t('common.actions.cancel') }}
                                </button>

                                <button @click="handleDelete" :disabled="deleteProcessing"
                                    class="flex items-center gap-2 rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-70">
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
