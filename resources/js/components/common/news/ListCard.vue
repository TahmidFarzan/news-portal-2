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
    faFolder,
    faBookOpen,
    faPlay,
    faClock,
    faImages,
    faLocationDot,
    faCalendarDays,
} from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'

FontAwesomeLibrary.add(
    faBookOpen,
    faPlay,
    faClock,
    faImages,
    faFolder,
    faLocationDot,
    faCalendarDays,
)

const {
    news,
    enableTitleLineClamp = false,
    enableSubTitleLineClamp = false,
    enableBriefLineClamp = false,

    hideNewsType = false,
    hideSubtitle = false,
    hideBrief = false,
    hideCategory = false,
    hideEvent = false,
    hideLocation = false,

    hideFeatureImage = false,
} = defineProps({
    news: {
        type: Object,
        required: true,
    },

    enableTitleLineClamp: {
        type: Boolean,
        default: false,
    },

    enableSubTitleLineClamp: {
        type: Boolean,
        default: false,
    },

    enableBriefLineClamp: {
        type: Boolean,
        default: false,
    },

    hideNewsType: {
        type: Boolean,
        default: false,
    },

    hideSubtitle: {
        type: Boolean,
        default: false,
    },

    hideBrief: {
        type: Boolean,
        default: false,
    },

    hideCategory: {
        type: Boolean,
        default: false,
    },

    hideLocation: {
        type: Boolean,
        default: false,
    },

    hideEvent: {
        type: Boolean,
        default: false,
    },

    hideFeatureImage: {
        type: Boolean,
        default: false,
    },
})

const imageSrc = computed(() => {
    return (
        news?.feature_image_mobile?.media_url ||
        news?.feature_image_mobile?.original_url ||
        news?.feature_image?.media_url ||
        news?.feature_image?.original_url ||
        ''
    )
})

const imageAlt = computed(() => {
    return (
        news?.feature_image_mobile?.custom_properties?.alt ||
        news?.feature_image?.custom_properties?.alt ||
        news?.title ||
        'News image'
    )
})

const shouldShowFeatureImage = computed(() => {
    return !hideFeatureImage && imageSrc.value
})

const newsType = computed(() => {
    return (
        news?.newsType ||
        news?.news_type ||
        news?.newType ||
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
    <article
        class="group relative flex h-full flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md sm:flex-row">
        <a v-if="news?.public_url" :href="news?.public_url" :aria-label="news?.title || 'Read news'"
            class="absolute inset-0 z-10 rounded-2xl"></a>

        <div v-if="shouldShowFeatureImage"
            class="pointer-events-none relative z-20 aspect-[16/10] w-full shrink-0 overflow-hidden rounded-xl bg-gray-100 sm:w-48 md:h-32 md:aspect-auto">
            <img :src="imageSrc" :alt="imageAlt"
                class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy" />

            <div v-if="!hideNewsType && hasNewsTypeIcons" class="absolute left-2 top-2 flex flex-wrap gap-1.5">
                <span v-for="item in newsTypeIcons" :key="item.key" :title="item.title" :aria-label="item.title"
                    class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-black/70 text-white shadow-sm backdrop-blur-sm">
                    <FontAwesomeIcon :icon="item.icon" class="text-xs" />
                </span>
            </div>
        </div>

        <div class="pointer-events-none relative z-20 min-w-0 flex flex-1 flex-col justify-center space-y-2">

            <div v-if="(news?.category && !hideCategory) || (news?.event && !hideEvent) || (news?.location && !hideLocation)"
                class="pointer-events-auto flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                <a v-if="news?.category && !hideCategory" :href="news.category.public_url" title="Category"
                    class="inline-flex min-w-0 items-center gap-1 transition duration-300 hover:text-red-600"
                    @click.stop>
                    <FontAwesomeIcon icon="folder" class="shrink-0" />
                    <span class="truncate">{{ news?.category?.name }}</span>
                </a>


                <span v-if="news?.event && !hideEvent && news?.category && !hideCategory" class="text-gray-300"
                    aria-hidden="true">
                    |
                </span>

                <a v-if="news?.event && !hideEvent" :href="news?.event?.public_url" title="Event"
                    class="inline-flex min-w-0 items-center gap-1 transition duration-300 hover:text-red-600"
                    @click.stop>
                    <FontAwesomeIcon icon="calendar-days" class="shrink-0" />
                    <span class="truncate">{{ news?.event?.name }}</span>
                </a>

                <span
                    v-if="news?.location && !hideLocation && ((news?.category && !hideCategory) || (news?.event && !hideEvent))"
                    class="text-gray-300" aria-hidden="true">
                    |
                </span>

                <a v-if="news?.location && !hideLocation" :href="news.location.public_url" title="Location"
                    class="inline-flex min-w-0 items-center gap-1 transition duration-300 hover:text-red-600"
                    @click.stop>
                    <FontAwesomeIcon icon="location-dot" class="shrink-0" />
                    <span class="truncate">{{ news?.location?.name }}</span>
                </a>

            </div>

            <p v-if="news?.sub_title && !hideSubtitle" class="break-words text-sm leading-6 text-gray-600"
                :class="{ 'line-clamp-2': enableSubTitleLineClamp }">
                {{ news?.sub_title }}
            </p>

            <h3 class="break-words text-sm font-bold leading-snug text-gray-950 transition duration-300 group-hover:text-red-600 sm:text-base"
                :class="{ 'line-clamp-2': enableTitleLineClamp }">
                <b v-if="news?.content_shoulder" class="mr-1 text-sm font-semibold text-red-600">
                    {{ news?.content_shoulder }}
                </b>

                {{ news?.title }}
            </h3>

            <p v-if="news?.brief && !hideBrief" class="break-words text-sm text-gray-600"
                :class="{ 'line-clamp-2': enableBriefLineClamp }">
                {{ news?.brief }}
            </p>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-3 text-sm text-gray-500 mb-3">
                <div v-if="!shouldShowFeatureImage && !hideNewsType && hasNewsTypeIcons" class="inline-flex items-center gap-1.5">
                    <span v-for="item in newsTypeIcons" :key="item.key" :title="item.title" :aria-label="item.title"
                        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-red-50 text-red-600">
                        <FontAwesomeIcon :icon="item.icon" class="text-xs" />
                    </span>
                </div>
                <span class="inline-flex items-center gap-1.5">
                    <FontAwesomeIcon icon="clock" class="text-xs text-gray-400" />
                    {{ news?.published_at || formatDateTime(news?.created_at) }}
                </span>
            </div>
        </div>
    </article>
</template>
