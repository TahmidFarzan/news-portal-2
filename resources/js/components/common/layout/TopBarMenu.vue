<script setup>
import { reactive, onMounted } from 'vue'
import HorizontalScroller from '@/components/common/layout/HorizontalScroller.vue'
import TopBarMenuItem from '@/components/common/layout/TopBarMenuItem.vue'
import { fetchFromApi } from '@/composables/useSystemApi'

const topBarMenu = reactive({
    items: [],
    loading: false,
    page: 1,
    lastPage: 1
})

const normalizeMenuItems = (items = []) => {
    return items.map((item) => ({
        ...item,
        children: item.children ?? []
    }))
}

const getTopBarMenuItems = async (pageNumber = 1) => {
    if (topBarMenu.loading || pageNumber > topBarMenu.lastPage) return

    try {
        topBarMenu.loading = true

        const response = await fetchFromApi(
            route('site.theme.menus.topbar-menu-items', { page: pageNumber })
        )

        const items = normalizeMenuItems(response?.items ?? [])

        topBarMenu.items = pageNumber === 1
            ? items
            : [...topBarMenu.items, ...items]

        topBarMenu.page = Number(response?.current_page ?? pageNumber)
        topBarMenu.lastPage = Number(response?.last_page ?? pageNumber)
    } catch (error) {
        console.error('Failed to fetch top bar menu:', error)
    } finally {
        topBarMenu.loading = false
    }
}

const handleReachEnd = async () => {
    const nextPage = topBarMenu.page + 1

    if (!topBarMenu.loading && nextPage <= topBarMenu.lastPage) {
        await getTopBarMenuItems(nextPage)
    }
}

onMounted(() => {
    getTopBarMenuItems()
})
</script>

<template>
    <HorizontalScroller v-if="topBarMenu.items.length || topBarMenu.loading"
        class="flex h-8 max-[450px]:w-full max-[450px]:min-w-0 max-[450px]:overflow-hidden" :loading="topBarMenu.loading"
        :watch-key="`${topBarMenu.items.length}-${topBarMenu.loading}`" @reach-end="handleReachEnd">
        <ul class="h-8 flex items-center gap-3 whitespace-nowrap min-w-0">
            <TopBarMenuItem v-for="item in topBarMenu.items" :key="item.id" :item="item" />

            <li v-if="topBarMenu.loading" class="text-xs text-gray-300 flex-shrink-0">
                Loading...
            </li>
        </ul>
    </HorizontalScroller>
</template>
