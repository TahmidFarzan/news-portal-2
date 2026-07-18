<script setup>
import { ref, computed, watch } from 'vue'

import { postToApi } from '@/composables/useApiClient'
import { useTranslate } from '@/composables/useTranslate'

const {
    survey,
    surveyQuestion,
    currentLanguage,
    isDefaultLanguage = false,
} = defineProps({
    survey: Object,
    surveyQuestion: Object,
    currentLanguage: {
        type: Object,
        required: true,
    },
    isDefaultLanguage: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['updated'])

const { t } = useTranslate()

const loading = ref(false)
const message = ref(null)
const error = ref(null)
const editing = ref(false)

const cacheKey = computed(() => `survey_answer_${survey?.id}_${surveyQuestion?.id}`)
const cachedAnswer = sessionStorage.getItem(cacheKey.value)

const submittedAnswer = ref(surveyQuestion?.selected_answer ?? cachedAnswer ?? null)
const selectedAnswer = ref(submittedAnswer.value)

const submitted = computed(() => !!submittedAnswer.value && !editing.value)

const result = computed(() => surveyQuestion?.survey_question_result)
const total = computed(() => result.value?.participate_count ?? 0)

const percent = value => {
    if (!total.value) {
        return 0
    }

    return Math.round(((value ?? 0) * 100) / total.value)
}

const options = computed(() => [
    {
        value: 'yes',
        label: t('common.boolean.yes'),
    },

    {
        value: 'no',
        label: t('common.boolean.no'),
    },

    {
        value: 'no_comment',
        label: t('components.utility.surveyQuestion.labels.noComment'),
    },
])

const selectedLabel = computed(() =>
    options.value.find(x => x.value === submittedAnswer.value)?.label
)

const submit = async () => {
    if (!isDefaultLanguage && !currentLanguage?.code) {
        return
    }

    loading.value = true

    message.value = null

    error.value = null

    try {

        const response = await postToApi(

            getSubmitApiUrl(),

            {
                yes: selectedAnswer.value === 'yes',
                no: selectedAnswer.value === 'no',
                no_comment: selectedAnswer.value === 'no_comment',
            }
        )

        if (response?.status === 'success') {

            submittedAnswer.value = response?.data?.answer ?? selectedAnswer.value
            selectedAnswer.value = submittedAnswer.value

            sessionStorage.setItem(cacheKey.value, submittedAnswer.value)

            editing.value = false
            message.value = t('components.utility.surveyQuestion.api.submitSuccess')

            await emit('updated')

            return
        }
        else {

            sessionStorage.removeItem(
                cacheKey.value
            )
            error.value = response?.message ?? t('components.utility.surveyQuestion.api.submitFail')
        }
    }

    catch {
        sessionStorage.removeItem(
            cacheKey.value
        )
        error.value = t('components.utility.surveyQuestion.api.submitFail')
    }

    finally {
        loading.value = false
    }
}

const changeAnswer = () => {
    editing.value = true
    message.value = null
    error.value = null
    selectedAnswer.value = submittedAnswer.value
}

const translateNumerText = value => {
    return String(value)
        .split('')
        .map(char => t(`numbers.${char}`))
        .join('')
}

const progressColor = value => {

    if (value === 'yes') {
        return 'bg-green-500'
    }

    if (value === 'no') {
        return 'bg-red-500'
    }

    return 'bg-sky-500'
}

const getSubmitApiUrl = () => {
    const params = {
        slug: survey?.slug,
        surveyQuestionSlug: surveyQuestion?.slug,
    }

    if (isDefaultLanguage) {
        return route(
            'home.surveys.survey-questions-submit',
            params
        )
    }

    return route(
        'localized.home.surveys.survey-questions-submit',
        {
            languageCode: currentLanguage.code,
            ...params,
        }
    )
}

watch(
    () => surveyQuestion,
    value => {
        submittedAnswer.value =
            value?.selected_answer ??
            sessionStorage.getItem(cacheKey.value) ??
            null

        selectedAnswer.value = submittedAnswer.value
    },
    { deep: true }
)

</script>

<template>

    <div class="survey-question grid grid-cols-1 md:grid-cols-[1.15fr_.85fr] gap-3">
        <div class="survey-panel rounded-lg border border-gray-300 bg-white p-3 space-y-3">
            <div>
                <h4 class="text-sm font-semibold leading-5">
                    {{ surveyQuestion.question }}
                </h4>
            </div>

            <Transition name="fade" mode="out-in">
                <div v-if="submitted"
                    class="flex items-center justify-between rounded-lg border border-green-200 bg-green-50 px-3 py-2 animate-in fade-in duration-300">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse" />
                        <span class="text-sm font-medium text-green-700">
                            {{ selectedLabel }}
                        </span>
                    </div>

                    <button class="h-8 px-3 text-xs rounded-md border transition hover:bg-white" @click="changeAnswer">
                        {{ t('components.utility.surveyQuestion.buttons.changeAnswer') }}
                    </button>
                </div>

                <div v-else>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                        <button v-for="option in options" :key="option.value" @click=" selectedAnswer = option.value"
                            class="survey-option h-10 rounded-md border text-xs font-medium transition-all duration-300 active:scale-95"
                            :class="selectedAnswer === option.value ? 'is-selected scale-[0.98]' : 'border-gray-300 hover:border-red-200 hover:bg-red-50'">
                            {{ option.label }}
                        </button>
                    </div>

                    <button
                        class="survey-submit mt-2 w-full h-10 rounded-md text-white text-sm font-medium transition-all duration-300 hover:brightness-110 disabled:opacity-50"
                        @click="submit" :disabled="loading || !selectedAnswer">
                        {{ loading ? '...' : t('components.utility.surveyQuestion.buttons.submit') }}
                    </button>

                </div>

            </Transition>

            <div v-if="message" class="text-[11px] text-green-600 animate-pulse ">
                {{ message }}
            </div>

            <div v-if="error" class="text-[11px] text-red-600">
                {{ error }}
            </div>
        </div>

        <div class="survey-panel rounded-lg border border-gray-300 bg-white p-3">
            <div class="flex justify-between items-center mb-2">
                <div class="text-[11px] text-gray-500">
                    {{ translateNumerText(total) }}
                </div>
            </div>

            <template v-if="result && total">
                <div class="space-y-2">
                    <div v-for="option in options" :key="option.value">
                        <div class="flex justify-between text-[11px] mb-1">
                            <span>
                                {{ option.label }}
                            </span>

                            <span class="font-medium">
                                {{ percent(result[option.value]) }}%
                            </span>

                        </div>

                        <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700 ease-out"
                                :class="progressColor(option.value)"
                                :style="{ width: percent(result[option.value]) + '%' }">
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <div v-else class="py-2 text-center text-xs text-gray-500">
                {{ t('components.utility.surveyQuestion.labels.noParticipateFound') }}
            </div>

        </div>

    </div>

</template>

<style scoped>
.survey-panel {
    border-color: var(--news-border);
    border-radius: var(--news-radius-sm);
}

.survey-question h4 {
    color: var(--news-ink);
}

.survey-option {
    border-color: var(--news-border-strong);
    color: var(--news-muted-strong);
}

.survey-option.is-selected {
    border-color: var(--news-primary);
    background: var(--news-primary);
    color: var(--news-white);
}

.survey-submit {
    background: var(--news-button-primary-gradient);
}
</style>
