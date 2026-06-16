<script setup>
import ListCard from '@/Components/common/news/ListCard.vue'

import { useTranslate } from '@/composables/useTranslate'
const { t } = useTranslate()

const {
    news,
} = defineProps({
    news: {
        type: Array,
        required: true,
    },
})
</script>

<template>
    <section v-if="news?.length" class="flex h-[500px] flex-col rounded-2xl border border-gray-200 p-2">
        <div class="flex shrink-0 items-center gap-2 recent-news">
            <h2 class="text-xl font-bold text-gray-950">
                {{ t("recent_news") }}
            </h2>
        </div>

        <div class="mt-2 min-h-0 flex-1 overflow-y-auto overscroll-contain p-2 thin-modern-scrollbar">
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-1">
                <ListCard v-for="(perNews, index) in news" :key="perNews?.id || perNews?.slug || index" :news="perNews"
                    :hideSubtitle="true" :hideBrief="true" :hideCategory="true" :hideEvent="true" :hideLocation="true"
                    :hideFeatureImage="true" />
            </div>
        </div>
    </section>
</template>
