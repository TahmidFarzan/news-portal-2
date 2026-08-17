<script setup>
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue'

import {
    defaultAdSizes,
} from '@/composables/useGoogleAd'
import { useTranslate } from '@/composables/useTranslate'

const { t } = useTranslate()

const { ads, showLabel, customClass } = defineProps({
    ads: {
        type: Array,
        default: () => [],
    },

    showLabel: {
        type: Boolean,
        default: true,
    },

    customClass: {
        type: [String, Array, Object],
        default: '',
    },
})

const emit = defineEmits([
    'status',
])

const DESKTOP_BREAKPOINT = 992
const DESKTOP_AD_MIN_WIDTH = 728

const wrapperRef = ref(null)
const localAds = ref([])
const isDisplaying = ref(false)

let observer = null
let resizeObserver = null

const hasConfiguredAds = computed(() => {
    return localAds.value.length > 0
})

const hasVisibleAds = computed(() => {
    return localAds.value.some((ad) => {
        return ad.status === 'filled'
    })
})

const allAdsFinished = computed(() => {
    return (
        localAds.value.length > 0 &&
        localAds.value.every((ad) => {
            return (
                ad.status === 'filled' ||
                ad.status === 'unfilled' ||
                ad.status === 'error'
            )
        })
    )
})

const getGoogleTagState = () => {
    window.__googleAdState =
        window.__googleAdState || {
            initialized: false,
            slotEventsInitialized: false,
            slots: {},
        }

    return window.__googleAdState
}

const emitStatus = (
    ad,
    status
) => {
    if (!ad?.slot_element_id) {
        return
    }

    ad.status = status

    emit('status', {
        slotElementId:
            ad.slot_element_id,
        status,
    })
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

                const registeredSlot =
                    googleState.slots[slotId]

                if (!registeredSlot?.ad) {
                    return
                }

                emitStatus(
                    registeredSlot.ad,
                    event.isEmpty
                        ? 'unfilled'
                        : 'filled'
                )
            }
        )

    googleState.slotEventsInitialized = true
}

const initializeGoogleTag = () => {
    return new Promise((resolve) => {
        if (!window.googletag?.cmd) {
            resolve(false)
            return
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

            resolve(true)
        })
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
    const sizes = normalizeSizes(
        ad?.ad_sizes
    )

    if (sizes.length) {
        return sizes
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

const defineSlot = (ad) => {
    const sizes = normalizeAdSizes(ad)

    if (
        !ad?.ad_unit_code ||
        !ad?.slot_element_id ||
        !sizes.length
    ) {
        return null
    }

    const existingSlot =
        window.googletag
            .pubads()
            .getSlots()
            .find((slot) => {
                return (
                    slot.getSlotElementId() ===
                    ad.slot_element_id
                )
            })

    if (existingSlot) {
        return existingSlot
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

const registerSlot = (
    slot,
    ad
) => {
    const slotId =
        slot.getSlotElementId()

    getGoogleTagState().slots[slotId] = {
        ad,
    }
}

const destroySlots = () => {
    return new Promise((resolve) => {
        const slots = localAds.value
            .map((ad) => ad.slot)
            .filter(Boolean)

        if (
            !slots.length ||
            !window.googletag?.cmd
        ) {
            resolve()
            return
        }

        slots.forEach((slot) => {
            const slotId =
                slot.getSlotElementId()

            delete getGoogleTagState().slots[
                slotId
            ]
        })

        window.googletag.cmd.push(() => {
            window.googletag.destroySlots(
                slots
            )

            resolve()
        })
    })
}

const displayAds = async () => {
    if (
        isDisplaying.value ||
        !hasConfiguredAds.value
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
        const initialized =
            await initializeGoogleTag()

        if (!initialized) {
            return
        }

        await new Promise((resolve) => {
            window.googletag.cmd.push(() => {
                localAds.value.forEach(
                    (ad) => {
                        if (ad.displayed) {
                            return
                        }

                        const slot =
                            defineSlot(ad)

                        if (!slot) {
                            emitStatus(
                                ad,
                                'error'
                            )

                            return
                        }

                        ad.slot = slot
                        ad.displayed = true

                        registerSlot(
                            slot,
                            ad
                        )

                        window.googletag.display(
                            ad.slot_element_id
                        )
                    }
                )

                resolve()
            })
        })
    } catch (error) {
        localAds.value.forEach((ad) => {
            emitStatus(ad, 'error')
        })
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
        !hasConfiguredAds.value
    ) {
        return
    }

    observer = new IntersectionObserver(
        (entries) => {
            const isVisible =
                entries.some((entry) => {
                    return entry.isIntersecting
                })

            if (!isVisible) {
                return
            }

            observer?.disconnect()
            observer = null

            displayAds()
        },
        {
            rootMargin: '100px',
        }
    )

    observer.observe(wrapperRef.value)
}

const syncAds = () => {
    localAds.value = ads.map((ad) => {
        return {
            ...ad,
            status: null,
            slot: null,
            displayed: false,
        }
    })
}

watch(
    () => ads,
    async () => {
        await destroySlots()

        syncAds()

        await nextTick()

        observeAds()
    },
    {
        immediate: true,
        deep: true,
    }
)

onMounted(() => {
    resizeObserver =
        new ResizeObserver(() => {
            if (
                !allAdsFinished.value &&
                !isDisplaying.value
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
    observer?.disconnect()
    resizeObserver?.disconnect()

    destroySlots()
})
</script>

<template>
    <section v-if="
        hasConfiguredAds &&
        (!allAdsFinished ||
            hasVisibleAds)
    " ref="wrapperRef" :class="[
            'relative mx-auto flex w-full flex-col items-center text-center',
            {
                'my-4': hasVisibleAds,
            },
            customClass,
        ]">
        <span v-if="
            showLabel &&
            hasVisibleAds
        " class="mb-1 block text-[10px] font-medium uppercase tracking-wider text-gray-500">
            {{ t('common.labels.ad') }}
        </span>

        <div v-for="(ad, index) in localAds" :key="ad.slot_element_id ??
            ad.id ??
            index
            " v-show="ad.status === 'filled'
                " class="flex w-full flex-col items-center justify-center text-center">
            <div :id="ad.slot_element_id" class="mx-auto flex max-w-full justify-center"></div>
        </div>
    </section>
</template>
