<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import RecentActivities from '@/components/back-office/activity-log/RecentModelActivityLogs.vue'
import RecentQuizQuestions from '@/components/back-office/quiz-question/RecentQuizQuestions.vue'

import { ref, computed, onMounted, nextTick, inject } from 'vue'
import { Head, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'

import {
    faTrash,
    faPen,
    faEye,
    faEyeSlash,
    faSpinner,
    faCircleCheck,
    faAdd,
    faList
} from '@fortawesome/free-solid-svg-icons'

import { formatDate, formatDateTime } from '@/composables/useDateTime'

import {
    canUpdateQuiz,
    canDeleteQuiz,
    canActiveQuiz,
    canInactiveQuiz,
    canCreateQuizQuestion,
    canAccessQuizQuestion,
} from '@/composables/useUserPermissions'

import { useTranslate } from '@/composables/useTranslate'


FontAwesomeLibrary.add(
    faTrash,
    faPen,
    faEye,
    faEyeSlash,
    faSpinner,
    faCircleCheck,
    faAdd,
    faList
)

defineOptions({
    layout: Layout
})

const { t } = useTranslate()

const authUser = inject('authUser')

const showDeleteModal = ref(false)
const showStatusModal = ref(false)
const statusAction = ref(null)
const deleteProcessing = ref(false)
const activeProcessing = ref(false)
const inactiveProcessing = ref(false)

const { quiz } = defineProps({
    quiz: Object
})

const pageTitle = computed(() => `${quiz?.name} ${t('common.actions.details')}`)

const canUpdate = (quiz) => canUpdateQuiz(authUser?.value, quiz)
const canDelete = (quiz) => canDeleteQuiz(authUser?.value, quiz)
const canActive = (quiz) => canActiveQuiz(authUser?.value, quiz)
const canInactive = (quiz) => canInactiveQuiz(authUser?.value, quiz)

const canCreateQuestion = () => canCreateQuizQuestion(authUser?.value)
const canAccessQuestion = () => canAccessQuizQuestion(authUser?.value)

const closeDeleteModal = () => { showDeleteModal.value = false }

const openStatusModal = (action) => {
    statusAction.value = action
    showStatusModal.value = true
}

const closeStatusModal = () => {
    showStatusModal.value = false
    statusAction.value = null
}

const handleDelete = () => {
    if (deleteProcessing.value) return

    deleteProcessing.value = true

    intertiaJsRoute.delete(route('back-office.quizzes.delete', { slug: quiz?.slug }),
        {
            preserveScroll: true,
            onFinish: () => {
                deleteProcessing.value = false

                closeDeleteModal()
            }
        }
    )
}

const executeStatusAction = () => {
    if (activeProcessing.value || inactiveProcessing.value) return

    const isInactive = statusAction.value === 'inactive'

    if (isInactive) {
        inactiveProcessing.value = true
    } else {
        activeProcessing.value = true
    }

    intertiaJsRoute.patch(
        route(isInactive ? 'back-office.quizzes.inactive' : 'back-office.quizzes.active', { slug: quiz?.slug }), {},
        {
            preserveScroll: true,

            onSuccess: () => {
                closeStatusModal()
            },

            onFinish: () => {
                activeProcessing.value = false
                inactiveProcessing.value = false
            },

            onError: () => {
                activeProcessing.value = false
                inactiveProcessing.value = false
            }
        }
    )
}

onMounted(
    async () => {
        await nextTick()

        window.dispatchEvent(
            new CustomEvent(
                'set-breadcrumb',
                {
                    detail:
                        [
                            {
                                text: t('common.labels.quizzes'),
                                href: route('back-office.quizzes.index')
                            },
                            {
                                text: pageTitle.value,
                                active: true
                            }
                        ]
                }
            )
        )
    }
)
</script>



<template>

    <Head :title="pageTitle" />

    <div class="w-full space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">
                {{ t('admin.quizzes.details.labels.quizDetails') }}
            </h2>

            <div class="flex gap-2">
                <a v-if="canUpdate(quiz)" :href="route('back-office.quizzes.edit', { slug: quiz?.slug })"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="pen" />
                    {{ t('common.actions.edit') }}
                </a>

                <button v-if="quiz?.is_active && canInactive(quiz)" type="button" @click="openStatusModal('inactive')"
                    :disabled="inactiveProcessing"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md flex items-center gap-2 disabled:opacity-60">
                    <FontAwesomeIcon v-if="inactiveProcessing" icon="spinner" spin />
                    <FontAwesomeIcon v-else icon="eye-slash" /> {{ inactiveProcessing ?
                        t('common.actions.inactivating') :
                        t('common.actions.inactive') }}
                </button>

                <button v-if="!quiz?.is_active && canActive(quiz)" type="button" @click="openStatusModal('active')"
                    :disabled="activeProcessing"
                    class="bg-sky-500 hover:bg-sky-600 text-white px-4 py-2 rounded-md flex items-center gap-2 disabled:opacity-60">
                    <FontAwesomeIcon v-if="activeProcessing" icon="spinner" spin />
                    <FontAwesomeIcon v-else icon="eye" /> {{ activeProcessing ?
                        t('common.actions.activating') :
                        t('common.actions.active') }}
                </button>

                <button v-if="canDelete(quiz)" type="button" @click="showDeleteModal = true"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="trash" />
                    {{ t('common.actions.delete') }}
                </button>

                <a v-if="canAccessQuestion(quiz)"
                    :href="route('back-office.quizzes.quiz-questions.index', { slug: quiz?.slug })"
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="list" />
                    {{ t('common.messages.questions') }}
                </a>

                <a v-if="canCreateQuestion(quiz)"
                    :href="route('back-office.quizzes.quiz-questions.create', { slug: quiz?.slug })"
                    class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="add" />
                    {{ t('common.messages.createQuestion') }}
                </a>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('common.labels.basicInformation') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.language') }}</span>
                        <span class="font-medium">{{ quiz?.language?.name || t('common.labels.notAvailable') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.name') }}</span>
                        <span class="font-medium">
                            {{ quiz?.name || t('common.labels.notAvailable') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.brief') }}</span>
                        <span class="font-medium">
                            {{ quiz?.brief ||
                                t('common.labels.notAvailable') }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.show_bellow_event') }}</span>
                        <span :class="quiz?.show_bellow_event ? 'text-green-600' : 'text-red-500'" class="font-medium">
                            {{ quiz?.show_bellow_event ? t('common.boolean.yes') :
                                t('common.boolean.no') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.show_result') }}</span>
                        <span :class="quiz?.show_result ? 'text-green-600' : 'text-red-500'" class="font-medium">
                            {{ quiz?.show_result ? t('common.boolean.yes') :
                                t('common.boolean.no') }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.max_winner') }}</span>
                        {{ quiz?.max_winner || t('common.labels.notAvailable') }}
                    </div>


                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.status') }}</span>
                        <span :class="quiz?.is_active ? 'text-green-600' : 'text-red-500'" class="font-medium">
                            {{ quiz?.is_active ? t('common.actions.active') :
                                t('common.actions.inactive') }}
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
                        <span class="text-gray-500">{{ t('common.labels.createdAt')
                            }}</span>
                        <span class="font-medium">
                            {{ quiz?.created_at ? formatDateTime(quiz.created_at) :
                                t('common.labels.notAvailable') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.createdBy')
                            }}</span>
                        <span class="font-medium">
                            {{ quiz?.created_by?.name || t('common.labels.notAvailable')
                            }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.updatedAt')
                            }}</span>
                        <span class="font-medium">
                            {{ quiz?.updated_at ? formatDateTime(quiz.updated_at) :
                                t('common.labels.notAvailable') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('common.labels.updatedBy')
                            }}</span>
                        <span class="font-medium">
                            {{ quiz?.latest_activity_log?.caquiz?.name ||
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

            <RecentActivities :model-slug="'quiz'" :model="quiz" />
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('common.labels.quizQuestions') }}
            </h3>

            <RecentQuizQuestions :quiz="quiz" />
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
                                {{ quiz?.name }}
                            </p>

                            <p class="text-sm text-gray-500">
                                {{
                                    t('common.modals.deleteConfirmation')
                                }}
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="closeDeleteModal"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    {{ t('common.actions.cancel') }}
                                </button>

                                <button type="button" @click="handleDelete" :disabled="deleteProcessing"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                                    <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />
                                    {{ deleteProcessing ? t('common.actions.deleting') :
                                        t('common.actions.delete') }}
                                </button>
                            </div>
                        </div>
                    </Transition>

                </div>
            </Transition>

            <Transition enter-active-class="transition duration-200" leave-active-class="transition duration-150">

                <div v-if="showStatusModal"
                    class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50">

                    <div class="bg-white rounded-xl shadow-lg w-[380px] p-6 space-y-4">

                        <h3 class="text-lg font-semibold" :class="statusAction === 'inactive'
                            ? 'text-yellow-600'
                            : 'text-sky-600'
                            ">

                            {{
                                statusAction === 'inactive'
                                    ? t('common.actions.inactive')
                                    : t('common.actions.active')
                            }}

                        </h3>

                        <p>
                            {{ quiz?.name }}
                        </p>

                        <p class="text-sm text-gray-500">

                            {{
                                statusAction === 'inactive'
                                    ? 'Are you sure you want to inactive this quiz?'
                                    : 'Are you sure you want to activate this quiz?'
                            }}

                        </p>

                        <div class="flex justify-end gap-2">

                            <button @click="closeStatusModal" class="px-4 py-2 bg-gray-100 rounded-md">
                                {{
                                    t('common.actions.cancel')
                                }}
                            </button>

                            <button @click="executeStatusAction" :disabled="activeProcessing || inactiveProcessing"
                                :class="[
                                    statusAction === 'inactive'
                                        ? 'bg-yellow-500 hover:bg-yellow-600'
                                        : 'bg-sky-500 hover:bg-sky-600',
                                    'px-4 py-2 text-white rounded-md flex items-center gap-2'
                                ]">

                                <FontAwesomeIcon v-if="activeProcessing || inactiveProcessing" icon="spinner" spin />

                                {{
                                    statusAction === 'inactive'
                                        ? (
                                            inactiveProcessing
                                                ? 'Inactivating...'
                                                : t('common.actions.inactive')
                                        )
                                        : (
                                            activeProcessing
                                                ? 'Activating...'
                                                : t('common.actions.active')
                                        )
                                }}

                            </button>

                        </div>

                    </div>

                </div>

            </Transition>
        </Teleport>
    </div>
</template>
