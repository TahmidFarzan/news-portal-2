<script setup>
import { computed } from 'vue'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faRightLong } from '@fortawesome/free-solid-svg-icons'

import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faRightLong)

const { t } = useTranslate()

const { events } = defineProps({
    events: {
        type: [Array, Object],
        default: () => [],
    },
})

const normalizeEvents = (events) => {
    if (Array.isArray(events)) {
        return events
    }

    return events?.data ?? []
}

const getEventImageUrl = (event, type = 'desktop') => {
    const image = type === 'mobile'
        ? event?.mobile_banner_image
        : event?.desktop_banner_image

    return image?.media_url ?? image?.original_url ?? null
}

const eventItems = computed(() => {
    return normalizeEvents(events).filter((event) => {
        return getEventImageUrl(event, 'mobile') || getEventImageUrl(event, 'desktop')
    })
})
</script>

<template>
    <div v-if="eventItems.length" class="space-y-3 rounded-2xl border border-slate-200 p-2">
        <div v-for="(event, index) in eventItems" :key="event?.id || event?.slug || index"
            class="overflow-hidden rounded-2xl">
            <img :src="getEventImageUrl(event, 'mobile') || getEventImageUrl(event, 'desktop')" :alt="event?.name || ''"
                class="block h-auto w-full object-cover md:hidden" loading="lazy" />

            <img :src="getEventImageUrl(event, 'desktop') || getEventImageUrl(event, 'mobile')" :alt="event?.name || ''"
                class="hidden h-auto w-full object-cover md:block" loading="lazy" />

            <div class="mt-3 flex justify-center">
                <a :href="event?.public_url || '#'"
                    class="group inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-blue-600 to-sky-500 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 transition-all duration-300 hover:-translate-y-0.5 hover:from-blue-700 hover:to-sky-600 hover:shadow-xl hover:shadow-blue-500/40">
                    <FontAwesomeIcon icon="right-long"
                        class="transition-transform duration-300 group-hover:translate-x-1" />

                    {{ t('components.common.pages.event_banners.labels.read_more') }}
                </a>
            </div>
        </div>
    </div>
</template>
