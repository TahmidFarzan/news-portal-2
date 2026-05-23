<script setup>
import { reactive, onMounted } from 'vue'
import FooterMenuItem from '@/components/common/layout/FooterMenuItem.vue'
import { fetchFromApi } from '@/composables/useSystemApi'

const footerMenu = reactive({
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

const getFooterMenuItems = async (pageNumber = 1) => {
    if (footerMenu.loading || pageNumber > footerMenu.lastPage) return

    try {
        footerMenu.loading = true

        const response = await fetchFromApi(
            route('site.theme.menus.footer-menu-items', { page: pageNumber })
        )

        const items = normalizeMenuItems(response?.items ?? [])

        footerMenu.items = pageNumber === 1
            ? items
            : [...footerMenu.items, ...items]

        footerMenu.page = Number(response?.current_page ?? pageNumber)
        footerMenu.lastPage = Number(response?.last_page ?? pageNumber)
    } catch (error) {
        console.error('Failed to fetch footer menu:', error)
    } finally {
        footerMenu.loading = false
    }
}

onMounted(() => {
    getFooterMenuItems()
})
</script>

<template>
    <nav v-if="footerMenu.items.length || footerMenu.loading" class="w-full md:w-auto md:flex-1 min-w-0"
        aria-label="Footer menu">
        <ul class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1 text-center">
            <FooterMenuItem v-for="item in footerMenu.items" :key="item.id" :item="item" />

            <li v-if="footerMenu.loading" class="text-xs text-gray-400">
                Loading...
            </li>
        </ul>
    </nav>
</template>
