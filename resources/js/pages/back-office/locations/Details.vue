<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import RecentActivities from '@/components/back-office/activity-log/RecentModelActivityLogs.vue'

import { ref, onMounted, nextTick, inject, computed } from 'vue'
import { Head, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faTrash, faPen, faEye, faEyeSlash, faSpinner, faCopy, faCheck } from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'
import { canUpdateLocation, canDeleteLocation } from '@/composables/useUserPermissions'
import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faTrash, faPen, faEye, faEyeSlash, faSpinner, faCopy, faCheck)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const authUser = inject("authUser")

const showDeleteModal = ref(false)
const deleteProcessing = ref(false)
const boundaryGeoJsonCopied = ref(false)

const { location } = defineProps({
    location: {
        type: Object,
        default: () => ({})
    },
})

const pageTitle = computed(() => `${location?.name} ${t('common.actions.details')}`)

const boundaryGeoJsonText = computed(() => {
    if (!location?.boundary_geojson) {
        return t('common.labels.notAvailable')
    }

    if (typeof location.boundary_geojson === 'string') {
        return location.boundary_geojson
    }

    return JSON.stringify(location.boundary_geojson, null, 2)
})

const canUpdate = (location) => canUpdateLocation(authUser?.value, location)
const canDelete = (location) => canDeleteLocation(authUser?.value, location)

const handleDelete = () => {
    if (deleteProcessing.value) return

    deleteProcessing.value = true

    intertiaJsRoute.delete(route('back-office.locations.delete', { slug: location?.slug }), {
        onFinish: () => {
            deleteProcessing.value = false
        }
    })
}

const copyBoundaryGeoJson = async () => {
    if (!boundaryGeoJsonText.value || boundaryGeoJsonText.value === t('common.labels.notAvailable')) {
        return
    }

    try {
        await navigator.clipboard.writeText(boundaryGeoJsonText.value)
    } catch {
        const textarea = document.createElement('textarea')
        textarea.value = boundaryGeoJsonText.value
        textarea.setAttribute('readonly', '')
        textarea.style.position = 'fixed'
        textarea.style.opacity = '0'

        document.body.appendChild(textarea)
        textarea.select()
        document.execCommand('copy')
        document.body.removeChild(textarea)
    }

    boundaryGeoJsonCopied.value = true

    setTimeout(() => {
        boundaryGeoJsonCopied.value = false
    }, 1500)
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: t('common.messages.locations'), href: route('back-office.locations.index') },
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
                {{ t('admin.locations.details.title') }}
            </h2>

            <div class="flex gap-2">
                <a v-if="canUpdate(location)" :href="route('back-office.locations.edit', { slug: location?.slug })"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="pen" />
                    {{ t('common.actions.edit') }}
                </a>

                <button v-if="canDelete(location)" @click="showDeleteModal = true"
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
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.name') }}</span>
                        <span class="font-medium">{{ location?.name || t('common.labels.notAvailable') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.language') }}</span>
                        <span class="font-medium">{{ location?.language?.name || t('common.labels.notAvailable') }}</span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.placeholders.parent') }}</span>
                        <span class="font-medium">{{ location?.parent?.name || t('common.labels.notAvailable') }}</span>
                    </div>

                    <div>
                        <div class="text-gray-500 mb-1">
                            {{ t('common.labels.brief') }}
                        </div>
                        <div class="text-gray-700">
                            {{ location?.brief || t('common.labels.notAvailable') }}
                        </div>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-3">
                    <div class="text-gray-500">
                        {{ t('common.labels.tree') }}
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <span v-for="node in location?.bloodline || []" :key="node.id"
                            class="bg-blue-600 text-white text-xs px-3 py-1 rounded-md">
                            {{ node.name }}
                        </span>

                        <span v-if="!location?.bloodline?.length" class="text-gray-500">
                            {{ t('common.labels.notAvailable') }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="text-gray-500 mb-2">
                        {{ t('common.labels.category') }}
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">{{ t('common.labels.title') }}</span>
                            <span class="font-medium">{{ location?.category?.name || t('common.labels.notAvailable') }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-500">{{ t('common.placeholders.parent') }}</span>
                            <span class="font-medium">{{ location?.category?.parent?.name || t('common.labels.notAvailable')
                                }}</span>
                        </div>

                        <div>
                            <div class="text-gray-500 mb-1">
                                {{ t('common.labels.brief') }}
                            </div>
                            <div class="text-gray-700">
                                {{ location?.category?.brief || t('common.labels.notAvailable') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-1 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="text-gray-500 mb-2">
                        {{ t('common.labels.mapInformation') }}
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500">{{ t('common.labels.latitude') }}</span>
                                <span class="font-medium break-all">{{ location?.latitude || t('common.labels.notAvailable')
                                    }}</span>
                            </div>

                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500">{{ t('common.labels.longitude') }}</span>
                                <span class="font-medium break-all">{{ location?.longitude || t('common.labels.notAvailable')
                                    }}</span>
                            </div>
                        </div>

                        <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500">{{ t('common.labels.boundaryNorth') }}</span>
                                <span class="font-medium break-all">{{ location?.boundary_north ||
                                    t('common.labels.notAvailable') }}</span>
                            </div>

                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500">{{ t('common.labels.boundarySouth') }}</span>
                                <span class="font-medium break-all">{{ location?.boundary_south ||
                                    t('common.labels.notAvailable') }}</span>
                            </div>

                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500">{{ t('common.labels.boundaryEast') }}</span>
                                <span class="font-medium break-all">{{ location?.boundary_east ||
                                    t('common.labels.notAvailable') }}</span>
                            </div>

                            <div class="flex justify-between gap-4">
                                <span class="text-gray-500">{{ t('common.labels.boundaryWest') }}</span>
                                <span class="font-medium break-all">{{ location?.boundary_west ||
                                    t('common.labels.notAvailable') }}</span>
                            </div>
                        </div>

                        <div class="md:col-span-2 border border-gray-200 rounded-lg p-4">
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <div class="text-gray-500">
                                    {{ t('common.labels.boundaryGeojson') }}
                                </div>

                                <button type="button"
                                    :disabled="!boundaryGeoJsonText || boundaryGeoJsonText === t('common.labels.notAvailable')"
                                    @click="copyBoundaryGeoJson"
                                    class="inline-flex items-center gap-2 rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-200 disabled:cursor-not-allowed disabled:opacity-50">
                                    <FontAwesomeIcon :icon="boundaryGeoJsonCopied ? 'check' : 'copy'" />

                                    {{ boundaryGeoJsonCopied ? t('common.labels.copied') : t('common.labels.copy') }}
                                </button>
                            </div>

                            <pre
                                class="max-h-80 overflow-auto rounded-lg bg-gray-950 p-4 text-xs leading-6 text-gray-100 whitespace-pre-wrap break-words">{{ boundaryGeoJsonText }}</pre>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="text-gray-500 mb-2">
                        {{ t('common.messages.seo') }}
                    </div>

                    <div class="space-y-3 text-sm">
                        <div>
                            <div class="text-gray-500 mb-1">
                                {{ t('common.labels.title') }}
                            </div>
                            <div class="font-medium text-gray-700">
                                {{ location?.seo_title || t('common.labels.notAvailable') }}
                            </div>
                        </div>

                        <div>
                            <div class="text-gray-500 mb-1">
                                {{ t('common.labels.brief') }}
                            </div>
                            <div class="font-medium text-gray-700">
                                {{ location?.seo_brief || t('common.labels.notAvailable') }}
                            </div>
                        </div>

                        <div>
                            <div class="text-gray-500 mb-1">
                                {{ t('common.labels.seoKeywords') }}
                            </div>
                            <div class="font-medium text-gray-700">
                                {{ location?.seo_keywords || t('common.labels.notAvailable') }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="text-gray-500 mb-2">
                        {{ t('common.messages.sitemapAndFeeds') }}
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">{{ t('common.messages.sitemapUrl') }}</span>
                            <span class="font-medium">{{ location?.sitemap_url || t('common.labels.notAvailable') }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-500">{{ t('common.messages.feedsRss') }}</span>
                            <span class="font-medium">{{ location?.feeds_rss_url || t('common.labels.notAvailable') }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-500">{{ t('common.messages.feedsAtom') }}</span>
                            <span class="font-medium">{{ location?.feeds_atom_url || t('common.labels.notAvailable') }}</span>
                        </div>
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
                            {{ location?.created_at ? formatDateTime(location.created_at) : t('common.labels.notAvailable') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.createdBy') }}</span>
                        <span class="font-medium">
                            {{ location?.created_by?.name || t('common.labels.notAvailable') }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.updatedAt') }}</span>
                        <span class="font-medium">
                            {{ location?.updated_at ? formatDateTime(location.updated_at) : t('common.labels.notAvailable') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.updatedBy') }}</span>
                        <span class="font-medium">
                            {{ location?.latest_activity_log?.causer?.name || t('common.labels.notAvailable') }}
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('common.labels.activityLogs') }}
            </h3>

            <RecentActivities :model-slug="'location'" :model="location" />
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
                                {{ t('common.modals.deleteLocation') }}
                            </h3>

                            <p class="text-sm font-medium">
                                {{ location?.name }}
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
