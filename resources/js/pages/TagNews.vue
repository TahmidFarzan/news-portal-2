<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'

import Layout from '@/pages/layouts/PublicLayout.vue'
import List from '@/components/common/news/List.vue'

import { useTranslate } from '@/composables/useTranslate'

defineOptions({ layout: Layout })

const { t } = useTranslate()

const { tag, news } = defineProps({
    tag: {
        type: Object,
        required: true,
    },

    news: {
        type: Object,
        required: true,
    },
})

const metaTitle = computed(() => {
    return tag?.seo_title || tag?.name || ''
})

const metaDescription = computed(() => {
    return tag?.seo_brief || tag?.brief || ''
})

const metaKeywords = computed(() => {
    if (Array.isArray(tag?.seo_keywords)) {
        return tag.seo_keywords.join(', ')
    }

    return tag?.seo_keywords || ''
})

const hasBrief = computed(() => {
    return Boolean(tag?.brief)
})
</script>

<template>

    <Head :title="tag?.name || t('labels.tag')">
        <link v-if="tag?.public_url" rel="canonical" :href="tag.public_url" />

        <meta v-if="metaTitle" name="title" :content="metaTitle" />
        <meta v-if="metaDescription" name="description" :content="metaDescription" />
        <meta v-if="metaKeywords" name="keywords" :content="metaKeywords" />
    </Head>

    <div class="space-y-6">
        <section class="grid grid-cols-1 items-center gap-5 md:grid-cols-12">
            <div class="md:col-span-3 lg:col-span-2">
                <div class="flex h-28 w-28 items-center justify-center rounded-2xl bg-blue-50 p-4 sm:h-32 sm:w-32">
                    <img :src="'/uploads/images/logo/tag.png'" :alt="tag?.name || t('tags.details.tag_image_alt')"
                        class="h-full w-full object-contain" loading="lazy" />
                </div>
            </div>

            <div class="space-y-2 md:col-span-9 lg:col-span-10">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">
                    {{ t('labels.tag') }}
                </p>

                <h1 class="text-2xl font-bold tracking-tight text-gray-950 sm:text-3xl">
                    {{ tag?.name }}
                </h1>

                <p v-if="hasBrief" class="max-w-3xl text-sm leading-7 text-gray-600 sm:text-base">
                    {{ tag.brief }}
                </p>
            </div>
        </section>

        <div class="border-t border-gray-200"></div>

        <List :news="news" pagination-type="Cursor" />
    </div>
</template>
