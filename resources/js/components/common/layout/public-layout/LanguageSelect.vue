<script setup>
import { computed } from 'vue'

const {
    availableLanguages = [],
    currentLanguage = null,
    defaultLanguage = null,
} = defineProps({
    availableLanguages: {
        type: Array,
        default: () => [],
    },
    currentLanguage: {
        type: Object,
        default: null,
    },
    defaultLanguage: {
        type: Object,
        default: null,
    },
})

const normalizeLanguageCode = (code) => {
    return String(code ?? '').trim().toLowerCase()
}

const languageCodes = computed(() => {
    return availableLanguages
        .map((language) => normalizeLanguageCode(language?.code))
        .filter(Boolean)
})

const targetLanguage = computed(() => {
    const currentCode = normalizeLanguageCode(currentLanguage?.code)

    return availableLanguages.find((language) => {
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
</script>

<template>
    <a v-if="targetLanguage" :href="switchUrl" target="_blank" rel="noopener noreferrer"
        class="inline-flex h-8 min-w-12 items-center justify-center rounded-md border border-white/20 px-3 text-sm font-semibold text-white transition hover:bg-white/10"
        :hreflang="targetLanguage?.code" :lang="targetLanguage?.code">
        {{ targetLabel }}
    </a>
</template>
