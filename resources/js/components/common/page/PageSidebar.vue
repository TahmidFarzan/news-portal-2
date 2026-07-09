```vue
<script setup>
import { computed, ref } from 'vue'

import ListCard from '@/components/common/news/ListCard.vue'

import { useTranslate } from '@/composables/useTranslate'

const { t } = useTranslate()

const { recentNews, popularNews } = defineProps({
    recentNews: {
        type: Object,
        required: true,
    },

    popularNews: {
        type: Object,
        required: true,
    },
})

const activeTab = ref('recent')

const hasRecentNews = computed(() => (recentNews?.length ?? 0) > 0)
const hasPopularNews = computed(() => (popularNews?.length ?? 0) > 0)

const currentNews = computed(() => {
    if (activeTab.value === 'popular') {
        return popularNews ?? []
    }

    return recentNews ?? []
})
</script>

<template>
    <aside v-if="hasRecentNews || hasPopularNews"
        class="page-sidebar flex h-[400px] flex-col rounded-2xl border border-gray-200 p-2">
        <div class="flex shrink-0 gap-2 border-b border-gray-200 pb-2">
            <button v-if="hasRecentNews" class="sidebar-tab" :class="{ active: activeTab === 'recent' }"
                @click="activeTab = 'recent'">
                {{ t('common.labels.recentNews') }}
            </button>

            <button v-if="hasPopularNews" class="sidebar-tab" :class="{ active: activeTab === 'popular' }"
                @click="activeTab = 'popular'">
                {{ t('common.labels.recentNews') }}
            </button>
        </div>

        <div class="thin-modern-scrollbar mt-3 min-h-0 flex-1 overflow-y-auto overscroll-contain pr-1">
            <div class="grid grid-cols-1 gap-3">
                <ListCard v-for="(perNews, index) in currentNews" :key="perNews?.id || perNews?.slug || index"
                    :news="perNews" :hideSubtitle="true" :hideBrief="true" :hideCategory="true" :hideEvent="true"
                    :hideLocation="true" :hideFeatureImage="true" :isCompact="true" />
            </div>
        </div>
    </aside>
</template>

<style scoped>
.page-sidebar {
    background: var(--news-white);
    border-color: var(--news-border);
    box-shadow: var(--news-shadow-soft);
}

.sidebar-tab {
    flex: 1;
    height: 42px;
    border-radius: 999px;
    color: var(--news-muted-strong);
    font-size: .875rem;
    font-weight: 600;
    transition: all .25s ease;
}

.sidebar-tab:hover {
    background: var(--news-surface);
}

.sidebar-tab.active {
    background: var(--news-primary);
    color: var(--news-white);
}

.thin-modern-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: var(--news-scrollbar-thumb) transparent;
}

.thin-modern-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.thin-modern-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.thin-modern-scrollbar::-webkit-scrollbar-thumb {
    background: var(--news-scrollbar-thumb-soft);
    border-radius: 999px;
}

.thin-modern-scrollbar::-webkit-scrollbar-thumb:hover {
    background: var(--news-scrollbar-thumb-hover);
}
</style>
```
