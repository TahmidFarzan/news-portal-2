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
import {
    apiCacheKey,
    apiCacheTTL,
} from '@/composables/useApiCache'
import {
    adPages,
    adTypes,
    defaultAdSizes,
} from '@/composables/useGoogleAd'
import { useTranslate } from '@/composables/useTranslate'

const { t } = useTranslate()

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
const DESKTOP_BREAKPOINT = 992
const DESKTOP_AD_MIN_WIDTH = 728

const ads = ref([])
const wrapperRef = ref(null)

const adsLoaded = ref(false)
const isDisplaying = ref(false)
const popupIndex = ref(0)

let observer = null
let resizeObserver = null

const isPopupType = computed(() => {
    return type === adTypes.POPUP
})

const hasConfiguredAds = computed(() => {
    return ads.value.length > 0
})

const hasVisibleAds = computed(() => {
    return ads.value.some((ad) => {
        return ad.status === 'filled'
    })
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

const getGoogleTagState = () => {
    window.__googleAdState =
        window.__googleAdState || {
            initialized: false,
            slotEventsInitialized: false,
            slots: {},
        }

    return window.__googleAdState
}

const getAdKey = (ad, index) => {
    return (
        ad?.slot_element_id ??
        ad?.id ??
        ad?.gpt_slot_id ??
        ad?.slug ??
        index
    )
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

                if (
                    width > 0 &&
                    height > 0
                ) {
                    return [
                        width,
                        height,
                    ]
                }
            }

            if (
                size &&
                typeof size === 'object'
            ) {
                const width = Number(
                    size.width
                )

                const height = Number(
                    size.height
                )

                if (
                    width > 0 &&
                    height > 0
                ) {
                    return [
                        width,
                        height,
                    ]
                }
            }

            return null
        })
        .filter(Boolean)
}

const normalizeAdSizes = (ad) => {
    const adSizes = normalizeSizes(
        ad?.ad_sizes
    )

    if (adSizes.length) {
        return adSizes
    }

    return normalizeSizes(
        defaultAdSizes
    )
}

const createSizeMapping = (ad) => {
    const sizes = normalizeAdSizes(ad)

    if (!sizes.length) {
        return null
    }

    if (sizes.length === 1) {
        return window.googletag
            .sizeMapping()
            .addSize([0, 0], sizes)
            .build()
    }

    const desktopSizes = sizes.filter(
        ([width]) => {
            return (
                width >=
                DESKTOP_AD_MIN_WIDTH
            )
        }
    )

    const mobileSizes = sizes.filter(
        ([width]) => {
            return (
                width <
                DESKTOP_AD_MIN_WIDTH
            )
        }
    )

    const sizeMapping =
        window.googletag.sizeMapping()

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

    if (
        !desktopSizes.length &&
        mobileSizes.length
    ) {
        sizeMapping.addSize(
            [DESKTOP_BREAKPOINT, 0],
            mobileSizes
        )
    }

    if (
        !mobileSizes.length &&
        desktopSizes.length
    ) {
        sizeMapping.addSize(
            [0, 0],
            desktopSizes
        )
    }

    return sizeMapping.build()
}

const getCacheParamsKey = (
    params = {}
) => {
    return new URLSearchParams(
        Object.entries(params)
            .filter(([, value]) => {
                return (
                    value !== undefined &&
                    value !== null
                )
            })
            .sort(([a], [b]) => {
                return a.localeCompare(b)
            })
    ).toString()
}

const updateAdStatus = (
    slotId,
    status
) => {
    const googleState =
        getGoogleTagState()

    const registeredSlot =
        googleState.slots[slotId]

    if (!registeredSlot?.ad) {
        return
    }

    registeredSlot.ad.status = status
}

const registerAdSlot = (
    slot,
    ad
) => {
    if (!slot || !ad) {
        return
    }

    const slotId =
        slot.getSlotElementId()

    if (!slotId) {
        return
    }

    const googleState =
        getGoogleTagState()

    googleState.slots[slotId] = {
        ad,
        componentId,
    }
}

const unregisterAdSlot = (ad) => {
    if (!ad) {
        return
    }

    const googleState =
        getGoogleTagState()

    if (ad.slot) {
        const slotId =
            ad.slot.getSlotElementId()

        if (slotId) {
            delete googleState.slots[
                slotId
            ]
        }
    }

    if (ad.slot_element_id) {
        delete googleState.slots[
            ad.slot_element_id
        ]
    }
}

const unregisterAdSlots = () => {
    ads.value.forEach((ad) => {
        unregisterAdSlot(ad)
    })
}

const getExistingSlot = (slotId) => {
    if (
        !slotId ||
        !window.googletag?.pubads
    ) {
        return null
    }

    return (
        window.googletag
            .pubads()
            .getSlots()
            .find((slot) => {
                return (
                    slot.getSlotElementId() ===
                    slotId
                )
            }) ?? null
    )
}

const destroyOwnedSlots = () => {
    return new Promise((resolve) => {
        const googleState =
            getGoogleTagState()

        const slots = ads.value
            .map((ad) => ad.slot)
            .filter(Boolean)

        unregisterAdSlots()

        if (
            !slots.length ||
            !window.googletag?.cmd ||
            !googleState.initialized
        ) {
            resolve()
            return
        }

        window.googletag.cmd.push(() => {
            window.googletag.destroySlots(
                slots
            )

            resolve()
        })
    })
}

const resetAds = () => {
    adsLoaded.value = false
    isDisplaying.value = false
    popupIndex.value = 0
}

const setupSlotEvents = () => {
    const googleState =
        getGoogleTagState()

    if (
        googleState.slotEventsInitialized
    ) {
        return
    }

    window.googletag
        .pubads()
        .addEventListener(
            'slotRenderEnded',
            (event) => {
                const slotId =
                    event.slot.getSlotElementId()

                updateAdStatus(
                    slotId,
                    event.isEmpty
                        ? 'unfilled'
                        : 'filled'
                )
            }
        )

    window.googletag
        .pubads()
        .addEventListener(
            'slotOnload',
            (event) => {
                const slotId =
                    event.slot.getSlotElementId()

                updateAdStatus(
                    slotId,
                    'filled'
                )
            }
        )

    googleState.slotEventsInitialized = true
}

const initializeGoogleTag = () => {
    return new Promise((resolve) => {
        window.googletag =
            window.googletag || {
                cmd: [],
            }

        window.googletag.cmd.push(() => {
            const googleState =
                getGoogleTagState()

            if (
                !googleState.initialized
            ) {
                window.googletag.setConfig({
                    singleRequest: true,
                    collapseDiv: 'ON_NO_FILL',
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

    const existingSlot =
        getExistingSlot(
            ad.slot_element_id
        )

    if (existingSlot) {
        return existingSlot
    }

    const sizes = normalizeAdSizes(ad)

    if (!sizes.length) {
        return null
    }

    const slot =
        window.googletag.defineSlot(
            ad.ad_unit_code,
            sizes,
            ad.slot_element_id
        )

    if (!slot) {
        return null
    }

    const sizeMapping =
        createSizeMapping(ad)

    if (sizeMapping) {
        slot.defineSizeMapping(
            sizeMapping
        )
    }

    return slot.addService(
        window.googletag.pubads()
    )
}

const pushGoogleAds = async () => {
    if (
        adsLoaded.value ||
        isDisplaying.value ||
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

    isDisplaying.value = true

    try {
        await initializeGoogleTag()

        await new Promise((resolve) => {
            window.googletag.cmd.push(() => {
                ads.value.forEach((ad) => {
                    if (ad.displayed) {
                        return
                    }

                    const slot =
                        defineOrGetSlot(ad)

                    if (!slot) {
                        ad.status = 'error'
                        return
                    }

                    ad.slot = slot
                    ad.displayed = true

                    registerAdSlot(
                        slot,
                        ad
                    )

                    window.googletag.display(
                        ad.slot_element_id
                    )
                })

                adsLoaded.value = true

                resolve()
            })
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
    } finally {
        isDisplaying.value = false
    }
}

const loadNextPopupAd = () => {
    if (!isPopupType.value) {
        return
    }

    popupIndex.value++

    if (
        popupIndex.value >=
        ads.value.length
    ) {
        return
    }

    loadPopupAd()
}

const loadPopupAd = async () => {
    if (
        isDisplaying.value ||
        !hasConfiguredAds.value ||
        popupIndex.value >= ads.value.length
    ) {
        return
    }

    const ad =
        ads.value[popupIndex.value]

    if (
        !ad ||
        ad.displayed ||
        !ad.ad_unit_code
    ) {
        return
    }

    isDisplaying.value = true

    try {
        await initializeGoogleTag()

        await new Promise((resolve) => {
            window.googletag.cmd.push(() => {
                const slot =
                    window.googletag
                        .defineOutOfPageSlot(
                            ad.ad_unit_code,
                            window.googletag.enums
                                .OutOfPageFormat
                                .INTERSTITIAL
                        )

                if (!slot) {
                    ad.status = 'error'
                    resolve()
                    return
                }

                slot.addService(
                    window.googletag.pubads()
                )

                ad.slot = slot
                ad.displayed = true

                registerAdSlot(
                    slot,
                    ad
                )

                window.googletag.display(
                    slot
                )

                resolve()
            })
        })
    } catch (error) {
        console.warn(
            'Google Ad Manager popup error:',
            error
        )

        ad.status = 'error'

        loadNextPopupAd()
    } finally {
        isDisplaying.value = false
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
        isPopupType.value ||
        adsLoaded.value ||
        isDisplaying.value
    ) {
        return
    }

    observer = new IntersectionObserver(
        (entries) => {
            if (
                !entries.some((entry) => {
                    return entry.isIntersecting
                })
            ) {
                return
            }

            observer?.disconnect()
            observer = null

            pushGoogleAds()
        },
        {
            rootMargin: '100px',
        }
    )

    observer.observe(wrapperRef.value)
}

const fetchAds = async () => {
    await destroyOwnedSlots()

    resetAds()

    const apiUrl =
        route('site.google-ads')

    const params = {
        page,
        type,
    }

    if (
        !isPopupType.value &&
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

        ads.value =
            normalizeRows(response)

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
    }
}

watch(
    () => [
        page,
        type,
        placement,
    ],
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

    resizeObserver =
        new ResizeObserver(() => {
            if (
                !adsLoaded.value &&
                !isDisplaying.value &&
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
    <section v-if="hasConfiguredAds" ref="wrapperRef" :class="[
        'relative mx-auto flex w-full flex-col items-center text-center',
        {
            'my-4':
                !isPopupType &&
                hasVisibleAds,

            'hidden':
                !isPopupType &&
                adsLoaded &&
                !hasVisibleAds,
        },
        customClass,
    ]">
        <span v-if="
            !isPopupType &&
            showLabel &&
            hasVisibleAds
        " class="mb-1 block text-[10px] font-medium uppercase tracking-wider text-gray-500">
            {{ t('common.labels.ad') }}
        </span>

        <div v-for="(ad, index) in ads" :key="getAdKey(ad, index)" v-show="isPopupType ||
            ad.status === 'filled'
            " class="flex w-full flex-col items-center justify-center text-center">
            <div v-if="ad.slot_element_id" :id="ad.slot_element_id" class="mx-auto flex max-w-full justify-center">
            </div>
        </div>
    </section>
</template>
