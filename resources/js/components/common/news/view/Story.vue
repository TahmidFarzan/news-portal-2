<script setup>

import NewsContextLinks from '@/components/common/news/NewsContextLinks.vue'
import TagTrend from '@/components/common/news/TagTrend.vue'
import ImageWithLightbox from '@/components/common/media/ImageWithLightbox.vue'
import SocialShare from '@/components/common/news/SocialShare.vue'
import NewsByline from '@/components/common/news/NewsByline.vue'
import NewsHeadline from '@/components/common/news/NewsHeadline.vue'
import RelatedNewsGrid from '@/components/common/news/RelatedNewsGrid.vue'
import NewsBody from '@/components/common/news/NewsBody.vue'
import GoogleAd from '@/components/common/advertising/GoogleAd.vue'

import { computed,inject } from 'vue'
import { formatDateTime } from '@/composables/useDateTime'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faClock,
} from '@fortawesome/free-solid-svg-icons'

import { useTranslate } from '@/composables/useTranslate'
import { adPages, adTypes, adPlacements } from '@/composables/useGoogleAd'

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
const googleAdEnable = inject('googleAdEnable', computed(() => false))

</script>

<template>
    <article class="news-detail-article mx-auto w-full max-w-5xl space-y-6 px-4 py-6">
        <header class="article-header space-y-3">
            <NewsContextLinks :news="news" />

            <NewsHeadline :news="news"/>

            <NewsByline :news="news" />

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

        <GoogleAd v-if="googleAdEnable" :page="adPages.NEWS_DETAILS" :type="adTypes.SECTION" :placement="adPlacements.ONE" />

        <TagTrend :news="news" />

        <div v-if="news?.source" class="pt-4 text-sm text-gray-500">
            <span>{{ t("common.labels.source") }}: </span>
            {{ news.source }}
        </div>

        <GoogleAd v-if="googleAdEnable" :page="adPages.NEWS_DETAILS" :type="adTypes.SECTION" :placement="adPlacements.TWO" />

        <div class="border-t pt-4 text-sm text-gray-500">
            <RelatedNewsGrid :news="news"/>
        </div>

        <GoogleAd v-if="googleAdEnable" :page="adPages.NEWS_DETAILS" :type="adTypes.SECTION" :placement="adPlacements.THREE" />
    </article>

    <Teleport to="body">
        <GoogleAd v-if="googleAdEnable" :page="adPages.NEWS_DETAILS" :type="adTypes.POPUP"/>
    </Teleport>
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
