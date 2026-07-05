<script setup>
import { computed, inject } from 'vue'
import { Head } from '@inertiajs/vue3'

import Layout from '@/pages/layouts/PublicLayout.vue'
import { useTranslate } from '@/composables/useTranslate'
import ListCard from '@/components/common/news/ListCard.vue'
import GridCard from '@/components/common/news/GridCard.vue'
import RecentNewsScroller from '@/components/common/news/RecentNewsScroller.vue'
import EventNewsSection from '@/components/common/page/EventNewsSection.vue'
import VideoNewsSection from '@/components/common/page/VideoNewsSection.vue'
import ImageGalleryNewsSection from '@/components/common/page/ImageGalleryNewsSection.vue'
import CategoryNewsSection from '@/components/common/page/CategoryNewsSection.vue'
import GoogleAdsence from '@/components/common/util/GoogleAdsence.vue'
import Trends from '@/components/common/util/Trends.vue'
import Surveys from '@/components/common/util/Surveys.vue'

import { fetchFromApi } from '@/composables/useSystemApi'
import {
    languages,
} from '@/composables/useTranslate'
import { adTypes, adPositions } from '@/composables/useGoogleAdsence'

defineOptions({ layout: Layout })


const { t } = useTranslate()

const { page, leadNews, recentNews, topEvents, bottomEvents } = defineProps({
    page: {
        type: Object,
        required: true,
    },
    leadNews: {
        type: Object,
        required: true,
    },
    recentNews: {
        type: Object,
        required: true,
    },
    topEvents: {
        type: Object,
        required: true,
    },
    bottomEvents: {
        type: Object,
        required: true,
    },
})

const showGoogleAd = inject('showGoogleAd', computed(() => false))
const showTrends = inject('showTrends', computed(() => false))
const showSurveys = inject('showSurveys', computed(() => false))

const leadNewsItems = computed(() => {
    if (Array.isArray(leadNews)) {
        return leadNews
    }

    return leadNews?.data ?? []
})

const mainLeadNews = computed(() => {
    return leadNewsItems.value.slice(0, 2)
})

const primaryLeadNews = computed(() => {
    return mainLeadNews.value[0] ?? null
})

const mainLeadListNews = computed(() => {
    return mainLeadNews.value.slice(1)
})

const secondaryLeadNews = computed(() => {
    return leadNewsItems.value.slice(2, 6)
})

const extraLeadNews = computed(() => {
    return leadNewsItems.value.slice(6, 10)
})

const recentNewsItems = computed(() => {
    if (Array.isArray(recentNews)) {
        return recentNews
    }

    return recentNews?.data ?? []
})

const metaTitle = computed(() => {
    return page?.seo_title ?? page?.title ?? t('pages.home.labels.page')
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
</script>

<template>

    <Head :title="metaTitle">
        <link v-if="page?.public_url" rel="canonical" :href="page.public_url" />

        <meta v-if="metaTitle" name="title" :content="metaTitle" />

        <meta v-if="metaDescription" name="description" :content="metaDescription" />

        <meta v-if="metaKeywords" name="keywords" :content="metaKeywords" />
    </Head>

    <section class="home-page min-h-screen">
        <div v-if="topEvents" class="home-top-events">
            <EventNewsSection :events="topEvents" class="mb-4" />
        </div>

        <Trends v-if="showTrends" class="home-trends" />

        <div class="home-lead-shell rounded-2xl border border-slate-100 p-2">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                <main class="lg:col-span-8">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-12 lg:grid-cols-12">
                        <div class="space-y-3 md:col-span-6 lg:col-span-6">
                            <GridCard v-if="primaryLeadNews" class="home-primary-lead-card" :news="primaryLeadNews"
                                :hideCategory="true" :hideEvent="true" :hideLocation="true" :hideBrief="true"
                                :isCompact="true" :useFullHeight="false" />

                            <ListCard v-for="(perNews, index) in mainLeadListNews"
                                :key="perNews?.id || perNews?.slug || index" :news="perNews" :hideCategory="true"
                                :hideEvent="true" :hideLocation="true" :hideBrief="true" :isCompact="true"
                                :useFullHeight="false" />
                        </div>

                        <div class="md:col-span-6 lg:col-span-6">
                            <div v-if="secondaryLeadNews.length" class="grid grid-cols-1 gap-3">
                                <ListCard v-for="(perNews, index) in secondaryLeadNews"
                                    :key="perNews?.id || perNews?.slug || index" :news="perNews" :hideCategory="true"
                                    :hideEvent="true" :hideLocation="true" :hideBrief="true" :isCompact="true" />
                            </div>
                        </div>
                        <div class="md:col-span-12 lg:col-span-12">
                            <GoogleAdsence v-if="showGoogleAd" :type="adTypes.SECTION"
                                :position="adPositions.BETWEEN" />
                        </div>
                    </div>
                </main>

                <aside class="lg:col-span-4">
                    <div v-if="recentNewsItems.length"
                        class="home-recent-rail flex h-[400px] flex-col rounded-2xl border border-gray-200 p-2">
                        <div class="recent-news flex shrink-0 items-center gap-2">
                            <h2 class="text-xl font-bold text-gray-950">
                                {{ t('components.common.news.recent_news_list.labels.recent_news') }}
                            </h2>
                        </div>

                        <RecentNewsScroller>
                            <div class="grid grid-cols-1 gap-3">
                                <ListCard v-for="(perNews, index) in recentNewsItems"
                                    :key="perNews?.id || perNews?.slug || index" :news="perNews" :hideSubtitle="true"
                                    :hideBrief="true" :hideCategory="true" :hideEvent="true" :hideLocation="true"
                                    :hideFeatureImage="true" :isCompact="true" />
                            </div>
                        </RecentNewsScroller>

                    </div>

                    <GoogleAdsence v-if="showGoogleAd" :type="adTypes.SIDEBAR" :position="adPositions.BOTTOM"
                        class="mt-4" />
                </aside>
            </div>

            <div v-if="extraLeadNews.length" class="home-extra-news mt-4 border-t border-slate-100 pt-4">
                <div class="grid grid-cols-1 gap-3 md:hidden">
                    <ListCard v-for="(perNews, index) in extraLeadNews" :key="perNews?.id || perNews?.slug || index"
                        :news="perNews" :hideCategory="true" :hideEvent="true" :hideLocation="true" :hideBrief="true"
                        :isCompact="true" />
                </div>

                <div class="hidden gap-3 md:grid md:grid-cols-2 lg:grid-cols-4">
                    <GridCard v-for="(perNews, index) in extraLeadNews" :key="perNews?.id || perNews?.slug || index"
                        :news="perNews" :hideCategory="true" :hideEvent="true" :hideLocation="true" :hideBrief="true" />
                </div>
            </div>
        </div>

        <Surveys v-if="showSurveys" class="home-surveys mt-4" />

        <div v-if="bottomEvents" class="home-bottom-events">
            <EventNewsSection :events="bottomEvents" class="mt-4" />
        </div>

        <GoogleAdsence v-if="showGoogleAd" />

        <CategoryNewsSection v-if="page?.language?.code == languages.English.Code" class="mt-4"
            categoryIdOrSlug="politics" :language="page?.language" :style="1" :limit="4" />
        <CategoryNewsSection v-if="page?.language?.code == languages.Bangla.Code" class="mt-4"
            categoryIdOrSlug="রাজনীতি" :language="page?.language" :style="1" :limit="4" />

        <CategoryNewsSection v-if="page?.language?.code == languages.English.Code" class="mt-4"
            categoryIdOrSlug="national" :language="page?.language" :style="1" :limit="4" />
        <CategoryNewsSection v-if="page?.language?.code == languages.Bangla.Code" class="mt-4" categoryIdOrSlug="জাতীয়"
            :language="page?.language" :style="1" :limit="4" />

        <GoogleAdsence v-if="showGoogleAd" />

        <CategoryNewsSection v-if="page?.language?.code == languages.English.Code" class="mt-4"
            categoryIdOrSlug="sports" :language="page?.language" :style="2" :limit="6" />
        <CategoryNewsSection v-if="page?.language?.code == languages.Bangla.Code" class="mt-4"
            categoryIdOrSlug="খেলাধুলা" :language="page?.language" :style="2" :limit="6" />

        <CategoryNewsSection v-if="page?.language?.code == languages.English.Code" class="mt-4"
            categoryIdOrSlug="entertainment" :language="page?.language" :style="2" :limit="6" />
        <CategoryNewsSection v-if="page?.language?.code == languages.Bangla.Code" class="mt-4" categoryIdOrSlug="বিনোদন"
            :language="page?.language" :style="2" :limit="6" />

        <GoogleAdsence v-if="showGoogleAd" />

        <VideoNewsSection class="mt-4" />

        <div class="mt-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="space-y-3">
                    <CategoryNewsSection v-if="page?.language?.code == languages.English.Code" class="mt-4"
                        categoryIdOrSlug="international" :language="page?.language" :style="3" :limit="4" />
                    <CategoryNewsSection v-if="page?.language?.code == languages.Bangla.Code" class="mt-4"
                        categoryIdOrSlug="আন্তর্জাতিক" :language="page?.language" :style="3" :limit="4" />
                </div>

                <div class="space-y-3">
                    <CategoryNewsSection v-if="page?.language?.code == languages.English.Code" class="mt-4"
                        categoryIdOrSlug="technology" :language="page?.language" :style="3" :limit="4" />
                    <CategoryNewsSection v-if="page?.language?.code == languages.Bangla.Code" class="mt-4"
                        categoryIdOrSlug="প্রযুক্তি" :language="page?.language" :style="3" :limit="4" />
                </div>
            </div>
        </div>

        <ImageGalleryNewsSection class="mt-4" />

        <GoogleAdsence v-if="showGoogleAd" />

        <div class="mt-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="space-y-3">
                    <CategoryNewsSection v-if="page?.language?.code == languages.English.Code" class="mt-4"
                        categoryIdOrSlug="health" :language="page?.language" :style="4" :limit="4" />
                    <CategoryNewsSection v-if="page?.language?.code == languages.Bangla.Code" class="mt-4"
                        categoryIdOrSlug="স্বাস্থ্য" :language="page?.language" :style="4" :limit="4" />
                </div>

                <div class="space-y-3">
                    <CategoryNewsSection v-if="page?.language?.code == languages.English.Code" class="mt-4"
                        categoryIdOrSlug="education" :language="page?.language" :style="4" :limit="4" />
                    <CategoryNewsSection v-if="page?.language?.code == languages.Bangla.Code" class="mt-4"
                        categoryIdOrSlug="শিক্ষা" :language="page?.language" :style="4" :limit="4" />
                </div>

                <div class="space-y-3">
                    <CategoryNewsSection v-if="page?.language?.code == languages.English.Code" class="mt-4"
                        categoryIdOrSlug="lifestyle" :language="page?.language" :style="4" :limit="4" />
                    <CategoryNewsSection v-if="page?.language?.code == languages.Bangla.Code" class="mt-4"
                        categoryIdOrSlug="জীবনধারা" :language="page?.language" :style="4" :limit="4" />
                </div>
            </div>
        </div>

        <GoogleAdsence v-if="showGoogleAd" :type="adTypes.SECTION" :position="adPositions.BOTTOM" />
    </section>
</template>

<style scoped>
.home-page {
    display: flex;
    flex-direction: column;
    gap: var(--news-section-gap);
}

.home-lead-shell {
    border-color: var(--news-border-soft);
    background: var(--news-lead-gradient);
    box-shadow: var(--news-shadow);
}

.home-primary-lead-card :deep(h3) {
    font-size: var(--news-lead-title-size);
    line-height: 1.25;
}

.home-extra-news {
    border-color: var(--news-border);
}

.home-recent-rail {
    border-color: var(--news-border-primary-soft);
    background: var(--news-recent-gradient);
    box-shadow: var(--news-shadow-soft);
}

.home-recent-rail .recent-news {
    border-bottom: var(--news-border-default);
    margin-bottom: 0.75rem;
    padding: 0.5rem 0.5rem 0.75rem;
}

.home-recent-rail .recent-news h2 {
    position: relative;
    padding-inline-start: 0.75rem;
    font-size: var(--news-section-heading-size);
}

.home-recent-rail .recent-news h2::before {
    content: '';
    position: absolute;
    inset-block: 0.25rem;
    inset-inline-start: 0;
    width: 0.25rem;
    border-radius: 999px;
    background: var(--news-primary);
}

.home-top-events :deep(.event-news-section) {
    background: var(--news-event-top-gradient);
}

.home-bottom-events :deep(.event-news-section) {
    background: var(--news-event-bottom-gradient);
}

.home-surveys {
    display: block;
}

@media (max-width: 1023px) {
    .home-recent-rail {
        height: auto;
        max-height: 34rem;
    }
}

@media (max-width: 640px) {
    .home-lead-shell {
        border-radius: var(--news-radius-sm);
        padding: 0.5rem;
    }
}
</style>
