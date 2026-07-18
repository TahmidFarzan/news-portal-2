<script setup>
import HeaderMenu from '@/components/common/layout/public-layout/HeaderMenu.vue'
import OffCanvasMenu from '@/components/common/layout/public-layout/OffCanvasMenu.vue'
import AuthTopbarDropdownMenu from '@/components/common/layout/public-layout/AuthTopbarDropdownMenu.vue'
import TopbarMenu from '@/components/common/layout/public-layout/TopbarMenu.vue'
import FooterMenu from '@/components/common/layout/public-layout/FooterMenu.vue'
import ToasterMessage from '@/components/common/layout/ToasterMessage.vue'
import BreakingNews from '@/components/common/layout/public-layout/BreakingNews.vue'
import LanguageSelect from '@/components/common/layout/public-layout/LanguageSelect.vue'

import {
    ref,
    computed,
    watch,
    nextTick,
    onMounted,
    onBeforeUnmount,
    provide,
} from 'vue'

import { usePage } from '@inertiajs/vue3'

import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

import {
    faArrowRightToBracket,
    faMagnifyingGlass,
} from '@fortawesome/free-solid-svg-icons'

import {
    faFacebook,
    faGoogle,
    faYoutube,
} from '@fortawesome/free-brands-svg-icons'

import { fetchFromApi } from '@/composables/useApiClient'
import { apiCacheKey, apiCacheTTL } from '@/composables/useApiCache'
import { useTheme } from '@/composables/useTheme'

import {
    setSelectedLanguage,
    getSelectedLanguageCode,
    useTranslate,
} from '@/composables/useTranslate'

const { t } = useTranslate()

library.add(
    faArrowRightToBracket,
    faMagnifyingGlass,
    faFacebook,
    faGoogle,
    faYoutube
)

const page = usePage()

const {
    themeGroups,
    themeOptions,
    isTruthyValue,
} = useTheme()

const headerNavbar = ref(null)
const isHeaderSticky = ref(false)

const siteThemes = ref([])

const defaultLanguage = ref(null)
const availableLanguages = ref([])
const languageContextLoaded = ref(false)

const languageCacheKey = 'api:layout:language'

const year = new Date().getFullYear()

const appName = import.meta.env.VITE_APP_NAME
const appLogo = import.meta.env.VITE_APP_LOGO

const authUser = computed(() => {
    return page.props.auth?.user ?? null
})

const flashMessage = computed(() => {
    return page.props.flashMessage
})

const translateNumerText = (value) => {
    return String(value)
        .split('')
        .map((char) => t(`numbers.${char}`))
        .join('')
}

const handlePageScroll = () => {
    isHeaderSticky.value = window.scrollY > 0
}

const normalizeText = (value) => {
    return String(value ?? '')
        .trim()
        .toLowerCase()
}

const normalizeLanguageCode = (code) => {
    return String(code ?? '')
        .trim()
        .toLowerCase()
}

const loadSiteThemes = async () => {
    const apiUrl = route('site.themes')

    const response = await fetchFromApi(
        apiUrl,
        {},
        {
            key: `${apiCacheKey.API_LAYOUT_THEME}:${apiUrl}`,
            ttl: apiCacheTTL.SYSTEM_LONG,
        }
    )

    siteThemes.value = Array.isArray(response)
        ? response
        : response?.data ?? []
}

const getTheme = (field, group = null) => {
    return siteThemes.value.find((theme) => {
        const matchedField =
            normalizeText(theme?.key) === normalizeText(field) ||
            normalizeText(theme?.label) === normalizeText(field)

        const matchedGroup =
            !group ||
            normalizeText(theme?.group) === normalizeText(group)

        return matchedField && matchedGroup
    }) ?? null
}

const facebookTheme = computed(() => {
    return getTheme(
        themeOptions.FB_SOCIAL_LINK,
        themeGroups.SOCIAL_LINK
    )
})

const youtubeTheme = computed(() => {
    return getTheme(
        themeOptions.YOUTUBE_SOCIAL_LINK,
        themeGroups.SOCIAL_LINK
    )
})

const googleNewsTheme = computed(() => {
    return getTheme(
        themeOptions.GOOGLE_NEWS_SOCIAL_LINK,
        themeGroups.SOCIAL_LINK
    )
})

const showTopbarMenu = computed(() => {
    return getTheme(
        themeOptions.SHOW_TOPBAR_MENU,
        themeGroups.MENU
    )
})

const showFooterMenu = computed(() => {
    return getTheme(
        themeOptions.SHOW_FOOTER_MENU,
        themeGroups.MENU
    )
})

const showNameOnHeaderMenu = computed(() => {
    return getTheme(
        themeOptions.SHOW_NAME_ON_HEADER_MENU,
        themeGroups.App
    )
})

const showLogoOnHeaderMenu = computed(() => {
    return getTheme(
        themeOptions.SHOW_LOGO_ON_HEADER_MENU,
        themeGroups.App
    )
})

const showBreakingNews = computed(() => {
    return getTheme(
        themeOptions.SHOW_BREAKING_NEWS,
        themeGroups.App
    )
})

const showGoogleAd = computed(() => {
    const theme = getTheme(
        themeOptions.SHOW_GOOGLE_AD,
        themeGroups.App
    )

    return isTruthyValue(theme?.value)
})

const showTrends = computed(() => {
    const theme = getTheme(
        themeOptions.SHOW_TRENDS,
        themeGroups.App
    )

    return isTruthyValue(theme?.value)
})

const showSurveys = computed(() => {
    const theme = getTheme(
        themeOptions.SHOW_SURVEYS,
        themeGroups.App
    )

    return isTruthyValue(theme?.value)
})

const currentPath = computed(() => {
    const url = String(page.url ?? '/')

    return url.split('?')[0]
})

const firstPathSegment = computed(() => {
    const pathname = currentPath.value

    try {
        return decodeURIComponent(
            pathname
                .split('/')
                .filter(Boolean)[0] ?? ''
        )
    } catch {
        return pathname
            .split('/')
            .filter(Boolean)[0] ?? ''
    }
})

const findLanguageByCode = (code) => {
    const normalizedCode = normalizeLanguageCode(code)

    if (!normalizedCode) {
        return null
    }

    return availableLanguages.value.find((language) => {
        return (
            normalizeLanguageCode(language?.code) ===
            normalizedCode
        )
    }) ?? null
}

const currentLanguage = computed(() => {
    const routeLanguage = findLanguageByCode(
        firstPathSegment.value
    )

    return (
        routeLanguage ??
        defaultLanguage.value ??
        null
    )
})

const isLanguageDefault = (language) => {
    const languageCode = normalizeLanguageCode(
        language?.code
    )

    const defaultCode = normalizeLanguageCode(
        defaultLanguage.value?.code
    )

    if (!languageCode || !defaultCode) {
        return false
    }

    return languageCode === defaultCode
}

const isDefaultLanguage = computed(() => {
    return isLanguageDefault(
        currentLanguage.value
    )
})

const selectedLanguageCode = computed(() => {
    return (
        currentLanguage.value?.code ??
        defaultLanguage.value?.code ??
        getSelectedLanguageCode() ??
        ''
    )
})

const publicRoute = (
    routeName,
    params = {},
    language = currentLanguage.value
) => {
    if (
        language?.code &&
        !isLanguageDefault(language)
    ) {
        return route(
            `localized.${routeName}`,
            {
                languageCode: language.code,
                ...params,
            }
        )
    }

    return route(
        routeName,
        params
    )
}

const layoutSystemApiRefreshKey = (componentName) => {
    return [
        'section-component',
        componentName,
        normalizeLanguageCode(
            currentLanguage.value?.code
        ) || 'default',
    ].join('-')
}

provide(
    'showGoogleAd',
    showGoogleAd
)

provide(
    'showTrends',
    showTrends
)

provide(
    'showSurveys',
    showSurveys
)

provide(
    'publicRoute',
    publicRoute
)

provide(
    'currentLanguage',
    currentLanguage
)

provide(
    'defaultLanguage',
    defaultLanguage
)

provide(
    'availableLanguages',
    availableLanguages
)

provide(
    'isDefaultLanguage',
    isDefaultLanguage
)

const loadLanguageContext = async () => {
    languageContextLoaded.value = false

    try {
        const [
            defaultLanguageResponse,
            languagesResponse,
        ] = await Promise.all([
            fetchFromApi(
                route('site.default-language'),
                {},
                {
                    cache: false,
                }
            ),

            fetchFromApi(
                route('site.languages'),
                {
                    per_page: 100,
                },
                {
                    key: `${languageCacheKey}:${route('site.languages')}`,
                    ttl: apiCacheTTL.SYSTEM_LONG,
                }
            ),
        ])

        defaultLanguage.value =
            defaultLanguageResponse?.data ??
            defaultLanguageResponse ??
            null

        const languages =
            Array.isArray(languagesResponse?.items)
                ? languagesResponse.items
                : Array.isArray(languagesResponse?.data)
                    ? languagesResponse.data
                    : []

        availableLanguages.value = languages

        if (
            defaultLanguage.value &&
            !findLanguageByCode(
                defaultLanguage.value.code
            )
        ) {
            availableLanguages.value = [
                defaultLanguage.value,
                ...availableLanguages.value,
            ]
        }

        const language =
            findLanguageByCode(
                firstPathSegment.value
            ) ??
            defaultLanguage.value ??
            null

        if (language) {
            setSelectedLanguage(language)
        }
    } catch (error) {
        console.error(
            'Failed to load language context:',
            error
        )
    } finally {
        languageContextLoaded.value = true
    }
}

watch(
    () => currentLanguage.value?.code,
    (
        newLanguageCode,
        oldLanguageCode
    ) => {
        if (
            !newLanguageCode ||
            newLanguageCode === oldLanguageCode
        ) {
            return
        }

        const language =
            findLanguageByCode(
                newLanguageCode
            ) ??
            currentLanguage.value

        if (language) {
            setSelectedLanguage(language)
        }
    }
)

onMounted(async () => {
    await nextTick()

    await Promise.all([
        loadLanguageContext(),
        loadSiteThemes(),
    ])

    handlePageScroll()

    window.addEventListener(
        'scroll',
        handlePageScroll,
        {
            passive: true,
        }
    )
})

onBeforeUnmount(() => {
    window.removeEventListener(
        'scroll',
        handlePageScroll
    )

    document.body.style.overflow = ''
})
</script>

<template>
    <div class="guest-layout flex flex-col min-h-screen" :data-lang="selectedLanguageCode">
        <div class="public-topbar text-white">
            <div class="max-w-7xl mx-auto px-4 py-2 flex justify-between items-center max-[450px]:gap-2">
                <div class="flex space-x-3 max-[450px]:space-x-2 max-[450px]:flex-shrink-0">
                    <a v-if="facebookTheme?.value" :href="facebookTheme.value" target="_blank" rel="noopener noreferrer"
                        aria-label="Facebook" class="topbar-icon">
                        <FontAwesomeIcon :icon="['fab', 'facebook']" />
                    </a>

                    <a v-if="youtubeTheme?.value" :href="youtubeTheme.value" target="_blank" rel="noopener noreferrer"
                        aria-label="Youtube" class="topbar-icon">
                        <FontAwesomeIcon :icon="['fab', 'youtube']" />
                    </a>

                    <a v-if="googleNewsTheme?.value" :href="googleNewsTheme.value" target="_blank"
                        rel="noopener noreferrer" aria-label="Google News" class="topbar-icon">
                        <FontAwesomeIcon :icon="['fab', 'google']" />
                    </a>
                </div>

                <div
                    class="flex items-center space-x-3 relative max-[450px]:flex-1 max-[450px]:min-w-0 max-[450px]:justify-end max-[450px]:space-x-0 max-[450px]:gap-2">
                    <div v-if="
                        languageContextLoaded &&
                        isTruthyValue(
                            showTopbarMenu?.value
                        )
                    " class="max-[450px]:flex-1 max-[450px]:min-w-0">
                        <TopbarMenu :key="layoutSystemApiRefreshKey(
                            'topbar-menu'
                        )
                            " :language-route="publicRoute" class="hidden min-[300px]:inline" />
                    </div>

                    <a v-if="!authUser" :href="route('login')"
                        class="flex items-center gap-1 text-gray-300 hover:text-white max-[450px]:flex-shrink-0">
                        <FontAwesomeIcon icon="arrow-right-to-bracket" />

                        <span class="max-[450px]:hidden">
                            {{ t('common.labels.login') }}
                        </span>
                    </a>

                    <div v-else class="max-[450px]:flex-shrink-0">
                        <AuthTopbarDropdownMenu :key="layoutSystemApiRefreshKey(
                            'auth-topbar-menu'
                        )
                            " :auth-user="authUser" />
                    </div>

                    <LanguageSelect v-if="languageContextLoaded" class="max-[450px]:flex-shrink-0" :available-languages="availableLanguages
                        " :current-language="currentLanguage
                            " :default-language="defaultLanguage
                            " />
                </div>
            </div>
        </div>

        <div ref="headerNavbar" class="public-header text-white transition-shadow" :class="{
            'is-sticky sticky top-0 z-50':
                isHeaderSticky,
        }">
            <div class="max-w-7xl mx-auto px-4 h-16 flex items-center gap-4">
                <a :href="languageContextLoaded
                        ? publicRoute('home')
                        : route('home')
                    "
                    class="brand-link h-10 flex items-center pr-4 text-white font-semibold flex-shrink-0 leading-none">
                    <img v-if="
                        isTruthyValue(
                            showLogoOnHeaderMenu?.value
                        ) &&
                        appLogo
                    " :src="appLogo" :alt="appName" class="h-10 max-w-40 object-contain" />

                    <b v-if="
                        isTruthyValue(
                            showNameOnHeaderMenu?.value
                        )
                    " class="hidden sm:inline">
                        {{ t('common.app.name') }}
                    </b>
                </a>

                <div class="flex-1 min-w-0 h-10 flex items-center">
                    <HeaderMenu v-if="
                        languageContextLoaded
                    " :key="layoutSystemApiRefreshKey(
                            'header-menu'
                        )
                            " :language-route="publicRoute
                            " class="hidden min-[401px]:inline" />
                </div>

                <div class="h-10 flex items-center gap-2 flex-shrink-0">
                    <a :href="languageContextLoaded
                            ? publicRoute(
                                'search'
                            )
                            : route(
                                'search'
                            )
                        " class="header-action w-10 h-10 flex items-center justify-center rounded-lg hover:bg-white/10"
                        aria-label="Search">
                        <FontAwesomeIcon icon="magnifying-glass" />
                    </a>

                    <OffCanvasMenu v-if="
                        languageContextLoaded
                    " :key="layoutSystemApiRefreshKey(
                            'off-canvas-menu'
                        )
                            " :language-route="publicRoute
                            " />
                </div>
            </div>
        </div>

        <main class="main public-main mx-auto w-full max-w-7xl px-4 py-6">
            <slot v-if="
                languageContextLoaded
            " />
        </main>

        <BreakingNews v-if="
            languageContextLoaded &&
            isTruthyValue(
                showBreakingNews?.value
            )
        " :key="layoutSystemApiRefreshKey(
                'breaking-news'
            )
                " :title="t(
                'common.messages.breakingNews'
            )
                " :current-language="currentLanguage
                " :is-default-language="isDefaultLanguage
                " />

        <footer class="public-footer py-4 mt-2 text-sm">
            <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-2 md:gap-4">
                <span class="text-center md:text-left w-full md:w-auto flex-shrink-0">
                    {{ t('common.messages.text') }}
                    {{ translateNumerText(year) }}
                    {{ t('common.app.name') }}
                </span>

                <FooterMenu v-if="
                    languageContextLoaded &&
                    isTruthyValue(
                        showFooterMenu?.value
                    )
                " :key="layoutSystemApiRefreshKey(
                        'footer-menu'
                    )
                        " :current-language="currentLanguage
                        " :is-default-language="isDefaultLanguage
                        " />

                <span class="text-center md:text-right w-full md:w-auto flex-shrink-0">
                    {{ t('common.app.developedBy') }}

                    <a href="https://www.linkedin.com/in/sk-md-tahmid-farzan/" target="_blank" rel="noopener noreferrer"
                        class="text-blue-600 hover:underline font-medium">
                        {{ t('common.app.developerName') }}
                    </a>
                </span>
            </div>
        </footer>

        <ToasterMessage :flash-message="flashMessage
            " />
    </div>
</template>

<style scoped>
.guest-layout {
    font-family: var(--font-en);
    background: var(--news-body-gradient);
    color: var(--news-ink);
    text-rendering: optimizeLegibility;
}

.guest-layout[data-lang="bn"] {
    font-family: var(--font-bn);
}

.guest-layout ::selection {
    background: var(--news-selection-bg);
    color: var(--news-selection-color);
}

.guest-layout :deep(a:focus-visible),
.guest-layout :deep(button:focus-visible),
.guest-layout :deep(input:focus-visible),
.guest-layout :deep(select:focus-visible),
.guest-layout :deep(textarea:focus-visible) {
    outline: 0;
    box-shadow: var(--news-focus-ring);
}

.public-topbar {
    background: var(--news-topbar-gradient);
    border-bottom: var(--news-border-white-subtle-line);
    font-size: var(--news-topbar-font-size);
}

.topbar-icon {
    display: inline-flex;
    height: 1.75rem;
    width: 1.75rem;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    color: var(--news-text-inverse-muted);
    transition:
        transform var(--news-transition),
        background-color var(--news-transition),
        color var(--news-transition);
}

.topbar-icon:hover {
    transform: translateY(-1px);
    background: var(--news-border-white-muted);
    color: var(--news-white);
}

.public-header {
    background: var(--news-header-gradient);
    border-bottom: var(--news-border-white-subtle-line);
}

.public-header.is-sticky {
    box-shadow: var(--news-shadow-sticky);
    backdrop-filter: blur(18px);
}

.brand-link {
    border-right: 1px solid var(--news-border-white-muted);
}

.header-action {
    transition:
        background-color var(--news-transition),
        transform var(--news-transition);
}

.header-action:hover {
    transform: translateY(-1px);
}

.public-main {
    flex: 1;
}

.public-footer {
    border-top: var(--news-border-default);
    background: var(--news-surface);
    color: var(--news-muted);
}

@media (max-width: 640px) {
    .public-main {
        padding-top: 1rem;
        padding-bottom: 1rem;
    }
}
</style>
