<script setup>
import {
    computed,
    nextTick,
    onBeforeUnmount,
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

const AUTO_CLOSE_TIME = 10000
const DESKTOP_BREAKPOINT = 992
const DESKTOP_AD_MIN_WIDTH = 728

const localAds = ref([])
const currentIndex = ref(0)
const popupVisible = ref(false)
const isDisplaying = ref(false)

let autoCloseTimer = null

const currentAd = computed(() => {
    return (
        localAds.value[
        currentIndex.value
        ] ?? null
    )
})

const clearAutoCloseTimer = () => {
    if (!autoCloseTimer) {
        return
    }

    clearTimeout(autoCloseTimer)

    autoCloseTimer = null
}

const closePopup = () => {
    clearAutoCloseTimer()

    popupVisible.value = false
}

const startAutoCloseTimer = () => {
    clearAutoCloseTimer()

    autoCloseTimer = setTimeout(() => {
        closePopup()
    }, AUTO_CLOSE_TIME)
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

                const ad =
                    registeredSlot.ad

                if (event.isEmpty) {
                    emitStatus(
                        ad,
                        'unfilled'
                    )

                    if (
                        currentAd.value
                            ?.slot_element_id ===
                        slotId
                    ) {
                        closePopup()
                    }

                    return
                }

                emitStatus(
                    ad,
                    'filled'
                )

                if (
                    currentAd.value
                        ?.slot_element_id !==
                    slotId
                ) {
                    return
                }

                popupVisible.value = true

                startAutoCloseTimer()
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
                const width = Number(
                    size[0]
                )

                const height = Number(
                    size[1]
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

const getExistingSlot = (slotElementId) => {
    if (
        !slotElementId ||
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
                    slotElementId
                )
            }) ?? null
    )
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

    const slotElement =
        document.getElementById(
            ad.slot_element_id
        )

    if (!slotElement) {
        return null
    }

    const existingSlot =
        getExistingSlot(
            ad.slot_element_id
        )

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
    if (!slot || !ad) {
        return
    }

    const slotId =
        slot.getSlotElementId()

    if (!slotId) {
        return
    }

    getGoogleTagState().slots[slotId] = {
        ad,
    }
}

const unregisterSlot = (ad) => {
    if (!ad) {
        return
    }

    const googleState =
        getGoogleTagState()

    const slotId =
        ad.slot?.getSlotElementId?.() ??
        ad.slot_element_id

    if (slotId) {
        delete googleState.slots[slotId]
    }
}

const destroySlots = () => {
    return new Promise((resolve) => {
        const slots = localAds.value
            .map((ad) => ad.slot)
            .filter(Boolean)

        localAds.value.forEach((ad) => {
            unregisterSlot(ad)
        })

        if (
            !slots.length ||
            !window.googletag?.cmd
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

const loadCurrentAd = async () => {
    const ad = currentAd.value

    if (
        !ad ||
        isDisplaying.value ||
        ad.displayed
    ) {
        return
    }

    isDisplaying.value = true

    try {
        await nextTick()

        const slotElement =
            document.getElementById(
                ad.slot_element_id
            )

        if (!slotElement) {
            emitStatus(ad, 'error')

            return
        }

        const initialized =
            await initializeGoogleTag()

        if (!initialized) {
            emitStatus(ad, 'error')

            return
        }

        await new Promise((resolve) => {
            window.googletag.cmd.push(() => {
                const currentSlotElement =
                    document.getElementById(
                        ad.slot_element_id
                    )

                if (!currentSlotElement) {
                    emitStatus(
                        ad,
                        'error'
                    )

                    resolve()

                    return
                }

                const slot =
                    getExistingSlot(
                        ad.slot_element_id
                    ) ??
                    defineSlot(ad)

                if (!slot) {
                    emitStatus(
                        ad,
                        'error'
                    )

                    resolve()

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

                resolve()
            })
        })
    } catch (error) {
        emitStatus(ad, 'error')
    } finally {
        isDisplaying.value = false
    }
}

const resetPopup = async () => {
    clearAutoCloseTimer()

    popupVisible.value = false
    isDisplaying.value = false
    currentIndex.value = 0

    await destroySlots()

    localAds.value = ads.map((ad) => {
        return {
            ...ad,
            status: null,
            slot: null,
            displayed: false,
        }
    })

    await nextTick()

    if (!currentAd.value) {
        return
    }

    await loadCurrentAd()
}

watch(
    () => ads,
    async () => {
        await resetPopup()
    },
    {
        immediate: true,
        deep: true,
    }
)

onBeforeUnmount(() => {
    clearAutoCloseTimer()

    popupVisible.value = false

    destroySlots()
})
</script>

<template>
    <Teleport to="body">
        <div v-if="currentAd" class="fixed inset-0 z-[9999] pointer-events-none">
            <div :class="[
                'absolute left-1/2 top-1/2 flex w-full max-w-5xl -translate-x-1/2 -translate-y-1/2 flex-col items-center transition-opacity duration-200',
                popupVisible &&
                    currentAd.status === 'filled'
                    ? 'pointer-events-auto opacity-100'
                    : 'pointer-events-none opacity-0',
                customClass,
            ]">
                <button v-if="
                    popupVisible &&
                    currentAd.status === 'filled'
                " type="button"
                    class="absolute -top-10 left-0 z-20 flex h-9 w-9 items-center justify-center rounded-md bg-white text-xl leading-none text-gray-700 shadow-md transition hover:bg-gray-100 focus:outline-none"
                    aria-label="Close advertisement" @click="closePopup">
                    ×
                </button>

                <span v-if="
                    showLabel &&
                    popupVisible &&
                    currentAd.status === 'filled'
                "
                    class="absolute -top-8 right-0 z-20 rounded bg-white px-2 py-1 text-[10px] font-medium uppercase tracking-wider text-gray-500 shadow">
                    {{ t('common.labels.ad') }}
                </span>

                <div :id="currentAd.slot_element_id" class="mx-auto flex max-w-full justify-center"></div>
            </div>
        </div>
    </Teleport>
</template>
