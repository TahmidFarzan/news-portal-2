<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import SelectInfinityLoadingApi from '@/components/common/multi-select/SelectInfinityLoadingApi.vue'
import SelectTaggable from '@/components/common/multi-select/SelectTaggable.vue'
import Editor from '@/components/common/tinymce/Editor.vue'
import MediaSelectFromMediaLibery from '@/components/common/media/MediaSelectFromMediaLibery.vue'
import NewsImageGalleryGrid from '@/components/back-office/news/NewsImageGalleryGrid.vue'
import NewsImageGalleryDraftGrid from '@/components/back-office/news/NewsImageGalleryDraftGrid.vue'

import { computed, onMounted, nextTick, watch, ref } from 'vue'
import { Head, useForm, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faSave, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { isStory as checkIsStory, isVideo as checkIsVideo, isImageGallery as checkIsImageGallery } from '@/composables/useNews'
import { fetchFromApi } from '@/composables/useApiClient'
import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faSave, faSpinner)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const { news = {} } = defineProps({
    news: {
        type: Object,
        default: () => ({}),
    },
})

const isStory = ref(false)
const isVideo = ref(false)
const isImageGallery = ref(false)
const seoKeywordsKey = ref(0)
const showLocation = ref(false)

const isUpdate = computed(() => !!news?.slug)

const pageTitle = computed(() => {
    return isUpdate.value
        ? `${news?.title} ${t('common.actions.edit')}`
        : t('admin.news.create.form.createPageTitle')
})

const saveForm = useForm({
    news_type_id: news?.news_type_id ?? null,
    language_id: news?.language_id ?? null,
    category_id: news?.category_id ?? null,
    location_id: news?.location_id ?? null,
    event_id: news?.event_id ?? null,
    tag_ids: news?.tags?.map(item => item.id) ?? [],
    contributor_ids: news?.contributors?.map(item => item.id) ?? [],

    title: news?.title ?? null,
    sub_title: news?.sub_title ?? null,
    content_shoulder: news?.content_shoulder ?? null,

    brief: news?.brief ?? null,
    body: news?.body ?? null,
    video_url: news?.video_url ?? null,

    gallery_image_ids: null,

    upload_feature_image_mobile: null,
    upload_feature_image: null,
    selected_feature_image_mobile_url: null,
    selected_feature_image_url: null,

    feature_image_caption: news?.feature_image?.custom_properties?.caption ?? null,

    is_published: news?.is_published ?? false,

    writer: news?.writer ?? null,
    source: news?.source ?? null,

    seo_brief: news?.seo_brief ?? null,
    seo_title: news?.seo_title ?? null,
    seo_keywords: news?.seo_keywords ? news.seo_keywords.split(',') : [],

    breaking_news_id: news?.breaking_news?.id ?? null,
    editor_media_ids: null,
    relevant_news_ids: news?.relevant_news?.map(item => item.id) ?? [],
    related_news_ids: news?.related_news?.map(item => item.id) ?? [],

    clear_cache: false,
})

const categoryApiUrl = computed(() => {
    return saveForm.language_id
        ? `${route('search.category-tree')}?language_id=${saveForm.language_id}`
        : route('search.category-tree')
})

const eventApiUrl = computed(() => {
    return saveForm.language_id
        ? `${route('search.events')}?language_id=${saveForm.language_id}`
        : route('search.events')
})

const locationApiUrl = computed(() => {
    const params = new URLSearchParams()

    if (saveForm.language_id) params.append('language_id', saveForm.language_id)
    if (saveForm.category_id) params.append('category_id', saveForm.category_id)

    return `${route('search.location-tree')}?${params.toString()}`
})

const tagApiUrl = computed(() => {
    return saveForm.language_id
        ? `${route('search.tags')}?language_id=${saveForm.language_id}`
        : route('search.tags')
})

const contributorApiUrl = computed(() => {
    return saveForm.language_id
        ? `${route('search.contributors')}?language_id=${saveForm.language_id}`
        : route('search.contributors')
})

const relevantOrRelatedNewsApiUrl = computed(() => {
    return saveForm.language_id
        ? `${route('search.news')}?language_id=${saveForm.language_id}&news_type_id=${saveForm.news_type_id}`
        : route('search.news')
})

const breakingNewsApiUrl = computed(() => {
    const isSyncToNews = true

    return saveForm.language_id
        ? `${route('search.breaking-news')}?language_id=${saveForm.language_id}&is_sync_to_news=${isSyncToNews}`
        : route('search.breaking-news')
})

function handleSelectedFeatureImage(media) {
    saveForm.selected_feature_image_url = media?.preview_url || media?.original_url || null
    saveForm.upload_feature_image = null

    saveForm.feature_image_caption =
        media?.custom_properties?.caption ||
        media?.caption ||
        saveForm.feature_image_caption
}

function handleSelectedThumbnail(media) {
    saveForm.selected_feature_image_mobile_url = media?.preview_url || media?.original_url || null
    saveForm.upload_feature_image_mobile = null
}

function validateForm() {
    saveForm.clearErrors()

    let valid = true

    const requiredRules = [
        ['news_type_id', t('admin.news.create.form.validation.newsTypeRequired')],
        ['language_id', t('common.validation.languageIsRequired')],
        ['category_id', t('admin.news.create.form.validation.categoryRequired')],
        ['title', t('common.validation.titleIsRequired')],
        ['brief', t('common.validation.briefIsRequired')],
    ]

    requiredRules.forEach(([field, message]) => {
        if (!saveForm[field]) {
            saveForm.setError(field, message)
            valid = false
        }
    })

    if (!saveForm.body && isStory.value) {
        saveForm.setError('body', t('common.validation.bodyIsRequired'))
        valid = false
    }

    if (!saveForm.video_url && isVideo.value) {
        saveForm.setError('video_url', t('admin.news.create.form.validation.videoUrlRequired'))
        valid = false
    }

    if ((!saveForm.gallery_image_ids || saveForm.gallery_image_ids.length === 0) && !isUpdate.value && isImageGallery.value) {
        saveForm.setError('gallery_image_ids', t('admin.news.create.form.validation.galleryImageRequired'))
        valid = false
    }

    if (saveForm.upload_feature_image && saveForm.selected_feature_image_url) {
        saveForm.setError('upload_feature_image', t('admin.news.create.form.validation.useOneFeatureImage'))
        valid = false
    }

    if (saveForm.upload_feature_image_mobile && saveForm.selected_feature_image_mobile_url) {
        saveForm.setError('upload_feature_image_mobile', t('admin.news.create.form.validation.useOneMobileImage'))
        valid = false
    }

    if (!saveForm.feature_image_caption) {
        saveForm.setError('feature_image_caption', t('admin.news.create.form.validation.featureImageCaptionRequired'))
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
        onError: errors => {
            saveForm.clearErrors()
            saveForm.setError(errors)
        },
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
    async categoryId => {
        showLocation.value = false
        saveForm.location_id = null

        if (!categoryId) return

        try {
            const category = await fetchFromApi(route('search.category', { slugOrId: categoryId }))
            showLocation.value = category?.has_location === true
        } catch {
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

        saveForm.clearErrors()
    }
)

watch(
    () => saveForm.news_type_id,
    async newsTypeId => {
        isStory.value = false
        isVideo.value = false
        isImageGallery.value = false

        if (!newsTypeId) return

        try {
            const newsType = await fetchFromApi(route('search.news-type', { slugOrId: newsTypeId }))

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
                { text: t('common.labels.news') || 'News', href: route('back-office.news.index') },
                { text: pageTitle.value, active: true },
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
                    <h3 class="text-base font-semibold">{{ t('common.labels.basicInformation') }}</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.newsType') }} <span class="text-red-500">*</span>
                            </label>

                            <SelectInfinityLoadingApi :form="saveForm" fieldName="news_type_id"
                                :selectedItem="news?.news_type" :apiUrl="route('search.news-types')"
                                :error="saveForm.errors.news_type_id" :multiple="false"
                                :placeholder="t('admin.news.create.form.newsTypePlaceholder')" />

                            <p v-if="saveForm.errors.news_type_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.news_type_id }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.language') }} <span class="text-red-500">*</span>
                            </label>

                            <SelectInfinityLoadingApi :form="saveForm" fieldName="language_id"
                                :selectedItem="news?.language" :apiUrl="route('search.languages')"
                                :error="saveForm.errors.language_id" :multiple="false"
                                :placeholder="t('common.placeholders.selectLanguage')" />

                            <p v-if="saveForm.errors.language_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.language_id }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.category') }} <span class="text-red-500">*</span>
                            </label>

                            <SelectInfinityLoadingApi :form="saveForm" fieldName="category_id"
                                :selectedItem="saveForm.category_id ? news?.category : null" :apiUrl="categoryApiUrl"
                                :error="saveForm.errors.category_id" selectedLabelKey="indentation_name"
                                selectedValueKey="id" apiLabelKey="indentation_name" apiValueKey="id" :multiple="false"
                                :placeholder="t('common.placeholders.selectCategory')" />

                            <p v-if="saveForm.errors.category_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.category_id }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.event') }}
                            </label>

                            <SelectInfinityLoadingApi :form="saveForm" fieldName="event_id"
                                :selectedItem="saveForm.event_id ? news?.event : null" :apiUrl="eventApiUrl"
                                :error="saveForm.errors.event_id" :multiple="false"
                                :placeholder="t('admin.news.create.form.eventPlaceholder')" />

                            <p v-if="saveForm.errors.event_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.event_id }}
                            </p>
                        </div>

                        <div v-if="showLocation">
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.location') }}
                            </label>

                            <SelectInfinityLoadingApi :form="saveForm" fieldName="location_id"
                                :selectedItem="saveForm.location_id ? news?.location : null" :apiUrl="locationApiUrl"
                                :error="saveForm.errors.location_id" :multiple="false"
                                selectedLabelKey="indentation_name" selectedValueKey="id" apiLabelKey="indentation_name"
                                apiValueKey="id" :placeholder="t('admin.news.create.form.locationPlaceholder')" />

                            <p v-if="saveForm.errors.location_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.location_id }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.title') }} <span class="text-red-500">*</span>
                            </label>

                            <input v-model="saveForm.title" :placeholder="t('common.placeholders.enterTitle')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.title ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.title" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.title }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.subtitle') }}
                            </label>

                            <input v-model="saveForm.sub_title" :placeholder="t('admin.news.create.form.subTitlePlaceholder')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.sub_title ? 'border-red-500' : 'border-gray-300'" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.contentShoulder') }}
                            </label>

                            <input v-model="saveForm.content_shoulder"
                                :placeholder="t('admin.news.create.form.contentShoulderPlaceholder')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.content_shoulder ? 'border-red-500' : 'border-gray-300'" />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.brief') }} <span class="text-red-500">*</span>
                            </label>

                            <textarea v-model="saveForm.brief" rows="4" :placeholder="t('common.placeholders.enterBrief')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.brief ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.brief" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.brief }}
                            </p>
                        </div>

                        <div v-if="isStory" class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.body') }} <span class="text-red-500">*</span>
                            </label>

                            <Editor inputField="body" :form="saveForm" erroField="body" :isSimple="false"
                                :enableMediaUpload="true" :enableSelectFormMediaLibery="true" />

                            <p v-if="saveForm.errors.body" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.body }}
                            </p>
                        </div>

                        <div v-if="isVideo" class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.videoUrl') }} <span class="text-red-500">*</span>
                            </label>

                            <input v-model="saveForm.video_url" :placeholder="t('admin.news.create.form.videoUrlPlaceholder')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.video_url ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.video_url" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.video_url }}
                            </p>
                        </div>

                        <div v-if="isImageGallery" class="md:col-span-2">
                            <div v-if="isUpdate" class="border border-gray-200 rounded-lg p-4 space-y-3">
                                <NewsImageGalleryGrid :news="news" />
                            </div>

                            <div v-else class="border border-gray-200 rounded-lg p-4 space-y-4">
                                <label class="block text-sm font-medium mb-1">
                                    {{ t('common.labels.galleryImages') }} <span class="text-red-500">*</span>
                                </label>

                                <NewsImageGalleryDraftGrid :form="saveForm" fieldName="gallery_image_ids" />

                                <p v-if="saveForm.errors.gallery_image_ids" class="text-red-500 text-sm mt-1">
                                    {{ saveForm.errors.gallery_image_ids }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.tags') }}
                            </label>

                            <SelectInfinityLoadingApi :form="saveForm" fieldName="tag_ids"
                                :selectedItem="saveForm.tag_ids ? news?.tags : null" :apiUrl="tagApiUrl"
                                :error="saveForm.errors.tag_ids" :multiple="true"
                                :placeholder="t('admin.news.create.form.tagsPlaceholder')" />
                        </div>
                    </div>
                </div>

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">{{ t('admin.news.create.form.imageSettings') }}</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-medium">
                                    {{ t('common.labels.featureImage') }} <span class="text-red-500">*</span>
                                </label>

                                <MediaSelectFromMediaLibery :galleryTitle="t('common.labels.featureImage')"
                                    :fetchUrl="route('search.medias')" mediaType="image" :multiple="false"
                                    @media-selected="handleSelectedFeatureImage" />
                            </div>

                            <input type="file" @change="e => {
                                saveForm.upload_feature_image = e.target.files[0] || null
                                if (saveForm.upload_feature_image) saveForm.selected_feature_image_url = null
                            }" class="border rounded px-3 py-2 w-full"
                                :class="saveForm.errors.upload_feature_image ? 'border-red-500' : 'border-gray-300'" />

                            <input v-model="saveForm.feature_image_caption"
                                class="w-full border rounded-md px-3 py-2 mt-1 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.feature_image_caption ? 'border-red-500' : 'border-gray-300'"
                                :placeholder="t('admin.news.create.form.captionPlaceholder')" />

                            <p v-if="saveForm.errors.upload_feature_image" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.upload_feature_image }}
                            </p>

                            <p v-if="saveForm.errors.feature_image_caption" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.feature_image_caption }}
                            </p>

                            <img :src="saveForm.selected_feature_image_url || news?.feature_image?.preview_url || '/uploads/images/news/story-feature-image.png'"
                                class="w-75 object-contain rounded-xl border border-gray-200 mt-2" />
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-medium">
                                    {{ t('common.labels.mobileFeatureImage') }}
                                </label>

                                <MediaSelectFromMediaLibery :galleryTitle="t('common.labels.mobileFeatureImage')"
                                    :fetchUrl="route('search.medias')" mediaType="image" :multiple="false"
                                    @media-selected="handleSelectedThumbnail" />
                            </div>

                            <input type="file" @change="e => {
                                saveForm.upload_feature_image_mobile = e.target.files[0] || null
                                if (saveForm.upload_feature_image_mobile) saveForm.selected_feature_image_mobile_url = null
                            }" class="border rounded px-3 py-2 w-full"
                                :class="saveForm.errors.upload_feature_image_mobile ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.upload_feature_image_mobile" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.upload_feature_image_mobile }}
                            </p>

                            <img :src="saveForm.selected_feature_image_mobile_url || news?.feature_image_mobile?.preview_url || '/uploads/images/news/story-feature-image.png'"
                                class="w-75 object-contain rounded-xl border border-gray-200 mt-2" />
                        </div>
                    </div>
                </div>

                <div v-if="isStory" class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">{{ t('admin.news.create.form.contributorSettings') }}</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.contributors') }}
                            </label>

                            <SelectInfinityLoadingApi :form="saveForm" fieldName="contributor_ids"
                                :selectedItem="saveForm.contributor_ids ? news?.contributors : null"
                                :apiUrl="contributorApiUrl" :error="saveForm.errors.contributor_ids" :multiple="true"
                                :placeholder="t('admin.news.create.form.contributorsPlaceholder')" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.writer') }}
                            </label>

                            <input v-model="saveForm.writer" :placeholder="t('admin.news.create.form.writerPlaceholder')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.writer ? 'border-red-500' : 'border-gray-300'" />
                        </div>
                    </div>
                </div>

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">{{ t('admin.news.create.form.extraSettings') }}</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.relevantNews') }}
                            </label>

                            <SelectInfinityLoadingApi :form="saveForm" fieldName="relevant_news_ids"
                                :selectedItem="news?.relevant_news || null" :apiUrl="relevantOrRelatedNewsApiUrl"
                                :error="saveForm.errors.relevant_news_ids" selectedLabelKey="title_with_published_at"
                                selectedValueKey="id" apiLabelKey="title_with_published_at" apiValueKey="id"
                                :multiple="true" :placeholder="t('admin.news.create.form.relevantNewsPlaceholder')" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.relatedNews') }}
                            </label>

                            <SelectInfinityLoadingApi :form="saveForm" fieldName="related_news_ids"
                                :selectedItem="news?.related_news || null" :apiUrl="relevantOrRelatedNewsApiUrl"
                                :error="saveForm.errors.related_news_ids" selectedLabelKey="title_with_published_at"
                                selectedValueKey="id" apiLabelKey="title_with_published_at" apiValueKey="id"
                                :multiple="true" :placeholder="t('admin.news.create.form.relatedNewsPlaceholder')" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.messages.breakingNews') }}
                            </label>

                            <SelectInfinityLoadingApi :form="saveForm" fieldName="breaking_news_id"
                                :selectedItem="news?.breaking_news || null" :apiUrl="breakingNewsApiUrl"
                                :error="saveForm.errors.breaking_news_id" selectedLabelKey="title" selectedValueKey="id"
                                apiLabelKey="title" apiValueKey="id" :multiple="false"
                                :placeholder="t('admin.news.create.form.breakingNewsPlaceholder')" />
                        </div>
                    </div>
                </div>

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">{{ t('common.labels.publishSettings') }}</h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div v-if="isStory">
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.source') }}
                            </label>

                            <input v-model="saveForm.source" :placeholder="t('admin.news.create.form.sourcePlaceholder')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.source ? 'border-red-500' : 'border-gray-300'" />
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                {{ t('common.labels.published') }}
                            </label>

                            <label class="inline-flex cursor-pointer items-center gap-3">
                                <input v-model="saveForm.is_published" type="checkbox" class="peer sr-only" :checked="saveForm.is_published"/>

                                <span class="relative h-7 w-14 rounded-full bg-gray-300 transition
                                    after:absolute after:left-1 after:top-1 after:h-5 after:w-5
                                    after:rounded-full after:bg-white after:transition-all after:content-['']
                                    peer-checked:bg-green-600 peer-checked:after:translate-x-7">
                                </span>

                                <span class="text-sm text-gray-600">
                                    {{ saveForm.is_published ? t('common.boolean.yes') : t('common.boolean.no') }}
                                </span>
                            </label>
                        </div>

                        <div v-if="isUpdate">
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                {{ t('common.labels.clear_cache') }}
                            </label>

                            <label class="inline-flex cursor-pointer items-center gap-3">
                                <input v-model="saveForm.clear_cache" type="checkbox" class="peer sr-only" :checked="saveForm.clear_cache"/>

                                <span class="relative h-7 w-14 rounded-full bg-gray-300 transition
                                    after:absolute after:left-1 after:top-1 after:h-5 after:w-5
                                    after:rounded-full after:bg-white after:transition-all after:content-['']
                                    peer-checked:bg-green-600 peer-checked:after:translate-x-7">
                                </span>

                                <span class="text-sm text-gray-600">
                                    {{ saveForm.clear_cache ? t('common.boolean.yes') : t('common.boolean.no') }}
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">{{ t('common.labels.seoSettings') }}</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.seoTitle') }}
                            </label>

                            <input v-model="saveForm.seo_title" type="text"
                                :placeholder="t('common.placeholders.enterSeoTitle')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.seo_title ? 'border-red-500' : 'border-gray-300'" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.seoBrief') }}
                            </label>

                            <textarea v-model="saveForm.seo_brief" rows="3"
                                :placeholder="t('common.placeholders.enterSeoBrief')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.seo_brief ? 'border-red-500' : 'border-gray-300'" />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.seoKeywords') }}
                            </label>

                            <SelectTaggable :key="seoKeywordsKey" :selectedItem="saveForm.seo_keywords"
                                fieldName="seo_keywords" :form="saveForm" :error="saveForm.errors.seo_keywords"
                                :placeholder="t('common.placeholders.addKeywords')" />
                        </div>
                    </div>
                </div>

                <div class="flex justify-center">
                    <button type="submit" :disabled="saveForm.processing"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md flex items-center gap-2 transition disabled:opacity-60">
                        <FontAwesomeIcon v-if="saveForm.processing" icon="spinner" spin />
                        <FontAwesomeIcon v-else icon="save" />
                        {{ saveForm.processing ? t('common.actions.saving') : t('common.actions.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
