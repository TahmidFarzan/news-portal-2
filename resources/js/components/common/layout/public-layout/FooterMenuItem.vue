<script setup>
import { computed, ref } from 'vue'

const {
    item,
    level = 0
} = defineProps({
    item: {
        type: Object,
        required: true
    },
    level: {
        type: Number,
        default: 0
    }
})

import { useTranslate } from '@/composables/useTranslate'

const { t } = useTranslate()

const isOpen = ref(false)

const title = computed(() => {
    return item.title
        ?? item.name
        ?? item.label
        ?? t("layout.public.footerMenuItem.labels.menu")
})

const children = computed(() => item.children ?? [])

const hasChildren = computed(() => children.value.length > 0)

const isRoot = computed(() => level === 0)

const linkClasses = computed(() => {
    if (isRoot.value) {
        return 'inline-flex items-center gap-1 text-sm text-gray-600 hover:text-blue-600 hover:underline transition'
    }

    return 'flex items-center justify-between gap-3 w-full px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 transition'
})

const dropdownClasses = computed(() => {
    if (isRoot.value) {
        return 'absolute left-1/2 bottom-full mb-2 min-w-44 -translate-x-1/2 bg-white border border-gray-200 shadow-lg rounded-xl py-1 z-[999]'
    }

    return 'absolute left-full top-0 ml-1 min-w-44 bg-white border border-gray-200 shadow-lg rounded-xl py-1 z-[999]'
})

</script>

<template>
    <li class="relative" @mouseenter="isOpen = true" @mouseleave="isOpen = false">
        <a :href="item?.public_url" :class="linkClasses">
            <span>{{ item.name }}</span>
            <span v-if="hasChildren" class="text-xs">▾</span>
        </a>

        <Transition enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 scale-95 translate-y-1" enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-1">
            <ul v-if="hasChildren && isOpen" :class="dropdownClasses">
                <FooterMenuItem v-for="child in children" :key="child.id" :item="child" :level="level + 1" />
            </ul>
        </Transition>
    </li>
</template>
