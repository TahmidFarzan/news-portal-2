<script setup>
import { computed,inject } from 'vue'
import { Head } from '@inertiajs/vue3'

import Layout from '@/pages/layouts/PublicLayout.vue'
import List from '@/components/common/news/List.vue'
import GoogleAdsense from '@/components/common/advertising/GoogleAdsense.vue'

import { useTranslate } from '@/composables/useTranslate'
import { adTypes, adPositions } from '@/composables/useGoogleAdsense'

defineOptions({ layout: Layout })

const { t } = useTranslate()

const { news } = defineProps({
    news: {
        type: Object,
        required: true,
    },
})

const metaTitle = computed(() => {
    return t("pages.imageGalleryNews.labels.imageGalleries") || ''
})

const metaDescription = computed(() => {
    return t("pages.imageGalleryNews.labels.brief") || ''
})

const metaKeywords = computed(() => {
    return t("pages.imageGalleryNews.labels.keyWords") || ''
})

const googleAdEnable = inject('googleAdEnable', computed(() => false))
</script>

<template>

    <Head :title="t('pages.imageGalleryNews.labels.imageGalleries')">
        <link v-if="route('image-galleries')" rel="canonical" :href="route('image-galleries')" />

        <meta v-if="metaTitle" name="title" :content="metaTitle" />
        <meta v-if="metaDescription" name="description" :content="metaDescription" />
        <meta v-if="metaKeywords" name="keywords" :content="metaKeywords" />
    </Head>

    <div class="entity-page gallery-page space-y-6">
        <section class="grid grid-cols-1 items-center gap-5 md:grid-cols-12">
            <div class="md:col-span-3 lg:col-span-2">
                <div class="flex h-28 w-28 items-center justify-center rounded-2xl bg-blue-50 p-4 sm:h-32 sm:w-32">
                    <img :src="'/uploads/images/logo/image-gallery.png'" :alt="t('pages.imageGalleryNews.labels.imageGalleries')"
                        class="h-full w-full object-contain" loading="lazy" />
                </div>
            </div>

            <div class="space-y-2 md:col-span-9 lg:col-span-10">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">
                    {{ t('pages.imageGalleryNews.labels.imageGalleries') }}
                </p>
            </div>
        </section>

        <div class="border-t border-gray-200"></div>


        <GoogleAdsense v-if="googleAdEnable" class="mt-4 mb-4" :type="adTypes.SECTION" :position="adPositions.BETWEEN"/>

        <List :news="news" paginationType="Cursor" />
    </div>
</template>

<style scoped>
.entity-page > section:first-child {
    border: var(--news-border-default);
    border-radius: var(--news-radius);
    background: var(--news-hero-success-gradient);
    padding: var(--news-hero-padding);
    box-shadow: var(--news-shadow-soft);
}
</style>
