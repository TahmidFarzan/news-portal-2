<script setup>
import { computed,inject } from 'vue'
import { Head } from '@inertiajs/vue3'

import Layout from '@/pages/layouts/PublicLayout.vue'
import List from '@/components/common/news/List.vue'
import GoogleAd from '@/components/common/advertising/GoogleAd.vue'

import { useTranslate } from '@/composables/useTranslate'
import { adTypes, adPositions } from '@/composables/useGoogleAd'

defineOptions({ layout: Layout })

const { t } = useTranslate()

const { news } = defineProps({
    news: {
        type: Object,
        required: true,
    },
})

const metaTitle = computed(() => {
    return t("pages.videoNews.labels.videos") || ''
})

const metaDescription = computed(() => {
    return t("pages.videoNews.labels.brief") || ''
})

const metaKeywords = computed(() => {
    return t("pages.videoNews.labels.keyWords") || ''
})
const googleAdEnable = inject('googleAdEnable', computed(() => false))

</script>

<template>

    <Head :title="t('pages.videoNews.labels.videos')">
        <link v-if="route('videos')" rel="canonical" :href="route('videos')" />

        <meta v-if="metaTitle" name="title" :content="metaTitle" />
        <meta v-if="metaDescription" name="description" :content="metaDescription" />
        <meta v-if="metaKeywords" name="keywords" :content="metaKeywords" />
    </Head>

    <div class="entity-page video-page space-y-6">
        <section class="grid grid-cols-1 items-center gap-5 md:grid-cols-12">
            <div class="md:col-span-3 lg:col-span-2">
                <div class="flex h-28 w-28 items-center justify-center rounded-2xl bg-blue-50 p-4 sm:h-32 sm:w-32">
                    <img :src="'/uploads/images/logo/video.png'" :alt="t('pages.videoNews.labels.videos')"
                        class="h-full w-full object-contain" loading="lazy" />
                </div>
            </div>

            <div class="space-y-2 md:col-span-9 lg:col-span-10">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">
                    {{ t('pages.videoNews.labels.videos') }}
                </p>
            </div>
        </section>

        <div class="border-t border-gray-200"></div>

        <GoogleAd v-if="googleAdEnable" class="mt-4 mb-4" :type="adTypes.SECTION" :position="adPositions.BETWEEN"/>

        <List :news="news" pagination-type="Cursor" />
    </div>
</template>

<style scoped>
.entity-page > section:first-child {
    border: var(--news-border-default);
    border-radius: var(--news-radius);
    background: var(--news-hero-video-gradient);
    padding: var(--news-hero-padding);
    box-shadow: var(--news-shadow-soft);
}

.entity-page > section:first-child p {
    color: var(--news-white);
}
</style>
