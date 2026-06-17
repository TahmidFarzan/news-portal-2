<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import ModelPropertyAttributes from '@/components/back-office/activity-log/ModelPropertyAttributes.vue'

import { ref, onMounted, nextTick } from 'vue'
import { Head, router as inertiaJsRoute } from '@inertiajs/vue3'
import { useTranslate } from '@/composables/useTranslate'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faTrash, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { titleFormat } from '@/composables/useUtil'
import { formatDateTime } from '@/composables/useDateTime'

FontAwesomeLibrary.add(faTrash, faSpinner)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const { activityLog } = defineProps({
    activityLog: Object,
})

const deleting = ref(false)
const showDeleteModal = ref(false)

function handleDelete() {
    if (deleting.value) return

    deleting.value = true

    inertiaJsRoute.delete(route('back-office.activity-logs.delete', { slug: activityLog?.slug }), {
        onFinish: () => {
            deleting.value = false
            showDeleteModal.value = false
        },
    })
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                {
                    text: t('pages.back_office.activity_logs.details.navigation.activity_logs'),
                    href: route('back-office.activity-logs.index')
                },
                {
                    text: t('pages.back_office.activity_logs.details.page_title'),
                    active: true
                },
            ],
        })
    )
})
</script>

<template>

    <Head :title="t('pages.back_office.activity_logs.details.page_title')" />

    <div class="w-full space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">
                {{ t('pages.back_office.activity_logs.details.title') }}
            </h2>

            <button @click="showDeleteModal = true"
                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                <FontAwesomeIcon icon="trash" />
                {{ t('pages.back_office.activity_logs.details.actions.delete') }}
            </button>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('pages.back_office.activity_logs.details.basic_information') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">
                            {{ t('pages.back_office.activity_logs.details.log_name') }}
                        </span>

                        <span class="font-medium">
                            {{ activityLog?.log_name || t('pages.back_office.activity_logs.details.labels.not_available') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">
                            {{ t('pages.back_office.activity_logs.details.table.columns.causer') }}
                        </span>

                        <span class="font-medium">
                            {{ activityLog?.causer?.name || t('pages.back_office.activity_logs.details.labels.system') }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="text-gray-500 mb-1">
                        {{ t('pages.back_office.activity_logs.details.table.columns.description') }}
                    </div>

                    <div class="text-gray-700">
                        {{ activityLog?.description || t('pages.back_office.activity_logs.details.labels.not_available') }}
                    </div>
                </div>

            </div>
        </div>

        <div v-if="Object.keys(activityLog?.properties || {}).length"
            class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('pages.back_office.activity_logs.details.properties') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div v-for="(property, propertyIndex) in activityLog?.properties" :key="propertyIndex"
                    class="border border-gray-200 rounded-lg p-4 bg-gray-50 space-y-2">
                    <div class="text-sm font-medium border-b pb-1 text-center">
                        {{ titleFormat(propertyIndex) }}
                    </div>

                    <ModelPropertyAttributes :property="property" />
                </div>

            </div>
        </div>

        <div v-if="Object.keys(activityLog?.attribute_changes || {}).length"
            class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('pages.back_office.activity_logs.details.attribute_changes') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div v-for="(property, propertyIndex) in activityLog?.attribute_changes" :key="propertyIndex"
                    class="border border-gray-200 rounded-lg p-4 bg-gray-50 space-y-2">
                    <div class="text-sm font-medium border-b pb-1 text-center">
                        {{ titleFormat(propertyIndex) }}
                    </div>

                    <ModelPropertyAttributes :property="property" />
                </div>

            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('pages.back_office.activity_logs.details.system_information') }}
            </h3>

            <div class="text-sm border border-gray-200 rounded-lg p-4 flex justify-between">
                <span class="text-gray-500">
                    {{ t('pages.back_office.activity_logs.details.table.columns.created_at') }}
                </span>

                <span class="font-medium">
                    {{
                        activityLog?.created_at
                            ? formatDateTime(activityLog?.created_at)
                            : t('pages.back_office.activity_logs.details.labels.not_available')
                    }}
                </span>
            </div>
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
                                {{ t('pages.back_office.activity_logs.details.delete_modal.title') }}
                            </h3>

                            <p class="text-sm text-gray-500">
                                {{ t('pages.back_office.activity_logs.details.delete_modal.body') }}
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button @click="showDeleteModal = false"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    {{ t('pages.back_office.activity_logs.details.actions.cancel') }}
                                </button>

                                <button @click="handleDelete" :disabled="deleting"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2">
                                    <FontAwesomeIcon v-if="deleting" icon="spinner" spin />

                                    {{
                                        deleting
                                            ? t('pages.back_office.activity_logs.details.actions.deleting')
                                            : t('pages.back_office.activity_logs.details.actions.delete')
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
