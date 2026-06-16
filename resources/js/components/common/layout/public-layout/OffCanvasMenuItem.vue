<script setup>
import { ref, onMounted, watch } from "vue"
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { fetchFromApi } from '@/composables/useSystemApi'
import VerticalScroller from '@/components/common/layout/VerticalScroller.vue'

import { useTranslate } from '@/composables/useTranslate'

const { t } = useTranslate()

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

const isOpen = ref(false)
const children = ref([])
const childrenLoading = ref(false)
const childrenLoaded = ref(false)
const childrenPage = ref(0)
const childrenLastPage = ref(1)

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

const loadChildren = async (page = 1) => {
    if (!item?.has_descendants) return
    if (childrenLoading.value) return
    if (childrenLoaded.value && page > childrenLastPage.value) return

    try {
        childrenLoading.value = true

        const response = await fetchFromApi(
            route('site.menu-items.sub-menu-items', {
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
        console.error('Failed to fetch off-canvas submenu items:', error)
    } finally {
        childrenLoading.value = false
    }
}

const autoLoadChildren = async () => {
    if (item?.has_descendants && !childrenLoaded.value) {
        await loadChildren(1)
    }
}

onMounted(() => {
    autoLoadChildren()
})

watch(
    () => item?.id,
    () => {
        resetChildrenState()
        autoLoadChildren()
    }
)

const toggleSubMenu = async () => {
    if (!item.has_descendants) return

    isOpen.value = !isOpen.value

    if (isOpen.value && !childrenLoaded.value) {
        await loadChildren(1)
    }
}

const handleReachEnd = async () => {
    if (childrenLoading.value) return

    const nextPage = childrenPage.value + 1

    if (nextPage <= childrenLastPage.value) {
        await loadChildren(nextPage)
    }
}
</script>

<template>
    <li class="w-full">
        <div class="flex items-center gap-2 rounded-lg transition"
            :class="level === 0 ? 'hover:bg-gray-100' : 'hover:bg-gray-50'">
            <a :href="item.public_url || '#'" class="flex-1 min-w-0 px-3 py-2 text-sm text-gray-700"
                :class="level > 0 ? 'pl-5' : ''">
                <span class="block truncate">
                    {{ item.name }}
                </span>
            </a>

            <button v-if="item.has_descendants" type="button" @click.stop="toggleSubMenu"
                class="mr-1 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-200 hover:text-gray-800">
                <FontAwesomeIcon icon="chevron-down" class="text-xs transition-transform"
                    :class="{ 'rotate-180': isOpen }" />
            </button>
        </div>

        <Transition enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-1">
            <div v-if="item.has_descendants && isOpen" class="ml-3 mt-1 border-l border-gray-200 pl-2">
                <VerticalScroller max-height-class="max-h-64" :loading="childrenLoading"
                    :watch-key="`${children.length}-${childrenLoading}-${isOpen}`" @reach-end="handleReachEnd">
                    <ul class="space-y-1 py-1">
                        <OffCanvasMenuItem v-for="child in children" :key="child.id" :item="child" :level="level + 1" />

                        <li v-if="childrenLoading" class="px-3 py-2 text-sm text-gray-400">
                            {{ t("labels.loading") }}
                        </li>

                        <li v-if="childrenLoaded && !childrenLoading && !children.length"
                            class="px-3 py-2 text-sm text-gray-400">
                            {{ t("labels.no_menu_found") }}
                        </li>
                    </ul>
                </VerticalScroller>
            </div>
        </Transition>
    </li>
</template>
