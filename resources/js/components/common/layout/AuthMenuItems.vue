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
    faPhotoFilm
} from "@fortawesome/free-solid-svg-icons"

library.add(
    faUser,
    faUsers,
    faChevronDown,
    faChevronUp,
    faRectangleList,
    faGauge,
    faPhotoFilm
)

import { canAccessUserManagementMenu } from '@/composables/useAuthUserAccessPermissions'

const { authUser } = defineProps({
    authUser: Object
})

const page = usePage()

const subMenus = ref({
    UserManagement: false,
    Reports: false,
})

const canAccessUserManagementMenuComputed = computed(() =>
    canAccessUserManagementMenu(authUser)
)

function toggleShowSubMenu(key) {
    subMenus.value[key] = !subMenus.value[key]
}

function isSubMenuVisible(key) {
    const routeMap = {
        UserManagement: '/back-office/users/*',
        Reports: '/back-office/reports/*',
    }

    const inRoute = isCurrentPage(routeMap[key] || '')
    return subMenus.value[key] || inRoute
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
</script>

<template>
    <div class="flex flex-col space-y-1 text-sm">

        <a :href="route('auth-user.dashboard.index')"
            class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
            :class="isCurrentPage('/auth-user/dashboard/*') ? 'bg-gray-200 font-medium' : ''">
            <FontAwesomeIcon icon="gauge" />
            Dashboard
        </a>

        <a :href="route('back-office.medias.index')"
            class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
            :class="isCurrentPage('/auth-user/medias/*') ? 'bg-gray-200 font-medium' : ''">
            <FontAwesomeIcon icon="photo-film" />
            Medias
        </a>

        <button v-if="!authUser?.is_member" @click="toggleShowSubMenu('UserManagement')"
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
                    :class="isCurrentPage('/back-office/users/*') ? 'bg-gray-200 font-medium' : ''">
                    <FontAwesomeIcon icon="user" />
                    Users
                </a>

            </div>
        </transition>

    </div>
</template>
