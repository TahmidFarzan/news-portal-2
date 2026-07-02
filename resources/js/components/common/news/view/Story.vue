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

import { computed } from 'vue'
import { formatDateTime } from '@/composables/useDateTime'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faClock,
} from '@fortawesome/free-solid-svg-icons'

import { useTranslate } from '@/composables/useTranslate'

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

</script>

<template>
    <article class="mx-auto w-full max-w-5xl space-y-6 px-4 py-6">
        <header class="space-y-3">
            <CategoryLocationEvent :news="news" />

            <TitleSubtitleContentShoulder :news="news"/>

            <WriterConstributer :news="news" />

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

        <figure v-if="news?.feature_image" class="mx-auto max-w-3xl space-y-2">
            <img :src="news?.feature_image?.preview_url || news?.feature_image?.original_url || ''"
                :alt="news?.feature_image?.custom_properties?.alt || news?.title"
                class="h-auto w-full rounded-2xl object-contain" />

            <figcaption v-if="news?.feature_image?.custom_properties?.caption" class="text-sm text-gray-500">
                {{ news?.feature_image.custom_properties.caption }}
            </figcaption>
        </figure>

        <NewsBody v-if="news?.body" class="prose prose-lg max-w-none" :news="news"/>

        <GoogleAdsence v-if="showGoogleAd" />

        <TagTrend :news="news" />

        <div v-if="news?.source" class="pt-4 text-sm text-gray-500">
            <span>{{ t("components.common.news.view.story.labels.source") }}: </span>
            {{ news.source }}
        </div>

        <div class="border-t pt-4 text-sm text-gray-500">
            <RelatedNewsGrid :news="news"/>
        </div>
    </article>
</template>
