<script setup>
import { reactive, onMounted } from 'vue'
import HorizontalScroller from '@/components/common/layout/HorizontalScroller.vue'
import TopbarMenuItem from '@/components/common/layout/public-layout/TopbarMenuItem.vue'
import { fetchFromApi } from '@/composables/useSystemApi'

const topbarMenu = reactive({
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

const gettopbarMenuItems = async (pageNumber = 1) => {
    if (topbarMenu.loading || pageNumber > topbarMenu.lastPage) return

    try {
        topbarMenu.loading = true

        const response = await fetchFromApi(
            route('site.theme.menus.topbar-menu-items', { page: pageNumber })
        )

        const items = normalizeMenuItems(response?.items ?? [])

        topbarMenu.items = pageNumber === 1
            ? items
            : [...topbarMenu.items, ...items]

        topbarMenu.page = Number(response?.current_page ?? pageNumber)
        topbarMenu.lastPage = Number(response?.last_page ?? pageNumber)
    } catch (error) {
        console.error('Failed to fetch top bar menu:', error)
    } finally {
        topbarMenu.loading = false
    }
}

const handleReachEnd = async () => {
    const nextPage = topbarMenu.page + 1

    if (!topbarMenu.loading && nextPage <= topbarMenu.lastPage) {
        await gettopbarMenuItems(nextPage)
    }
}

onMounted(() => {
    gettopbarMenuItems()
})
</script>

<template>
    <HorizontalScroller v-if="topbarMenu.items.length || topbarMenu.loading"
        class="flex h-8 max-[450px]:w-full max-[450px]:min-w-0 max-[450px]:overflow-hidden" :loading="topbarMenu.loading"
        :watch-key="`${topbarMenu.items.length}-${topbarMenu.loading}`" @reach-end="handleReachEnd">
        <ul class="h-8 flex items-center gap-3 whitespace-nowrap min-w-0">
            <TopbarMenuItem v-for="item in topbarMenu.items" :key="item.id" :item="item" />

            <li v-if="topbarMenu.loading" class="text-xs text-gray-300 flex-shrink-0">
                Loading...
            </li>
        </ul>
    </HorizontalScroller>
</template>
