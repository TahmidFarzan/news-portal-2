<script setup>
import Layout from '@/pages/layouts/PublicLayout.vue'
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'

defineOptions({ layout: Layout })

const { page } = defineProps({
    page: {
        type: Object,
        required: true,
    },
})

const metaTitle = computed(() => {
    return page?.seo_title ?? page?.title
})

const metaDescription = computed(() => {
    return page?.seo_brief ?? page?.brief
})

const metaKeywords = computed(() => {

    return page?.seo_keywords
})
</script>

<template>

    <Head :title="metaTitle">
        <link rel="canonical" :href="route('home')" />

        <meta v-if="metaTitle" name="title" :content="metaTitle" />

        <meta v-if="metaDescription" name="description" :content="metaDescription" />

        <meta v-if="metaKeywords" name="keywords" :content="metaKeywords" />
    </Head>

    <div class="bg-gray-100 py-8">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <div class="lg:col-span-9">
                    <div class="bg-white rounded-lg shadow p-6">
                        Main Content
                    </div>
                </div>

                <div class="lg:col-span-3">
                    <div class="bg-white rounded-lg shadow p-6">
                        Sidebar Content
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
