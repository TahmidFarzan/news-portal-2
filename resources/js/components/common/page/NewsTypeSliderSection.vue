<script setup>
import { computed, onMounted, ref } from 'vue'

import { Swiper, SwiperSlide } from 'swiper/vue'
import { Autoplay, Navigation } from 'swiper/modules'

import 'swiper/css'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faLeftLong, faRightLong } from '@fortawesome/free-solid-svg-icons'

import GridCard from '@/components/common/news/GridCard.vue'

import { newsTypes } from '@/composables/useNews'
import { fetchFromApi } from '@/composables/useSystemApi'
import { useTranslate, generateTranslationKey } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faLeftLong, faRightLong)

const { t } = useTranslate()

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
    return `news-type-slider-prev-${slug.value}`
})

const nextButtonClass = computed(() => {
    return `news-type-slider-next-${slug.value}`
})

const loadNews = async () => {
    if (!slug.value) return

    const response = await fetchFromApi(route('home.news-type-news', {
        slug: slug.value,
    }))

    newsItems.value = Array.isArray(response)
        ? response
        : response?.data ?? []
}

onMounted(async () => {
    await loadNews()
})
</script>

<template>
    <section v-if="newsItems.length" class="rounded-2xl border border-slate-100 bg-white p-3">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-xl font-bold text-gray-950">
                {{ t(`components.common.pages.news_type_gallery_section.labels.${generateTranslationKey(title)}`) }}
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
