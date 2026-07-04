<script setup>
import { computed,inject } from 'vue'
import { Head } from '@inertiajs/vue3'

import Layout from '@/pages/layouts/PublicLayout.vue'
import RelatedNewsGrid from '@/components/common/news/RelatedNewsGrid.vue'
import GoogleAdsence from '@/components/common/util/GoogleAdsence.vue'

import { useTranslate } from '@/composables/useTranslate'
import { adTypes, adPositions } from '@/composables/useGoogleAdsence'

defineOptions({ layout: Layout })

const { t } = useTranslate()

const { page } = defineProps({
    page: {
        type: Object,
        required: true,
    },
})

const metaTitle = computed(() => {
    return page?.seo_title ?? page?.title ?? t('pages.page.labels.page')
})

const metaDescription = computed(() => {
    return page?.seo_brief ?? page?.brief ?? ''
})

const metaKeywords = computed(() => {
    if (Array.isArray(page?.seo_keywords)) {
        return page.seo_keywords.join(', ')
    }

    return page?.seo_keywords ?? ''
})
const showGoogleAd = inject('showGoogleAd', computed(() => false))
</script>

<template>

    <Head :title="metaTitle">
        <link v-if="page?.public_url" rel="canonical" :href="page.public_url" />

        <meta v-if="metaTitle" name="title" :content="metaTitle" />
        <meta v-if="metaDescription" name="description" :content="metaDescription" />
        <meta v-if="metaKeywords" name="keywords" :content="metaKeywords" />
    </Head>

    <div class="static-page space-y-6">
        <GoogleAdsence v-if="showGoogleAd" :type="adTypes.SECTION" :position="adPositions.TOP" />

        <article v-if="page?.body" class="page-content prose max-w-none" v-html="page.body" />

        <GoogleAdsence v-if="showGoogleAd" :type="adTypes.SECTION" :position="adPositions.BOTTOM" />
    </div>
</template>

<style scoped>
.page-content {
    border: var(--news-border-default);
    border-radius: var(--news-radius);
    background: var(--news-surface);
    padding: var(--news-content-padding);
    box-shadow: var(--news-shadow-soft);
    color: var(--news-muted-strong);
}

.page-content :deep(h1),
.page-content :deep(h2),
.page-content :deep(h3) {
    color: var(--news-ink);
    letter-spacing: 0;
}

.page-content :deep(a) {
    color: var(--news-primary);
    font-weight: 700;
}
</style>
