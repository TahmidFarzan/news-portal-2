<script setup>
import { ref, computed } from "vue"
import { usePage } from "@inertiajs/vue3"

import { library } from "@fortawesome/fontawesome-svg-core"
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"

import {
    faUser,
    faUsers,
    faChevronDown,
    faChevronUp,
    faRectangleList,
    faGauge,
    faPhotoFilm,
    faNewspaper,
    faLanguage,
    faLayerGroup,
    faTags,
    faStar,
    faFan,
    faGlobe

} from "@fortawesome/free-solid-svg-icons"

library.add(
    faUser,
    faUsers,
    faChevronDown,
    faChevronUp,
    faRectangleList,
    faGauge,
    faPhotoFilm,
    faNewspaper,
    faLanguage,
    faLayerGroup,
    faTags,
    faStar,
    faFan,
    faGlobe,
)

import {
    canAccessUserManagementMenu,
    canAccessNewsAttributesMenu,
    canAccessStoryMenu
} from '@/composables/useAuthUserAccessPermissions'
import { faHotjar } from "@fortawesome/free-brands-svg-icons"

const { authUser } = defineProps({
    authUser: Object
})

const page = usePage()

const subMenus = ref({
    UserManagement: false,
    Reports: false,
    NewsAttributes: false,
})

const routeMap = {
    UserManagement: ['/back-office/users/*'],
    NewsAttributes: [
        '/back-office/languages/*',
        '/back-office/categories/*',
        '/back-office/tags/*',
        '/back-office/trends/*',
        '/back-office/locations/*',
        '/back-office/events/*',
        '/back-office/contributor/*',
    ],
    Reports: ['/back-office/reports/*'],
}

const canAccessUserManagementMenuComputed = computed(() =>
    canAccessUserManagementMenu(authUser)
)

const canAccessNewsAttributesMenuComputed = computed(() =>
    canAccessNewsAttributesMenu(authUser)
)

const canAccessStoryMenuComputed = computed(() =>
    canAccessStoryMenu(authUser)
)

function toggleShowSubMenu(key) {
    subMenus.value[key] = !subMenus.value[key]
}

function isCurrentPage(url) {
    const currentUrl = typeof page.url === 'string'
        ? page.url.split('?')[0].replace(/\/+$/, '')
        : ''

    const cleanUrl = url.replace(/\/+$/, '')

    if (cleanUrl.endsWith('/*')) {
        const basePattern = cleanUrl.slice(0, -2)
        return currentUrl === basePattern || currentUrl.startsWith(basePattern + '/')
    }

    return currentUrl === cleanUrl
}

function isAnyCurrentPage(urls = []) {
    return urls.some(url => isCurrentPage(url))
}

function isSubMenuVisible(key) {
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
            Dashboard
        </a>

        <a :href="route('back-office.medias.index')" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
            :class="isCurrentPage('/auth-user/medias/*') ? 'bg-gray-200 font-medium' : ''">
            <FontAwesomeIcon icon="photo-film" />
            Medias
        </a>

        <button @click="toggleShowSubMenu('NewsAttributes')"
            class="flex items-center justify-between w-full px-3 py-2 rounded hover:bg-gray-100">
            <span class="flex items-center gap-2">
                <FontAwesomeIcon icon="layer-group" />
                News Attributes
            </span>
            <FontAwesomeIcon :icon="isSubMenuVisible('NewsAttributes') ? 'chevron-up' : 'chevron-down'" />
        </button>

        <transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 max-h-0"
            enter-to-class="opacity-100 max-h-40" leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 max-h-40" leave-to-class="opacity-0 max-h-0">
            <div v-if="isSubMenuVisible('NewsAttributes') && canAccessNewsAttributesMenuComputed"
                class="ml-4 flex flex-col space-y-1 overflow-hidden">

                <a :href="route('back-office.languages.index')"
                    class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
                    :class="isCurrentPage('/back-office/languages/*') ? 'bg-gray-200 font-medium' : ''">
                    <FontAwesomeIcon icon="language" />
                    Languages
                </a>

                <a :href="route('back-office.categories.index')"
                    class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
                    :class="isCurrentPage('/back-office/categories/*') ? 'bg-gray-200 font-medium' : ''">
                    <FontAwesomeIcon icon="layer-group" />
                    Categories
                </a>

                <a :href="route('back-office.tags.index')"
                    class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
                    :class="isCurrentPage('/back-office/tags/*') ? 'bg-gray-200 font-medium' : ''">
                    <FontAwesomeIcon icon="tags" />
                    Tags
                </a>

                <a :href="route('back-office.trends.index')"
                    class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
                    :class="isCurrentPage('/back-office/trends/*') ? 'bg-gray-200 font-medium' : ''">
                    <FontAwesomeIcon icon="star" />
                    Trends
                </a>

                <a :href="route('back-office.locations.index')"
                    class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
                    :class="isCurrentPage('/back-office/locations/*') ? 'bg-gray-200 font-medium' : ''">
                    <FontAwesomeIcon icon="globe" />
                    Locations
                </a>

                <a :href="route('back-office.events.index')"
                    class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
                    :class="isCurrentPage('/back-office/events/*') ? 'bg-gray-200 font-medium' : ''">
                    <FontAwesomeIcon icon="fan" />
                    Events
                </a>

                <a :href="route('back-office.contributors.index')"
                    class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
                    :class="isCurrentPage('/back-office/contributors/*') ? 'bg-gray-200 font-medium' : ''">
                    <FontAwesomeIcon icon="users" />
                    Contributors
                </a>

            </div>
        </transition>

        <a v-if="canAccessStoryMenuComputed" :href="route('back-office.stories.index')" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
            :class="isCurrentPage('/auth-user/stories/*') ? 'bg-gray-200 font-medium' : ''">
            <FontAwesomeIcon icon="newspaper" />
            Stories
        </a>

        <button @click="toggleShowSubMenu('UserManagement')"
            class="flex items-center justify-between w-full px-3 py-2 rounded hover:bg-gray-100">
            <span class="flex items-center gap-2">
                <FontAwesomeIcon icon="users" />
                User Management
            </span>
            <FontAwesomeIcon :icon="isSubMenuVisible('UserManagement') ? 'chevron-up' : 'chevron-down'" />
        </button>

        <transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 max-h-0"
            enter-to-class="opacity-100 max-h-40" leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 max-h-40" leave-to-class="opacity-0 max-h-0">
            <div v-if="isSubMenuVisible('UserManagement') && canAccessUserManagementMenuComputed"
                class="ml-4 flex flex-col space-y-1 overflow-hidden">

                <a :href="route('back-office.users.index')"
                    class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
                    :class="isAnyCurrentPage(routeMap.UserManagement) ? 'bg-gray-200 font-medium' : ''">
                    <FontAwesomeIcon icon="user" />
                    Users
                </a>

            </div>
        </transition>

    </div>
</template>
