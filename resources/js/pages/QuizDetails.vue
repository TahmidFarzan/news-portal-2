<script setup>
import { computed, inject, ref, reactive, onUnmounted } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import Layout from '@/pages/layouts/PublicLayout.vue'
import GoogleAdsence from '@/components/common/util/GoogleAdsence.vue'
import { adTypes, adPositions } from '@/composables/useGoogleAdsence'
import { useTranslate, translateDate } from '@/composables/useTranslate'
import { quizQuestionAnswerTypes } from '@/composables/useQuiz'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faCalendar, faChevronRight, faChevronLeft, faTrophy, faPlay, faCheck, faEdit, faPaperPlane, faClock, faCircleCheck } from '@fortawesome/free-solid-svg-icons'
import { Swiper, SwiperSlide } from 'swiper/vue'
import { Navigation } from 'swiper/modules'
import { VueTelInput } from 'vue-tel-input'
import 'vue-tel-input/vue-tel-input.css'
import 'swiper/css'
import 'swiper/css/navigation'

FontAwesomeLibrary.add(faCalendar, faTrophy, faChevronLeft, faChevronRight, faPlay, faCheck, faEdit, faPaperPlane, faClock, faCircleCheck)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const {
    quiz,
    previousQuiz,
    previousQuizWinnerResults,
    alreadySubmitted,
} = defineProps({
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
    alreadySubmitted: {
        type: Boolean,
        default: false,
    },
})

const metaTitle = computed(() => quiz?.name ?? t('common.labels.page'))
const metaDescription = computed(() => quiz?.brief || '')
const metaKeywords = computed(() => '')

const showGoogleAd = inject('showGoogleAd', computed(() => false))

const getQuizDuration = (quizItem) => {
    const startDate = quizItem?.start_date
    const endDate = quizItem?.end_date

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

const hasPreviousResult = computed(() => previousQuiz?.show_result === true)

const questions = computed(() => quiz?.quiz_questions ?? [])

const isStarted = ref(false)
const isConfirmed = ref(false)
const isUpdating = ref(false)
const isSubmitted = ref(alreadySubmitted === true)
const submitResult = ref(null)

const elapsedSeconds = ref(0)
let timerInterval = null

const answers = reactive({})
const participant = reactive({
    name: '',
    mobile: '',
    email: '',
    address: '',
})

const formErrors = reactive({
    name: '',
    mobile_or_email: '',
    questions: '',
})

const telInputOptions = {
    mode: 'international',
    defaultCountry: 'BD',
    autoFormat: true,
    validCharactersOnly: true,
    dropdownOptions: {
        showDialCodeInSelection: true,
        showDialCodeInList: true,
        showFlags: true,
        showSearchBox: true,
    },
    inputOptions: {
        placeholder: t('common.placeholders.mobile') || 'Enter mobile number',
        showDialCode: true,
    },
}

const onMobileInput = (mobile, mobileObject) => {
    if (mobileObject?.valid) {
        participant.mobile = mobileObject.number || mobile
    } else {
        participant.mobile = mobile || ''
    }
}

const isMultiple = (question) => {
    return question?.answer_type === quizQuestionAnswerTypes.MULTIPLE
}

const isAnswered = (question) => {
    const value = answers[question.id]

    if (isMultiple(question)) {
        return Array.isArray(value) && value.length > 0
    }

    return value !== undefined && value !== null && value !== ''
}

const allQuestionsAnswered = computed(() => {
    if (!questions.value.length) return false
    return questions.value.every((q) => isAnswered(q))
})

const canShowConfirm = computed(() => isStarted.value && allQuestionsAnswered.value && !isConfirmed.value)
const canShowUpdate = computed(() => isConfirmed.value)
const canEditParticipant = computed(() => isConfirmed.value && !isUpdating.value)
const canEditAnswers = computed(() => isStarted.value && (!isConfirmed.value || isUpdating.value))

const formatTime = (totalSeconds) => {
    const m = Math.floor(totalSeconds / 60)
    const s = totalSeconds % 60
    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
}

const formatReadableDuration = (totalSeconds) => {
    if (!totalSeconds || totalSeconds < 1) {
        return t('common.labels.less_than_a_second') || 'Less than a second'
    }

    const hours = Math.floor(totalSeconds / 3600)
    const minutes = Math.floor((totalSeconds % 3600) / 60)
    const seconds = totalSeconds % 60

    const parts = []

    if (hours > 0) {
        parts.push(`${hours} ${hours === 1 ? (t('common.labels.hour') || 'hour') : (t('common.labels.hours') || 'hours')}`)
    }
    if (minutes > 0) {
        parts.push(`${minutes} ${minutes === 1 ? (t('common.labels.minute') || 'minute') : (t('common.labels.minutes') || 'minutes')}`)
    }
    if (seconds > 0 || parts.length === 0) {
        parts.push(`${seconds} ${seconds === 1 ? (t('common.labels.second') || 'second') : (t('common.labels.seconds') || 'seconds')}`)
    }

    return parts.join(' ')
}

const initAnswers = () => {
    questions.value.forEach((q) => {
        if (answers[q.id] === undefined) {
            answers[q.id] = isMultiple(q) ? [] : null
        }
    })
}

const startTimer = () => {
    if (timerInterval) return
    timerInterval = setInterval(() => {
        elapsedSeconds.value += 1
    }, 1000)
}

const stopTimer = () => {
    if (timerInterval) {
        clearInterval(timerInterval)
        timerInterval = null
    }
}

const handleStart = () => {
    initAnswers()
    isStarted.value = true
    isConfirmed.value = false
    isUpdating.value = false
    startTimer()
}

const handleConfirm = () => {
    if (!allQuestionsAnswered.value) return
    isConfirmed.value = true
    isUpdating.value = false
    stopTimer()
}

const handleUpdate = () => {
    isUpdating.value = true
    isConfirmed.value = false
    startTimer()
}

const isOptionSelected = (question, optionId) => {
    if (isMultiple(question)) {
        return Array.isArray(answers[question.id]) && answers[question.id].includes(optionId)
    }
    return answers[question.id] === optionId
}

const validateForm = () => {
    formErrors.name = ''
    formErrors.mobile_or_email = ''
    formErrors.questions = ''

    let valid = true

    if (!participant.name?.trim()) {
        formErrors.name = t('common.validation.required') || 'Name is required'
        valid = false
    }

    const hasMobile = participant.mobile?.trim()
    const hasEmail = participant.email?.trim()
    if (!hasMobile && !hasEmail) {
        formErrors.mobile_or_email = t('common.validation.mobile_or_email') || 'Mobile or email is required'
        valid = false
    }

    if (!allQuestionsAnswered.value) {
        formErrors.questions = t('common.validation.all_questions') || 'Please answer all questions'
        valid = false
    }

    return valid
}

const submitForm = useForm({
    answers: [],
    name: '',
    mobile: '',
    email: '',
    address: '',
    duration: 0,
})

const handleSubmit = () => {
    if (submitForm.processing) return
    if (!isConfirmed.value) return
    if (!validateForm()) return

    const answersPayload = questions.value.map((q) => {
        if (isMultiple(q)) {
            return {
                question_id: q.id,
                selected_option_ids: Array.isArray(answers[q.id]) ? answers[q.id] : [],
            }
        }

        return {
            question_id: q.id,
            selected_option_id: answers[q.id],
        }
    })

    submitForm.answers = answersPayload
    submitForm.name = participant.name.trim()
    submitForm.mobile = participant.mobile?.trim() || null
    submitForm.email = participant.email?.trim() || null
    submitForm.address = participant.address?.trim() || null
    submitForm.duration = elapsedSeconds.value

    const requestConfig = {
        preserveScroll: true,
        preserveState: true,
        forceFormData: true,
        onSuccess: (page) => {
            stopTimer()

            const flash = page?.props?.flash ?? {}
            const data = flash?.data ?? page?.props?.result ?? {}

            isSubmitted.value = true
            submitResult.value = {
                total_point: data?.total_point ?? null,
                duration: elapsedSeconds.value,
                message: flash?.message ?? data?.message ?? null,
            }

            submitForm.reset()
            submitForm.clearErrors()
        },
        onError: (errors) => {
            submitForm.clearErrors()
            submitForm.setError(errors)
        },
    }

    if (quiz?.language?.is_default === false) {
        submitForm.post(
            route('localized.home.quizzes.submit', {
                languageCode: quiz.language.code,
                slug: quiz.slug,
            }),
            requestConfig
        )
    } else {
        submitForm.post(
            route('home.quizzes.submit', { slug: quiz.slug }),
            requestConfig
        )
    }
}

onUnmounted(() => {
    stopTimer()
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
                            <h4 class="text-base font-semibold text-gray-800">{{ previousQuiz.name }}</h4>
                            <p class="mt-1 text-sm text-gray-500">{{ getQuizDuration(previousQuiz) }}</p>
                        </div>

                        <div class="lg:col-span-6">
                            <div v-if="previousQuizWinnerResults.length > 0">
                                <Swiper :modules="[Navigation]" :slides-per-view="1" :space-between="16"
                                    :navigation="{ prevEl: '.winner-result-prev', nextEl: '.winner-result-next' }"
                                    class="winner-results-swiper">
                                    <SwiperSlide v-for="winnerResult in previousQuizWinnerResults"
                                        :key="winnerResult.id">
                                        <div class="rounded-lg border border-green-100 bg-white p-4 text-center">
                                            <div
                                                class="mx-auto flex h-30 w-30 items-center justify-center rounded-full bg-green-100 text-xl font-bold text-green-700">
                                                {{ winnerResult?.total_point || 0 }} / {{ previousQuiz?.total_point || 0
                                                }}
                                            </div>
                                            <h5 class="mt-3 text-base font-semibold text-gray-800">
                                                {{ winnerResult?.quiz_participant?.name }}
                                            </h5>
                                            <p v-if="winnerResult?.quiz_participant?.email"
                                                class="mt-2 break-all text-sm text-gray-600">
                                                {{ winnerResult?.quiz_participant?.email }}
                                            </p>
                                            <p v-else-if="winnerResult?.quiz_participant?.mobile"
                                                class="mt-2 text-sm text-gray-600">
                                                {{ winnerResult?.quiz_participant?.mobile }}
                                            </p>
                                            <p v-if="winnerResult?.quiz_participant?.address"
                                                class="mt-2 text-xs text-gray-500">
                                                {{ winnerResult?.quiz_participant?.address }}
                                            </p>
                                        </div>
                                    </SwiperSlide>
                                </Swiper>

                                <div class="mt-4 flex justify-center gap-3">
                                    <button type="button"
                                        class="winner-result-prev flex h-9 w-9 items-center justify-center rounded-full border border-green-300 bg-white text-green-600 transition hover:bg-green-600 hover:text-white">
                                        <FontAwesomeIcon icon="chevron-left" />
                                    </button>
                                    <button type="button"
                                        class="winner-result-next flex h-9 w-9 items-center justify-center rounded-full border border-green-300 bg-white text-green-600 transition hover:bg-green-600 hover:text-white">
                                        <FontAwesomeIcon icon="chevron-right" />
                                    </button>
                                </div>
                            </div>
                            <div v-else class="py-8 text-center text-sm text-gray-500">
                                {{ t('common.labels.no_winners') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <GoogleAdsence v-if="showGoogleAd" :type="adTypes.SECTION" :position="adPositions.BETWEEN" />

        <div v-if="isStarted && !isSubmitted" class="sticky top-4 z-50 flex justify-center">
            <div
                class="inline-flex items-center gap-3 rounded-full border border-indigo-200 bg-white/95 px-5 py-2.5 shadow-lg backdrop-blur-sm">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-white">
                    <FontAwesomeIcon icon="clock" class="text-sm" />
                </div>
                <div class="leading-tight">
                    <p class="text-[10px] uppercase tracking-wider text-gray-500">
                        {{ t('common.labels.duration') }}
                    </p>
                    <p class="font-mono text-lg font-bold text-indigo-700 tabular-nums">
                        {{ formatTime(elapsedSeconds) }}
                    </p>
                </div>
            </div>
        </div>

        <section class="quiz-form-section">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
                <div class="rounded-xl border border-indigo-100 bg-gradient-to-b from-indigo-50 to-white p-5 space-y-6">

                    <div v-if="isSubmitted" class="py-12 text-center space-y-5">
                        <div
                            class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 shadow-sm">
                            <FontAwesomeIcon icon="circle-check" class="text-4xl" />
                        </div>

                        <h3 class="text-2xl font-bold text-emerald-700">
                            {{ t('common.labels.quiz_submitted') }}
                        </h3>

                        <p class="text-gray-600 max-w-md mx-auto text-base leading-relaxed">
                            {{ t('common.messages.quiz_submitted_success') }}
                        </p>

                        <div
                            class="mt-6 inline-flex flex-col sm:flex-row items-center justify-center gap-6 rounded-2xl border border-emerald-200 bg-white px-8 py-5 shadow-sm">
                            <div v-if="submitResult?.total_point !== null && submitResult?.total_point !== undefined"
                                class="text-center min-w-[100px]">
                                <p class="text-xs uppercase tracking-wider text-gray-500 mb-1">
                                    {{ t('common.labels.score') }}
                                </p>
                                <p class="text-3xl font-bold text-emerald-700">
                                    {{ submitResult.total_point }}
                                </p>
                            </div>

                            <div v-if="submitResult?.duration" class="text-center min-w-[140px]">
                                <p class="text-xs uppercase tracking-wider text-gray-500 mb-1">
                                    {{ t('common.labels.time_taken') }}
                                </p>
                                <p class="text-lg font-semibold text-gray-800">
                                    {{ formatReadableDuration(submitResult.duration) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <template v-else>
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <h3 class="text-lg font-bold text-indigo-800">
                                {{ t('common.labels.quizQuestions') }}
                            </h3>
                        </div>

                        <div v-if="!isStarted" class="flex justify-center py-8">
                            <button type="button"
                                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 text-white font-semibold shadow-md transition-all duration-300 hover:bg-indigo-700 hover:shadow-lg hover:scale-105 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                @click="handleStart">
                                <FontAwesomeIcon icon="play" />
                                {{ t('common.labels.startQuestion') }}
                            </button>
                        </div>

                        <form v-else @submit.prevent="handleSubmit" class="space-y-8">
                            <div v-if="questions.length === 0" class="py-10 text-center text-gray-500">
                                {{ t('common.labels.noQuestionFound') }}
                            </div>

                            <div v-else class="space-y-6">
                                <div v-for="(question, index) in questions" :key="question.id"
                                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                                    <p class="font-semibold text-gray-900 mb-4">
                                        <span class="text-indigo-600 mr-2">{{ index + 1 }}.</span>
                                        {{ question.question }}
                                        <span v-if="isMultiple(question)"
                                            class="ml-2 text-xs font-normal text-gray-500">
                                            ({{ t('common.labels.multipleChoice') }})
                                        </span>
                                    </p>

                                    <div class="flex flex-wrap gap-3">
                                        <label v-for="option in (question.quiz_question_options || [])" :key="option.id"
                                            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2.5 cursor-pointer transition-all duration-200 hover:border-indigo-300 hover:bg-indigo-50 select-none"
                                            :class="{
                                                'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-300 shadow-sm': isOptionSelected(question, option.id),
                                                'opacity-60 pointer-events-none': !canEditAnswers,
                                            }">
                                            <input v-if="isMultiple(question)" type="checkbox" :value="option.id"
                                                v-model="answers[question.id]" :disabled="!canEditAnswers"
                                                class="h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500" />
                                            <input v-else type="radio" :name="`question_${question.id}`"
                                                :value="option.id" v-model="answers[question.id]"
                                                :disabled="!canEditAnswers"
                                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500" />
                                            <span class="text-sm font-medium text-gray-800">
                                                {{ option.option }}
                                            </span>
                                        </label>
                                    </div>
                                </div>

                                <p v-if="formErrors.questions" class="text-sm text-red-600">
                                    {{ formErrors.questions }}
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center justify-center gap-3">
                                <button v-if="canShowConfirm" type="button"
                                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-3 text-white font-semibold shadow-md transition-all duration-300 hover:bg-emerald-700 hover:shadow-lg hover:scale-105"
                                    @click="handleConfirm">
                                    <FontAwesomeIcon icon="check" />
                                    {{ t('common.labels.confirmAnswer') }}
                                </button>

                                <button v-if="canShowUpdate" type="button"
                                    class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-6 py-3 text-white font-semibold shadow-md transition-all duration-300 hover:bg-amber-600 hover:shadow-lg hover:scale-105"
                                    @click="handleUpdate">
                                    <FontAwesomeIcon icon="edit" />
                                    {{ t('common.labels.updateAnswer') }}
                                </button>
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 space-y-4 transition-all duration-300"
                                :class="{ 'opacity-60 pointer-events-none': !canEditParticipant }">
                                <h4 class="text-base font-bold text-gray-800">
                                    {{ t('common.labels.participantInfo') }}
                                </h4>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            {{ t('common.labels.name') }} <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" v-model="participant.name" :disabled="!canEditParticipant"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100" />
                                        <p v-if="formErrors.name" class="mt-1 text-sm text-red-600">{{ formErrors.name
                                            }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            {{ t('common.labels.mobile') }}
                                        </label>
                                        <VueTelInput v-model="participant.mobile" :disabled="!canEditParticipant"
                                            v-bind="telInputOptions" @on-input="onMobileInput"
                                            class="vue-tel-input-custom" />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            {{ t('common.labels.email') }}
                                        </label>
                                        <input type="email" v-model="participant.email" :disabled="!canEditParticipant"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100" />
                                    </div>

                                    <div class="md:col-span-2">
                                        <p v-if="formErrors.mobile_or_email" class="text-sm text-red-600 mb-2">
                                            {{ formErrors.mobile_or_email }}
                                        </p>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            {{ t('common.labels.address') }}
                                        </label>
                                        <textarea v-model="participant.address" :disabled="!canEditParticipant" rows="3"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div v-if="isConfirmed && !isUpdating" class="flex justify-center pt-2">
                                <button type="submit" :disabled="submitForm.processing"
                                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-8 py-3 text-white font-semibold shadow-md transition-all duration-300 hover:bg-blue-700 hover:shadow-lg hover:scale-105 disabled:opacity-60">
                                    <FontAwesomeIcon icon="paper-plane" />
                                    <span v-if="submitForm.processing">
                                        {{ t('common.labels.submitting') }}
                                    </span>
                                    <span v-else>
                                        {{ t('common.labels.submit') }}
                                    </span>
                                </button>
                            </div>
                        </form>
                    </template>
                </div>
            </div>
        </section>

        <GoogleAdsence v-if="showGoogleAd" :type="adTypes.SECTION" :position="adPositions.BOTTOM" />
    </div>
</template>

<style scoped>
:deep(.vue-tel-input) {
    border-radius: 0.5rem;
    border: 1px solid #d1d5db;
    width: 100%;
}

:deep(.vue-tel-input:focus-within) {
    border-color: #6366f1;
    box-shadow: 0 0 0 1px #6366f1;
}

:deep(.vti__input) {
    border: none !important;
    border-radius: 0.5rem;
    padding: 0.5rem 0.75rem;
    width: 100%;
    font-size: 0.875rem;
}

:deep(.vti__dropdown) {
    border-radius: 0.5rem 0 0 0.5rem;
    background: #f9fafb;
}

:deep(.vue-tel-input.disabled) {
    opacity: 0.6;
    pointer-events: none;
    background: #f3f4f6;
}
</style>
