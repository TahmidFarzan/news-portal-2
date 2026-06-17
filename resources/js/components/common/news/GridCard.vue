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

    hideSubtitle = false,
    hideBrief = false,

    hideCategory = false,
    hideEvent = false,
    hideLocation = false,
    hideFeatureImage = false,

    isCompact = false,
    useFullHeight = true,
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

    hideBrief: {
        type: Boolean,
        default: false,
    },

    hideSubtitle: {
        type: Boolean,
        default: false,
    },

    hideFeatureImage: {
        type: Boolean,
        default: false,
    },

    hideCategory: {
        type: Boolean,
        default: false,
    },

    hideEvent: {
        type: Boolean,
        default: false,
    },

    hideLocation: {
        type: Boolean,
        default: false,
    },

    isCompact: {
        type: Boolean,
        default: false,
    },

    useFullHeight: {
        type: Boolean,
        default: true,
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
        ''
    )
})

const shouldShowFeatureImage = computed(() => {
    return !hideFeatureImage && imageSrc.value
})

const newsType = computed(() => {
    return news?.newsType || news?.news_type || news?.newType || null
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
        class="group relative flex flex-col overflow-hidden border border-gray-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:border-red-100 hover:shadow-lg"
        :class="[
            isCompact ? 'rounded-xl' : 'rounded-2xl',
            useFullHeight ? 'h-full' : '',
        ]">
        <a v-if="news?.public_url" :href="news.public_url" :aria-label="news?.title || 'Read news'"
            class="absolute inset-0 z-10" :class="isCompact ? 'rounded-xl' : 'rounded-2xl'"></a>

        <div v-if="shouldShowFeatureImage"
            class="pointer-events-none relative z-20 w-full shrink-0 overflow-hidden bg-gray-100"
            :class="isCompact ? 'aspect-[16/9] rounded-t-xl' : 'aspect-[16/10] rounded-t-2xl'">
            <img :src="imageSrc" :alt="imageAlt"
                class="w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy" :class="[
                    useFullHeight ? 'h-full' : '',
                ]" />

            <div v-if="hasNewsTypeIcons" class="absolute flex flex-wrap"
                :class="isCompact ? 'left-2 top-2 gap-1' : 'left-3 top-3 gap-2'">
                <span v-for="item in newsTypeIcons" :key="item.key" :title="item.title" :aria-label="item.title"
                    class="inline-flex items-center justify-center rounded-full bg-black/70 text-white shadow-sm backdrop-blur-sm"
                    :class="isCompact ? 'h-6 w-6' : 'h-8 w-8'">
                    <FontAwesomeIcon :icon="item.icon" :class="isCompact ? 'text-xs' : 'text-sm'" />
                </span>
            </div>
        </div>

        <div class="pointer-events-none relative z-20 flex flex-1 flex-col"
            :class="isCompact ? 'space-y-1 p-2' : 'space-y-2 p-4'">
            <div v-if="!shouldShowFeatureImage && hasNewsTypeIcons" class="flex flex-wrap"
                :class="isCompact ? 'gap-1' : 'gap-2'">
                <span v-for="item in newsTypeIcons" :key="item.key" :title="item.title" :aria-label="item.title"
                    class="inline-flex items-center justify-center rounded-full bg-red-50 text-red-600"
                    :class="isCompact ? 'h-6 w-6' : 'h-8 w-8'">
                    <FontAwesomeIcon :icon="item.icon" :class="isCompact ? 'text-xs' : 'text-sm'" />
                </span>
            </div>

            <div v-if="(news?.category?.name && !hideCategory) || (news?.event?.name && !hideEvent) || (news?.location?.name && !hideLocation)"
                class="pointer-events-auto flex flex-wrap items-center font-semibold uppercase tracking-wide text-gray-500"
                :class="isCompact ? 'gap-x-1.5 gap-y-0.5 text-[10px]' : 'gap-x-2 gap-y-1 text-[11px]'">
                <a v-if="news?.category && !hideCategory" :href="news.category.public_url" title="Category"
                    class="inline-flex min-w-0 items-center gap-1 transition duration-300 hover:text-red-600"
                    @click.stop>
                    <FontAwesomeIcon icon="folder" class="shrink-0" />
                    <span class="truncate">{{ news.category.name }}</span>
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

            <p v-if="news?.sub_title && !hideSubtitle" class="break-words text-gray-600" :class="[
                isCompact ? 'text-xs leading-5' : 'text-sm leading-6',
                { 'line-clamp-2': enableSubTitleLineClamp },
            ]">
                {{ news?.sub_title }}
            </p>

            <h3 class="break-words font-bold leading-snug text-gray-950 transition duration-300 group-hover:text-red-600"
                :class="[
                    isCompact ? 'text-sm' : 'text-base',
                    { 'line-clamp-2': enableTitleLineClamp },
                ]">
                <b v-if="news?.content_shoulder" class="mr-1 font-semibold text-red-600"
                    :class="isCompact ? 'text-xs' : 'text-sm'">
                    {{ news?.content_shoulder }}
                </b>

                {{ news?.title }}
            </h3>

            <p v-if="news?.brief && !hideBrief" class="break-words text-gray-600" :class="[
                isCompact ? 'text-xs leading-5' : 'text-sm',
                { 'line-clamp-2': enableBriefLineClamp },
            ]">
                {{ news?.brief }}
            </p>

            <div class="flex flex-wrap items-center text-gray-500"
                :class="isCompact ? 'gap-x-2 gap-y-1 text-xs' : 'mb-3 gap-x-4 gap-y-3 text-sm'">
                <span class="inline-flex items-center" :class="isCompact ? 'gap-1' : 'gap-1.5'">
                    <FontAwesomeIcon icon="clock" class="text-gray-400"
                        :class="isCompact ? 'text-[10px]' : 'text-xs'" />

                    {{ news?.published_at || formatDateTime(news?.created_at) }}
                </span>
            </div>
        </div>
    </article>
</template>
