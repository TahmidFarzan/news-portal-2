<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'

import Layout from '@/pages/layouts/PublicLayout.vue'
import List from '@/components/common/news/List.vue'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'

import {
    faFolder,
    faLocationDot,
} from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faLocationDot, faFolder)

defineOptions({ layout: Layout })

const { location, news } = defineProps({
    location: {
        type: Object,
        required: true,
    },

    news: {
        type: Object,
        required: true,
    },
})

const metaTitle = computed(() => {
    return location?.seo_title || location?.name || ''
})

const metaDescription = computed(() => {
    return location?.seo_brief || location?.brief || ''
})

const metaKeywords = computed(() => {
    if (Array.isArray(location?.seo_keywords)) {
        return location.seo_keywords.join(', ')
    }

    return location?.seo_keywords || ''
})

const hasBrief = computed(() => {
    return Boolean(location?.brief)
})

const hasCoordinates = computed(() => {
    return (
        location?.latitude !== null &&
        location?.latitude !== undefined &&
        location?.latitude !== '' &&
        location?.longitude !== null &&
        location?.longitude !== undefined &&
        location?.longitude !== ''
    )
})

const googleMapQuery = computed(() => {
    return `${location?.latitude},${location?.longitude}`
})

const googleMapEmbedUrl = computed(() => {
    return `https://www.google.com/maps?q=${encodeURIComponent(googleMapQuery.value)}&z=14&output=embed`
})

const googleMapUrl = computed(() => {
    return `https://www.google.com/maps?q=${encodeURIComponent(googleMapQuery.value)}`
})
</script>

<template>

    <Head :title="location?.name || 'Location'">
        <link v-if="location?.public_url" rel="canonical" :href="location?.public_url || ''" />

        <meta v-if="metaTitle" name="title" :content="metaTitle" />

        <meta v-if="metaDescription" name="description" :content="metaDescription" />

        <meta v-if="metaKeywords" name="keywords" :content="metaKeywords" />
    </Head>

    <div class="space-y-6">
        <section class="grid grid-cols-1 items-center gap-5 md:grid-cols-12">
            <div class="md:col-span-3 lg:col-span-2">
                <div class="flex h-28 w-28 items-center justify-center rounded-2xl bg-blue-50 p-4 sm:h-32 sm:w-32">
                    <img :src="'/uploads/images/logo/location.png'" :alt="location?.name || 'Location image'"
                        class="h-full w-full object-contain" loading="lazy" />
                </div>
            </div>

            <div class="md:col-span-9 lg:col-span-10">
                <div :class="[
                    hasCoordinates
                        ? 'grid grid-cols-1 items-start gap-5 lg:grid-cols-12'
                        : 'space-y-2',
                ]">
                    <div :class="[
                        hasCoordinates
                            ? 'space-y-2 lg:col-span-7'
                            : 'space-y-2',
                    ]">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">
                            Location
                        </p>

                        <div v-if="location?.parent"
                            class="flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                            <a :href="location?.parent?.public_url" title="Parent Location"
                                class="inline-flex min-w-0 items-center gap-1 transition duration-300 hover:text-red-600">
                                <FontAwesomeIcon icon="location-dot" class="shrink-0" />

                                <span class="truncate">
                                    {{ location?.parent?.name }}
                                </span>
                            </a>
                        </div>

                        <h1 class="text-2xl font-bold tracking-tight text-gray-950 sm:text-3xl">
                            {{ location?.name }}
                        </h1>

                        <p v-if="hasBrief" class="max-w-3xl text-sm leading-7 text-gray-600 sm:text-base">
                            {{ location?.brief }}
                        </p>

                        <div v-if="location?.has_descendants && location?.children?.length"
                            class="flex flex-wrap items-center gap-2 pt-1">
                            <a v-for="child in location.children" :key="child.id || child.slug" :href="child.public_url"
                                :title="child.name"
                                class="inline-flex min-w-0 items-center gap-1 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-600 shadow-sm transition duration-300 hover:border-red-200 hover:bg-red-50 hover:text-red-600">
                                <FontAwesomeIcon icon="location-dot" class="shrink-0" />

                                <span class="truncate">
                                    {{ child.name }}
                                </span>
                            </a>
                        </div>
                    </div>

                    <div v-if="hasCoordinates" class="lg:col-span-5">
                        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-gray-100 shadow-sm">
                            <iframe :src="googleMapEmbedUrl" :title="`${location?.name || 'Location'} map`"
                                class="h-64 w-full border-0 sm:h-72 lg:h-80" loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>

                        <a :href="googleMapUrl" target="_blank" rel="noopener noreferrer"
                            class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-blue-600 transition duration-300 hover:text-red-600">
                            <FontAwesomeIcon icon="location-dot" class="shrink-0" />
                            Open in Google Maps
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <div class="border-t border-gray-200"></div>

        <List :news="news" pagination-type="Cursor" />
    </div>
</template>
