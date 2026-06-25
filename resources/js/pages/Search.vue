<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { Head, useForm, router as inertiaRouter } from '@inertiajs/vue3'

import Layout from '@/pages/layouts/PublicLayout.vue'
import List from '@/components/common/news/List.vue'
import SelectInfinityLoadingApi from '@/components/common/multi-select/SelectInfinityLoadingApi.vue'
import GoogleAdsence from '@/components/common/util/GoogleAdsence.vue'

import { useTranslate } from '@/composables/useTranslate'
import { fetchFromApi } from '@/composables/useSystemApi'
import { itemListFilterParameters } from '@/composables/useDataTable'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faFilter, faSpinner } from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faFilter, faSpinner)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const { news, language, page } = defineProps({
    news: {
        type: Object,
        required: true,
    },

    language: {
        type: Object,
        required: true,
    },

    page: {
        type: Object,
        required: true,
    },
})

const isFiltering = ref(false)

const filterForm = useForm({
    created_by_id: null,
    news_type_id: null,
    category_id: null,
    location_id: null,
    event_id: null,
    date: '',
    search: '',
})

const makeLanguageUrl = (routeName) => {
    if (!language?.id) {
        return route(routeName)
    }

    return route(routeName) + `?language_id=${language.id}`
}

const newsTypesApiUrl = computed(() => makeLanguageUrl('search.news-types'))
const categoryApiUrl = computed(() => makeLanguageUrl('search.category-tree'))
const locationApiUrl = computed(() => makeLanguageUrl('search.location-tree'))
const eventApiUrl = computed(() => makeLanguageUrl('search.events'))

const metaTitle = computed(() => {
    return page?.seo_title ?? page?.title ?? t('pages.search.labels.search')
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

const getFilterValue = (value) => {
    if (!value) {
        return ''
    }

    if (typeof value === 'object') {
        return value.id ?? value.slug ?? ''
    }

    return value
}

const applyFilter = () => {
    if (isFiltering.value) {
        return
    }

    const params = {
        created_by_id: getFilterValue(filterForm.created_by_id),
        news_type_id: getFilterValue(filterForm.news_type_id),
        category_id: getFilterValue(filterForm.category_id),
        location_id: getFilterValue(filterForm.location_id),
        event_id: getFilterValue(filterForm.event_id),
        date: filterForm.date,
        search: filterForm.search,
    }

    const cleanParams = itemListFilterParameters(params)

    isFiltering.value = true

    inertiaRouter.get(route('search'), cleanParams, {
        replace: true,
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            isFiltering.value = false
        },
    })
}

onMounted(async () => {
    const urlParams = new URLSearchParams(window.location.search)

    filterForm.news_type_id = urlParams.get('news_type_id') || null
    filterForm.category_id = urlParams.get('category_id') || null
    filterForm.location_id = urlParams.get('location_id') || null
    filterForm.event_id = urlParams.get('event_id') || null
    filterForm.date = urlParams.get('date') || ''
    filterForm.search = urlParams.get('search') || ''

    if (filterForm.news_type_id) {
        const rNewsType = await fetchFromApi(
            route('search.news-type', {
                slugOrId: filterForm.news_type_id,
            })
        )

        filterForm.news_type_id = rNewsType || null
    }

    if (filterForm.category_id) {
        const rCategory = await fetchFromApi(
            route('search.category', {
                slugOrId: filterForm.category_id,
            })
        )

        filterForm.category_id = rCategory || null
    }

    if (filterForm.location_id) {
        const rLocation = await fetchFromApi(
            route('search.location', {
                slugOrId: filterForm.location_id,
            })
        )

        filterForm.location_id = rLocation || null
    }

    if (filterForm.event_id) {
        const rEvent = await fetchFromApi(
            route('search.event', {
                slugOrId: filterForm.event_id,
            })
        )

        filterForm.event_id = rEvent || null
    }

    await nextTick()
})
</script>

<template>

    <Head :title="metaTitle">
        <link rel="canonical" :href="route('search')" />

        <meta v-if="metaTitle" name="title" :content="metaTitle" />

        <meta v-if="metaDescription" name="description" :content="metaDescription" />

        <meta v-if="metaKeywords" name="keywords" :content="metaKeywords" />
    </Head>

    <div class="space-y-6">
        <form class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm space-y-4" @submit.prevent="applyFilter">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <SelectInfinityLoadingApi :form="filterForm" fieldName="news_type_id"
                    :selectedItem="filterForm.news_type_id || null" :apiUrl="newsTypesApiUrl" :multiple="false"
                    :placeholder="t('pages.search.labels.news_type')" />

                <SelectInfinityLoadingApi :form="filterForm" fieldName="category_id"
                    selectedLabelKey="indentation_name" selectedValueKey="id"
                    :selectedItem="filterForm.category_id || null" apiLabelKey="indentation_name" apiValueKey="id"
                    :apiUrl="categoryApiUrl" :multiple="false" :placeholder="t('pages.search.labels.category')" />

                <SelectInfinityLoadingApi :form="filterForm" fieldName="location_id"
                    selectedLabelKey="indentation_name" selectedValueKey="id"
                    :selectedItem="filterForm.location_id || null" apiLabelKey="indentation_name" apiValueKey="id"
                    :apiUrl="locationApiUrl" :multiple="false" :placeholder="t('pages.search.labels.location')" />

                <SelectInfinityLoadingApi :form="filterForm" fieldName="event_id"
                    :selectedItem="filterForm.event_id || null" :apiUrl="eventApiUrl" :multiple="false"
                    :placeholder="t('pages.search.labels.event')" />

                <input v-model="filterForm.date" type="date" :aria-label="t('pages.search.labels.date')"
                    class="w-full rounded-md border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />

                <input v-model="filterForm.search" type="search" :placeholder="t('pages.search.news.index.search_placeholder')"
                    class="w-full rounded-md border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>

            <div class="flex justify-end">
                <button type="submit" :disabled="isFiltering"
                    class="flex items-center gap-2 rounded-md bg-blue-600 px-5 py-2 text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                    <FontAwesomeIcon v-if="isFiltering" icon="spinner" spin />

                    <FontAwesomeIcon v-else icon="filter" />

                    {{ isFiltering ? t('pages.search.labels.loading') : t('pages.search.categories.index.apply_filter') }}
                </button>
            </div>
        </form>

        <GoogleAdsence v-if="showGoogleAd" />

        <List :news="news" pagination-type="Cursor" />
    </div>
</template>
