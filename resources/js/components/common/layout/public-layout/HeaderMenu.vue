<script setup>
import { reactive, computed, onMounted } from 'vue'
import HorizontalScroller from '@/components/common/layout/HorizontalScroller.vue'
import HeaderMenuItem from '@/components/common/layout/public-layout/HeaderMenuItem.vue'
import { fetchFromApi } from '@/composables/useApiClient'
import { apiCacheKey, apiCacheTTL } from '@/composables/useApiCache'

import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faSpinner,
} from '@fortawesome/free-solid-svg-icons'

import { useTranslate } from '@/composables/useTranslate'

library.add(faSpinner)

const { t } = useTranslate()

const {
    isDefaultLanguage = false,
    currentLanguage,
} = defineProps({
    isDefaultLanguage: {
        type: Boolean,
        default: false,
    },
    currentLanguage: {
        type: Object,
        required: true,
    },
})

const headerMenu = reactive({
    items: [],
    loading: false,
    loaded: false,
    error: null,
    page: 1,
    lastPage: 1
})

const isInitialLoading = computed(() => {
    return headerMenu.loading && !headerMenu.items.length
})

const normalizeMenuItems = (items = []) => {
    return items.map((item) => ({
        ...item,
        children: item.children ?? []
    }))
}

const getMenuApiUrl = (pageNumber = 1) => {
    if (isDefaultLanguage) {
        return route('site.menus.header-menu-items', {
            page: pageNumber,
        })
    }

    const languageCode = currentLanguage?.code

    if (!languageCode) {
        throw new Error('Current language code is required.')
    }

    return route('localized.site.menus.header-menu-items', {
        languageCode: languageCode,
        page: pageNumber,
    })
}

const getHeaderMenuItems = async (pageNumber = 1) => {
    if (headerMenu.loading || pageNumber > headerMenu.lastPage) return

    try {
        headerMenu.loading = true
        headerMenu.error = null

        const apiUrl = getMenuApiUrl(pageNumber)
        const response = await fetchFromApi(
            apiUrl,
            {},
            {
                key: `${apiCacheKey.API_LAYOUT_HEADER_MENU}:${apiUrl}`,
                ttl: apiCacheTTL.LAYOUT_MENU,
            }
        )

        const items = normalizeMenuItems(response?.items ?? [])

        headerMenu.items = pageNumber === 1
            ? items
            : [...headerMenu.items, ...items]

        headerMenu.page = Number(response?.current_page ?? pageNumber)
        headerMenu.lastPage = Number(response?.last_page ?? pageNumber)
    } catch (error) {
        headerMenu.error = error
        console.error('Failed to fetch header menu:', error)
    } finally {
        headerMenu.loading = false
        headerMenu.loaded = true
    }
}

const handleReachEnd = async () => {
    const nextPage = headerMenu.page + 1

    if (!headerMenu.loading && nextPage <= headerMenu.lastPage) {
        await getHeaderMenuItems(nextPage)
    }
}

onMounted(() => {
    getHeaderMenuItems()
})
</script>

<template>
    <nav class="w-full min-w-0 h-full flex" aria-label="Header menu" :aria-busy="headerMenu.loading">
        <HorizontalScroller class="w-full min-w-0 h-full flex" :loading="headerMenu.loading"
            :watch-key="`${headerMenu.items.length}-${headerMenu.loading}`" @reach-end="handleReachEnd">
            <ul class="h-10 flex items-center gap-2 whitespace-nowrap">
                <template v-if="headerMenu.items.length">
                    <HeaderMenuItem v-for="item in headerMenu.items" :key="item.id" :item="item"
                        :currentLanguage="currentLanguage" :isDefaultLanguage="isDefaultLanguage" />
                </template>

                <template v-else-if="isInitialLoading">
                    <li v-for="index in 4" :key="index"
                        class="h-8 w-20 rounded-lg bg-white/10 animate-pulse flex-shrink-0" />
                </template>

                <li v-if="headerMenu.loading && headerMenu.items.length"
                    class="h-10 flex items-center px-3 text-sm text-gray-300 flex-shrink-0">
                    <FontAwesomeIcon icon="spinner" spin class="text-2xl text-blue-500" />
                    {{ t("layout.public.headerMenu.messages.loading") }}
                </li>
            </ul>
        </HorizontalScroller>
    </nav>
</template>
