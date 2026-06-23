<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'

import Layout from '@/pages/layouts/PublicLayout.vue'
import List from '@/components/common/news/List.vue'
import GoogleAdsence from '@/components/common/util/GoogleAdsence.vue'

import { useTranslate } from '@/composables/useTranslate'

defineOptions({ layout: Layout })

const { t } = useTranslate()

const { news } = defineProps({
    news: {
        type: Object,
        required: true,
    },
})

const metaTitle = computed(() => {
    return t("pages.image_gallery.labels.image_galleries") || ''
})

const metaDescription = computed(() => {
    return t("pages.image_gallery.labels.brief") || ''
})

const metaKeywords = computed(() => {
    return t("pages.image_gallery.labels.key_words") || ''
})

</script>

<template>

    <Head :title="t('pages.image_gallery.labels.image_galleries')">
        <link v-if="route('image-galleries')" rel="canonical" :href="route('image-galleries')" />

        <meta v-if="metaTitle" name="title" :content="metaTitle" />
        <meta v-if="metaDescription" name="description" :content="metaDescription" />
        <meta v-if="metaKeywords" name="keywords" :content="metaKeywords" />
    </Head>

    <div class="space-y-6">
        <section class="grid grid-cols-1 items-center gap-5 md:grid-cols-12">
            <div class="md:col-span-3 lg:col-span-2">
                <div class="flex h-28 w-28 items-center justify-center rounded-2xl bg-blue-50 p-4 sm:h-32 sm:w-32">
                    <img :src="'/uploads/images/logo/image-gallery.png'" :alt="t('pages.image_gallery.labels.image_galleries')"
                        class="h-full w-full object-contain" loading="lazy" />
                </div>
            </div>

            <div class="space-y-2 md:col-span-9 lg:col-span-10">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">
                    {{ t('pages.image_gallery.labels.image_galleries') }}
                </p>
            </div>
        </section>

        <div class="border-t border-gray-200"></div>

        <GoogleAdsence v-if="showGoogleAd" />

        <List :news="news" pagination-type="Cursor" />
    </div>
</template>
