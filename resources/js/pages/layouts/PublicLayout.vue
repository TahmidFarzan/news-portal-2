<script setup>
import HeaderMenuItem from '@/components/common/layout/HeaderMenuItem.vue'


import { ref, onMounted, onBeforeUnmount, computed, nextTick, watch, provide } from "vue"
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
const showDropdown = ref(false)
const showLogoutModal = ref(false)
const loggingOut = ref(false)
const dropdownRef = ref(null)

const headerMenuItems = ref([])
const headerMenuLoading = ref(false)
const headerMenuPage = ref(1)
const headerMenuLastPage = ref(1)
const headerMenuScrollRef = ref(null)
const showOffCanvas = ref(false)
const headerMenuTrackRef = ref(null)
const headerMenuThumbWidth = ref(0)
const headerMenuThumbLeft = ref(0)
const headerMenuDragging = ref(false)
const headerMenuDragStartX = ref(0)
const headerMenuDragStartLeft = ref(0)

provide("pageReady", pageReady)

const page = usePage()
const year = new Date().getFullYear()
const appName = import.meta.env.VITE_APP_NAME

const authUser = computed(() => page.props.auth?.user ?? null)
const flashMessage = computed(() => page.props.flashMessage)

const logoutHandler = async () => {
    loggingOut.value = true
    await inertia.post(route('logout'))
}

const handlePageScroll = () => {
    if (!headerNavbar.value) return
    if (window.scrollY > 0) headerNavbar.value.classList.add('shadow-md', 'sticky', 'top-0', 'z-50')
    else headerNavbar.value.classList.remove('shadow-md', 'sticky', 'top-0', 'z-50')
}

function handleClickOutside(e) {
    if (!dropdownRef.value) return
    if (!dropdownRef.value.contains(e.target)) {
        showDropdown.value = false
    }
}

const normalizeMenuItems = (items = []) => {
    return items.map((item) => ({
        ...item,
        children: item.children ?? [],
    }))
}

const getHeaderMenuItems = async (page = 1) => {
    if (headerMenuLoading.value) return
    if (page > headerMenuLastPage.value) return

    try {
        headerMenuLoading.value = true

        const response = await fetchFromApi(
            route('site.theme.header.menu.menu-items', { page })
        )

        const items = normalizeMenuItems(response?.items ?? [])

        headerMenuItems.value = page === 1
            ? items
            : [...headerMenuItems.value, ...items]

        headerMenuPage.value = Number(response?.current_page ?? page)
        headerMenuLastPage.value = Number(response?.last_page ?? page)

        console.log('Header Menus:', headerMenuItems.value)
    } catch (error) {
        console.error('Failed to fetch header menus:', error)
    } finally {
        headerMenuLoading.value = false
    }
}

const updateHeaderMenuScrollbar = () => {
    const el = headerMenuScrollRef.value

    if (!el) return

    const clientWidth = el.clientWidth
    const scrollWidth = el.scrollWidth
    const scrollLeft = el.scrollLeft

    if (scrollWidth <= clientWidth) {
        headerMenuThumbWidth.value = 0
        headerMenuThumbLeft.value = 0
        return
    }

    const thumbWidth = Math.max((clientWidth / scrollWidth) * clientWidth, 32)
    const maxThumbLeft = clientWidth - thumbWidth
    const maxScrollLeft = scrollWidth - clientWidth

    headerMenuThumbWidth.value = thumbWidth
    headerMenuThumbLeft.value = (scrollLeft / maxScrollLeft) * maxThumbLeft
}

const handleHeaderMenuScroll = async () => {
    const el = headerMenuScrollRef.value

    if (!el || headerMenuLoading.value) return

    updateHeaderMenuScrollbar()

    const almostEnd = el.scrollLeft + el.clientWidth >= el.scrollWidth - 80

    if (!almostEnd) return

    const nextPage = headerMenuPage.value + 1

    if (nextPage <= headerMenuLastPage.value) {
        await getHeaderMenuItems(nextPage)
        await nextTick()
        updateHeaderMenuScrollbar()
    }
}

const handleHeaderMenuWheel = (event) => {
    const el = headerMenuScrollRef.value

    if (!el) return

    const rawDelta = Math.abs(event.deltaX) > Math.abs(event.deltaY)
        ? event.deltaX
        : event.deltaY

    if (!rawDelta) return

    const delta = event.deltaMode === 1
        ? rawDelta * 16
        : rawDelta

    event.preventDefault()
    event.stopPropagation()

    el.scrollLeft += delta

    updateHeaderMenuScrollbar()
    handleHeaderMenuScroll()
}

const scrollHeaderMenuByThumbPosition = (thumbLeft) => {
    const el = headerMenuScrollRef.value

    if (!el || !headerMenuThumbWidth.value) return

    const maxThumbLeft = el.clientWidth - headerMenuThumbWidth.value
    const maxScrollLeft = el.scrollWidth - el.clientWidth

    if (maxThumbLeft <= 0 || maxScrollLeft <= 0) return

    const safeLeft = Math.max(0, Math.min(thumbLeft, maxThumbLeft))

    el.scrollLeft = (safeLeft / maxThumbLeft) * maxScrollLeft

    updateHeaderMenuScrollbar()
    handleHeaderMenuScroll()
}

const handleHeaderMenuTrackPointerDown = (event) => {
    const track = headerMenuTrackRef.value

    if (!track || !headerMenuThumbWidth.value) return

    event.preventDefault()

    const rect = track.getBoundingClientRect()
    const clickX = event.clientX - rect.left
    const targetLeft = clickX - headerMenuThumbWidth.value / 2

    scrollHeaderMenuByThumbPosition(targetLeft)
}

const handleHeaderMenuThumbPointerDown = (event) => {
    event.preventDefault()
    event.stopPropagation()

    headerMenuDragging.value = true
    headerMenuDragStartX.value = event.clientX
    headerMenuDragStartLeft.value = headerMenuThumbLeft.value

    event.currentTarget.setPointerCapture(event.pointerId)
}

const handleHeaderMenuThumbPointerMove = (event) => {
    if (!headerMenuDragging.value) return

    event.preventDefault()

    const diff = event.clientX - headerMenuDragStartX.value

    scrollHeaderMenuByThumbPosition(headerMenuDragStartLeft.value + diff)
}

const handleHeaderMenuThumbPointerUp = (event) => {
    headerMenuDragging.value = false

    if (event.currentTarget.hasPointerCapture(event.pointerId)) {
        event.currentTarget.releasePointerCapture(event.pointerId)
    }
}

watch(flashMessage, (newVal) => {
    if (newVal && newVal.message) {
        switch (newVal.status) {
            case 'success': toast.success(newVal.message); break
            case 'error': toast.error(newVal.message); break
            case 'warning': toast.warning(newVal.message); break
            case 'info': toast(newVal.message); break
            default: toast(newVal.message)
        }
        page.props.flashMessage = null
    }
}, { immediate: true })

watch(pageReady, (ready) => {
    document.body.style.overflow = ready ? '' : 'hidden'
})

onMounted(async () => {
    await nextTick()
    await getHeaderMenuItems()
    updateHeaderMenuScrollbar()
    pageReady.value = true

    inertia.on('start', () => pageReady.value = false)
    inertia.on('finish', () => pageReady.value = true)

    document.addEventListener('click', handleClickOutside)
    window.addEventListener('scroll', handlePageScroll)
    window.addEventListener('resize', updateHeaderMenuScrollbar)
})

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside)
    window.removeEventListener('scroll', handlePageScroll)
    window.removeEventListener('resize', updateHeaderMenuScrollbar)
})
</script>

<template>
    <div class="guest-layout flex flex-col min-h-screen">

        <div class="bg-gray-900 text-white">
            <div class="max-w-7xl mx-auto px-4 py-2 flex justify-between items-center">

                <div class="flex space-x-3">
                    <a href="https://facebook.com" target="_blank">
                        <FontAwesomeIcon :icon="['fab', 'facebook']" />
                    </a>
                    <a href="https://linkedin.com" target="_blank">
                        <FontAwesomeIcon :icon="['fab', 'linkedin']" />
                    </a>
                    <a href="https://news.google.com" target="_blank">
                        <FontAwesomeIcon :icon="['fab', 'google']" />
                    </a>
                </div>

                <div class="flex items-center space-x-3 relative">

                    <a v-if="!authUser" :href="route('login')" class="flex items-center gap-1 text-gray-300">
                        <FontAwesomeIcon icon="arrow-right-to-bracket" />
                        <span>Login</span>
                    </a>

                    <div v-if="authUser" class="relative" ref="dropdownRef">

                        <button @click.stop="showDropdown = !showDropdown" class="flex items-center gap-1">
                            <FontAwesomeIcon icon="user" />
                        </button>

                        <Transition enter-active-class="transition ease-out duration-150"
                            enter-from-class="opacity-0 scale-95 translate-y-1"
                            enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition ease-in duration-100"
                            leave-from-class="opacity-100 scale-100 translate-y-0"
                            leave-to-class="opacity-0 scale-95 translate-y-1">
                            <div v-if="showDropdown"
                                class="absolute right-0 mt-2 bg-white text-black shadow-md border border-gray-200 rounded-xl w-44 z-[999] origin-top-right">

                                <a @click="showDropdown = false" :href="route('auth-user.dashboard.index')"
                                    class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100">
                                    <FontAwesomeIcon icon="gauge" class="text-gray-500" />
                                    Dashboard
                                </a>

                                <a @click="showDropdown = false" :href="route('auth-user.profile.index')"
                                    class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100">
                                    <FontAwesomeIcon icon="user" class="text-gray-500" />
                                    Profile
                                </a>

                                <a @click="showDropdown = false" :href="route('auth-user.account.index')"
                                    class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100">
                                    <FontAwesomeIcon icon="user-gear" class="text-gray-500" />
                                    Account
                                </a>

                                <button @click="showLogoutModal = true; showDropdown = false"
                                    class="flex items-center gap-2 w-full text-left px-3 py-2 text-red-500 hover:bg-gray-100">
                                    <FontAwesomeIcon icon="right-from-bracket" />
                                    Logout
                                </button>

                            </div>
                        </Transition>

                    </div>

                </div>
            </div>
        </div>

        <div ref="headerNavbar" class="bg-gray-900 text-white">
            <div class="max-w-7xl mx-auto px-4 py-2 flex items-center gap-3">
                <a :href="route('home')" class="text-white font-semibold flex-shrink-0">
                    {{ appName }}
                </a>

                <div v-if="headerMenuItems.length" class="relative flex-1 min-w-0"
                    @wheel.prevent.stop="handleHeaderMenuWheel">
                    <nav ref="headerMenuScrollRef" @scroll.passive="handleHeaderMenuScroll"
                        @wheel.prevent.stop="handleHeaderMenuWheel"
                        class="overflow-x-auto overflow-y-visible pb-3 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                        <ul class="flex items-center gap-1 whitespace-nowrap py-1">
                            <HeaderMenuItem v-for="item in headerMenuItems" :key="item.id" :item="item" />

                            <li v-if="headerMenuLoading" class="px-3 py-2 text-sm text-gray-300 flex-shrink-0">
                                Loading...
                            </li>
                        </ul>
                    </nav>

                    <div v-if="headerMenuThumbWidth" ref="headerMenuTrackRef"
                        @pointerdown="handleHeaderMenuTrackPointerDown" @wheel.prevent.stop="handleHeaderMenuWheel"
                        class="absolute left-0 right-0 bottom-0 h-3 cursor-pointer flex items-center">
                        <div class="relative h-[2px] w-full rounded-full bg-white/10">
                            <div @pointerdown="handleHeaderMenuThumbPointerDown"
                                @pointermove="handleHeaderMenuThumbPointerMove"
                                @pointerup="handleHeaderMenuThumbPointerUp"
                                @pointercancel="handleHeaderMenuThumbPointerUp"
                                @wheel.prevent.stop="handleHeaderMenuWheel"
                                class="absolute top-1/2 -translate-y-1/2 h-[2px] cursor-grab rounded-full bg-white/40 transition-colors hover:bg-white/70 active:cursor-grabbing"
                                :style="{
                                    width: `${headerMenuThumbWidth}px`,
                                    transform: `translateX(${headerMenuThumbLeft}px) translateY(-50%)`
                                }"></div>
                        </div>
                    </div>
                </div>

                <div v-else class="flex-1"></div>

                <button type="button"
                    class="w-9 h-9 flex items-center justify-center rounded-lg hover:bg-white/10 flex-shrink-0">
                    <FontAwesomeIcon icon="magnifying-glass" />
                </button>

                <button type="button" @click="showOffCanvas = true"
                    class="w-9 h-9 flex items-center justify-center rounded-lg hover:bg-white/10 flex-shrink-0">
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
                    <a href="https://www.linkedin.com/in/sk-md-tahmid-farzan/" target="_blank"
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
                    <span class="font-semibold text-gray-800">{{ appName }}</span>

                    <button type="button" @click="showOffCanvas = false"
                        class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                        <FontAwesomeIcon icon="xmark" />
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-4 text-gray-500">
                    Off canvas menu will be added here
                </div>
            </aside>
        </Transition>

        <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition-opacity duration-150"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="authUser && showLogoutModal"
                class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">

                <transition enter-active-class="transition transform duration-200 ease-out"
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
                            <button @click="showLogoutModal = false"
                                class="flex items-center gap-1 px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                                <FontAwesomeIcon icon="xmark" />
                                Cancel
                            </button>

                            <button @click="logoutHandler"
                                class="flex items-center gap-1 px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600">
                                <FontAwesomeIcon v-if="!loggingOut" icon="right-from-bracket" />
                                <FontAwesomeIcon v-else icon="spinner" spin />
                                Logout
                            </button>
                        </div>

                    </div>
                </transition>

            </div>
        </Transition>

    </div>
</template>
