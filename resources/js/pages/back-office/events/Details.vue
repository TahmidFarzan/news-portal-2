<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import RecentActivities from '@/components/back-office/activity-log/RecentModelActivityLogs.vue'
import MediaRenderer from '@/components/common/media/MediaRenderer.vue'

import { ref, onMounted, nextTick, inject, computed } from 'vue'
import { Head, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faTrash, faPen, faEye, faEyeSlash, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'
import { canEditEvent, canDeleteEvent } from '@/composables/useAuthUserAccessPermissions'
import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faTrash, faPen, faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const authUser = inject("authUser")

const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const { event } = defineProps({
    event: {
        type: Object,
        default: () => ({})
    },
})

const pageTitle = computed(() => `${event?.name} ${t('pages.back_office.events.details.labels.details')}`)

const canEdit = (event) => canEditEvent(authUser?.value, event)
const canDelete = (event) => canDeleteEvent(authUser?.value, event)

const handleDelete = () => {
    if (deleteProcessing.value) return

    deleteProcessing.value = true

    intertiaJsRoute.delete(route('back-office.events.delete', { slug: event?.slug }), {
        onFinish: () => {
            deleteProcessing.value = false
        }
    })
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: t('pages.back_office.events.details.navigation.events'), href: route('back-office.events.index') },
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
                {{ t('pages.back_office.events.details.title') }}
            </h2>

            <div class="flex gap-2">
                <a v-if="canEdit(event)" :href="route('back-office.events.edit', { slug: event?.slug })"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="pen" />
                    {{ t('pages.back_office.events.details.table.menus.edit') }}
                </a>

                <button v-if="canDelete(event)" @click="showDeleteModal = true"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="trash" />
                    {{ t('pages.back_office.events.details.actions.delete') }}
                </button>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('pages.back_office.events.details.labels.basic_information') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.events.details.labels.name') }}</span>
                        <span class="font-medium">{{ event?.name || t('pages.back_office.events.details.labels.not_available') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.events.details.labels.position') }}</span>
                        <span class="font-medium">{{ event?.position || t('pages.back_office.events.details.labels.not_available') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.events.details.labels.language') }}</span>
                        <span class="font-medium">{{ event?.language?.name || t('pages.back_office.events.details.labels.not_available') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.events.details.labels.position') }}</span>
                        <span class="font-medium">{{ event?.position ? t('pages.back_office.events.details.labels.yes') : t('pages.back_office.events.details.labels.no') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.events.details.labels.is_current') }}</span>
                        <span class="font-medium">{{ event?.is_current ? t('pages.back_office.events.details.labels.yes') : t('pages.back_office.events.details.labels.no') }}</span>
                    </div>

                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div>
                        <div class="text-gray-500 mb-1">
                            {{ t('pages.back_office.events.details.form.brief') }}
                        </div>

                        <div class="text-gray-700">
                            {{ event?.brief || t('pages.back_office.events.details.labels.not_available') }}
                        </div>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div>
                        <div class="text-gray-500 mb-1">
                            {{ t('pages.back_office.events.details.form.desktop_banner_image') }}
                        </div>

                        <div class="text-gray-700">
                            <MediaRenderer v-if="event?.desktop_banner_image" :media="event?.desktop_banner_image" />

                            <img v-else :src="'/uploads/images/event/desktop.png'"
                                :alt="t('pages.back_office.events.details.form.desktop_banner_image')"
                                class="object-cover rounded-xl border border-gray-200" />
                        </div>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div>
                        <div class="text-gray-500 mb-1">
                            {{ t('pages.back_office.events.details.form.mobile_banner_image') }}
                        </div>

                        <div class="text-gray-700">
                            <MediaRenderer v-if="event?.mobile_banner_image" :media="event?.mobile_banner_image" />

                            <img v-else :src="'/uploads/images/event/mobile.png'"
                                :alt="t('pages.back_office.events.details.form.mobile_banner_image')"
                                class="object-cover rounded-xl border border-gray-200" />
                        </div>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="text-gray-500 mb-2">
                        {{ t('pages.back_office.events.details.seo') }}
                    </div>

                    <div class="space-y-3 text-sm">
                        <div>
                            <div class="text-gray-500 mb-1">
                                {{ t('pages.back_office.events.details.labels.title') }}
                            </div>

                            <div class="font-medium text-gray-700">
                                {{ event?.seo_title || t('pages.back_office.events.details.labels.not_available') }}
                            </div>
                        </div>

                        <div>
                            <div class="text-gray-500 mb-1">
                                {{ t('pages.back_office.events.details.form.brief') }}
                            </div>

                            <div class="font-medium text-gray-700">
                                {{ event?.seo_brief || t('pages.back_office.events.details.labels.not_available') }}
                            </div>
                        </div>

                        <div>
                            <div class="text-gray-500 mb-1">
                                {{ t('pages.back_office.events.details.form.seo_keywords') }}
                            </div>

                            <div class="font-medium text-gray-700">
                                {{ event?.seo_keywords || t('pages.back_office.events.details.labels.not_available') }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="text-gray-500 mb-2">
                        {{ t('pages.back_office.events.details.sitemap_and_feeds') }}
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">
                                {{ t('pages.back_office.events.details.sitemap_url') }}
                            </span>
                            <span class="font-medium">
                                {{ event?.sitemap_url || t('pages.back_office.events.details.labels.not_available') }}
                            </span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-500">
                                {{ t('pages.back_office.events.details.feeds_rss') }}
                            </span>
                            <span class="font-medium">
                                {{ event?.feeds_rss_url || t('pages.back_office.events.details.labels.not_available') }}
                            </span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-500">
                                {{ t('pages.back_office.events.details.feeds_atom') }}
                            </span>
                            <span class="font-medium">
                                {{ event?.feeds_atom_url || t('pages.back_office.events.details.labels.not_available') }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('pages.back_office.events.details.activity_logs.details.system_information') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.events.details.table.columns.created_at') }}</span>
                        <span class="font-medium">
                            {{ event?.created_at ? formatDateTime(event.created_at) : t('pages.back_office.events.details.labels.not_available') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.events.details.labels.created_by') }}</span>
                        <span class="font-medium">
                            {{ event?.created_by?.name || t('pages.back_office.events.details.labels.not_available') }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.events.details.labels.updated_at') }}</span>
                        <span class="font-medium">
                            {{ event?.updated_at ? formatDateTime(event.updated_at) : t('pages.back_office.events.details.labels.not_available') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.events.details.labels.updated_by') }}</span>
                        <span class="font-medium">
                            {{ event?.latest_activity_log?.causer?.name || t('pages.back_office.events.details.labels.not_available') }}
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('pages.back_office.events.details.navigation.activity_logs') }}
            </h3>

            <RecentActivities :model-slug="'event'" :model="event" />
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
                                {{ t('pages.back_office.events.details.delete_modal.title') }}
                            </h3>

                            <p class="text-sm font-medium">
                                {{ event?.name }}
                            </p>

                            <p class="text-sm text-gray-500">
                                {{ t('pages.back_office.events.details.modals.delete_confirmation_modal.irreversible_body') }}
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button @click="showDeleteModal = false"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    {{ t('pages.back_office.events.details.actions.cancel') }}
                                </button>

                                <button @click="handleDelete" :disabled="deleteProcessing"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
                                    <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />
                                    {{ deleteProcessing ? t('pages.back_office.events.details.actions.deleting') : t('pages.back_office.events.details.actions.delete') }}
                                </button>
                            </div>
                        </div>
                    </Transition>

                </div>
            </Transition>
        </Teleport>
    </div>
</template>
