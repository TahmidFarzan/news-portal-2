<script setup>
import { computed, ref, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'

import SelectInfinityLoadingApi from '@/components/common/multi-select/SelectInfinityLoadingApi.vue'

import { fetchFromApi } from '@/composables/useSystemApi'
import { useTranslate } from '@/composables/useTranslate'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'

import {
    faLocationDot,
    faMagnifyingGlass,
} from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(
    faLocationDot,
    faMagnifyingGlass,
)

const {
    category,
    isOnSidebar,
} = defineProps({
    category: {
        type: Object,
        required: true,
    },
    isOnSidebar: {
        type: Boolean,
        default: true,
    },
})

const { t } = useTranslate()

const categoryLocationMaxDepthAndLevel = ref(null)
const isLoadingLocationConfig = ref(false)
const isSearchingLocation = ref(false)
const loadedCategorySlugTreeKey = ref(null)
const isReadyToWatchLocationChanges = ref(false)

const locationLevelResetKeys = ref([])

const searchLocationForm = useForm({})

const getLocationFieldName = (index) => {
    return `location_level_${index + 1}`
}

const categorySlugTreeKey = computed(() => {
    if (!category?.slug_tree) {
        return null
    }

    if (Array.isArray(category.slug_tree)) {
        return category.slug_tree.join('/')
    }

    return String(category.slug_tree)
})

const locationMaxDepth = computed(() => {
    return categoryLocationMaxDepthAndLevel.value?.max_depth
        ?? categoryLocationMaxDepthAndLevel.value?.depth
        ?? null
})

const locationMaxLevel = computed(() => {
    return Number(
        categoryLocationMaxDepthAndLevel.value?.max_level
        ?? categoryLocationMaxDepthAndLevel.value?.level
        ?? 0
    )
})

const hasLocationFilter = computed(() => {
    return Boolean(
        category?.has_location
        && locationMaxDepth.value !== null
        && locationMaxLevel.value > 0
    )
})

const locationLevels = computed(() => {
    if (!hasLocationFilter.value) {
        return []
    }

    return Array.from({ length: locationMaxLevel.value }, (_, index) => index)
})

const baseLocationsApiUrl = computed(() => {
    const params = new URLSearchParams()

    if (category?.id) {
        params.append('category_id', category.id)
    }

    if (category?.language_id) {
        params.append('language_id', category.language_id)
    }

    const queryString = params.toString()

    return queryString
        ? `${route('search.locations')}?${queryString}`
        : route('search.locations')
})

const appendQueryParam = (url, key, value) => {
    const separator = url.includes('?') ? '&' : '?'

    return `${url}${separator}${key}=${encodeURIComponent(value)}`
}

const hasLocationValue = (value) => {
    if (Array.isArray(value)) {
        return value.length > 0
    }

    return value !== null && value !== undefined && value !== ''
}

const getSelectedLocationId = (index) => {
    const fieldName = getLocationFieldName(index)
    const selectedLocation = searchLocationForm[fieldName]

    if (!hasLocationValue(selectedLocation)) {
        return null
    }

    if (Array.isArray(selectedLocation)) {
        const firstSelectedLocation = selectedLocation[0]

        if (!hasLocationValue(firstSelectedLocation)) {
            return null
        }

        if (typeof firstSelectedLocation === 'object') {
            return firstSelectedLocation.id
                ?? firstSelectedLocation.value
                ?? firstSelectedLocation.slug
                ?? null
        }

        return firstSelectedLocation
    }

    if (typeof selectedLocation === 'object') {
        return selectedLocation.id
            ?? selectedLocation.value
            ?? selectedLocation.slug
            ?? null
    }

    return selectedLocation
}

const locationGridClass = computed(() => {
    return isOnSidebar
        ? 'grid-cols-1 md:grid-cols-1 lg:grid-cols-1'
        : 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3'
})

const selectedLocationIds = computed(() => {
    return locationLevels.value.map((index) => {
        return getSelectedLocationId(index)
    })
})

const selectedLocationIdsKey = computed(() => {
    return selectedLocationIds.value.map((id) => {
        return id === null || id === undefined ? '' : String(id)
    }).join('|')
})

const visibleLocationLevels = computed(() => {
    return locationLevels.value.filter((levelIndex) => {
        if (levelIndex === 0) {
            return true
        }

        return getSelectedLocationId(levelIndex - 1) !== null
    })
})

const getLocationApiUrl = (index) => {
    if (index === 0) {
        return appendQueryParam(baseLocationsApiUrl.value, 'only_main', true)
    }

    const parentId = getSelectedLocationId(index - 1)

    if (parentId === null) {
        return ''
    }

    return appendQueryParam(baseLocationsApiUrl.value, 'parent_id', parentId)
}

const selectedLastLoopLocationItem = computed(() => {
    for (let index = locationMaxLevel.value - 1; index >= 0; index--) {
        const selectedLocationId = getSelectedLocationId(index)

        if (selectedLocationId !== null) {
            return selectedLocationId
        }
    }

    return null
})

const canSearchLocation = computed(() => {
    return selectedLastLoopLocationItem.value !== null && !isSearchingLocation.value
})

const resetLocationLevelComponent = (index) => {
    locationLevelResetKeys.value[index] = (locationLevelResetKeys.value[index] ?? 0) + 1
}

const getLocationSelectKey = (levelIndex) => {
    const parentKey = levelIndex === 0
        ? 'root'
        : String(getSelectedLocationId(levelIndex - 1) ?? 'none')

    return [
        categorySlugTreeKey.value ?? 'category',
        levelIndex,
        parentKey,
        locationLevelResetKeys.value[levelIndex] ?? 0,
    ].join('-')
}

const ensureLocationFields = () => {
    for (let index = 0; index < locationMaxLevel.value; index++) {
        const fieldName = getLocationFieldName(index)

        if (!Object.prototype.hasOwnProperty.call(searchLocationForm, fieldName)) {
            searchLocationForm[fieldName] = null
        }

        if (locationLevelResetKeys.value[index] === undefined) {
            locationLevelResetKeys.value[index] = 0
        }
    }
}

const clearChildLocationFields = (parentIndex) => {
    for (let index = parentIndex + 1; index < locationMaxLevel.value; index++) {
        const fieldName = getLocationFieldName(index)

        if (hasLocationValue(searchLocationForm[fieldName])) {
            searchLocationForm[fieldName] = null
        }

        resetLocationLevelComponent(index)
    }
}

const clearAllLocationFields = () => {
    for (let index = 0; index < locationMaxLevel.value; index++) {
        const fieldName = getLocationFieldName(index)

        if (hasLocationValue(searchLocationForm[fieldName])) {
            searchLocationForm[fieldName] = null
        }

        resetLocationLevelComponent(index)
    }
}

const fetchCategoryLocationMaxDepthAndLevel = async () => {
    if (
        !category?.has_location
        || !category?.slug_tree
        || !categorySlugTreeKey.value
    ) {
        clearAllLocationFields()

        categoryLocationMaxDepthAndLevel.value = null
        loadedCategorySlugTreeKey.value = null
        isReadyToWatchLocationChanges.value = false

        return
    }

    if (loadedCategorySlugTreeKey.value === categorySlugTreeKey.value) {
        return
    }

    const shouldClearLocationFields = Boolean(loadedCategorySlugTreeKey.value)

    loadedCategorySlugTreeKey.value = categorySlugTreeKey.value
    categoryLocationMaxDepthAndLevel.value = null
    isLoadingLocationConfig.value = true
    isReadyToWatchLocationChanges.value = false

    try {
        const response = await fetchFromApi(
            route('category.location-max-depth-and-level', {
                slugTree: category.slug_tree,
            }),
            {},
            {
                languageAwareCache: true,
            }
        )

        categoryLocationMaxDepthAndLevel.value = response?.data ?? response

        ensureLocationFields()

        if (shouldClearLocationFields) {
            clearAllLocationFields()
        }
    } finally {
        isLoadingLocationConfig.value = false
        isReadyToWatchLocationChanges.value = true
    }
}

const searchByLocation = async () => {
    if (selectedLastLoopLocationItem.value === null) {
        return
    }

    isSearchingLocation.value = true

    try {
        const response = await fetchFromApi(
            route('search.location', {
                slugOrId: selectedLastLoopLocationItem.value,
            }),
            {},
            {
                languageAwareCache: true,
            }
        )

        const location = response?.data ?? response

        if (!location?.public_url) {
            return
        }

        router.visit(location.public_url)
    } finally {
        isSearchingLocation.value = false
    }
}

watch(
    categorySlugTreeKey,
    fetchCategoryLocationMaxDepthAndLevel,
    { immediate: true }
)

watch(
    () => locationMaxLevel.value,
    () => {
        ensureLocationFields()
    },
    { immediate: true }
)

watch(
    selectedLocationIdsKey,
    (newValue, oldValue) => {
        if (!isReadyToWatchLocationChanges.value || oldValue === undefined) {
            return
        }

        const newValues = newValue.split('|')
        const oldValues = oldValue.split('|')

        const changedIndex = newValues.findIndex((value, index) => {
            return value !== oldValues[index]
        })

        if (changedIndex === -1) {
            return
        }

        clearChildLocationFields(changedIndex)
    }
)
</script>

<template>
    <div v-if="hasLocationFilter" class="space-y-3 rounded-2xl border border-gray-200 bg-white p-3 shadow-sm">
        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-gray-700">
            <FontAwesomeIcon icon="location-dot" class="text-blue-600" />

            <span>
                {{ t('common.labels.location') }}
            </span>
        </div>

        <div class="grid gap-3" :class="locationGridClass">
            <div v-for="levelIndex in visibleLocationLevels" :key="levelIndex" class="min-w-0">
                <SelectInfinityLoadingApi :key="getLocationSelectKey(levelIndex)" :form="searchLocationForm"
                    :fieldName="getLocationFieldName(levelIndex)" :apiUrl="getLocationApiUrl(levelIndex)"
                    :error="searchLocationForm.errors?.[getLocationFieldName(levelIndex)]" :multiple="false"
                    :placeholder="t('pages.components.categoryHasLocationSection.form.locationPlaceholder')"
                    :language-aware-cache="true" />
            </div>
        </div>

        <button type="button"
            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition duration-300 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="!canSearchLocation || isLoadingLocationConfig" @click="searchByLocation">
            <FontAwesomeIcon icon="magnifying-glass" />

            <span>
                {{
                    isSearchingLocation
                        ? t('pages.components.categoryHasLocationSection.actions.searching')
                        : t('common.actions.search')
                }}
            </span>
        </button>
    </div>
</template>
