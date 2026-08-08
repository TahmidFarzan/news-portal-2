<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import InfiniteScrollApiSelect from '@/components/common/multi-select/InfiniteScrollApiSelect.vue'
import QuizQuestionOptionManager from '@/components/back-office/quiz-question-option/QuizQuestionOptionManager.vue'
import { computed, onMounted, nextTick, inject } from 'vue'
import { Head, useForm, router as inertiaJsRoute } from '@inertiajs/vue3'
import { useTranslate } from '@/composables/useTranslate'
import { quizQuestionAnswerTypes } from '@/composables/useQuiz'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faSave,
    faSpinner,
} from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faSave, faSpinner)

defineOptions({ layout: Layout })

const { t } = useTranslate()
const authUser = inject('authUser')

const { quiz, quizQuestion } = defineProps({
    quizQuestion: Object,
    quiz: Object,
})

const isUpdate = computed(() => !!quizQuestion?.slug)

const pageTitle = computed(() => {
    return isUpdate.value
        ? `${quizQuestion?.question} ${t('common.actions.edit')}`
        : t('common.actions.create')
})

let optionIdCounter = 1
function createOption(data = {}) {
    return {
        id: `opt-${Date.now()}-${optionIdCounter++}`,
        option: data.option ?? '',
        is_correct: data.is_correct ?? false,
        position: data.position ?? null,
        redirect_back_to_same_page: true,
    }
}

const saveForm = useForm({
    question: quizQuestion?.question || null,
    answer_type: quizQuestion?.answer_type || null,
    point: quizQuestion?.point || 1,
    position: quizQuestion?.position || null,
    options: isUpdate.value ? [] : [createOption({ position: 1 })],
})

function validateForm() {
    saveForm.clearErrors()
    let valid = true
    if (!saveForm.question || saveForm.question.trim() === '') {
        saveForm.setError('question', t('common.validation.questionIsRequired'))
        valid = false
    }
    if (!saveForm.answer_type) {
        saveForm.setError('answer_type', t('common.validation.answerTypeIsRequired'))
        valid = false
    }
    if (!saveForm.point) {
        saveForm.setError('point', t('common.validation.pointIsRequired'))
        valid = false
    }
    if (!isUpdate.value) {
        if (!saveForm.options || saveForm.options.length < 1) {
            saveForm.setError('options', t('common.validation.optionsRequired'))
            valid = false
        } else {
            const emptyOption = saveForm.options.some(
                (o) => !o.option || o.option.trim() === ''
            )
            if (emptyOption) {
                saveForm.setError('options', t('common.validation.optionTextRequired'))
                valid = false
            }
            const normalized = saveForm.options
                .map((o) => (o.option || '').trim().toLowerCase())
                .filter(Boolean)
            const hasDuplicate = normalized.length !== new Set(normalized).size
            if (hasDuplicate) {
                saveForm.setError('options', t('common.validation.optionDuplicate'))
                valid = false
            }
            const correctCount = saveForm.options.filter((o) => o.is_correct).length
            if (saveForm.answer_type === quizQuestionAnswerTypes.SINGLE && correctCount !== 1) {
                saveForm.setError('options', t('common.validation.singleCorrectRequired'))
                valid = false
            }
            if (saveForm.answer_type === quizQuestionAnswerTypes.MULTIPLE && correctCount < 1) {
                saveForm.setError('options', t('common.validation.multipleCorrectRequired'))
                valid = false
            }
        }
    }
    return valid
}

function handleSave() {
    if (saveForm.processing) return
    if (!validateForm()) return
    const requestConfig = {
        preserveScroll: true,
        preserveState: true,
        forceFormData: true,
        onSuccess: () => {
            saveForm.reset()
            saveForm.clearErrors()
        },
        onError: (errors) => {
            saveForm.clearErrors()
            saveForm.setError(errors)
        },
    }
    if (isUpdate.value) {
        inertiaJsRoute.post(
            route('back-office.quizzes.quiz-questions.update', {
                slug: quiz?.slug,
                quizQuestionSlug: quizQuestion?.slug,
            }),
            { ...saveForm.data(), _method: 'patch' },
            requestConfig
        )
    } else {
        const payload = {
            ...saveForm.data(),
            options: saveForm.options.map((o) => ({
                option: o.option,
                is_correct: o.is_correct,
                position: o.position,
            })),
        }
        saveForm.transform(() => payload).post(
            route('back-office.quizzes.quiz-questions.save', { slug: quiz?.slug }),
            requestConfig
        )
    }
}

onMounted(async () => {
    await nextTick()
    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: t('common.labels.quizzes'), href: route('back-office.quizzes.index') },
                {
                    text: `${quiz?.name} ${t('common.actions.details')}`,
                    href: route('back-office.quizzes.details', { slug: quiz?.slug }),
                },
                {
                    text: t('common.labels.quizQuestions'),
                    href: route('back-office.quizzes.quiz-questions.index', {
                        slug: quiz?.slug,
                    }),
                },
                {
                    text: pageTitle.value,
                    active: true,
                },
            ],
        })
    )
})
</script>

<template>

    <Head :title="pageTitle" />
    <div class="w-full">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6">
            <form @submit.prevent="handleSave" class="space-y-6">
                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">
                        {{ t('common.labels.basicInformation') }}
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.question') }}
                                <span class="text-red-500">*</span>
                            </label>
                            <textarea v-model="saveForm.question" rows="4" :placeholder="t('common.labels.question')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.question
                                        ? 'border-red-500'
                                        : 'border-gray-300'
                                    "></textarea>
                            <p v-if="saveForm.errors.question" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.question }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.answerType') }}
                                <span class="text-red-500">*</span>
                            </label>
                            <InfiniteScrollApiSelect :form="saveForm" fieldName="answer_type"
                                :selectedItem="saveForm.answer_type"
                                :apiUrl="route('search.quiz-question-answer-types')" :multiple="false"
                                :placeholder="t('common.labels.answerType')" />
                            <p v-if="saveForm.errors.answer_type" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.answer_type }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.point') }}
                            </label>
                            <input v-model="saveForm.point" type="number" min="1"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.point
                                        ? 'border-red-500'
                                        : 'border-gray-300'
                                    " :placeholder="t('common.placeholders.point')" />
                            <p v-if="saveForm.errors.point" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.point }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.position') }}
                            </label>
                            <input v-model="saveForm.position" type="number" min="1"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.position
                                        ? 'border-red-500'
                                        : 'border-gray-300'
                                    " :placeholder="t('common.placeholders.position')" />
                            <p v-if="saveForm.errors.position" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.position }}
                            </p>
                        </div>
                    </div>
                </div>

                <QuizQuestionOptionManager :quizQuestion="quizQuestion" :isUpdate="isUpdate" :quizQuestionSaveForm="saveForm" />

                <div class="flex justify-center">
                    <button type="submit" :disabled="saveForm.processing"
                        class="bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-6 py-2 rounded-md flex items-center gap-2 transition">
                        <FontAwesomeIcon v-if="saveForm.processing" icon="spinner" spin />
                        <FontAwesomeIcon v-else icon="save" />
                        {{
                            saveForm.processing
                                ? t('common.actions.saving')
                                : t('common.actions.save')
                        }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
