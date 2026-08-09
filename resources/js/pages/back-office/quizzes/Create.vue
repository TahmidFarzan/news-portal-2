<script setup>
import Layout from "@/pages/layouts/AuthLayout.vue";
import { computed, onMounted, nextTick, watch, inject } from "vue";
import { Head, useForm, router as inertiaJsRoute } from "@inertiajs/vue3";
import { useTranslate } from "@/composables/useTranslate";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { library as FontAwesomeLibrary } from "@fortawesome/fontawesome-svg-core";
import { faSave, faSpinner } from "@fortawesome/free-solid-svg-icons";
import InfiniteScrollApiSelect from "@/components/common/multi-select/InfiniteScrollApiSelect.vue";
import QuizQuestionManagerByQuiz from "@/components/back-office/quiz-question/QuizQuestionManagerByQuiz.vue";
import { formatDate } from "@/composables/useDateTime";
import { quizQuestionAnswerTypes } from "@/composables/useQuiz";

FontAwesomeLibrary.add(faSave, faSpinner);

defineOptions({ layout: Layout });

const { t } = useTranslate();
const authUser = inject("authUser");

const { quiz } = defineProps({
    quiz: Object,
});

const isUpdate = computed(() => !!quiz?.slug);

const pageTitle = computed(() => {
    return isUpdate.value
        ? `${quiz?.name} ${t("common.actions.edit")}`
        : t("common.actions.create");
});

const createEmptyOption = (position = 1) => ({
    option: "",
    is_correct: false,
    position,
});

const createEmptyQuestion = (position = 1) => ({
    question: "",
    answer_type: quizQuestionAnswerTypes.SINGLE,
    point: 1,
    position,
    options: [createEmptyOption(1), createEmptyOption(2)],
});

const saveForm = useForm({
    language_id: quiz?.language_id || null,
    name: quiz?.name || "",
    brief: quiz?.brief || "",
    start_date: quiz?.start_date ? formatDate(quiz?.start_date, "Y-m-d") : null,
    end_date: quiz?.end_date ? formatDate(quiz?.end_date, "Y-m-d") : null,
    is_active: quiz?.is_active ?? true,
    show_bellow_event: quiz?.show_bellow_event ?? false,
    max_winner: quiz?.max_winner ?? 1,
    enable_result: quiz?.enable_result ?? false,
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
                q.answer_type = quizQuestionAnswerTypes.SINGLE;
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
                        t("form-requests.quiz.option.required") ||
                        "Option text is required"
                    );
                    valid = false;
                }
            });

            const correctCount = q.options.filter((o) => !!o.is_correct).length;

            if (q.answer_type === quizQuestionAnswerTypes.SINGLE && correctCount !== 1) {
                saveForm.setError(
                    `questions.${qIndex}.options`,
                    t("form-requests.quiz.answer.single") ||
                    "Single answer type must have exactly one correct option"
                );
                valid = false;
            }

            if (q.answer_type === quizQuestionAnswerTypes.MULTIPLE && correctCount < 1) {
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

    if (!isUpdate.value && saveForm.questions?.length) {
        saveForm.questions.forEach((q, qIndex) => {
            q.position = qIndex + 1;
            q.options?.forEach((opt, oIndex) => {
                opt.position = oIndex + 1;
            });
        });
    }

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
        saveForm.post(route("back-office.quizzes.save"), requestConfig);
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
                            <InfiniteScrollApiSelect :form="saveForm" fieldName="language_id"
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
                                :class="saveForm.errors.name
                                    ? 'border-red-500'
                                    : 'border-gray-300'
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
                                :class="saveForm.errors.brief
                                    ? 'border-red-500'
                                    : 'border-gray-300'
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
                            <label class="block text-sm font-medium mb-1">
                                {{ t("common.labels.max_winner") }}
                                <span class="text-red-500">*</span>
                            </label>
                            <input v-model="saveForm.max_winner" type="number"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.max_winner
                                    ? 'border-red-500'
                                    : 'border-gray-300'
                                    " :placeholder="t('common.placeholders.max_winner')" min="1" step="1" />
                            <p v-if="saveForm.errors.max_winner" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.max_winner }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                {{ t("common.labels.enable_result") }}
                                <span class="text-red-500">*</span>
                            </label>
                            <label class="inline-flex cursor-pointer items-center gap-3">
                                <input v-model="saveForm.enable_result" type="checkbox" class="peer sr-only" />
                                <span
                                    class="relative h-7 w-14 rounded-full bg-gray-300 transition after:absolute after:left-1 after:top-1 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-green-600 peer-checked:after:translate-x-7"></span>
                                <span class="text-sm text-gray-600">
                                    {{
                                        saveForm.enable_result
                                            ? t("common.boolean.yes")
                                            : t("common.boolean.no")
                                    }}
                                </span>
                            </label>
                            <p v-if="saveForm.errors.enable_result" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.enable_result }}
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
                                {{
                                    t("common.labels.show_bellow_event") ||
                                    "Show below event"
                                }}
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

                <QuizQuestionManagerByQuiz :quiz="quiz" :isUpdate="isUpdate" :quizSaveForm="saveForm" />

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
