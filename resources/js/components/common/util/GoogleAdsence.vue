<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, toRefs, watch } from 'vue'

import { fetchFromApi } from '@/composables/useSystemApi'
import { smartCacheKey, smartCacheTTL } from '@/composables/useSmartCache'
import { adTypes, adPositions } from '@/composables/useGoogleAdsence'

const props = defineProps({
    type: {
        type: String,
        default: adTypes.SECTION,
    },
    position: {
        type: String,
        default: adPositions.BETWEEN,
    },
    label: {
        type: String,
        default: 'Ad',
    },
    showLabel: {
        type: Boolean,
        default: true,
    },
    class: {
        type: [String, Array, Object],
        default: '',
    },
})

const {
    type,
    position,
    label,
    showLabel,
} = toRefs(props)

const ads = ref([])
const adRefs = ref([])
const wrapperRef = ref(null)
const containerWidth = ref(0)

let observer = null
let resizeObserver = null
let adsLoaded = false

const hasAds = computed(() => {
    return ads.value.length > 0
})

const wrapperClasses = computed(() => {
    return [
        'relative mx-auto my-6 w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-5 text-center',
        props.class,
    ]
})

const truthyValues = [true, 1, '1', 'true', 'yes', 'on']

const isFullWidthResponsive = (ad) => {
    return truthyValues.includes(ad?.use_full_width_responsive)
}

const getAdKey = (ad) => {
    return ad?.id ?? `${ad?.client_id}-${ad?.slot_id}`
}

const updateContainerWidth = () => {
    if (!wrapperRef.value) return

    containerWidth.value = wrapperRef.value.clientWidth
}

const getAdStyle = (ad) => {
    if (isFullWidthResponsive(ad)) {
        return 'display:block'
    }

    const width = containerWidth.value

    if (width >= 728) {
        return 'display:inline-block;width:728px;height:90px'
    }

    if (width >= 468) {
        return 'display:inline-block;width:468px;height:60px'
    }

    if (width >= 320) {
        return 'display:inline-block;width:320px;height:100px'
    }

    return `display:inline-block;width:${Math.max(width, 200)}px;height:100px`
}

const normalizeRows = (response) => {
    const rows = Array.isArray(response)
        ? response
        : response?.data ?? response?.items ?? []

    return rows.filter((row) => {
        return row?.client_id && row?.slot_id
    })
}

const getCacheParamsKey = (params = {}) => {
    return new URLSearchParams(
        Object.entries(params)
            .filter(([, value]) => value !== undefined && value !== null)
            .sort(([a], [b]) => a.localeCompare(b))
    ).toString()
}

const fetchAds = async () => {
    const apiUrl = route('site.google-adsences')
    const params = {
        type: type.value,
        position: position.value,
    }
    const cacheParamsKey = getCacheParamsKey(params)

    const response = await fetchFromApi(
        apiUrl,
        params,
        {
            key: `${smartCacheKey.API_SITE_GOOGLE_ADSENCE}:${apiUrl}:${cacheParamsKey}`,
            ttl: smartCacheTTL.GOOGLE_ADSENCE,
        }
    )

    ads.value = normalizeRows(response)
    adsLoaded = false

    await nextTick()

    updateContainerWidth()
    observeAds()
}

const pushAdsense = async () => {
    if (adsLoaded || !hasAds.value) return

    adsLoaded = true

    await nextTick()

    try {
        window.adsbygoogle = window.adsbygoogle || []

        adRefs.value.forEach(() => {
            window.adsbygoogle.push({})
        })
    } catch (error) {
        console.warn('Google Adsense error:', error)
    }
}

const observeAds = () => {
    if (observer) {
        observer.disconnect()
        observer = null
    }

    if (!wrapperRef.value || !hasAds.value) return

    observer = new IntersectionObserver((entries) => {
        if (!entries[0]?.isIntersecting) return

        pushAdsense()

        observer.disconnect()
        observer = null
    }, {
        rootMargin: '200px',
    })

    observer.observe(wrapperRef.value)
}

watch(
    [type, position],
    async () => {
        await fetchAds()
    }
)

onMounted(async () => {
    await fetchAds()

    await nextTick()

    updateContainerWidth()

    resizeObserver = new ResizeObserver(() => {
        updateContainerWidth()
    })

    if (wrapperRef.value) {
        resizeObserver.observe(wrapperRef.value)
    }
})

onBeforeUnmount(() => {
    if (observer) {
        observer.disconnect()
        observer = null
    }

    if (resizeObserver) {
        resizeObserver.disconnect()
        resizeObserver = null
    }
})
</script>

<template>
    <section v-if="hasAds" ref="wrapperRef" :class="wrapperClasses">
        <span v-if="showLabel"
            class="absolute -top-2 left-3 bg-gray-50 px-2 text-[10px] font-medium uppercase tracking-wider text-gray-500">
            {{ label }}
        </span>

        <div class="flex w-full flex-col items-center gap-4">
            <div v-for="ad in ads" :key="getAdKey(ad)" class="flex w-full justify-center overflow-hidden">
                <ins ref="adRefs" class="adsbygoogle" :style="getAdStyle(ad)" :data-ad-client="ad.client_id"
                    :data-ad-slot="ad.slot_id" :data-ad-format="isFullWidthResponsive(ad) ? 'auto' : null"
                    :data-full-width-responsive="isFullWidthResponsive(ad) ? 'true' : null"></ins>
            </div>
        </div>
    </section>
</template>
