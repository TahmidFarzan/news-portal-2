<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import MultiSelectInfinityLoadingApi from '@/components/common/multi-select/InfinityLoadingApi.vue'
import { computed, onMounted, nextTick, inject, watch, ref } from 'vue'
import { Head, useForm, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faSave, faEye, faEyeSlash, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { fetchFromApi } from '@/composables/useSystemApi'

FontAwesomeLibrary.add(faSave, faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const { breakingNews } = defineProps({
    breakingNews: Object,
})

const isUpdate = computed(() => !!breakingNews?.slug)

const saveForm = useForm({
    language_id: breakingNews?.language_id || null,
    title: breakingNews?.title || null,

    is_published: breakingNews?.is_published,

    news_id:breakingNews?.news_id,
})

const newsApiUrl = computed(() => {
    if (!saveForm.language_id) {
        return route('search.newses')
    }

    return route('search.newses') + `?language_id=${saveForm.language_id}`
})

function validateForm() {
    saveForm.clearErrors()
    let valid = true


    if (!saveForm.language_id) {
        saveForm.setError('language_id', 'Language is required.')
        valid = false
    }

    if (!saveForm.title) {
        saveForm.setError('title', 'Title is required.')
        valid = false
    }

    return valid
}

function handleSave() {
    if (saveForm.processing) return

    if (!validateForm()) return

    saveForm.processing = true

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
        },
        onFinish: () => {
            saveForm.processing = false
        }
    }

    if (isUpdate.value) {
        intertiaJsRoute.post(
            route('back-office.breaking-newses.update', { slug: breakingNews?.slug }),
            { ...saveForm.data(), _method: 'patch' },
            requestConfig
        )
    } else {
        saveForm.post(route('back-office.breaking-newses.save'), requestConfig)
    }
}


watch(
    () => saveForm.language_id,
    () => {
        saveForm.news_id = null
        saveForm.clearErrors(
            'news_id',
        )
    }
)

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: 'Breaking newses', href: route('back-office.breaking-newses.index') },
                { text: isUpdate.value ? `${breakingNews?.title} edit` : 'Breaking news create', active: true }
            ],
        })
    )
})

</script>

<template>

    <Head :title="isUpdate ? `${breakingNews?.title} edit` : 'Breaking news create'" />

    <div class="w-full">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6">

            <form @submit.prevent="handleSave" class="space-y-6">

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">Basic Information</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Language <span class="text-red-500">*</span>
                            </label>

                            <MultiSelectInfinityLoadingApi :form="saveForm" fieldName="language_id"
                                :selectedItem="breakingNews?.language" :apiUrl="route('search.languages')"
                                :error="saveForm.errors.language_id" :multiple="false" placeholder="Select language" />
                            <p v-if="saveForm.errors.language_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.language_id }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Title <span class="text-red-500">*</span>
                            </label>

                            <input v-model="saveForm.title"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.title ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.title" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.title }}
                            </p>
                        </div>

                    </div>
                </div>

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">News Settings</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                News
                            </label>

                            <MultiSelectInfinityLoadingApi :form="saveForm" fieldName="news_id"
                                :selectedItem="saveForm.news_id ? breakingNews?.news : null"
                                :apiUrl="newsApiUrl" :error="saveForm.errors.news_id" selectedLabelKey="title_with_published_at"
                                selectedValueKey="id" apiLabelKey="title_with_published_at" apiValueKey="id" :multiple="false"
                                placeholder="Select news" />
                            <p v-if="saveForm.errors.news_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.news_id }}
                            </p>
                        </div>

                    </div>
                </div>

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">Publish Settings</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Published
                            </label>

                            <label class="inline-flex cursor-pointer items-center gap-3">
                                <input v-model="saveForm.is_published" type="checkbox" class="peer sr-only" />

                                <span class="relative h-7 w-14 rounded-full bg-gray-300 transition
                                    after:absolute after:left-1 after:top-1 after:h-5 after:w-5
                                    after:rounded-full after:bg-white after:transition-all after:content-['']
                                    peer-checked:bg-green-600 peer-checked:after:translate-x-7">
                                </span>

                                <span class="text-sm text-gray-600">
                                    {{ saveForm.is_published ? 'Yes' : 'No' }}
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
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md flex items-center gap-2 transition">
                        <FontAwesomeIcon v-if="saveForm.processing" icon="spinner" spin />
                        <FontAwesomeIcon v-else icon="save" />
                        Save
                    </button>
                </div>

            </form>

        </div>
    </div>
</template>
