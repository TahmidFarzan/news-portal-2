<script setup>
import ListCard from '@/Components/common/news/ListCard.vue'

const {
    news,
} = defineProps({
    news: {
        type: Array,
        required: true,
    },
})
</script>

<template>
    <section v-if="news?.length" class="flex h-[500px] flex-col rounded-2xl border border-gray-200 p-2">
        <div class="flex shrink-0 items-center gap-2 recent-news">
            <h2 class="text-xl font-bold text-gray-950">
                Recent News
            </h2>
        </div>

        <div class="mt-2 min-h-0 flex-1 overflow-y-auto overscroll-contain pr-1 thin-modern-scrollbar">
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-1">
                <ListCard v-for="(perNews, index) in news" :key="perNews?.id || perNews?.slug || index" :news="perNews"
                    :hideSubtitle="true" :hideBrief="true" :hideCategory="true" :hideEvent="true" :hideLocation="true"
                    :hideFeatureImage="true" />
            </div>
        </div>
    </section>
</template>

<style scoped>
.recent-news .thin-modern-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: rgb(156 163 175 / 0.55) transparent;
}

.recent-news .thin-modern-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.recent-news .thin-modern-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.recent-news .thin-modern-scrollbar::-webkit-scrollbar-thumb {
    background: rgb(156 163 175 / 0.45);
    border-radius: 999px;
}

.recent-news .thin-modern-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgb(107 114 128 / 0.75);
}
</style>
