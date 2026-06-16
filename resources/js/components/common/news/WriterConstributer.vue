<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { formatDateTime } from '@/composables/useDateTime'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faFire,
} from '@fortawesome/free-solid-svg-icons'

import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(
    faFire,
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
    <div class="flex flex-wrap gap-x-4 gap-y-2 text-sm text-gray-500">
        <span v-if="news?.writer">
            {{ t("labels.by") }} {{ news.writer }}
        </span>

        <span v-if="news?.contributors.length">
            {{ t("labels.contributors") }}
            <template v-for="(contributor, index) in news?.contributors" :key="contributor?.id || contributor?.name || index">
                <a :href="contributor?.public_url || '#'" class="hover:text-gray-900">
                    {{ contributor?.name }}
                </a>

                <span v-if="index + 1 < news?.contributors.length">, </span>
            </template>
        </span>
    </div>

</template>
