<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch,inject } from 'vue'
import { Head } from '@inertiajs/vue3'

import Layout from '@/pages/layouts/PublicLayout.vue'
import List from '@/components/common/news/List.vue'
import GoogleAd from '@/components/common/advertising/GoogleAd.vue'

import { useTranslate } from '@/composables/useTranslate'
import { adPages, adTypes, adPlacements } from '@/composables/useGoogleAd'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'

import {
    faLocationDot,
} from '@fortawesome/free-solid-svg-icons'

import 'leaflet/dist/leaflet.css'

FontAwesomeLibrary.add(faLocationDot)

defineOptions({ layout: Layout })

const { t } = useTranslate()

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

const mapElement = ref(null)

let L = null
let map = null
let boundaryLayer = null

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

const parseGeoJson = (value) => {
    if (!value) {
        return null
    }

    if (typeof value === 'object') {
        return value
    }

    if (typeof value === 'string') {
        try {
            return JSON.parse(value)
        } catch {
            return null
        }
    }

    return null
}

const hasCoordinate = (item) => {
    return (
        item?.latitude !== null &&
        item?.latitude !== undefined &&
        item?.latitude !== '' &&
        item?.longitude !== null &&
        item?.longitude !== undefined &&
        item?.longitude !== ''
    )
}

const isSameLocation = (child) => {
    return (
        child?.id !== null &&
        child?.id !== undefined &&
        location?.id !== null &&
        location?.id !== undefined &&
        String(child.id) === String(location.id)
    )
}

const displayChildren = computed(() => {
    if (!Array.isArray(location?.children)) {
        return []
    }

    return location.children.filter((child) => {
        return !isSameLocation(child)
    })
})

const getDynamicColor = (index, total) => {
    const safeTotal = Math.max(total, 1)
    const hue = Math.round((index * 360) / safeTotal)
    const saturation = 72
    const lightness = 48

    return `hsl(${hue}, ${saturation}%, ${lightness}%)`
}

const escapeHtml = (value) => {
    return String(value || '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;')
}

const getCssToken = (name, fallback) => {
    if (typeof window === 'undefined') {
        return fallback
    }

    return getComputedStyle(document.documentElement)
        .getPropertyValue(name)
        .trim() || fallback
}

const childAreaItems = computed(() => {
    return displayChildren.value
        .map((child, index) => {
            const childBoundary = parseGeoJson(child?.boundary_geojson)

            if (!childBoundary) {
                return null
            }

            return {
                id: child?.id || child?.slug || child?.name,
                key: `child-${child?.id || child?.slug || child?.name}-${index}`,
                name: child?.name,
                public_url: child?.public_url,
                boundary_geojson: childBoundary,
                isCurrent: false,
            }
        })
        .filter(Boolean)
})

const areaItems = computed(() => {
    if (childAreaItems.value.length > 0) {
        const total = childAreaItems.value.length

        return childAreaItems.value.map((item, index) => ({
            ...item,
            color: getDynamicColor(index, total),
        }))
    }

    const currentBoundary = parseGeoJson(location?.boundary_geojson)

    if (!currentBoundary) {
        return []
    }

    return [
        {
            id: location?.id || location?.slug || 'selected-location',
            key: `current-${location?.id || location?.slug || location?.name}`,
            name: location?.name,
            public_url: location?.public_url,
            boundary_geojson: currentBoundary,
            isCurrent: true,
            color: getDynamicColor(0, 1),
        },
    ]
})

const hasMapItems = computed(() => {
    return areaItems.value.length > 0
})

const mapCenter = computed(() => {
    if (hasCoordinate(location)) {
        return [Number(location.latitude), Number(location.longitude)]
    }

    const firstChildWithCoordinate = displayChildren.value.find((child) => hasCoordinate(child))

    if (firstChildWithCoordinate) {
        return [Number(firstChildWithCoordinate.latitude), Number(firstChildWithCoordinate.longitude)]
    }

    return [23.685, 90.3563]
})
const googleAdEnable = inject('googleAdEnable', computed(() => false))

const createAreaPopup = (item) => {
    return `
        <div class="location-popup">
            <p>${escapeHtml(item.name)}</p>
            ${item.public_url
            ? `<a href="${escapeHtml(item.public_url)}">${escapeHtml(t('pages.locationNews.locations.details.viewLocation'))}</a>`
            : ''
        }
        </div>
    `
}

const renderAreas = () => {
    if (!map || !boundaryLayer) {
        return []
    }

    boundaryLayer.clearLayers()

    const layers = []

    areaItems.value.forEach((item) => {
        const boundary = L.geoJSON(item.boundary_geojson, {
            style: {
                color: getCssToken('--news-white', '#ffffff'),
                weight: 1,
                opacity: 1,
                fillColor: item.color,
                fillOpacity: 0.82,
            },
            onEachFeature: (feature, layer) => {
                layer.bindPopup(createAreaPopup(item))

                layer.on({
                    mouseover: () => {
                        layer.setStyle({
                            color: getCssToken('--news-ink', '#111827'),
                            weight: 1.5,
                            fillOpacity: 0.95,
                        })
                    },
                    mouseout: () => {
                        layer.setStyle({
                            color: getCssToken('--news-white', '#ffffff'),
                            weight: 1,
                            fillOpacity: 0.82,
                        })
                    },
                })
            },
        }).addTo(boundaryLayer)

        layers.push(boundary)
    })

    return layers
}

const fitMap = (layers) => {
    if (!map || !layers.length) {
        return
    }

    const group = L.featureGroup(layers)

    if (group.getBounds().isValid()) {
        map.fitBounds(group.getBounds(), {
            padding: [35, 35],
            maxZoom: 10,
        })
    }
}

const renderMap = () => {
    const layers = renderAreas()

    fitMap(layers)
}

const initMap = async () => {
    if (!hasMapItems.value || !mapElement.value || map) {
        return
    }

    const leaflet = await import('leaflet')

    L = leaflet.default || leaflet

    map = L.map(mapElement.value, {
        zoomControl: true,
        scrollWheelZoom: true,
        attributionControl: false,
        doubleClickZoom: true,
        boxZoom: true,
        keyboard: true,
        dragging: true,
        touchZoom: true,
    }).setView(mapCenter.value, 7)

    boundaryLayer = L.layerGroup().addTo(map)

    renderMap()

    setTimeout(() => {
        map?.invalidateSize()
    }, 100)
}

onMounted(async () => {
    await nextTick()
    await initMap()
})

watch(
    areaItems,
    async () => {
        await nextTick()

        if (!map) {
            await initMap()
            return
        }

        renderMap()

        setTimeout(() => {
            map?.invalidateSize()
        }, 100)
    },
    {
        deep: true,
    },
)

onBeforeUnmount(() => {
    if (map) {
        map.remove()
        map = null
        boundaryLayer = null
        L = null
    }
})
</script>

<template>

    <Head :title="location?.name || t('common.labels.location')">
        <link v-if="location?.public_url" rel="canonical" :href="location.public_url" />

        <meta v-if="metaTitle" name="title" :content="metaTitle" />

        <meta v-if="metaDescription" name="description" :content="metaDescription" />

        <meta v-if="metaKeywords" name="keywords" :content="metaKeywords" />
    </Head>

    <div class="entity-page location-container space-y-6">
        <section class="grid grid-cols-1 items-center gap-5 md:grid-cols-12">
            <div class="md:col-span-3 lg:col-span-2">
                <div class="flex h-28 w-28 items-center justify-center rounded-2xl bg-blue-50 p-4 sm:h-32 sm:w-32">
                    <img :src="'/uploads/images/logo/location.png'"
                        :alt="location?.name || t('pages.locationNews.locations.details.locationImageAlt')"
                        class="h-full w-full object-contain" loading="lazy" />
                </div>
            </div>

            <div class="md:col-span-9 lg:col-span-10">
                <div :class="[
                    hasMapItems
                        ? 'grid grid-cols-1 items-start gap-5 lg:grid-cols-12'
                        : 'space-y-2',
                ]">
                    <div :class="[
                        hasMapItems
                            ? 'space-y-2 lg:col-span-6'
                            : 'space-y-2',
                    ]">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-600">
                            {{ t('common.labels.location') }}
                        </p>

                        <div v-if="location?.parent"
                            class="flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                            <a :href="location?.parent?.public_url"
                                :title="t('pages.locationNews.locations.details.parentLocation')"
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
                            {{ location.brief }}
                        </p>

                        <div v-if="location?.has_descendants && displayChildren.length"
                            class="flex flex-wrap items-center gap-2 pt-1">
                            <a v-for="child in displayChildren" :key="child.id || child.slug" :href="child.public_url"
                                :title="child.name"
                                class="inline-flex min-w-0 items-center gap-1 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-600 shadow-sm transition duration-300 hover:border-red-200 hover:bg-red-50 hover:text-red-600">
                                <FontAwesomeIcon icon="location-dot" class="shrink-0" />

                                <span class="truncate">
                                    {{ child.name }}
                                </span>
                            </a>
                        </div>
                    </div>

                    <div v-if="hasMapItems && location?.enable_map" class="lg:col-span-6">
                        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-slate-50 shadow-sm lg:flex">
                            <div class="min-w-0 flex-1">
                                <div ref="mapElement" class="h-80 w-full sm:h-96 lg:h-[28rem]"></div>
                            </div>

                            <div
                                class="border-t border-gray-200 bg-white/95 p-3 lg:w-40 lg:border-l lg:border-t-0 xl:w-40">
                                <div class="max-h-40 overflow-y-auto pr-1 lg:max-h-[28rem]">
                                    <div class="grid grid-cols-2 gap-2 lg:grid-cols-1">
                                        <a v-for="item in areaItems" :key="item.key" :href="item.public_url || '#'"
                                            :title="item.name"
                                            class="inline-flex min-w-0 items-center gap-2 text-xs font-semibold text-gray-900 transition hover:text-red-600">
                                            <span class="h-4 w-4 shrink-0"
                                                :style="{ backgroundColor: item.color }"></span>

                                            <span class="truncate">
                                                {{ item.name }}
                                            </span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <GoogleAd v-if="googleAdEnable" :page="adPages.LOCATION" :type="adTypes.SECTION" :placement="adPlacements.ONE" />

        <div class="border-t border-gray-200"></div>

        <GoogleAd v-if="googleAdEnable" :page="adPages.LOCATION" :type="adTypes.SECTION" :placement="adPlacements.TWO" />

        <List :news="news" pagination-type="Cursor" />

        <GoogleAd v-if="googleAdEnable" :page="adPages.LOCATION" :type="adTypes.SECTION" :placement="adPlacements.THREE" />
    </div>

    <Teleport to="body">
        <GoogleAd v-if="googleAdEnable" :page="adPages.LOCATION" :type="adTypes.POPUP"/>
    </Teleport>
</template>

<style scoped>
.entity-page > section:first-child {
    border: var(--news-border-default);
    border-radius: var(--news-radius);
    background: var(--news-hero-info-gradient);
    padding: var(--news-hero-padding);
    box-shadow: var(--news-shadow-soft);
}

.location-container :deep(.leaflet-container) {
    z-index: 0;
    font-family: inherit;
    background: var(--news-map-bg);
}

.location-container :deep(.leaflet-control-zoom) {
    overflow: hidden;
    border: var(--news-border-default);
    border-radius: var(--news-radius-sm);
    box-shadow: var(--news-shadow-map-control);
}

.location-container :deep(.leaflet-control-zoom a) {
    border: 0;
    color: var(--news-ink);
}

.location-container :deep(.leaflet-control-zoom a:hover) {
    background: var(--news-soft-hover);
    color: var(--news-danger);
}

.location-container :deep(.leaflet-popup-content-wrapper) {
    border-radius: var(--news-radius-sm);
}

.location-container :deep(.leaflet-popup-content) {
    margin: 10px 12px;
    font-size: var(--news-popup-text-size);
}

.location-container :deep(.leaflet-popup-content a) {
    color: var(--news-link);
    font-weight: 600;
}

.location-container :deep(.location-popup p) {
    margin: 0 0 4px;
    font-weight: 700;
    color: var(--news-ink);
}
</style>
