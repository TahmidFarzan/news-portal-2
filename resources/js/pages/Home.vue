<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'

import Layout from '@/pages/layouts/PublicLayout.vue'
import { useTranslate } from '@/composables/useTranslate'
import ListCard from '@/components/common/news/ListCard.vue'
import GridCard from '@/components/common/news/GridCard.vue'
import EventNewsSection from '@/components/common/page/EventNewsSection.vue'
import NewsTypeSliderSection from '@/components/common/page/NewsTypeSliderSection.vue'
import NewsTypeGallerySection from '@/components/common/page/NewsTypeGallerySection.vue'

import { fetchFromApi } from '@/composables/useSystemApi'

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

    <section class="min-h-screen">
        <div v-if="topEvents">
            <EventNewsSection :events="topEvents" class="mb-4" />
        </div>

        <div class="rounded-2xl border border-slate-100 p-2">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                <main class="lg:col-span-8">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-12 lg:grid-cols-12">
                        <div class="space-y-3 md:col-span-6 lg:col-span-6">
                            <GridCard v-if="primaryLeadNews" :news="primaryLeadNews" :hideCategory="true"
                                :hideEvent="true" :hideLocation="true" :hideBrief="true" :isCompact="true"
                                :useFullHeight="false" />

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
                                <!--
                                    when hideFeatureImage = false when device smaller then sm
                                -->
                            </div>
                        </div>
                    </div>

                    <div v-if="extraLeadNews.length" class="mt-4 border-t border-slate-100 pt-4">
                        <div class="grid grid-cols-1 gap-3 md:hidden">
                            <ListCard v-for="(perNews, index) in extraLeadNews"
                                :key="perNews?.id || perNews?.slug || index" :news="perNews" :hideCategory="true"
                                :hideEvent="true" :hideLocation="true" :hideBrief="true" :isCompact="true" />
                        </div>

                        <div class="hidden gap-3 md:grid md:grid-cols-2 lg:grid-cols-4">
                            <GridCard v-for="(perNews, index) in extraLeadNews"
                                :key="perNews?.id || perNews?.slug || index" :news="perNews" :hideCategory="true"
                                :hideEvent="true" :hideLocation="true" :hideBrief="true" />
                        </div>
                    </div>
                </main>

                <aside class="lg:col-span-4">
                    <div v-if="recentNewsItems.length"
                        class="flex h-[500px] flex-col rounded-2xl border border-gray-200 p-2">
                        <div class="recent-news flex shrink-0 items-center gap-2">
                            <h2 class="text-xl font-bold text-gray-950">
                                {{ t('components.common.news.recent_news_list.labels.recent_news') }}
                            </h2>
                        </div>

                        <div class="thin-modern-scrollbar mt-2 min-h-0 flex-1 overflow-y-auto overscroll-contain p-2">
                            <div class="grid grid-cols-1 gap-3">
                                <ListCard v-for="(perNews, index) in recentNewsItems"
                                    :key="perNews?.id || perNews?.slug || index" :news="perNews" :hideSubtitle="true"
                                    :hideBrief="true" :hideCategory="true" :hideEvent="true" :hideLocation="true"
                                    :hideFeatureImage="true" :isCompact="true" />
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>

        <div v-if="bottomEvents">
            <EventNewsSection :events="bottomEvents" class="mb-4" />
        </div>

        <NewsTypeSliderSection class="mt-4" />

        <NewsTypeGallerySection class="mt-4" />
    </section>
</template>
