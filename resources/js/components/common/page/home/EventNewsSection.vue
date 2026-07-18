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
    isDefaultLanguage = false,
} = defineProps({
    events: {
        type: [Array, Object],
        default: () => [],
    },
    currentLanguage: {
        type: Object,
        required: true,
    },
    isDefaultLanguage: {
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
    if (isDefaultLanguage) {
        return route('home.event-news', {
            slug: event.slug,
        })
    }

    return route('localized.home.event-news', {
        languageCode: currentLanguage.code,
        slug: event.slug,
    })
}

const loadEventNews = async (event) => {
    if (
        !event?.slug ||
        (!isDefaultLanguage && !currentLanguage?.code) ||
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
        isDefaultLanguage,
    ],
    ([items]) => {
        eventNews.value = {}
        loadingEvents.value = {}
        items.forEach(loadEventNews)
    },
    {
        immediate: true,
        deep: true,
    },
)
</script>

<template>
    <div v-if="eventItems.length" class="event-news-section space-y-6 rounded-2xl border border-slate-200 bg-white p-3">
        <div v-for="(event, index) in eventItems" :key="event?.id || event?.slug || index"
            class="event-panel overflow-hidden rounded-2xl border border-slate-100 bg-slate-50 p-3">
            <img :src="getEventImageUrl(event, 'mobile') || getEventImageUrl(event, 'desktop')" :alt="event?.name || ''"
                class="event-banner block h-auto w-full rounded-2xl object-cover md:hidden" loading="lazy" />

            <img :src="getEventImageUrl(event, 'desktop') || getEventImageUrl(event, 'mobile')" :alt="event?.name || ''"
                class="event-banner hidden h-auto w-full rounded-2xl object-cover md:block" loading="lazy" />

            <div v-if="eventNews[event.slug]?.length" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-12">
                <div class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-100 md:col-span-5">
                    <GridCard :news="eventNews[event.slug][0]" :hideCategory="true" :hideEvent="true"
                        :hideLocation="true" :hideBrief="true" :isCompact="false" :useFullHeight="true" />
                </div>

                <div v-if="eventNews[event.slug].length > 1" class="md:col-span-7">
                    <div class="grid grid-cols-1 gap-3 md:hidden">
                        <div v-for="(newsItem, newsIndex) in eventNews[event.slug].slice(1)"
                            :key="newsItem?.id || newsItem?.slug || newsIndex"
                            class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-100">
                            <ListCard :news="newsItem" :hideSubtitle="true" :hideBrief="true" :hideCategory="true"
                                :hideEvent="true" :hideLocation="true" :hideFeatureImage="true" :isCompact="true" />
                        </div>
                    </div>

                    <div class="hidden grid-cols-2 gap-4 md:grid">
                        <div v-for="(newsItem, newsIndex) in eventNews[event.slug].slice(1)"
                            :key="newsItem?.id || newsItem?.slug || newsIndex"
                            class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-100">
                            <GridCard :news="newsItem" :hideCategory="true" :hideEvent="true" :hideLocation="true"
                                :hideBrief="true" :isCompact="true" :useFullHeight="true" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex justify-center">
                <a :href="event?.public_url || '#'"
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
    border: var(--news-border-white-soft-line);
    box-shadow: var(--news-shadow-media);
}

.event-link {
    background: var(--news-button-primary-gradient);
    box-shadow: var(--news-shadow-primary);
}

.event-link:hover {
    box-shadow: var(--news-shadow-primary-hover);
}
</style>
