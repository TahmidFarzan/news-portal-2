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
    canUpdateSurveyQuestion,
    canDeleteSurveyQuestion
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

const { survey, surveyQuestion } = defineProps({
    survey: Object,
    surveyQuestion: Object,
})


const canUpdate = (surveyQuestion) => canUpdateSurveyQuestion(authUser?.value, surveyQuestion)
const canDelete = (surveyQuestion) => canDeleteSurveyQuestion(authUser?.value, surveyQuestion)

const handleDelete = () => {
    if (deleteProcessing.value) return

    deleteProcessing.value = true

    inertiaJsRoute.delete(route('back-office.surveys.survey-questions.delete', { slug: survey?.slug, surveyQuestionSlug: surveyQuestion?.slug }), {
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
                { text: t('common.labels.surveys'), href: route('back-office.surveys.index') },
                { text: `${survey?.name} ${t('common.actions.details')}`, href: route('back-office.surveys.details', { slug: survey?.slug }) },
                { text: t('common.labels.surveyQuestions'), href: route('back-office.surveys.survey-questions.index', { slug: survey?.slug }), active: false },
                {
                    text: `${surveyQuestion?.question} ${t('common.actions.details')}`,
                    active: true
                }
            ],
        })
    )
})
</script>

<template>

    <Head
        :title="`${surveyQuestion?.question} ${t('admin.surveyQuestions.details.labels.surveyQuestion')}`" />

    <div class="w-full space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">
                {{ t('admin.surveyQuestions.details.labels.surveyQuestion') }}
            </h2>

            <div class="flex gap-2">
                <a v-if="canUpdate(surveyQuestion)"
                    :href="route('back-office.surveys.survey-questions.edit', { slug: survey?.slug, surveyQuestionSlug: surveyQuestion?.slug })"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="pen" />
                    {{ t('common.actions.edit') }}
                </a>

                <button v-if="canDelete(surveyQuestion)" @click="showDeleteModal = true"
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
                        <span class="text-gray-500">{{ t('common.labels.question')
                            }}</span>
                        <span class="font-medium">{{ surveyQuestion?.question ||
                            t('common.labels.notAvailable') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('admin.surveyQuestions.details.labels.surveyQuestionResult') }}
            </h3>

            <div class="flex justify-between">
                <span class="text-gray-500">{{ t('common.boolean.yes') }}</span>
                <span class="font-medium">
                    {{ surveyQuestion?.survey_question_result?.yes || 0 }}
                </span>
            </div>


            <div class="flex justify-between">
                <span class="text-gray-500">{{ t('common.boolean.no') }}</span>
                <span class="font-medium">
                    {{ surveyQuestion?.survey_question_result?.no || 0 }}
                </span>
            </div>

            <div class="flex justify-between">
                <span class="text-gray-500">{{ t('admin.surveyQuestions.details.labels.noComment') }}</span>
                <span class="font-medium">
                    {{ surveyQuestion?.survey_question_result?.no_comment || 0 }}
                </span>
            </div>

            <div class="flex justify-between">
                <span class="text-gray-500">{{ t('admin.surveyQuestions.details.labels.participateCount') }}</span>
                <span class="font-medium">
                    {{ surveyQuestion?.survey_question_result?.participate_count || 0 }}
                </span>
            </div>

        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('common.labels.systemInformation') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.createdAt')
                            }}</span>

                        <span class="font-medium">
                            {{
                                surveyQuestion?.created_at
                                    ? formatDateTime(surveyQuestion.created_at)
                                    : t('common.labels.notAvailable')
                            }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.createdBy')
                            }}</span>
                        <span class="font-medium">
                            {{ surveyQuestion?.created_by?.name ||
                                t('common.labels.notAvailable') }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.updatedAt')
                            }}</span>

                        <span class="font-medium">
                            {{
                                surveyQuestion?.updated_at
                                    ? formatDateTime(surveyQuestion.updated_at)
                                    : t('common.labels.notAvailable')
                            }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.updatedBy')
                            }}</span>
                        <span class="font-medium">
                            {{ surveyQuestion?.latest_activity_log?.causer?.name ||
                                t('common.labels.notAvailable') }}
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('common.labels.activityLogs') }}
            </h3>

            <RecentActivities :model-slug="'survey-question'" :model="surveyQuestion" />
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
                                {{ t('common.modals.deleteConfirmation') }}
                            </h3>

                            <p class="text-sm font-medium">
                                {{ surveyQuestion?.question }}
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
        </Teleport>

    </div>
</template>
