<script setup>
import SurveyQuestion from '@/components/common/page/home/SurveyQuestion.vue'

import { computed, ref, watch } from 'vue'

import { Swiper, SwiperSlide } from 'swiper/vue'
import { Navigation } from 'swiper/modules'

import 'swiper/css'
import 'swiper/css/navigation'

import { fetchFromApi } from '@/composables/useSystemApi'
import { useTranslate } from '@/composables/useTranslate'

const { t } = useTranslate()

const { surveys: initialSurveys } = defineProps({
    surveys: {
        type: [Array, Object],
        default: () => [],
    },
})

const surveys = ref([...initialSurveys])


const load = async () => {
    try {
        const response = await fetchFromApi(route('home.surveys.get'))
        surveys.value = Array.isArray(response)
            ? [...response]
            : []
    } catch {
        surveys.value = []
    }
}

const useSwiper = computed(() => surveys.value.length > 1)

watch(
    () => initialSurveys,
    value => {
        surveys.value = [...value]
    },
    { deep: true }
)
</script>

<template>
    <section v-if="surveys.length" class="surveys-section">
        <Swiper v-if="useSwiper" :modules="[Navigation]" :navigation="{
            prevEl: '.survey-prev',
            nextEl: '.survey-next',
        }">
            <SwiperSlide v-for="survey in surveys" :key="`${survey.id}`">
                <div class="survey-card border rounded-xl p-2">
                    <h2 class="survey-title font-bold mb-4">
                        {{ survey.name }}
                    </h2>

                    <div v-if="survey.survey_questions?.length" class="grid grid-cols-12 gap-2">
                        <div v-for="surveyQuestion in survey.survey_questions"
                            :key="surveyQuestion.id" class="col-span-12">
                            <SurveyQuestion :key="`${survey.id}-${surveyQuestion.id}`" :survey="survey"
                                :survey-question="surveyQuestion" @updated="load" />
                        </div>
                    </div>

                    <div v-else>
                        {{ t('components.common.util.surveys.labels.no_survey_question_added') }}
                    </div>
                </div>
            </SwiperSlide>
        </Swiper>

        <div v-else class="space-y-2">
            <div v-for="survey in surveys" :key="survey.id"
                class="survey-card border border-gray-100 rounded-xl p-2">
                <h2 class="survey-title font-bold mb-4">
                    {{ survey.name }}
                </h2>

                <div v-if="survey.survey_questions?.length" class="grid grid-cols-12 gap-2">
                    <div v-for="surveyQuestion in survey.survey_questions" :key="`${survey.id}-${surveyQuestion.id}`"
                        class="col-span-12">
                        <SurveyQuestion :key="`${survey.id}-${surveyQuestion.id}`" :survey="survey"
                            :survey-question="surveyQuestion" @updated="load" />
                    </div>
                </div>

                <div v-else>
                    {{ t('components.common.util.surveys.labels.no_survey_question_added') }}
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.surveys-section {
    border-radius: var(--news-radius);
    background: var(--news-survey-gradient);
}

.survey-card {
    border-color: var(--news-border);
    padding: clamp(1rem, 2vw, 1.5rem);
    box-shadow: var(--news-shadow-soft);
}

.survey-title {
    color: var(--news-ink);
    font-size: var(--news-survey-title-size);
    line-height: 1.25;
}
</style>
