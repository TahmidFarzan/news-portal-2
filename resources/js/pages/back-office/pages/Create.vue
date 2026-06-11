<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import MultiSelectInfinityLoadingApi from '@/components/common/multi-select/InfinityLoadingApi.vue'
import MultiSelectTaggableSelect from '@/components/common/multi-select/TaggableSelect.vue'
import TinyMCEEditor from '@/components/common/tinymce/TinyMCEEditor.vue'

import { computed, onMounted, nextTick, watch, ref } from 'vue'
import { Head, useForm, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faSave, faSpinner } from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faSave, faSpinner)

defineOptions({ layout: Layout })

const { page } = defineProps({
    page: {
        type: Object,
        default: null,
    },
})

const seoKeywordsKey = ref(0)

const isUpdate = computed(() => !!page?.slug)

const saveForm = useForm({
    language_id: page?.language_id || null,
    title: page?.title || null,
    brief: page?.brief || null,
    body: page?.body || null,
    has_parent: page?.has_parent || false,
    parent_id: page?.parent_id || null,
    is_default: page?.is_default ?? false,
    is_published: page?.is_published ?? false,
    seo_title: page?.seo_title || null,
    seo_brief: page?.seo_brief || null,
    seo_keywords: page?.seo_keywords ? page.seo_keywords.split(',') : [],
})

const pageApiUrl = computed(() => {
    if (!saveForm.language_id) {
        return route('search.page-tree')
    }

    return route('search.page-tree') + `?language_id=${saveForm.language_id}`
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

    if (!saveForm.brief) {
        saveForm.setError('brief', 'Brief is required.')
        valid = false
    }

    if (!saveForm?.is_default) {
        if (!saveForm.body) {
            saveForm.setError('body', 'Body is required.')
            valid = false
        }
    }

    if (saveForm.has_parent && !saveForm.parent_id) {
        saveForm.setError('parent_id', 'Page is required.')
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
        },
    }

    if (isUpdate.value) {
        intertiaJsRoute.post(
            route('back-office.pages.update', { slug: page?.slug }),
            { ...saveForm.data(), _method: 'patch' },
            requestConfig
        )
    } else {
        saveForm.post(route('back-office.pages.save'), requestConfig)
    }
}

watch(
    () => saveForm.language_id,
    () => {
        saveForm.title = null
        saveForm.brief = null
        saveForm.body = null
        saveForm.seo_title = null
        saveForm.seo_brief = null
        saveForm.seo_keywords = []
        seoKeywordsKey.value++
    }
)

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: 'Page', href: route('back-office.pages.index') },
                { text: isUpdate.value ? `${page?.title} edit` : 'Page create', active: true }
            ],
        })
    )
})
</script>

<template>

    <Head :title="isUpdate ? `${page?.title} edit` : 'Page create'" />

    <div class="w-full">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6">

            <form @submit.prevent="handleSave" class="space-y-6">

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">Basic Information</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div v-if="saveForm?.is_default && page?.default_use_as" class="md:col-span-2">
                            <div class="rounded-md border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                                Default use as : {{ page?.default_use_as }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Language <span class="text-red-500">*</span>
                            </label>

                            <MultiSelectInfinityLoadingApi :form="saveForm" fieldName="language_id"
                                :selectedItem="page?.language" :apiUrl="route('search.languages')"
                                :error="saveForm.errors.language_id" :multiple="false" placeholder="Select language" />

                            <p v-if="saveForm.errors.language_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.language_id }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Title <span class="text-red-500">*</span>
                            </label>

                            <input v-model="saveForm.title" type="text" placeholder="Enter title"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.title ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.title" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.title }}
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">
                                Brief <span class="text-red-500">*</span>
                            </label>

                            <textarea v-model="saveForm.brief" rows="4" placeholder="Enter brief"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.brief ? 'border-red-500' : 'border-gray-300'"></textarea>

                            <p v-if="saveForm.errors.brief" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.brief }}
                            </p>
                        </div>

                        <div class="md:col-span-2" v-if="!saveForm?.is_default">
                            <label class="block text-sm font-medium mb-1">
                                Body <span class="text-red-500">*</span>
                            </label>

                            <TinyMCEEditor inputField="body" :form="saveForm" erroField="body" :isSimple="false"
                                :enableMediaUpload="false" :enableSelectFormMediaLibery="false" />

                            <p v-if="saveForm.errors.body" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.body }}
                            </p>
                        </div>

                    </div>
                </div>

                 <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">Hierarchy</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">

                        <div>
                            <label class="block text-sm font-medium mb-2">
                                Has Parent
                            </label>

                            <button type="button" @click="saveForm.has_parent = !saveForm.has_parent" :class="[
                                'relative inline-flex h-6 w-11 items-center rounded-full transition',
                                saveForm.has_parent ? 'bg-blue-600' : 'bg-gray-300'
                            ]">
                                <span :class="[
                                    'inline-block h-5 w-5 transform rounded-full bg-white transition',
                                    saveForm.has_parent ? 'translate-x-5' : 'translate-x-1'
                                ]" />
                            </button>
                        </div>

                        <div v-if="saveForm.has_parent">
                            <label class="block text-sm font-medium mb-1">
                                Parent <span class="text-red-500">*</span>
                            </label>

                            <MultiSelectInfinityLoadingApi :selectedItem="page?.parent"
                                fieldName="parent_id" :form="saveForm" :apiUrl="pageApiUrl"
                                :error="saveForm.errors.parent_id" selectedLabelKey="indentation_title"
                                selectedValueKey="id" apiLabelKey="indentation_title" apiValueKey="id"
                                :multiple="false" placeholder="Select parent" />

                            <p v-if="saveForm.errors.parent_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.parent_id }}
                            </p>
                        </div>

                    </div>
                </div>

                <div v-if="!saveForm?.is_default" class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
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

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">SEO Settings</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                SEO Title
                            </label>

                            <input v-model="saveForm.seo_title" type="text" placeholder="Enter SEO title"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.seo_title ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.seo_title" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.seo_title }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                SEO Brief
                            </label>

                            <textarea v-model="saveForm.seo_brief" rows="3" placeholder="Enter SEO brief"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.seo_brief ? 'border-red-500' : 'border-gray-300'"></textarea>

                            <p v-if="saveForm.errors.seo_brief" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.seo_brief }}
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">
                                SEO Keywords
                            </label>

                            <MultiSelectTaggableSelect :key="seoKeywordsKey" :selectedItem="saveForm.seo_keywords"
                                fieldName="seo_keywords" :form="saveForm" :error="saveForm.errors.seo_keywords"
                                placeholder="Add keywords" />

                            <p v-if="saveForm.errors.seo_keywords" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.seo_keywords }}
                            </p>
                        </div>

                    </div>
                </div>

                <div class="flex justify-center">
                    <button type="submit" :disabled="saveForm.processing"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md flex items-center gap-2 transition disabled:opacity-60 disabled:cursor-not-allowed">
                        <FontAwesomeIcon v-if="saveForm.processing" icon="spinner" spin />
                        <FontAwesomeIcon v-else icon="save" />
                        Save
                    </button>
                </div>

            </form>

        </div>
    </div>
</template>
