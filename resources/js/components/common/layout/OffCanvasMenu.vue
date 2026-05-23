<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import VerticalScroller from '@/components/common/layout/VerticalScroller.vue'
import OffCanvasMenuItem from '@/components/common/layout/OffCanvasMenuItem.vue'
import { fetchFromApi } from '@/composables/useSystemApi'

import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faBars,
    faXmark
} from '@fortawesome/free-solid-svg-icons'

library.add(faBars, faXmark)

const appName = import.meta.env.VITE_APP_NAME
const appLogo = import.meta.env.VITE_APP_LOGO

const showOffCanvas = ref(false)

const offCanvasMenu = reactive({
    items: [],
    loading: false,
    page: 1,
    lastPage: 1
})

const hasOffCanvasMenu = computed(() => offCanvasMenu.items.length > 0)

const normalizeMenuItems = (items = []) => {
    return items.map((item) => ({
        ...item,
        children: item.children ?? []
    }))
}

const getOffCanvasMenuItems = async (pageNumber = 1) => {
    if (offCanvasMenu.loading || pageNumber > offCanvasMenu.lastPage) return

    try {
        offCanvasMenu.loading = true

        const response = await fetchFromApi(
            route('site.theme.menus.off-canvas-menu-items', { page: pageNumber })
        )

        const items = normalizeMenuItems(response?.items ?? [])

        offCanvasMenu.items = pageNumber === 1
            ? items
            : [...offCanvasMenu.items, ...items]

        offCanvasMenu.page = Number(response?.current_page ?? pageNumber)
        offCanvasMenu.lastPage = Number(response?.last_page ?? pageNumber)
    } catch (error) {
        console.error('Failed to fetch off canvas menu:', error)
    } finally {
        offCanvasMenu.loading = false
    }
}

const handleReachEnd = async () => {
    const nextPage = offCanvasMenu.page + 1

    if (!offCanvasMenu.loading && nextPage <= offCanvasMenu.lastPage) {
        await getOffCanvasMenuItems(nextPage)
    }
}

const closeOffCanvas = () => {
    showOffCanvas.value = false
}

watch(showOffCanvas, async (open) => {
    if (open && !offCanvasMenu.items.length) {
        await getOffCanvasMenuItems()
    }
})

onMounted(() => {
    getOffCanvasMenuItems()
})
</script>

<template>
    <button v-if="hasOffCanvasMenu" type="button" @click="showOffCanvas = true"
        class="w-10 h-10 flex items-center justify-center rounded-lg hover:bg-white/10" aria-label="Open menu">
        <FontAwesomeIcon icon="bars" />
    </button>

    <Teleport to="body">
        <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition-opacity duration-150"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showOffCanvas && hasOffCanvasMenu" class="fixed inset-0 bg-black/50 z-[998]"
                @click="closeOffCanvas" />
        </Transition>

        <Transition enter-active-class="transition transform duration-200 ease-out" enter-from-class="translate-x-full"
            enter-to-class="translate-x-0" leave-active-class="transition transform duration-150 ease-in"
            leave-from-class="translate-x-0" leave-to-class="translate-x-full">
            <aside v-if="showOffCanvas && hasOffCanvasMenu"
                class="fixed right-0 top-0 h-full w-80 max-w-[90vw] bg-white shadow-xl z-[999] flex flex-col">
                <div class="flex items-center justify-between px-4 py-3 border-b">
                    <a :href="route('home')" class="inline-flex items-center">
                        <img v-if="appLogo" :src="appLogo" :alt="appName" class="h-10 max-w-40 object-contain">

                        <span v-else class="text-lg font-semibold text-gray-800">
                            {{ appName }}
                        </span>
                    </a>

                    <button type="button" @click="closeOffCanvas"
                        class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100"
                        aria-label="Close menu">
                        <FontAwesomeIcon icon="xmark" />
                    </button>
                </div>

                <div class="flex-1 min-h-0 p-4">
                    <VerticalScroller max-height-class="max-h-[calc(100vh-140px)]" :loading="offCanvasMenu.loading"
                        :watch-key="`${offCanvasMenu.items.length}-${offCanvasMenu.loading}-${showOffCanvas}`"
                        @reach-end="handleReachEnd">
                        <ul class="space-y-1 pr-1">
                            <OffCanvasMenuItem v-for="item in offCanvasMenu.items" :key="item.id" :item="item"
                                @navigate="closeOffCanvas" />

                            <li v-if="offCanvasMenu.loading" class="px-3 py-2 text-sm text-gray-400">
                                Loading...
                            </li>

                            <li v-if="!offCanvasMenu.loading && !offCanvasMenu.items.length"
                                class="px-3 py-2 text-sm text-gray-400">
                                No menu items
                            </li>
                        </ul>
                    </VerticalScroller>
                </div>
            </aside>
        </Transition>
    </Teleport>
</template>
