<script setup>
import { computed, onMounted, ref, watch } from 'vue'

import { Swiper, SwiperSlide } from 'swiper/vue'
import { Autoplay, Navigation } from 'swiper/modules'

import 'swiper/css'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faLeftLong, faRightLong, faImages } from '@fortawesome/free-solid-svg-icons'

import GridCard from '@/components/common/news/GridCard.vue'
import ListCard from '@/components/common/news/ListCard.vue'

import { newsTypes } from '@/composables/useNews'
import { fetchFromApi } from '@/composables/useApiClient'
import { apiCacheKey, apiCacheTTL } from '@/composables/useApiCache'
import { useTranslate, generateTranslationKey } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faLeftLong, faRightLong, faImages)

const { t } = useTranslate()

const {
    currentLanguage,
} = defineProps({
    currentLanguage: {
        type: Object,
        required: true,
    },
})

const newsType = newsTypes.ImageGallery

const modules = [Autoplay, Navigation]

const newsItems = ref([])

const title = computed(() => newsType?.Name ?? '')
const slug = computed(() => newsType?.Slug ?? '')
const translationKey = computed(() => generateTranslationKey(title.value))

const topNews = computed(() => newsItems.value.slice(0, 3))
const sliderNews = computed(() => newsItems.value.slice(3))

const prevButtonClass = computed(() => `news-type-gallery-prev-${slug.value}`)
const nextButtonClass = computed(() => `news-type-gallery-next-${slug.value}`)

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
    if (!slug.value || (!currentLanguage?.id_default) && !currentLanguage?.code) return

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
        currentLanguage?.id_default,
    ],
    loadNews
)
</script>

<template>
    <section v-if="newsItems.length" class="gallery-section rounded-2xl border border-slate-100 bg-white p-3">
        <div class="section-heading mb-4 flex items-center justify-between gap-3">
            <h2 class="text-xl font-bold text-gray-950">
                {{ t(`common.labels.${translationKey}`) }}
            </h2>
        </div>

        <div v-if="topNews.length" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                <GridCard v-if="topNews[0]" :news="topNews[0]" :hideCategory="true" :hideEvent="true"
                    :hideLocation="true" :hideBrief="true" :isCompact="true" :useFullHeight="true" />

                <GridCard v-if="topNews[1]" class="hidden md:block" :news="topNews[1]" :hideCategory="true"
                    :hideEvent="true" :hideLocation="true" :hideBrief="true" :isCompact="true" :useFullHeight="true" />

                <GridCard v-if="topNews[2]" class="hidden lg:block" :news="topNews[2]" :hideCategory="true"
                    :hideEvent="true" :hideLocation="true" :hideBrief="true" :isCompact="true" :useFullHeight="true" />
            </div>

            <div class="grid grid-cols-1 gap-3 md:hidden">
                <ListCard v-for="(news, index) in topNews.slice(1)" :key="news?.id || news?.slug || index" :news="news"
                    :hideCategory="true" :hideEvent="true" :hideLocation="true" :hideBrief="true" :isCompact="true" />
            </div>

            <div v-if="topNews[2]" class="hidden md:grid lg:hidden">
                <ListCard :news="topNews[2]" :hideCategory="true" :hideEvent="true" :hideLocation="true"
                    :hideBrief="true" :isCompact="true" />
            </div>
        </div>

        <div v-if="sliderNews.length" class="my-6 flex items-center gap-3">
            <div class="h-px flex-1 bg-gradient-to-r from-transparent via-slate-200 to-slate-300"></div>

            <div
                class="relative flex h-9 w-9 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-sky-500 shadow-lg shadow-blue-500/25 ring-1 ring-white/20">
                <span class="absolute inset-0 rounded-2xl bg-white/10"></span>
                <FontAwesomeIcon icon="images" class="relative z-10 text-sm text-white" />
            </div>

            <div class="h-px flex-1 bg-gradient-to-r from-slate-300 via-slate-200 to-transparent"></div>
        </div>

        <div v-if="sliderNews.length">
            <Swiper :modules="modules" :slides-per-view="1" :space-between="12" :loop="sliderNews.length > 4"
                :navigation="{
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
                }">
                <SwiperSlide v-for="(news, index) in sliderNews" :key="news?.id || news?.slug || index" class="h-auto">
                    <GridCard :news="news" :hideCategory="true" :hideEvent="true" :hideLocation="true" :hideBrief="true"
                        :isCompact="true" :useFullHeight="true" />
                </SwiperSlide>
            </Swiper>

            <div class="mt-5 flex items-center justify-center gap-3">
                <button type="button" :class="prevButtonClass"
                    class="group relative inline-flex h-11 w-11 items-center justify-center overflow-hidden rounded-2xl border border-blue-500/20 bg-gradient-to-br from-blue-600 to-sky-500 text-white shadow-lg shadow-blue-500/25 ring-1 ring-white/20 transition-all duration-300 ease-out hover:-translate-y-1 hover:scale-105 hover:from-blue-700 hover:to-sky-600 hover:shadow-xl hover:shadow-blue-500/40 active:translate-y-0 active:scale-95">
                    <span
                        class="absolute inset-0 translate-x-full bg-gradient-to-r from-transparent via-white/35 to-transparent transition-transform duration-700 ease-out group-hover:-translate-x-full"></span>

                    <FontAwesomeIcon icon="left-long"
                        class="relative z-10 text-sm transition-all duration-300 ease-out group-hover:-translate-x-1 group-hover:scale-110" />
                </button>

                <button type="button" :class="nextButtonClass"
                    class="group relative inline-flex h-11 w-11 items-center justify-center overflow-hidden rounded-2xl border border-blue-500/20 bg-gradient-to-br from-blue-600 to-sky-500 text-white shadow-lg shadow-blue-500/25 ring-1 ring-white/20 transition-all duration-300 ease-out hover:-translate-y-1 hover:scale-105 hover:from-blue-700 hover:to-sky-600 hover:shadow-xl hover:shadow-blue-500/40 active:translate-y-0 active:scale-95">
                    <span
                        class="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/35 to-transparent transition-transform duration-700 ease-out group-hover:translate-x-full"></span>

                    <FontAwesomeIcon icon="right-long"
                        class="relative z-10 text-sm transition-all duration-300 ease-out group-hover:translate-x-1 group-hover:scale-110" />
                </button>
            </div>
        </div>
    </section>
</template>

<style scoped>
.gallery-section {
    border-color: var(--news-border-soft);
    background: var(--news-gallery-gradient);
    box-shadow: var(--news-shadow-soft);
}

.section-heading {
    border-bottom: var(--news-border-default);
    padding-bottom: 0.75rem;
}

.section-heading h2 {
    letter-spacing: 0;
}
</style>
