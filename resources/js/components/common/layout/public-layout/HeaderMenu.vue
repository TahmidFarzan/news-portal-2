<script setup>
import { reactive, onMounted } from 'vue'
import HorizontalScroller from '@/components/common/layout/HorizontalScroller.vue'
import HeaderMenuItem from '@/components/common/layout/public-layout/HeaderMenuItem.vue'
import { fetchFromApi } from '@/composables/useSystemApi'

const headerMenu = reactive({
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

const getHeaderMenuItems = async (pageNumber = 1) => {
    if (headerMenu.loading || pageNumber > headerMenu.lastPage) return

    try {
        headerMenu.loading = true

        const response = await fetchFromApi(
            route('site.theme.menus.header-menu-items', { page: pageNumber })
        )

        const items = normalizeMenuItems(response?.items ?? [])

        headerMenu.items = pageNumber === 1
            ? items
            : [...headerMenu.items, ...items]

        headerMenu.page = Number(response?.current_page ?? pageNumber)
        headerMenu.lastPage = Number(response?.last_page ?? pageNumber)
    } catch (error) {
        console.error('Failed to fetch header menu:', error)
    } finally {
        headerMenu.loading = false
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
    <HorizontalScroller v-if="headerMenu.items.length || headerMenu.loading" class="w-full min-w-0 h-full flex"
        :loading="headerMenu.loading" :watch-key="`${headerMenu.items.length}-${headerMenu.loading}`"
        @reach-end="handleReachEnd">
        <ul class="h-10 flex items-center gap-2 whitespace-nowrap">
            <HeaderMenuItem v-for="item in headerMenu.items" :key="item.id" :item="item" />

            <li v-if="headerMenu.loading" class="h-10 flex items-center px-3 text-sm text-gray-300 flex-shrink-0">
                Loading...
            </li>
        </ul>
    </HorizontalScroller>
</template>
