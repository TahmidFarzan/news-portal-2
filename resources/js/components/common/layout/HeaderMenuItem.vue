<script setup>
import { ref, nextTick, onMounted, onBeforeUnmount } from "vue"
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { fetchFromApi } from '@/composables/useSystemApi'
import VerticalScroller from '@/components/common/layout/VerticalScroller.vue'

defineOptions({
    name: 'HeaderMenuItem'
})

const { item, level = 0 } = defineProps({
    item: {
        type: Object,
        required: true,
    },
    level: {
        type: Number,
        default: 0,
    },
})

const rootRef = ref(null)
const linkRef = ref(null)

const isOpen = ref(false)
const children = ref([])
const childrenLoading = ref(false)
const childrenLoaded = ref(false)
const childrenPage = ref(0)
const childrenLastPage = ref(1)

const dropdownStyle = ref({})
const closeTimer = ref(null)

const normalizeMenuItems = (items = []) => {
    return items.map((item) => ({
        ...item,
        children: item.children ?? [],
    }))
}

const updateDropdownPosition = () => {
    if (level !== 0 || !linkRef.value) return

    const rect = linkRef.value.getBoundingClientRect()
    const width = Math.max(rect.width + 80, 240)
    const maxLeft = window.innerWidth - width - 12
    const left = Math.max(12, Math.min(rect.left, maxLeft))

    dropdownStyle.value = {
        position: 'fixed',
        top: `${rect.bottom + 8}px`,
        left: `${left}px`,
        width: `${width}px`,
    }
}

const loadChildren = async (page = 1) => {
    if (!item?.has_descendants) return
    if (childrenLoading.value) return
    if (childrenLoaded.value && page > childrenLastPage.value) return

    try {
        childrenLoading.value = true

        const response = await fetchFromApi(
            route('site.theme.menu-item.sub-menu-items', {
                slug: item.slug,
                page,
            })
        )

        const items = normalizeMenuItems(response?.items ?? [])

        children.value = page === 1
            ? items
            : [...children.value, ...items]

        childrenPage.value = Number(response?.current_page ?? page)
        childrenLastPage.value = Number(response?.last_page ?? page)
        childrenLoaded.value = true
    } catch (error) {
        console.error('Failed to fetch submenu items:', error)
    } finally {
        childrenLoading.value = false
    }
}

const openMenu = async () => {
    if (!item.has_descendants) return

    clearTimeout(closeTimer.value)
    isOpen.value = true

    await nextTick()
    updateDropdownPosition()

    if (!childrenLoaded.value) {
        await loadChildren(1)
    }
}

const closeMenu = () => {
    clearTimeout(closeTimer.value)

    closeTimer.value = setTimeout(() => {
        isOpen.value = false
    }, 180)
}

const keepMenuOpen = () => {
    clearTimeout(closeTimer.value)
}

const handleSubMenuReachEnd = async () => {
    if (childrenLoading.value) return

    const nextPage = childrenPage.value + 1

    if (nextPage <= childrenLastPage.value) {
        await loadChildren(nextPage)
    }
}

const handleClickOutside = (event) => {
    if (!isOpen.value || !rootRef.value) return

    if (!rootRef.value.contains(event.target)) {
        isOpen.value = false
    }
}

const handleWindowChange = () => {
    if (!isOpen.value) return

    updateDropdownPosition()
}

onMounted(async () => {
    if (item.has_descendants) {
        await loadChildren(1)
    }

    document.addEventListener('click', handleClickOutside)
    window.addEventListener('resize', handleWindowChange)
    window.addEventListener('scroll', handleWindowChange, true)
})

onBeforeUnmount(() => {
    clearTimeout(closeTimer.value)

    document.removeEventListener('click', handleClickOutside)
    window.removeEventListener('resize', handleWindowChange)
    window.removeEventListener('scroll', handleWindowChange, true)
})
</script>

<template>
    <li ref="rootRef" class="relative flex-shrink-0" :class="{ 'w-full': level > 0 }" @mouseenter="openMenu"
        @mouseleave="closeMenu">
        <a ref="linkRef" :href="item.public_url || '#'"
            class="flex items-center justify-between gap-2 px-3 py-2 text-sm rounded-lg transition"
            :class="level === 0 ? 'text-white hover:bg-white/10' : 'text-gray-800 hover:bg-gray-100'">
            <span>{{ item.name }}</span>

            <FontAwesomeIcon v-if="item.has_descendants" icon="chevron-down" class="text-[10px] transition-transform"
                :class="[
                    isOpen ? 'rotate-180' : '',
                    level > 0 ? '-rotate-90' : ''
                ]" />
        </a>

        <Transition enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 scale-95 translate-y-1" enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-1">
            <div v-if="item.has_descendants && isOpen"
                class="bg-white text-gray-800 shadow-lg border border-gray-200 rounded-xl max-h-72 z-[999] relative overflow-visible"
                :class="level === 0 ? '' : 'absolute left-full top-0 ml-1 min-w-52'"
                :style="level === 0 ? dropdownStyle : {}" @mouseenter="keepMenuOpen" @mouseleave="closeMenu">
                <VerticalScroller max-height-class="max-h-72" :loading="childrenLoading"
                    :watch-key="`${children.length}-${childrenLoading}-${isOpen}`" @reach-end="handleSubMenuReachEnd">
                    <ul class="py-1">
                        <HeaderMenuItem v-for="child in children" :key="child.id" :item="child" :level="level + 1" />

                        <li v-if="childrenLoading" class="px-3 py-2 text-sm text-gray-400">
                            Loading...
                        </li>

                        <li v-if="childrenLoaded && !childrenLoading && !children.length"
                            class="px-3 py-2 text-sm text-gray-400">
                            No items
                        </li>
                    </ul>
                </VerticalScroller>
            </div>
        </Transition>
    </li>
</template>
