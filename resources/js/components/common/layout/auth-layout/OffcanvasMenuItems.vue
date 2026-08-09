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
    faFile,
    faBullhorn,
    faSquarePollHorizontal,
    faLanguage,
    faQuestionCircle
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
    faFile,
    faBullhorn,
    faSquarePollHorizontal,
    faLanguage,
    faQuestionCircle
)
const { t } = useTranslate()

import {
    canAccessUser,
    canAccessNewsAttributes,
    canAccessNews,
    canAccessBreakingNews,
    canAccessPage,
    canAccessMenu,
    canAccessTheme,
    canAccessLanguage,
    canAccessGoogleAdsense,
    canAccessCategory,
    canAccessTag,
    canAccessTrend,
    canAccessEvent,
    canAccessLocation,
    canAccessContributor,
    canAccessSurvey,
    canAccessQuiz
} from '@/composables/useUserPermissions'

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

const canAccessUserComputed = computed(() => {
    return canAccessUser(authUser)
})

const canAccessNewsAttributesComputed = computed(() => {
    return canAccessNewsAttributes(authUser)
})

const canAccessCategoryComputed = computed(() => {
    return canAccessCategory(authUser)
})

const canAccessTagComputed = computed(() => {
    return canAccessTag(authUser)
})

const canAccessTrendComputed = computed(() => {
    return canAccessTrend(authUser)
})

const canAccessEventComputed = computed(() => {
    return canAccessEvent(authUser)
})

const canAccessLocationComputed = computed(() => {
    return canAccessLocation(authUser)
})

const canAccessContributorComputed = computed(() => {
    return canAccessContributor(authUser)
})

const canAccessNewsComputed = computed(() => {
    return canAccessNews(authUser)
})

const canAccessBreakingNewsComputed = computed(() => {
    return canAccessBreakingNews(authUser)
})

const canAccessPageComputed = computed(() => {
    return canAccessPage(authUser)
})

const canAccessMenuComputed = computed(() => {
    return canAccessMenu(authUser)
})

const canAccessThemeComputed = computed(() => {
    return canAccessTheme(authUser)
})

const canAccessLanguageComputed = computed(() => {
    return canAccessLanguage(authUser)
})

const canAccessGoogleAdsenseComputed = computed(() => {
    return canAccessGoogleAdsense(authUser)
})

const canAccessSurveyComputed = computed(() => {
    return canAccessSurvey(authUser)
})

const canAccessQuizComputed = computed(() => {
    return canAccessQuiz(authUser)
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
            {{ t("common.labels.dashboard") }}
        </a>

        <a :href="route('back-office.medias.index')" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
            :class="isCurrentPage('/back-office/medias/*') ? 'bg-gray-200 font-medium' : ''">
            <FontAwesomeIcon icon="photo-film" />
            {{ t("common.labels.media") }}
        </a>

        <button @click="toggleShowSubMenu('NewsAttributes')"
            class="flex items-center justify-between w-full px-3 py-2 rounded hover:bg-gray-100">
            <span class="flex items-center gap-2">
                <FontAwesomeIcon icon="layer-group" />
                {{ t("layout.auth.offcanvasMenuItems.navigation.newsAttributes") }}
            </span>
            <FontAwesomeIcon :icon="isSubMenuVisible('NewsAttributes') ? 'chevron-up' : 'chevron-down'" />
        </button>

        <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 max-h-0"
            enter-to-class="opacity-100 max-h-40" leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 max-h-40" leave-to-class="opacity-0 max-h-0">
            <div v-if="isSubMenuVisible('NewsAttributes') && canAccessNewsAttributesComputed"
                class="ml-4 flex flex-col space-y-1 overflow-hidden">

                <a v-if="canAccessCategoryComputed" :href="route('back-office.categories.index')"
                    class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
                    :class="isCurrentPage('/back-office/categories/*') ? 'bg-gray-200 font-medium' : ''">
                    <FontAwesomeIcon icon="layer-group" />
                    {{ t("common.messages.categories") }}
                </a>

                <a v-if="canAccessTagComputed" :href="route('back-office.tags.index')"
                    class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
                    :class="isCurrentPage('/back-office/tags/*') ? 'bg-gray-200 font-medium' : ''">
                    <FontAwesomeIcon icon="tags" />
                    {{ t("common.labels.tags") }}
                </a>

                <a v-if="canAccessTrendComputed" :href="route('back-office.trends.index')"
                    class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
                    :class="isCurrentPage('/back-office/trends/*') ? 'bg-gray-200 font-medium' : ''">
                    <FontAwesomeIcon icon="star" />
                    {{ t("common.labels.trends") }}
                </a>

                <a v-if="canAccessLocationComputed" :href="route('back-office.locations.index')"
                    class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
                    :class="isCurrentPage('/back-office/locations/*') ? 'bg-gray-200 font-medium' : ''">
                    <FontAwesomeIcon icon="globe" />
                    {{ t("common.labels.location") }}
                </a>

                <a v-if="canAccessEventComputed" :href="route('back-office.events.index')"
                    class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
                    :class="isCurrentPage('/back-office/events/*') ? 'bg-gray-200 font-medium' : ''">
                    <FontAwesomeIcon icon="fan" />
                    {{ t("common.messages.events") }}
                </a>

                <a v-if="canAccessContributorComputed" :href="route('back-office.contributors.index')"
                    class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
                    :class="isCurrentPage('/back-office/contributors/*') ? 'bg-gray-200 font-medium' : ''">
                    <FontAwesomeIcon icon="users" />
                    {{ t("common.labels.contributors") }}
                </a>

            </div>
        </Transition>

        <a v-if="canAccessNewsComputed" :href="route('back-office.news.index')"
            class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
            :class="isCurrentPage('/back-office/news/*') ? 'bg-gray-200 font-medium' : ''">
            <FontAwesomeIcon icon="newspaper" />
            {{ t("common.labels.news") }}
        </a>

        <a v-if="canAccessBreakingNewsComputed" :href="route('back-office.breaking-news.index')"
            class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
            :class="isCurrentPage('/back-office/breaking-news/*') ? 'bg-gray-200 font-medium' : ''">
            <FontAwesomeIcon icon="newspaper" />
            {{ t("common.messages.breakingNews") }}
        </a>

        <a v-if="canAccessPageComputed" :href="route('back-office.pages.index')"
            class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
            :class="isCurrentPage('/back-office/pages/*') ? 'bg-gray-200 font-medium' : ''">
            <FontAwesomeIcon icon="file" />
            {{ t("layout.auth.offcanvasMenuItems.navigation.pages") }}
        </a>

        <a v-if="canAccessMenuComputed" :href="route('back-office.menus.index')"
            class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
            :class="isCurrentPage('/back-office/menus/*') ? 'bg-gray-200 font-medium' : ''">
            <FontAwesomeIcon icon="ellipsis-vertical" />
            {{ t("common.labels.menus") }}
        </a>

        <button @click="toggleShowSubMenu('UserManagement')"
            class="flex items-center justify-between w-full px-3 py-2 rounded hover:bg-gray-100">
            <span class="flex items-center gap-2">
                <FontAwesomeIcon icon="users" />
                {{ t("layout.auth.offcanvasMenuItems.navigation.userAnagement") }}
            </span>
            <FontAwesomeIcon :icon="isSubMenuVisible('UserManagement') ? 'chevron-up' : 'chevron-down'" />
        </button>

        <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 max-h-0"
            enter-to-class="opacity-100 max-h-40" leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 max-h-40" leave-to-class="opacity-0 max-h-0">
            <div v-if="isSubMenuVisible('UserManagement') && canAccessUserComputed"
                class="ml-4 flex flex-col space-y-1 overflow-hidden">

                <a :href="route('back-office.users.index')"
                    class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
                    :class="isAnyCurrentPage(routeMap.UserManagement) ? 'bg-gray-200 font-medium' : ''">
                    <FontAwesomeIcon icon="user" />
                    {{ t("common.labels.users") }}
                </a>

            </div>
        </Transition>

        <a v-if="canAccessThemeComputed" :href="route('back-office.themes.index')"
            class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
            :class="isCurrentPage('/auth-user/themes/*') ? 'bg-gray-200 font-medium' : ''">
            <FontAwesomeIcon icon="gears" />
            {{ t("common.labels.themes") }}
        </a>

        <a v-if="canAccessLanguageComputed" :href="route('back-office.languages.index')"
            class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
            :class="isCurrentPage('/back-office/languages/*') ? 'bg-gray-200 font-medium' : ''">
            <FontAwesomeIcon icon="language" />
            {{ t("common.labels.languages") }}
        </a>

        <a v-if="canAccessGoogleAdsenseComputed" :href="route('back-office.google-adsenses.index')"
            class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
            :class="isCurrentPage('/back-office/google-adsenses/*') ? 'bg-gray-200 font-medium' : ''">
            <FontAwesomeIcon icon="bullhorn" />
            {{ t("common.messages.googleAdsense") }}
        </a>

        <a v-if="canAccessSurveyComputed" :href="route('back-office.surveys.index')"
            class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
            :class="isCurrentPage('/back-office/surveys/*') ? 'bg-gray-200 font-medium' : ''">
            <FontAwesomeIcon icon="square-poll-horizontal" />
            {{ t("common.labels.surveys") }}
        </a>

        <a v-if="canAccessQuizComputed" :href="route('back-office.quizzes.index')"
            class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100"
            :class="isCurrentPage('/back-office/quizzes/*') ? 'bg-gray-200 font-medium' : ''">
            <FontAwesomeIcon icon="question-circle" />
            {{ t("common.labels.quizzes") }}
        </a>
    </div>
</template>
