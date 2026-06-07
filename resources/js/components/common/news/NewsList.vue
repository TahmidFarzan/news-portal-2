<script setup>
import { computed } from 'vue'

import ListCard from '@/Components/common/news/ListCard.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faAngleRight } from '@fortawesome/free-solid-svg-icons'

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
    <section v-if="news.length" class="space-y-2 p-2">
        <div class="grid grid-cols-1 lg:grid-cols-1 gap-3">
            <ListCard v-for="(perNews, index) in news" :key="perNews?.id || perNews?.slug || index" :news="perNews"/>
        </div>
    </section>
</template>
