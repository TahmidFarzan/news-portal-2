<script setup>
import { reactive, computed, onMounted } from 'vue'
import HorizontalScroller from '@/components/common/layout/HorizontalScroller.vue'
import TopbarMenuItem from '@/components/common/layout/public-layout/TopbarMenuItem.vue'
import { fetchFromApi } from '@/composables/useApiClient'
import { apiCacheKey, apiCacheTTL } from '@/composables/useApiCache'

import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faSpinner
} from '@fortawesome/free-solid-svg-icons'

import { useTranslate } from '@/composables/useTranslate'

library.add(faSpinner)

const { t } = useTranslate()

const topbarMenu = reactive({
    items: [],
    loading: false,
    loaded: false,
    error: null,
    page: 1,
    lastPage: 1
})

const isInitialLoading = computed(() => {
    return topbarMenu.loading && !topbarMenu.items.length
})

const normalizeMenuItems = (items = []) => {
    return items.map((item) => ({
        ...item,
        children: item.children ?? []
    }))
}

const getTopbarMenuItems = async (pageNumber = 1) => {
    if (topbarMenu.loading || pageNumber > topbarMenu.lastPage) return

    try {
        topbarMenu.loading = true
        topbarMenu.error = null

        const apiUrl = route('site.menus.topbar-menu-items', { page: pageNumber })
        const response = await fetchFromApi(
            apiUrl,
            {},
            {
                key: `${apiCacheKey.API_LAYOUT_TOPBAR_MENU}:${apiUrl}`,
                ttl: apiCacheTTL.LAYOUT_TOPBAR,
            }
        )

        const items = normalizeMenuItems(response?.items ?? [])

        topbarMenu.items = pageNumber === 1
            ? items
            : [...topbarMenu.items, ...items]

        topbarMenu.page = Number(response?.current_page ?? pageNumber)
        topbarMenu.lastPage = Number(response?.last_page ?? pageNumber)
    } catch (error) {
        topbarMenu.error = error
        console.error('Failed to fetch top bar menu:', error)
    } finally {
        topbarMenu.loading = false
        topbarMenu.loaded = true
    }
}

const handleReachEnd = async () => {
    const nextPage = topbarMenu.page + 1

    if (!topbarMenu.loading && nextPage <= topbarMenu.lastPage) {
        await getTopbarMenuItems(nextPage)
    }
}

onMounted(() => {
    getTopbarMenuItems()
})
</script>

<template>
    <nav class="h-8 max-[450px]:w-full max-[450px]:min-w-0" aria-label="Top bar menu" :aria-busy="topbarMenu.loading">
        <HorizontalScroller class="flex h-8 max-[450px]:w-full max-[450px]:min-w-0 max-[450px]:overflow-hidden"
            :loading="topbarMenu.loading" :watch-key="`${topbarMenu.items.length}-${topbarMenu.loading}`"
            @reach-end="handleReachEnd">
            <ul class="h-8 flex items-center gap-3 whitespace-nowrap min-w-0">
                <template v-if="topbarMenu.items.length">
                    <TopbarMenuItem v-for="item in topbarMenu.items" :key="item.id" :item="item" />
                </template>

                <template v-else-if="isInitialLoading">
                    <li v-for="index in 3" :key="index"
                        class="h-4 w-14 rounded bg-white/10 animate-pulse flex-shrink-0" />
                </template>

                <li v-if="topbarMenu.loading && topbarMenu.items.length" class="text-xs text-gray-300 flex-shrink-0">
                    <FontAwesomeIcon icon="spinner" spin class="text-2xl text-blue-500" />
                    {{ t("common.labels.loading") }}
                </li>
            </ul>
        </HorizontalScroller>
    </nav>
</template>
