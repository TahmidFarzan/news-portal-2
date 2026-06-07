<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'

import Layout from '@/pages/layouts/PublicLayout.vue'
import List from '@/components/common/news/List.vue'
import Grid from '@/components/common/news/Grid.vue'

defineOptions({ layout: Layout })

const { contributor, news } = defineProps({
    contributor: {
        type: Object,
        required: true,
    },

    news: {
        type: Object,
        required: true,
    },
})

const metaTitle = computed(() => {
    return contributor?.seo_title || contributor?.name || ''
})

const metaDescription = computed(() => {
    return contributor?.seo_brief || contributor?.brief || ''
})

const metaKeywords = computed(() => {
    if (Array.isArray(contributor?.seo_keywords)) {
        return contributor.seo_keywords.join(', ')
    }

    return contributor?.seo_keywords || ''
})
</script>

<template>

    <Head :title="contributor?.name || 'Contributor'">
        <link v-if="contributor?.public_url" rel="canonical" :href="contributor?.public_url" />

        <meta v-if="metaTitle" name="title" :content="metaTitle" />

        <meta v-if="metaDescription" name="description" :content="metaDescription" />

        <meta v-if="metaKeywords" name="keywords" :content="metaKeywords" />
    </Head>

    <div class="space-y-6">
        <section class="grid grid-cols-1 items-center gap-5 md:grid-cols-12">
            <div class="md:col-span-3 lg:col-span-2">
                <div class="flex h-28 w-28 items-center justify-center rounded-2xl bg-blue-50 p-4 sm:h-32 sm:w-32">
                    <img v-if="contributor?.profile_image"
                        :src="contributor?.profile_image?.media_url || contributor?.profile_image?.original_url || '/uploads/images/news/contributor.png'"
                        :alt="contributor?.name || 'Contributor image'" class="h-full w-full object-contain"
                        loading="lazy" />
                    <img v-else :src="'/uploads/images/news/contributor.png'"
                        :alt="contributor?.name || 'Contributor image'" class="h-full w-full object-contain"
                        loading="lazy" />
                </div>
            </div>

            <div class="space-y-2 md:col-span-9 lg:col-span-10">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">
                    Contributor
                </p>

                <h1 class="text-2xl font-bold tracking-tight text-gray-950 sm:text-3xl">
                    {{ contributor?.name }}
                </h1>

                <p v-if="contributor?.brief" class="max-w-3xl text-sm leading-7 text-gray-600 sm:text-base">
                    {{ contributor?.brief }}
                </p>

                <section v-if="contributor?.profile_details"
                    class="max-w-3xl text-sm leading-7 text-gray-600 sm:text-base"
                    v-html="contributor?.profile_details" />
            </div>
        </section>

        <div class="border-t border-gray-200"></div>

        <List :news="news" pagination-type="Cursor" />
    </div>
</template>
