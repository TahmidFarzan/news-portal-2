<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'

import Layout from '@/pages/layouts/PublicLayout.vue'
import List from '@/components/common/news/List.vue'
import Grid from '@/components/common/news/Grid.vue'

defineOptions({ layout: Layout })

const {news } = defineProps({
    news: {
        type: Object,
        required: true,
    },
})

const metaTitle = computed(() => {
    return  'Latest'
})

const metaDescription = computed(() => {
    return 'Latest'
})

const metaKeywords = computed(() => {

    return 'Latest News, Top News'
})

</script>

<template>

    <Head :title="metaTitle">
        <link rel="canonical" :href="route('latest')" />

        <meta v-if="metaTitle" name="title" :content="metaTitle" />

        <meta v-if="metaDescription" name="description" :content="metaDescription" />

        <meta v-if="metaKeywords" name="keywords" :content="metaKeywords" />
    </Head>

    <div class="space-y-6">
        <List :news="news" pagination-type="Cursor" />
    </div>
</template>
