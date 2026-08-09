<script setup>
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    toRefs,
    watch,
} from 'vue'

import { fetchFromApi } from '@/composables/useApiClient'
import { apiCacheKey, apiCacheTTL } from '@/composables/useApiCache'
import { adTypes, adPositions } from '@/composables/useGoogleAdsense'

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

const appEnv = import.meta.env.VITE_APP_ENV

const ads = ref([])
const adRefs = ref([])
const wrapperRef = ref(null)

const adsLoaded = ref(false)
const adsChecked = ref(false)

let observer = null
let resizeObserver = null
let statusCheckTimer = null

const isProduction = computed(() => {
    return appEnv === 'production'
})

const isTestEnvironment = computed(() => {
    return !isProduction.value
})

const hasConfiguredAds = computed(() => {
    return ads.value.length > 0
})

const hasFilledAds = computed(() => {
    return ads.value.some((ad) => ad.status === 'filled')
})

const shouldShow = computed(() => {
    if (isTestEnvironment.value) {
        return true
    }

    return hasFilledAds.value
})

const wrapperClasses = computed(() => {
    return [
        'relative mx-auto my-4 w-full text-center',
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

const normalizeRows = (response) => {
    const rows = Array.isArray(response)
        ? response
        : response?.data ?? response?.items ?? []

    return rows
        .filter((row) => {
            return row?.client_id && row?.slot_id
        })
        .map((row) => ({
            ...row,
            status: null,
        }))
}

const getCacheParamsKey = (params = {}) => {
    return new URLSearchParams(
        Object.entries(params)
            .filter(([, value]) => value !== undefined && value !== null)
            .sort(([a], [b]) => a.localeCompare(b))
    ).toString()
}

const fetchAds = async () => {
    const apiUrl = route('site.google-adsenses')

    const params = {
        type: type.value,
        position: position.value,
    }

    const cacheParamsKey = getCacheParamsKey(params)

    const response = await fetchFromApi(
        apiUrl,
        params,
        {
            key: `${apiCacheKey.API_SITE_GOOGLE_ADSENSE}:${apiUrl}:${cacheParamsKey}`,
            ttl: apiCacheTTL.GOOGLE_ADSENSE,
        }
    )

    ads.value = normalizeRows(response)
    adRefs.value = []
    adsLoaded.value = false
    adsChecked.value = false

    clearStatusCheckTimer()

    await nextTick()

    if (!isProduction.value) {
        return
    }

    if (!hasConfiguredAds.value) {
        return
    }

    observeAds()
}

const pushAdsense = async () => {
    if (!isProduction.value) {
        return
    }

    if (adsLoaded.value || !hasConfiguredAds.value) {
        return
    }

    await nextTick()

    if (!wrapperRef.value) {
        return
    }

    const width = wrapperRef.value.clientWidth

    if (width <= 0) {
        return
    }

    const slots = adRefs.value.filter(Boolean)

    if (!slots.length) {
        return
    }

    adsLoaded.value = true

    try {
        window.adsbygoogle = window.adsbygoogle || []

        slots.forEach(() => {
            window.adsbygoogle.push({})
        })

        startAdStatusCheck()
    } catch (error) {
        console.warn('Google Adsense error:', error)

        adsLoaded.value = false

        ads.value.forEach((ad) => {
            ad.status = 'error'
        })

        adsChecked.value = true
    }
}

const checkAdsStatus = () => {
    if (!adRefs.value.length) {
        return false
    }

    let hasPending = false

    adRefs.value.forEach((adRef, index) => {
        if (!adRef || !ads.value[index]) {
            return
        }

        const status = adRef.getAttribute('data-ad-status')

        if (status === 'filled') {
            ads.value[index].status = 'filled'
        } else if (status === 'unfilled') {
            ads.value[index].status = 'unfilled'
        } else {
            hasPending = true
        }
    })

    if (hasPending) {
        return false
    }

    adsChecked.value = true

    clearStatusCheckTimer()

    return true
}

const startAdStatusCheck = () => {
    clearStatusCheckTimer()

    let attempts = 0
    const maxAttempts = 20

    statusCheckTimer = window.setInterval(() => {
        attempts++

        const resolved = checkAdsStatus()

        if (resolved || attempts >= maxAttempts) {
            clearStatusCheckTimer()

            if (!resolved) {
                ads.value.forEach((ad, index) => {
                    const adRef = adRefs.value[index]

                    if (!adRef) {
                        return
                    }

                    const status = adRef.getAttribute('data-ad-status')

                    ad.status = status === 'filled'
                        ? 'filled'
                        : 'unfilled'
                })

                adsChecked.value = true
            }
        }
    }, 500)
}

const clearStatusCheckTimer = () => {
    if (statusCheckTimer) {
        window.clearInterval(statusCheckTimer)
        statusCheckTimer = null
    }
}

const observeAds = () => {
    if (observer) {
        observer.disconnect()
        observer = null
    }

    if (!wrapperRef.value || !hasConfiguredAds.value) {
        return
    }

    observer = new IntersectionObserver(
        (entries) => {
            if (!entries[0]?.isIntersecting) {
                return
            }

            pushAdsense()

            observer.disconnect()
            observer = null
        },
        {
            rootMargin: '100px',
        }
    )

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

    resizeObserver = new ResizeObserver(() => {
        if (
            isProduction.value &&
            !adsLoaded.value &&
            hasConfiguredAds.value
        ) {
            observeAds()
        }
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

    clearStatusCheckTimer()
})
</script>

<template>
    <section v-if="shouldShow" ref="wrapperRef" :class="wrapperClasses">
        <span v-if="showLabel" class="mb-1 block text-[10px] font-medium uppercase tracking-wider text-gray-500">
            {{ label }}
        </span>

        <template v-if="isTestEnvironment">
            <div class="mx-auto w-full max-w-[815px]">
                <div class="border border-dashed border-gray-300 bg-gray-50 px-4 py-5 text-center">
                    <div class="text-sm font-semibold text-gray-600">
                        TEST AD
                    </div>

                    <div class="mt-1 text-xs text-gray-500">
                        Google AdSense unavailable
                    </div>

                    <div class="mt-1 text-xs text-gray-400">
                        Environment: {{ appEnv }}
                    </div>
                </div>
            </div>
        </template>

        <template v-else>
            <div v-for="(ad, index) in ads" :key="getAdKey(ad)" class="w-full text-center" :class="{
                'hidden': adsChecked && ad.status !== 'filled'
            }">
                <ins ref="adRefs" class="adsbygoogle" :style="isFullWidthResponsive(ad)
                    ? 'display:block;width:100%'
                    : 'display:block;width:100%'
                    " :data-ad-client="ad.client_id" :data-ad-slot="ad.slot_id" :data-ad-format="isFullWidthResponsive(ad)
                        ? 'auto'
                        : null
                        " :data-full-width-responsive="isFullWidthResponsive(ad)
                            ? 'true'
                            : null
                            "></ins>
            </div>
        </template>
    </section>
</template>
