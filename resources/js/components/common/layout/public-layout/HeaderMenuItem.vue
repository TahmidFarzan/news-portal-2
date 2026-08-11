<script setup>
import {
    ref,
    nextTick,
    onMounted,
    onBeforeUnmount,
    watch,
} from 'vue'

import { fetchFromApi } from '@/composables/useApiClient'
import { apiCacheKey, apiCacheTTL } from '@/composables/useApiCache'

import VerticalScroller from '@/components/common/layout/VerticalScroller.vue'

import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faChevronDown,
} from '@fortawesome/free-solid-svg-icons'

import { useTranslate } from '@/composables/useTranslate'

library.add(faChevronDown)

const { t } = useTranslate()

const {
    item,
    currentLanguage,
    level = 0,
} = defineProps({
    item: {
        type: Object,
        required: true,
    },
    currentLanguage: {
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

const resetChildrenState = () => {
    children.value = []
    childrenLoading.value = false
    childrenLoaded.value = false
    childrenPage.value = 0
    childrenLastPage.value = 1
}

const updateDropdownPosition = () => {
    if (
        level !== 0 ||
        !linkRef.value
    ) {
        return
    }

    const rect = linkRef.value.getBoundingClientRect()

    const width = Math.max(
        rect.width + 80,
        240,
    )

    const viewportPadding = 12

    const maxLeft = Math.max(
        viewportPadding,
        window.innerWidth - width - viewportPadding,
    )

    const left = Math.max(
        viewportPadding,
        Math.min(rect.left, maxLeft),
    )

    const header = linkRef.value.closest('.public-header')

    const headerBottom = header
        ? header.getBoundingClientRect().bottom
        : rect.bottom

    const top = Math.max(
        rect.bottom + 8,
        headerBottom + 4,
    )

    dropdownStyle.value = {
        position: 'fixed',
        top: `${top}px`,
        left: `${left}px`,
        width: `${width}px`,
        zIndex: 10000,
    }
}

const getMenuApiUrl = (slug, pageNumber = 1) => {
    return currentLanguage?.is_default
        ? route('site.menu-items.sub-menu-items', {
            slug,
            page: pageNumber,
        })
        : route('localized.site.menu-items.sub-menu-items', {
            languageCode: currentLanguage?.code,
            slug,
            page: pageNumber,
        })
}

const loadChildren = async (page = 1) => {
    if (!item?.has_descendants) {
        return
    }

    if (childrenLoading.value) {
        return
    }

    if (
        childrenLoaded.value &&
        page > childrenLastPage.value
    ) {
        return
    }

    try {
        childrenLoading.value = true

        const apiUrl = getMenuApiUrl(
            item.slug,
            page,
        )

        const response = await fetchFromApi(
            apiUrl,
            {},
            {
                key: `${apiCacheKey.API_LAYOUT_HEADER_MENU}:${apiUrl}`,
                ttl: apiCacheTTL.LAYOUT_MENU,
            },
        )

        const items = normalizeMenuItems(
            response?.items ?? [],
        )

        children.value = page === 1
            ? items
            : [...children.value, ...items]

        childrenPage.value = Number(
            response?.current_page ?? page,
        )

        childrenLastPage.value = Number(
            response?.last_page ?? page,
        )

        childrenLoaded.value = true
    } catch (error) {
        console.error(
            'Failed to fetch submenu items:',
            error,
        )
    } finally {
        childrenLoading.value = false
    }
}

const autoLoadChildren = async () => {
    if (
        item?.has_descendants &&
        !childrenLoaded.value
    ) {
        await loadChildren(1)
    }
}

const openMenu = async () => {
    if (!item?.has_descendants) {
        return
    }

    clearTimeout(closeTimer.value)

    isOpen.value = true

    await nextTick()

    updateDropdownPosition()

    if (!childrenLoaded.value) {
        await loadChildren(1)

        await nextTick()

        updateDropdownPosition()
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
    if (childrenLoading.value) {
        return
    }

    const nextPage = childrenPage.value + 1

    if (nextPage <= childrenLastPage.value) {
        await loadChildren(nextPage)
    }
}

const handleClickOutside = (event) => {
    if (
        !isOpen.value ||
        !rootRef.value
    ) {
        return
    }

    const target = event.target

    if (
        rootRef.value.contains(target)
    ) {
        return
    }

    const dropdown = document.querySelector(
        `[data-header-dropdown="${item.id}"]`,
    )

    if (
        dropdown &&
        dropdown.contains(target)
    ) {
        return
    }

    isOpen.value = false
}

const handleWindowChange = () => {
    if (!isOpen.value) {
        return
    }

    updateDropdownPosition()
}

watch(
    () => item?.id,
    async () => {
        isOpen.value = false

        resetChildrenState()

        await autoLoadChildren()
    },
)

onMounted(() => {
    autoLoadChildren()

    document.addEventListener(
        'click',
        handleClickOutside,
    )

    window.addEventListener(
        'resize',
        handleWindowChange,
    )

    window.addEventListener(
        'scroll',
        handleWindowChange,
        true,
    )
})

onBeforeUnmount(() => {
    clearTimeout(closeTimer.value)

    document.removeEventListener(
        'click',
        handleClickOutside,
    )

    window.removeEventListener(
        'resize',
        handleWindowChange,
    )

    window.removeEventListener(
        'scroll',
        handleWindowChange,
        true,
    )
})
</script>

<template>
    <li ref="rootRef" class="relative flex-shrink-0" :class="{ 'w-full': level > 0 }" @mouseenter="openMenu"
        @mouseleave="closeMenu">
        <a ref="linkRef" :href="item.public_url || '#'"
            class="flex items-center justify-between gap-2 rounded-lg px-3 py-2 text-sm transition" :class="level === 0
                    ? 'text-white hover:bg-white/10'
                    : 'text-gray-800 hover:bg-gray-100'
                ">
            <span>
                {{ item.name }}
            </span>

            <FontAwesomeIcon v-if="item.has_descendants" icon="chevron-down" class="text-[10px] transition-transform"
                :class="[
                    isOpen ? 'rotate-180' : '',
                    level > 0 ? '-rotate-90' : '',
                ]" />
        </a>

        <Teleport v-if="level === 0" to="body">
            <Transition enter-active-class="transition ease-out duration-150"
                enter-from-class="translate-y-1 scale-95 opacity-0" enter-to-class="translate-y-0 scale-100 opacity-100"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="translate-y-0 scale-100 opacity-100"
                leave-to-class="translate-y-1 scale-95 opacity-0">
                <div v-if="item.has_descendants && isOpen" :data-header-dropdown="item.id"
                    class="overflow-visible rounded-xl border border-gray-200 bg-white text-gray-800 shadow-lg"
                    :style="dropdownStyle" @mouseenter="keepMenuOpen" @mouseleave="closeMenu">
                    <VerticalScroller max-height-class="max-h-72" :loading="childrenLoading"
                        :watch-key="`${children.length}-${childrenLoading}-${isOpen}`"
                        @reach-end="handleSubMenuReachEnd">
                        <ul class="py-1">
                            <HeaderMenuItem v-for="child in children" :key="child.id" :item="child" :level="level + 1"
                                :currentLanguage="currentLanguage" />

                            <li v-if="childrenLoading" class="px-3 py-2 text-sm text-gray-400">
                                {{ t('common.labels.loading') }}
                            </li>

                            <li v-if="
                                childrenLoaded &&
                                !childrenLoading &&
                                !children.length
                            " class="px-3 py-2 text-sm text-gray-400">
                                {{ t('common.labels.noMenuFound') }}
                            </li>
                        </ul>
                    </VerticalScroller>
                </div>
            </Transition>
        </Teleport>

        <Transition v-if="level > 0" enter-active-class="transition ease-out duration-150"
            enter-from-class="translate-y-1 scale-95 opacity-0" enter-to-class="translate-y-0 scale-100 opacity-100"
            leave-active-class="transition ease-in duration-100" leave-from-class="translate-y-0 scale-100 opacity-100"
            leave-to-class="translate-y-1 scale-95 opacity-0">
            <div v-if="item.has_descendants && isOpen"
                class="relative z-[999] overflow-visible rounded-xl border border-gray-200 bg-white text-gray-800 shadow-lg absolute left-full top-0 ml-1 min-w-52"
                @mouseenter="keepMenuOpen" @mouseleave="closeMenu">
                <VerticalScroller max-height-class="max-h-72" :loading="childrenLoading"
                    :watch-key="`${children.length}-${childrenLoading}-${isOpen}`" @reach-end="handleSubMenuReachEnd">
                    <ul class="py-1">
                        <HeaderMenuItem v-for="child in children" :key="child.id" :item="child" :level="level + 1"
                            :currentLanguage="currentLanguage" />

                        <li v-if="childrenLoading" class="px-3 py-2 text-sm text-gray-400">
                            {{ t('common.labels.loading') }}
                        </li>

                        <li v-if="
                            childrenLoaded &&
                            !childrenLoading &&
                            !children.length
                        " class="px-3 py-2 text-sm text-gray-400">
                            {{ t('common.labels.noMenuFound') }}
                        </li>
                    </ul>
                </VerticalScroller>
            </div>
        </Transition>
    </li>
</template>
