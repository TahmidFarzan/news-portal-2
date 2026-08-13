<script setup>
import {
    computed,
    getCurrentInstance,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue'

import { fetchFromApi } from '@/composables/useApiClient'
import { apiCacheKey, apiCacheTTL } from '@/composables/useApiCache'
import {
    adTypes,
    adPositions,
    defaultAdSizes,
    popupLabel,
} from '@/composables/useGoogleAd'
import { useTranslate } from '@/composables/useTranslate'

const { t } = useTranslate()

const {
    type = adTypes.SECTION,
    position = adPositions.BETWEEN,
    showLabel = true,
    class: customClass = '',
} = defineProps({
    type: {
        type: String,
        default: adTypes.SECTION,
    },

    position: {
        type: String,
        default: adPositions.BETWEEN,
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

const appEnv = import.meta.env.VITE_APP_ENV

const instance = getCurrentInstance()
const componentId = instance?.uid ?? Math.random().toString(36).slice(2)

const currentUrl = window.location.href
const AD_SLOT_CACHE_TTL = 120 * 1000
const DESKTOP_BREAKPOINT = 992
const DESKTOP_AD_MIN_WIDTH = 728

const ads = ref([])
const wrapperRef = ref(null)

const adsLoaded = ref(false)
const adsChecked = ref(false)
const popupIndex = ref(0)

let observer = null
let resizeObserver = null

const getGoogleAdSlotCache = () => {
    window.__googleAdSlotCache = window.__googleAdSlotCache || {}

    const now = Date.now()

    Object.keys(window.__googleAdSlotCache).forEach((url) => {
        const cache = window.__googleAdSlotCache[url]

        if (
            !cache?.createdAt ||
            now - cache.createdAt >= AD_SLOT_CACHE_TTL
        ) {
            delete window.__googleAdSlotCache[url]
        }
    })

    if (!window.__googleAdSlotCache[currentUrl]) {
        window.__googleAdSlotCache[currentUrl] = {
            createdAt: now,
            gptSlotIds: {},
            slotElementIds: {},
        }
    }

    return window.__googleAdSlotCache[currentUrl]
}

const registerCachedSlot = (
    gptSlotId,
    slotElementId
) => {
    if (!gptSlotId || !slotElementId) {
        return
    }

    const cache = getGoogleAdSlotCache()

    cache.gptSlotIds[gptSlotId] = (
        cache.gptSlotIds[gptSlotId] || 0
    ) + 1

    cache.slotElementIds[slotElementId] = true
}

const createSlotElementId = (ad, index) => {
    const baseId = ad?.gpt_slot_id
        ?? `google-ad-${ad?.id ?? index}`

    const cache = getGoogleAdSlotCache()

    if (!cache.gptSlotIds[baseId]) {
        return baseId
    }

    let slotElementId = `${baseId}-${componentId}-${index}`
    let duplicateIndex = 1

    while (cache.slotElementIds[slotElementId]) {
        slotElementId = `${baseId}-${componentId}-${index}-${duplicateIndex}`
        duplicateIndex++
    }

    return slotElementId
}

const getGoogleTagState = () => {
    window.__googleAdState = window.__googleAdState || {
        initialized: false,
        slotEventsInitialized: false,
        slots: {},
    }

    return window.__googleAdState
}

const isProduction = computed(() => {
    return appEnv === 'production'
})

const isTestEnvironment = computed(() => {
    return !isProduction.value
})

const isPopupType = computed(() => {
    return type.includes(popupLabel)
})

const hasConfiguredAds = computed(() => {
    return ads.value.length > 0
})

const shouldShow = computed(() => {
    if (isPopupType.value) {
        return false
    }

    if (hasConfiguredAds.value) {
        return true
    }

    return isTestEnvironment.value
})

const wrapperClasses = computed(() => {
    return [
        'relative mx-auto my-4 flex w-full flex-col items-center text-center',
        customClass,
    ]
})

const getAdKey = (ad, index) => {
    return ad?.slot_element_id
        ?? ad?.id
        ?? ad?.gpt_slot_id
        ?? ad?.slug
        ?? index
}

const normalizeRows = (response) => {
    const rows = Array.isArray(response)
        ? response
        : response?.data ?? response?.items ?? []

    return rows
        .filter((row) => {
            return row?.ad_unit_code
        })
        .map((row, index) => {
            const slotElementId = createSlotElementId(
                row,
                index
            )

            registerCachedSlot(
                row?.gpt_slot_id
                ?? `google-ad-${row?.id ?? index}`,
                slotElementId
            )

            return {
                ...row,
                slot_element_id: slotElementId,
                status: null,
                slot: null,
                displayed: false,
            }
        })
}

const normalizeSizes = (sizes) => {
    if (!Array.isArray(sizes)) {
        return []
    }

    return sizes
        .map((size) => {
            if (
                Array.isArray(size) &&
                size.length >= 2
            ) {
                const width = Number(size[0])
                const height = Number(size[1])

                if (width > 0 && height > 0) {
                    return [width, height]
                }
            }

            if (
                size &&
                typeof size === 'object'
            ) {
                const width = Number(size.width)
                const height = Number(size.height)

                if (width > 0 && height > 0) {
                    return [width, height]
                }
            }

            return null
        })
        .filter(Boolean)
}

const normalizeAdSizes = (ad) => {
    const adSizes = normalizeSizes(ad?.ad_sizes)

    if (adSizes.length) {
        return adSizes
    }

    return normalizeSizes(defaultAdSizes)
}

const createSizeMapping = (ad) => {
    const sizes = normalizeAdSizes(ad)

    if (!sizes.length) {
        return null
    }

    if (sizes.length === 1) {
        return window.googletag
            .sizeMapping()
            .addSize(
                [0, 0],
                sizes
            )
            .build()
    }

    const desktopSizes = sizes.filter(([width]) => {
        return width >= DESKTOP_AD_MIN_WIDTH
    })

    const mobileSizes = sizes.filter(([width]) => {
        return width < DESKTOP_AD_MIN_WIDTH
    })

    const sizeMapping = window.googletag
        .sizeMapping()

    if (desktopSizes.length) {
        sizeMapping.addSize(
            [DESKTOP_BREAKPOINT, 0],
            desktopSizes
        )
    }

    if (mobileSizes.length) {
        sizeMapping.addSize(
            [0, 0],
            mobileSizes
        )
    }

    return sizeMapping.build()
}

const getCacheParamsKey = (params = {}) => {
    return new URLSearchParams(
        Object.entries(params)
            .filter(([, value]) => {
                return value !== undefined && value !== null
            })
            .sort(([a], [b]) => a.localeCompare(b))
    ).toString()
}

const checkAdsStatus = () => {
    if (
        !isPopupType.value &&
        ads.value.length &&
        ads.value.every((ad) => {
            return ad.status !== null
        })
    ) {
        adsChecked.value = true
    }
}

const registerAdSlot = (slot, ad) => {
    if (!slot || !ad) {
        return
    }

    const slotId = slot.getSlotElementId()

    if (!slotId) {
        return
    }

    const googleState = getGoogleTagState()

    googleState.slots[slotId] = {
        ad,
        componentId,
        onStatusChange: checkAdsStatus,
    }
}

const unregisterAdSlot = (ad) => {
    if (!ad) {
        return
    }

    const googleState = getGoogleTagState()

    if (ad.slot) {
        const slotId = ad.slot.getSlotElementId()

        if (slotId) {
            delete googleState.slots[slotId]
        }
    }

    if (ad.slot_element_id) {
        delete googleState.slots[ad.slot_element_id]
    }
}

const unregisterAdSlots = () => {
    ads.value.forEach((ad) => {
        unregisterAdSlot(ad)
    })
}

const getExistingSlot = (slotId) => {
    if (!slotId || !window.googletag?.pubads) {
        return null
    }

    return window.googletag
        .pubads()
        .getSlots()
        .find((slot) => {
            return slot.getSlotElementId() === slotId
        }) ?? null
}

const destroyOwnedSlots = () => {
    const googleState = getGoogleTagState()

    if (
        !window.googletag?.cmd ||
        !googleState.initialized
    ) {
        unregisterAdSlots()
        return
    }

    const slots = ads.value
        .map((ad) => ad.slot)
        .filter(Boolean)

    unregisterAdSlots()

    if (!slots.length) {
        return
    }

    window.googletag.cmd.push(() => {
        window.googletag.destroySlots(slots)
    })
}

const resetAds = () => {
    adsLoaded.value = false
    adsChecked.value = false
    popupIndex.value = 0
}

const setupSlotEvents = () => {
    const googleState = getGoogleTagState()

    if (googleState.slotEventsInitialized) {
        return
    }

    window.googletag
        .pubads()
        .addEventListener('slotRenderEnded', (event) => {
            const slotId = event.slot.getSlotElementId()

            const registeredSlot = googleState.slots[slotId]

            if (!registeredSlot?.ad) {
                return
            }

            registeredSlot.ad.status = event.isEmpty
                ? 'unfilled'
                : 'filled'

            registeredSlot.onStatusChange?.()
        })

    window.googletag
        .pubads()
        .addEventListener('slotOnload', (event) => {
            const slotId = event.slot.getSlotElementId()

            const registeredSlot = googleState.slots[slotId]

            if (!registeredSlot?.ad) {
                return
            }

            registeredSlot.ad.status = 'filled'

            registeredSlot.onStatusChange?.()
        })

    googleState.slotEventsInitialized = true
}

const initializeGoogleTag = () => {
    return new Promise((resolve) => {
        window.googletag = window.googletag || {
            cmd: [],
        }

        window.googletag.cmd.push(() => {
            const googleState = getGoogleTagState()

            if (!googleState.initialized) {
                window.googletag.setConfig({
                    singleRequest: true,
                    collapseDiv: isTestEnvironment.value
                        ? 'DISABLED'
                        : 'ON_NO_FILL',
                })

                window.googletag.enableServices()

                googleState.initialized = true
            }

            setupSlotEvents()

            resolve()
        })
    })
}

const defineOrGetSlot = (ad) => {
    if (
        !ad.slot_element_id ||
        !ad.ad_unit_code
    ) {
        return null
    }

    const existingSlot = getExistingSlot(
        ad.slot_element_id
    )

    if (existingSlot) {
        return existingSlot
    }

    const sizes = normalizeAdSizes(ad)

    if (!sizes.length) {
        return null
    }

    const slot = window.googletag
        .defineSlot(
            ad.ad_unit_code,
            sizes,
            ad.slot_element_id
        )

    if (!slot) {
        return null
    }

    const sizeMapping = createSizeMapping(ad)

    if (sizeMapping) {
        slot.defineSizeMapping(sizeMapping)
    }

    return slot.addService(
        window.googletag.pubads()
    )
}

const pushGoogleAds = async () => {
    if (
        adsLoaded.value ||
        !hasConfiguredAds.value ||
        isPopupType.value
    ) {
        return
    }

    if (
        !wrapperRef.value ||
        wrapperRef.value.clientWidth <= 0
    ) {
        return
    }

    adsLoaded.value = true

    try {
        await initializeGoogleTag()

        window.googletag.cmd.push(() => {
            ads.value.forEach((ad) => {
                if (ad.displayed) {
                    return
                }

                const slot = defineOrGetSlot(ad)

                if (!slot) {
                    ad.status = 'error'
                    return
                }

                ad.slot = slot
                ad.displayed = true

                registerAdSlot(slot, ad)

                window.googletag.display(
                    ad.slot_element_id
                )
            })

            checkAdsStatus()
        })
    } catch (error) {
        console.warn(
            'Google Ad Manager error:',
            error
        )

        adsLoaded.value = false

        ads.value.forEach((ad) => {
            ad.status = 'error'
        })

        adsChecked.value = true
    }
}

const loadNextPopupAd = () => {
    if (!isPopupType.value) {
        return
    }

    popupIndex.value++

    if (popupIndex.value >= ads.value.length) {
        return
    }

    loadPopupAd()
}

const loadPopupAd = async () => {
    if (
        !hasConfiguredAds.value ||
        popupIndex.value >= ads.value.length
    ) {
        return
    }

    const ad = ads.value[popupIndex.value]

    if (!ad || ad.displayed || !ad.ad_unit_code) {
        return
    }

    try {
        await initializeGoogleTag()

        window.googletag.cmd.push(() => {
            const slot = window.googletag
                .defineOutOfPageSlot(
                    ad.ad_unit_code,
                    window.googletag.enums
                        .OutOfPageFormat
                        .INTERSTITIAL
                )

            if (!slot) {
                ad.status = 'error'
                loadNextPopupAd()
                return
            }

            slot.addService(
                window.googletag.pubads()
            )

            ad.slot = slot
            ad.displayed = true

            registerAdSlot(slot, ad)

            window.googletag.display(slot)
        })
    } catch (error) {
        console.warn(
            'Google Ad Manager popup error:',
            error
        )

        ad.status = 'error'

        loadNextPopupAd()
    }
}

const observeAds = () => {
    if (observer) {
        observer.disconnect()
        observer = null
    }

    if (
        !wrapperRef.value ||
        !hasConfiguredAds.value ||
        isPopupType.value
    ) {
        return
    }

    observer = new IntersectionObserver(
        (entries) => {
            if (!entries[0]?.isIntersecting) {
                return
            }

            pushGoogleAds()

            observer?.disconnect()
            observer = null
        },
        {
            rootMargin: '100px',
        }
    )

    observer.observe(wrapperRef.value)
}

const fetchAds = async () => {
    destroyOwnedSlots()
    resetAds()

    const apiUrl = route('site.google-ads')

    const params = {
        type,
        position,
    }

    const cacheParamsKey = getCacheParamsKey(params)

    try {
        const response = await fetchFromApi(
            apiUrl,
            params,
            {
                key: `${apiCacheKey.API_SITE_GOOGLE_AD}:${apiUrl}:${cacheParamsKey}`,
                ttl: apiCacheTTL.GOOGLE_AD,
            }
        )

        ads.value = normalizeRows(response)

        await nextTick()

        if (!hasConfiguredAds.value) {
            return
        }

        if (isPopupType.value) {
            await loadPopupAd()
            return
        }

        observeAds()
    } catch (error) {
        console.warn(
            'Failed to fetch Google Ads:',
            error
        )

        ads.value = []
        adsChecked.value = true
    }
}

watch(
    () => [type, position],
    async () => {
        await fetchAds()
    }
)

onMounted(async () => {
    await fetchAds()

    await nextTick()

    if (isPopupType.value) {
        return
    }

    resizeObserver = new ResizeObserver(() => {
        if (
            !adsLoaded.value &&
            hasConfiguredAds.value
        ) {
            observeAds()
        }
    })

    if (wrapperRef.value) {
        resizeObserver.observe(
            wrapperRef.value
        )
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

    destroyOwnedSlots()
})
</script>

<template>
    <section v-if="shouldShow" ref="wrapperRef" :class="wrapperClasses">
        <span v-if="showLabel" class="mb-1 block text-[10px] font-medium uppercase tracking-wider text-gray-500">
            {{ t('common.labels.ad') }}
        </span>

        <template v-if="
            isTestEnvironment &&
            !hasConfiguredAds
        ">
            <div class="mx-auto flex w-full justify-center">
                <div class="w-full max-w-[815px]">
                    <div class="border border-dashed border-gray-300 bg-gray-50 px-4 py-5 text-center">
                        <div class="text-sm font-semibold text-gray-600">
                            {{ t('common.labels.test') }}
                        </div>

                        <div class="mt-1 text-xs text-gray-500">
                            {{
                                t(
                                    'common.labels.googleAdManagerUnavailable'
                                )
                            }}
                        </div>

                        <div class="mt-1 text-xs text-gray-400">
                            {{ t('common.labels.environment') }}:
                            {{ appEnv }}
                        </div>

                        <div class="mt-1 text-xs text-gray-400">
                            {{ t('common.labels.type') }}:
                            {{ type }}
                        </div>

                        <div class="mt-1 text-xs text-gray-400">
                            {{ t('common.labels.position') }}:
                            {{ position }}
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template v-else>
            <div v-for="(ad, index) in ads" :key="getAdKey(ad, index)"
                class="flex w-full flex-col items-center justify-center text-center" :class="{
                    hidden:
                        isProduction &&
                        adsChecked &&
                        ad.status !== 'filled',
                }">
                <div v-if="ad.slot_element_id" :id="ad.slot_element_id" class="mx-auto flex max-w-full justify-center">
                </div>

                <div v-if="
                    isTestEnvironment &&
                    adsChecked &&
                    ad.status !== 'filled'
                "
                    class="mx-auto mt-2 w-full max-w-[815px] border border-dashed border-gray-300 bg-gray-50 px-4 py-5 text-center">
                    <div class="text-sm font-semibold text-gray-600">
                        {{ t('common.labels.test') }}
                    </div>

                    <div class="mt-1 text-xs text-gray-500">
                        {{ t('common.labels.googleAdMangerNoAds') }}
                    </div>

                    <div class="mt-1 text-xs text-gray-400">
                        {{ t('common.labels.status') }}:
                        {{ ad.status || t('common.labels.pending') }}
                    </div>

                    <div class="mt-1 text-xs text-gray-400">
                        {{ t('common.labels.slot') }}:
                        {{ ad.slot_element_id }}
                    </div>

                    <div class="mt-1 text-xs text-gray-400">
                        {{ t('common.labels.gptSlot') }}:
                        {{ ad.gpt_slot_id }}
                    </div>

                    <div class="mt-1 text-xs text-gray-400">
                        {{ t('common.labels.adUnit') }}:
                        {{ ad.ad_unit_code }}
                    </div>

                    <div class="mt-1 text-xs text-gray-400">
                        {{ t('common.labels.sizes') }}:
                        {{ normalizeAdSizes(ad) }}
                    </div>
                </div>
            </div>
        </template>
    </section>
</template>
