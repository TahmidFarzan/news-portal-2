<script setup>
import { ref, nextTick, onMounted, onBeforeUnmount } from "vue"
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { fetchFromApi } from '@/composables/useSystemApi'

defineOptions({
    name: 'HeaderMenuItem'
})

const props = defineProps({
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
const submenuRef = ref(null)
const submenuTrackRef = ref(null)

const isOpen = ref(false)
const children = ref([])
const childrenLoading = ref(false)
const childrenLoaded = ref(false)
const childrenPage = ref(0)
const childrenLastPage = ref(1)

const dropdownStyle = ref({})
const closeTimer = ref(null)

const submenuThumbHeight = ref(0)
const submenuThumbTop = ref(0)
const submenuDragging = ref(false)
const submenuDragStartY = ref(0)
const submenuDragStartTop = ref(0)

const normalizeMenuItems = (items = []) => {
    return items.map((item) => ({
        ...item,
        children: item.children ?? [],
    }))
}

const updateDropdownPosition = () => {
    if (props.level !== 0 || !linkRef.value) return

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

const updateSubMenuScrollbar = () => {
    const el = submenuRef.value

    if (!el) return

    const clientHeight = el.clientHeight
    const scrollHeight = el.scrollHeight
    const scrollTop = el.scrollTop

    if (scrollHeight <= clientHeight) {
        submenuThumbHeight.value = 0
        submenuThumbTop.value = 0
        return
    }

    const trackHeight = submenuTrackRef.value?.clientHeight || clientHeight
    const thumbHeight = Math.max((clientHeight / scrollHeight) * trackHeight, 28)
    const maxThumbTop = trackHeight - thumbHeight
    const maxScrollTop = scrollHeight - clientHeight

    submenuThumbHeight.value = thumbHeight
    submenuThumbTop.value = (scrollTop / maxScrollTop) * maxThumbTop
}

const scrollSubMenuByThumbPosition = (thumbTop) => {
    const el = submenuRef.value
    const track = submenuTrackRef.value

    if (!el || !track || !submenuThumbHeight.value) return

    const maxThumbTop = track.clientHeight - submenuThumbHeight.value
    const maxScrollTop = el.scrollHeight - el.clientHeight

    if (maxThumbTop <= 0 || maxScrollTop <= 0) return

    const safeTop = Math.max(0, Math.min(thumbTop, maxThumbTop))

    el.scrollTop = (safeTop / maxThumbTop) * maxScrollTop

    updateSubMenuScrollbar()
    handleSubMenuScroll()
}

const loadChildren = async (page = 1) => {
    if (!props.item?.has_descendants) return
    if (childrenLoading.value) return
    if (childrenLoaded.value && page > childrenLastPage.value) return

    try {
        childrenLoading.value = true

        const response = await fetchFromApi(
            route('site.theme.menu-item.sub-menu-items', {
                slug: props.item.slug,
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

        await nextTick()
        updateSubMenuScrollbar()
    } catch (error) {
        console.error('Failed to fetch submenu items:', error)
    } finally {
        childrenLoading.value = false
    }
}

const openMenu = async () => {
    if (!props.item.has_descendants) return

    clearTimeout(closeTimer.value)
    isOpen.value = true

    await nextTick()
    updateDropdownPosition()

    if (!childrenLoaded.value) {
        await loadChildren(1)
    }

    await nextTick()
    updateSubMenuScrollbar()
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

const handleSubMenuScroll = async () => {
    const el = submenuRef.value

    if (!el || childrenLoading.value) return

    updateSubMenuScrollbar()

    const almostEnd = el.scrollTop + el.clientHeight >= el.scrollHeight - 60

    if (!almostEnd) return

    const nextPage = childrenPage.value + 1

    if (nextPage <= childrenLastPage.value) {
        await loadChildren(nextPage)
        await nextTick()
        updateSubMenuScrollbar()
    }
}

const handleSubMenuWheel = (event) => {
    const el = submenuRef.value

    if (!el) return

    const rawDelta = Math.abs(event.deltaY) >= Math.abs(event.deltaX)
        ? event.deltaY
        : event.deltaX

    if (!rawDelta) return

    const delta = event.deltaMode === 1
        ? rawDelta * 16
        : rawDelta

    event.preventDefault()
    event.stopPropagation()

    el.scrollTop += delta

    updateSubMenuScrollbar()
    handleSubMenuScroll()
}

const handleSubmenuTrackPointerDown = (event) => {
    const track = submenuTrackRef.value

    if (!track || !submenuThumbHeight.value) return

    event.preventDefault()
    event.stopPropagation()

    const rect = track.getBoundingClientRect()
    const clickY = event.clientY - rect.top
    const targetTop = clickY - submenuThumbHeight.value / 2

    scrollSubMenuByThumbPosition(targetTop)
}

const handleSubmenuThumbPointerDown = (event) => {
    event.preventDefault()
    event.stopPropagation()

    submenuDragging.value = true
    submenuDragStartY.value = event.clientY
    submenuDragStartTop.value = submenuThumbTop.value

    event.currentTarget.setPointerCapture(event.pointerId)
}

const handleSubmenuThumbPointerMove = (event) => {
    if (!submenuDragging.value) return

    event.preventDefault()
    event.stopPropagation()

    const diff = event.clientY - submenuDragStartY.value

    scrollSubMenuByThumbPosition(submenuDragStartTop.value + diff)
}

const handleSubmenuThumbPointerUp = (event) => {
    submenuDragging.value = false

    if (event.currentTarget.hasPointerCapture(event.pointerId)) {
        event.currentTarget.releasePointerCapture(event.pointerId)
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
    updateSubMenuScrollbar()
}

onMounted(async () => {
    if (props.item.has_descendants) {
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
                <div ref="submenuRef" @scroll.passive="handleSubMenuScroll" @wheel.prevent.stop="handleSubMenuWheel"
                    class="max-h-72 overflow-y-auto overflow-x-visible pr-3 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
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
                </div>

                <div v-if="submenuThumbHeight" ref="submenuTrackRef" @pointerdown="handleSubmenuTrackPointerDown"
                    @wheel.prevent.stop="handleSubMenuWheel"
                    class="absolute right-1 top-2 bottom-2 w-3 cursor-pointer flex justify-center">
                    <div class="relative h-full w-[2px] rounded-full bg-gray-200/70">
                        <div @pointerdown="handleSubmenuThumbPointerDown" @pointermove="handleSubmenuThumbPointerMove"
                            @pointerup="handleSubmenuThumbPointerUp" @pointercancel="handleSubmenuThumbPointerUp"
                            @wheel.prevent.stop="handleSubMenuWheel"
                            class="absolute left-1/2 w-[2px] cursor-grab rounded-full bg-gray-400 transition-colors hover:bg-gray-600 active:cursor-grabbing"
                            :style="{
                                height: `${submenuThumbHeight}px`,
                                transform: `translateX(-50%) translateY(${submenuThumbTop}px)`
                            }"></div>
                    </div>
                </div>
            </div>
        </Transition>
    </li>
</template>
