<script setup>
import Layout from "@/pages/layouts/AuthLayout.vue";
import { computed, onMounted, nextTick, watch } from "vue";
import { Head, useForm, router as inertiaJsRoute } from "@inertiajs/vue3";
import { useTranslate } from "@/composables/useTranslate";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { library as FontAwesomeLibrary } from "@fortawesome/fontawesome-svg-core";
import {
    faSave,
    faSpinner,
    faPlus,
    faTrash,
    faGripVertical,
} from "@fortawesome/free-solid-svg-icons";
import { VueDraggable } from "vue-draggable-plus";
import SelectInfinityLoadingApi from "@/components/common/multi-select/SelectInfinityLoadingApi.vue";

FontAwesomeLibrary.add(faSave, faSpinner, faPlus, faTrash, faGripVertical);

defineOptions({ layout: Layout });

const { t } = useTranslate();

const { quiz } = defineProps({
    quiz: Object,
});

const isUpdate = computed(() => !!quiz?.slug);

const pageTitle = computed(() => {
    return isUpdate.value
        ? `${quiz?.name} ${t("common.actions.edit")}`
        : t("common.actions.create");
});

const answerTypes = [
    { value: "single", label: t("common.labels.single") || "Single" },
    { value: "multiple", label: t("common.labels.multiple") || "Multiple" },
];

const createEmptyOption = (position = 1) => ({
    option: "",
    is_correct: false,
    position,
});

const createEmptyQuestion = (position = 1) => ({
    question: "",
    answer_type: "single",
    point: 1,
    position,
    options: [createEmptyOption(1), createEmptyOption(2)],
});

const saveForm = useForm({
    language_id: quiz?.language_id || null,
    name: quiz?.name || "",
    brief: quiz?.brief || "",
    start_date: quiz?.start_date || "",
    end_date: quiz?.end_date || "",
    is_active: quiz?.is_active ?? true,
    show_bellow_event: quiz?.show_bellow_event ?? false,
    questions: isUpdate.value ? [] : [createEmptyQuestion(1)],
});

watch(
    () => saveForm.language_id,
    (newVal, oldVal) => {
        if (oldVal === undefined || newVal === oldVal) return;

        saveForm.name = "";
        saveForm.brief = "";
        saveForm.start_date = "";
        saveForm.end_date = "";

        if (!isUpdate.value) {
            saveForm.questions.forEach((q, qIndex) => {
                q.question = "";
                q.answer_type = "single";
                q.point = 1;
                q.position = qIndex + 1;

                q.options.forEach((opt, oIndex) => {
                    opt.option = "";
                    opt.is_correct = false;
                    opt.position = oIndex + 1;
                });
            });
        }

        saveForm.clearErrors();
    }
);

function reindexPositions(items) {
    items.forEach((item, index) => {
        item.position = index + 1;
    });
}

function addQuestion() {
    const nextPos = saveForm.questions.length + 1;
    saveForm.questions.push(createEmptyQuestion(nextPos));
}

function removeQuestion(index) {
    if (saveForm.questions.length <= 1) return;
    saveForm.questions.splice(index, 1);
    reindexPositions(saveForm.questions);
}

function onQuestionDragEnd() {
    reindexPositions(saveForm.questions);
}

function addOption(qIndex) {
    const options = saveForm.questions[qIndex].options;
    const nextPos = options.length + 1;
    options.push(createEmptyOption(nextPos));
}

function removeOption(qIndex, oIndex) {
    const options = saveForm.questions[qIndex].options;
    if (options.length <= 2) return;
    options.splice(oIndex, 1);
    reindexPositions(options);
}

function onOptionDragEnd(qIndex) {
    reindexPositions(saveForm.questions[qIndex].options);
}

function handleAnswerTypeChange(qIndex) {
    const question = saveForm.questions[qIndex];
    if (question.answer_type === "single") {
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
    const question = saveForm.questions[qIndex];
    if (question.answer_type === "single") {
        question.options.forEach((opt, idx) => {
            opt.is_correct = idx === oIndex;
        });
    }
}

function validateForm() {
    saveForm.clearErrors();
    let valid = true;

    if (!saveForm.language_id) {
        saveForm.setError(
            "language_id",
            t("form-requests.quiz.language_id.required") || "Language is required"
        );
        valid = false;
    }

    if (!saveForm.name?.trim()) {
        saveForm.setError(
            "name",
            t("form-requests.quiz.name.required") || "Name is required"
        );
        valid = false;
    }

    if (!saveForm.start_date) {
        saveForm.setError(
            "start_date",
            t("form-requests.quiz.start_date.required") || "Start date is required"
        );
        valid = false;
    }

    if (!saveForm.end_date) {
        saveForm.setError(
            "end_date",
            t("form-requests.quiz.end_date.required") || "End date is required"
        );
        valid = false;
    } else if (saveForm.start_date && saveForm.end_date < saveForm.start_date) {
        saveForm.setError(
            "end_date",
            t("form-requests.quiz.end_date.after_or_equal") ||
            "End date must be after or equal to start date"
        );
        valid = false;
    }

    if (!isUpdate.value) {
        if (!saveForm.questions?.length) {
            saveForm.setError(
                "questions",
                t("form-requests.quiz.questions.required") ||
                "At least one question is required"
            );
            valid = false;
        }

        saveForm.questions.forEach((q, qIndex) => {
            if (!q.question?.trim()) {
                saveForm.setError(
                    `questions.${qIndex}.question`,
                    t("form-requests.quiz.question.required") || "Question is required"
                );
                valid = false;
            }

            if (!q.answer_type) {
                saveForm.setError(
                    `questions.${qIndex}.answer_type`,
                    t("form-requests.quiz.answer_type.required") ||
                    "Answer type is required"
                );
                valid = false;
            }

            if (
                q.point === null ||
                q.point === undefined ||
                q.point === "" ||
                Number(q.point) < 0
            ) {
                saveForm.setError(
                    `questions.${qIndex}.point`,
                    t("form-requests.quiz.point.required") || "Point is required"
                );
                valid = false;
            }

            if (!q.options || q.options.length < 2) {
                saveForm.setError(
                    `questions.${qIndex}.options`,
                    t("form-requests.quiz.options.min") || "At least 2 options required"
                );
                valid = false;
            }

            const optionTexts = q.options
                .map((o) => (o.option || "").trim().toLowerCase())
                .filter(Boolean);
            const uniqueTexts = new Set(optionTexts);
            if (optionTexts.length !== uniqueTexts.size) {
                saveForm.setError(
                    `questions.${qIndex}.options`,
                    t("form-requests.quiz.option.duplicate") ||
                    "Duplicate options are not allowed"
                );
                valid = false;
            }

            q.options.forEach((opt, oIndex) => {
                if (!opt.option?.trim()) {
                    saveForm.setError(
                        `questions.${qIndex}.options.${oIndex}.option`,
                        t("form-requests.quiz.option.required") || "Option text is required"
                    );
                    valid = false;
                }
            });

            const correctCount = q.options.filter((o) => !!o.is_correct).length;

            if (q.answer_type === "single" && correctCount !== 1) {
                saveForm.setError(
                    `questions.${qIndex}.options`,
                    t("form-requests.quiz.answer.single") ||
                    "Single answer type must have exactly one correct option"
                );
                valid = false;
            }

            if (q.answer_type === "multiple" && correctCount < 1) {
                saveForm.setError(
                    `questions.${qIndex}.options`,
                    t("form-requests.quiz.answer.multiple") ||
                    "Multiple answer type must have at least one correct option"
                );
                valid = false;
            }
        });
    }

    return valid;
}

function handleSave() {
    if (saveForm.processing) return;
    if (!validateForm()) return;

    reindexPositions(saveForm.questions);
    saveForm.questions.forEach((q) => reindexPositions(q.options));

    const requestConfig = {
        preserveScroll: true,
        preserveState: true,
        forceFormData: true,
        onSuccess: () => {
            saveForm.reset();
            saveForm.clearErrors();
        },
        onError: (errors) => {
            saveForm.clearErrors();
            saveForm.setError(errors);
        },
    };

    if (isUpdate.value) {
        inertiaJsRoute.post(
            route("back-office.quizzes.update", { slug: quiz?.slug }),
            { ...saveForm.data(), _method: "patch" },
            requestConfig
        );
    } else {
        saveForm.post(route("back-office.quizzes.store"), requestConfig);
    }
}

onMounted(async () => {
    await nextTick();

    window.dispatchEvent(
        new CustomEvent("set-breadcrumb", {
            detail: [
                {
                    text: t("common.labels.quizzes"),
                    href: route("back-office.quizzes.index"),
                },
                {
                    text: pageTitle.value,
                    active: true,
                },
            ],
        })
    );
});
</script>

<template>

    <Head :title="pageTitle" />
    <div class="w-full">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6">
            <form @submit.prevent="handleSave" class="space-y-6">
                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">
                        {{ t("common.labels.basicInformation") }}
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t("common.labels.language") }}
                                <span class="text-red-500">*</span>
                            </label>
                            <SelectInfinityLoadingApi :form="saveForm" fieldName="language_id"
                                :selectedItem="quiz?.language" :apiUrl="route('search.languages')"
                                :error="saveForm.errors.language_id" :multiple="false"
                                :placeholder="t('common.placeholders.selectLanguage')" />
                            <p v-if="saveForm.errors.language_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.language_id }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t("common.labels.name") }}
                                <span class="text-red-500">*</span>
                            </label>
                            <input v-model="saveForm.name" type="text"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.name ? 'border-red-500' : 'border-gray-300'
                                    " :placeholder="t('common.placeholders.name') || 'Quiz name'" />
                            <p v-if="saveForm.errors.name" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.name }}
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">
                                {{ t("common.labels.brief") }}
                            </label>
                            <textarea v-model="saveForm.brief" rows="3"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.brief ? 'border-red-500' : 'border-gray-300'
                                    " :placeholder="t('common.placeholders.brief') || 'Brief description'
                    "></textarea>
                            <p v-if="saveForm.errors.brief" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.brief }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t("common.labels.start_date") }}
                                <span class="text-red-500">*</span>
                            </label>
                            <input v-model="saveForm.start_date" type="date"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.start_date
                                        ? 'border-red-500'
                                        : 'border-gray-300'
                                    " />
                            <p v-if="saveForm.errors.start_date" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.start_date }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t("common.labels.end_date") }}
                                <span class="text-red-500">*</span>
                            </label>
                            <input v-model="saveForm.end_date" type="date"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.end_date
                                        ? 'border-red-500'
                                        : 'border-gray-300'
                                    " />
                            <p v-if="saveForm.errors.end_date" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.end_date }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                {{ t("common.labels.is_active") }}
                                <span class="text-red-500">*</span>
                            </label>
                            <label class="inline-flex cursor-pointer items-center gap-3">
                                <input v-model="saveForm.is_active" type="checkbox" class="peer sr-only" />
                                <span
                                    class="relative h-7 w-14 rounded-full bg-gray-300 transition after:absolute after:left-1 after:top-1 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-green-600 peer-checked:after:translate-x-7"></span>
                                <span class="text-sm text-gray-600">
                                    {{
                                        saveForm.is_active
                                            ? t("common.boolean.yes")
                                            : t("common.boolean.no")
                                    }}
                                </span>
                            </label>
                            <p v-if="saveForm.errors.is_active" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.is_active }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                {{ t("common.labels.show_bellow_event") || "Show below event" }}
                                <span class="text-red-500">*</span>
                            </label>
                            <label class="inline-flex cursor-pointer items-center gap-3">
                                <input v-model="saveForm.show_bellow_event" type="checkbox" class="peer sr-only" />
                                <span
                                    class="relative h-7 w-14 rounded-full bg-gray-300 transition after:absolute after:left-1 after:top-1 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-green-600 peer-checked:after:translate-x-7"></span>
                                <span class="text-sm text-gray-600">
                                    {{
                                        saveForm.show_bellow_event
                                            ? t("common.boolean.yes")
                                            : t("common.boolean.no")
                                    }}
                                </span>
                            </label>
                            <p v-if="saveForm.errors.show_bellow_event" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.show_bellow_event }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="!isUpdate" class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
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

                    <p v-if="saveForm.errors.questions" class="text-red-500 text-sm">
                        {{ saveForm.errors.questions }}
                    </p>

                    <VueDraggable v-model="saveForm.questions" :animation="200" handle=".question-drag-handle"
                        @end="onQuestionDragEnd" class="space-y-4">
                        <div v-for="(question, qIndex) in saveForm.questions" :key="qIndex"
                            class="border border-gray-200 rounded-lg p-4 bg-gray-50 space-y-4">
                            <div class="flex items-start gap-3">
                                <button type="button"
                                    class="question-drag-handle cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600 mt-2">
                                    <FontAwesomeIcon icon="grip-vertical" />
                                </button>

                                <div class="flex-1 space-y-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold text-gray-700">
                                            {{ t("common.labels.question") || "Question" }} #{{
                                                qIndex + 1
                                            }}
                                        </span>
                                        <button v-if="saveForm.questions.length > 1" type="button"
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
                                            :class="saveForm.errors[`questions.${qIndex}.question`]
                                                    ? 'border-red-500'
                                                    : 'border-gray-300'
                                                " :placeholder="t('common.placeholders.question') || 'Enter question'
                        "></textarea>
                                        <p v-if="saveForm.errors[`questions.${qIndex}.question`]"
                                            class="text-red-500 text-sm mt-1">
                                            {{ saveForm.errors[`questions.${qIndex}.question`] }}
                                        </p>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium mb-1">
                                                {{ t("common.labels.answer_type") || "Answer Type" }}
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <select v-model="question.answer_type"
                                                @change="handleAnswerTypeChange(qIndex)"
                                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                                :class="saveForm.errors[`questions.${qIndex}.answer_type`]
                                                        ? 'border-red-500'
                                                        : 'border-gray-300'
                                                    ">
                                                <option v-for="type in answerTypes" :key="type.value"
                                                    :value="type.value">
                                                    {{ type.label }}
                                                </option>
                                            </select>
                                            <p v-if="
                                                saveForm.errors[`questions.${qIndex}.answer_type`]
                                            " class="text-red-500 text-sm mt-1">
                                                {{
                                                    saveForm.errors[`questions.${qIndex}.answer_type`]
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
                                                :class="saveForm.errors[`questions.${qIndex}.point`]
                                                        ? 'border-red-500'
                                                        : 'border-gray-300'
                                                    " />
                                            <p v-if="saveForm.errors[`questions.${qIndex}.point`]"
                                                class="text-red-500 text-sm mt-1">
                                                {{ saveForm.errors[`questions.${qIndex}.point`] }}
                                            </p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium mb-1">
                                                {{ t("common.labels.position") || "Position" }}
                                            </label>
                                            <input v-model.number="question.position" type="number" min="1"
                                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                                :class="saveForm.errors[`questions.${qIndex}.position`]
                                                        ? 'border-red-500'
                                                        : 'border-gray-300'
                                                    " />
                                            <p v-if="saveForm.errors[`questions.${qIndex}.position`]"
                                                class="text-red-500 text-sm mt-1">
                                                {{ saveForm.errors[`questions.${qIndex}.position`] }}
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

                                        <p v-if="saveForm.errors[`questions.${qIndex}.options`]"
                                            class="text-red-500 text-sm">
                                            {{ saveForm.errors[`questions.${qIndex}.options`] }}
                                        </p>

                                        <VueDraggable v-model="question.options" :animation="200"
                                            handle=".option-drag-handle" @end="() => onOptionDragEnd(qIndex)"
                                            class="space-y-2">
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
                                                            :class="saveForm.errors[
                                                                    `questions.${qIndex}.options.${oIndex}.option`
                                                                ]
                                                                    ? 'border-red-500'
                                                                    : 'border-gray-300'
                                                                " :placeholder="t('common.placeholders.option') ||
                                'Option text'
                                " />
                                                        <p v-if="
                                                            saveForm.errors[
                                                            `questions.${qIndex}.options.${oIndex}.option`
                                                            ]
                                                        " class="text-red-500 text-xs mt-1">
                                                            {{
                                                                saveForm.errors[
                                                                `questions.${qIndex}.options.${oIndex}.option`
                                                            ]
                                                            }}
                                                        </p>
                                                    </div>

                                                    <div class="md:col-span-2">
                                                        <label class="block text-xs font-medium mb-1">
                                                            {{ t("common.labels.position") || "Position" }}
                                                        </label>
                                                        <input v-model.number="option.position" type="number" min="1"
                                                            class="w-full border rounded-md px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                                            :class="saveForm.errors[
                                                                    `questions.${qIndex}.options.${oIndex}.position`
                                                                ]
                                                                    ? 'border-red-500'
                                                                    : 'border-gray-300'
                                                                " />
                                                    </div>

                                                    <div class="md:col-span-3 flex items-end pb-1">
                                                        <label class="inline-flex cursor-pointer items-center gap-2">
                                                            <input type="checkbox" :checked="option.is_correct"
                                                                @change="handleCorrectChange(qIndex, oIndex)"
                                                                class="peer sr-only" />
                                                            <span
                                                                class="relative h-6 w-11 rounded-full bg-gray-300 transition after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-green-600 peer-checked:after:translate-x-5"></span>
                                                            <span class="text-xs text-gray-600">
                                                                {{ t("common.labels.correct") || "Correct" }}
                                                            </span>
                                                        </label>
                                                    </div>

                                                    <div class="md:col-span-1 flex items-end justify-end pb-1">
                                                        <button v-if="question.options.length > 2" type="button"
                                                            @click="removeOption(qIndex, oIndex)"
                                                            class="text-red-500 hover:text-red-700 p-1"
                                                            :title="t('common.actions.remove') || 'Remove'">
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

                <div class="flex justify-center">
                    <button type="submit" :disabled="saveForm.processing"
                        class="bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-6 py-2 rounded-md flex items-center gap-2 transition">
                        <FontAwesomeIcon v-if="saveForm.processing" icon="spinner" spin />
                        <FontAwesomeIcon v-else icon="save" />
                        {{
                            saveForm.processing
                                ? t("common.actions.saving")
                                : t("common.actions.save")
                        }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
