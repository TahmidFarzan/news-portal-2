<script setup>

import CategoryLocationEvent from '@/components/common/news/CategoryLocationEvent.vue'
import TagTrend from '@/components/common/news/TagTrend.vue'
import ImageWithLightBox from '@/components/common/media/ImageWithLightBox.vue'
import SocialShare from '@/components/common/news/SocialShare.vue'
import WriterConstributer from '@/components/common/news/WriterConstributer.vue'
import TitleSubtitleContentShoulder from '@/components/common/news/TitleSubtitleContentShoulder.vue'
import RelatedNewsGrid from '@/components/common/news/RelatedNewsGrid.vue'
import NewsBody from '@/components/common/news/NewsBody.vue'
import GoogleAdsence from '@/components/common/util/GoogleAdsence.vue'

import { computed,inject } from 'vue'
import { formatDateTime } from '@/composables/useDateTime'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faClock,
} from '@fortawesome/free-solid-svg-icons'

import { useTranslate } from '@/composables/useTranslate'
import { adTypes, adPositions } from '@/composables/useGoogleAdsence'

FontAwesomeLibrary.add(
    faClock
)

const { t } = useTranslate()

const { news } = defineProps({
    news: {
        type: Object,
        required: true,
    },
})
const showGoogleAd = inject('showGoogleAd', computed(() => false))

</script>

<template>
    <article class="news-detail-article mx-auto w-full max-w-5xl space-y-6 px-4 py-6">
        <header class="article-header space-y-3">
            <CategoryLocationEvent :news="news" />

            <TitleSubtitleContentShoulder :news="news"/>

            <WriterConstributer :news="news" />

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

        <figure v-if="news?.feature_image" class="feature-figure mx-auto max-w-3xl space-y-2">
            <img :src="news?.feature_image?.preview_url || news?.feature_image?.original_url || ''"
                :alt="news?.feature_image?.custom_properties?.alt || news?.title"
                class="h-auto w-full rounded-2xl object-contain" />

            <figcaption v-if="news?.feature_image?.custom_properties?.caption" class="text-sm text-gray-500">
                {{ news?.feature_image.custom_properties.caption }}
            </figcaption>
        </figure>

        <NewsBody v-if="news?.body" class="prose prose-lg max-w-none" :news="news"/>

        <GoogleAdsence v-if="showGoogleAd" class="mt-4 mb-4" :type="adTypes.SECTION" :position="adPositions.BETWEEN"/>

        <TagTrend :news="news" />

        <div v-if="news?.source" class="pt-4 text-sm text-gray-500">
            <span>{{ t("common.labels.source") }}: </span>
            {{ news.source }}
        </div>

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

.article-header {
    border-bottom: var(--news-border-default);
    padding-bottom: 1rem;
}

.feature-figure img {
    border: var(--news-border-default);
    box-shadow: var(--news-shadow-soft);
}
</style>
