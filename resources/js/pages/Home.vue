<script setup>
import { computed, inject } from 'vue'
import { Head } from '@inertiajs/vue3'

import Layout from '@/pages/layouts/PublicLayout.vue'
import { useTranslate } from '@/composables/useTranslate'
import ListCard from '@/components/common/news/ListCard.vue'
import GridCard from '@/components/common/news/GridCard.vue'
import PageSidebar from '@/components/common/page/PageSidebar.vue'
import EventNewsSection from '@/components/common/page/home/EventNewsSection.vue'
import VideoNewsSection from '@/components/common/page/home/VideoNewsSection.vue'
import ImageGalleryNewsSection from '@/components/common/page/home/ImageGalleryNewsSection.vue'
import CategoryNewsSection from '@/components/common/page/home/CategoryNewsSection.vue'
import Trends from '@/components/common/page/home/Trends.vue'
import Surveys from '@/components/common/page/home/Surveys.vue'
import Quizzes from '@/components/common/page/home/Quizzes.vue'
import GoogleAdSense from '@/components/common/advertising/GoogleAdSense.vue'
import { loweriseText } from '@/composables/useUtil'

import {
    languages,
} from '@/composables/useTranslate'
import { adTypes, adPositions } from '@/composables/useGoogleAdsence'

defineOptions({ layout: Layout })

const { t } = useTranslate()

const { page, leadNews, recentNews, popularNews, topEvents, bottomEvents, trends,} = defineProps({
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
    popularNews: {
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

    trends: {
        type: Object,
        required: true,
    },
})

const showGoogleAd = inject('showGoogleAd', computed(() => false))
const showTrends = inject('showTrends', computed(() => false))
const showSurveys = inject('showSurveys', computed(() => false))
const showQuizzes = inject('showQuizzes', computed(() => false))
const currentLanguage = inject('currentLanguage', computed(() => null))

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

const metaTitle = computed(() => {
    return page?.seo_title ?? page?.title ?? t('common.labels.page')
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

const componentRefreshKey = (componentName) => {
    return [
        'home-section-component',
        componentName,
        loweriseText(currentLanguage?.code) || 'default',
    ].join('-')
}

</script>

<template>

    <Head :title="metaTitle">
        <link v-if="page?.public_url" rel="canonical" :href="page?.public_url" />

        <meta v-if="metaTitle" name="title" :content="metaTitle" />

        <meta v-if="metaDescription" name="description" :content="metaDescription" />

        <meta v-if="metaKeywords" name="keywords" :content="metaKeywords" />
    </Head>

    <section class="home-page min-h-screen">
        <div v-if="topEvents" :key="componentRefreshKey('event-section-top')"  class="home-top-events">
            <EventNewsSection :events="topEvents" :currentLanguage="currentLanguage" />
            <Quizzes v-if="showQuizzes"
            :currentLanguage="currentLanguage" :belowEvent="true"  class="home-quizzes mt-4"/>
        </div>

        <Trends :key="componentRefreshKey('trend')" v-if="showTrends" class="home-trends" :trends="trends" />

        <div class="home-lead-shell rounded-2xl border border-slate-100 p-2">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                <main class="lg:col-span-8" :key="componentRefreshKey('main')">
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
                            <GoogleAdSense v-if="showGoogleAd" :type="adTypes.SECTION"
                                :position="adPositions.BETWEEN" />
                        </div>
                    </div>
                </main>

                <aside class="lg:col-span-4" :key="componentRefreshKey('sidebar')">
                    <PageSidebar :recentNews="recentNews" :popularNews="popularNews" />
                    <GoogleAdSense v-if="showGoogleAd" :type="adTypes.SIDEBAR" :position="adPositions.BOTTOM" class="mt-4" />
                </aside>
            </div>

            <div :key="componentRefreshKey('extra-news')" v-if="extraLeadNews.length"
                class="home-extra-news mt-4 border-t border-slate-100 pt-4">
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

        <div v-if="bottomEvents" :key="componentRefreshKey('event-section-bottom')" class="home-bottom-events">
            <EventNewsSection :events="bottomEvents"
                :currentLanguage="currentLanguage" class="mt-4" />
            <Quizzes v-if="showQuizzes"
            :currentLanguage="currentLanguage" :belowEvent="true"  class="home-quizzes mt-4"/>
        </div>

        <Surveys :key="componentRefreshKey('survey-section')" v-if="showSurveys"
            :currentLanguage="currentLanguage"  class="home-surveys mt-4"/>
        <Quizzes :key="componentRefreshKey('quiz-section')" v-if="showQuizzes"
            :currentLanguage="currentLanguage" :belowEvent="false"  class="home-quizzes mt-4"/>

        <GoogleAdSense v-if="showGoogleAd" :type="adTypes.SECTION" :position="adPositions.BETWEEN" class="mt-4 mb-4"/>

        <CategoryNewsSection :key="componentRefreshKey('politic-section')" v-if="page?.language?.code == languages.English.Code"
            categorySlug="politics" :currentLanguage="currentLanguage"  :style="1" :limit="4" class="home-politics-section mt-4" />
        <CategoryNewsSection :key="componentRefreshKey('politic-section')" v-if="page?.language?.code == languages.Bangla.Code"
            categorySlug="রাজনীতি" :currentLanguage="currentLanguage"   :style="1" :limit="4" class="home-politics-section mt-4" />

        <CategoryNewsSection :key="componentRefreshKey('national-section')" v-if="page?.language?.code == languages.English.Code"
            categorySlug="national" :currentLanguage="currentLanguage"  :style="1" :limit="4" class="home-national-section mt-4"/>
        <CategoryNewsSection :key="componentRefreshKey('national-section')" v-if="page?.language?.code == languages.Bangla.Code"
            categorySlug="জাতীয়" :currentLanguage="currentLanguage" :style="1" :limit="4" class="home-national-section mt-4"/>

        <GoogleAdSense v-if="showGoogleAd" :type="adTypes.SECTION" :position="adPositions.BETWEEN" class="mt-4 mb-4"/>

        <CategoryNewsSection :key="componentRefreshKey('sport-section')" v-if="page?.language?.code == languages.English.Code"
            categorySlug="sports" :currentLanguage="currentLanguage" :style="2" :limit="6" class="home-sports-section mt-4"/>
        <CategoryNewsSection :key="componentRefreshKey('sport-section')" v-if="page?.language?.code == languages.Bangla.Code"
            categorySlug="খেলাধুলা" :currentLanguage="currentLanguage" :style="2" :limit="6" class="home-sports-section mt-4" />

        <CategoryNewsSection :key="componentRefreshKey('entertainment-section')" v-if="page?.language?.code == languages.English.Code"
            categorySlug="entertainment" :currentLanguage="currentLanguage" :style="2" :limit="6" class="home-entertainment-section mt-4" />
        <CategoryNewsSection :key="componentRefreshKey('entertainment-section')" v-if="page?.language?.code == languages.Bangla.Code"
            categorySlug="বিনোদন" :currentLanguage="currentLanguage" :style="2" :limit="6" class="home-entertainment-section mt-4"/>

        <GoogleAdSense v-if="showGoogleAd" :type="adTypes.SECTION" :position="adPositions.BETWEEN" class="mt-4 mb-4"/>

        <VideoNewsSection :key="componentRefreshKey('video-section')" :currentLanguage="currentLanguage" class="home-video-news-section mt-4" />

        <div class="mt-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="space-y-3">
                    <CategoryNewsSection :key="componentRefreshKey('internation-section')" v-if="page?.language?.code == languages.English.Code"
                        categorySlug="international" :currentLanguage="currentLanguage" :style="3" :limit="4" class="home-international-section mt-4"/>
                    <CategoryNewsSection :key="componentRefreshKey('international-section')" v-if="page?.language?.code == languages.Bangla.Code"
                        categorySlug="আন্তর্জাতিক" :currentLanguage="currentLanguage" :style="3" :limit="4"  class="home-international-section mt-4"/>
                </div>

                <div class="space-y-3">
                    <CategoryNewsSection :key="componentRefreshKey('technology-section')" v-if="page?.language?.code == languages.English.Code"
                        categorySlug="technology" :currentLanguage="currentLanguage" :style="3" :limit="4"  class="home-technology-section mt-4"/>
                    <CategoryNewsSection :key="componentRefreshKey('technology-section')" v-if="page?.language?.code == languages.Bangla.Code"
                        categorySlug="প্রযুক্তি" :currentLanguage="currentLanguage" :style="3" :limit="4" class="home-technology-section mt-4"/>
                </div>
            </div>
        </div>

        <ImageGalleryNewsSection :key="componentRefreshKey('image-gallery-section')"
            :currentLanguage="currentLanguage"  class="home-gallery-news-section mt-4" />

        <GoogleAdSense v-if="showGoogleAd" class="mt-4 mb-4" :type="adTypes.SECTION" :position="adPositions.BETWEEN"/>

        <div class="mt-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="space-y-3">
                    <CategoryNewsSection :key="componentRefreshKey('health-section')" v-if="page?.language?.code == languages.English.Code"
                        categorySlug="health" :currentLanguage="currentLanguage" :style="4" :limit="4" class="home-health-section mt-4" />
                    <CategoryNewsSection :key="componentRefreshKey('health-section')" v-if="page?.language?.code == languages.Bangla.Code"
                        categorySlug="স্বাস্থ্য" :currentLanguage="currentLanguage" :style="4" :limit="4" class="home-health-section mt-4" />
                </div>

                <div class="space-y-3">
                    <CategoryNewsSection :key="componentRefreshKey('education-section')" v-if="page?.language?.code == languages.English.Code"
                        categorySlug="education" :currentLanguage="currentLanguage" :style="4" :limit="4" class="mt-4"/>
                    <CategoryNewsSection :key="componentRefreshKey('education-section')" v-if="page?.language?.code == languages.Bangla.Code"
                        categorySlug="শিক্ষা" :currentLanguage="currentLanguage" :style="4" :limit="4" class="mt-4"/>
                </div>

                <div class="space-y-3">
                    <CategoryNewsSection :key="componentRefreshKey('lifestyle-section')" v-if="page?.language?.code == languages.English.Code"
                        categorySlug="lifestyle" :currentLanguage="currentLanguage" :style="4" :limit="4" class="mt-4"/>
                    <CategoryNewsSection :key="componentRefreshKey('lifestyle-section')" v-if="page?.language?.code == languages.Bangla.Code"
                        categorySlug="জীবনধারা" :currentLanguage="currentLanguage" :style="4" :limit="4" class="mt-4"/>
                </div>
            </div>
        </div>

        <GoogleAdSense v-if="showGoogleAd" :type="adTypes.SECTION" :position="adPositions.BOTTOM" />
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

.home-quizzes {
    display: block;
}

.home-politics-section,
.home-national-section,
.home-international-section,
.home-technology-section,
.home-health-section,
.home-sports-section,
.home-entertainment-section,
.home-video-news-section,
.home-gallery-news-section {
    overflow: hidden;
}

.home-politics-section,
.home-national-section,
.home-international-section,
.home-technology-section,
.home-health-section,
.home-sports-section,
.home-entertainment-section {
    padding: clamp(0.7rem, 1.6vw, 1rem);
    border: 1px solid var(--news-border-soft);
    border-radius: var(--news-radius);
    box-shadow: var(--news-shadow-soft);
}

.home-politics-section :deep(.category-heading),
.home-national-section :deep(.category-heading),
.home-international-section :deep(.category-heading),
.home-technology-section :deep(.category-heading),
.home-health-section :deep(.category-heading) {
    margin-bottom: 1rem;
}

.home-politics-section :deep(.news-grid-card),
.home-politics-section :deep(.news-list-card),
.home-national-section :deep(.news-grid-card),
.home-national-section :deep(.news-list-card),
.home-international-section :deep(.news-grid-card),
.home-international-section :deep(.news-list-card),
.home-technology-section :deep(.news-grid-card),
.home-technology-section :deep(.news-list-card),
.home-health-section :deep(.news-grid-card),
.home-health-section :deep(.news-list-card) {
    transition:
        border-color var(--news-transition),
        box-shadow var(--news-transition);
}

.home-politics-section {
    background:
        linear-gradient(90deg, rgb(15 23 42 / 4%), transparent 36%),
        linear-gradient(180deg, rgb(248 250 252 / 96%) 0%, #ffffff 100%);
    border-color: rgb(15 23 42 / 10%);
}

.home-politics-section :deep(.category-heading) {
    border-color: rgb(15 23 42 / 16%);
}

.home-politics-section :deep(.category-heading::before) {
    background: linear-gradient(180deg, #111827, var(--news-primary-dark));
}

.home-politics-section :deep(.news-grid-card),
.home-politics-section :deep(.news-list-card) {
    border-color: rgb(15 23 42 / 12%);
    box-shadow: var(--news-shadow-list);
}

.home-politics-section :deep(.news-grid-card:hover),
.home-politics-section :deep(.news-list-card:hover) {
    border-color: rgb(15 23 42 / 22%);
}

.home-national-section {
    background:
        linear-gradient(90deg, rgb(185 28 28 / 3%), transparent 32%),
        linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border-color: var(--news-border-soft);
}

.home-national-section :deep(.category-heading) {
    border-color: var(--news-border);
}

.home-national-section :deep(.category-heading::before) {
    background: var(--news-primary);
}

.home-national-section :deep(.news-grid-card),
.home-national-section :deep(.news-list-card) {
    border-color: rgb(15 23 42 / 9%);
}

.home-national-section :deep(.news-grid-card:hover),
.home-national-section :deep(.news-list-card:hover) {
    border-color: var(--news-border-primary-hover);
}

.home-international-section {
    background:
        linear-gradient(135deg, rgb(224 242 254 / 34%) 0%, transparent 42%),
        linear-gradient(90deg, rgb(37 99 235 / 4%) 0 1px, transparent 1px 100%),
        linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    background-size: auto, 4rem 100%, auto;
    border-color: rgb(37 99 235 / 12%);
}

.home-international-section :deep(.category-heading) {
    border-color: rgb(37 99 235 / 16%);
}

.home-international-section :deep(.category-heading::before) {
    background: linear-gradient(180deg, var(--news-link), #0f766e);
}

.home-international-section :deep(.news-grid-card),
.home-international-section :deep(.news-list-card) {
    border-color: rgb(37 99 235 / 13%);
}

.home-international-section :deep(.news-grid-card:hover),
.home-international-section :deep(.news-list-card:hover) {
    border-color: rgb(37 99 235 / 25%);
}

.home-technology-section {
    background:
        linear-gradient(135deg, rgb(240 253 250 / 74%) 0%, transparent 40%),
        linear-gradient(115deg, transparent 0%, rgb(224 242 254 / 34%) 56%, transparent 86%),
        linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border-color: rgb(15 118 110 / 14%);
}

.home-technology-section :deep(.category-heading) {
    border-color: rgb(15 118 110 / 18%);
}

.home-technology-section :deep(.category-heading::before) {
    background: linear-gradient(180deg, #0891b2, var(--news-accent));
}

.home-technology-section :deep(.news-grid-card),
.home-technology-section :deep(.news-list-card) {
    border-color: rgb(15 118 110 / 15%);
    box-shadow: var(--news-shadow-list);
}

.home-technology-section :deep(.news-grid-card:hover),
.home-technology-section :deep(.news-list-card:hover) {
    border-color: rgb(15 118 110 / 28%);
}

.home-health-section {
    background:
        linear-gradient(135deg, rgb(220 252 231 / 42%) 0%, transparent 42%),
        linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border-color: rgb(22 163 74 / 12%);
}

.home-health-section :deep(.category-heading) {
    border-color: rgb(22 163 74 / 14%);
}

.home-health-section :deep(.category-heading::before) {
    background: linear-gradient(180deg, #16a34a, var(--news-accent));
}

.home-health-section :deep(.news-grid-card),
.home-health-section :deep(.news-list-card) {
    border-color: rgb(22 163 74 / 12%);
    box-shadow: var(--news-shadow-list);
}

.home-health-section :deep(.news-grid-card:hover),
.home-health-section :deep(.news-list-card:hover) {
    border-color: rgb(22 163 74 / 22%);
}

.home-sports-section {
    background:
        linear-gradient(135deg, rgb(15 118 110 / 8%) 0%, transparent 32%),
        linear-gradient(105deg, transparent 0%, transparent 58%, rgb(185 28 28 / 4%) 58.5%, transparent 74%),
        linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.home-sports-section :deep(.category-heading) {
    border-color: rgb(15 118 110 / 18%);
}

.home-sports-section :deep(.category-heading::before) {
    background: linear-gradient(180deg, var(--news-accent), var(--news-primary));
}

.home-sports-section :deep(.news-grid-card),
.home-sports-section :deep(.news-list-card) {
    border-color: rgb(15 118 110 / 16%);
}

.home-sports-section :deep(.news-grid-card:hover),
.home-sports-section :deep(.news-list-card:hover) {
    border-color: rgb(15 118 110 / 30%);
}

.home-entertainment-section {
    background:
        linear-gradient(135deg, rgb(255 247 237 / 72%) 0%, transparent 40%),
        linear-gradient(100deg, transparent 0%, rgb(254 226 226 / 24%) 48%, transparent 78%),
        linear-gradient(180deg, #ffffff 0%, #fffafa 100%);
}

.home-entertainment-section :deep(.category-heading) {
    border-color: rgb(185 28 28 / 13%);
}

.home-entertainment-section :deep(.category-heading::before) {
    background: linear-gradient(180deg, #f59e0b, var(--news-primary));
}

.home-entertainment-section :deep(.news-grid-card),
.home-entertainment-section :deep(.news-list-card) {
    border-color: rgb(185 28 28 / 12%);
}

.home-entertainment-section :deep(.news-grid-card:hover),
.home-entertainment-section :deep(.news-list-card:hover) {
    border-color: rgb(185 28 28 / 24%);
}

.home-video-news-section {
    position: relative;
    border-color: rgb(255 255 255 / 10%);
    background:
        linear-gradient(135deg, #111827 0%, #1f2937 58%, #450a0a 140%);
    box-shadow: 0 18px 38px rgb(15 23 42 / 14%);
}

.home-video-news-section::before {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    background:
        linear-gradient(180deg, rgb(255 255 255 / 8%), transparent 30%);
}

.home-video-news-section :deep(.section-heading),
.home-video-news-section :deep(.news-type-slider) {
    position: relative;
}

.home-video-news-section :deep(.section-heading) {
    padding-bottom: 0.85rem;
    border-bottom: 1px solid rgb(255 255 255 / 10%);
}

.home-video-news-section :deep(.section-heading h2) {
    text-shadow: 0 2px 10px rgb(0 0 0 / 22%);
}

.home-video-news-section :deep(.news-grid-card) {
    border-color: rgb(255 255 255 / 12%);
    box-shadow: 0 12px 28px rgb(0 0 0 / 14%);
}

.home-video-news-section :deep(.news-grid-card img) {
    filter: contrast(1.02) saturate(1.02);
}

.home-gallery-news-section {
    position: relative;
    padding: clamp(0.7rem, 1.6vw, 1rem);
    border-color: rgb(15 23 42 / 10%);
    background:
        linear-gradient(90deg, rgb(15 118 110 / 4%), transparent 34%),
        linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    box-shadow: var(--news-shadow-soft);
}

.home-gallery-news-section :deep(.section-heading) {
    border-color: rgb(15 23 42 / 10%);
}

.home-gallery-news-section :deep(.section-heading h2) {
    color: var(--news-ink);
}

.home-gallery-news-section :deep(.news-grid-card) {
    border-color: rgb(15 23 42 / 10%);
    box-shadow: var(--news-shadow-soft);
}

.home-gallery-news-section :deep(.news-grid-card img) {
    filter: saturate(1.03) contrast(1.01);
}

.home-gallery-news-section :deep(.news-list-card) {
    border-color: rgb(15 23 42 / 10%);
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
