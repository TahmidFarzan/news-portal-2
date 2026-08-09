<script setup>

import NewsContextLinks from '@/components/common/news/NewsContextLinks.vue'
import TagTrend from '@/components/common/news/TagTrend.vue'
import SocialShare from '@/components/common/news/SocialShare.vue'
import NewsHeadline from '@/components/common/news/NewsHeadline.vue'
import RelatedNewsGrid from '@/components/common/news/RelatedNewsGrid.vue'

import { computed,inject } from 'vue'
import { formatDateTime } from '@/composables/useDateTime'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faClock,
} from '@fortawesome/free-solid-svg-icons'

import GoogleAdsense from '@/components/common/advertising/GoogleAdsense.vue'

import { adTypes, adPositions } from '@/composables/useGoogleAdsense'

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
const showGoogleAd = inject('showGoogleAd', computed(() => false))
</script>

<template>
    <article class="news-detail-article mx-auto w-full max-w-5xl space-y-6 px-4 py-6">
        <section v-if="videoUrl" class="video-frame overflow-hidden rounded-2xl bg-black">
            <iframe v-if="isEmbedVideo" :src="videoUrl" class="aspect-video w-full"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                allowfullscreen />

            <video v-else :src="videoUrl" controls class="aspect-video w-full" />
        </section>

        <header class="article-header space-y-3">
            <NewsContextLinks :news="news" />

            <NewsHeadline :news="news" />

            <p v-if="news?.brief" class="max-w-3xl text-lg leading-8 text-gray-700">
                {{ news.brief }}
            </p>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-3 text-sm text-gray-500 mb-3">
                <span v-if="news?.published_at" class="inline-flex items-center gap-1.5">
                    <FontAwesomeIcon icon="clock" class="text-xs text-gray-400" />
                    {{ news?.published_at }}
                </span>

                <span v-if="news?.published_at"
                    class="hidden h-4 w-px bg-gray-300 sm:inline-block" />

                <SocialShare :news="news" />
            </div>
        </header>


        <GoogleAdsense v-if="showGoogleAd" class="mt-4 mb-4" :type="adTypes.SECTION" :position="adPositions.BETWEEN"/>

        <TagTrend :news="news" />

        <div class="border-t pt-4 text-sm text-gray-500">
            <RelatedNewsGrid :news="news"/>
        </div>
    </article>
</template>

<style scoped>
.news-detail-article {
    border: var(--news-border-default);
    border-radius: var(--news-radius);
    background: var(--news-surface);
    box-shadow: var(--news-shadow-soft);
}

.video-frame {
    box-shadow: var(--news-shadow-video);
}

.article-header {
    border-bottom: var(--news-border-default);
    padding-bottom: 1rem;
}
</style>
