<script setup>
import { computed, onMounted, ref, watch } from 'vue'

import { Swiper, SwiperSlide } from 'swiper/vue'
import { Navigation } from 'swiper/modules'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { library as FontAwesomeLibrary } from "@fortawesome/fontawesome-svg-core";
import { faCalendar, faCircleChevronRight } from "@fortawesome/free-solid-svg-icons"; 

import { fetchFromApi } from '@/composables/useApiClient'
import { apiCacheKey, apiCacheTTL } from '@/composables/useApiCache'
import { useTranslate, translateDate } from '@/composables/useTranslate'

import 'swiper/css'
import 'swiper/css/navigation'

FontAwesomeLibrary.add(faCalendar, faCircleChevronRight);

const { t } = useTranslate()

const { currentLanguage, belowEvent } = defineProps({
    currentLanguage: {
        type: Object,
        required: true,
    },
    belowEvent: {
        type: Boolean,
        required: true,
    },
})

const quizzes = ref([])

const normalizeQuizzes = (value) => {
    if (Array.isArray(value)) {
        return value
    }

    if (Array.isArray(value?.data)) {
        return value.data
    }

    return []
}

const getQuizzesApiUrl = () => {
    return currentLanguage?.is_default
        ? route('home.quizzes.get', {
            show_bellow_event: belowEvent,
        })
        : route('localized.home.quizzes.get', {
            languageCode: currentLanguage?.code,
            show_bellow_event: belowEvent,
        })
}

const getQuizDuration = (quiz) => {
    const startDate = quiz?.start_date
    const endDate = quiz?.end_date

    if (startDate && endDate) {
        return `${translateDate(startDate)} ${t('common.labels.to')} ${translateDate(endDate)}`
    }

    if (startDate) {
        return `${translateDate(startDate)} ${t('common.labels.to')} ${t('common.labels.ongoing')}`
    }

    if (endDate) {
        return `${t('common.labels.until')} ${translateDate(endDate)}`
    }

    return t('common.labels.ongoing')
}

const load = async () => {
    if (!currentLanguage?.is_default && !currentLanguage?.code) {
        quizzes.value = []
        return
    }

    try {
        const apiUrl = getQuizzesApiUrl()

        const response = await fetchFromApi(
            apiUrl,
            {},
            {
                key: `${apiCacheKey.API_HOME_PAGE}:${apiUrl}`,
                ttl: apiCacheTTL.HOME_PAGE,
            }
        )

        quizzes.value = normalizeQuizzes(response)
    } catch {
        quizzes.value = []
    }
}

const useSwiper = computed(() => quizzes.value.length > 1)

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
    <section v-if="quizzes.length" class="quizzes-section">
        <Swiper v-if="useSwiper" :modules="[Navigation]" :navigation="{
            prevEl: '.quiz-prev',
            nextEl: '.quiz-next',
        }">
            <SwiperSlide v-for="quiz in quizzes" :key="quiz.id">
                <div class="quiz-card border rounded-xl p-2">
                    <div class="grid grid-cols-12 gap-4 items-center">
                        <div class="col-span-12 md:col-span-6">
                            <h2 class="quiz-title font-bold">
                                <a :href="quiz?.public_url"
                                    class="inline-flex items-center gap-2 text-inherit transition-all duration-200 hover:text-primary group">
                                    <span>{{ quiz.name }}</span>

                                    <FontAwesomeIcon icon="circle-chevron-right"
                                        class="text-primary text-base transition-transform duration-200 group-hover:translate-x-1" />
                                </a>
                            </h2>
                        </div>

                        <div class="col-span-12 md:col-span-6 flex md:justify-end">
                            <div
                                class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-700 shadow-sm">
                                <FontAwesomeIcon icon="calendar" />

                                <span>{{ getQuizDuration(quiz) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </SwiperSlide>
        </Swiper>

        <div v-else class="space-y-2">
            <div v-for="quiz in quizzes" :key="quiz.id" class="quiz-card border border-gray-100 rounded-xl p-2">
                <div class="grid grid-cols-12 gap-4 items-center">
                    <div class="col-span-12 md:col-span-6">
                        <h2 class="quiz-title font-bold">
                            <a :href="quiz?.public_url"
                                class="inline-flex items-center gap-2 text-inherit transition-all duration-200 hover:text-primary group">
                                <span>{{ quiz.name }}</span>

                                <FontAwesomeIcon icon="circle-chevron-right"
                                    class="text-primary text-base transition-transform duration-200 group-hover:translate-x-1" />
                            </a>
                        </h2>
                    </div>

                    <div class="col-span-12 md:col-span-6 flex md:justify-end">
                        <div class="flex md:justify-end">
                            <div
                                class="inline-flex items-center gap-3 rounded-xl border border-blue-100 bg-blue-50 px-4 py-2 shadow-sm">
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-600 text-white">
                                    <FontAwesomeIcon icon="calendar" />
                                </div>

                                <div class="flex flex-col leading-tight">
                                    <span class="text-xs uppercase tracking-wide text-gray-500">
                                        {{ t('common.labels.schedule') }}
                                    </span>

                                    <span class="font-semibold text-gray-800">
                                        {{ getQuizDuration(quiz) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.quizzes-section {
    border-radius: var(--news-radius);
    background: var(--news-quiz-gradient);
}

.quiz-card {
    border-color: var(--news-border);
    padding: clamp(1rem, 2vw, 1.5rem);
    box-shadow: var(--news-shadow-soft);
}

.quiz-title {
    color: var(--news-ink);
    font-size: var(--news-quiz-title-size);
    line-height: 1.25;
}
</style>
