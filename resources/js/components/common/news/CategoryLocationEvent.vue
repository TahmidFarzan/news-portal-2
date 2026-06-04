<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { formatDateTime } from '@/composables/useDateTime'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faFolder,
    faLocationDot,
    faCalendarDays,
} from '@fortawesome/free-solid-svg-icons'
FontAwesomeLibrary.add(
    faFolder,
    faLocationDot,
    faCalendarDays,
)

const { news } = defineProps({
    news: {
        type: Object,
        required: true,
    },
})
</script>

<template>
    <div class="flex flex-wrap gap-x-4 gap-y-2 text-sm text-gray-500">
        <a v-if="news?.category" :href="news.category.public_url || '#'" class="hover:text-gray-900">
            <FontAwesomeIcon icon="folder" />
            {{ news?.category?.name }}
        </a>

        <span v-if="news?.location" class="text-gray-300">|</span>

        <a v-if="news?.location" :href="news.location?.public_url || '#'" class="hover:text-gray-900">
            <FontAwesomeIcon icon="location-dot" />
            {{ news.location?.name }}
        </a>

        <span v-if="news?.event" class="text-gray-300">|</span>

        <a v-if="news?.event" :href="news.event?.public_url || '#'" class="hover:text-gray-900">
            <FontAwesomeIcon icon="calendar-days" />
            {{ news.event?.name }}
        </a>
    </div>
</template>
