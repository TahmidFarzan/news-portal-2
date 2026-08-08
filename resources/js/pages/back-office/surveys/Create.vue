<script setup>
import Layout from "@/pages/layouts/AuthLayout.vue";
import { computed, onMounted, nextTick, watch, inject } from "vue";
import { Head, useForm, router as inertiaJsRoute } from "@inertiajs/vue3";
import { useTranslate } from "@/composables/useTranslate";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { library as FontAwesomeLibrary } from "@fortawesome/fontawesome-svg-core";
import { faSave, faSpinner } from "@fortawesome/free-solid-svg-icons";
import InfiniteScrollApiSelect from "@/components/common/multi-select/InfiniteScrollApiSelect.vue";
import SurveyQuestionManager from "@/components/back-office/survey-question/SurveyQuestionManager.vue";
import { formatDate } from "@/composables/useDateTime";

FontAwesomeLibrary.add(faSave, faSpinner);

defineOptions({ layout: Layout });

const { t } = useTranslate();
const authUser = inject("authUser");

const { survey } = defineProps({
    survey: Object,
});

const isUpdate = computed(() => !!survey?.slug);

const pageTitle = computed(() => {
    return isUpdate.value
        ? `${survey?.name} ${t("common.actions.edit")}`
        : t("common.actions.create");
});

const createEmptyQuestion = (position = 1) => ({
    question: "",
    position,
});

const saveForm = useForm({
    language_id: survey?.language_id || null,
    name: survey?.name || "",
    brief: survey?.brief || "",
    start_date: survey?.start_date ? formatDate(survey?.start_date, "Y-m-d") : null,
    end_date: survey?.end_date ? formatDate(survey?.end_date, "Y-m-d") : null,
    is_active: survey?.is_active ?? true,
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
                q.position = qIndex + 1;
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
            t("form-requests.survey.language_id.required") || "Language is required"
        );
        valid = false;
    }

    if (!saveForm.name?.trim()) {
        saveForm.setError(
            "name",
            t("form-requests.survey.name.required") || "Name is required"
        );
        valid = false;
    }

    if (!saveForm.start_date) {
        saveForm.setError(
            "start_date",
            t("form-requests.survey.start_date.required") || "Start date is required"
        );
        valid = false;
    }

    if (!saveForm.end_date) {
        saveForm.setError(
            "end_date",
            t("form-requests.survey.end_date.required") || "End date is required"
        );
        valid = false;
    } else if (saveForm.start_date && saveForm.end_date < saveForm.start_date) {
        saveForm.setError(
            "end_date",
            t("form-requests.survey.end_date.after_or_equal") ||
            "End date must be after or equal to start date"
        );
        valid = false;
    }

    if (!isUpdate.value) {
        if (!saveForm.questions?.length) {
            saveForm.setError(
                "questions",
                t("form-requests.survey.questions.required") ||
                "At least one question is required"
            );
            valid = false;
        }

        saveForm.questions.forEach((q, qIndex) => {
            if (!q.question?.trim()) {
                saveForm.setError(
                    `questions.${qIndex}.question`,
                    t("form-requests.survey.question.required") || "Question is required"
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
            route("back-office.surveys.update", { slug: survey?.slug }),
            { ...saveForm.data(), _method: "patch" },
            requestConfig
        );
    } else {
        saveForm.post(route("back-office.surveys.save"), requestConfig);
    }
}

onMounted(async () => {
    await nextTick();

    window.dispatchEvent(
        new CustomEvent("set-breadcrumb", {
            detail: [
                {
                    text: t("common.labels.surveys"),
                    href: route("back-office.surveys.index"),
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
                                :selectedItem="survey?.language" :apiUrl="route('search.languages')"
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
                                    " :placeholder="t('common.placeholders.name') || 'Survey name'
                                    " />
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
                    </div>
                </div>

                <SurveyQuestionManager :survey="survey" :isUpdate="isUpdate"
                    :surveySaveForm="saveForm" />

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
