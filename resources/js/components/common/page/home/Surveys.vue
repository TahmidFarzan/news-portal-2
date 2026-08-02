<script setup>
import SurveyQuestion from '@/components/common/page/home/SurveyQuestion.vue'

import { computed, onMounted, ref, watch } from 'vue'

import { Swiper, SwiperSlide } from 'swiper/vue'
import { Navigation } from 'swiper/modules'

import 'swiper/css'
import 'swiper/css/navigation'

import { fetchFromApi } from '@/composables/useApiClient'
import { apiCacheKey, apiCacheTTL } from '@/composables/useApiCache'
import { useTranslate } from '@/composables/useTranslate'

const { t } = useTranslate()

const { currentLanguage } = defineProps({
    currentLanguage: {
        type: Object,
        required: true,
    },
})

const surveys = ref([])

const normalizeSurveys = (value) => {
    if (Array.isArray(value)) {
        return value
    }

    if (Array.isArray(value?.data)) {
        return value.data
    }

    return []
}

const getSurveysApiUrl = () => {
    return currentLanguage?.is_default
        ? route('home.surveys.get')
        : route('localized.home.surveys.get', {
            languageCode: currentLanguage?.code,
        })
}

const load = async () => {
    if (!currentLanguage?.is_default && !currentLanguage?.code) {
        surveys.value = []
        return
    }

    try {
        const apiUrl = getSurveysApiUrl()

        const response = await fetchFromApi(
            apiUrl,
            {},
            {
                key: `${apiCacheKey.API_HOME_PAGE}:${apiUrl}`,
                ttl: apiCacheTTL.HOME_PAGE,
            }
        )

        surveys.value = normalizeSurveys(response)
    } catch {
        surveys.value = []
    }
}

const useSwiper = computed(() => surveys.value.length > 1)

onMounted(load)

watch(
    () => [
        currentLanguage?.code,
        currentLanguage?.is_default,
    ],
    load
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
                        <div v-for="surveyQuestion in survey.survey_questions" :key="surveyQuestion.id"
                            class="col-span-12">
                            <SurveyQuestion :key="`${survey.id}-${surveyQuestion.id}`" :survey="survey"
                                :survey-question="surveyQuestion" :current-language="currentLanguage" @updated="load" />
                        </div>
                    </div>

                    <div v-else>
                        {{ t('components.utility.surveys.labels.noSurveyQuestionAdded') }}
                    </div>
                </div>
            </SwiperSlide>
        </Swiper>

        <div v-else class="space-y-2">
            <div v-for="survey in surveys" :key="survey.id" class="survey-card border border-gray-100 rounded-xl p-2">
                <h2 class="survey-title font-bold mb-4">
                    {{ survey.name }}
                </h2>

                <div v-if="survey.survey_questions?.length" class="grid grid-cols-12 gap-2">
                    <div v-for="surveyQuestion in survey.survey_questions" :key="`${survey.id}-${surveyQuestion.id}`"
                        class="col-span-12">
                        <SurveyQuestion :key="`${survey.id}-${surveyQuestion.id}`" :survey="survey"
                            :survey-question="surveyQuestion" :current-language="currentLanguage" @updated="load" />
                    </div>
                </div>

                <div v-else>
                    {{ t('components.utility.surveys.labels.noSurveyQuestionAdded') }}
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
