<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import RecentActivities from '@/components/back-office/activity-log/RecentModelActivityLogs.vue'

import { ref, onMounted, nextTick, inject } from 'vue'
import { Head, router as inertiaJsRoute } from '@inertiajs/vue3'
import { useTranslate } from '@/composables/useTranslate'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faTrash, faPen, faEye, faEyeSlash, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'
import {
    canUpdateBreakingNews,
    canDeleteBreakingNews,
    canTrashBreakingNews,
    canRestoreBreakingNews
} from '@/composables/useUserPermissions'

FontAwesomeLibrary.add(faTrash, faPen, faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const authUser = inject('authUser')

const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const showRestoreModal = ref(false)
const restoreProcessing = ref(false)

const showTrashModal = ref(false)
const trashProcessing = ref(false)

const { breakingNews } = defineProps({
    breakingNews: Object,
})

const canUpdate = (breakingNews) => canUpdateBreakingNews(authUser?.value, breakingNews)
const canDelete = (breakingNews) => canDeleteBreakingNews(authUser?.value, breakingNews)
const canRestore = (breakingNews) => canRestoreBreakingNews(authUser?.value, breakingNews)
const canTrash = (breakingNews) => canTrashBreakingNews(authUser?.value, breakingNews)

const handleDelete = () => {
    if (deleteProcessing.value) return

    deleteProcessing.value = true

    inertiaJsRoute.delete(route('back-office.breaking-news.delete', { slug: breakingNews?.slug }), {
        onFinish: () => {
            deleteProcessing.value = false
            showDeleteModal.value = false
        }
    })
}

const handleRestore = () => {
    if (restoreProcessing.value) return

    restoreProcessing.value = true

    inertiaJsRoute.patch(route('back-office.breaking-news.restore', { slug: breakingNews?.slug }), {}, {
        onFinish: () => {
            restoreProcessing.value = false
            showRestoreModal.value = false
        }
    })
}

const handleTrash = () => {
    if (trashProcessing.value) return

    trashProcessing.value = true

    inertiaJsRoute.patch(route('back-office.breaking-news.trash', { slug: breakingNews?.slug }), {}, {
        onFinish: () => {
            showTrashModal.value = false
            trashProcessing.value = false
        }
    })
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                {
                    text: t('common.messages.breakingNews'),
                    href: route('back-office.breaking-news.index')
                },
                {
                    text: `${breakingNews?.title} ${t('common.actions.details')}`,
                    active: true
                }
            ],
        })
    )
})
</script>

<template>

    <Head :title="`${breakingNews?.title} ${t('common.actions.details')}`" />

    <div class="w-full space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">
                {{ t('admin.breakingNews.details.title') }}
            </h2>

            <div class="flex gap-2">
                <a v-if="canUpdate(breakingNews)"
                    :href="route('back-office.breaking-news.edit', { slug: breakingNews?.slug })"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="pen" />
                    {{ t('common.actions.edit') }}
                </a>

                <button v-if="canDelete(breakingNews)" @click="showDeleteModal = true"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="trash" />
                    {{ t('common.actions.delete') }}
                </button>

                <button v-if="canRestore(breakingNews)" @click="showRestoreModal = true"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="eye" />
                    {{ t('common.actions.restore') }}
                </button>

                <button v-if="!breakingNews.is_published && canTrash(breakingNews)" @click="showTrashModal = true"
                    class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="eye-slash" />
                    {{ t('common.actions.trash') }}
                </button>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('common.labels.basicInformation') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.title') }}</span>
                        <span class="font-medium">{{ breakingNews?.title || t('common.labels.notAvailable') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.language') }}</span>
                        <span class="font-medium">{{ breakingNews?.language?.name || t('common.labels.notAvailable') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.published') }}</span>
                        <span class="font-medium">
                            {{ breakingNews?.is_published ? t('common.boolean.yes') : t('common.boolean.no') }}
                        </span>
                    </div>
                </div>

                <div v-if="breakingNews?.news" class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <h3 class="text-base font-semibold border-b pb-2">
                        {{ t('common.labels.news') }}
                    </h3>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.title') }}</span>
                        <span class="font-medium">{{ breakingNews?.news?.title || t('common.labels.notAvailable') }}</span>
                    </div>

                    <div>
                        <div class="text-gray-500 mb-1">
                            {{ t('common.labels.url') }}
                        </div>

                        <div class="text-gray-700">
                            {{ breakingNews?.news?.public_url || t('common.labels.notAvailable') }}
                        </div>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.published') }}</span>
                        <span class="font-medium">
                            {{ breakingNews?.news?.is_published ? t('common.boolean.yes') : t('common.boolean.no') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('common.labels.systemInformation') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.createdAt') }}</span>

                        <span class="font-medium">
                            {{
                                breakingNews?.created_at
                                    ? formatDateTime(breakingNews.created_at)
                                    : t('common.labels.notAvailable')
                            }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.createdBy') }}</span>
                        <span class="font-medium">
                            {{ breakingNews?.created_by?.name || t('common.labels.notAvailable') }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.updatedAt') }}</span>

                        <span class="font-medium">
                            {{
                                breakingNews?.updated_at
                                    ? formatDateTime(breakingNews.updated_at)
                                    : t('common.labels.notAvailable')
                            }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.updatedBy') }}</span>
                        <span class="font-medium">
                            {{ breakingNews?.latest_activity_log?.causer?.name || t('common.labels.notAvailable') }}
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('common.labels.activityLogs') }}
            </h3>

            <RecentActivities :model-slug="'breaking-news'" :model="breakingNews" />
        </div>

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
                                {{ t('common.modals.deleteBreakingNews') }}
                            </h3>

                            <p class="text-sm font-medium">
                                {{ breakingNews?.title }}
                            </p>

                            <p class="text-sm text-gray-500">
                                {{ t('common.modals.thisActionCannotBeUndone') }}
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button @click="showDeleteModal = false"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    {{ t('common.actions.cancel') }}
                                </button>

                                <button @click="handleDelete" :disabled="deleteProcessing"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2">
                                    <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />

                                    {{
                                        deleteProcessing
                                            ? t('common.actions.deleting')
                                            : t('common.actions.delete')
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
                                {{ t('common.modals.trashBreakingNews') }}
                            </h3>

                            <p class="text-sm font-medium">
                                {{ breakingNews?.title }}
                            </p>

                            <p class="text-sm text-gray-500">
                                {{ t('common.modals.thisBreakingNewsWillBeMovedToTrash') }}
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button @click="showTrashModal = false"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    {{ t('common.actions.cancel') }}
                                </button>

                                <button @click="handleTrash" :disabled="trashProcessing"
                                    class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-md text-sm flex items-center gap-2">
                                    <FontAwesomeIcon v-if="trashProcessing" icon="spinner" spin />

                                    {{
                                        trashProcessing
                                            ? t('common.messages.trashing')
                                            : t('common.actions.trash')
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
                                {{ t('common.modals.restoreBreakingNews') }}
                            </h3>

                            <p class="text-sm font-medium">
                                {{ breakingNews?.title }}
                            </p>

                            <p class="text-sm text-gray-500">
                                {{ t('common.modals.thisActionCanBeUndoneByDeletingBreakingNews') }}
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button @click="showRestoreModal = false"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    {{ t('common.actions.cancel') }}
                                </button>

                                <button @click="handleRestore" :disabled="restoreProcessing"
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm flex items-center gap-2">
                                    <FontAwesomeIcon v-if="restoreProcessing" icon="spinner" spin />

                                    {{
                                        restoreProcessing
                                            ? t('common.messages.restoring')
                                            : t('common.actions.restore')
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
