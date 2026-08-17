<script setup>
import {
    computed,
    getCurrentInstance,
    onBeforeUnmount,
    ref,
    watch,
} from 'vue'

import { fetchFromApi } from '@/composables/useApiClient'
import {
    apiCacheKey,
    apiCacheTTL,
} from '@/composables/useApiCache'
import {
    adPages,
    adTypes,
} from '@/composables/useGoogleAd'

import GoogleAdBanner from './GoogleAdBanner.vue'
import GoogleAdPopup from './GoogleAdPopup.vue'

const {
    page,
    type,
    placement = null,
    showLabel = true,
    class: customClass = '',
} = defineProps({
    page: {
        type: String,
        default: adPages.HOME,
    },

    type: {
        type: String,
        default: adTypes.SECTION,
    },

    placement: {
        type: [String, Number],
        default: null,
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

const instance = getCurrentInstance()

const componentId =
    instance?.uid ??
    Math.random().toString(36).slice(2)

const currentUrl = window.location.href

const AD_SLOT_CACHE_TTL = 120 * 1000

const ads = ref([])
const requestVersion = ref(0)

const isPopupType = computed(() => {
    return type === adTypes.POPUP
})

const hasConfiguredAds = computed(() => {
    return ads.value.length > 0
})

const getGoogleAdSlotCache = () => {
    window.__googleAdSlotCache =
        window.__googleAdSlotCache || {}

    const now = Date.now()

    Object.keys(
        window.__googleAdSlotCache
    ).forEach((url) => {
        const cache =
            window.__googleAdSlotCache[url]

        if (
            !cache?.createdAt ||
            now - cache.createdAt >=
            AD_SLOT_CACHE_TTL
        ) {
            delete window.__googleAdSlotCache[
                url
            ]
        }
    })

    if (
        !window.__googleAdSlotCache[
        currentUrl
        ]
    ) {
        window.__googleAdSlotCache[
            currentUrl
        ] = {
            createdAt: now,
            gptSlotIds: {},
            slotElementIds: {},
        }
    }

    return window.__googleAdSlotCache[
        currentUrl
    ]
}

const registerCachedSlot = (
    gptSlotId,
    slotElementId
) => {
    if (!gptSlotId || !slotElementId) {
        return
    }

    const cache = getGoogleAdSlotCache()

    cache.gptSlotIds[gptSlotId] =
        (cache.gptSlotIds[gptSlotId] || 0) + 1

    cache.slotElementIds[slotElementId] = true
    cache.createdAt = Date.now()
}

const createSlotElementId = (
    ad,
    index
) => {
    const baseId =
        ad?.gpt_slot_id ??
        `google-ad-${ad?.id ?? index}`

    const cache = getGoogleAdSlotCache()

    if (!cache.gptSlotIds[baseId]) {
        return baseId
    }

    let slotElementId =
        `${baseId}-${componentId}-${index}`

    let duplicateIndex = 1

    while (
        cache.slotElementIds[slotElementId]
    ) {
        slotElementId =
            `${baseId}-${componentId}-${index}-${duplicateIndex}`

        duplicateIndex++
    }

    return slotElementId
}

const getCacheParamsKey = (
    params = {}
) => {
    return new URLSearchParams(
        Object.entries(params)
            .filter(([, value]) => {
                return (
                    value !== undefined &&
                    value !== null &&
                    value !== ''
                )
            })
            .sort(([a], [b]) => {
                return a.localeCompare(b)
            })
    ).toString()
}

const normalizeRows = (response) => {
    const rows = Array.isArray(response)
        ? response
        : response?.data ??
        response?.items ??
        []

    return rows
        .filter((row) => {
            return row?.ad_unit_code
        })
        .map((row, index) => {
            const slotElementId =
                createSlotElementId(
                    row,
                    index
                )

            registerCachedSlot(
                row?.gpt_slot_id ??
                `google-ad-${row?.id ?? index}`,
                slotElementId
            )

            return {
                ...row,
                slot_element_id:
                    slotElementId,
                status: null,
            }
        })
}

const fetchAds = async () => {
    const version =
        requestVersion.value + 1

    requestVersion.value = version
    ads.value = []

    const apiUrl =
        route('site.google-ads')

    const params = {
        page,
        type,
    }

    if (
        placement !== null &&
        placement !== undefined &&
        placement !== ''
    ) {
        params.placement = placement
    }

    const cacheParamsKey =
        getCacheParamsKey(params)

    try {
        const response =
            await fetchFromApi(
                apiUrl,
                params,
                {
                    key:
                        `${apiCacheKey.API_SITE_GOOGLE_AD}:${apiUrl}:${cacheParamsKey}`,
                    ttl:
                        apiCacheTTL.GOOGLE_AD,
                }
            )

        if (
            version !==
            requestVersion.value
        ) {
            return
        }

        ads.value =
            normalizeRows(response)
    } catch (error) {
        if (
            version !==
            requestVersion.value
        ) {
            return
        }

        console.warn(
            'Failed to fetch Google Ads:',
            error
        )

        ads.value = []
    }
}

const handleAdStatus = (
    payload
) => {
    if (
        !payload?.slotElementId ||
        !payload?.status
    ) {
        return
    }

    const ad = ads.value.find(
        (item) => {
            return (
                item.slot_element_id ===
                payload.slotElementId
            )
        }
    )

    if (!ad) {
        return
    }

    ad.status = payload.status
}

watch(
    () => [
        page,
        type,
        placement,
    ],
    async () => {
        await fetchAds()
    },
    {
        immediate: true,
    }
)

onBeforeUnmount(() => {
    requestVersion.value++
})
</script>

<template>
    <GoogleAdPopup v-if="
        isPopupType &&
        hasConfiguredAds
    " :ads="ads" :show-label="showLabel" :custom-class="customClass" @status="handleAdStatus" />

    <GoogleAdBanner v-else-if="
        !isPopupType &&
        hasConfiguredAds
    " :ads="ads" :show-label="showLabel" :custom-class="customClass" @status="handleAdStatus" />
</template>
