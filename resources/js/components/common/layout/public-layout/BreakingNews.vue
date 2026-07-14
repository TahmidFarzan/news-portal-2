<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { fetchFromApi } from '@/composables/useSystemApi'
import { smartCacheKey, smartCacheTTL } from '@/composables/useApiSmartCache'

import { useTranslate } from '@/composables/useTranslate'

const { t } = useTranslate()

const {
    title = 'Breaking News',
    speed = 45,
} = defineProps({
    title: String,
    speed: Number,
})

const wrapperRef = ref(null)
const trackRef = ref(null)

const news = ref([])
const nextPageUrl = ref(null)

const loading = ref(false)
const fullyLoaded = ref(false)
const translateX = ref(0)
const isSmallScreen = ref(false)

let animationFrame = null
let lastTimestamp = 0

const hasNews = computed(() => news.value.length > 0)

const tickerSpeed = computed(() => {
    return isSmallScreen.value ? Math.max(speed * 0.7, 22) : speed
})

const displayNews = computed(() => {
    if (!news.value.length) {
        return []
    }

    return fullyLoaded.value
        ? [...news.value, ...news.value]
        : news.value
})

const normalizeResponse = (response) => {
    return {
        data: Array.isArray(response?.data) ? response.data : [],
        nextPageUrl: response?.next_page_url ?? null,
    }
}

const hasPublicUrl = (newsItem) => {
    return typeof newsItem?.public_url === 'string' && newsItem.public_url.trim() !== ''
}

const appendUniqueNews = (items = []) => {
    const existingIds = new Set(news.value.map((newsItem) => newsItem.id))

    const uniqueItems = items.filter((newsItem) => {
        return newsItem?.id && !existingIds.has(newsItem.id)
    })

    news.value.push(...uniqueItems)
}

const loadBreakingNews = async (url = null) => {
    if (loading.value || fullyLoaded.value) {
        return
    }

    loading.value = true

    try {
        const apiUrl = url ?? route('site.breaking-news')
        const response = await fetchFromApi(
            apiUrl,
            {},
            {
                key: `${smartCacheKey.API_CURSOR_PAGINATION}:${apiUrl}`,
                ttl: smartCacheTTL.SYSTEM_SHORT,
            }
        )
        const result = normalizeResponse(response)

        appendUniqueNews(result.data)

        nextPageUrl.value = result.nextPageUrl

        if (!result.nextPageUrl) {
            fullyLoaded.value = true
        }

        await nextTick()
    } catch (error) {
        console.error('Failed to load breaking news:', error)
    } finally {
        loading.value = false
    }
}

const shouldLoadNextPage = () => {
    if (!wrapperRef.value || !trackRef.value) {
        return false
    }

    if (!nextPageUrl.value || loading.value || fullyLoaded.value) {
        return false
    }

    const wrapperWidth = wrapperRef.value.offsetWidth
    const trackWidth = trackRef.value.scrollWidth

    return Math.abs(translateX.value) + wrapperWidth + 300 >= trackWidth
}

const animate = (timestamp) => {
    if (!lastTimestamp) {
        lastTimestamp = timestamp
    }

    const deltaTime = timestamp - lastTimestamp
    lastTimestamp = timestamp

    if (news.value.length) {
        translateX.value -= (tickerSpeed.value * deltaTime) / 1000
    }

    if (shouldLoadNextPage()) {
        loadBreakingNews(nextPageUrl.value)
    }

    if (fullyLoaded.value && trackRef.value) {
        const originalTrackWidth = trackRef.value.scrollWidth / 2

        if (originalTrackWidth > 0 && Math.abs(translateX.value) >= originalTrackWidth) {
            translateX.value = 0
        }
    }

    animationFrame = requestAnimationFrame(animate)
}

const updateScreenSize = () => {
    isSmallScreen.value = window.innerWidth < 640
    translateX.value = 0
    lastTimestamp = 0
}

onMounted(async () => {
    updateScreenSize()
    window.addEventListener('resize', updateScreenSize)

    await loadBreakingNews()

    animationFrame = requestAnimationFrame(animate)
})

onBeforeUnmount(() => {
    if (animationFrame) {
        cancelAnimationFrame(animationFrame)
    }

    window.removeEventListener('resize', updateScreenSize)
})
</script>

<template>
    <section v-if="hasNews"
        class="fixed bottom-0 left-0 right-0 z-[9999] overflow-hidden border-t border-gray-200 bg-white shadow-lg">
        <div class="mx-auto flex max-w-7xl flex-col overflow-hidden px-3 sm:h-11 sm:flex-row sm:items-center sm:px-4">
            <div
                class="flex h-9 w-full shrink-0 items-center justify-center bg-red-600 px-4 text-sm font-bold text-white sm:h-full sm:w-auto sm:justify-start sm:text-base">
                {{ title }}
            </div>

            <div class="flex min-w-0 flex-1 items-center gap-3 py-2 sm:h-full sm:py-0">
                <div ref="wrapperRef" class="min-w-0 flex-1 overflow-hidden px-1 text-gray-800 sm:px-4">
                    <div ref="trackRef" class="inline-flex items-center whitespace-nowrap will-change-transform"
                        :style="{ transform: `translateX(${translateX}px)` }">
                        <template v-for="(newsItem, index) in displayNews" :key="`${newsItem.id}-${index}`">
                            <a v-if="hasPublicUrl(newsItem)" :href="newsItem.public_url"
                                class="inline-flex items-center text-xs font-medium transition duration-200 hover:text-blue-600 hover:underline sm:text-sm md:text-base">
                                {{ newsItem.title }}
                            </a>

                            <span v-else
                                class="inline-flex cursor-default items-center text-xs font-medium sm:text-sm md:text-base">
                                {{ newsItem.title }}
                            </span>

                            <span class="mx-3 select-none text-gray-400 sm:mx-4">
                                |
                            </span>
                        </template>
                    </div>
                </div>

                <div v-if="loading && !fullyLoaded"
                    class="shrink-0 whitespace-nowrap text-[11px] text-gray-500 sm:text-xs">
                    {{ t('common.labels.loading') }}
                </div>
            </div>
        </div>
    </section>
</template>
