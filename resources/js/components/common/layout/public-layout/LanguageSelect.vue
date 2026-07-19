<script setup>
import { computed, onMounted, ref } from 'vue'
import { fetchFromApi } from '@/composables/useApiClient'
import { apiCacheKey, apiCacheTTL } from '@/composables/useApiCache'

const {
    currentLanguage = null,
    defaultLanguage = null,
} = defineProps({
    currentLanguage: {
        type: Object,
        default: null,
    },
    defaultLanguage: {
        type: Object,
        default: null,
    },
})

const loadedLanguages = ref([])

const normalizeLanguageCode = (code) => {
    return String(code ?? '').trim().toLowerCase()
}

const availableLanguages = computed(() => {
    const languages = loadedLanguages.value

    if (!defaultLanguage) {
        return languages
    }

    const exists = languages.some(
        (lang) => lang.code === defaultLanguage.code
    )

    if (exists) {
        return languages
    }

    return [
        defaultLanguage,
        ...languages,
    ]
})

const languageCodes = computed(() => {
    return availableLanguages.value
        .map((language) => normalizeLanguageCode(language?.code))
        .filter(Boolean)
})

const targetLanguage = computed(() => {
    const currentCode = normalizeLanguageCode(currentLanguage?.code)

    return availableLanguages.value.find((language) => {
        return normalizeLanguageCode(language?.code) !== currentCode
    }) ?? null
})

const switchUrl = computed(() => {
    if (!targetLanguage.value?.code || !defaultLanguage?.code) {
        return '#'
    }

    const targetCode = normalizeLanguageCode(targetLanguage.value.code)
    const defaultCode = normalizeLanguageCode(defaultLanguage.code)
    const pathSegments = window.location.pathname.split('/').filter(Boolean)

    if (pathSegments.length && languageCodes.value.includes(normalizeLanguageCode(pathSegments[0]))) {
        pathSegments.shift()
    }

    if (targetCode !== defaultCode) {
        pathSegments.unshift(targetCode)
    }

    const path = `/${pathSegments.join('/')}`.replace(/\/+$/, '') || '/'

    return `${window.location.origin}${path}${window.location.search}${window.location.hash}`
})

const targetLabel = computed(() => {
    return targetLanguage.value?.name
        ?? targetLanguage.value?.code?.toUpperCase()
        ?? ''
})

const loadAvailableLanguages = async () => {
    try {
        const response = await fetchFromApi(
            route('site.languages'),
            {
                per_page: 100,
            },
            {
                key: `${apiCacheKey.API_LAYOUT_LANGUAGE}:${route('site.languages')}`,
                ttl: apiCacheTTL.SYSTEM_LONG,
            }
        )

        const languages =
            Array.isArray(response?.items)
                ? response.items
                : Array.isArray(response?.data)
                    ? response.data
                    : []

        loadedLanguages.value = languages
    } catch (error) {
        console.error('Failed to load available languages:', error)
    }
}

onMounted(loadAvailableLanguages)
</script>

<template>
    <a v-if="targetLanguage" :href="switchUrl" target="_blank" rel="noopener noreferrer"
        class="inline-flex h-8 min-w-12 items-center justify-center rounded-md border border-white/20 px-3 text-sm font-semibold text-white transition hover:bg-white/10"
        :hreflang="targetLanguage?.code" :lang="targetLanguage?.code">
        {{ targetLabel }}
    </a>
</template>
