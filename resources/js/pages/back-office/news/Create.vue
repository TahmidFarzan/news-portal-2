<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import MultiSelectInfinityLoadingApi from '@/components/common/multi-select/InfinityLoadingApi.vue'
import MultiSelectTaggableSelect from '@/components/common/multi-select/TaggableSelect.vue'
import TinyMCEEditor from '@/components/common/tinymce/TinyMCEEditor.vue'
import MediaSelectFromMediaLibery from '@/components/common/media/MediaSelectFromMediaLibery.vue'
import NewsImageGalleryGrid from '@/components/back-office/news/NewsImageGalleryGrid.vue'
import NewsImageGalleryDraftGrid from '@/components/back-office/news/NewsImageGalleryDraftGrid.vue'

import { computed, onMounted, nextTick, inject, watch, ref } from 'vue'
import { Head, useForm, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faSave, faEye, faEyeSlash, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { isStory as checkIsStory, isVideo as checkIsVideo, isImageGallery as checkIsImageGallery } from '@/composables/useNews'
import { fetchFromApi } from '@/composables/useSystemApi'

FontAwesomeLibrary.add(faSave, faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const { news } = defineProps({
    news: Object,
})

const isStory = ref(false)
const isVideo = ref(false)
const isImageGallery = ref(false)

const seoKeywordsKey = ref(0)

const showLocation = ref(false)

const isUpdate = computed(() => !!news?.slug)

const saveForm = useForm({
    news_type_id: news?.news_type_id ?? null,
    language_id: news?.language_id || null,
    category_id: news?.category_id || null,
    location_id: news?.location_id || null,
    event_id: news?.event_id || null,
    tag_ids: [],
    contributor_ids: [],

    title: news?.title || null,
    sub_title: news?.sub_title || null,
    content_shoulder: news?.content_shoulder || null,

    brief: news?.brief || null,
    body: news?.body || null,
    video_url: news?.video_url || null,

    gallery_image_ids: null,

    upload_feature_image_mobile: null,
    upload_feature_image: null,
    selected_feature_image_mobile_url: null,
    selected_feature_image_url: null,

    feature_image_caption: news?.feature_image?.custom_properties?.caption || null,

    is_published: news?.is_published,

    writer: news?.writer || null,
    source: news?.source || null,

    seo_brief: news?.seo_brief || null,
    seo_title: news?.seo_title || null,
    seo_keywords: news?.seo_keywords ? news?.seo_keywords.split(',') : [],

    breaking_news_id: news?.breaking_news?.id || null,
    editor_media_ids: null,
    relevant_news_ids: news?.relevant_news?.map(item => item.id) || [],
    related_news_ids: news?.related_news?.map(item => item.id) || [],
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

    return `${route('search.location-tree')}?${params.toString()}`
})

const tagApiUrl = computed(() => {
    if (!saveForm.language_id) {
        return route('search.tags')
    }

    return route('search.tags') + `?language_id=${saveForm.language_id}`
})

const contributorApiUrl = computed(() => {
    if (!saveForm.language_id) {
        return route('search.contributors')
    }

    return route('search.contributors') + `?language_id=${saveForm.language_id}`
})

const relevantOrRelatedNewsApiUrl = computed(() => {
    if (!saveForm.language_id) {
        return route('search.news')
    }

    return route('search.news') + `?language_id=${saveForm.language_id}&news_type_id=${saveForm.news_type_id}`
})

const breakingNewsApiUrl = computed(() => {
    const isSyncToNews = true;
    if (!saveForm.language_id) {
        return route('search.breaking-news')
    }

    return route('search.breaking-news') + `?language_id=${saveForm.language_id}&is_sync_to_news=${isSyncToNews}`
})

function handleSelectedFeatureImage(media) {
    saveForm.selected_feature_image_url = media?.media_url || media?.original_url || media?.url || null
    saveForm.upload_feature_image = null

    saveForm.feature_image_caption =
        media?.custom_properties?.caption
        || media?.caption
        || saveForm.feature_image_caption
}

function handleSelectedThumbnail(media) {
    saveForm.selected_feature_image_mobile_url = media?.media_url || media?.original_url || media?.url || null
    saveForm.upload_feature_image_mobile = null
}

function validateForm() {
    saveForm.clearErrors()
    let valid = true

    if (!saveForm.news_type_id) {
        saveForm.setError('news_type_id', 'News type is required.')
        valid = false
    }

    if (!saveForm.language_id) {
        saveForm.setError('language_id', 'Language is required.')
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

    if (!saveForm.body && isStory.value) {
        saveForm.setError('body', 'Body is required.')
        valid = false
    }

    if (!saveForm.video_url && isVideo.value) {
        saveForm.setError('video_url', 'Video url is required.')
        valid = false
    }

    if (
        (!saveForm.gallery_image_ids || saveForm.gallery_image_ids.length === 0)
        && !isUpdate.value
        && isImageGallery.value
    ) {
        saveForm.setError('gallery_image_ids', 'Gallery image is required.')
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

    if (saveForm.upload_feature_image_mobile) {
        if (saveForm.selected_feature_image_mobile_url) {
            saveForm.setError('upload_feature_image_mobile', 'Please use either selected feature image or uploaded feature image, not both.')
            valid = false
        }
    }

    if (saveForm.selected_feature_image_mobile_url) {
        if (saveForm.upload_feature_image_mobile) {
            saveForm.setError('upload_feature_image_mobile', 'Please use either selected feature image or uploaded feature image, not both.')
            valid = false
        }
    }

    if (!saveForm.feature_image_caption) {
        saveForm.setError('feature_image_caption', 'Feature image caption is required.')
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
            route('back-office.news.update', { slug: news?.slug }),
            { ...saveForm.data(), _method: 'patch' },
            requestConfig
        )
    } else {
        saveForm.post(route('back-office.news.save'), requestConfig)
    }
}


watch(
    () => saveForm.category_id,
    async (categoryId) => {
        showLocation.value = false
        saveForm.location_id = null

        if (!categoryId) return

        try {

            const category = await fetchFromApi(
                route('search.category', { slugOrId: categoryId })
            )

            showLocation.value = category?.has_location === true
        } catch (error) {
            showLocation.value = false
        }
    },
    { immediate: true }
)

watch(
    () => saveForm.language_id,
    () => {
        saveForm.title = null
        saveForm.sub_title = null
        saveForm.content_shoulder = null
        saveForm.brief = null
        saveForm.body = null

        saveForm.category_id = null
        saveForm.event_id = null
        saveForm.location_id = null
        saveForm.tag_ids = []
        saveForm.contributor_ids = []
        saveForm.relevant_news_ids = []
        saveForm.related_news_ids = []
        saveForm.breaking_news_id = null

        saveForm.feature_image_caption = null

        saveForm.seo_title = null
        saveForm.seo_brief = null
        saveForm.seo_keywords = []
        seoKeywordsKey.value++

        saveForm.clearErrors(
            'category_id',
            'event_id',
            'location_id',
            'tag_ids',
            'contributor_ids',
            'relevant_news_ids',
            'related_news_ids',
        )
    }
)

watch(
    () => saveForm.news_type_id,
    async (news_type_id) => {
        isStory.value = false
        isVideo.value = false
        isImageGallery.value = false

        if (!news_type_id) return

        try {
            const newsType = await fetchFromApi(
                route('search.news-type', { slugOrId: news_type_id })
            )

            isStory.value = checkIsStory(newsType)
            isVideo.value = checkIsVideo(newsType)
            isImageGallery.value = checkIsImageGallery(newsType)
        } catch (error) {
            console.error(error)
        }
    },
    { immediate: true }
)

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: 'News', href: route('back-office.news.index') },
                { text: isUpdate.value ? `${news?.title} edit` : 'News create', active: true }
            ],
        })
    )
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
                                News Type <span class="text-red-500">*</span>
                            </label>

                            <MultiSelectInfinityLoadingApi :form="saveForm" fieldName="news_type_id"
                                :selectedItem="news.news_type" :apiUrl="route('search.news-types')"
                                :error="saveForm.errors.news_type_id" :multiple="false"
                                placeholder="Select news type" />
                            <p v-if="saveForm.errors.news_type_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.news_type_id }}
                            </p>
                        </div>


                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Language <span class="text-red-500">*</span>
                            </label>

                            <MultiSelectInfinityLoadingApi :form="saveForm" fieldName="language_id"
                                :selectedItem="news?.language" :apiUrl="route('search.languages')"
                                :error="saveForm.errors.language_id" :multiple="false" placeholder="Select language" />
                            <p v-if="saveForm.errors.language_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.language_id }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Category <span class="text-red-500">*</span>
                            </label>

                            <MultiSelectInfinityLoadingApi :form="saveForm" fieldName="category_id"
                                :selectedItem="saveForm.category_id ? news?.category : null" :apiUrl="categoryApiUrl"
                                :error="saveForm.errors.category_id" selectedLabelKey="indentation_name"
                                selectedValueKey="id" apiLabelKey="indentation_name" apiValueKey="id" :multiple="false"
                                placeholder="Select category" />
                            <p v-if="saveForm.errors.category_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.category_id }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Event
                            </label>

                            <MultiSelectInfinityLoadingApi :form="saveForm" fieldName="event_id"
                                :selectedItem="saveForm.event_id ? news?.event : null" :apiUrl="eventApiUrl"
                                :error="saveForm.errors.event_id" :multiple="false" placeholder="Select event" />
                            <p v-if="saveForm.errors.event_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.event_id }}
                            </p>
                        </div>

                        <div v-if="showLocation">
                            <label class="block text-sm font-medium mb-1">
                                Location
                            </label>

                            <MultiSelectInfinityLoadingApi :form="saveForm" fieldName="location_id"
                                :selectedItem="saveForm.location_id ? news?.location : null" :apiUrl="locationApiUrl"
                                :error="saveForm.errors.location_id" :multiple="false"
                                selectedLabelKey="indentation_name" selectedValueKey="id" apiLabelKey="indentation_name"
                                apiValueKey="id" placeholder="Select location" />
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

                            <input v-model="saveForm.sub_title"
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

                            <textarea v-model="saveForm.brief" rows="4" placeholder="Enter brief"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.brief ? 'border-red-500' : 'border-gray-300'"></textarea>

                            <p v-if="saveForm.errors.brief" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.brief }}
                            </p>
                        </div>

                        <div v-if="isStory" class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">
                                Body <span class="text-red-500">*</span>
                            </label>

                            <TinyMCEEditor inputField="body" :form="saveForm" erroField="body" :isSimple="false"
                                :enableMediaUpload="true" :enableSelectFormMediaLibery="true" />

                            <p v-if="saveForm.errors.brief" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.body }}
                            </p>
                        </div>

                        <div v-if="isVideo" class="md:col-span-2">
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

                        <div v-if="isImageGallery" class="md:col-span-2">
                            <div v-if="isUpdate">
                                <div class="border border-gray-200 rounded-lg p-4 space-y-3">
                                    <NewsImageGalleryGrid :news="news" />
                                </div>
                            </div>

                            <div v-else class="border border-gray-200 rounded-lg p-4 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">
                                        Gallery Images <span class="text-red-500">*</span>
                                    </label>

                                    <p class="text-sm mt-1">
                                        <NewsImageGalleryDraftGrid :form="saveForm" fieldName="gallery_image_ids" />
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Tags
                            </label>

                            <MultiSelectInfinityLoadingApi :form="saveForm" fieldName="tag_ids"
                                :selectedItem="saveForm.tag_ids ? news?.tags : null" :apiUrl="tagApiUrl"
                                :error="saveForm.errors.tag_ids" :multiple="true" placeholder="Select tags" />
                            <p v-if="saveForm.errors.tag_ids" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.tag_ids }}
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

                                <MediaSelectFromMediaLibery galleryTitle="Feature Image"
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

                            <input v-model="saveForm.feature_image_caption"
                                class="w-full border rounded-md px-3 py-2 mt-1 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.feature_image_caption ? 'border-red-500' : 'border-gray-300'"
                                placeholder="Enter Caption" />

                            <p v-if="saveForm.errors.upload_feature_image" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.upload_feature_image }}
                            </p>

                            <p v-if="saveForm.errors.selected_feature_image_url" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.selected_feature_image_url }}
                            </p>

                            <img :src="saveForm.selected_feature_image_url || news?.feature_image?.media_url || '/uploads/images/news/story-feature-image.png'"
                                class="w-75 object-contain rounded-xl border border-gray-200 mt-2" />
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-medium">
                                    Feature Image (Mobile)
                                </label>

                                <MediaSelectFromMediaLibery galleryTitle="Feature Image (Mobile)"
                                    :fetchUrl="route('search.medias')" mediaType="image" :multiple="false"
                                    @media-selected="handleSelectedThumbnail" />
                            </div>

                            <input type="file" @change="e => {
                                saveForm.upload_feature_image_mobile = e.target.files[0] || null

                                if (saveForm.upload_feature_image_mobile) {
                                    saveForm.selected_feature_image_mobile_url = null
                                }
                            }" class="border rounded px-3 py-2 w-full"
                                :class="saveForm.errors.upload_feature_image_mobile ? 'border-red-500' : 'border-gray-300'" />


                            <p v-if="saveForm.errors.upload_feature_image_mobile" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.upload_feature_image_mobile }}
                            </p>

                            <p v-if="saveForm.errors.selected_feature_image_mobile_url"
                                class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.selected_feature_image_mobile_url }}
                            </p>

                            <img :src="saveForm.selected_feature_image_mobile_url || news?.feature_image_mobile?.media_url || '/uploads/images/news/story-feature-image.png'"
                                class="w-75 object-contain rounded-xl border border-gray-200 mt-2" />
                        </div>

                    </div>
                </div>

                <div v-if="isStory" class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">Contributor Settings</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Contributors
                            </label>

                            <MultiSelectInfinityLoadingApi :form="saveForm" fieldName="contributor_ids"
                                :selectedItem="saveForm.contributor_ids ? news?.contributors : null"
                                :apiUrl="contributorApiUrl" :error="saveForm.errors.contributor_ids" :multiple="true"
                                placeholder="Select contributors" />
                            <p v-if="saveForm.errors.contributor_ids" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.contributor_ids }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Writer
                            </label>

                            <input v-model="saveForm.writer"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.writer ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.writer" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.writer }}
                            </p>
                        </div>

                    </div>
                </div>

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">Extra Settings</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Relevant News
                            </label>

                            <MultiSelectInfinityLoadingApi :form="saveForm" fieldName="relevant_news_ids"
                                :selectedItem="news?.relevant_news || null" :apiUrl="relevantOrRelatedNewsApiUrl"
                                :error="saveForm.errors.relevant_news_ids" selectedLabelKey="title_with_published_at"
                                selectedValueKey="id" apiLabelKey="title_with_published_at" apiValueKey="id"
                                :multiple="true" placeholder="Select relevant news" />
                            <p v-if="saveForm.errors.relevant_news_ids" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.relevant_news_ids }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Related News
                            </label>

                            <MultiSelectInfinityLoadingApi :form="saveForm" fieldName="related_news_ids"
                                :selectedItem="news?.related_news || null" :apiUrl="relevantOrRelatedNewsApiUrl"
                                :error="saveForm.errors.related_news_ids" selectedLabelKey="title_with_published_at"
                                selectedValueKey="id" apiLabelKey="title_with_published_at" apiValueKey="id"
                                :multiple="true" placeholder="Select related news" />
                            <p v-if="saveForm.errors.related_news_ids" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.related_news_ids }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Breaking News
                            </label>

                            <MultiSelectInfinityLoadingApi :form="saveForm" fieldName="breaking_news_id"
                                :selectedItem="news?.breaking_news || null" :apiUrl="breakingNewsApiUrl"
                                :error="saveForm.errors.breaking_news_id" selectedLabelKey="title" selectedValueKey="id"
                                apiLabelKey="title" apiValueKey="id" :multiple="false"
                                placeholder="Select breaking news" />
                            <p v-if="saveForm.errors.breaking_news_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.breaking_news_id }}
                            </p>
                        </div>

                    </div>
                </div>

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">Publish Settings</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div v-if="isStory">
                            <label class="block text-sm font-medium mb-1">
                                Source
                            </label>

                            <input v-model="saveForm.source"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.source ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.source" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.source }}
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

                            <MultiSelectTaggableSelect :key="seoKeywordsKey" :selectedItem="saveForm.seo_keywords" fieldName="seo_keywords"
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
