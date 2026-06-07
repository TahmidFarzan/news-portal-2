<script setup>
import { computed } from 'vue'

import {
    isStory as checkIsStory,
    isVideo as checkIsVideo,
    isImageGallery as checkIsImageGallery,
} from '@/composables/useNews'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'

import {
    faBookOpen,
    faPlay,
    faImages,
} from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faBookOpen, faPlay, faImages)

const props = defineProps({
    news: {
        type: Object,
        required: true,
    },

    enableTitleLineClamp: {
        type: Boolean,
        default: true,
    },

    enableSubTitleLineClamp: {
        type: Boolean,
        default: true,
    },

    hideFeatureImage: {
        type: Boolean,
        default: false,
    },

    hideCategory: {
        type: Boolean,
        default: false,
    },

    hideSubtitle: {
        type: Boolean,
        default: false,
    },
})

const imageSrc = computed(() => {
    return (
        props.news?.feature_image_mobile?.media_url ||
        props.news?.feature_image_mobile?.original_url ||
        props.news?.feature_image?.media_url ||
        props.news?.feature_image?.original_url ||
        ''
    )
})

const imageAlt = computed(() => {
    return (
        props.news?.feature_image_mobile?.custom_properties?.alt ||
        props.news?.feature_image?.custom_properties?.alt ||
        props.news?.title ||
        'News image'
    )
})

const cardTag = computed(() => {
    return props.news?.public_url ? 'a' : 'article'
})

const shouldShowFeatureImage = computed(() => {
    return !props.hideFeatureImage && imageSrc.value
})

const newsType = computed(() => {
    return (
        props.news?.newsType ||
        props.news?.news_type ||
        props.news?.newType ||
        null
    )
})

const newsTypeIcons = computed(() => {
    const icons = []

    if (checkIsStory(newsType.value)) {
        icons.push({
            key: 'story',
            title: 'Story',
            icon: faBookOpen,
        })
    }

    if (checkIsVideo(newsType.value)) {
        icons.push({
            key: 'video',
            title: 'Video',
            icon: faPlay,
        })
    }

    if (checkIsImageGallery(newsType.value)) {
        icons.push({
            key: 'gallery',
            title: 'Image Gallery',
            icon: faImages,
        })
    }

    return icons
})

const hasNewsTypeIcons = computed(() => {
    return newsTypeIcons.value.length > 0
})
</script>

<template>
    <component :is="cardTag" :href="props.news?.public_url || undefined"
        class="group flex h-full gap-3 rounded-2xl border border-gray-200 bg-white p-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md">
        <div v-if="shouldShowFeatureImage"
            class="relative h-24 w-32 shrink-0 overflow-hidden rounded-xl bg-gray-100 sm:h-28 sm:w-40">
            <img :src="imageSrc" :alt="imageAlt"
                class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy" />

            <div v-if="hasNewsTypeIcons" class="absolute left-2 top-2 flex flex-wrap gap-1.5">
                <span v-for="item in newsTypeIcons" :key="item.key" :title="item.title" :aria-label="item.title"
                    class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-black/70 text-white shadow-sm backdrop-blur-sm">
                    <FontAwesomeIcon :icon="item.icon" class="text-xs" />
                </span>
            </div>
        </div>

        <div class="min-w-0 flex flex-1 flex-col justify-center space-y-2">
            <div v-if="!shouldShowFeatureImage && hasNewsTypeIcons" class="flex flex-wrap gap-1.5">
                <span v-for="item in newsTypeIcons" :key="item.key" :title="item.title" :aria-label="item.title"
                    class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-red-50 text-red-600">
                    <FontAwesomeIcon :icon="item.icon" class="text-xs" />
                </span>
            </div>

            <p v-if="props.news?.category?.name && !props.hideCategory"
                class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                {{ props.news?.category?.name }}
            </p>

            <h3 class="break-words text-base font-bold leading-snug text-gray-950 group-hover:text-red-600"
                :class="{ 'line-clamp-2': props.enableTitleLineClamp }">
                <b v-if="props.news?.content_shoulder" class="mr-1 text-sm font-semibold text-red-600">
                    {{ props.news?.content_shoulder }}
                </b>

                {{ props.news?.title }}
            </h3>

            <p v-if="props.news?.sub_title && !props.hideSubtitle" class="break-words text-sm leading-6 text-gray-600"
                :class="{ 'line-clamp-2': props.enableSubTitleLineClamp }">
                {{ props.news?.sub_title }}
            </p>
        </div>
    </component>
</template>
