<script setup>
import HeaderMenu from '@/components/common/layout/public-layout/HeaderMenu.vue'
import OffCanvasMenu from '@/components/common/layout/public-layout/OffCanvasMenu.vue'
import AuthTopbarDropdownMenu from '@/components/common/layout/public-layout/AuthTopbarDropdownMenu.vue'
import TopbarMenu from '@/components/common/layout/public-layout/TopbarMenu.vue'
import FooterMenu from '@/components/common/layout/public-layout/FooterMenu.vue'
import ToasterMessage from '@/components/common/layout/ToasterMessage.vue'

import { ref, computed, watch, provide, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { usePage, router as inertia } from '@inertiajs/vue3'

import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faArrowRightToBracket,
    faSpinner,
    faMagnifyingGlass
} from '@fortawesome/free-solid-svg-icons'
import { faFacebook, faLinkedin, faGoogle } from '@fortawesome/free-brands-svg-icons'

library.add(
    faArrowRightToBracket,
    faSpinner,
    faMagnifyingGlass,
    faFacebook,
    faLinkedin,
    faGoogle
)

const page = usePage()

const pageReady = ref(true)
const headerNavbar = ref(null)
const isHeaderSticky = ref(false)

provide('pageReady', pageReady)

const year = new Date().getFullYear()
const appName = import.meta.env.VITE_APP_NAME

const authUser = computed(() => page.props.auth?.user ?? null)
const flashMessage = computed(() => page.props.flashMessage)

let removeInertiaStartListener = null
let removeInertiaFinishListener = null

const handlePageScroll = () => {
    isHeaderSticky.value = window.scrollY > 0
}

watch(pageReady, (ready) => {
    document.body.style.overflow = ready ? '' : 'hidden'
}, { immediate: true })

onMounted(async () => {
    await nextTick()

    pageReady.value = true

    removeInertiaStartListener = inertia.on('start', () => {
        pageReady.value = false
    })

    removeInertiaFinishListener = inertia.on('finish', () => {
        pageReady.value = true
    })

    handlePageScroll()

    window.addEventListener('scroll', handlePageScroll, { passive: true })
})

onBeforeUnmount(() => {
    removeInertiaStartListener?.()
    removeInertiaFinishListener?.()

    window.removeEventListener('scroll', handlePageScroll)

    document.body.style.overflow = ''
})
</script>

<template>
    <div class="guest-layout flex flex-col min-h-screen">
        <div class="bg-gray-900 text-white">
            <div class="max-w-7xl mx-auto px-4 py-2 flex justify-between items-center max-[450px]:gap-2">
                <div class="flex space-x-3 max-[450px]:space-x-2 max-[450px]:flex-shrink-0">
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

                <div
                    class="flex items-center space-x-3 relative max-[450px]:flex-1 max-[450px]:min-w-0 max-[450px]:justify-end max-[450px]:space-x-0 max-[450px]:gap-2">
                    <div class="max-[450px]:flex-1 max-[450px]:min-w-0">
                        <TopbarMenu />
                    </div>

                    <a v-if="!authUser" :href="route('login')"
                        class="flex items-center gap-1 text-gray-300 hover:text-white max-[450px]:flex-shrink-0">
                        <FontAwesomeIcon icon="arrow-right-to-bracket" />
                        <span class="max-[450px]:hidden">Login</span>
                    </a>

                    <div v-else class="max-[450px]:flex-shrink-0">
                        <AuthTopbarDropdownMenu :auth-user="authUser" />
                    </div>
                </div>
            </div>
        </div>

        <div ref="headerNavbar" class="bg-gray-900 text-white transition-shadow"
            :class="{ 'shadow-md sticky top-0 z-50': isHeaderSticky }">

            <div class="max-w-7xl mx-auto px-4 h-16 flex items-center gap-4">
                <a :href="route('home')"
                    class="h-10 flex items-center pr-4 text-white font-semibold flex-shrink-0 leading-none">
                    {{ appName }}
                </a>

                <div class="flex-1 min-w-0 h-10 flex items-center">
                    <HeaderMenu />
                </div>

                <div class="h-10 flex items-center gap-2 flex-shrink-0">
                    <button type="button"
                        class="w-10 h-10 flex items-center justify-center rounded-lg hover:bg-white/10"
                        aria-label="Search">
                        <FontAwesomeIcon icon="magnifying-glass" />
                    </button>

                    <OffCanvasMenu />
                </div>
            </div>
        </div>

        <main class="flex-1 max-w-7xl mx-auto px-4 py-4 relative">
            <div v-if="!pageReady" class="fixed inset-0 bg-white/90 flex items-center justify-center z-50">
                <FontAwesomeIcon icon="spinner" spin class="text-2xl text-blue-500" />
            </div>

            <slot />
        </main>

        <footer class="bg-gray-100 py-3 mt-2 text-gray-600 text-sm">
            <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-2 md:gap-4">
                <span class="text-center md:text-left w-full md:w-auto flex-shrink-0">
                    © {{ year }} {{ appName }}
                </span>

                <FooterMenu />

                <span class="text-center md:text-right w-full md:w-auto flex-shrink-0">
                    Developed by
                    <a href="https://www.linkedin.com/in/sk-md-tahmid-farzan/" target="_blank" rel="noopener noreferrer"
                        class="text-blue-600 hover:underline font-medium">
                        Seikh Md Tahmid Farzan
                    </a>
                </span>
            </div>
        </footer>

        <ToasterMessage :flash-message="flashMessage" />
    </div>
</template>
