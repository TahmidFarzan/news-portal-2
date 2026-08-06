<script setup>
import { computed, inject } from 'vue'
import { Head } from '@inertiajs/vue3'
import Layout from '@/pages/layouts/PublicLayout.vue'
import GoogleAdsence from '@/components/common/util/GoogleAdsence.vue'
import { adTypes, adPositions } from '@/composables/useGoogleAdsence'
import { useTranslate, translateDate } from '@/composables/useTranslate'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faCalendar, faChevronRight, faChevronLeft, faTrophy } from '@fortawesome/free-solid-svg-icons'
import { Swiper, SwiperSlide } from 'swiper/vue'
import { Navigation } from 'swiper/modules'
import 'swiper/css'
import 'swiper/css/navigation'

FontAwesomeLibrary.add(faCalendar, faTrophy, faChevronLeft, faChevronRight)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const props = defineProps({
    quiz: {
        type: Object,
        required: true,
    },
    previousQuiz: {
        type: Object,
        required: true,
    },
    previousQuizWinnerResults: {
        type: Array,
        required: true,
        default: () => [],
    },
})

const metaTitle = computed(() => props.quiz?.name ?? t('common.labels.page'))
const metaDescription = computed(() => props.quiz?.brief || '')
const metaKeywords = computed(() => '')

const showGoogleAd = inject('showGoogleAd', computed(() => false))

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

const hasPreviousResult = computed(() => {
    return props.previousQuiz?.show_result === true
})
</script>

<template>

    <Head :title="metaTitle">
        <link v-if="quiz?.public_url" rel="canonical" :href="quiz.public_url" />
        <meta v-if="metaTitle" name="title" :content="metaTitle" />
        <meta v-if="metaDescription" name="description" :content="metaDescription" />
        <meta v-if="metaKeywords" name="keywords" :content="metaKeywords" />
    </Head>

    <div class="static-page space-y-6">
        <GoogleAdsence v-if="showGoogleAd" :type="adTypes.SECTION" :position="adPositions.TOP" />

        <section class="quiz-info">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex-1 min-w-0">
                            <h2 class="quiz-title font-bold text-xl text-gray-900">
                                {{ quiz.name }}
                            </h2>
                            <p v-if="quiz.brief" class="mt-2 text-gray-600 leading-7">
                                {{ quiz.brief }}
                            </p>
                        </div>

                        <div class="shrink-0">
                            <div
                                class="inline-flex items-center gap-3 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 shadow-sm">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 text-white">
                                    <FontAwesomeIcon icon="calendar" />
                                </div>
                                <div class="leading-tight">
                                    <p class="text-xs uppercase tracking-wide text-gray-500">
                                        {{ t('common.labels.schedule') }}
                                    </p>
                                    <p class="font-semibold text-gray-800">
                                        {{ getQuizDuration(quiz) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="hasPreviousResult" class="previous-quiz-result">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
                <div class="rounded-xl border border-green-200 bg-gradient-to-b from-green-50 to-white p-5">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                        <div class="lg:col-span-6">
                            <div class="flex items-center gap-3 mb-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-green-600 text-white">
                                    <FontAwesomeIcon icon="trophy" />
                                </div>
                                <h3 class="text-lg font-bold text-green-700">
                                    {{ t('common.labels.result') }}
                                </h3>
                            </div>

                            <h4 class="text-base font-semibold text-gray-800">
                                {{ previousQuiz.name }}
                            </h4>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ getQuizDuration(previousQuiz) }}
                            </p>
                        </div>

                        <div class="lg:col-span-6">
                            <div v-if="previousQuizWinnerResults.length > 0">
                                <Swiper :modules="[Navigation]" :slides-per-view="1" :space-between="16" :navigation="{
                                    prevEl: '.winner-result-prev',
                                    nextEl: '.winner-result-next',
                                }" class="winner-results-swiper">
                                    <SwiperSlide v-for="winnerResult in previousQuizWinnerResults" :key="winnerResult.id">
                                        <div class="rounded-lg border border-green-100 bg-white p-4 text-center">
                                            <div
                                                class="mx-auto flex h-30 w-30 items-center justify-center rounded-full bg-green-100 text-xl font-bold text-green-700">
                                                {{ winnerResult?.total_point || 0 }} / {{ previousQuiz?.total_point || 0 }}
                                            </div>

                                            <h5 class="mt-3 text-base font-semibold text-gray-800">
                                                {{ winnerResult?.quiz_participant?.name }}
                                            </h5>

                                            <p v-if="winnerResult?.quiz_participant?.email" class="mt-2 break-all text-sm text-gray-600">
                                                {{ winnerResult?.quiz_participant?.email }}
                                            </p>
                                            <p v-else-if="winnerResult?.quiz_participant?.phone" class="mt-2 text-sm text-gray-600">
                                                {{ winnerResult?.quiz_participant?.phone }}
                                            </p>

                                            <p v-if="winnerResult?.quiz_participant?.address" class="mt-2 text-xs text-gray-500">
                                                {{ winnerResult?.quiz_participant?.address }}
                                            </p>
                                        </div>
                                    </SwiperSlide>
                                </Swiper>

                                <div class="mt-4 flex justify-center gap-3">
                                    <button type="button"
                                        class="winner-result-prev flex h-9 w-9 items-center justify-center rounded-full border border-green-300 bg-white text-green-600 transition hover:bg-green-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-1"
                                        :aria-label="t('common.labels.previous')">
                                        <FontAwesomeIcon icon="chevron-left" />
                                    </button>
                                    <button type="button"
                                        class="winner-result-next flex h-9 w-9 items-center justify-center rounded-full border border-green-300 bg-white text-green-600 transition hover:bg-green-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-1"
                                        :aria-label="t('common.labels.next')">
                                        <FontAwesomeIcon icon="chevron-right" />
                                    </button>
                                </div>
                            </div>

                            <div v-else class="py-8 text-center text-sm text-gray-500">
                                {{ t('common.labels.no_winners') || 'No winners announced yet.' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <GoogleAdsence v-if="showGoogleAd" :type="adTypes.SECTION" :position="adPositions.BOTTOM" />
    </div>
</template>
