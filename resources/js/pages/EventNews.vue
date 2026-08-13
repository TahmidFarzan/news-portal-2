<script setup>
import { computed, inject } from 'vue'
import { Head } from '@inertiajs/vue3'

import Layout from '@/pages/layouts/PublicLayout.vue'
import List from '@/components/common/news/List.vue'
import GoogleAd from '@/components/common/advertising/GoogleAd.vue'

import { useTranslate } from '@/composables/useTranslate'
import { adPages, adTypes, adPlacements } from '@/composables/useGoogleAd'

defineOptions({ layout: Layout })

const { t } = useTranslate()

const { event, news } = defineProps({
    event: {
        type: Object,
        required: true,
    },

    news: {
        type: Object,
        required: true,
    },
})

const metaTitle = computed(() => {
    return event?.seo_title || event?.name || ''
})

const metaDescription = computed(() => {
    return event?.seo_brief || event?.brief || ''
})

const metaKeywords = computed(() => {
    if (Array.isArray(event?.seo_keywords)) {
        return event.seo_keywords.join(', ')
    }

    return event?.seo_keywords || ''
})

const hasBrief = computed(() => {
    return Boolean(event?.brief)
})
const googleAdEnable = inject('googleAdEnable', computed(() => false))
</script>

<template>

    <Head :title="event?.name || t('common.labels.event')">
        <link v-if="event?.public_url" rel="canonical" :href="event.public_url" />

        <meta v-if="metaTitle" name="title" :content="metaTitle" />

        <meta v-if="metaDescription" name="description" :content="metaDescription" />

        <meta v-if="metaKeywords" name="keywords" :content="metaKeywords" />
    </Head>

    <div class="entity-page event-page space-y-6">
        <section class="grid grid-cols-1 items-center gap-5 md:grid-cols-12">
            <div class="md:col-span-3 lg:col-span-2">
                <div class="flex h-28 w-28 items-center justify-center rounded-2xl bg-blue-50 p-4 sm:h-32 sm:w-32">
                    <img :src="'/uploads/images/logo/event.png'"
                        :alt="event?.name || t('pages.eventNews.events.details.eventImageAlt')"
                        class="h-full w-full object-contain" loading="lazy" />
                </div>
            </div>

            <div class="space-y-2 md:col-span-9 lg:col-span-10">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">
                    {{ t('common.labels.event') }}
                </p>

                <h1 class="text-2xl font-bold tracking-tight text-gray-950 sm:text-3xl">
                    {{ event?.name }}
                </h1>

                <p v-if="hasBrief" class="max-w-3xl text-sm leading-7 text-gray-600 sm:text-base">
                    {{ event.brief }}
                </p>
            </div>
        </section>

        <GoogleAd v-if="googleAdEnable" :page="adPages.EVENT" :type="adTypes.SECTION" :placement="adPlacements.ONE" />

        <div class="border-t border-gray-200"></div>

        <GoogleAd v-if="googleAdEnable" :page="adPages.EVENT" :type="adTypes.SECTION" :placement="adPlacements.TWO" />

        <List :news="news" pagination-type="Cursor" />

        <GoogleAd v-if="googleAdEnable" :page="adPages.EVENT" :type="adTypes.SECTION" :placement="adPlacements.THREE" />
    </div>
</template>

<style scoped>
.entity-page>section:first-child {
    border: var(--news-border-default);
    border-radius: var(--news-radius);
    background: var(--news-hero-danger-gradient);
    padding: var(--news-hero-padding);
    box-shadow: var(--news-shadow-soft);
}
</style>
