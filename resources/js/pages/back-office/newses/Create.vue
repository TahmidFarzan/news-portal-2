<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import MultiSelectInfinityLoadingApi from '@/components/common/multi-select/InfinityLoadingApi.vue'
import MultiSelectTaggableSelect from '@/components/common/multi-select/TaggableSelect.vue'
import TinyMCEEditor from '@/components/common/tinymce/TinyMCEEditor.vue'
import MediaSelectFromMediaLibery from '@/components/common/media/MediaSelectFromMediaLibery.vue'

import { isStory, isVideo } from '@/composables/useNews'

import axios from 'axios'
import { computed, onMounted, nextTick, inject, watch, ref } from 'vue'
import { Head, useForm, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faSave, faEye, faEyeSlash, faSpinner } from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faSave, faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const pageReady = inject("pageReady")

const { news } = defineProps({
    news: Object,
})

const showLocation = ref(false)

const isUpdate = computed(() => !!news?.slug)

const saveForm = useForm({
    language_id: news?.language_id || null,
    news_type: news?.news_type || "Story",
    category_id: news?.category_id || null,

    location_id: news?.location_id || null,
    event_id: news?.event_id || null,
    tag_ids: [],
    contributor_ids: [],

    is_story: news?.is_story || true,
    is_video: news?.is_video || false,

    title: news?.title || null,
    sub_title: news?.sub_title || null,
    content_shoulder: news?.content_shoulder || null,

    brief: news?.brief || null,
    body: news?.body || null,
    video_url: news?.video_url || null,

    upload_thumbnail: null,
    upload_feature_image: null,
    selected_thumbnail_url: null,
    selected_feature_image_url: null,

    upload_feature_image_caption: news?.feature_image?.custom_properties?.caption || null,

    is_published: news?.is_published,
    page_section: news?.page_section,

    seo_brief: news?.seo_brief || null,
    seo_title: news?.seo_title || null,
    seo_keywords: news?.seo_keywords ? news?.seo_keywords.split(',') : [],


    editor_media_ids: null
})


const categoryApiUrl = computed(() => {
    if (!saveForm.language_id) {
        return route('search.category-tree')
    }

    return route('search.category-tree') + `?language_id=${saveForm.language_id}`
})

const eventApiUrl = computed(() => {
    if (!saveForm.language_id) {
        return route('search.events')
    }

    return route('search.events') + `?language_id=${saveForm.language_id}`
})

const locationApiUrl = computed(() => {
    const params = new URLSearchParams()

    if (saveForm.language_id) {
        params.append('language_id', saveForm.language_id)
    }

    if (saveForm.category_id) {
        params.append('category_id', saveForm.category_id)
    }

    return `${route('search.locations')}?${params.toString()}`
})

function handleSelectedFeatureImage(media) {
    saveForm.selected_feature_image_url = media?.media_url || media?.original_url || media?.url || null
    saveForm.upload_feature_image = null

    saveForm.upload_feature_image_caption =
        media?.custom_properties?.caption
        || media?.caption
        || saveForm.upload_feature_image_caption
}

function handleSelectedThumbnail(media) {
    saveForm.selected_thumbnail_url = media?.media_url || media?.original_url || media?.url || null
    saveForm.upload_thumbnail = null
}

function validateForm() {
    saveForm.clearErrors()
    let valid = true

    if (!saveForm.language_id) {
        saveForm.setError('language_id', 'Language is required.')
        valid = false
    }

    if (!saveForm.news_type) {
        saveForm.setError('news_type', 'News type is required.')
        valid = false
    }

    if (!saveForm.category_id) {
        saveForm.setError('category_id', 'Category is required.')
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

    if (!saveForm.body && saveForm.is_story) {
        saveForm.setError('body', 'Body is required.')
        valid = false
    }

    if (!saveForm.video_url && saveForm.is_video) {
        saveForm.setError('video_url', 'Video url is required.')
        valid = false
    }


    if (!news?.feature_image) {
        if (saveForm.upload_feature_image) {
            if (saveForm.selected_feature_image_url) {
                saveForm.setError('upload_feature_image', 'Please use either selected feature image or uploaded feature image, not both.')
                valid = false
            }
        }

        if (saveForm.selected_feature_image_url) {
            if (saveForm.upload_feature_image) {
                saveForm.setError('upload_feature_image', 'Please use either selected feature image or uploaded feature image, not both.')
                valid = false
            }
        }
    }

    if (saveForm.upload_thumbnail) {
        if (saveForm.selected_thumbnail_url) {
            saveForm.setError('upload_thumbnail', 'Please use either selected feature image or uploaded feature image, not both.')
            valid = false
        }
    }

    if (saveForm.selected_thumbnail_url) {
        if (saveForm.upload_thumbnail) {
            saveForm.setError('upload_thumbnail', 'Please use either selected feature image or uploaded feature image, not both.')
            valid = false
        }
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
            route('back-office.newses.update', { slug: news?.slug }),
            { ...saveForm.data(), _method: 'patch' },
            requestConfig
        )
    } else {
        saveForm.post(route('back-office.newses.save'), requestConfig)
    }
}

watch(
    () => saveForm.category_id,
    async (categoryId) => {
        showLocation.value = false
        saveForm.location_id = null

        if (!categoryId) return

        try {
            const { data } = await axios.get(
                route('search.category', { slugOrId: categoryId })
            )

            showLocation.value = data?.has_location === true
        } catch (error) {
            showLocation.value = false
        }
    },
    { immediate: true }
)

watch(
    () => saveForm.news_type,
    (newsType) => {
        saveForm.is_story = isStory(newsType || '')
        saveForm.is_video = isVideo(newsType || '')

        if (saveForm.is_story) {
            saveForm.video_url = null
        }

        if (saveForm.is_video) {
            saveForm.body = null
        }
    },
    { immediate: true }
)

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: 'Dashboard', href: route('auth-user.dashboard.index') },
                { text: 'Newses', href: route('back-office.newses.index') },
                { text: isUpdate.value ? `${news?.title} edit` : 'News create', active: true }
            ],
        })
    )

    pageReady.value = true
})
</script>

<template>

    <Head :title="isUpdate ? `${news?.title} edit` : 'News create'" />

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

                            <MultiSelectInfinityLoadingApi v-if="pageReady" :form="saveForm" fieldName="language_id"
                                :selectedItem="news?.language" :apiUrl="route('search.languages')"
                                :error="saveForm.errors.language_id" :multiple="false" placeholder="Select language" />
                            <p v-if="saveForm.errors.language_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.language_id }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                News Type <span class="text-red-500">*</span>
                            </label>

                            <MultiSelectInfinityLoadingApi v-if="pageReady" :form="saveForm" fieldName="news_type"
                                :selectedItem="saveForm.news_type" :apiUrl="route('search.news-types')"
                                :error="saveForm.errors.news_type" :multiple="false" placeholder="Select news type" />
                            <p v-if="saveForm.errors.news_type" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.news_type }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Category <span class="text-red-500">*</span>
                            </label>

                            <MultiSelectInfinityLoadingApi v-if="pageReady" :form="saveForm" fieldName="category_id"
                                :selectedItem="news?.category" :apiUrl="categoryApiUrl"
                                :error="saveForm.errors.category_id" :multiple="false" placeholder="Select category" />
                            <p v-if="saveForm.errors.category_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.category_id }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Event
                            </label>

                            <MultiSelectInfinityLoadingApi v-if="pageReady" :form="saveForm" fieldName="event_id"
                                :selectedItem="news?.event" :apiUrl="eventApiUrl" :error="saveForm.errors.event_id"
                                :multiple="false" placeholder="Select event" />
                            <p v-if="saveForm.errors.event_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.event_id }}
                            </p>
                        </div>

                        <div v-if="showLocation">
                            <label class="block text-sm font-medium mb-1">
                                Location
                            </label>

                            <MultiSelectInfinityLoadingApi v-if="pageReady" :form="saveForm" fieldName="location_id"
                                :selectedItem="news?.location" :apiUrl="locationApiUrl"
                                :error="saveForm.errors.location_id" :multiple="false" placeholder="Select location" />
                            <p v-if="saveForm.errors.location_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.location_id }}
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

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Sub Title
                            </label>

                            <input v-model="saveForm.title"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.sub_title ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.sub_title" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.sub_title }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Content shoulder
                            </label>

                            <input v-model="saveForm.content_shoulder"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.content_shoulder ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.content_shoulder" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.content_shoulder }}
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">
                                Brief <span class="text-red-500">*</span>
                            </label>

                            <textarea v-if="pageReady" v-model="saveForm.brief" rows="4" placeholder="Enter brief"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.brief ? 'border-red-500' : 'border-gray-300'"></textarea>

                            <p v-if="saveForm.errors.brief" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.brief }}
                            </p>
                        </div>

                        <div v-if="saveForm.is_story" class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">
                                Body <span class="text-red-500">*</span>
                            </label>

                            <TinyMCEEditor inputField="body" :form="saveForm" erroField="body" :isSimple="false"
                                :enableMediaUpload="true" :enableSelectFormMediaLibery="true" />

                            <p v-if="saveForm.errors.brief" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.body }}
                            </p>
                        </div>

                        <div v-if="saveForm.is_video" class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">
                                Video Url <span class="text-red-500">*</span>
                            </label>

                            <input v-model="saveForm.video_url"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.video_url ? 'border-red-500' : 'border-gray-300'" />


                            <p v-if="saveForm.errors.brief" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.video_url }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Tags
                            </label>

                            <MultiSelectInfinityLoadingApi v-if="pageReady" :form="saveForm" fieldName="tag_ids"
                                :selectedItem="news?.tags" :apiUrl="route('search.tags')"
                                :error="saveForm.errors.tag_ids" :multiple="true" placeholder="Select tags" />
                            <p v-if="saveForm.errors.tag_ids" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.tag_ids }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Contributors
                            </label>

                            <MultiSelectInfinityLoadingApi v-if="pageReady" :form="saveForm" fieldName="contributor_ids"
                                :selectedItem="news?.contributors" :apiUrl="route('search.contributors')"
                                :error="saveForm.errors.contributor_ids" :multiple="true"
                                placeholder="Select contributors" />
                            <p v-if="saveForm.errors.contributor_ids" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.contributor_ids }}
                            </p>
                        </div>

                    </div>
                </div>

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">Image Settings</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-medium">
                                    Feature Image <span class="text-red-500">*</span>
                                </label>

                                <MediaSelectFromMediaLibery v-if="pageReady" galleryTitle="Feature Image"
                                    :fetchUrl="route('search.medias')" mediaType="image" :multiple="false"
                                    @media-selected="handleSelectedFeatureImage" />
                            </div>

                            <input type="file" @change="e => {
                                saveForm.upload_feature_image = e.target.files[0] || null

                                if (saveForm.upload_feature_image) {
                                    saveForm.selected_feature_image_url = null
                                }
                            }" class="border rounded px-3 py-2 w-full"
                                :class="saveForm.errors.upload_feature_image ? 'border-red-500' : 'border-gray-300'" />

                            <input v-model="saveForm.upload_feature_image_caption"
                                class="w-full border rounded-md px-3 py-2 mt-1 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.upload_feature_image_caption ? 'border-red-500' : 'border-gray-300'"
                                placeholder="Enter Caption" />

                            <p v-if="saveForm.errors.upload_feature_image" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.upload_feature_image }}
                            </p>

                            <p v-if="saveForm.errors.selected_feature_image_url" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.selected_feature_image_url }}
                            </p>

                            <img :src="saveForm.selected_feature_image_url || news?.feature_image?.media_url || '/uploads/images/news/feature-image.png'"
                                class="object-cover rounded-xl border border-gray-200 mt-2" />
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-medium">
                                    Thumbnail
                                </label>

                                <MediaSelectFromMediaLibery v-if="pageReady" galleryTitle="Thumbnail"
                                    :fetchUrl="route('search.medias')" mediaType="image" :multiple="false"
                                    @media-selected="handleSelectedThumbnail" />
                            </div>

                            <input type="file" @change="e => {
                                saveForm.upload_thumbnail = e.target.files[0] || null

                                if (saveForm.upload_thumbnail) {
                                    saveForm.selected_thumbnail_url = null
                                }
                            }" class="border rounded px-3 py-2 w-full"
                                :class="saveForm.errors.upload_thumbnail ? 'border-red-500' : 'border-gray-300'" />


                            <p v-if="saveForm.errors.upload_thumbnail" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.upload_thumbnail }}
                            </p>

                            <p v-if="saveForm.errors.selected_thumbnail_url" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.selected_thumbnail_url }}
                            </p>

                            <img :src="saveForm.selected_thumbnail_url || news?.thumbnail?.media_url || '/uploads/images/news/feature-image.png'"
                                class="object-cover rounded-xl border border-gray-200 mt-2" />
                        </div>

                    </div>
                </div>

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">Publish Settings</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Page section
                            </label>

                            <MultiSelectInfinityLoadingApi v-if="pageReady" :form="saveForm" fieldName="page_section"
                                :selectedItem="news?.page_section" :apiUrl="route('search.page-sections')"
                                :error="saveForm.errors.page_section" :multiple="false"
                                placeholder="Select page section" />

                            <p v-if="saveForm.errors.page_section" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.page_section }}
                            </p>
                        </div>

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

                            <textarea v-if="pageReady" v-model="saveForm.seo_brief" rows="3"
                                placeholder="Enter SEO brief"
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

                            <MultiSelectTaggableSelect :selectedItem="saveForm.seo_keywords" fieldName="seo_keywords"
                                :form="saveForm" :error="saveForm.errors.seo_keywords" placeholder="Add keywords" />

                            <p v-if="saveForm.errors.seo_keywords" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.seo_keywords }}
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
