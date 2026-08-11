<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch,inject } from 'vue'

import NewsContextLinks from '@/components/common/news/NewsContextLinks.vue'
import TagTrend from '@/components/common/news/TagTrend.vue'
import ImageWithLightbox from '@/components/common/media/ImageWithLightbox.vue'
import SocialShare from '@/components/common/news/SocialShare.vue'
import NewsHeadline from '@/components/common/news/NewsHeadline.vue'
import RelatedNewsGrid from '@/components/common/news/RelatedNewsGrid.vue'
import GoogleAd from '@/components/common/advertising/GoogleAd.vue'

import { formatDateTime } from '@/composables/useDateTime'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faClock,
} from '@fortawesome/free-solid-svg-icons'

import { useTranslate } from '@/composables/useTranslate'
import { adTypes, adPositions } from '@/composables/useGoogleAd'

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
    <article class="news-detail-article space-y-6">
        <header class="article-header space-y-3 border-b border-gray-200">
            <NewsContextLinks :news="news" />

            <NewsHeadline :news="news"/>

            <p v-if="news?.brief" class="max-w-3xl text-lg leading-8 text-gray-700">
                {{ news.brief }}
            </p>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-3 text-sm text-gray-500 mb-3">
                <span v-if="news?.published_at" class="inline-flex items-center gap-1.5">
                    <FontAwesomeIcon icon="clock" class="text-xs text-gray-400" />
                    {{ news?.published_at }}
                </span>

                <span v-if="news?.published_at" class="hidden h-4 w-px bg-gray-300 sm:inline-block" />

                <SocialShare :news="news"/>
            </div>
        </header>

        <section v-if="news?.gallery_images" class="gallery-strip flex flex-col gap-8">
            <ImageWithLightbox v-for="image in news?.gallery_images" :key="image?.id || image?.uuid" :image="image"  :is-image-gallery-item="true" :show-image-galery-counter="true"/>
        </section>

        <div v-else class="rounded-2xl border border-dashed border-gray-300 p-8 text-center text-gray-500">
            {{ t("news.components.view.imageGallery.labels.noGalleryImageFound") }}
        </div>

       <GoogleAd v-if="googleAdEnable" class="mt-4 mb-4" :type="adTypes.SECTION" :position="adPositions.BETWEEN"/>

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
    padding: var(--news-article-padding);
    box-shadow: var(--news-shadow-soft);
}

.article-header {
    border-color: var(--news-border);
    padding-bottom: 1rem;
}

.gallery-strip {
    border-radius: var(--news-radius);
}
</style>
