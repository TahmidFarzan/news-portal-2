<script setup>
import { ref, computed, onMounted } from 'vue'

import { Swiper, SwiperSlide } from 'swiper/vue'
import { Navigation, Autoplay } from 'swiper/modules'

import 'swiper/css'
import 'swiper/css/navigation'

import { fetchFromApi } from '@/composables/useSystemApi'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'

import {
    faFolder,
    faChevronLeft,
    faChevronRight,
} from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(
    faFolder,
    faChevronLeft,
    faChevronRight,
)

const trends = ref([])

const loadTrends = async () => {
    try {
        const response = await fetchFromApi(
            route('site.trends')
        )

        trends.value = Array.isArray(response)
            ? response
            : []

    } catch {
        trends.value = []
    }
}

onMounted(() => {
    loadTrends()
})

const visible = computed(() => ( trends.value.length > 0))

const openTrend = (item) => {
    if (
        item?.public_url &&
        item.public_url.trim()
    ) {
        window.location.href =
            item.public_url
    }
}
</script>

<template>
    <section v-if="visible" class="trends-section relative w-full py-3">
        <Swiper :modules="[
            Navigation,
            Autoplay,
        ]" :slides-per-view="'auto'" :space-between="12" :loop="true" :navigation="{
            prevEl: '.trend-prev',
            nextEl: '.trend-next',
        }" :autoplay="{
                delay: 3500,
                disableOnInteraction: false,
            }" class="group">
            <SwiperSlide v-for="item in trends" :key="item.id" class="!w-auto">
                <button type="button" @click="openTrend(item)" class="trend-chip flex items-center gap-2 rounded-full px-5 py-3 text-white transition hover:-translate-y-1 cursor-pointer">
                    <FontAwesomeIcon :icon="[
                        'fas',
                        'folder',
                    ]" class="text-xs" />

                    <span class="text-sm font-semibold whitespace-nowrap">
                        {{ item?.tag?.name }}
                    </span>
                </button>
            </SwiperSlide>

            <button type="button" class="trend-prev absolute left-0 top-1/2 z-20 hidden group-hover:flex
                h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 shadow-xl
                text-slate-700 hover:scale-105 cursor-pointer">
                <FontAwesomeIcon :icon="[
                    'fas',
                    'chevron-left',
                ]" />
            </button>

            <button type="button" class="trend-next absolute right-0 top-1/2 z-20 hidden group-hover:flex
                h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 shadow-xl
                text-slate-700 hover:scale-105 cursor-pointer">
                <FontAwesomeIcon :icon="[
                    'fas',
                    'chevron-right',
                ]" />
            </button>
        </Swiper>
    </section>
</template>

<style scoped>
.trends-section {
    border-block: var(--news-trends-border);
}

.trend-chip {
    background: var(--news-trend-gradient);
    box-shadow: var(--news-shadow-trend);
}

.trend-chip:hover {
    box-shadow: var(--news-shadow-trend-hover);
}
</style>
