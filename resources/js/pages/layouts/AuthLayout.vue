<script setup>
import OffcanvasMenu from '@/components/common/layout/auth-layout/OffcanvasMenu.vue'
import Breadcrumbs from '@/components/common/layout/auth-layout/Breadcrumbs.vue'
import ToasterMessage from '@/components/common/layout/ToasterMessage.vue'


import { usePage, router as inertiaJsRoute } from '@inertiajs/vue3'
import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { ref, onMounted, onBeforeUnmount, computed, provide } from 'vue'

import {
    faUser,
    faXmark,
    faUserGear,
    faChartLine,
    faRightFromBracket,
    faSpinner
} from '@fortawesome/free-solid-svg-icons'

import { canAccessActivityLogMenu } from '@/composables/useAuthUserAccessPermissions'

library.add(
    faUser,
    faXmark,
    faUserGear,
    faChartLine,
    faRightFromBracket,
    faSpinner
)

const pageReady = ref(false)
const logoutProcessing = ref(false)
const logoutShowConfirmationModal = ref(false)


const showUserDropdown = ref(false)
const dropdownRef = ref(null)

const appName = import.meta.env.VITE_APP_NAME
const appLogo = import.meta.env.VITE_APP_LOGO

const page = usePage()

const authUser = computed(() => page.props.auth?.user ?? null)
const flashMessage = computed(() => page.props.flashMessage)

provide('pageReady', pageReady)
provide('authUser', authUser)

const canAccessActivityLogMenuComputed = computed(() => {
    return canAccessActivityLogMenu(authUser.value)
})

const handleLogout = () => {
    if (logoutProcessing.value) return

    logoutProcessing.value = true

    inertiaJsRoute.post(route('logout'), {}, {
        onFinish: () => {
            logoutProcessing.value = false
        }
    })
}


const toggleDropdown = (event) => {
    event.stopPropagation()
    showUserDropdown.value = !showUserDropdown.value
}

const closeUserDropdown = () => {
    showUserDropdown.value = false
}

const openLogoutModal = () => {
    logoutShowConfirmationModal.value = true
    closeUserDropdown()
}

const closeLogoutModal = () => {
    if (logoutProcessing.value) return
    logoutShowConfirmationModal.value = false
}

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        closeUserDropdown()
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)

    pageReady.value = true
})

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
    <div class="auth-layout flex flex-col min-h-screen">
        <header class="fixed top-0 left-0 w-full bg-white shadow-sm z-50">
            <div class="px-4 py-2 flex items-center justify-between">
                <a :href="route('home')" class="flex items-center gap-2">
                    <img :src="appLogo" :alt="appName" class="h-8" />
                    <span class="font-semibold">{{ appName }}</span>
                </a>

                <div class="flex items-center gap-3">
                    <OffcanvasMenu mode="trigger" :auth-user="authUser" />

                    <div ref="dropdownRef" class="relative">
                        <button type="button" @click="toggleDropdown" class="flex items-center gap-2"
                            aria-label="User menu">
                            <img :src="authUser?.profile_image?.media_url || '/uploads/icons/auth/user.png'"
                                class="w-8 h-8 rounded-full object-cover" :alt="authUser?.name || 'User'" />
                        </button>

                        <Transition enter-active-class="transition ease-out duration-150"
                            enter-from-class="opacity-0 scale-95 translate-y-1"
                            enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition ease-in duration-100"
                            leave-from-class="opacity-100 scale-100 translate-y-0"
                            leave-to-class="opacity-0 scale-95 translate-y-1">
                            <div v-if="showUserDropdown"
                                class="absolute right-0 mt-2 w-52 bg-white border border-gray-200 rounded-xl shadow-md z-50 origin-top-right overflow-hidden">
                                <div class="px-3 py-2 border-b border-gray-100">
                                    <div class="font-medium">
                                        {{ authUser?.name }}
                                    </div>

                                    <div class="text-sm text-gray-500">
                                        {{ authUser?.user_role?.name }}
                                    </div>
                                </div>

                                <a :href="route('auth-user.profile.index')" @click="closeUserDropdown"
                                    class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100">
                                    <FontAwesomeIcon icon="user" class="text-gray-500" />
                                    <span>Profile</span>
                                </a>

                                <a :href="route('auth-user.account.index')" @click="closeUserDropdown"
                                    class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100">
                                    <FontAwesomeIcon icon="user-gear" class="text-gray-500" />
                                    <span>Account</span>
                                </a>

                                <a v-if="canAccessActivityLogMenuComputed"
                                    :href="route('back-office.activity-logs.index')" @click="closeUserDropdown"
                                    class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100">
                                    <FontAwesomeIcon icon="chart-line" class="text-gray-500" />
                                    <span>Activity logs</span>
                                </a>

                                <button type="button" @click="openLogoutModal"
                                    class="flex items-center gap-2 w-full text-left px-3 py-2 text-red-500 hover:bg-gray-100">
                                    <FontAwesomeIcon icon="right-from-bracket" />
                                    <span>Logout</span>
                                </button>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 flex pt-16">
            <OffcanvasMenu mode="sidebar" :auth-user="authUser" />

            <div class="flex-1 p-4 min-w-0">
                <div v-if="!pageReady" class="fixed inset-0 bg-white/90 flex items-center justify-center z-50">
                    <FontAwesomeIcon icon="spinner" spin class="text-2xl text-blue-500" />
                </div>

                <Breadcrumbs />

                <div v-if="authUser && !authUser.email_verified_at"
                    class="mb-4 p-3 bg-yellow-100 border border-yellow-300 text-yellow-800 rounded">
                    Verify your email to continue.
                </div>

                <slot />
            </div>
        </main>

        <footer class="bg-white border-t border-gray-200 py-3 text-gray-500 text-sm">
            <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-2">
                <span class="text-center md:text-left w-full md:w-auto">
                    © {{ new Date().getFullYear() }} {{ appName }}
                </span>

                <span class="text-center md:text-right w-full md:w-auto">
                    Developed by
                    <a href="https://www.linkedin.com/in/sk-md-tahmid-farzan/" target="_blank" rel="noopener noreferrer"
                        class="text-blue-600 hover:underline font-medium">
                        Seikh Md Tahmid Farzan
                    </a>
                </span>
            </div>
        </footer>

        <ToasterMessage :flash-message="flashMessage" />

        <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition-opacity duration-150"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="authUser && logoutShowConfirmationModal"
                class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="closeLogoutModal">
                <Transition enter-active-class="transition transform duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition transform duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-2">
                    <div class="bg-white p-5 rounded-xl shadow-lg w-80">
                        <div class="flex items-center gap-2 mb-3 text-red-500">
                            <FontAwesomeIcon icon="right-from-bracket" />
                            <span class="font-semibold text-gray-800">
                                Logout Confirmation
                            </span>
                        </div>

                        <div class="mb-4 text-gray-600">
                            Are you sure you want to logout?
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="button" @click="closeLogoutModal" :disabled="logoutProcessing"
                                class="flex items-center gap-1 px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-70 disabled:cursor-not-allowed">
                                <FontAwesomeIcon icon="xmark" />
                                <span>Cancel</span>
                            </button>

                            <button type="button" @click="handleLogout" :disabled="logoutProcessing"
                                class="flex items-center gap-1 px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 disabled:opacity-70 disabled:cursor-not-allowed">
                                <FontAwesomeIcon v-if="!logoutProcessing" icon="right-from-bracket" />

                                <FontAwesomeIcon v-else icon="spinner" spin />

                                <span>Logout</span>
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </div>
</template>
