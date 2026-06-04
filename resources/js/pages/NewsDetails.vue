<script setup>
import { computed } from 'vue'
import Layout from '@/pages/layouts/PublicLayout.vue'

import {
    isStory as checkIsStory,
    isVideo as checkIsVideo,
    isImageGallery as checkIsImageGallery,
} from '@/composables/useNews'

import SeoHeader from '@/components/common/news/SeoHeader.vue'
import Story from '@/components/common/news/view/Story.vue'
import Video from '@/components/common/news/view/Video.vue'
import ImageGallery from '@/components/common/news/view/ImageGallery.vue'

defineOptions({ layout: Layout })

const { news } = defineProps({
    news: {
        type: Object,
        required: true,
    },
})

</script>

<template>
    <SeoHeader :news="news" />

    <Story v-if="checkIsStory(news?.news_type)" :news="news" />
    <Video v-if="checkIsVideo(news?.news_type)" :news="news" />
    <ImageGallery v-if="checkIsImageGallery(news?.news_type)" :news="news" />
</template>
