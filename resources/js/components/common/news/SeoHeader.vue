<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'

import {
    isStory as checkIsStory,
    isVideo as checkIsVideo,
} from '@/composables/useNews'

const { news } = defineProps({
    news: {
        type: Object,
        required: true,
    },
})

const tagNames = computed(() => {
    return (news?.tags ?? [])
        .map((tag) => tag?.name)
        .filter(Boolean)
})

const contributorNames = computed(() => {
    return (news?.contributors ?? [])
        .map((contributor) => contributor?.name)
        .filter(Boolean)
})

const title = computed(() => {
    return news?.seo_title || news?.title || 'News Details'
})

const description = computed(() => {
    return news?.seo_brief || news?.brief || ''
})

const keywords = computed(() => {
    return news?.seo_keywords || tagNames.value.join(', ')
})

const author = computed(() => {
    if (!checkIsStory(news?.news_type)) {
        return ''
    }

    return [news?.writer, ...contributorNames.value]
        .filter(Boolean)
        .join(', ')
})

const canonicalUrl = computed(() => {
    return news?.public_url || ''
})

const imageUrl = computed(() => {
    return (
        news?.feature_image_mobile?.preview_url ||
        news?.feature_image?.preview_url ||
        news?.feature_image_mobile?.original_url ||
        news?.feature_image?.original_url ||
        ''
    )
})

const ogType = computed(() => {
    if (checkIsVideo(news?.news_type)) {
        return 'video.other'
    }

    if (checkIsStory(news?.news_type)) {
        return 'article'
    }

    return 'website'
})
</script>

<template>
    <Head :title="title">
        <link v-if="canonicalUrl" rel="canonical" :href="canonicalUrl" />

        <meta v-if="title" name="title" :content="title" />
        <meta v-if="description" name="description" :content="description" />
        <meta v-if="keywords" name="keywords" :content="keywords" />
        <meta v-if="author" name="author" :content="author" />

        <meta v-if="title" property="og:title" :content="title" />
        <meta v-if="description" property="og:description" :content="description" />
        <meta property="og:type" :content="ogType" />
        <meta v-if="canonicalUrl" property="og:url" :content="canonicalUrl" />
        <meta v-if="imageUrl" property="og:image" :content="imageUrl" />

        <meta name="twitter:card" content="summary_large_image" />
        <meta v-if="title" name="twitter:title" :content="title" />
        <meta v-if="description" name="twitter:description" :content="description" />
        <meta v-if="imageUrl" name="twitter:image" :content="imageUrl" />
    </Head>
</template>
