<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { formatDateTime } from '@/composables/useDateTime'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faFire,
} from '@fortawesome/free-solid-svg-icons'
FontAwesomeLibrary.add(
    faFire,
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
        <div v-if="news?.tags.length" class="flex flex-wrap gap-2">
            <a v-for="tag in news?.tags" :key="tag?.id || tag?.name" :href="tag?.public_url || '#'"
                class="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-700 hover:bg-gray-200">
                <FontAwesomeIcon v-if="tag?.trend" icon="fire" /> {{ tag?.name }}
            </a>
        </div>
    </div>
</template>
