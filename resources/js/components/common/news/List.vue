<script setup>
import { computed, ref, watch } from 'vue'

import ListCard from '@/Components/common/news/ListCard.vue'
import ModelPagination from '@/components/common/model/Pagination.vue'
import CursorPagination from '@/components/common/model/CursorPagination.vue'
import { useTranslate } from '@/composables/useTranslate'

const { t } = useTranslate()

const {
    news,
    paginationType = 'Normal',
    showPaginationOption = true,
    cursorPropName = 'news',
} = defineProps({
    news: {
        type: [Object, Array],
        required: true,
    },

    paginationType: {
        type: [String, Boolean],
    },

    showPaginationOption: {
        type: Boolean,
    },

    cursorPropName: {
        type: String,
    },
})

const newsItems = ref([])
const cursorPayload = ref({})

const extractItems = (value) => {
    if (Array.isArray(value)) {
        return value
    }

    if (Array.isArray(value?.data)) {
        return value.data
    }

    return []
}

const extractPaginationOnly = (value) => {
    if (!value || Array.isArray(value)) {
        return {}
    }

    const { data, ...rest } = value

    return rest
}

const paginationOnly = computed(() => {
    return extractPaginationOnly(news)
})

const hasCursorMeta = computed(() => {
    return Boolean(
        news?.next_page_url ||
        news?.prev_page_url ||
        news?.next_cursor ||
        news?.prev_cursor ||
        news?.links?.next ||
        news?.links?.prev
    )
})

const resolvedPaginationType = computed(() => {
    if (paginationType === false) {
        return hasCursorMeta.value ? 'Cursor' : 'Normal'
    }

    return String(paginationType).toLowerCase() === 'cursor'
        ? 'Cursor'
        : 'Normal'
})

const hasNews = computed(() => {
    return newsItems.value.length > 0
})

const shouldShowNormalPagination = computed(() => {
    return (
        showPaginationOption &&
        resolvedPaginationType.value === 'Normal' &&
        !Array.isArray(news) &&
        Object.keys(paginationOnly.value).length > 0
    )
})

const shouldShowCursorPagination = computed(() => {
    return (
        showPaginationOption &&
        resolvedPaginationType.value === 'Cursor' &&
        !Array.isArray(news) &&
        Object.keys(cursorPayload.value).length > 0
    )
})

const syncNews = () => {
    newsItems.value = extractItems(news)
    cursorPayload.value = news
}

watch(
    () => news,
    () => {
        syncNews()
    },
    {
        immediate: true,
        deep: true,
    }
)

const getUniqueKey = (item, index) => {
    return item?.id || item?.slug || item?.uuid || index
}

const appendCursorData = (pagination) => {
    const newItems = extractItems(pagination)

    const existingKeys = new Set(
        newsItems.value.map((item, index) => getUniqueKey(item, index))
    )

    const uniqueItems = newItems.filter((item, index) => {
        return !existingKeys.has(getUniqueKey(item, index))
    })

    newsItems.value = [
        ...newsItems.value,
        ...uniqueItems,
    ]

    cursorPayload.value = pagination
}

const removeLastCursorData = ({ count, pagination }) => {
    if (count > 0) {
        newsItems.value = newsItems.value.slice(0, -count)
    }

    if (pagination) {
        cursorPayload.value = pagination
    }
}

const replaceCursorData = (pagination) => {
    newsItems.value = extractItems(pagination)
    cursorPayload.value = pagination
}
</script>

<template>
    <section v-if="hasNews" class="news-list space-y-4 p-2">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-1">
            <ListCard v-for="(perNews, index) in newsItems" :key="getUniqueKey(perNews, index)" :news="perNews" />
        </div>

        <ModelPagination v-if="shouldShowNormalPagination" :pagination="paginationOnly" />

        <CursorPagination v-if="shouldShowCursorPagination" :pagination="cursorPayload" :prop-name="cursorPropName"
            @append="appendCursorData" @remove-last="removeLastCursorData" @replace="replaceCursorData" />
    </section>
    <section v-else class="news-empty-state">
        <p>{{ t('components.common.news.list.labels.no_news_found') }}</p>
    </section>
</template>

<style scoped>
.news-list {
    border-radius: var(--news-radius);
}

.news-empty-state {
    display: grid;
    min-height: 12rem;
    place-items: center;
    border: var(--news-border-dashed);
    border-radius: var(--news-radius);
    background: var(--news-surface);
    color: var(--news-muted);
    font-size: var(--news-list-title-size);
}
</style>
