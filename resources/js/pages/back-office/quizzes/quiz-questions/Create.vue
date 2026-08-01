<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import SelectInfinityLoadingApi from '@/components/common/multi-select/SelectInfinityLoadingApi.vue'
import { computed, onMounted, nextTick, ref, watch, inject } from 'vue'
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
    faPencil,
    faList,
    faTimes,
} from '@fortawesome/free-solid-svg-icons'
import {
    canAccessQuizQuestionOption,
    canCreateQuizQuestionOption,
    canDeleteQuizQuestionOption,
    canUpdateQuizQuestionOption,
} from '@/composables/useUserPermissions'

FontAwesomeLibrary.add(
    faSave,
    faEye,
    faEyeSlash,
    faSpinner,
    faPlus,
    faTrash,
    faGripVertical,
    faPencil,
    faList,
    faTimes
)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const authUser = inject('authUser')

const { quiz, quizQuestion } = defineProps({
    quizQuestion: Object,
    quiz: Object,
})

const isUpdate = computed(() => !!quizQuestion?.slug)

const canAccessQuestionOption = () => canAccessQuizQuestionOption(authUser?.value)
const canCreateQuestionOption = () => canCreateQuizQuestionOption(authUser?.value)
const canUpdateQuestionOption = (quizQuestionOption) =>
    canUpdateQuizQuestionOption(authUser?.value, quizQuestionOption)
const canDeleteQuestionOption = (quizQuestionOption) =>
    canDeleteQuizQuestionOption(authUser?.value, quizQuestionOption)

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
        redirect_back_to_same_page: true
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

const questionOptions = ref([])
watch(
    () => quizQuestion?.quiz_question_options,
    (val) => {
        questionOptions.value = val ? val.map((o) => ({ ...o })) : []
    },
    { immediate: true, deep: true }
)

function reindexQuestionOptionPositions() {
    questionOptions.value.forEach((opt, index) => {
        opt.position = index + 1
    })
}

function onQuestionOptionDragEnd() {
    reindexQuestionOptionPositions()
}

function onQuestionOptionManualPositionChange(changedSlug) {
    const options = [...questionOptions.value]
    options.sort((a, b) => {
        const posA = Number(a.position) || 9999
        const posB = Number(b.position) || 9999
        if (posA === posB) {
            if (a.slug === changedSlug) return -1
            if (b.slug === changedSlug) return 1
        }
        return posA - posB
    })
    questionOptions.value = options
    reindexQuestionOptionPositions()
}

const showOptionModal = ref(false)
const editingOption = ref(null)
const saveFormSaveQuizQuestionOption = useForm({
    option: null,
    is_correct: false,
    position: null,
    redirect_back_to_same_page: true
})

function openAddOptionModal() {
    editingOption.value = null
    saveFormSaveQuizQuestionOption.reset()
    saveFormSaveQuizQuestionOption.clearErrors()
    saveFormSaveQuizQuestionOption.option = ''
    saveFormSaveQuizQuestionOption.is_correct = false
    saveFormSaveQuizQuestionOption.position = questionOptions.value.length + 1
    showOptionModal.value = true
}

function openEditOptionModal(opt) {
    editingOption.value = opt
    saveFormSaveQuizQuestionOption.reset()
    saveFormSaveQuizQuestionOption.clearErrors()
    saveFormSaveQuizQuestionOption.option = opt.option ?? ''
    saveFormSaveQuizQuestionOption.is_correct = !!opt.is_correct
    saveFormSaveQuizQuestionOption.position = opt.position ?? null
    showOptionModal.value = true
}

function closeOptionModal() {
    showOptionModal.value = false
    editingOption.value = null
    saveFormSaveQuizQuestionOption.reset()
    saveFormSaveQuizQuestionOption.clearErrors()
}

function validateFormSaveQuizQuestionOption() {
    saveFormSaveQuizQuestionOption.clearErrors()
    let valid = true
    if (
        !saveFormSaveQuizQuestionOption.option ||
        saveFormSaveQuizQuestionOption.option.trim() === ''
    ) {
        saveFormSaveQuizQuestionOption.setError(
            'option',
            t('common.validation.optionTextRequired')
        )
        valid = false
    }
    if (!saveFormSaveQuizQuestionOption.position) {
        saveFormSaveQuizQuestionOption.setError(
            'position',
            t('common.validation.positionIsRequired')
        )
        valid = false
    }
    return valid
}

function handleSaveQuizQuestionOption() {
    if (saveFormSaveQuizQuestionOption.processing) return
    if (!validateFormSaveQuizQuestionOption()) return

    const requestConfig = {
        preserveScroll: true,
        preserveState: true,
        forceFormData: true,
        onSuccess: () => {
            saveFormSaveQuizQuestionOption.reset()
            saveFormSaveQuizQuestionOption.clearErrors()
            closeOptionModal()
        },
        onError: (errors) => {
            saveFormSaveQuizQuestionOption.clearErrors()
            saveFormSaveQuizQuestionOption.setError(errors)
        },
    }

    if (editingOption.value?.slug) {
        inertiaJsRoute.post(
            route('back-office.quizzes.quiz-questions.quiz-question-options.update', {
                slug: quiz?.slug,
                quizQuestionSlug: quizQuestion?.slug,
                quizQuestionOptionSlug: editingOption.value.slug,
            }),
            { ...saveFormSaveQuizQuestionOption.data(), _method: 'patch' },
            requestConfig
        )
    } else {
        saveFormSaveQuizQuestionOption.post(
            route('back-office.quizzes.quiz-questions.quiz-question-options.save', {
                slug: quiz?.slug,
                quizQuestionSlug: quizQuestion?.slug,
            }),
            requestConfig
        )
    }
}

const showDeleteModal = ref(false)
const deletingRow = ref(null)
const deleteProcessing = ref(false)

function openDeleteModal(opt) {
    deletingRow.value = opt
    showDeleteModal.value = true
}

function closeDeleteModal() {
    showDeleteModal.value = false
    deletingRow.value = null
}

const handleDelete = (quizQuestionOption) => {
    if (!quizQuestionOption || deleteProcessing.value) return
    deleteProcessing.value = true
    inertiaJsRoute.delete(
        route('back-office.quizzes.quiz-questions.quiz-question-options.delete', {
            slug: quiz?.slug,
            quizQuestionSlug: quizQuestion?.slug,
            quizQuestionOptionSlug: quizQuestionOption?.slug,
        }),
        {
            preserveScroll: true,
            onFinish: () => {
                showDeleteModal.value = false
                deletingRow.value = null
                deleteProcessing.value = false
            },
        }
    )
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
                                :class="saveForm.errors.question ? 'border-red-500' : 'border-gray-300'
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
                                :class="saveForm.errors.point ? 'border-red-500' : 'border-gray-300'
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
                                :class="saveForm.errors.position ? 'border-red-500' : 'border-gray-300'
                                    " :placeholder="t('common.placeholders.position')" />
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

                <div v-if="isUpdate" class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold">
                            {{ t('common.labels.options') }}
                        </h3>
                        <button v-if="canCreateQuestionOption()" type="button" @click="openAddOptionModal"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md text-sm flex items-center gap-2">
                            <FontAwesomeIcon icon="plus" />
                            {{ t('common.actions.add') }}
                        </button>
                    </div>

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
                                    <th class="text-center px-3 py-2 font-medium w-28">
                                        {{ t('common.labels.action') }}
                                    </th>
                                </tr>
                            </thead>
                            <VueDraggable v-model="questionOptions" tag="tbody" :animation="150" handle=".drag-handle"
                                @end="onQuestionOptionDragEnd">
                                <tr v-for="opt in questionOptions" :key="opt.slug || opt.id"
                                    class="border-b hover:bg-gray-50">
                                    <td class="px-2 py-2 text-center">
                                        <span
                                            class="drag-handle cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600">
                                            <FontAwesomeIcon icon="grip-vertical" />
                                        </span>
                                    </td>
                                    <td class="px-3 py-2">
                                        {{ opt.option }}
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <input type="checkbox" :checked="!!opt.is_correct" disabled
                                            class="w-4 h-4 rounded border-gray-300 text-blue-600" />
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <input v-model.number="opt.position" type="number" min="1"
                                            class="w-20 border border-gray-300 rounded-md px-2 py-1.5 text-sm text-center focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                            @change="onQuestionOptionManualPositionChange(opt.slug)" />
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <div class="flex items-center justify-center gap-3">
                                            <button v-if="canUpdateQuestionOption(opt)" type="button"
                                                @click="openEditOptionModal(opt)"
                                                class="text-blue-600 hover:text-blue-800">
                                                <FontAwesomeIcon icon="pencil" />
                                            </button>
                                            <button v-if="canDeleteQuestionOption(opt)" type="button"
                                                @click="openDeleteModal(opt)" class="text-red-500 hover:text-red-700">
                                                <FontAwesomeIcon icon="trash" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </VueDraggable>
                        </table>
                    </div>

                    <div class="flex justify-start pt-2">
                        <a v-if="canAccessQuestionOption()" :href="route(
                            'back-office.quizzes.quiz-questions.quiz-question-options.index',
                            {
                                slug: quiz?.slug,
                                quizQuestionSlug: quizQuestion?.slug,
                            }
                        )
                            "
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                            <FontAwesomeIcon icon="list" />
                            {{ t('common.messages.questionOptions') }}
                        </a>
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

        <div v-if="showOptionModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">
                        {{
                            editingOption
                                ? t('common.actions.edit')
                                : t('common.actions.add')
                        }}
                        {{ t('common.labels.option') }}
                    </h3>
                    <button type="button" @click="closeOptionModal" class="text-gray-400 hover:text-gray-600">
                        <FontAwesomeIcon icon="times" />
                    </button>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">
                        {{ t('common.labels.option') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <input v-model="saveFormSaveQuizQuestionOption.option" type="text"
                        class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        :class="saveFormSaveQuizQuestionOption.errors.option
                            ? 'border-red-500'
                            : 'border-gray-300'
                            " :placeholder="t('common.labels.option')" />
                    <p v-if="saveFormSaveQuizQuestionOption.errors.option" class="text-red-500 text-sm mt-1">
                        {{ saveFormSaveQuizQuestionOption.errors.option }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">
                        {{ t('common.labels.position') }}
                    </label>
                    <input v-model.number="saveFormSaveQuizQuestionOption.position" type="number" min="1"
                        class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        :class="saveFormSaveQuizQuestionOption.errors.position
                            ? 'border-red-500'
                            : 'border-gray-300'
                            " :placeholder="t('common.placeholders.position')" />
                    <p v-if="saveFormSaveQuizQuestionOption.errors.position" class="text-red-500 text-sm mt-1">
                        {{ saveFormSaveQuizQuestionOption.errors.position }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <input id="option-is-correct" v-model="saveFormSaveQuizQuestionOption.is_correct" type="checkbox"
                        class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                    <label for="option-is-correct" class="text-sm font-medium">
                        {{ t('common.labels.correct') }}
                    </label>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="closeOptionModal"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50">
                        {{ t('common.actions.cancel') }}
                    </button>
                    <button type="button" @click="handleSaveQuizQuestionOption"
                        :disabled="saveFormSaveQuizQuestionOption.processing"
                        class="bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-4 py-2 rounded-md text-sm flex items-center gap-2">
                        <FontAwesomeIcon v-if="saveFormSaveQuizQuestionOption.processing" icon="spinner" spin />
                        <FontAwesomeIcon v-else icon="save" />
                        {{
                            saveFormSaveQuizQuestionOption.processing
                                ? t('common.actions.saving')
                                : t('common.actions.save')
                        }}
                    </button>
                </div>
            </div>
        </div>

        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-sm mx-4 p-6 space-y-4">
                <h3 class="text-lg font-semibold">
                    {{ t('common.actions.delete') }}
                </h3>
                <p class="text-sm text-gray-600">
                    {{ t('common.messages.confirmDelete') }}
                </p>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="closeDeleteModal"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50"
                        :disabled="deleteProcessing">
                        {{ t('common.actions.cancel') }}
                    </button>
                    <button type="button" @click="handleDelete(deletingRow)" :disabled="deleteProcessing"
                        class="bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white px-4 py-2 rounded-md text-sm flex items-center gap-2">
                        <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />
                        <FontAwesomeIcon v-else icon="trash" />
                        {{
                            deleteProcessing
                                ? t('common.actions.deleting')
                                : t('common.actions.delete')
                        }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
