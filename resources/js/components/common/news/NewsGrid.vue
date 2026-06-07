<script setup>
import { computed } from 'vue'

import GridCard from '@/Components/common/news/GridCard.vue'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faAngleRight } from '@fortawesome/free-solid-svg-icons'

const { news } = defineProps({
    news: {
        type: Object,
        required: true,
    },
})
</script>

<template>
    <section v-if="news.length" class="space-y-5">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <GridCard v-for="perNews in news" :key="perNews?.id || perNews?.slug" :news="perNews" />
        </div>
    </section>
</template>
