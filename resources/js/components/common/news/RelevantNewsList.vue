<script setup>
import { computed } from 'vue'

import ListCard from '@/Components/common/news/ListCard.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faAngleRight } from '@fortawesome/free-solid-svg-icons'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'

import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(
    faAngleRight
)

const { t } = useTranslate()

const {
    news,
} = defineProps({
    news: {
        type: Object,
        required: true,
    },
})
</script>

<template>
    <section v-if="news?.relevant_news.length" class="space-y-2 rounded-2xl border border-gray-200 p-2">
        <div class="flex items-center gap-2">
            <FontAwesomeIcon :icon="faAngleRight" class="text-red-600" />

            <h2 class="text-xl font-bold text-gray-950">
                {{ t("components.common.news.relevant_news_list.labels.relevant_news") }}
            </h2>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-1 gap-3">
            <ListCard v-for="(perNews, index) in news?.relevant_news" :key="perNews?.id || perNews?.slug || index" :news="perNews" :hideSubtitle="true" :hideCategory="true" :hideEvent="true" :hideLocation="true" :hideFeatureImage="true" />
        </div>
    </section>
</template>
