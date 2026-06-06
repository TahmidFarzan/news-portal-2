<script setup>
import { computed } from 'vue'

import ListCard from '@/Components/common/news/ListCard.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faAngleRight } from '@fortawesome/free-solid-svg-icons'

const {
    newsItems,
} = defineProps({
    newsItems: {
        type: Object,
        required: true,
    },
})
</script>

<template>
    <section v-if="newsItems.length" class="space-y-2 p-2">
        <div class="grid grid-cols-1 lg:grid-cols-1 gap-3">
            <ListCard v-for="(item, index) in newsItems" :key="item?.id || item?.slug || index" :item="item"/>
        </div>
    </section>
</template>
