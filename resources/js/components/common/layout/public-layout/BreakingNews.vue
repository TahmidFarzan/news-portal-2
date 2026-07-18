<script setup>
import {
    ref,
    computed,
    onMounted,
    onBeforeUnmount,
    nextTick,
    watch,
} from 'vue'

import { fetchFromApi } from '@/composables/useApiClient'
import { apiCacheKey, apiCacheTTL } from '@/composables/useApiCache'
import { useTranslate } from '@/composables/useTranslate'

const { t } = useTranslate()

const {
    title = 'Breaking News',
    speed = 45,
    isDefaultLanguage = false,
    currentLanguage,
} = defineProps({
    title: {
        type: String,
        default: 'Breaking News',
    },
    speed: {
        type: Number,
        default: 45,
    },
    isDefaultLanguage: {
        type: Boolean,
        default: false,
    },
    currentLanguage: {
        type: Object,
        required: true,
    },
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
let resizeTimer = null
let requestId = 0

const hasNews = computed(() => {
    return news.value.length > 0
})

const tickerSpeed = computed(() => {
    const normalizedSpeed = Number(speed) || 45

    return isSmallScreen.value
        ? Math.max(normalizedSpeed * 0.7, 22)
        : normalizedSpeed
})

const displayNews = computed(() => {
    if (!news.value.length) {
        return []
    }

    if (!fullyLoaded.value) {
        return news.value
    }

    return [
        ...news.value,
        ...news.value,
    ]
})

const getInitialApiUrl = () => {
    if (isDefaultLanguage) {
        return route('site.breaking-news')
    }

    const languageCode = currentLanguage?.code

    if (!languageCode) {
        throw new Error('Current language code is required.')
    }

    return route('localized.site.breaking-news', {
        languageCode: languageCode,
    })
}

const normalizeResponse = (response) => {
    const source =
        response?.data &&
            !Array.isArray(response.data) &&
            (
                Array.isArray(response.data.data) ||
                'next_page_url' in response.data
            )
            ? response.data
            : response

    const items =
        Array.isArray(source?.data)
            ? source.data
            : Array.isArray(source?.items)
                ? source.items
                : []

    return {
        data: items,
        nextPageUrl:
            source?.next_page_url ??
            source?.nextPageUrl ??
            null,
    }
}

const hasPublicUrl = (newsItem) => {
    return (
        typeof newsItem?.public_url === 'string' &&
        newsItem.public_url.trim() !== ''
    )
}

const getNewsUniqueKey = (newsItem) => {
    return (
        newsItem?.id ??
        newsItem?.public_url ??
        newsItem?.slug ??
        newsItem?.title ??
        null
    )
}

const appendUniqueNews = (items = []) => {
    if (!Array.isArray(items) || !items.length) {
        return
    }

    const existingKeys = new Set(
        news.value
            .map(getNewsUniqueKey)
            .filter(Boolean)
    )

    const uniqueItems = items.filter((newsItem) => {
        const key = getNewsUniqueKey(newsItem)

        if (!key || existingKeys.has(key)) {
            return false
        }

        existingKeys.add(key)

        return true
    })

    news.value.push(...uniqueItems)
}

const loadBreakingNews = async (url = null) => {
    if (loading.value || fullyLoaded.value) {
        return
    }

    const currentRequestId = ++requestId

    loading.value = true

    try {
        const apiUrl = url || getInitialApiUrl()

        const response = await fetchFromApi(
            apiUrl,
            {},
            {
                key: `${apiCacheKey.API_CURSOR_PAGINATION}:${apiUrl}`,
                ttl: apiCacheTTL.SYSTEM_SHORT,
            }
        )

        if (currentRequestId !== requestId) {
            return
        }

        const result = normalizeResponse(response)

        appendUniqueNews(result.data)

        nextPageUrl.value = result.nextPageUrl

        if (!result.nextPageUrl) {
            fullyLoaded.value = true
        }

        await nextTick()
    } catch (error) {
        if (currentRequestId === requestId) {
            console.error(
                'Failed to load breaking news:',
                error
            )
        }
    } finally {
        if (currentRequestId === requestId) {
            loading.value = false
        }
    }
}

const shouldLoadNextPage = () => {
    if (
        !wrapperRef.value ||
        !trackRef.value
    ) {
        return false
    }

    if (
        !nextPageUrl.value ||
        loading.value ||
        fullyLoaded.value
    ) {
        return false
    }

    const wrapperWidth =
        wrapperRef.value.clientWidth

    const trackWidth =
        trackRef.value.scrollWidth

    if (
        wrapperWidth <= 0 ||
        trackWidth <= 0
    ) {
        return false
    }

    return (
        Math.abs(translateX.value) +
        wrapperWidth +
        300 >=
        trackWidth
    )
}

const resetTickerPosition = () => {
    translateX.value = 0
    lastTimestamp = 0
}

const animate = (timestamp) => {
    if (!lastTimestamp) {
        lastTimestamp = timestamp
    }

    const deltaTime = Math.min(
        timestamp - lastTimestamp,
        100
    )

    lastTimestamp = timestamp

    if (
        news.value.length &&
        trackRef.value
    ) {
        translateX.value -=
            (tickerSpeed.value * deltaTime) /
            1000
    }

    if (shouldLoadNextPage()) {
        loadBreakingNews(nextPageUrl.value)
    }

    if (
        fullyLoaded.value &&
        news.value.length &&
        trackRef.value
    ) {
        const originalTrackWidth =
            trackRef.value.scrollWidth / 2

        if (
            originalTrackWidth > 0 &&
            Math.abs(translateX.value) >=
            originalTrackWidth
        ) {
            translateX.value += originalTrackWidth
        }
    }

    animationFrame =
        requestAnimationFrame(animate)
}

const updateScreenSize = () => {
    isSmallScreen.value =
        window.innerWidth < 640
}

const handleResize = () => {
    if (resizeTimer) {
        clearTimeout(resizeTimer)
    }

    resizeTimer = setTimeout(() => {
        updateScreenSize()
        resetTickerPosition()
    }, 150)
}

const resetBreakingNews = async () => {
    requestId++

    news.value = []
    nextPageUrl.value = null
    loading.value = false
    fullyLoaded.value = false

    resetTickerPosition()

    if (
        !isDefaultLanguage &&
        !currentLanguage?.code
    ) {
        return
    }

    await loadBreakingNews()
}

watch(
    () => [
        isDefaultLanguage,
        currentLanguage?.code,
    ],
    async (
        [newIsDefault, newCode],
        [oldIsDefault, oldCode]
    ) => {
        if (
            newIsDefault === oldIsDefault &&
            newCode === oldCode
        ) {
            return
        }

        await resetBreakingNews()
    }
)

onMounted(async () => {
    updateScreenSize()

    window.addEventListener(
        'resize',
        handleResize
    )

    await loadBreakingNews()

    animationFrame =
        requestAnimationFrame(animate)
})

onBeforeUnmount(() => {
    requestId++

    if (animationFrame) {
        cancelAnimationFrame(animationFrame)
        animationFrame = null
    }

    if (resizeTimer) {
        clearTimeout(resizeTimer)
        resizeTimer = null
    }

    window.removeEventListener(
        'resize',
        handleResize
    )
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
                    <div ref="trackRef" class="inline-flex items-center whitespace-nowrap will-change-transform" :style="{
                        transform: `translate3d(${translateX}px, 0, 0)`,
                    }">
                        <template v-for="(newsItem, index) in displayNews"
                            :key="`${getNewsUniqueKey(newsItem)}-${index}`">
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
