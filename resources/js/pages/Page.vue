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

    <div class="space-y-6">
        <GoogleAdsence v-if="showGoogleAd" :type="adTypes.SECTION" :position="adPositions.TOP" />

        <div v-if="page?.body" class="prose max-w-none" v-html="page.body" />

        <GoogleAdsence v-if="showGoogleAd" :type="adTypes.SECTION" :position="adPositions.BOTTOM" />
    </div>
</template>
