<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import RecentActivities from '@/components/back-office/activity-log/RecentModelActivityLogs.vue'

import { ref, onMounted, nextTick, inject, computed } from 'vue'
import { Head, router as inertiaJsRoute } from '@inertiajs/vue3'
import { useTranslate } from '@/composables/useTranslate'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faTrash, faPen, faEye, faEyeSlash, faSpinner, faList, faAdd } from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'

FontAwesomeLibrary.add(faTrash, faPen, faEye, faEyeSlash, faSpinner, faList, faAdd)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const authUser = inject('authUser')

const { quiz, quizResult } = defineProps({
    quiz: Object,
    quizResult: Object,
})

const deviceInfo = computed(() => {
    const info = quizResult?.quiz_participant?.device_info

    if (!info) return null

    if (typeof info === 'object') {
        return info
    }

    try {
        return JSON.parse(info)
    } catch {
        return null
    }
})

const formatKey = (key) => {
    return key
        .replace(/_/g, ' ')
        .replace(/\b\w/g, char => char.toUpperCase())
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: t('common.labels.quizzes'), href: route('back-office.quizzes.index') },
                { text: `${quiz?.name} ${t('common.actions.details')}`, href: route('back-office.quizzes.details', { slug: quiz?.slug }) },
                { text: t('common.labels.quizResults'), href: route('back-office.quizzes.quiz-questions.index', { slug: quiz?.slug }), active: false },
                {
                    text: `${t('common.actions.quizResult')} ${t('common.actions.details')}`,
                    active: true
                }
            ],
        })
    )
})
</script>

<template>

    <Head :title="`${t('common.actions.quizResult')} ${t('admin.quizResults.details.labels.quizResult')}`" />

    <div class="w-full space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">
                {{ t('admin.quizResults.details.labels.quizResult') }}
            </h2>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('common.labels.basicInformation') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-2">

                    <div class="flex justify-between">
                        <span class="text-gray-500">
                            {{ t('common.labels.point') }}
                        </span>
                        <span class="font-medium">
                            {{ quizResult?.point || t('common.labels.notAvailable') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">
                            {{ t('common.labels.duration') }}
                        </span>
                        <span class="font-medium">
                            {{ quizResult?.duration || t('common.labels.notAvailable') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('common.labels.quizParticipantInformation') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">
                            {{ t('common.labels.name') }}
                        </span>
                        <span class="font-medium">
                            {{ quizResult?.quiz_participant?.name || t('common.labels.notAvailable') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">
                            {{ t('common.labels.email') }}
                        </span>
                        <span class="font-medium">
                            {{ quizResult?.quiz_participant?.email || t('common.labels.notAvailable') }}
                        </span>
                    </div>


                    <div class="flex justify-between">
                        <span class="text-gray-500">
                            {{ t('common.labels.phone') }}
                        </span>
                        <span class="font-medium">
                            {{ quizResult?.quiz_participant?.phone || t('common.labels.notAvailable') }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">

                    <span class="text-gray-500">
                        {{ t('common.labels.address') }}
                    </span>

                    <p>
                        <span class="font-medium">
                            {{ quizResult?.quiz_participant?.address || t('common.labels.notAvailable') }}
                        </span>
                    </p>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">

                    <span class="text-gray-500">
                        {{ t('common.labels.ip') }}
                    </span>

                    <p>
                        <span class="font-medium">
                            {{ quizResult?.quiz_participant?.ip || t('common.labels.notAvailable') }}
                        </span>
                    </p>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">

                    <span class="text-gray-500">
                        {{ t('common.labels.device') }}
                    </span>

                    <div v-if="deviceInfo" class="space-y-2">
                        <div v-for="(value, key) in deviceInfo" :key="key" class="grid grid-cols-3 gap-2">
                            <span class="text-gray-500">
                                {{ formatKey(key) }}
                            </span>

                            <span class="col-span-2 font-medium break-all">
                                {{ value || t('common.labels.notAvailable') }}
                            </span>
                        </div>
                    </div>

                    <p v-else class="font-medium">
                        {{ t('common.labels.notAvailable') }}
                    </p>
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
                        <span class="text-gray-500">
                            {{ t('common.labels.createdAt') }}
                        </span>

                        <span class="font-medium">
                            {{
                                quizResult?.created_at
                                    ? formatDateTime(quizResult.created_at)
                                    : t('common.labels.notAvailable')
                            }}
                        </span>
                    </div>

                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">
                            {{
                                t('common.labels.updatedAt')
                            }}
                        </span>

                        <span class="font-medium">
                            {{
                                quizResult?.updated_at
                                    ? formatDateTime(quizResult.updated_at)
                                    : t('common.labels.notAvailable')
                            }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.updatedBy')
                        }}</span>
                        <span class="font-medium">
                            {{ quizResult?.latest_activity_log?.causer?.name ||
                                t('common.labels.notAvailable') }}
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div v-if="quizResult?.activity_logs"
            class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('common.labels.activityLogs') }}
            </h3>

            <RecentActivities :model-slug="'quiz-question'" :model="quizResult" />
        </div>

    </div>
</template>
