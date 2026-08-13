<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import RecentActivities from '@/components/back-office/activity-log/RecentModelActivityLogs.vue'

import { ref, onMounted, nextTick, inject, computed } from 'vue'
import { Head, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faTrash, faPen, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'
import { canUpdateGoogleAd, canDeleteGoogleAd } from '@/composables/useUserPermissions'
import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faTrash, faPen, faSpinner)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const authUser = inject("authUser")

const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const { googleAd } = defineProps({
    googleAd: {
        type: Object,
        default: () => ({})
    },
})

const pageTitle = computed(
    () => `${googleAd?.name} ${t('common.actions.details')}`
)

const formattedAdSizes = computed(() => {
    if (!Array.isArray(googleAd?.ad_sizes) || !googleAd.ad_sizes.length) {
        return t('common.labels.notAvailable')
    }

    return googleAd.ad_sizes
        .filter(size => Array.isArray(size) && size.length >= 2)
        .map(size => `${size[0]} × ${size[1]}`)
        .join(', ') || t('common.labels.notAvailable')
})

const canUpdate = (googleAd) => canUpdateGoogleAd(authUser?.value, googleAd)
const canDelete = (googleAd) => canDeleteGoogleAd(authUser?.value, googleAd)

const handleDelete = () => {
    if (deleteProcessing.value) return

    deleteProcessing.value = true

    intertiaJsRoute.delete(
        route('back-office.google-ads.delete', {
            slug: googleAd?.slug
        }),
        {
            onFinish: () => {
                deleteProcessing.value = false
            }
        }
    )
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                {
                    text: t('common.messages.googleAd'),
                    href: route('back-office.google-ads.index')
                },
                {
                    text: pageTitle.value,
                    active: true
                }
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
                {{ t('admin.googleAds.details.title') }}
            </h2>

            <div class="flex gap-2">
                <a v-if="canUpdate(googleAd)" :href="route('back-office.google-ads.edit', {
                    slug: googleAd?.slug
                })"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="pen" />
                    {{ t('common.actions.edit') }}
                </a>

                <button v-if="canDelete(googleAd)" @click="showDeleteModal = true"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="trash" />
                    {{ t('common.actions.delete') }}
                </button>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('common.labels.basicInformation') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">
                            {{ t('common.labels.name') }}
                        </span>

                        <span class="font-medium text-right">
                            {{ googleAd?.name || t('common.labels.notAvailable') }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">
                            {{ t('common.labels.page') }}
                        </span>

                        <span class="font-medium text-right">
                            {{ googleAd?.page || t('common.labels.notAvailable') }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">
                            {{ t('common.labels.type') }}
                        </span>

                        <span class="font-medium text-right">
                            {{ googleAd?.type || t('common.labels.notAvailable') }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">
                            {{ t('common.labels.placement') }}
                        </span>

                        <span class="font-medium text-right">
                            {{ googleAd?.placement || t('common.labels.notAvailable') }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">
                            {{ t('common.labels.adUnitCode') }}
                        </span>

                        <span class="font-medium text-right break-all">
                            {{ googleAd?.ad_unit_code || t('common.labels.notAvailable') }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">
                            {{ t('common.labels.gptSlotId') }}
                        </span>

                        <span class="font-medium text-right break-all">
                            {{ googleAd?.gpt_slot_id || t('common.labels.notAvailable') }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">
                            {{ t('common.labels.adSizes') }}
                        </span>

                        <span class="font-medium text-right">
                            {{ formattedAdSizes }}
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
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">
                            {{ t('common.labels.createdAt') }}
                        </span>

                        <span class="font-medium text-right">
                            {{
                                googleAd?.created_at
                                    ? formatDateTime(googleAd.created_at)
                                    : t('common.labels.notAvailable')
                            }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">
                            {{ t('common.labels.createdBy') }}
                        </span>

                        <span class="font-medium text-right">
                            {{
                                googleAd?.created_by?.name
                                || t('common.labels.notAvailable')
                            }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">
                            {{ t('common.labels.updatedAt') }}
                        </span>

                        <span class="font-medium text-right">
                            {{
                                googleAd?.updated_at
                                    ? formatDateTime(googleAd.updated_at)
                                    : t('common.labels.notAvailable')
                            }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">
                            {{ t('common.labels.updatedBy') }}
                        </span>

                        <span class="font-medium text-right">
                            {{
                                googleAd?.latest_activity_log?.causer?.name
                                || t('common.labels.notAvailable')
                            }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('common.labels.activityLogs') }}
            </h3>

            <RecentActivities :model-slug="'google-ad'" :model="googleAd" />
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
                                {{ t('common.modals.deleteGoogleAd') }}
                            </h3>

                            <p class="text-sm font-medium">
                                {{ googleAd?.name }}
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
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
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
        </Teleport>
    </div>
</template>
