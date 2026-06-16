<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import MultiSelectInfinityLoadingApi from '@/components/common/multi-select/InfinityLoadingApi.vue'

import { computed, onMounted, nextTick, watch } from 'vue'
import { Head, useForm, router as inertiaJsRoute } from '@inertiajs/vue3'
import { useTranslate } from '@/composables/useTranslate'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faSave, faEye, faEyeSlash, faSpinner } from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faSave, faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const { breakingNews } = defineProps({
    breakingNews: Object,
})

const isUpdate = computed(() => !!breakingNews?.slug)

const pageTitle = computed(() => {
    return isUpdate.value
        ? `${breakingNews?.title} ${t('pages.back_office.breaking_news.create.labels.edit')}`
        : t('pages.back_office.breaking_news.create.form.create_page_title')
})

const saveForm = useForm({
    language_id: breakingNews?.language_id || null,
    title: breakingNews?.title || null,
    is_published: breakingNews?.is_published,
    news_id: breakingNews?.news_id,
})

const newsApiUrl = computed(() => {
    if (!saveForm.language_id) {
        return route('search.news')
    }

    return route('search.news') + `?language_id=${saveForm.language_id}`
})

function validateForm() {
    saveForm.clearErrors()

    let valid = true

    if (!saveForm.language_id) {
        saveForm.setError('language_id', t('pages.back_office.breaking_news.create.validation.language_is_required'))
        valid = false
    }

    if (!saveForm.title || saveForm.title.trim() === '') {
        saveForm.setError('title', t('pages.back_office.breaking_news.create.validation.title_is_required'))
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
            route('back-office.breaking-news.update', { slug: breakingNews?.slug }),
            { ...saveForm.data(), _method: 'patch' },
            requestConfig
        )
    } else {
        saveForm.post(route('back-office.breaking-news.save'), requestConfig)
    }
}

watch(
    () => saveForm.language_id,
    () => {
        saveForm.news_id = null
        saveForm.clearErrors('news_id')
    }
)

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                {
                    text: t('pages.back_office.breaking_news.create.navigation.breaking_news'),
                    href: route('back-office.breaking-news.index')
                },
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
                        {{ t('pages.back_office.breaking_news.create.labels.basic_information') }}
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('pages.back_office.breaking_news.create.labels.language') }}
                                <span class="text-red-500">*</span>
                            </label>

                            <MultiSelectInfinityLoadingApi :form="saveForm" fieldName="language_id"
                                :selectedItem="breakingNews?.language" :apiUrl="route('search.languages')"
                                :error="saveForm.errors.language_id" :multiple="false"
                                :placeholder="t('pages.back_office.breaking_news.create.form.language_placeholder')" />

                            <p v-if="saveForm.errors.language_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.language_id }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('pages.back_office.breaking_news.create.labels.title') }}
                                <span class="text-red-500">*</span>
                            </label>

                            <input v-model="saveForm.title"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.title ? 'border-red-500' : 'border-gray-300'"
                                :placeholder="t('pages.back_office.breaking_news.create.form.title_placeholder')" />

                            <p v-if="saveForm.errors.title" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.title }}
                            </p>
                        </div>

                    </div>
                </div>

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">
                        {{ t('pages.back_office.breaking_news.create.form.news_settings') }}
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('pages.back_office.breaking_news.create.navigation.news') }}
                            </label>

                            <MultiSelectInfinityLoadingApi :form="saveForm" fieldName="news_id"
                                :selectedItem="saveForm.news_id ? breakingNews?.news : null" :apiUrl="newsApiUrl"
                                :error="saveForm.errors.news_id" selectedLabelKey="title_with_published_at"
                                selectedValueKey="id" apiLabelKey="title_with_published_at" apiValueKey="id"
                                :multiple="false" :placeholder="t('pages.back_office.breaking_news.create.form.news_placeholder')" />

                            <p v-if="saveForm.errors.news_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.news_id }}
                            </p>
                        </div>

                    </div>
                </div>

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">
                        {{ t('pages.back_office.breaking_news.create.form.publish_settings') }}
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                {{ t('pages.back_office.breaking_news.create.labels.published') }}
                            </label>

                            <label class="inline-flex cursor-pointer items-center gap-3">
                                <input v-model="saveForm.is_published" type="checkbox" class="peer sr-only" />

                                <span class="relative h-7 w-14 rounded-full bg-gray-300 transition
                                    after:absolute after:left-1 after:top-1 after:h-5 after:w-5
                                    after:rounded-full after:bg-white after:transition-all after:content-['']
                                    peer-checked:bg-green-600 peer-checked:after:translate-x-7">
                                </span>

                                <span class="text-sm text-gray-600">
                                    {{ saveForm.is_published ? t('pages.back_office.breaking_news.create.labels.yes') : t('pages.back_office.breaking_news.create.labels.no') }}
                                </span>
                            </label>

                            <p v-if="saveForm.errors.is_published" class="mt-1 text-sm text-red-500">
                                {{ saveForm.errors.is_published }}
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
                                ? t('pages.back_office.breaking_news.create.actions.saving')
                                : t('pages.back_office.breaking_news.create.actions.save')
                        }}
                    </button>
                </div>

            </form>

        </div>
    </div>
</template>
