<script setup>

import CategoryLocationEvent from '@/components/common/news/CategoryLocationEvent.vue'
import TagTrend from '@/components/common/news/TagTrend.vue'
import SocialShare from '@/components/common/news/SocialShare.vue'
import TitleSubtitleContentShoulder from '@/components/common/news/TitleSubtitleContentShoulder.vue'
import RelatedNewsGrid from '@/components/common/news/RelatedNewsGrid.vue'

import { computed } from 'vue'
import { formatDateTime } from '@/composables/useDateTime'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faClock,
} from '@fortawesome/free-solid-svg-icons'

import GoogleAdsence from '@/components/common/util/GoogleAdsence.vue'

FontAwesomeLibrary.add(
    faClock
)

const { news } = defineProps({
    news: {
        type: Object,
        required: true,
    },
})

const videoUrl = computed(() => {
    const value = news?.video_url || ''

    if (!value) {
        return ''
    }

    try {
        const url = new URL(value)

        if (url.hostname.includes('youtube.com') && url.pathname === '/watch') {
            const id = url.searchParams.get('v')
            return id ? `https://www.youtube.com/embed/${id}` : value
        }

        if (url.hostname.includes('youtu.be')) {
            const id = url.pathname.replace('/', '')
            return id ? `https://www.youtube.com/embed/${id}` : value
        }

        if (url.hostname.includes('vimeo.com') && !url.hostname.includes('player.')) {
            const id = url.pathname.replace('/', '')
            return id ? `https://player.vimeo.com/video/${id}` : value
        }

        return value
    } catch {
        return value
    }
})

const isEmbedVideo = computed(() => {
    return /youtube\.com|youtu\.be|vimeo\.com/i.test(videoUrl.value)
})
</script>

<template>
    <article class="mx-auto w-full max-w-5xl space-y-6 px-4 py-6">
        <section v-if="videoUrl" class="overflow-hidden rounded-2xl bg-black">
            <iframe v-if="isEmbedVideo" :src="videoUrl" class="aspect-video w-full"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                allowfullscreen />

            <video v-else :src="videoUrl" controls class="aspect-video w-full" />
        </section>

        <header class="space-y-3">
            <CategoryLocationEvent :news="news" />

            <TitleSubtitleContentShoulder :news="news" />

            <p v-if="news?.brief" class="max-w-3xl text-lg leading-8 text-gray-700">
                {{ news.brief }}
            </p>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-3 text-sm text-gray-500 mb-3">
                <span class="inline-flex items-center gap-1.5">
                    <FontAwesomeIcon icon="clock" class="text-xs text-gray-400" />
                    {{ news?.published_at || formatDateTime(news?.created_at) }}
                </span>

                <span v-if="news?.published_at || news?.created_at"
                    class="hidden h-4 w-px bg-gray-300 sm:inline-block" />

                <SocialShare :news="news" />
            </div>
        </header>

        <GoogleAdsence v-if="showGoogleAd" />

        <TagTrend :news="news" />

        <div class="border-t pt-4 text-sm text-gray-500">
            <RelatedNewsGrid :news="news"/>
        </div>
    </article>
</template>
