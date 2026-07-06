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

const { contributor, news } = defineProps({
    contributor: {
        type: Object,
        required: true,
    },

    news: {
        type: Object,
        required: true,
    },
})

const metaTitle = computed(() => {
    return contributor?.seo_title || contributor?.name || ''
})

const metaDescription = computed(() => {
    return contributor?.seo_brief || contributor?.brief || ''
})

const metaKeywords = computed(() => {
    if (Array.isArray(contributor?.seo_keywords)) {
        return contributor.seo_keywords.join(', ')
    }

    return contributor?.seo_keywords || ''
})

const contributorImage = computed(() => {
    return contributor?.profile_image?.preview_url
        || contributor?.profile_image?.original_url
        || '/uploads/images/logo/contributor.png'
})
const showGoogleAd = inject('showGoogleAd', computed(() => false))
</script>

<template>

    <Head :title="contributor?.name || t('pages.contributor_news.labels.contributor')">
        <link v-if="contributor?.public_url" rel="canonical" :href="contributor.public_url" />

        <meta v-if="metaTitle" name="title" :content="metaTitle" />

        <meta v-if="metaDescription" name="description" :content="metaDescription" />

        <meta v-if="metaKeywords" name="keywords" :content="metaKeywords" />
    </Head>

    <div class="entity-page contributor-page space-y-6">
        <section class="grid grid-cols-1 items-center gap-5 md:grid-cols-12">
            <div class="md:col-span-3 lg:col-span-2">
                <div class="flex h-28 w-28 items-center justify-center rounded-2xl bg-blue-50 p-4 sm:h-32 sm:w-32">
                    <img :src="contributorImage"
                        :alt="contributor?.name || t('pages.contributor_news.contributors.details.contributor_image_alt')"
                        class="h-full w-full object-contain" loading="lazy" />
                </div>
            </div>

            <div class="space-y-2 md:col-span-9 lg:col-span-10">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">
                    {{ t('pages.contributor_news.labels.contributor') }}
                </p>

                <h1 class="text-2xl font-bold tracking-tight text-gray-950 sm:text-3xl">
                    {{ contributor?.name }}
                </h1>

                <p v-if="contributor?.brief" class="max-w-3xl text-sm leading-7 text-gray-600 sm:text-base">
                    {{ contributor.brief }}
                </p>

                <section v-if="contributor?.profile_details"
                    class="max-w-3xl text-sm leading-7 text-gray-600 sm:text-base"
                    v-html="contributor.profile_details" />
            </div>
        </section>

        <div class="border-t border-gray-200"></div>

        <GoogleAdsence v-if="showGoogleAd" class="mt-4 mb-4" :type="adTypes.SECTION" :position="adPositions.BETWEEN"/>

        <List :news="news" pagination-type="Cursor" />
    </div>
</template>

<style scoped>
.entity-page > section:first-child {
    border: var(--news-border-default);
    border-radius: var(--news-radius);
    background: var(--news-hero-info-gradient);
    padding: var(--news-hero-padding);
    box-shadow: var(--news-shadow-soft);
}

.entity-page :deep([v-html]) {
    color: var(--news-muted);
}
</style>
