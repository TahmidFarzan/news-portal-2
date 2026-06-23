<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, toRefs, watch } from 'vue'

import { fetchFromApi } from '@/composables/useSystemApi'
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
const screenWidth = ref(window.innerWidth)

let observer = null
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

const getAdStyle = (ad) => {
    if (isFullWidthResponsive(ad)) {
        return 'display:block'
    }

    const width = screenWidth.value

    if (width < 576) {
        return 'display:inline-block;width:320px;height:100px'
    }

    if (width < 992) {
        return 'display:inline-block;width:468px;height:60px'
    }

    return 'display:inline-block;width:728px;height:90px'
}

const normalizeRows = (response) => {
    const rows = Array.isArray(response)
        ? response
        : response?.data ?? response?.items ?? []

    return rows.filter((row) => {
        return row?.client_id && row?.slot_id
    })
}

const fetchAds = async () => {
    const response = await fetchFromApi(
        route('site.google-adsences', {
            type: type.value,
            position: position.value,
        })
    )

    ads.value = normalizeRows(response)
    adsLoaded = false

    await nextTick()

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

const resizeHandler = () => {
    screenWidth.value = window.innerWidth
}

watch(
    [type, position],
    async () => {
        await fetchAds()
    }
)

onMounted(async () => {
    window.addEventListener('resize', resizeHandler)

    await fetchAds()
})

onBeforeUnmount(() => {
    window.removeEventListener('resize', resizeHandler)

    if (observer) {
        observer.disconnect()
        observer = null
    }
})
</script>

<template>
    <section v-if="hasAds" ref="wrapperRef" :class="wrapperClasses">
        <span v-if="showLabel"
            class="absolute -top-2 left-3 bg-gray-50 px-2 text-[10px] font-medium uppercase tracking-wider text-gray-500">
            {{ label }}
        </span>

        <div class="flex w-full flex-col items-center justify-center gap-4">
            <ins v-for="ad in ads" :key="getAdKey(ad)" ref="adRefs" class="adsbygoogle mx-auto" :style="getAdStyle(ad)"
                :data-ad-client="ad.client_id" :data-ad-slot="ad.slot_id"
                :data-ad-format="isFullWidthResponsive(ad) ? 'auto' : null"
                :data-full-width-responsive="isFullWidthResponsive(ad) ? 'true' : null"></ins>
        </div>
    </section>
</template>
