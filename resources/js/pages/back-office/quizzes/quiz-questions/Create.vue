<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import SelectInfinityLoadingApi from '@/components/common/multi-select/SelectInfinityLoadingApi.vue'
import { computed, onMounted, nextTick, ref, watch } from 'vue'
import { Head, useForm, router as inertiaJsRoute } from '@inertiajs/vue3'
import { useTranslate } from '@/composables/useTranslate'
import { quizQuestionTypes } from '@/composables/useQuiz'
import { VueDraggable } from 'vue-draggable-plus'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faSave,
    faEye,
    faEyeSlash,
    faSpinner,
    faPlus,
    faTrash,
    faGripVertical,
} from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faSave, faEye, faEyeSlash, faSpinner, faPlus, faTrash, faGripVertical)

defineOptions({ layout: Layout })

const { t } = useTranslate()

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
    }
}

const saveForm = useForm({
    question: quizQuestion?.question || null,
    answer_type: quizQuestion?.answer_type || null,
    point: quizQuestion?.point || 1,
    position: quizQuestion?.position || null,
    options: isUpdate.value ? [] : [createOption({ position: 1 })],
})

function reindexPositions() {
    saveForm.options.forEach((opt, index) => {
        opt.position = index + 1
    })
}

function addOption() {
    const nextPosition = saveForm.options.length + 1
    saveForm.options.push(createOption({ position: nextPosition }))
}

function removeOption(id) {
    if (saveForm.options.length <= 1) return
    const index = saveForm.options.findIndex((o) => o.id === id)
    if (index === -1) return
    saveForm.options.splice(index, 1)
    reindexPositions()
}

function onDragEnd() {
    reindexPositions()
}

function onManualPositionChange(changedId) {
    const options = [...saveForm.options]
    options.sort((a, b) => {
        const posA = Number(a.position) || 9999
        const posB = Number(b.position) || 9999
        if (posA === posB) {
            if (a.id === changedId) return -1
            if (b.id === changedId) return 1
        }
        return posA - posB
    })
    saveForm.options = options
    reindexPositions()
}

function onCorrectChange(changedId, checked) {
    if (!checked) return
    if (saveForm.answer_type === quizQuestionTypes.SINGLE) {
        saveForm.options.forEach((opt) => {
            opt.is_correct = opt.id === changedId
        })
    }
}

watch(
    () => saveForm.answer_type,
    (newType) => {
        if (newType === quizQuestionTypes.SINGLE) {
            const firstCorrect = saveForm.options.find((o) => o.is_correct)
            saveForm.options.forEach((opt) => {
                opt.is_correct = firstCorrect ? opt.id === firstCorrect.id : false
            })
        }
    }
)

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

            if (saveForm.answer_type === quizQuestionTypes.SINGLE && correctCount !== 1) {
                saveForm.setError('options', t('common.validation.singleCorrectRequired'))
                valid = false
            }

            if (saveForm.answer_type === quizQuestionTypes.MULTIPLE && correctCount < 1) {
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
                    href: route('back-office.quizzes.quiz-questions.index', { slug: quiz?.slug }),
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
                                :class="saveForm.errors.question ? 'border-red-500' : 'border-gray-300'"></textarea>
                            <p v-if="saveForm.errors.question" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.question }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.answerType') }}
                                <span class="text-red-500">*</span>
                            </label>
                            <SelectInfinityLoadingApi :form="saveForm" fieldName="answer_type"
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
                                :class="saveForm.errors.point ? 'border-red-500' : 'border-gray-300'"
                                :placeholder="t('common.placeholders.point')" />
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
                                :class="saveForm.errors.position ? 'border-red-500' : 'border-gray-300'"
                                :placeholder="t('common.placeholders.position')" />
                            <p v-if="saveForm.errors.position" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.position }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="!isUpdate" class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold">
                            {{ t('common.labels.options') }}
                            <span class="text-red-500">*</span>
                        </h3>
                        <button type="button" @click="addOption"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md text-sm flex items-center gap-2">
                            <FontAwesomeIcon icon="plus" />
                            {{ t('common.actions.add') }}
                        </button>
                    </div>

                    <p v-if="saveForm.errors.options" class="text-red-500 text-sm">
                        {{ saveForm.errors.options }}
                    </p>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b">
                                    <th class="w-10 px-2 py-2"></th>
                                    <th class="text-left px-3 py-2 font-medium">
                                        {{ t('common.labels.option') }}
                                    </th>
                                    <th class="text-center px-3 py-2 font-medium w-24">
                                        {{ t('common.labels.correct') }}
                                    </th>
                                    <th class="text-center px-3 py-2 font-medium w-28">
                                        {{ t('common.labels.position') }}
                                    </th>
                                    <th class="text-center px-3 py-2 font-medium w-20">
                                        {{ t('common.labels.action') }}
                                    </th>
                                </tr>
                            </thead>
                            <VueDraggable v-model="saveForm.options" tag="tbody" :animation="150" handle=".drag-handle"
                                @end="onDragEnd">
                                <tr v-for="opt in saveForm.options" :key="opt.id" class="border-b hover:bg-gray-50">
                                    <td class="px-2 py-2 text-center">
                                        <span
                                            class="drag-handle cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600">
                                            <FontAwesomeIcon icon="grip-vertical" />
                                        </span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input v-model="opt.option" type="text"
                                            class="w-full border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                            :placeholder="t('common.labels.option')" />
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <input type="checkbox" :checked="opt.is_correct" @change="
                                            opt.is_correct = $event.target.checked;
                                        onCorrectChange(opt.id, $event.target.checked)
                                            "
                                            class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <input v-model.number="opt.position" type="number" min="1"
                                            class="w-20 border border-gray-300 rounded-md px-2 py-1.5 text-sm text-center focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                            @change="onManualPositionChange(opt.id)" />
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button type="button" @click="removeOption(opt.id)"
                                            :disabled="saveForm.options.length <= 1"
                                            class="text-red-500 hover:text-red-700 disabled:opacity-40 disabled:cursor-not-allowed">
                                            <FontAwesomeIcon icon="trash" />
                                        </button>
                                    </td>
                                </tr>
                            </VueDraggable>
                        </table>
                    </div>
                </div>

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
