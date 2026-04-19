<script setup>
import { ref, onMounted, onBeforeUnmount, computed, nextTick, watch, provide } from "vue"
import { usePage, router as inertia } from '@inertiajs/vue3'
import { Toaster, toast } from 'vue-sonner'
import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faUser, faRightFromBracket, faArrowRightToBracket, faSpinner } from '@fortawesome/free-solid-svg-icons'
import { faFacebook, faLinkedin, faGoogle } from '@fortawesome/free-brands-svg-icons'

library.add(faUser, faRightFromBracket, faArrowRightToBracket, faSpinner, faFacebook, faLinkedin, faGoogle)

const pageReady = ref(false)
const headerNavbar = ref(null)
const showDropdown = ref(false)
const showLogoutModal = ref(false)
const loggingOut = ref(false)

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
    pageReady.value = true

    inertia.on('start', () => pageReady.value = false)
    inertia.on('finish', () => pageReady.value = true)

    const enableGrabScroll = (selector) => {
        const el = document.querySelector(selector)
        if (!el) return

        let isDown = false
        let startX, scrollLeft
        let touchStartX = 0, touchScrollLeft = 0

        el.addEventListener('mousedown', (e) => {
            isDown = true
            startX = e.pageX - el.offsetLeft
            scrollLeft = el.scrollLeft
        })

        el.addEventListener('mouseleave', () => isDown = false)
        el.addEventListener('mouseup', () => isDown = false)

        el.addEventListener('mousemove', (e) => {
            if (!isDown) return
            e.preventDefault()
            const x = e.pageX - el.offsetLeft
            el.scrollLeft = scrollLeft - (x - startX) * 1.5
        })

        el.addEventListener('wheel', (e) => {
            if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
                e.preventDefault()
                el.scrollLeft += e.deltaY
            }
        }, { passive: false })

        el.addEventListener('touchstart', (e) => {
            touchStartX = e.touches[0].pageX
            touchScrollLeft = el.scrollLeft
        })

        el.addEventListener('touchmove', (e) => {
            const diff = (e.touches[0].pageX - touchStartX) * 1.3
            el.scrollLeft = touchScrollLeft - diff
        })
    }

    enableGrabScroll('.overflow-x-auto')

    window.addEventListener('scroll', handlePageScroll)
})

onBeforeUnmount(() => {
    window.removeEventListener('scroll', handlePageScroll)
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

                <div class="flex items-center space-x-3 overflow-x-auto">

                    <a v-if="!authUser" :href="route('login')" class="flex items-center gap-1 text-gray-300">
                        <FontAwesomeIcon icon="arrow-right-to-bracket" />
                        <span>Login</span>
                    </a>

                    <div v-if="authUser" class="relative">
                        <button @click="showDropdown = !showDropdown" class="flex items-center gap-1">
                            <FontAwesomeIcon icon="user" />
                        </button>

                        <div v-if="showDropdown"
                            class="absolute right-0 mt-2 bg-white text-black shadow-lg rounded w-44">
                            <a :href="route('auth-user.dashboard.index')" class="block px-3 py-2">Dashboard</a>
                            <a :href="route('auth-user.profile.index')" class="block px-3 py-2">Profile</a>
                            <a :href="route('auth-user.account.index')" class="block px-3 py-2">Account</a>

                            <button @click="showLogoutModal = true; showDropdown = false"
                                class="flex items-center gap-2 w-full text-left px-3 py-2 text-red-500">
                                <FontAwesomeIcon icon="right-from-bracket" />
                                <span>Logout</span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div ref="headerNavbar" class="bg-gray-900 text-white">
            <div class="max-w-7xl mx-auto px-4 py-2 flex justify-between items-center">
                <a :href="route('home')" class="text-white">{{ appName }}</a>
            </div>
        </div>

        <div class="flex-1 max-w-7xl mx-auto px-4 py-4 relative">
            <div v-if="!pageReady" class="fixed inset-0 bg-white/90 flex items-center justify-center z-50">
                <FontAwesomeIcon icon="spinner" spin class="text-2xl text-blue-500" />
            </div>

            <slot />
        </div>

        <footer class="bg-gray-100 py-3 mt-2">
            <div
                class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center gap-2 text-sm text-gray-600">

                <div>
                    © {{ year }} {{ appName }}
                </div>

                <div>
                    Developed by
                    <a href="https://www.linkedin.com/in/sk-md-tahmid-farzan/" target="_blank"
                        class="text-blue-600 hover:underline font-medium">
                        Seikh Md Tahmid Farzan
                    </a>
                </div>

            </div>
        </footer>

        <Toaster richColors position="top-right" />

        <div v-if="authUser && showLogoutModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white p-4 rounded shadow-md w-80">
                <div class="mb-4">Logout Confirmation</div>
                <div class="mb-4">Are you sure you want to logout?</div>

                <div class="flex justify-end space-x-2">
                    <button @click="showLogoutModal = false" class="px-3 py-1 bg-gray-200">Cancel</button>

                    <button @click="logoutHandler" class="flex items-center gap-2 px-3 py-1 bg-red-500 text-white">
                        <FontAwesomeIcon v-if="!loggingOut" icon="right-from-bracket" />
                        <FontAwesomeIcon v-else icon="spinner" spin />
                        Logout
                    </button>
                </div>

            </div>
        </div>

    </div>
</template>
