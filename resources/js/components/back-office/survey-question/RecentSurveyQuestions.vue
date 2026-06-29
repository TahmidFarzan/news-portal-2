<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faInfo } from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'

import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faInfo)

const { t } = useTranslate()

const { survey } = defineProps({
    survey: { type: Object, required: true },
})
</script>

<template>
    <div>

        <div v-if="survey?.survey_questions && survey?.survey_questions?.length">

            <div class="overflow-x-auto bg-white border border-gray-200 rounded-xl shadow-sm">
                <table class="min-w-full text-sm text-left">

                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-2">
                                {{ t("components.back_office.survey_question.recent_survey_questions.labels.sl") }}
                            </th>
                            <th class="px-4 py-2">
                                {{ t("components.back_office.survey_question.recent_survey_questions.labels.question") }}
                            </th>

                            <th class="px-4 py-2">
                                {{ t("components.back_office.survey_question.recent_survey_questions.labels.created_at") }}
                            </th>
                            <th class="px-4 py-2">
                                {{ t("components.back_office.survey_question.recent_survey_questions.labels.actions") }}
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="(item, index) in survey?.survey_questions" :key="item.id"
                            class="border-t hover:bg-gray-50">

                            <td class="px-4 py-2">
                                {{ index + 1 }}
                            </td>

                            <td class="px-4 py-2">
                                {{ item.question }}
                            </td>

                            <td class="px-4 py-2">
                                {{ formatDateTime(item.created_at) }}
                            </td>

                            <td class="px-4 py-2">
                                <a :href="route('back-office.surveys.survey-questions.details', { slug: survey.slug ,surveyQuestionSlug: item.slug })"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs border border-blue-500 text-blue-500 rounded hover:bg-blue-50">
                                    <FontAwesomeIcon icon="info" />
                                    {{ t("components.back_office.survey_question.recent_survey_questions.links.details") }}
                                </a>
                            </td>

                        </tr>
                    </tbody>

                </table>
            </div>

            <div class="flex justify-center mt-4">
                <a :href="route('back-office.surveys.survey-questions.index', { slug: survey.slug })"
                    class="inline-flex items-center justify-center px-4 py-2 border border-gray-400 text-gray-600 rounded hover:bg-gray-100">
                    {{ t("components.back_office.survey_question.recent_survey_questions.links.show_all") }}
                </a>
            </div>

        </div>

        <div v-else>
            <div class="bg-yellow-100 border border-yellow-300 text-yellow-800 px-4 py-3 rounded">
                {{ t("components.back_office.survey_question.recent_survey_questions.labels.no_record_found") }}
            </div>
        </div>

    </div>
</template>
