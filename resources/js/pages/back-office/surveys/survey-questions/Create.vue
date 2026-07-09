<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import SelectInfinityLoadingApi from '@/components/common/multi-select/SelectInfinityLoadingApi.vue'

import { computed, onMounted, nextTick, watch } from 'vue'
import { Head, useForm, router as inertiaJsRoute } from '@inertiajs/vue3'
import { useTranslate } from '@/composables/useTranslate'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faSave, faEye, faEyeSlash, faSpinner } from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faSave, faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const { survey, surveyQuestion } = defineProps({
    surveyQuestion: Object,
    survey: Object,
})

const isUpdate = computed(() => !!surveyQuestion?.slug)

const pageTitle = computed(() => {
    return isUpdate.value
        ? `${surveyQuestion?.question} ${t('common.actions.edit')}`
        : t('common.actions.create')
})

const saveForm = useForm({
    question: surveyQuestion?.question || null,
})

function validateForm() {
    saveForm.clearErrors()

    let valid = true

    if (!saveForm.question || saveForm.question.trim() === '') {
        saveForm.setError('question', t('common.validation.titleIsRequired'))
        valid = false
    }

    return valid
}

function handleSave() {
    if (saveForm.processing) return
    if (!validateForm()) return

    const requestConfig = {
        preserveScroll: true,
        preserveState: true,
        forceFormData: true,

        onSuccess: () => {
            saveForm.reset()
            saveForm.clearErrors()
        },

        onError: (errors) => {
            saveForm.clearErrors()
            saveForm.setError(errors)
        }
    }

    if (isUpdate.value) {
        inertiaJsRoute.post(
            route('back-office.surveys.survey-questions.update', { slug: survey?.slug, surveyQuestionSlug: surveyQuestion?.slug }),
            { ...saveForm.data(), _method: 'patch' },
            requestConfig
        )
    } else {
        saveForm.post(route('back-office.surveys.survey-questions.save', { slug: survey?.slug}), requestConfig)
    }
}


onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: t('common.labels.surveys'), href: route('back-office.surveys.index') },
                { text: `${survey?.name} ${t('common.actions.details')}`, href: route('back-office.surveys.details',{slug : survey?.slug}) },
                { text: t('common.labels.surveyQuestions'),  href: route('back-office.surveys.survey-questions.index',{slug : survey?.slug}) ,active: false },
                {
                    text: pageTitle.value,
                    active: true
                }
            ],
        })
    )
})
</script>

<template>

    <Head :title="pageTitle" />

    <div class="w-full">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6">

            <form @submit.prevent="handleSave" class="space-y-6">

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">
                        {{ t('common.labels.basicInformation') }}
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.question') }}
                                <span class="text-red-500">*</span>
                            </label>


                            <textarea v-model="saveForm.question" rows="4"
                                :placeholder="t('common.labels.question')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.question ? 'border-red-500' : 'border-gray-300'"></textarea>

                            <p v-if="saveForm.errors.question" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.question }}
                            </p>
                        </div>

                    </div>
                </div>

                <div class="flex justify-center">
                    <button type="submit" :disabled="saveForm.processing"
                        class="bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-6 py-2 rounded-md flex items-center gap-2 transition">
                        <FontAwesomeIcon v-if="saveForm.processing" icon="spinner" spin />
                        <FontAwesomeIcon v-else icon="save" />

                        {{
                            saveForm.processing
                                ? t('common.actions.saving')
                                : t('common.actions.save')
                        }}
                    </button>
                </div>

            </form>

        </div>
    </div>
</template>
