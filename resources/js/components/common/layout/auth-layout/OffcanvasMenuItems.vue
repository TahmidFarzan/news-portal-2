<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

import {
    faUser,
    faUsers,
    faChevronDown,
    faChevronUp,
    faRectangleList,
    faGauge,
    faPhotoFilm,
    faNewspaper,
    faLayerGroup,
    faTags,
    faStar,
    faFan,
    faGlobe,
    faEllipsisVertical,
    faGears,
    faFile
} from '@fortawesome/free-solid-svg-icons'

import { useTranslate } from '@/composables/useTranslate'

library.add(
    faUser,
    faUsers,
    faChevronDown,
    faChevronUp,
    faRectangleList,
    faGauge,
    faPhotoFilm,
    faNewspaper,
    faLayerGroup,
    faTags,
    faStar,
    faFan,
    faGlobe,
    faEllipsisVertical,
    faGears,
    faFile
)
const { t } = useTranslate()

import {
    canAccessUserManagementMenu,
    canAccessNewsAttributesMenu,
    canAccessNewsMenu,
    canAccessBreakingNewsMenu,
    canAccessPageMenu,
    canAccessMenuMenu,
    canAccessTheme,
} from '@/composables/useAuthUserAccessPermissions'

const {
    authUser
} = defineProps({
    authUser: {
        type: Object,
        default: null
    }
})

const emit = defineEmits(['navigate'])

const page = usePage()

const subMenus = ref({
    UserManagement: false,
    Reports: false,
    NewsAttributes: false
})

const routeMap = {
    UserManagement: ['/back-office/users/*'],
    NewsAttributes: [
        '/back-office/categories/*',
        '/back-office/tags/*',
        '/back-office/trends/*',
        '/back-office/locations/*',
        '/back-office/events/*',
        '/back-office/contributors/*'
    ],
    Reports: ['/back-office/reports/*']
}

const canAccessUserManagementMenuComputed = computed(() => {
    return canAccessUserManagementMenu(authUser)
})

const canAccessNewsAttributesMenuComputed = computed(() => {
    return canAccessNewsAttributesMenu(authUser)
})

const canAccessNewsMenuComputed = computed(() => {
    return canAccessNewsMenu(authUser)
})
const canAccessBreakingNewsMenuComputed = computed(() => {
    return canAccessBreakingNewsMenu(authUser)
})
const canAccessPageMenuComputed = computed(() => {
    return canAccessPageMenu(authUser)
})

const canAccessMenuMenuComputed = computed(() => {
    return canAccessMenuMenu(authUser)
})

const canAccessThemeComputed = computed(() => {
    return canAccessTheme(authUser)
})

const toggleShowSubMenu = (key) => {
    subMenus.value[key] = !subMenus.value[key]
}

const handleNavigate = () => {
    emit('navigate')
}

const isCurrentPage = (url) => {
    const currentUrl = typeof page.url === 'string'
        ? page.url.split('?')[0].replace(/\/+$/, '')
        : ''

    const cleanUrl = url.replace(/\/+$/, '')

    if (cleanUrl.endsWith('/*')) {
        const basePattern = cleanUrl.slice(0, -2)

        return currentUrl === basePattern || currentUrl.startsWith(`${basePattern}/`)
    }

    return currentUrl === cleanUrl
}

const isAnyCurrentPage = (urls = []) => {
    return urls.some((url) => isCurrentPage(url))
}

const isSubMenuVisible = (key) => {
    const routes = routeMap[key] || []
    const inRoute = isAnyCurrentPage(routes)

    return subMenus.value[key] || inRoute
}
</script>

<template>
    <div class="flex flex-col space-y-1 text-sm">

        <a :href="route('auth-user.dashboard.index')"
            class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
            :class="isCurrentPage('/auth-user/dashboard/*') ? 'bg-gray-200 font-medium' : ''">
            <FontAwesomeIcon icon="gauge" />
            {{ t("components.common.layout.auth_layout.offcanvas_menu_items.navigation.dashboard") }}
        </a>

        <a :href="route('back-office.medias.index')" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
            :class="isCurrentPage('/auth-user/medias/*') ? 'bg-gray-200 font-medium' : ''">
            <FontAwesomeIcon icon="photo-film" />
            {{ t("components.common.layout.auth_layout.offcanvas_menu_items.navigation.medias") }}
        </a>

        <button @click="toggleShowSubMenu('NewsAttributes')"
            class="flex items-center justify-between w-full px-3 py-2 rounded hover:bg-gray-100">
            <span class="flex items-center gap-2">
                <FontAwesomeIcon icon="layer-group" />
                {{ t("components.common.layout.auth_layout.offcanvas_menu_items.navigation.news_attributes") }}
            </span>
            <FontAwesomeIcon :icon="isSubMenuVisible('NewsAttributes') ? 'chevron-up' : 'chevron-down'" />
        </button>

        <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 max-h-0"
            enter-to-class="opacity-100 max-h-40" leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 max-h-40" leave-to-class="opacity-0 max-h-0">
            <div v-if="isSubMenuVisible('NewsAttributes') && canAccessNewsAttributesMenuComputed"
                class="ml-4 flex flex-col space-y-1 overflow-hidden">

                <a :href="route('back-office.categories.index')"
                    class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
                    :class="isCurrentPage('/back-office/categories/*') ? 'bg-gray-200 font-medium' : ''">
                    <FontAwesomeIcon icon="layer-group" />
                    {{ t("components.common.layout.auth_layout.offcanvas_menu_items.navigation.categories") }}
                </a>

                <a :href="route('back-office.tags.index')"
                    class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
                    :class="isCurrentPage('/back-office/tags/*') ? 'bg-gray-200 font-medium' : ''">
                    <FontAwesomeIcon icon="tags" />
                    {{ t("components.common.layout.auth_layout.offcanvas_menu_items.navigation.tags") }}
                </a>

                <a :href="route('back-office.trends.index')"
                    class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
                    :class="isCurrentPage('/back-office/trends/*') ? 'bg-gray-200 font-medium' : ''">
                    <FontAwesomeIcon icon="star" />
                    {{ t("components.common.layout.auth_layout.offcanvas_menu_items.navigation.trends") }}
                </a>

                <a :href="route('back-office.locations.index')"
                    class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
                    :class="isCurrentPage('/back-office/locations/*') ? 'bg-gray-200 font-medium' : ''">
                    <FontAwesomeIcon icon="globe" />
                    {{ t("components.common.layout.auth_layout.offcanvas_menu_items.navigation.location") }}
                </a>

                <a :href="route('back-office.events.index')"
                    class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
                    :class="isCurrentPage('/back-office/events/*') ? 'bg-gray-200 font-medium' : ''">
                    <FontAwesomeIcon icon="fan" />
                    {{ t("components.common.layout.auth_layout.offcanvas_menu_items.navigation.events") }}
                </a>

                <a :href="route('back-office.contributors.index')"
                    class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
                    :class="isCurrentPage('/back-office/contributors/*') ? 'bg-gray-200 font-medium' : ''">
                    <FontAwesomeIcon icon="users" />
                    {{ t("components.common.layout.auth_layout.offcanvas_menu_items.navigation.contributors") }}
                </a>

            </div>
        </Transition>

        <a v-if="canAccessNewsMenuComputed" :href="route('back-office.news.index')"
            class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
            :class="isCurrentPage('/auth-user/news/*') ? 'bg-gray-200 font-medium' : ''">
            <FontAwesomeIcon icon="newspaper" />
            {{ t("components.common.layout.auth_layout.offcanvas_menu_items.navigation.news") }}
        </a>

        <a v-if="canAccessBreakingNewsMenuComputed" :href="route('back-office.breaking-news.index')"
            class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
            :class="isCurrentPage('/auth-user/breaking-news/*') ? 'bg-gray-200 font-medium' : ''">
            <FontAwesomeIcon icon="newspaper" />
            {{ t("components.common.layout.auth_layout.offcanvas_menu_items.navigation.breaking_news") }}
        </a>

        <a v-if="canAccessPageMenuComputed" :href="route('back-office.pages.index')"
            class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
            :class="isCurrentPage('/auth-user/pages/*') ? 'bg-gray-200 font-medium' : ''">
            <FontAwesomeIcon icon="file" />
            {{ t("components.common.layout.auth_layout.offcanvas_menu_items.navigation.pages") }}
        </a>

        <a v-if="canAccessMenuMenuComputed" :href="route('back-office.menus.index')"
            class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
            :class="isCurrentPage('/auth-user/menus/*') ? 'bg-gray-200 font-medium' : ''">
            <FontAwesomeIcon icon="ellipsis-vertical" />
            {{ t("components.common.layout.auth_layout.offcanvas_menu_items.navigation.menus") }}
        </a>

        <button @click="toggleShowSubMenu('UserManagement')"
            class="flex items-center justify-between w-full px-3 py-2 rounded hover:bg-gray-100">
            <span class="flex items-center gap-2">
                <FontAwesomeIcon icon="users" />
                {{ t("components.common.layout.auth_layout.offcanvas_menu_items.navigation.user_anagement") }}
            </span>
            <FontAwesomeIcon :icon="isSubMenuVisible('UserManagement') ? 'chevron-up' : 'chevron-down'" />
        </button>

        <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 max-h-0"
            enter-to-class="opacity-100 max-h-40" leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 max-h-40" leave-to-class="opacity-0 max-h-0">
            <div v-if="isSubMenuVisible('UserManagement') && canAccessUserManagementMenuComputed"
                class="ml-4 flex flex-col space-y-1 overflow-hidden">

                <a :href="route('back-office.users.index')"
                    class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
                    :class="isAnyCurrentPage(routeMap.UserManagement) ? 'bg-gray-200 font-medium' : ''">
                    <FontAwesomeIcon icon="user" />
                    {{ t("components.common.layout.auth_layout.offcanvas_menu_items.navigation.users") }}
                </a>

            </div>
        </Transition>

        <a v-if="canAccessThemeComputed" :href="route('back-office.themes.index')"
            class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
            :class="isCurrentPage('/auth-user/themes/*') ? 'bg-gray-200 font-medium' : ''">
            <FontAwesomeIcon icon="gears" />
            {{ t("components.common.layout.auth_layout.offcanvas_menu_items.navigation.themes") }}
        </a>
    </div>
</template>
