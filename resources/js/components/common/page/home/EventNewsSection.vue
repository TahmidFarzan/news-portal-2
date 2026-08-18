<script setup>
import { computed, ref, watch } from 'vue'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faRightLong } from '@fortawesome/free-solid-svg-icons'

import { useTranslate } from '@/composables/useTranslate'
import { fetchFromApi } from '@/composables/useApiClient'
import { apiCacheKey, apiCacheTTL } from '@/composables/useApiCache'

import GridCard from '@/components/common/news/GridCard.vue'
import ListCard from '@/components/common/news/ListCard.vue'

FontAwesomeLibrary.add(faRightLong)

const { t } = useTranslate()

const {
    events,
    currentLanguage,
    eager,
} = defineProps({
    events: {
        type: [Array, Object],
        default: () => [],
    },
    currentLanguage: {
        type: Object,
        required: true,
    },
    eager: {
        type: Boolean,
        default: false,
    },
})

const eventNews = ref({})
const loadingEvents = ref({})

const normalizeEvents = (events) => {
    if (Array.isArray(events)) {
        return events
    }

    return events?.data ?? []
}

const getEventImageUrl = (event, type = 'desktop') => {
    const image = type === 'mobile'
        ? event?.mobile_banner_image
        : event?.desktop_banner_image

    return image?.preview_url ?? image?.original_url ?? null
}

const eventItems = computed(() => {
    return normalizeEvents(events).filter((event) => {
        return getEventImageUrl(event, 'mobile') || getEventImageUrl(event, 'desktop')
    })
})

const getNewsItems = (news) => {
    if (Array.isArray(news)) {
        return news
    }

    return news?.data ?? []
}

const getEventNewsApiUrl = (event) => {
    return currentLanguage?.is_default
        ? route('home.event-news', {
            slug: event.slug,
        })
        : route('localized.home.event-news', {
            languageCode: currentLanguage?.code,
            slug: event.slug,
        })
}

const loadEventNews = async (event) => {
    if (
        !event?.slug ||
        (!currentLanguage?.id_default && !currentLanguage?.code) ||
        eventNews.value[event.slug] ||
        loadingEvents.value[event.slug]
    ) {
        return
    }

    loadingEvents.value[event.slug] = true

    try {
        const apiUrl = getEventNewsApiUrl(event)

        const response = await fetchFromApi(
            apiUrl,
            {},
            {
                key: `${apiCacheKey.API_HOME_PAGE}:${apiUrl}`,
                ttl: apiCacheTTL.HOME_PAGE,
            }
        )

        eventNews.value[event.slug] = getNewsItems(response).slice(0, 5)
    } catch (error) {
        eventNews.value[event.slug] = []
    } finally {
        loadingEvents.value[event.slug] = false
    }
}

watch(
    () => [
        eventItems.value,
        currentLanguage?.code,
        currentLanguage?.id_default,
    ],
    ([items]) => {
        eventNews.value = {}
        loadingEvents.value = {}

        items.forEach(loadEventNews)
    },
    {
        immediate: true,
        deep: true,
    }
)
</script>

<template>
    <div v-if="eventItems.length" class="event-news-section space-y-6 rounded-2xl border bg-white p-3">
        <div v-for="(event, index) in eventItems" :key="event?.id || event?.slug || index"
            class="event-panel overflow-hidden rounded-2xl border p-3">
            <img :src="getEventImageUrl(event, 'mobile') || getEventImageUrl(event, 'desktop')" :alt="event?.name || ''"
                class="event-banner block h-auto w-full rounded-2xl object-cover md:hidden"
                :loading="eager && index === 0 ? undefined : 'lazy'" />

            <img :src="getEventImageUrl(event, 'desktop') || getEventImageUrl(event, 'mobile')" :alt="event?.name || ''"
                class="event-banner hidden h-auto w-full rounded-2xl object-cover md:block"
                :loading="eager && index === 0 ? undefined : 'lazy'"
                :fetchpriority="eager && index === 0 ? 'high' : undefined" />

            <div v-if="eventNews[event.slug] === undefined || eventNews[event.slug]?.length"
                class="event-news-content mt-4">
                <template v-if="eventNews[event.slug]?.length">
                    <div class="grid grid-cols-1 gap-3 md:hidden">
                        <div class="event-news-card">
                            <GridCard :news="eventNews[event.slug][0]" :hideCategory="true" :hideEvent="true"
                                :hideLocation="true" :hideBrief="true" :isCompact="false" :useFullHeight="true" />
                        </div>

                        <div v-for="(newsItem, newsIndex) in eventNews[event.slug].slice(1)"
                            :key="newsItem?.id || newsItem?.slug || newsIndex" class="event-news-card">
                            <ListCard :news="newsItem" :hideSubtitle="true" :hideBrief="true" :hideCategory="true"
                                :hideEvent="true" :hideLocation="true" :hideFeatureImage="true" :isCompact="true" />
                        </div>
                    </div>

                    <div class="hidden md:grid md:grid-cols-2 md:items-stretch md:gap-4 lg:hidden">
                        <div class="event-news-card h-full">
                            <GridCard :news="eventNews[event.slug][0]" :hideCategory="true" :hideEvent="true"
                                :hideLocation="true" :hideBrief="true" :isCompact="false" :useFullHeight="true" />
                        </div>

                        <div class="grid h-full grid-rows-4 gap-3">
                            <div v-for="(newsItem, newsIndex) in eventNews[event.slug].slice(1)"
                                :key="newsItem?.id || newsItem?.slug || newsIndex" class="event-news-card h-full">
                                <ListCard :news="newsItem" :hideSubtitle="true" :hideBrief="true" :hideCategory="true"
                                    :hideEvent="true" :hideLocation="true" :hideFeatureImage="true" :isCompact="true" />
                            </div>
                        </div>
                    </div>

                    <div class="hidden lg:grid lg:grid-cols-5 lg:items-stretch lg:gap-4">
                        <div v-for="(newsItem, newsIndex) in eventNews[event.slug]"
                            :key="newsItem?.id || newsItem?.slug || newsIndex" class="event-news-card h-full">
                            <GridCard :news="newsItem" :hideCategory="true" :hideEvent="true" :hideLocation="true"
                                :hideBrief="true" :isCompact="true" :useFullHeight="true" />
                        </div>
                    </div>
                </template>

                <template v-else>
                    <div class="grid grid-cols-1 gap-3 md:hidden">
                        <div class="event-news-card">
                            <div class="aspect-[16/9] w-full bg-gray-200 animate-pulse"></div>
                            <div class="space-y-1.5 p-2">
                                <div class="h-3 w-3/4 rounded bg-gray-200 animate-pulse"></div>
                                <div class="h-2.5 w-1/2 rounded bg-gray-200 animate-pulse"></div>
                            </div>
                        </div>
                        <div v-for="n in 4" :key="'esk-m-'+n" class="event-news-card">
                            <div class="flex items-center gap-3 p-2">
                                <div class="flex-1 space-y-1.5">
                                    <div class="h-3 w-3/4 rounded bg-gray-200 animate-pulse"></div>
                                    <div class="h-2.5 w-1/2 rounded bg-gray-200 animate-pulse"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="hidden md:grid md:grid-cols-2 md:items-stretch md:gap-4 lg:hidden">
                        <div class="event-news-card h-full">
                            <div class="aspect-[16/9] w-full bg-gray-200 animate-pulse"></div>
                            <div class="space-y-1.5 p-2">
                                <div class="h-3 w-3/4 rounded bg-gray-200 animate-pulse"></div>
                                <div class="h-2.5 w-1/2 rounded bg-gray-200 animate-pulse"></div>
                            </div>
                        </div>
                        <div class="grid h-full grid-rows-4 gap-3">
                            <div v-for="n in 4" :key="'esk-t-'+n" class="event-news-card h-full">
                                <div class="flex items-center gap-3 p-2">
                                    <div class="flex-1 space-y-1.5">
                                        <div class="h-3 w-3/4 rounded bg-gray-200 animate-pulse"></div>
                                        <div class="h-2.5 w-1/2 rounded bg-gray-200 animate-pulse"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="hidden lg:grid lg:grid-cols-5 lg:items-stretch lg:gap-4">
                        <div v-for="n in 5" :key="'esk-d-'+n" class="event-news-card h-full">
                            <div class="aspect-[16/9] w-full bg-gray-200 animate-pulse"></div>
                            <div class="space-y-1.5 p-2">
                                <div class="h-3 w-3/4 rounded bg-gray-200 animate-pulse"></div>
                                <div class="h-2.5 w-1/2 rounded bg-gray-200 animate-pulse"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="mt-4 flex justify-center">
                <a :href="event?.public_url"
                    class="event-link group inline-flex items-center justify-center gap-2 rounded-full px-5 py-2 text-sm font-semibold text-white transition-all duration-300 hover:-translate-y-0.5">
                    <FontAwesomeIcon icon="right-long"
                        class="transition-transform duration-300 group-hover:translate-x-1" />

                    {{ t('pages.components.eventBanners.labels.readMore') }}
                </a>
            </div>
        </div>
    </div>
</template>

<style scoped>
.event-news-section {
    border-color: var(--news-border-primary-soft);
    background: var(--news-event-gradient);
    box-shadow: var(--news-shadow-soft);
}

.event-panel {
    border-color: var(--news-border-soft);
    background: var(--news-surface);
}

.event-banner {
    aspect-ratio: 40 / 9;
    border: var(--news-border-white-soft-line);
    box-shadow: var(--news-shadow-media);
}

.event-news-content {
    min-width: 0;
}

.event-news-card {
    min-width: 0;
    overflow: hidden;
    border-radius: 1rem;
    background: white;
    box-shadow: 0 1px 2px rgb(15 23 42 / 0.05);
    ring: 1px;
}

.event-news-card :deep(> *) {
    height: 100%;
}

@media (min-width: 768px) {
    .event-banner {
        aspect-ratio: 130 / 9;
    }
}

.event-link {
    background: var(--news-button-primary-gradient);
    box-shadow: var(--news-shadow-primary);
}

.event-link:hover {
    box-shadow: var(--news-shadow-primary-hover);
}
</style>
