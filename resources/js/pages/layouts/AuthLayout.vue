<script setup>
import LayoutAuthMenuItems from '@/components/common/layout/AuthMenuItems.vue'

import { usePage, router as intertiaJsRoute } from "@inertiajs/vue3"
import { Toaster, toast } from "vue-sonner"
import { library } from "@fortawesome/fontawesome-svg-core"
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { ref, onMounted, onBeforeUnmount, computed, watch, provide } from "vue"

import {
    faBars,
    faUser,
    faXmark,
    faUserGear,
    faChartLine,
    faRightFromBracket,
    faSpinner
} from "@fortawesome/free-solid-svg-icons"

library.add(
    faBars,
    faUser,
    faXmark,
    faUserGear,
    faChartLine,
    faRightFromBracket,
    faSpinner
)

import { canAccessActivityLogMenu } from '@/composables/useAuthUserAccessPermissions'

const pageReady = ref(false)
const logoutProcessing = ref(false)
const logoutShowConfirmationModal = ref(false)
const breadcrumbItems = ref([])
const offcanvasSidebarShow = ref(false)
const offcanvasSidebarEnable = ref(window.innerWidth < 768)

const showUserDropdown = ref(false)
const dropdownRef = ref(null)

const appName = import.meta.env.VITE_APP_NAME
const appLogo = import.meta.env.VITE_APP_LOGO

const page = usePage()
const authUser = computed(() => page.props.auth?.user ?? null)
const flashMessage = computed(() => page.props.flashMessage)

provide("pageReady", pageReady)
provide('authUser', authUser)

const canAccessActivityLogMenuComputed = computed(() =>
    canAccessActivityLogMenu(authUser?.value)
)

function toggleOffcanvasSidebarShow() {
    offcanvasSidebarShow.value = !offcanvasSidebarShow.value
}

function handlePageResize() {
    offcanvasSidebarEnable.value = window.innerWidth < 768
}

function handleLogout() {
    if (logoutProcessing.value) return
    logoutProcessing.value = true
    intertiaJsRoute.post(route("logout"), {}, {
        onFinish: () => logoutProcessing.value = false
    })
}

function setBreadcrumb(e) {
    breadcrumbItems.value = e.detail
}

function toggleDropdown(e) {
    e.stopPropagation()
    showUserDropdown.value = !showUserDropdown.value
}

function handleClickOutside(e) {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        showUserDropdown.value = false
    }
}

watch(flashMessage, (newVal) => {
    if (newVal && newVal.message) {
        if (newVal.status === 'success') toast.success(newVal.message)
        else if (newVal.status === 'error') toast.error(newVal.message)
        else if (newVal.status === 'warning') toast.warning(newVal.message)
        else toast(newVal.message)

        page.props.flashMessage = null
    }
}, { immediate: true })

onMounted(() => {
    window.addEventListener("resize", handlePageResize)
    window.addEventListener("set-breadcrumb", setBreadcrumb)
    document.addEventListener("click", handleClickOutside)
    pageReady.value = true
})

onBeforeUnmount(() => {
    window.removeEventListener("resize", handlePageResize)
    window.removeEventListener("set-breadcrumb", setBreadcrumb)
    document.removeEventListener("click", handleClickOutside)
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

                    <button @click="toggleOffcanvasSidebarShow"
                        class="md:hidden border border-gray-200 px-2 py-1 rounded" v-if="!offcanvasSidebarShow">
                        <FontAwesomeIcon icon="bars" />
                    </button>

                    <div class="relative" ref="dropdownRef">

                        <button @click="toggleDropdown" class="flex items-center gap-2">
                            <img :src="authUser?.profile_image?.media_url || '/uploads/icons/auth/user.png'"
                                class="w-8 h-8 rounded-full" />
                        </button>

                        <Transition enter-active-class="transition ease-out duration-150"
                            enter-from-class="opacity-0 scale-95 translate-y-1"
                            enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition ease-in duration-100"
                            leave-from-class="opacity-100 scale-100 translate-y-0"
                            leave-to-class="opacity-0 scale-95 translate-y-1">
                            <div v-if="showUserDropdown"
                                class="absolute right-0 mt-2 w-52 bg-white border border-gray-200 rounded-xl shadow-md z-50 origin-top-right">

                                <div class="px-3 py-2 border-b border-gray-100">
                                    <div class="font-medium">{{ authUser?.name }}</div>
                                    <div class="text-sm text-gray-500">{{ authUser?.user_role?.name }}</div>
                                </div>

                                <a :href="route('auth-user.profile.index')"
                                    class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100">
                                    <FontAwesomeIcon icon="user" class="text-gray-500" />
                                    Profile
                                </a>

                                <a :href="route('auth-user.account.index')"
                                    class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100">
                                    <FontAwesomeIcon icon="user-gear" class="text-gray-500" />
                                    Account
                                </a>

                                <a v-if="canAccessActivityLogMenuComputed"
                                    :href="route('back-office.activity-logs.index')"
                                    class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100">
                                    <FontAwesomeIcon icon="chart-line" class="text-gray-500" />
                                    Activity logs
                                </a>

                                <button @click="logoutShowConfirmationModal = true"
                                    class="flex items-center gap-2 w-full text-left px-3 py-2 text-red-500 hover:bg-gray-100">
                                    <FontAwesomeIcon icon="right-from-bracket" />
                                    Logout
                                </button>

                            </div>
                        </Transition>

                    </div>

                </div>
            </div>
        </header>

        <main class="flex-1 flex pt-16">

            <aside v-if="!offcanvasSidebarEnable" class="w-64 border-r border-gray-200 bg-white hidden md:block">
                <div class="p-3">
                    <LayoutAuthMenuItems :authUser="authUser" />
                </div>
            </aside>

            <div class="flex-1 p-4 min-w-0">

                <div v-if="!pageReady" class="fixed inset-0 bg-white/90 flex items-center justify-center z-50">
                    <FontAwesomeIcon icon="spinner" spin class="text-2xl text-blue-500" />
                </div>

                <div v-if="breadcrumbItems.length"
                    class="mb-4 border border-gray-200 rounded-lg p-3 bg-white shadow-sm">
                    <div class="text-sm text-gray-600 flex flex-wrap gap-2">
                        <template v-for="(item, i) in breadcrumbItems" :key="i">
                            <a v-if="!item.active" :href="item.href" class="hover:underline">
                                {{ item.text }}
                            </a>
                            <span v-else class="font-medium">{{ item.text }}</span>
                            <span v-if="i < breadcrumbItems.length - 1">/</span>
                        </template>
                    </div>
                </div>

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
                    <a href="https://www.linkedin.com/in/sk-md-tahmid-farzan/" target="_blank"
                        class="text-blue-600 hover:underline font-medium">
                        Seikh Md Tahmid Farzan
                    </a>
                </span>

            </div>
        </footer>

        <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition-opacity duration-150"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="offcanvasSidebarShow" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 md:hidden"
                @click="offcanvasSidebarShow = false"></div>
        </Transition>

        <Transition enter-active-class="transition transform duration-300 ease-out" enter-from-class="-translate-x-full"
            enter-to-class="translate-x-0" leave-active-class="transition transform duration-200 ease-in"
            leave-from-class="translate-x-0" leave-to-class="-translate-x-full">
            <div v-if="offcanvasSidebarShow"
                class="fixed top-0 left-0 h-full w-64 bg-white z-50 p-3 md:hidden shadow-lg">
                <LayoutAuthMenuItems :authUser="authUser" />
            </div>
        </Transition>

        <Toaster richColors position="top-right" />

        <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition-opacity duration-150"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="authUser && logoutShowConfirmationModal"
                class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">

                <Transition enter-active-class="transition transform duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition transform duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-2">
                    <div class="bg-white p-5 rounded-xl shadow-lg w-80">

                        <div class="flex items-center gap-2 mb-3 text-red-500">
                            <FontAwesomeIcon icon="right-from-bracket" />
                            <span class="font-semibold text-gray-800">Logout Confirmation</span>
                        </div>

                        <div class="mb-4 text-gray-600">
                            Are you sure you want to logout?
                        </div>

                        <div class="flex justify-end gap-2">
                            <button @click="logoutShowConfirmationModal = false"
                                class="flex items-center gap-1 px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                                <FontAwesomeIcon icon="xmark" />
                                Cancel
                            </button>

                            <button @click="handleLogout"
                                class="flex items-center gap-1 px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600">
                                <FontAwesomeIcon icon="right-from-bracket" />
                                Logout
                            </button>
                        </div>

                    </div>
                </Transition>

            </div>
        </Transition>

    </div>
</template>
