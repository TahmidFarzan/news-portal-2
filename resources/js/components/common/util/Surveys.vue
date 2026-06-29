<script setup>
import SurveyQuestion from '@/components/common/util/SurveyQuestion.vue'

import { ref, computed, onMounted, } from 'vue'

import { Swiper, SwiperSlide, } from 'swiper/vue'
import { Navigation, } from 'swiper/modules'

import 'swiper/css'
import 'swiper/css/navigation'

import { fetchFromApi, } from '@/composables/useSystemApi'
import { useTranslate, } from '@/composables/useTranslate'

const { t } = useTranslate()

const surveys = ref([])
const loading = ref(false)
const useSwiper = computed(() => surveys.value.length > 1)

const load = async () => {
    loading.value = true

    try {
        const response = await fetchFromApi(route('site.surveys'))
        surveys.value = Array.isArray(response) ? response : []
    }
    finally {
        loading.value = false
    }
}

onMounted(
    load
)
</script>


<template>

    <section v-if="surveys.length">
        <Swiper v-if="useSwiper" :modules="[Navigation]"
            :navigation="{ prevEl: '.survey-prev', nextEl: '.survey-next' }">
            <SwiperSlide v-for="survey in surveys" :key="survey.id">
                <div class="border rounded-xl p-2">

                    <h2 class="font-bold mb-4">
                        {{ survey.name }}
                    </h2>

                    <div v-if="survey.survey_questions?.length" class="grid grid-cols-12 gap-2">
                        <div v-for="surveyQuestion in survey.survey_questions" :key="surveyQuestion.id"
                            class="col-span-12">
                            <SurveyQuestion :survey="survey" :survey-question="surveyQuestion" @updated="load" />
                        </div>
                    </div>

                    <div v-else>
                        {{ t('components.common.util.surveys.labels.no_survey_question_added') }}
                    </div>
                </div>
            </SwiperSlide>
        </Swiper>

        <div v-else class="space-y-2">
            <div v-for="survey in surveys" :key="survey.id" class="border border-gray-100 rounded-xl p-2">
                <h2 class="font-bold mb-4">
                    {{ survey.name }}
                </h2>
                <div v-if="survey.survey_questions?.length" class="grid grid-cols-12 gap-2">

                    <div v-for="surveyQuestion in survey.survey_questions" :key="surveyQuestion.id" class="col-span-12">
                        <SurveyQuestion :survey="survey" :survey-question="surveyQuestion" @updated="load" />
                    </div>
                </div>

                <div v-else>
                    {{ t('components.common.util.surveys.labels.no_survey_question_added') }}
                </div>

            </div>
        </div>
    </section>

</template>
