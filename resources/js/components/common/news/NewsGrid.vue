<script setup>
import { computed } from 'vue'

import GridCard from '@/Components/common/news/GridCard.vue'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faAngleRight } from '@fortawesome/free-solid-svg-icons'

const { newsItems } = defineProps({
    newsItems: {
        type: Object,
        required: true,
    },
})
</script>

<template>
    <section v-if="newsItems.length" class="space-y-5">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <GridCard v-for="item in newsItems" :key="item?.id || item?.slug" :item="item" />
        </div>
    </section>
</template>
