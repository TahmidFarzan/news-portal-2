<script setup>
import HeaderMenuItem from '@/components/common/layout/HeaderMenuItem.vue'
import HorizontalScroller from '@/components/common/layout/HorizontalScroller.vue'
import VerticalScroller from '@/components/common/layout/VerticalScroller.vue'
import OffCanvasMenuItem from '@/components/common/layout/OffCanvasMenuItem.vue'

import { ref, reactive, computed, watch, provide, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { usePage, router as inertia } from '@inertiajs/vue3'
import { Toaster, toast } from 'vue-sonner'
import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faUser,
    faRightFromBracket,
    faArrowRightToBracket,
    faSpinner,
    faGauge,
    faUserGear,
    faXmark,
    faMagnifyingGlass,
    faBars,
    faChevronDown
} from '@fortawesome/free-solid-svg-icons'
import { faFacebook, faLinkedin, faGoogle } from '@fortawesome/free-brands-svg-icons'

import { fetchFromApi } from '@/composables/useSystemApi'

library.add(
    faUser,
    faRightFromBracket,
    faArrowRightToBracket,
    faSpinner,
    faGauge,
    faUserGear,
    faXmark,
    faMagnifyingGlass,
    faBars,
    faChevronDown,
    faFacebook,
    faLinkedin,
    faGoogle
)

const pageReady = ref(false)
const headerNavbar = ref(null)
const dropdownRef = ref(null)

const showDropdown = ref(false)
const showLogoutModal = ref(false)
const loggingOut = ref(false)
const showOffCanvas = ref(false)
const isHeaderSticky = ref(false)
const lastFlashKey = ref(null)

const headerMenu = reactive({
    items: [],
    loading: false,
    page: 1,
    lastPage: 1
})

const offCanvasMenu = reactive({
    items: [],
    loading: false,
    page: 1,
    lastPage: 1
})

provide('pageReady', pageReady)

const page = usePage()
const year = new Date().getFullYear()
const appName = import.meta.env.VITE_APP_NAME
const appLogo = import.meta.env.VITE_APP_LOGO

const authUser = computed(() => page.props.auth?.user ?? null)
const flashMessage = computed(() => page.props.flashMessage)

let removeInertiaStartListener = null
let removeInertiaFinishListener = null

const normalizeMenuItems = (items = []) => {
    return items.map((item) => ({
        ...item,
        children: item.children ?? []
    }))
}

const loadMenuItems = async (state, routeName, pageNumber = 1) => {
    if (state.loading || pageNumber > state.lastPage) return

    try {
        state.loading = true

        const response = await fetchFromApi(route(routeName, { page: pageNumber }))
        const items = normalizeMenuItems(response?.items ?? [])

        state.items = pageNumber === 1 ? items : [...state.items, ...items]
        state.page = Number(response?.current_page ?? pageNumber)
        state.lastPage = Number(response?.last_page ?? pageNumber)
    } catch (error) {
        console.error(`Failed to fetch ${routeName}:`, error)
    } finally {
        state.loading = false
    }
}

const getHeaderMenuItems = (pageNumber = 1) => {
    return loadMenuItems(headerMenu, 'site.theme.header.menu.menu-items', pageNumber)
}

const getOffCanvasMenuItems = (pageNumber = 1) => {
    return loadMenuItems(offCanvasMenu, 'site.theme.off-canvas.menu-items', pageNumber)
}

const handleHeaderMenuReachEnd = async () => {
    const nextPage = headerMenu.page + 1

    if (!headerMenu.loading && nextPage <= headerMenu.lastPage) {
        await getHeaderMenuItems(nextPage)
    }
}

const handleOffCanvasMenuReachEnd = async () => {
    const nextPage = offCanvasMenu.page + 1

    if (!offCanvasMenu.loading && nextPage <= offCanvasMenu.lastPage) {
        await getOffCanvasMenuItems(nextPage)
    }
}

const logoutHandler = () => {
    if (loggingOut.value) return

    loggingOut.value = true

    inertia.post(route('logout'), {}, {
        onFinish: () => {
            loggingOut.value = false
        }
    })
}

const handlePageScroll = () => {
    isHeaderSticky.value = window.scrollY > 0
}

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        showDropdown.value = false
    }
}

watch(flashMessage, (value) => {
    if (!value?.message) return

    const flashKey = `${value.status ?? 'default'}-${value.message}`

    if (flashKey === lastFlashKey.value) return

    lastFlashKey.value = flashKey

    switch (value.status) {
        case 'success':
            toast.success(value.message)
            break
        case 'error':
            toast.error(value.message)
            break
        case 'warning':
            toast.warning(value.message)
            break
        case 'info':
            toast.info(value.message)
            break
        default:
            toast.info(value.message)
    }
}, { immediate: true })

watch(pageReady, (ready) => {
    document.body.style.overflow = ready ? '' : 'hidden'
}, { immediate: true })

watch(showOffCanvas, async (open) => {
    if (open && !offCanvasMenu.items.length) {
        await getOffCanvasMenuItems()
    }
})

onMounted(async () => {
    await nextTick()
    await getHeaderMenuItems()

    pageReady.value = true

    removeInertiaStartListener = inertia.on('start', () => {
        pageReady.value = false
    })

    removeInertiaFinishListener = inertia.on('finish', () => {
        pageReady.value = true
    })

    handlePageScroll()

    document.addEventListener('click', handleClickOutside)
    window.addEventListener('scroll', handlePageScroll, { passive: true })
})

onBeforeUnmount(() => {
    removeInertiaStartListener?.()
    removeInertiaFinishListener?.()

    document.removeEventListener('click', handleClickOutside)
    window.removeEventListener('scroll', handlePageScroll)

    document.body.style.overflow = ''
})
</script>

<template>
    <div class="guest-layout flex flex-col min-h-screen">
        <div class="bg-gray-900 text-white">
            <div class="max-w-7xl mx-auto px-4 py-2 flex justify-between items-center">
                <div class="flex space-x-3">
                    <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                        <FontAwesomeIcon :icon="['fab', 'facebook']" />
                    </a>

                    <a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">
                        <FontAwesomeIcon :icon="['fab', 'linkedin']" />
                    </a>

                    <a href="https://news.google.com" target="_blank" rel="noopener noreferrer"
                        aria-label="Google News">
                        <FontAwesomeIcon :icon="['fab', 'google']" />
                    </a>
                </div>

                <div class="flex items-center space-x-3 relative">
                    <a v-if="!authUser" :href="route('login')"
                        class="flex items-center gap-1 text-gray-300 hover:text-white">
                        <FontAwesomeIcon icon="arrow-right-to-bracket" />
                        <span>Login</span>
                    </a>

                    <div v-else ref="dropdownRef" class="relative">
                        <button type="button" @click.stop="showDropdown = !showDropdown"
                            class="flex items-center gap-1 hover:text-gray-300" aria-label="User menu">
                            <FontAwesomeIcon icon="user" />
                        </button>

                        <Transition enter-active-class="transition ease-out duration-150"
                            enter-from-class="opacity-0 scale-95 translate-y-1"
                            enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition ease-in duration-100"
                            leave-from-class="opacity-100 scale-100 translate-y-0"
                            leave-to-class="opacity-0 scale-95 translate-y-1">
                            <div v-if="showDropdown"
                                class="absolute right-0 mt-2 bg-white text-black shadow-md border border-gray-200 rounded-xl w-44 z-[999] origin-top-right overflow-hidden">
                                <a @click="showDropdown = false" :href="route('auth-user.dashboard.index')"
                                    class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100">
                                    <FontAwesomeIcon icon="gauge" class="text-gray-500" />
                                    <span>Dashboard</span>
                                </a>

                                <a @click="showDropdown = false" :href="route('auth-user.profile.index')"
                                    class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100">
                                    <FontAwesomeIcon icon="user" class="text-gray-500" />
                                    <span>Profile</span>
                                </a>

                                <a @click="showDropdown = false" :href="route('auth-user.account.index')"
                                    class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100">
                                    <FontAwesomeIcon icon="user-gear" class="text-gray-500" />
                                    <span>Account</span>
                                </a>

                                <button type="button" @click="showLogoutModal = true; showDropdown = false"
                                    class="flex items-center gap-2 w-full text-left px-3 py-2 text-red-500 hover:bg-gray-100">
                                    <FontAwesomeIcon icon="right-from-bracket" />
                                    <span>Logout</span>
                                </button>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>
        </div>

        <div ref="headerNavbar" class="bg-gray-900 text-white transition-shadow"
            :class="{ 'shadow-md sticky top-0 z-50': isHeaderSticky }">
            <div class="max-w-7xl mx-auto px-4 py-2 flex items-center gap-3">
                <a :href="route('home')" class="text-white font-semibold flex-shrink-0">
                    {{ appName }}
                </a>

                <HorizontalScroller v-if="headerMenu.items.length" class="flex-1 min-w-0" :loading="headerMenu.loading"
                    :watch-key="`${headerMenu.items.length}-${headerMenu.loading}`"
                    @reach-end="handleHeaderMenuReachEnd">
                    <ul class="flex items-center gap-1 whitespace-nowrap py-1">
                        <HeaderMenuItem v-for="item in headerMenu.items" :key="item.id" :item="item" />

                        <li v-if="headerMenu.loading" class="px-3 py-2 text-sm text-gray-300 flex-shrink-0">
                            Loading...
                        </li>
                    </ul>
                </HorizontalScroller>

                <div v-else class="flex-1"></div>

                <button type="button"
                    class="w-9 h-9 flex items-center justify-center rounded-lg hover:bg-white/10 flex-shrink-0"
                    aria-label="Search">
                    <FontAwesomeIcon icon="magnifying-glass" />
                </button>

                <button type="button" @click="showOffCanvas = true"
                    class="w-9 h-9 flex items-center justify-center rounded-lg hover:bg-white/10 flex-shrink-0"
                    aria-label="Open menu">
                    <FontAwesomeIcon icon="bars" />
                </button>
            </div>
        </div>

        <main class="flex-1 max-w-7xl mx-auto px-4 py-4 relative">
            <div v-if="!pageReady" class="fixed inset-0 bg-white/90 flex items-center justify-center z-50">
                <FontAwesomeIcon icon="spinner" spin class="text-2xl text-blue-500" />
            </div>

            <slot />
        </main>

        <footer class="bg-gray-100 py-3 mt-2 text-gray-600 text-sm">
            <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-2">
                <span class="text-center md:text-left w-full md:w-auto">
                    © {{ year }} {{ appName }}
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

        <Toaster richColors position="top-right" />

        <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition-opacity duration-150"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showOffCanvas" class="fixed inset-0 bg-black/50 z-[998]" @click="showOffCanvas = false"></div>
        </Transition>

        <Transition enter-active-class="transition transform duration-200 ease-out" enter-from-class="translate-x-full"
            enter-to-class="translate-x-0" leave-active-class="transition transform duration-150 ease-in"
            leave-from-class="translate-x-0" leave-to-class="translate-x-full">
            <aside v-if="showOffCanvas"
                class="fixed right-0 top-0 h-full w-80 max-w-[90vw] bg-white shadow-xl z-[999] flex flex-col">
                <div class="flex items-center justify-between px-4 py-3 border-b">
                    <a :href="route('home')" class="inline-flex items-center">
                        <img v-if="appLogo" :src="appLogo" :alt="appName" class="h-10 max-w-40 object-contain">

                        <span v-else class="text-lg font-semibold text-gray-800">
                            {{ appName }}
                        </span>
                    </a>

                    <button type="button" @click="showOffCanvas = false"
                        class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100"
                        aria-label="Close menu">
                        <FontAwesomeIcon icon="xmark" />
                    </button>
                </div>

                <div class="flex-1 min-h-0 p-4">
                    <VerticalScroller max-height-class="max-h-[calc(100vh-140px)]" :loading="offCanvasMenu.loading"
                        :watch-key="`${offCanvasMenu.items.length}-${offCanvasMenu.loading}-${showOffCanvas}`"
                        @reach-end="handleOffCanvasMenuReachEnd">
                        <ul class="space-y-1 pr-1">
                            <OffCanvasMenuItem v-for="item in offCanvasMenu.items" :key="item.id" :item="item" />

                            <li v-if="offCanvasMenu.loading" class="px-3 py-2 text-sm text-gray-400">
                                Loading...
                            </li>

                            <li v-if="!offCanvasMenu.loading && !offCanvasMenu.items.length"
                                class="px-3 py-2 text-sm text-gray-400">
                                No menu items
                            </li>
                        </ul>
                    </VerticalScroller>
                </div>
            </aside>
        </Transition>

        <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition-opacity duration-150"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="authUser && showLogoutModal"
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
                            <button type="button" @click="showLogoutModal = false"
                                class="flex items-center gap-1 px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                                <FontAwesomeIcon icon="xmark" />
                                <span>Cancel</span>
                            </button>

                            <button type="button" @click="logoutHandler" :disabled="loggingOut"
                                class="flex items-center gap-1 px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 disabled:opacity-70 disabled:cursor-not-allowed">
                                <FontAwesomeIcon v-if="!loggingOut" icon="right-from-bracket" />
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
