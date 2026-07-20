<script setup>
import { computed, onMounted, ref, watch } from 'vue'

import { Swiper, SwiperSlide } from 'swiper/vue'
import { Autoplay, Navigation } from 'swiper/modules'

import 'swiper/css'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faLeftLong, faRightLong } from '@fortawesome/free-solid-svg-icons'

import GridCard from '@/components/common/news/GridCard.vue'

import { newsTypes } from '@/composables/useNews'
import { fetchFromApi } from '@/composables/useApiClient'
import { apiCacheKey, apiCacheTTL } from '@/composables/useApiCache'
import { useTranslate, generateTranslationKey } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faLeftLong, faRightLong)

const { t } = useTranslate()

const {
    currentLanguage,
} = defineProps({
    currentLanguage: {
        type: Object,
        required: true,
    },
})

const newsType = newsTypes.Video

const modules = [Autoplay, Navigation]

const newsItems = ref([])

const title = computed(() => {
    return newsType?.Name ?? ''
})

const slug = computed(() => {
    return newsType?.Slug ?? ''
})

const prevButtonClass = computed(() => {
    return `video-slider-prev`
})

const nextButtonClass = computed(() => {
    return `video-slider-next`
})

const getNewsTypeNewsApiUrl = () => {
    return currentLanguage?.is_default
        ? route('home.news-type-news', {
            slug: slug.value,
        })
        : route('localized.home.news-type-news', {
            languageCode: currentLanguage?.code,
            slug: slug.value,
        });
}

const loadNews = async () => {
    if (!slug.value || (!currentLanguage?.is_default && !currentLanguage?.code)) return

    const apiUrl = getNewsTypeNewsApiUrl()

    const response = await fetchFromApi(
        apiUrl,
        {},
        {
            key: `${apiCacheKey.API_HOME_PAGE}:${apiUrl}`,
            ttl: apiCacheTTL.HOME_PAGE,
        }
    )

    newsItems.value = Array.isArray(response)
        ? response
        : response?.data ?? []
}

onMounted(async () => {
    await loadNews()
})

watch(
    () => [
        currentLanguage?.code,
        currentLanguage?.is_default,
    ],
    loadNews
)
</script>

<template>
    <section v-if="newsItems.length" class="video-section rounded-2xl border border-slate-100 bg-white p-3">
        <div class="section-heading mb-4 flex items-center justify-between gap-3">
            <h2 class="text-xl font-bold text-gray-950">
                {{ t(`common.labels.${generateTranslationKey(title)}`) }}
            </h2>

            <div class="flex items-center gap-2">
                <button type="button" :class="prevButtonClass"
                    class="group relative inline-flex h-10 w-10 items-center justify-center overflow-hidden rounded-2xl border border-blue-500/20 bg-gradient-to-br from-blue-600 to-sky-500 text-white shadow-lg shadow-blue-500/25 ring-1 ring-white/20 transition-all duration-300 ease-out hover:-translate-y-1 hover:scale-105 hover:from-blue-700 hover:to-sky-600 hover:shadow-xl hover:shadow-blue-500/40 active:translate-y-0 active:scale-95">
                    <span
                        class="absolute inset-0 translate-x-full bg-gradient-to-r from-transparent via-white/35 to-transparent transition-transform duration-700 ease-out group-hover:-translate-x-full"></span>

                    <FontAwesomeIcon icon="left-long"
                        class="relative z-10 text-sm transition-all duration-300 ease-out group-hover:-translate-x-1 group-hover:scale-110" />
                </button>

                <button type="button" :class="nextButtonClass"
                    class="group relative inline-flex h-10 w-10 items-center justify-center overflow-hidden rounded-2xl border border-blue-500/20 bg-gradient-to-br from-blue-600 to-sky-500 text-white shadow-lg shadow-blue-500/25 ring-1 ring-white/20 transition-all duration-300 ease-out hover:-translate-y-1 hover:scale-105 hover:from-blue-700 hover:to-sky-600 hover:shadow-xl hover:shadow-blue-500/40 active:translate-y-0 active:scale-95">
                    <span
                        class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/35 to-transparent transition-transform duration-700 ease-out group-hover:translate-x-full"></span>

                    <FontAwesomeIcon icon="right-long"
                        class="relative z-10 text-sm transition-all duration-300 ease-out group-hover:translate-x-1 group-hover:scale-110" />
                </button>
            </div>
        </div>

        <Swiper :modules="modules" :slides-per-view="1" :space-between="12" :loop="newsItems.length > 4" :navigation="{
            prevEl: `.${prevButtonClass}`,
            nextEl: `.${nextButtonClass}`,
        }" :autoplay="{
            delay: 3500,
            disableOnInteraction: false,
        }" :breakpoints="{
            640: {
                slidesPerView: 2,
                spaceBetween: 12,
            },
            768: {
                slidesPerView: 3,
                spaceBetween: 14,
            },
            1024: {
                slidesPerView: 4,
                spaceBetween: 16,
            },
        }" class="news-type-slider">
            <SwiperSlide v-for="(news, index) in newsItems" :key="news?.id || news?.slug || index" class="h-auto">
                <GridCard :news="news" :hideCategory="true" :hideEvent="true" :hideLocation="true" :hideBrief="true"
                    :isCompact="true" :useFullHeight="true" />
            </SwiperSlide>
        </Swiper>
    </section>
</template>

<style scoped>
.video-section {
    border-color: var(--news-border-soft);
    background: var(--news-video-gradient);
    box-shadow: var(--news-shadow-soft);
}

.section-heading h2 {
    color: var(--news-white);
}

@media (max-width: 767px) {
    .video-section {
        background: var(--news-video-gradient-mobile);
    }
}
</style>
