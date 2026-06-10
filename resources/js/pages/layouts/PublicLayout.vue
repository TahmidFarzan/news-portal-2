<script setup>
import HeaderMenu from '@/components/common/layout/public-layout/HeaderMenu.vue'
import OffCanvasMenu from '@/components/common/layout/public-layout/OffCanvasMenu.vue'
import AuthTopbarDropdownMenu from '@/components/common/layout/public-layout/AuthTopbarDropdownMenu.vue'
import TopbarMenu from '@/components/common/layout/public-layout/TopbarMenu.vue'
import FooterMenu from '@/components/common/layout/public-layout/FooterMenu.vue'
import ToasterMessage from '@/components/common/layout/ToasterMessage.vue'
import BreakingNews from '@/components/common/layout/public-layout/BreakingNews.vue'

import { ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { usePage } from '@inertiajs/vue3'

import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faArrowRightToBracket,
    faMagnifyingGlass,
} from '@fortawesome/free-solid-svg-icons'
import { faFacebook, faGoogle, faYoutube } from '@fortawesome/free-brands-svg-icons'

import { fetchFromApi } from '@/composables/useSystemApi'
import { useSetting } from '@/composables/useSetting'

library.add(
    faArrowRightToBracket,
    faMagnifyingGlass,
    faFacebook,
    faGoogle,
    faYoutube
)

const page = usePage()

const {
    settingGroups,
    settingOptions,
    isTruthyValue,
} = useSetting()

const headerNavbar = ref(null)
const isHeaderSticky = ref(false)
const siteSettings = ref([])

const year = new Date().getFullYear()
const appName = import.meta.env.VITE_APP_NAME
const appLogo = import.meta.env.VITE_APP_LOGO

const authUser = computed(() => page.props.auth?.user ?? null)
const flashMessage = computed(() => page.props.flashMessage)

const handlePageScroll = () => {
    isHeaderSticky.value = window.scrollY > 0
}

const normalizeText = (value) => {
    return String(value ?? '').trim().toLowerCase()
}

const loadSiteSettings = async () => {
    const response = await fetchFromApi(route('site.settings'))

    siteSettings.value = Array.isArray(response)
        ? response
        : response?.data ?? []
}

const getSetting = (field, group = null) => {
    return siteSettings.value.find((setting) => {
        const matchedField =
            normalizeText(setting?.key) === normalizeText(field) ||
            normalizeText(setting?.label) === normalizeText(field)

        const matchedGroup =
            !group || normalizeText(setting?.group) === normalizeText(group)

        return matchedField && matchedGroup
    }) ?? null
}

const facebookSetting = computed(() => {
    return getSetting(settingOptions.FB_SOCIAL_LINK, settingGroups.SOCIAL_LINK)
})

const youtubeSetting = computed(() => {
    return getSetting(settingOptions.YOUTUBE_SOCIAL_LINK, settingGroups.SOCIAL_LINK)
})

const googleNewsSetting = computed(() => {
    return getSetting(settingOptions.GOOGLE_NEWS_SOCIAL_LINK, settingGroups.SOCIAL_LINK)
})

const showTopbarMenu = computed(() => {
    return getSetting(settingOptions.SHOW_TOPBAR_MENU, settingGroups.MENU)
})

const showFooterMenu = computed(() => {
    return getSetting(settingOptions.SHOW_FOOTER_MENU, settingGroups.MENU)
})

const showNameOnHeaderMenu = computed(() => {
    return getSetting(settingOptions.SHOW_NAME_ON_HEADER_MENU, settingGroups.App)
})

const showLogoOnHeaderMenu = computed(() => {
    return getSetting(settingOptions.SHOW_LOGO_ON_HEADER_MENU, settingGroups.App)
})

const showBreakingNews = computed(() => {
    return getSetting(settingOptions.SHOW_BREAKING_NEWS, settingGroups.App)
})

onMounted(async () => {
    await nextTick()

    await loadSiteSettings()

    handlePageScroll()

    window.addEventListener('scroll', handlePageScroll, { passive: true })
})

onBeforeUnmount(() => {
    window.removeEventListener('scroll', handlePageScroll)

    document.body.style.overflow = ''
})

</script>

<template>
    <div class="guest-layout flex flex-col min-h-screen">
        <div class="bg-gray-900 text-white">
            <div class="max-w-7xl mx-auto px-4 py-2 flex justify-between items-center max-[450px]:gap-2">
                <div class="flex space-x-3 max-[450px]:space-x-2 max-[450px]:flex-shrink-0">
                    <a v-if="facebookSetting?.value" :href="facebookSetting.value" target="_blank"
                        rel="noopener noreferrer" aria-label="Facebook">
                        <FontAwesomeIcon :icon="['fab', 'facebook']" />
                    </a>

                    <a v-if="youtubeSetting?.value" :href="youtubeSetting.value" target="_blank"
                        rel="noopener noreferrer" aria-label="Youtube">
                        <FontAwesomeIcon :icon="['fab', 'youtube']" />
                    </a>

                    <a v-if="googleNewsSetting?.value" :href="googleNewsSetting.value" target="_blank"
                        rel="noopener noreferrer" aria-label="Google News">
                        <FontAwesomeIcon :icon="['fab', 'google']" />
                    </a>
                </div>

                <div
                    class="flex items-center space-x-3 relative max-[450px]:flex-1 max-[450px]:min-w-0 max-[450px]:justify-end max-[450px]:space-x-0 max-[450px]:gap-2">
                    <div v-if="isTruthyValue(showTopbarMenu?.value)" class="max-[450px]:flex-1 max-[450px]:min-w-0">
                        <TopbarMenu class="hidden min-[300px]:inline" />
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
                    <img v-if="isTruthyValue(showLogoOnHeaderMenu?.value) && appLogo" :src="appLogo" :alt="appName"
                        class="h-10 max-w-40 object-contain">
                    <b v-if="isTruthyValue(showNameOnHeaderMenu?.value)" class="hidden sm:inline"> {{ appName }}</b>
                </a>


                <div class="flex-1 min-w-0 h-10 flex items-center">
                    <HeaderMenu class="hidden min-[401px]:inline" />
                </div>

                <div class="h-10 flex items-center gap-2 flex-shrink-0">
                    <a :href="route('search')"
                        class="w-10 h-10 flex items-center justify-center rounded-lg hover:bg-white/10"
                        aria-label="Search">
                        <FontAwesomeIcon icon="magnifying-glass" />
                    </a>

                    <OffCanvasMenu />
                </div>
            </div>
        </div>

        <main class="main mx-auto w-full max-w-7xl px-4 py-6">
            <slot />
        </main>

        <BreakingNews v-if="isTruthyValue(showBreakingNews?.value)" title="Breaking News" />

        <footer class="bg-gray-100 py-3 mt-2 text-gray-600 text-sm">
            <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-2 md:gap-4">
                <span class="text-center md:text-left w-full md:w-auto flex-shrink-0">
                    © {{ year }} {{ appName }}
                </span>

                <FooterMenu v-if="isTruthyValue(showFooterMenu?.value)" />

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
