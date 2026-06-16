<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import RecentActivities from '@/components/back-office/activity-log/RecentModelActivityLogs.vue'

import { ref, computed, onMounted, nextTick, inject } from 'vue'
import { Head, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faTrash, faPen, faEye, faEyeSlash, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'
import { canEditTag, canDeleteTag } from '@/composables/useAuthUserAccessPermissions'
import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faTrash, faPen, faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const authUser = inject("authUser")

const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const { tag } = defineProps({
    tag: Object,
})

const pageTitle = computed(() => `${tag?.name} ${t('labels.details')}`)

const canEdit = (tag) => canEditTag(authUser?.value, tag)
const canDelete = (tag) => canDeleteTag(authUser?.value, tag)

const handleDelete = () => {
    if (deleteProcessing.value) return

    deleteProcessing.value = true

    intertiaJsRoute.delete(route('back-office.tags.delete', { slug: tag?.slug }), {
        onFinish: () => {
            deleteProcessing.value = false
            showDeleteModal.value = false
        }
    })
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: t('labels.tags'), href: route('back-office.tags.index') },
                { text: pageTitle.value, active: true }
            ],
        })
    )
})
</script>

<template>

    <Head :title="pageTitle" />

    <div class="w-full space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">
                {{ t('tags.details.title') }}
            </h2>

            <div class="flex gap-2">
                <a v-if="canEdit(tag)" :href="route('back-office.tags.edit', { slug: tag?.slug })"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="pen" />
                    {{ t('buttons.edit') }}
                </a>

                <button v-if="canDelete(tag)" @click="showDeleteModal = true"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="trash" />
                    {{ t('buttons.delete') }}
                </button>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('labels.basic_information') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('labels.name') }}</span>
                        <span class="font-medium">{{ tag?.name || t('labels.not_available') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('labels.language') }}</span>
                        <span class="font-medium">{{ tag?.language?.name || t('labels.not_available') }}</span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div>
                        <div class="text-gray-500 mb-1">{{ t('tags.form.brief') }}</div>
                        <div class="text-gray-700">{{ tag?.brief || t('labels.not_available') }}</div>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="text-gray-500 mb-2">
                        {{ t('tags.details.trend_information') }}
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">{{ t('tags.details.is_trend') }}</span>
                            <span class="font-medium">{{ tag?.trend ? t('labels.yes') : t('labels.no') }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-500">{{ t('tags.details.is_current_trend') }}</span>
                            <span class="font-medium">{{ tag?.trend?.is_current ? t('labels.yes') : t('labels.no') }}</span>
                        </div>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="text-gray-500 mb-2">
                        {{ t('tags.details.seo') }}
                    </div>

                    <div class="space-y-3 text-sm">
                        <div>
                            <div class="text-gray-500 mb-1">{{ t('labels.title') }}</div>
                            <div class="font-medium text-gray-700">
                                {{ tag?.seo_title || t('labels.not_available') }}
                            </div>
                        </div>

                        <div>
                            <div class="text-gray-500 mb-1">{{ t('tags.form.brief') }}</div>
                            <div class="font-medium text-gray-700">
                                {{ tag?.seo_brief || t('labels.not_available') }}
                            </div>
                        </div>

                        <div>
                            <div class="text-gray-500 mb-1">{{ t('tags.form.seo_keywords') }}</div>
                            <div class="font-medium text-gray-700">
                                {{ tag?.seo_keywords || t('labels.not_available') }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="text-gray-500 mb-2">
                        {{ t('tags.details.sitemap') }}
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500">{{ t('tags.details.sitemap_url') }}</span>
                            <span class="font-medium break-all text-right">
                                {{ tag?.sitemap_url || t('labels.not_available') }}
                            </span>
                        </div>

                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500">{{ t('tags.details.feeds_rss') }}</span>
                            <span class="font-medium break-all text-right">
                                {{ tag?.feeds_rss_url || t('labels.not_available') }}
                            </span>
                        </div>

                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500">{{ t('tags.details.feeds_atom') }}</span>
                            <span class="font-medium break-all text-right">
                                {{ tag?.feeds_atom_url || t('labels.not_available') }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('labels.system_information') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('labels.created_at') }}</span>
                        <span class="font-medium">
                            {{ tag?.created_at ? formatDateTime(tag.created_at) : t('labels.not_available') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('labels.created_by') }}</span>
                        <span class="font-medium">
                            {{ tag?.created_by?.name || t('labels.not_available') }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('labels.updated_at') }}</span>
                        <span class="font-medium">
                            {{ tag?.updated_at ? formatDateTime(tag.updated_at) : t('labels.not_available') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('labels.updated_by') }}</span>
                        <span class="font-medium">
                            {{ tag?.latest_activity_log?.causer?.name || t('labels.not_available') }}
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('activity_logs.index.title') }}
            </h3>

            <RecentActivities :model-slug="'tag'" :model="tag" />
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
                                {{ t('tags.delete_modal.title') }}
                            </h3>

                            <p class="text-sm font-medium">
                                {{ tag?.name }}
                            </p>

                            <p class="text-sm text-gray-500">
                                {{ t('delete_confirmation_modal.irreversible_body') }}
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button @click="showDeleteModal = false"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    {{ t('buttons.cancel') }}
                                </button>

                                <button @click="handleDelete" :disabled="deleteProcessing"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
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
