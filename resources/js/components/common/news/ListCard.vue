<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

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
    isCompact: {
        type: Boolean,
        default: false,
    },
    useFullHeight: {
        type: Boolean,
        default: true,
    },
})

const windowWidth = ref(0)

const updateWindowWidth = () => {
    windowWidth.value = window.innerWidth
}

const resolvedHideFeatureImage = computed(() => {
    return hideFeatureImage || windowWidth.value < 300
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
        news?.title
    )
})

const shouldShowFeatureImage = computed(() => {
    return !resolvedHideFeatureImage.value && imageSrc.value
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

onMounted(() => {
    updateWindowWidth()

    window.addEventListener('resize', updateWindowWidth, { passive: true })
})

onBeforeUnmount(() => {
    window.removeEventListener('resize', updateWindowWidth)
})
</script>

<template>
    <article
        class="group relative flex flex-row overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-red-100 hover:shadow-lg"
        :class="[
            isCompact ? 'gap-1 p-1' : 'gap-2 p-2 sm:gap-3 sm:p-3',
            useFullHeight ? 'h-full' : '',
        ]">
        <a v-if="news?.public_url" :href="news?.public_url" :aria-label="news?.title"
            class="absolute inset-0 z-10 rounded-2xl"></a>

        <div v-if="shouldShowFeatureImage"
            class="pointer-events-none relative z-20 shrink-0 overflow-hidden bg-gray-100"
            :class="isCompact
                ? 'h-16 w-20 rounded-lg min-[330px]:h-20 min-[330px]:w-28 min-[640px]:rounded-xl'
                : 'h-20 w-24 rounded-lg min-[330px]:h-28 min-[330px]:w-36 min-[640px]:h-32 min-[640px]:w-48 min-[640px]:rounded-xl'">
            <img :src="imageSrc" :alt="imageAlt"
                class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy" />

            <div v-if="!hideNewsType && hasNewsTypeIcons" class="absolute flex flex-wrap"
                :class="isCompact ? 'left-1 top-1 gap-1' : 'left-1 top-1 gap-1 sm:left-2 sm:top-2 sm:gap-1.5'">
                <span v-for="item in newsTypeIcons" :key="item.key" :title="item.title" :aria-label="item.title"
                    class="inline-flex items-center justify-center rounded-full bg-black/70 text-white shadow-sm backdrop-blur-sm"
                    :class="isCompact ? 'h-5 w-5' : 'h-5 w-5 sm:h-7 sm:w-7'">
                    <FontAwesomeIcon :icon="item.icon" :class="isCompact ? 'text-[10px]' : 'text-[10px] sm:text-xs'" />
                </span>
            </div>
        </div>

        <div class="pointer-events-none relative z-20 min-w-0 flex flex-1 flex-col justify-center"
            :class="isCompact ? 'space-y-1 px-1 py-0.5' : 'space-y-1 sm:space-y-2'">
            <div v-if="(news?.category && !hideCategory) || (news?.event && !hideEvent) || (news?.location && !hideLocation)"
                class="pointer-events-auto flex flex-wrap items-center font-semibold uppercase tracking-wide text-gray-500"
                :class="isCompact ? 'gap-x-1 gap-y-0.5 text-[9px] min-[330px]:gap-x-1.5 min-[330px]:text-[10px]' : 'gap-x-1 gap-y-0.5 text-[9px] min-[330px]:gap-x-1.5 min-[330px]:text-[10px] sm:gap-x-2 sm:gap-y-1 sm:text-[11px]'">
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

            <p v-if="news?.sub_title && !hideSubtitle" class="break-words text-gray-600" :class="[
                isCompact ? 'text-[11px] leading-4 min-[330px]:text-xs min-[330px]:leading-5' : 'text-[11px] leading-4 min-[330px]:text-xs min-[330px]:leading-5 sm:text-sm sm:leading-6',
                { 'line-clamp-2': enableSubTitleLineClamp },
            ]">
                {{ news?.sub_title }}
            </p>

            <h3 class="break-words font-bold leading-snug text-gray-950 transition duration-300 group-hover:text-red-600"
                :class="[
                    isCompact ? 'text-xs min-[330px]:text-sm' : 'text-xs min-[330px]:text-sm sm:text-base',
                    { 'line-clamp-2': enableTitleLineClamp },
                ]">
                <b v-if="news?.content_shoulder" class="mr-1 font-semibold text-red-600"
                    :class="isCompact ? 'text-[11px] min-[330px]:text-xs' : 'text-[11px] min-[330px]:text-xs sm:text-sm'">
                    {{ news?.content_shoulder }}
                </b>

                {{ news?.title }}
            </h3>

            <p v-if="news?.brief && !hideBrief" class="break-words text-gray-600" :class="[
                isCompact ? 'text-[11px] leading-4 min-[330px]:text-xs min-[330px]:leading-5' : 'text-[11px] leading-4 min-[330px]:text-xs min-[330px]:leading-5 sm:text-sm',
                { 'line-clamp-2': enableBriefLineClamp },
            ]">
                {{ news?.brief }}
            </p>

            <div class="flex flex-wrap items-center text-gray-500"
                :class="isCompact ? 'gap-x-1 gap-y-1 text-[10px] min-[330px]:gap-x-2 min-[330px]:text-xs' : 'gap-x-1 gap-y-1 text-[10px] min-[330px]:gap-x-2 min-[330px]:text-xs sm:mb-3 sm:gap-x-4 sm:gap-y-3 sm:text-sm'">
                <div v-if="!shouldShowFeatureImage && !hideNewsType && hasNewsTypeIcons"
                    class="inline-flex items-center" :class="isCompact ? 'gap-1' : 'gap-1 sm:gap-1.5'">
                    <span v-for="item in newsTypeIcons" :key="item.key" :title="item.title" :aria-label="item.title"
                        class="inline-flex items-center justify-center rounded-full bg-red-50 text-red-600"
                        :class="isCompact ? 'h-5 w-5' : 'h-5 w-5 sm:h-7 sm:w-7'">
                        <FontAwesomeIcon :icon="item.icon"
                            :class="isCompact ? 'text-[10px]' : 'text-[10px] sm:text-xs'" />
                    </span>
                </div>

                <span class="inline-flex min-w-0 items-center" :class="isCompact ? 'gap-1' : 'gap-1 sm:gap-1.5'">
                    <FontAwesomeIcon icon="clock" class="shrink-0 text-gray-400"
                        :class="isCompact ? 'text-[10px]' : 'text-[10px] sm:text-xs'" />
                    <span class="truncate">
                        {{ news?.published_at || formatDateTime(news?.created_at) }}
                    </span>
                </span>
            </div>
        </div>
    </article>
</template>
