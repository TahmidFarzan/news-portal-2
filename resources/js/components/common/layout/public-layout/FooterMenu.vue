<script setup>
import { reactive, computed, onMounted } from 'vue'
import FooterMenuItem from '@/components/common/layout/public-layout/FooterMenuItem.vue'
import { fetchFromApi } from '@/composables/useSystemApi'
import { smartCacheKey, smartCacheTTL } from '@/composables/useApiSmartCache'

import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faSpinner
} from '@fortawesome/free-solid-svg-icons'

import { useTranslate } from '@/composables/useTranslate'

const { t } = useTranslate()

const footerMenu = reactive({
    items: [],
    loading: false,
    loaded: false,
    error: null,
    page: 1,
    lastPage: 1
})

const isInitialLoading = computed(() => {
    return footerMenu.loading && !footerMenu.items.length
})

const normalizeMenuItems = (items = []) => {
    return items.map((item) => ({
        ...item,
        children: item.children ?? []
    }))
}

const getFooterMenuItems = async (pageNumber = 1) => {
    if (footerMenu.loading || pageNumber > footerMenu.lastPage) return

    try {
        footerMenu.loading = true
        footerMenu.error = null

        const apiUrl = route('site.menus.footer-menu-items', { page: pageNumber })
        const response = await fetchFromApi(
            apiUrl,
            {},
            {
                key: `${smartCacheKey.API_LAYOUT_FOOTER_MENU}:${apiUrl}`,
                ttl: smartCacheTTL.LAYOUT_FOOTER_MENU,
            }
        )

        const items = normalizeMenuItems(response?.items ?? [])

        footerMenu.items = pageNumber === 1
            ? items
            : [...footerMenu.items, ...items]

        footerMenu.page = Number(response?.current_page ?? pageNumber)
        footerMenu.lastPage = Number(response?.last_page ?? pageNumber)
    } catch (error) {
        footerMenu.error = error
        console.error('Failed to fetch footer menu:', error)
    } finally {
        footerMenu.loading = false
        footerMenu.loaded = true
    }
}

onMounted(() => {
    getFooterMenuItems()
})
</script>

<template>
    <nav class="w-full md:w-auto md:flex-1 min-w-0 min-h-6" aria-label="Footer menu" :aria-busy="footerMenu.loading">
        <ul class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1 text-center">
            <template v-if="footerMenu.items.length">
                <FooterMenuItem v-for="item in footerMenu.items" :key="item.id" :item="item" />
            </template>

            <template v-else-if="isInitialLoading">
                <li v-for="index in 4" :key="index" class="h-4 w-16 rounded bg-gray-200 animate-pulse" />
            </template>

            <li v-if="footerMenu.loading && footerMenu.items.length" class="text-xs text-gray-400">
                <FontAwesomeIcon icon="spinner" spin class="text-2xl text-blue-500" />
                {{ t("common.labels.loading") }}
            </li>
        </ul>
    </nav>
</template>
