<script setup>
import { computed,inject } from 'vue'
import { Head } from '@inertiajs/vue3'

import Layout from '@/pages/layouts/PublicLayout.vue'
import List from '@/components/common/news/List.vue'
import GoogleAdsence from '@/components/common/util/GoogleAdsence.vue'

import { useTranslate } from '@/composables/useTranslate'
import { adTypes, adPositions } from '@/composables/useGoogleAdsence'


defineOptions({ layout: Layout })

const { t } = useTranslate()

const { news, page } = defineProps({
    news: {
        type: Object,
        required: true,
    },

    page: {
        type: Object,
        required: true,
    },
})

const metaTitle = computed(() => {
    return page?.seo_title ?? page?.title ?? t('pages.latest.labels.latest_news')
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
        <link rel="canonical" :href="route('latest')" />

        <meta v-if="metaTitle" name="title" :content="metaTitle" />

        <meta v-if="metaDescription" name="description" :content="metaDescription" />

        <meta v-if="metaKeywords" name="keywords" :content="metaKeywords" />
    </Head>

    <div class="latest-page space-y-6">
        <section class="page-hero">
            <p>{{ t('pages.latest.labels.latest_news') }}</p>
            <h1>{{ t('pages.latest.labels.latest_news') }}</h1>
        </section>

        <List :news="news" pagination-type="Cursor" />
    </div>

    <GoogleAdsence v-if="showGoogleAd" :type="adTypes.SECTION" :position="adPositions.BOTTOM" />
</template>

<style scoped>
.page-hero {
    border: var(--news-border-default);
    border-radius: var(--news-radius);
    background: var(--news-hero-danger-gradient);
    padding: var(--news-hero-padding);
    box-shadow: var(--news-shadow-soft);
}

.page-hero p {
    color: var(--news-primary);
    font-size: var(--news-page-kicker-size);
    font-weight: 800;
    letter-spacing: 0;
    text-transform: uppercase;
}

.page-hero h1 {
    margin-top: 0.35rem;
    color: var(--news-ink);
    font-size: var(--news-page-title-size);
    font-weight: 800;
    line-height: 1.15;
}
</style>
