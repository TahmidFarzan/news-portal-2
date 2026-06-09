<script setup>
import { computed, ref, watch } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'

import Layout from '@/pages/layouts/PublicLayout.vue'
import List from '@/components/common/news/List.vue'
import GridCard from '@/components/common/news/GridCard.vue'
import MultiSelectInfinityLoadingApi from '@/components/common/multi-select/InfinityLoadingApi.vue'

import { fetchFromApi } from '@/composables/useSystemApi'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'

import {
    faFolder,
    faLocationDot,
    faMagnifyingGlass,
} from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(
    faFolder,
    faLocationDot,
    faMagnifyingGlass,
)

defineOptions({ layout: Layout })

const {
    category,
    news,
    pageSectionNews,
    categoryLocationMaxDepthAndLevel,
} = defineProps({
    category: {
        type: Object,
        required: true,
    },

    news: {
        type: Object,
        required: true,
    },

    pageSectionNews: {
        type: [Array, Object],
        required: true,
    },

    categoryLocationMaxDepthAndLevel: {
        type: Object,
        required: true,
    },
})

const getLocationFieldName = (index) => {
    return `location_level_${index + 1}`
}

const locationMaxDepth = computed(() => {
    return categoryLocationMaxDepthAndLevel?.max_depth
        ?? categoryLocationMaxDepthAndLevel?.depth
        ?? null
})

const locationMaxLevel = computed(() => {
    return Number(
        categoryLocationMaxDepthAndLevel?.max_level
        ?? categoryLocationMaxDepthAndLevel?.level
        ?? 0
    )
})

const getInitialLocationFields = () => {
    const fields = {}

    for (let index = 0; index < locationMaxLevel.value; index++) {
        fields[getLocationFieldName(index)] = null
    }

    return fields
}

const searchLocationForm = useForm(getInitialLocationFields())

const isSearchingLocation = ref(false)

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

const hasLocationFilter = computed(() => {
    return Boolean(
        category?.has_location
        && locationMaxDepth.value !== null
        && locationMaxLevel.value > 0
    )
})

const locationLevels = computed(() => {
    if (!hasLocationFilter.value) {
        return []
    }

    return Array.from({ length: locationMaxLevel.value }, (_, index) => index)
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

    if (index === 2) {
        return 'col-span-1 sm:col-span-2 md:col-span-4'
    }

    return 'col-span-1 sm:col-span-1 md:col-span-4'
}

const baseLocationsApiUrl = computed(() => {
    const params = new URLSearchParams()

    if (category?.id) {
        params.append('category_id', category.id)
    }

    if (category?.language_id) {
        params.append('language_id', category.language_id)
    }

    const queryString = params.toString()

    return queryString
        ? `${route('search.locations')}?${queryString}`
        : route('search.locations')
})

const appendQueryParam = (url, key, value) => {
    const separator = url.includes('?') ? '&' : '?'

    return `${url}${separator}${key}=${encodeURIComponent(value)}`
}

const getSelectedLocationId = (index) => {
    const fieldName = getLocationFieldName(index)
    const selectedLocation = searchLocationForm[fieldName]

    if (!selectedLocation) {
        return null
    }

    if (Array.isArray(selectedLocation)) {
        const firstSelectedLocation = selectedLocation[0]

        if (!firstSelectedLocation) {
            return null
        }

        if (typeof firstSelectedLocation === 'object') {
            return firstSelectedLocation.id
                ?? firstSelectedLocation.value
                ?? firstSelectedLocation.slug
                ?? null
        }

        return firstSelectedLocation
    }

    if (typeof selectedLocation === 'object') {
        return selectedLocation.id
            ?? selectedLocation.value
            ?? selectedLocation.slug
            ?? null
    }

    return selectedLocation
}

const getLocationApiUrl = (index) => {
    if (index === 0) {
        return appendQueryParam(baseLocationsApiUrl.value, 'only_main', true)
    }

    const parentId = getSelectedLocationId(index - 1)

    if (!parentId) {
        return ''
    }

    return appendQueryParam(baseLocationsApiUrl.value, 'parent_id', parentId)
}

const selectedLastLoopLocationItem = computed(() => {
    for (let index = locationMaxLevel.value - 1; index >= 0; index--) {
        const selectedLocationId = getSelectedLocationId(index)

        if (selectedLocationId) {
            return selectedLocationId
        }
    }

    return null
})

const canSearchLocation = computed(() => {
    return Boolean(selectedLastLoopLocationItem.value) && !isSearchingLocation.value
})

const searchByLocation = async () => {
    if (!selectedLastLoopLocationItem.value) {
        return
    }

    isSearchingLocation.value = true

    try {
        const response = await fetchFromApi(
            route('search.location', {
                slugOrId: selectedLastLoopLocationItem.value,
            })
        )

        const location = response?.data ?? response

        if (!location?.public_url) {
            return
        }

        router.visit(location.public_url)
    } finally {
        isSearchingLocation.value = false
    }
}

watch(
    () => locationMaxLevel.value,
    (level) => {
        for (let index = 0; index < level; index++) {
            const fieldName = getLocationFieldName(index)

            if (!(fieldName in searchLocationForm)) {
                searchLocationForm[fieldName] = null
            }
        }
    },
    { immediate: true }
)

watch(
    () => locationLevels.value.map((index) => getSelectedLocationId(index)),
    (newValues, oldValues = []) => {
        const changedIndex = newValues.findIndex((value, index) => {
            return value !== oldValues[index]
        })

        if (changedIndex === -1) {
            return
        }

        for (let index = changedIndex + 1; index < locationMaxLevel.value; index++) {
            searchLocationForm[getLocationFieldName(index)] = null
        }
    }
)
</script>

<template>

    <Head :title="category?.name || 'Category'">
        <link v-if="category?.public_url" rel="canonical" :href="category.public_url" />

        <meta v-if="metaTitle" name="title" :content="metaTitle" />

        <meta v-if="metaDescription" name="description" :content="metaDescription" />

        <meta v-if="metaKeywords" name="keywords" :content="metaKeywords" />
    </Head>

    <div class="space-y-6">
        <section class="grid grid-cols-1 items-center gap-5 md:grid-cols-12">
            <div class="md:col-span-3 lg:col-span-2">
                <div class="flex h-28 w-28 items-center justify-center rounded-2xl bg-blue-50 p-4 sm:h-32 sm:w-32">
                    <img :src="'/uploads/images/logo/category.png'" :alt="category?.name || 'Category image'"
                        class="h-full w-full object-contain" loading="lazy" />
                </div>
            </div>

            <div class="space-y-2 md:col-span-9 lg:col-span-10">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">
                    Category
                </p>

                <div v-if="category?.parent"
                    class="pointer-events-auto flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                    <a :href="category.parent.public_url" title="Category"
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

        <section class="grid grid-cols-1 items-start gap-5 md:grid-cols-12">
            <div class="space-y-4 md:col-span-9 lg:col-span-9">
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

            <div class="md:col-span-3 lg:col-span-3">
                <div v-if="hasLocationFilter"
                    class="space-y-3 rounded-2xl border border-gray-200 bg-white p-3 shadow-sm">
                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-gray-700">
                        <FontAwesomeIcon icon="location-dot" class="text-blue-600" />

                        <span>Location</span>
                    </div>

                    <div v-for="levelIndex in locationLevels" :key="levelIndex" class="space-y-1">
                        <MultiSelectInfinityLoadingApi v-if="levelIndex === 0 || getSelectedLocationId(levelIndex - 1)"
                            :form="searchLocationForm" :fieldName="getLocationFieldName(levelIndex)"
                            :selectedItem="searchLocationForm[getLocationFieldName(levelIndex)]"
                            :apiUrl="getLocationApiUrl(levelIndex)"
                            :error="searchLocationForm.errors?.[getLocationFieldName(levelIndex)]" :multiple="false"
                            placeholder="Select location" />
                    </div>

                    <button type="button"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition duration-300 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="!canSearchLocation" @click="searchByLocation">
                        <FontAwesomeIcon icon="magnifying-glass" />

                        <span>
                            {{ isSearchingLocation ? 'Searching...' : 'Search' }}
                        </span>
                    </button>
                </div>
            </div>
        </section>

        <div class="border-t border-gray-200"></div>

        <List :news="news" pagination-type="Cursor" />
    </div>
</template>
