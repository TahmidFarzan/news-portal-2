<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

import CategoryLocationEvent from '@/components/common/news/CategoryLocationEvent.vue'
import TagTrend from '@/components/common/news/TagTrend.vue'
import ImageWithLightBox from '@/components/common/media/ImageWithLightBox.vue'
import SocialShare from '@/components/common/news/SocialShare.vue'
import TitleSubtitleContentShoulder from '@/components/common/news/TitleSubtitleContentShoulder.vue'

import { formatDateTime } from '@/composables/useDateTime'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faClock,
} from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(
    faClock
)

const { news } = defineProps({
    news: {
        type: Object,
        required: true,
    },
})

</script>

<template>
    <article class="space-y-6">
        <header class="space-y-3 border-b border-gray-200">
            <CategoryLocationEvent :news="news" />

            <TitleSubtitleContentShoulder :news="news"/>

            <p v-if="news?.brief" class="max-w-3xl text-lg leading-8 text-gray-700">
                {{ news.brief }}
            </p>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-3 text-sm text-gray-500 mb-3">
                <span class="inline-flex items-center gap-1.5">
                    <FontAwesomeIcon icon="clock" class="text-xs text-gray-400" />
                    {{ news?.published_at || formatDateTime(news?.created_at) }}
                </span>

                <span v-if="news?.published_at || news?.created_at" class="hidden h-4 w-px bg-gray-300 sm:inline-block" />

                <SocialShare :news="news"/>
            </div>
        </header>

        <section v-if="news?.gallery_images" class="flex flex-col gap-8">
            <ImageWithLightBox v-for="image in news?.gallery_images" :key="image?.id || image?.uuid" :image="image"  :is-image-gallery-item="true" :show-image-galery-counter="true"/>
        </section>

        <div v-else class="rounded-2xl border border-dashed border-gray-300 p-8 text-center text-gray-500">
            No gallery images found.
        </div>

        <TagTrend :news="news" />
    </article>
</template>
