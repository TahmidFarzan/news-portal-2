<script setup>
import { computed } from 'vue'

import GridCard from '@/Components/common/news/GridCard.vue'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faAngleRight } from '@fortawesome/free-solid-svg-icons'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'

import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(
    faAngleRight
)

const { t } = useTranslate()

const { news } = defineProps({
    news: {
        type: Object,
        required: true,
    },
})
</script>

<template>
    <section v-if="news?.related_news.length" class="space-y-5">
        <div class="flex items-center gap-2">
            <FontAwesomeIcon :icon="faAngleRight" class="text-red-600" />

            <h2 class="text-xl font-bold text-gray-950">
                {{ t("labels.related_news") }}
            </h2>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <GridCard v-for="perNews in news?.related_news" :key="perNews?.id || perNews?.slug" :news="perNews" />
        </div>
    </section>
</template>
