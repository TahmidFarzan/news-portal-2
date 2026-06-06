<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { fetchFromApi } from '@/composables/useSystemApi'

const {
    title = 'Breaking News',
    speed = 45, // px per second
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

let animationFrame = null
let lastTimestamp = 0

const hasNews = computed(() => news.value.length > 0)

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

const hasPublicUrl = (news) => {
    return typeof news?.public_url === 'string' && news.public_url.trim() !== ''
}

const appendUniqueNews = (items = []) => {
    const existingIds = new Set(news.value.map((news) => news.id))

    const uniqueItems = items.filter((news) => {
        return news?.id && !existingIds.has(news.id)
    })

    news.value.push(...uniqueItems)
}

const loadBreakingNews = async (url = null) => {
    if (loading.value || fullyLoaded.value) {
        return
    }

    loading.value = true

    try {
        const response = await fetchFromApi(url ?? route('site.breaking-news'))
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
        translateX.value -= (speed * deltaTime) / 1000
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

onMounted(async () => {
    await loadBreakingNews()

    animationFrame = requestAnimationFrame(animate)
})

onBeforeUnmount(() => {
    if (animationFrame) {
        cancelAnimationFrame(animationFrame)
    }
})
</script>

<template>
    <section v-if="hasNews" class="bg-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 h-11 flex items-center">
            <div
                class="h-full px-4 -ml-4 flex items-center flex-shrink-0 bg-red-600 text-white font-bold text-sm md:text-base whitespace-nowrap">
                {{ title }}
            </div>

            <div ref="wrapperRef" class="flex-1 overflow-hidden min-w-0 px-4 text-gray-800">
                <div ref="trackRef" class="inline-flex items-center whitespace-nowrap will-change-transform"
                    :style="{ transform: `translateX(${translateX}px)` }">
                    <template v-for="(news, index) in displayNews" :key="`${news.id}-${index}`">
                        <a v-if="hasPublicUrl(news)" :href="news.public_url"
                            class="inline-flex items-center text-sm md:text-base font-medium hover:text-blue-600 hover:underline">
                            {{ news.title }}
                        </a>

                        <span v-else class="inline-flex items-center text-sm md:text-base font-medium cursor-default">
                            {{ news.title }}
                        </span>

                        <span class="mx-4 text-gray-400 select-none">
                            |
                        </span>
                    </template>
                </div>
            </div>

            <div v-if="loading && !fullyLoaded" class="flex-shrink-0 text-xs text-gray-500">
                Loading...
            </div>
        </div>
    </section>
</template>
