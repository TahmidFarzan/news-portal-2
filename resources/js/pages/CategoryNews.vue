<script setup>
import { computed, inject } from 'vue'
import { Head } from '@inertiajs/vue3'

import Layout from '@/pages/layouts/PublicLayout.vue'
import List from '@/components/common/news/List.vue'
import PageSidebar from '@/components/common/page/PageSidebar.vue'
import GridCard from '@/components/common/news/GridCard.vue'
import CategoryHasLocationSection from '@/components/common/page/CategoryHasLocationSection.vue'
import GoogleAdsence from '@/components/common/util/GoogleAdsence.vue'

import { useTranslate } from '@/composables/useTranslate'
import { adTypes, adPositions } from '@/composables/useGoogleAdsence'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'

import {
    faFolder,
} from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(
    faFolder,
)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const {
    category,
    news,
    recentNews,
    popularNews,
    pageSectionNews,
} = defineProps({
    category: {
        type: Object,
        required: true,
    },
    news: {
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
    pageSectionNews: {
        type: [Array, Object],
        required: true,
    },
})

const metaTitle = computed(() => {
    return category?.seo_title || category?.name || ''
})

const metaDescription = computed(() => {
    return category?.seo_brief || category?.brief || ''
})

const metaKeywords = computed(() => {
    if (Array.isArray(category?.seo_keywords)) {
        return category.seo_keywords.join(', ')
    }

    return category?.seo_keywords || ''
})

const hasBrief = computed(() => {
    return Boolean(category?.brief)
})

const pageSectionNewsItems = computed(() => {
    const items = Array.isArray(pageSectionNews)
        ? pageSectionNews
        : Array.isArray(pageSectionNews?.data)
            ? pageSectionNews.data
            : []

    return items.slice(0, 5)
})

const hasPageSectionNews = computed(() => {
    return pageSectionNewsItems.value.length > 0
})

const firstGridPageSectionNews = computed(() => {
    return pageSectionNewsItems.value.slice(0, 2)
})

const secondGridPageSectionNews = computed(() => {
    return pageSectionNewsItems.value.slice(2, 5)
})

const showGoogleAd = inject('showGoogleAd', computed(() => false))

const getFirstGridColumnClass = (index) => {
    if (firstGridPageSectionNews.value.length === 1) {
        return 'col-span-1 sm:col-span-2 md:col-span-12'
    }

    return index === 0
        ? 'col-span-1 sm:col-span-1 md:col-span-7'
        : 'col-span-1 sm:col-span-1 md:col-span-5'
}

const getSecondGridColumnClass = (index) => {
    const total = secondGridPageSectionNews.value.length

    if (total === 1) {
        return 'col-span-1 sm:col-span-2 md:col-span-12'
    }

    if (total === 2) {
        return 'col-span-1 sm:col-span-1 md:col-span-6'
    }

    return 'col-span-1 sm:col-span-1 md:col-span-4'
}
</script>

<template>

    <Head :title="category?.name || t('common.labels.category')">
        <link v-if="category?.public_url" rel="canonical" :href="category.public_url" />

        <meta v-if="metaTitle" name="title" :content="metaTitle" />

        <meta v-if="metaDescription" name="description" :content="metaDescription" />

        <meta v-if="metaKeywords" name="keywords" :content="metaKeywords" />
    </Head>

    <div class="entity-page category-page space-y-6">
        <section class="grid grid-cols-1 items-center gap-5 md:grid-cols-12">
            <div class="md:col-span-3 lg:col-span-2">
                <div class="flex h-28 w-28 items-center justify-center rounded-2xl bg-blue-50 p-4 sm:h-32 sm:w-32">
                    <img :src="'/uploads/images/logo/category.png'"
                        :alt="category?.name || t('pages.categoryNews.categories.details.categoryImageAlt')"
                        class="h-full w-full object-contain" loading="lazy" />
                </div>
            </div>

            <div class="space-y-2 md:col-span-9 lg:col-span-10">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">
                    {{ t('common.labels.category') }}
                </p>

                <div v-if="category?.parent"
                    class="pointer-events-auto flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                    <a :href="category.parent.public_url" :title="t('common.labels.category')"
                        class="inline-flex min-w-0 items-center gap-1 transition duration-300 hover:text-red-600"
                        @click.stop>
                        <FontAwesomeIcon icon="folder" class="shrink-0" />

                        <span class="truncate">
                            {{ category.parent.name }}
                        </span>
                    </a>
                </div>

                <h1 class="text-2xl font-bold tracking-tight text-gray-950 sm:text-3xl">
                    {{ category?.name }}
                </h1>

                <p v-if="hasBrief" class="max-w-3xl text-sm leading-7 text-gray-600 sm:text-base">
                    {{ category.brief }}
                </p>

                <div v-if="category?.has_descendants && category?.children?.length"
                    class="flex flex-wrap items-center gap-2 pt-1">
                    <a v-for="child in category.children" :key="child.id || child.slug" :href="child.public_url"
                        :title="child.name"
                        class="inline-flex min-w-0 items-center gap-1 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-600 shadow-sm transition duration-300 hover:border-red-200 hover:bg-red-50 hover:text-red-600">
                        <FontAwesomeIcon icon="folder" class="shrink-0" />

                        <span class="truncate">
                            {{ child.name }}
                        </span>
                    </a>
                </div>
            </div>
        </section>

        <GoogleAdsence v-if="showGoogleAd" class="mt-4 mb-4" :type="adTypes.SECTION" :position="adPositions.BETWEEN"/>

        <section class="grid grid-cols-1 items-start gap-5 md:grid-cols-12">
            <div class="space-y-4 md:col-span-8 lg:col-span-8">
                <section v-if="hasPageSectionNews" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-12">
                        <div v-for="(perPageSectionNews, index) in firstGridPageSectionNews"
                            :key="perPageSectionNews.id || perPageSectionNews.slug || index"
                            :class="getFirstGridColumnClass(index)">
                            <GridCard :news="perPageSectionNews" />
                        </div>
                    </div>

                    <div v-if="secondGridPageSectionNews.length"
                        class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-12">
                        <div v-for="(perPageSectionNews, index) in secondGridPageSectionNews"
                            :key="perPageSectionNews.id || perPageSectionNews.slug || index"
                            :class="getSecondGridColumnClass(index)">
                            <GridCard :news="perPageSectionNews" />
                        </div>
                    </div>
                </section>
            </div>

            <div class="space-y-2 md:col-span-4 lg:col-span-4">
                <CategoryHasLocationSection :category="category" />

                <PageSidebar :recentNews="recentNews" :popularNews="popularNews" />

                <GoogleAdsence v-if="showGoogleAd" :type="adTypes.SIDEBAR" :position="adPositions.BOTTOM"
                    class="mt-4" />
            </div>
        </section>

        <div class="border-t border-gray-200"></div>

        <GoogleAdsence v-if="showGoogleAd" class="mt-4 mb-4" :type="adTypes.SECTION" :position="adPositions.BETWEEN"/>

        <List :news="news" pagination-type="Cursor" />
    </div>
</template>

<style scoped>
.entity-page>section:first-child {
    border: var(--news-border-default);
    border-radius: var(--news-radius);
    background: var(--news-hero-danger-gradient);
    padding: var(--news-hero-padding);
    box-shadow: var(--news-shadow-soft);
}

.category-page>section:nth-of-type(2) {
    align-items: stretch;
}
</style>
