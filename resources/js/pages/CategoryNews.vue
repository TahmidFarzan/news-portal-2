<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'

import Layout from '@/pages/layouts/PublicLayout.vue'
import List from '@/components/common/news/List.vue'
import Grid from '@/components/common/news/Grid.vue'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'

import {
    faFolder,
    faLocationDot,
} from '@fortawesome/free-solid-svg-icons'


FontAwesomeLibrary.add(
    faFolder,
    faLocationDot,
)

defineOptions({ layout: Layout })

const { category, news } = defineProps({
    category: {
        type: Object,
        required: true,
    },

    news: {
        type: Object,
        required: true,
    },
})

const metaTitle = computed(() => {
    return category?.seo_title || category?.name || ''
})

const metaDescription = computed(() => {
    return category?.seo_brief || category?.brief || ''
})

const metaKeywords = computed(() => {
    if (Array.isArray(category?.seo_keywords)) {
        return category.seo_keywords.join(', ')
    }

    return category?.seo_keywords || ''
})

const hasBrief = computed(() => {
    return Boolean(category?.brief)
})
</script>

<template>

    <Head :title="category?.name || 'Category'">
        <link v-if="category?.public_url" rel="canonical" :href="category?.public_url || ''" />

        <meta v-if="metaTitle" name="title" :content="metaTitle" />

        <meta v-if="metaDescription" name="description" :content="metaDescription" />

        <meta v-if="metaKeywords" name="keywords" :content="metaKeywords" />
    </Head>

    <div class="space-y-6">
        <section class="grid grid-cols-1 items-center gap-5 md:grid-cols-12">
            <div class="md:col-span-3 lg:col-span-2">
                <div class="flex h-28 w-28 items-center justify-center rounded-2xl bg-blue-50 p-4 sm:h-32 sm:w-32">
                    <img :src="'/uploads/images/logo/category.png'" :alt="category?.name || 'Category image'"
                        class="h-full w-full object-contain" loading="lazy" />
                </div>
            </div>

            <div class="space-y-2 md:col-span-9 lg:col-span-10">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">
                    Category
                </p>

                <div v-if="category?.parent"
                    class="pointer-events-auto flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                    <a v-if="category?.parent" :href="category?.parent?.public_url" title="Category"
                        class="inline-flex min-w-0 items-center gap-1 transition duration-300 hover:text-red-600"
                        @click.stop>
                        <FontAwesomeIcon icon="folder" class="shrink-0" />
                        <span class="truncate">{{ category?.parent?.name }}</span>
                    </a>
                </div>

                <h1 class="text-2xl font-bold tracking-tight text-gray-950 sm:text-3xl">
                    {{ category?.name }}
                </h1>

                <p v-if="hasBrief" class="max-w-3xl text-sm leading-7 text-gray-600 sm:text-base">
                    {{ category?.brief }}
                </p>

                <div v-if="category?.has_descendants && category?.children?.length"
                    class="flex flex-wrap items-center gap-2 pt-1">
                    <a v-for="child in category.children" :key="child.id || child.slug" :href="child.public_url"
                        :title="child.name"
                        class="inline-flex min-w-0 items-center gap-1 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-600 shadow-sm transition duration-300 hover:border-red-200 hover:bg-red-50 hover:text-red-600">
                        <FontAwesomeIcon icon="folder" class="shrink-0" />

                        <span class="truncate">
                            {{ child.name }}
                        </span>
                    </a>
                </div>
            </div>
        </section>

        <div class="border-t border-gray-200"></div>

        <List :news="news" pagination-type="Cursor" />
    </div>
</template>
