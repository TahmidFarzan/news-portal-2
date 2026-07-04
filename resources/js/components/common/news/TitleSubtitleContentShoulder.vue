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
    <h1 class="article-title text-3xl font-bold leading-tight text-gray-950 md:text-3xl m-0 p-0">
        <b v-if="news?.content_shoulder" class="text-red-400">{{ news?.content_shoulder }} : </b>
        {{ news?.title }}
    </h1>

    <p v-if="news?.sub_title" class="article-subtitle mt-0 mb-3 p-0 max-w-3xl text-sm leading-6 text-gray-500 md:text-base md:leading-7">
        {{ news?.sub_title }}
    </p>
</template>

<style scoped>
.article-title {
    max-width: 56rem;
    font-size: var(--news-article-title-size);
    letter-spacing: 0;
    line-height: 1.16;
}

.article-title b {
    color: var(--news-primary);
}

.article-subtitle {
    color: var(--news-muted-strong);
    font-size: var(--news-article-subtitle-size);
}
</style>
