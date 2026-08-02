<script setup>
import { ref, watch, inject, nextTick, computed } from "vue";
import { useForm, router as inertiaJsRoute } from "@inertiajs/vue3";
import { useTranslate } from "@/composables/useTranslate";
import { VueDraggable } from "vue-draggable-plus";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { library as FontAwesomeLibrary } from "@fortawesome/fontawesome-svg-core";
import {
    faPlus,
    faTrash,
    faGripVertical,
    faPencil,
    faList,
    faTimes,
    faSave,
    faSpinner,
} from "@fortawesome/free-solid-svg-icons";
import {
    canAccessQuizQuestion,
    canCreateQuizQuestion,
    canUpdateQuizQuestion,
    canDeleteQuizQuestion,
} from "@/composables/useUserPermissions";
import CreateUpdateQuizQuestionOptionTable from "@/components/back-office/quiz-question-option/CreateUpdateQuizQuestionOptionTable.vue";
import SelectInfinityLoadingApi from "@/components/common/multi-select/SelectInfinityLoadingApi.vue";
import { quizQuestionTypes } from "@/composables/useQuiz";

FontAwesomeLibrary.add(
    faPlus,
    faTrash,
    faGripVertical,
    faPencil,
    faList,
    faTimes,
    faSave,
    faSpinner
);

const props = defineProps({
    quiz: {
        type: Object,
        default: null,
    },
    isUpdate: {
        type: Boolean,
        default: false,
    },
    quizSaveForm: {
        type: Object,
        default: null,
    },
});

const { t } = useTranslate();
const authUser = inject("authUser");

const canAccessQuestion = () => canAccessQuizQuestion(authUser?.value);
const canCreateQuestion = () => canCreateQuizQuestion(authUser?.value);
const canUpdateQuestion = (quizQuestion) =>
    canUpdateQuizQuestion(authUser?.value, quizQuestion);
const canDeleteQuestion = (quizQuestion) =>
    canDeleteQuizQuestion(authUser?.value, quizQuestion);

const createEmptyOption = (position = 1) => ({
    option: "",
    is_correct: false,
    position,
});

const createEmptyQuestion = (position = 1) => ({
    question: "",
    answer_type: quizQuestionTypes.SINGLE,
    point: 1,
    position,
    options: [createEmptyOption(1), createEmptyOption(2)],
});

function reindexPositions(items) {
    items.forEach((item, index) => {
        item.position = index + 1;
    });
}

function addQuestion() {
    if (!props.quizSaveForm) return;
    const nextPos = props.quizSaveForm.questions.length + 1;
    props.quizSaveForm.questions.push(createEmptyQuestion(nextPos));
}

function removeQuestion(index) {
    if (!props.quizSaveForm || props.quizSaveForm.questions.length <= 1) return;
    props.quizSaveForm.questions.splice(index, 1);
    reindexPositions(props.quizSaveForm.questions);
}

function onCreateQuestionDragEnd() {
    if (!props.quizSaveForm) return;
    reindexPositions(props.quizSaveForm.questions);
}

function addOption(qIndex) {
    if (!props.quizSaveForm) return;
    const options = props.quizSaveForm.questions[qIndex].options;
    const nextPos = options.length + 1;
    options.push(createEmptyOption(nextPos));
}

function removeOption(qIndex, oIndex) {
    if (!props.quizSaveForm) return;
    const options = props.quizSaveForm.questions[qIndex].options;
    if (options.length <= 2) return;
    options.splice(oIndex, 1);
    reindexPositions(options);
}

function onCreateOptionDragEnd(qIndex) {
    if (!props.quizSaveForm) return;
    reindexPositions(props.quizSaveForm.questions[qIndex].options);
}

function handleAnswerTypeChange(qIndex) {
    if (!props.quizSaveForm) return;
    const question = props.quizSaveForm.questions[qIndex];
    if (question.answer_type === quizQuestionTypes.SINGLE) {
        let found = false;
        question.options.forEach((opt) => {
            if (opt.is_correct && !found) {
                found = true;
            } else {
                opt.is_correct = false;
            }
        });
    }
}

function handleCorrectChange(qIndex, oIndex) {
    if (!props.quizSaveForm) return;
    const question = props.quizSaveForm.questions[qIndex];
    if (question.answer_type === quizQuestionTypes.SINGLE) {
        question.options.forEach((opt, idx) => {
            opt.is_correct = idx === oIndex;
        });
    }
}

watch(
    () =>
        props.quizSaveForm?.questions?.map((q) => q.answer_type) ?? [],
    (newTypes, oldTypes) => {
        if (!oldTypes?.length || !props.quizSaveForm) return;
        newTypes.forEach((type, index) => {
            if (type !== oldTypes[index]) {
                handleAnswerTypeChange(index);
            }
        });
    }
);

const questions = ref([]);
const reorderProcessing = ref(false);

watch(
    () => props.quiz?.quiz_questions,
    (val) => {
        questions.value = val ? val.map((q) => ({ ...q })) : [];
    },
    { immediate: true, deep: true }
);

function reindexQuestionPositions() {
    questions.value.forEach((q, index) => {
        q.position = index + 1;
    });
}

async function submitQuestionReorder() {
    if (reorderProcessing.value || !props.quiz?.slug) return;

    reorderProcessing.value = true;

    inertiaJsRoute.post(
        route("back-office.quizzes.quiz-questions.reorder", {
            slug: props.quiz?.slug,
        }),
        {
            questions: questions.value.map((q) => ({
                slug: q?.slug,
                position: q?.position,
            })),
            redirect_back_to_same_page: true,
            _method: "patch",
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                reorderProcessing.value = false;
            },
            onError: () => {
                reorderProcessing.value = false;
            },
            onFinish: () => {
                reorderProcessing.value = false;
            },
        }
    );
}

async function onQuestionDragEnd() {
    if (reorderProcessing.value) return;
    await nextTick();
    reindexQuestionPositions();
    await submitQuestionReorder();
}

function onQuestionManualPositionChange(changedSlug) {
    const list = [...questions.value];
    list.sort((a, b) => {
        const posA = Number(a.position) || 9999;
        const posB = Number(b.position) || 9999;
        if (posA === posB) {
            if (a.slug === changedSlug) return -1;
            if (b.slug === changedSlug) return 1;
        }
        return posA - posB;
    });
    questions.value = list;
    reindexQuestionPositions();
    submitQuestionReorder();
}

const showQuestionModal = ref(false);
const editingQuestion = ref(null);

const saveQuizQuestionForm = useForm({
    question: "",
    answer_type: quizQuestionTypes.SINGLE,
    point: 1,
    position: null,
    options: [],
    redirect_back_to_same_page: true,
});

watch(
    () => saveQuizQuestionForm.answer_type,
    (newType, oldType) => {
        if (!oldType || newType === oldType) return;
        if (newType === quizQuestionTypes.SINGLE) {
            let found = false;
            saveQuizQuestionForm.options.forEach((opt) => {
                if (opt.is_correct && !found) {
                    found = true;
                } else {
                    opt.is_correct = false;
                }
            });
        }
    }
);

function openAddQuestionModal() {
    editingQuestion.value = null;
    saveQuizQuestionForm.reset();
    saveQuizQuestionForm.clearErrors();
    saveQuizQuestionForm.question = "";
    saveQuizQuestionForm.answer_type = quizQuestionTypes.SINGLE;
    saveQuizQuestionForm.point = 1;
    saveQuizQuestionForm.position = questions.value.length + 1;
    saveQuizQuestionForm.options = [
        {
            id: `opt-${Date.now()}-1`,
            option: "",
            is_correct: false,
            position: 1,
        },
        {
            id: `opt-${Date.now()}-2`,
            option: "",
            is_correct: false,
            position: 2,
        },
    ];
    showQuestionModal.value = true;
}

function openEditQuestionModal(q) {
    editingQuestion.value = q;
    saveQuizQuestionForm.reset();
    saveQuizQuestionForm.clearErrors();
    saveQuizQuestionForm.question = q.question ?? "";
    saveQuizQuestionForm.answer_type = q.answer_type ?? quizQuestionTypes.SINGLE;
    saveQuizQuestionForm.point = q.point ?? 1;
    saveQuizQuestionForm.position = q.position ?? null;
    saveQuizQuestionForm.options = [];
    showQuestionModal.value = true;
}

function closeQuestionModal() {
    showQuestionModal.value = false;
    editingQuestion.value = null;
    saveQuizQuestionForm.reset();
    saveQuizQuestionForm.clearErrors();
}

function validateQuizQuestionForm() {
    saveQuizQuestionForm.clearErrors();
    let valid = true;

    if (!saveQuizQuestionForm.question?.trim()) {
        saveQuizQuestionForm.setError(
            "question",
            t("form-requests.quiz.question.required") || "Question is required"
        );
        valid = false;
    }

    if (!saveQuizQuestionForm.answer_type) {
        saveQuizQuestionForm.setError(
            "answer_type",
            t("form-requests.quiz.answer_type.required") || "Answer type is required"
        );
        valid = false;
    }

    if (
        saveQuizQuestionForm.point === null ||
        saveQuizQuestionForm.point === undefined ||
        saveQuizQuestionForm.point === "" ||
        Number(saveQuizQuestionForm.point) < 0
    ) {
        saveQuizQuestionForm.setError(
            "point",
            t("form-requests.quiz.point.required") || "Point is required"
        );
        valid = false;
    }

    if (!editingQuestion.value) {
        if (!saveQuizQuestionForm.options || saveQuizQuestionForm.options.length < 2) {
            saveQuizQuestionForm.setError(
                "options",
                t("form-requests.quiz.options.min") || "At least 2 options required"
            );
            valid = false;
        }

        const optionTexts = saveQuizQuestionForm.options
            .map((o) => (o.option || "").trim().toLowerCase())
            .filter(Boolean);
        const uniqueTexts = new Set(optionTexts);
        if (optionTexts.length !== uniqueTexts.size) {
            saveQuizQuestionForm.setError(
                "options",
                t("form-requests.quiz.option.duplicate") ||
                "Duplicate options are not allowed"
            );
            valid = false;
        }

        saveQuizQuestionForm.options.forEach((opt, oIndex) => {
            if (!opt.option?.trim()) {
                saveQuizQuestionForm.setError(
                    `options.${oIndex}.option`,
                    t("form-requests.quiz.option.required") || "Option text is required"
                );
                valid = false;
            }
        });

        const correctCount = saveQuizQuestionForm.options.filter(
            (o) => !!o.is_correct
        ).length;

        if (
            saveQuizQuestionForm.answer_type === quizQuestionTypes.SINGLE &&
            correctCount !== 1
        ) {
            saveQuizQuestionForm.setError(
                "options",
                t("form-requests.quiz.answer.single") ||
                "Single answer type must have exactly one correct option"
            );
            valid = false;
        }

        if (
            saveQuizQuestionForm.answer_type === quizQuestionTypes.MULTIPLE &&
            correctCount < 1
        ) {
            saveQuizQuestionForm.setError(
                "options",
                t("form-requests.quiz.answer.multiple") ||
                "Multiple answer type must have at least one correct option"
            );
            valid = false;
        }
    }

    return valid;
}

function handleQuizQuestionSave() {
    if (saveQuizQuestionForm.processing) return;
    if (!validateQuizQuestionForm()) return;

    const requestConfig = {
        preserveScroll: true,
        preserveState: true,
        forceFormData: true,
        onSuccess: () => {
            saveQuizQuestionForm.reset();
            saveQuizQuestionForm.clearErrors();
            closeQuestionModal();
        },
        onError: (errors) => {
            saveQuizQuestionForm.clearErrors();
            saveQuizQuestionForm.setError(errors);
        },
    };

    if (editingQuestion.value?.slug) {
        inertiaJsRoute.post(
            route("back-office.quizzes.quiz-questions.update", {
                slug: props.quiz?.slug,
                quizQuestionSlug: editingQuestion.value.slug,
            }),
            { ...saveQuizQuestionForm.data(), _method: "patch" },
            requestConfig
        );
    } else {
        const payload = {
            ...saveQuizQuestionForm.data(),
            options: saveQuizQuestionForm.options.map((o) => ({
                option: o.option,
                is_correct: o.is_correct,
                position: o.position,
            })),
        };
        saveQuizQuestionForm.transform(() => payload).post(
            route("back-office.quizzes.quiz-questions.save", {
                slug: props.quiz?.slug,
            }),
            requestConfig
        );
    }
}

const showDeleteModal = ref(false);
const deletingRow = ref(null);
const deleteProcessing = ref(false);

function openDeleteModal(q) {
    deletingRow.value = q;
    showDeleteModal.value = true;
}

function closeDeleteModal() {
    showDeleteModal.value = false;
    deletingRow.value = null;
}

function handleQuizQuestionDelete(quizQuestion) {
    if (!quizQuestion || deleteProcessing.value) return;

    deleteProcessing.value = true;

    inertiaJsRoute.delete(
        route("back-office.quizzes.quiz-questions.delete", {
            slug: props.quiz?.slug,
            quizQuestionSlug: quizQuestion?.slug,
        }),
        {
            preserveScroll: true,
            onFinish: () => {
                showDeleteModal.value = false;
                deletingRow.value = null;
                deleteProcessing.value = false;
            },
        }
    );
}
</script>

<template>
    <div v-if="!isUpdate && quizSaveForm" class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold">
                {{ t("common.labels.questions") || "Questions" }}
                <span class="text-red-500">*</span>
            </h3>
            <button type="button" @click="addQuestion"
                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md text-sm flex items-center gap-2">
                <FontAwesomeIcon icon="plus" />
                {{ t("common.actions.add") || "Add Question" }}
            </button>
        </div>

        <p v-if="quizSaveForm.errors.questions" class="text-red-500 text-sm">
            {{ quizSaveForm.errors.questions }}
        </p>

        <VueDraggable v-model="quizSaveForm.questions" :animation="200" handle=".question-drag-handle"
            @end="onCreateQuestionDragEnd" class="space-y-4">
            <div v-for="(question, qIndex) in quizSaveForm.questions" :key="qIndex"
                class="border border-gray-200 rounded-lg p-4 bg-gray-50 space-y-4">
                <div class="flex items-start gap-3">
                    <button type="button"
                        class="question-drag-handle cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600 mt-2">
                        <FontAwesomeIcon icon="grip-vertical" />
                    </button>

                    <div class="flex-1 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-700">
                                {{ t("common.labels.question") || "Question" }}
                                #{{ qIndex + 1 }}
                            </span>
                            <button v-if="quizSaveForm.questions.length > 1" type="button"
                                @click="removeQuestion(qIndex)"
                                class="text-red-500 hover:text-red-700 text-sm flex items-center gap-1">
                                <FontAwesomeIcon icon="trash" />
                                {{ t("common.actions.remove") || "Remove" }}
                            </button>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t("common.labels.question") || "Question" }}
                                <span class="text-red-500">*</span>
                            </label>
                            <textarea v-model="question.question" rows="2"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="quizSaveForm.errors[`questions.${qIndex}.question`]
                                    ? 'border-red-500'
                                    : 'border-gray-300'
                                    " :placeholder="t('common.placeholders.question') || 'Enter question'
                                        "></textarea>
                            <p v-if="quizSaveForm.errors[`questions.${qIndex}.question`]"
                                class="text-red-500 text-sm mt-1">
                                {{ quizSaveForm.errors[`questions.${qIndex}.question`] }}
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">
                                    {{ t("common.labels.answer_type") || "Answer Type" }}
                                    <span class="text-red-500">*</span>
                                </label>
                                <SelectInfinityLoadingApi :form="question" fieldName="answer_type"
                                    :selectedItem="question.answer_type || null"
                                    :apiUrl="route('search.quiz-question-answer-types')" :error="quizSaveForm.errors[
                                        `questions.${qIndex}.answer_type`
                                    ]
                                        " :multiple="false" :placeholder="t('common.labels.answerType') || 'Answer Type'
                                            " />
                                <p v-if="
                                    quizSaveForm.errors[
                                    `questions.${qIndex}.answer_type`
                                    ]
                                " class="text-red-500 text-sm mt-1">
                                    {{
                                        quizSaveForm.errors[
                                        `questions.${qIndex}.answer_type`
                                        ]
                                    }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">
                                    {{ t("common.labels.point") || "Point" }}
                                    <span class="text-red-500">*</span>
                                </label>
                                <input v-model.number="question.point" type="number" min="0" step="0.01"
                                    class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                    :class="quizSaveForm.errors[`questions.${qIndex}.point`]
                                        ? 'border-red-500'
                                        : 'border-gray-300'
                                        " />
                                <p v-if="quizSaveForm.errors[`questions.${qIndex}.point`]"
                                    class="text-red-500 text-sm mt-1">
                                    {{ quizSaveForm.errors[`questions.${qIndex}.point`] }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">
                                    {{ t("common.labels.position") || "Position" }}
                                </label>
                                <input v-model.number="question.position" type="number" min="1"
                                    class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                    :class="quizSaveForm.errors[
                                        `questions.${qIndex}.position`
                                    ]
                                        ? 'border-red-500'
                                        : 'border-gray-300'
                                        " />
                                <p v-if="
                                    quizSaveForm.errors[
                                    `questions.${qIndex}.position`
                                    ]
                                " class="text-red-500 text-sm mt-1">
                                    {{
                                        quizSaveForm.errors[
                                        `questions.${qIndex}.position`
                                        ]
                                    }}
                                </p>
                            </div>
                        </div>

                        <div class="border-t pt-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-sm font-semibold text-gray-700">
                                    {{ t("common.labels.options") || "Options" }}
                                    <span class="text-red-500">*</span>
                                </h4>
                                <button type="button" @click="addOption(qIndex)"
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-2.5 py-1 rounded text-xs flex items-center gap-1">
                                    <FontAwesomeIcon icon="plus" />
                                    {{ t("common.actions.add") || "Add Option" }}
                                </button>
                            </div>

                            <p v-if="quizSaveForm.errors[`questions.${qIndex}.options`]" class="text-red-500 text-sm">
                                {{ quizSaveForm.errors[`questions.${qIndex}.options`] }}
                            </p>

                            <VueDraggable v-model="question.options" :animation="200" handle=".option-drag-handle"
                                @end="() => onCreateOptionDragEnd(qIndex)" class="space-y-2">
                                <div v-for="(option, oIndex) in question.options" :key="oIndex"
                                    class="flex items-start gap-2 bg-white border border-gray-200 rounded-md p-3">
                                    <button type="button"
                                        class="option-drag-handle cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600 mt-2">
                                        <FontAwesomeIcon icon="grip-vertical" />
                                    </button>

                                    <div class="flex-1 grid grid-cols-1 md:grid-cols-12 gap-2 items-start">
                                        <div class="md:col-span-6">
                                            <label class="block text-xs font-medium mb-1">
                                                {{ t("common.labels.option") || "Option" }}
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <input v-model="option.option" type="text"
                                                class="w-full border rounded-md px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                                :class="quizSaveForm.errors[
                                                    `questions.${qIndex}.options.${oIndex}.option`
                                                ]
                                                    ? 'border-red-500'
                                                    : 'border-gray-300'
                                                    " :placeholder="t('common.placeholders.option') ||
                                                        'Option text'
                                                        " />
                                            <p v-if="
                                                quizSaveForm.errors[
                                                `questions.${qIndex}.options.${oIndex}.option`
                                                ]
                                            " class="text-red-500 text-xs mt-1">
                                                {{
                                                    quizSaveForm.errors[
                                                    `questions.${qIndex}.options.${oIndex}.option`
                                                    ]
                                                }}
                                            </p>
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="block text-xs font-medium mb-1">
                                                {{
                                                    t("common.labels.position") ||
                                                    "Position"
                                                }}
                                            </label>
                                            <input v-model.number="option.position" type="number" min="1"
                                                class="w-full border rounded-md px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                                :class="quizSaveForm.errors[
                                                    `questions.${qIndex}.options.${oIndex}.position`
                                                ]
                                                    ? 'border-red-500'
                                                    : 'border-gray-300'
                                                    " />
                                        </div>

                                        <div class="md:col-span-3 flex items-end pb-1">
                                            <label class="inline-flex cursor-pointer items-center gap-2">
                                                <input type="checkbox" :checked="option.is_correct" @change="
                                                    handleCorrectChange(qIndex, oIndex)
                                                    " class="peer sr-only" />
                                                <span
                                                    class="relative h-6 w-11 rounded-full bg-gray-300 transition after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-green-600 peer-checked:after:translate-x-5"></span>
                                                <span class="text-xs text-gray-600">
                                                    {{
                                                        t("common.labels.correct") ||
                                                        "Correct"
                                                    }}
                                                </span>
                                            </label>
                                        </div>

                                        <div class="md:col-span-1 flex items-end justify-end pb-1">
                                            <button v-if="question.options.length > 2" type="button"
                                                @click="removeOption(qIndex, oIndex)"
                                                class="text-red-500 hover:text-red-700 p-1" :title="t('common.actions.remove') || 'Remove'
                                                    ">
                                                <FontAwesomeIcon icon="trash" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </VueDraggable>
                        </div>
                    </div>
                </div>
            </div>
        </VueDraggable>
    </div>

    <div v-else-if="isUpdate" class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold">
                {{ t("common.labels.questions") || "Questions" }}
            </h3>
            <button v-if="canCreateQuestion()" type="button" @click="openAddQuestionModal"
                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md text-sm flex items-center gap-2">
                <FontAwesomeIcon icon="plus" />
                {{ t("common.actions.add") || "Add Question" }}
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b">
                        <th class="w-10 px-2 py-2"></th>
                        <th class="text-left px-3 py-2 font-medium">
                            {{ t("common.labels.question") || "Question" }}
                        </th>
                        <th class="text-center px-3 py-2 font-medium w-28">
                            {{ t("common.labels.answer_type") || "Answer Type" }}
                        </th>
                        <th class="text-center px-3 py-2 font-medium w-20">
                            {{ t("common.labels.point") || "Point" }}
                        </th>
                        <th class="text-center px-3 py-2 font-medium w-24">
                            {{ t("common.labels.position") || "Position" }}
                        </th>
                        <th class="text-center px-3 py-2 font-medium w-28">
                            {{ t("common.labels.action") || "Action" }}
                        </th>
                    </tr>
                </thead>
                <VueDraggable v-model="questions" tag="tbody" :animation="150" handle=".drag-handle"
                    :disabled="reorderProcessing" @end="onQuestionDragEnd">
                    <tr v-for="q in questions" :key="q.slug || q.id" class="border-b hover:bg-gray-50">
                        <td class="px-2 py-2 text-center">
                            <span
                                class="drag-handle cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600"
                                :class="{
                                    'opacity-50 pointer-events-none': reorderProcessing,
                                }">
                                <FontAwesomeIcon icon="grip-vertical" />
                            </span>
                        </td>
                        <td class="px-3 py-2">
                            {{ q.question }}
                        </td>
                        <td class="px-3 py-2 text-center capitalize">
                            {{ q.answer_type }}
                        </td>
                        <td class="px-3 py-2 text-center">
                            {{ q.point }}
                        </td>
                        <td class="px-3 py-2 text-center">
                            <input v-model.number="q.position" type="number" min="1"
                                class="w-20 border border-gray-300 rounded-md px-2 py-1.5 text-sm text-center focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :disabled="reorderProcessing" @change="onQuestionManualPositionChange(q.slug)" />
                        </td>
                        <td class="px-3 py-2 text-center">
                            <div class="flex items-center justify-center gap-3">
                                <button v-if="canUpdateQuestion(q)" type="button" @click="openEditQuestionModal(q)"
                                    class="text-blue-600 hover:text-blue-800">
                                    <FontAwesomeIcon icon="pencil" />
                                </button>
                                <button v-if="canDeleteQuestion(q)" type="button" @click="openDeleteModal(q)"
                                    class="text-red-500 hover:text-red-700">
                                    <FontAwesomeIcon icon="trash" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </VueDraggable>
            </table>
        </div>

        <div v-for="q in questions" :key="`options-${q.slug || q.id}`" class="mt-4">
            <div class="mb-2 text-sm font-medium text-gray-700">
                {{ t("common.labels.options") || "Options" }} — {{ q.question }}
            </div>
            <CreateUpdateQuizQuestionOptionTable :quiz="quiz" :quizQuestion="q" :isUpdate="true" />
        </div>

        <div class="space-y-3 pt-2">
            <div class="flex justify-start">
                <a v-if="canAccessQuestion()" :href="route('back-office.quizzes.quiz-questions.index', {
                    slug: quiz?.slug,
                })
                    "
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="list" />
                    {{ t("common.messages.questions") || "Questions" }}
                </a>
            </div>
        </div>
    </div>

    <div v-if="showQuestionModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl mx-4 p-6 space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">
                    {{
                        editingQuestion
                            ? t("common.actions.edit")
                            : t("common.actions.add")
                    }}
                    {{ t("common.labels.question") || "Question" }}
                </h3>
                <button type="button" @click="closeQuestionModal" class="text-gray-400 hover:text-gray-600">
                    <FontAwesomeIcon icon="times" />
                </button>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">
                    {{ t("common.labels.question") || "Question" }}
                    <span class="text-red-500">*</span>
                </label>
                <textarea v-model="saveQuizQuestionForm.question" rows="3"
                    class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    :class="saveQuizQuestionForm.errors.question
                        ? 'border-red-500'
                        : 'border-gray-300'
                        " :placeholder="t('common.placeholders.question') || 'Enter question'"></textarea>
                <p v-if="saveQuizQuestionForm.errors.question" class="text-red-500 text-sm mt-1">
                    {{ saveQuizQuestionForm.errors.question }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">
                        {{ t("common.labels.answer_type") || "Answer Type" }}
                        <span class="text-red-500">*</span>
                    </label>
                    <SelectInfinityLoadingApi :form="saveQuizQuestionForm" fieldName="answer_type" :selectedItem="saveQuizQuestionForm.answer_type
                        ? {
                            id: saveQuizQuestionForm.answer_type,
                            name: saveQuizQuestionForm.answer_type,
                        }
                        : null
                        " :apiUrl="route('search.quiz-question-answer-types')"
                        :error="saveQuizQuestionForm.errors.answer_type" :multiple="false"
                        :placeholder="t('common.labels.answerType') || 'Answer Type'" />
                    <p v-if="saveQuizQuestionForm.errors.answer_type" class="text-red-500 text-sm mt-1">
                        {{ saveQuizQuestionForm.errors.answer_type }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">
                        {{ t("common.labels.point") || "Point" }}
                        <span class="text-red-500">*</span>
                    </label>
                    <input v-model.number="saveQuizQuestionForm.point" type="number" min="0" step="0.01"
                        class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        :class="saveQuizQuestionForm.errors.point
                            ? 'border-red-500'
                            : 'border-gray-300'
                            " />
                    <p v-if="saveQuizQuestionForm.errors.point" class="text-red-500 text-sm mt-1">
                        {{ saveQuizQuestionForm.errors.point }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">
                        {{ t("common.labels.position") || "Position" }}
                    </label>
                    <input v-model.number="saveQuizQuestionForm.position" type="number" min="1"
                        class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        :class="saveQuizQuestionForm.errors.position
                            ? 'border-red-500'
                            : 'border-gray-300'
                            " />
                    <p v-if="saveQuizQuestionForm.errors.position" class="text-red-500 text-sm mt-1">
                        {{ saveQuizQuestionForm.errors.position }}
                    </p>
                </div>
            </div>

            <div v-if="!editingQuestion">
                <CreateUpdateQuizQuestionOptionTable :quiz="quiz" :quizQuestion="null" :isUpdate="false"
                    :quizQuestionSaveForm="saveQuizQuestionForm" />
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" @click="closeQuestionModal"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50">
                    {{ t("common.actions.cancel") }}
                </button>
                <button type="button" @click="handleQuizQuestionSave" :disabled="saveQuizQuestionForm.processing"
                    class="bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-4 py-2 rounded-md text-sm flex items-center gap-2">
                    <FontAwesomeIcon v-if="saveQuizQuestionForm.processing" icon="spinner" spin />
                    <FontAwesomeIcon v-else icon="save" />
                    {{
                        saveQuizQuestionForm.processing
                            ? t("common.actions.saving")
                            : t("common.actions.save")
                    }}
                </button>
            </div>
        </div>
    </div>

    <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-sm mx-4 p-6 space-y-4">
            <h3 class="text-lg font-semibold">
                {{ t("common.actions.delete") }}
            </h3>
            <p class="text-sm text-gray-600">
                {{ t("common.messages.confirmDelete") }}
            </p>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" @click="closeDeleteModal"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50"
                    :disabled="deleteProcessing">
                    {{ t("common.actions.cancel") }}
                </button>
                <button type="button" @click="handleQuizQuestionDelete(deletingRow)" :disabled="deleteProcessing"
                    class="bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white px-4 py-2 rounded-md text-sm flex items-center gap-2">
                    <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />
                    <FontAwesomeIcon v-else icon="trash" />
                    {{
                        deleteProcessing
                            ? t("common.actions.deleting")
                            : t("common.actions.delete")
                    }}
                </button>
            </div>
        </div>
    </div>
</template>
