<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'

import { computed, nextTick, onMounted, ref } from 'vue'
import { Head } from '@inertiajs/vue3'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'

import {
    faAtom,
    faBroom,
    faCalendarDays,
    faCheck,
    faClock,
    faCopy,
    faFileCode,
    faGlobe,
    faPenToSquare,
    faPlay,
    faRotateRight,
    faRss,
    faShieldHalved,
    faSitemap,
    faStop,
    faTrashCan,
    faTriangleExclamation,
} from '@fortawesome/free-solid-svg-icons'

import { languages, useTranslate } from '@/composables/useTranslate'

import { fetchFromApi } from '@/composables/useApiClient'
import { apiCacheKey, apiCacheTTL } from '@/composables/useApiCache'


FontAwesomeLibrary.add(
    faAtom,
    faBroom,
    faCalendarDays,
    faCheck,
    faClock,
    faCopy,
    faFileCode,
    faGlobe,
    faPenToSquare,
    faPlay,
    faRotateRight,
    faRss,
    faShieldHalved,
    faSitemap,
    faStop,
    faTrashCan,
    faTriangleExclamation,
)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const appUrl = String(import.meta.env.VITE_APP_URL || '').replace(/\/$/, '')

const activeTab = ref(null)
const copiedKey = ref(null)

const siteLanguages = ref([])
const defaultLanguage = ref(null)

const tabs = computed(() => [
    {
        key: 'queue',
        label: t('admin.settings.index.tabs.queue'),
        icon: faClock,
    },
    {
        key: 'schedule',
        label: t('admin.settings.index.tabs.schedule'),
        icon: faCalendarDays,
    },
    {
        key: 'sitemap',
        label: t('common.messages.sitemap'),
        icon: faSitemap,
    },
    {
        key: 'feeds_rss',
        label: t('admin.settings.index.tabs.feedsRss'),
        icon: faRss,
    },
    {
        key: 'robots_txt',
        label: t('admin.settings.index.tabs.robotsTxt'),
        icon: faGlobe,
    },
    {
        key: 'ads_txt',
        label: t('admin.settings.index.tabs.adsTxt'),
        icon: faFileCode,
    },
])

const activeTabKey = computed(() => {
    return activeTab.value ?? tabs.value[0]?.key ?? null
})

const queueActions = computed(() => [
    {
        key: 'queue_start',
        title: t('admin.settings.index.queueActions.startQueue'),
        url: route('back-office.settings.queue.start'),
        icon: faPlay,
    },
    {
        key: 'queue_restart',
        title: t('admin.settings.index.queueActions.restartQueue'),
        url: route('back-office.settings.queue.restart'),
        icon: faRotateRight,
    },
    {
        key: 'queue_clear',
        title: t('admin.settings.index.queueActions.clearQueue'),
        url: route('back-office.settings.queue.clear'),
        icon: faBroom,
    },
    {
        key: 'queue_flush',
        title: t('admin.settings.index.queueActions.flushQueue'),
        url: route('back-office.settings.queue.flush'),
        icon: faTrashCan,
    },
    {
        key: 'queue_monitor_stale',
        title: t('admin.settings.index.queueActions.monitorStale'),
        url: route('back-office.settings.queue.monitor.stale'),
        icon: faTriangleExclamation,
    },
    {
        key: 'queue_monitor_purge',
        title: t('admin.settings.index.queueActions.monitorPurge'),
        url: route('back-office.settings.queue.monitor.purge'),
        icon: faShieldHalved,
    },
])

const scheduleActions = computed(() => [
    {
        key: 'schedule_start',
        title: t('admin.settings.index.scheduleActions.startSchedule'),
        url: route('back-office.settings.schedule.start'),
        icon: faPlay,
    },
    {
        key: 'schedule_stop',
        title: t('admin.settings.index.scheduleActions.stopSchedule'),
        url: route('back-office.settings.schedule.stop'),
        icon: faStop,
    },
])

const robotsTxtAction = computed(() => ({
    key: 'robots_txt_edit',
    title: t('common.labels.editRobotsTxt'),
    url: route('back-office.settings.robots-txt.edit'),
    icon: faPenToSquare,
}))

const adsTxtAction = computed(() => ({
    key: 'ads_txt_edit',
    title: t('common.labels.editAdsTxt'),
    url: route('back-office.settings.ads-txt.edit'),
    icon: faPenToSquare,
}))

const sitemapPatterns = [
    {
        key: 'sitemaps_index',
        title: () => t('admin.settings.index.sitemapLinks.sitemapIndex'),
        path: '/sitemaps.xml',
    },
    {
        key: 'sitemaps_categories',
        title: () => t('admin.settings.index.sitemapLinks.categoriesSitemap'),
        path: '/sitemaps/categories.xml',
    },
    {
        key: 'sitemaps_tags',
        title: () => t('admin.settings.index.sitemapLinks.tagsSitemap'),
        path: '/sitemaps/tags.xml',
    },
    {
        key: 'sitemaps_events',
        title: () => t('admin.settings.index.sitemapLinks.eventsSitemap'),
        path: '/sitemaps/events.xml',
    },
    {
        key: 'sitemaps_contributors',
        title: () => t('admin.settings.index.sitemapLinks.contributorsSitemap'),
        path: '/sitemaps/contributors.xml',
    },
    {
        key: 'sitemaps_news',
        title: () => t('admin.settings.index.sitemapLinks.newsSitemap'),
        path: '/sitemaps/news.xml',
    },
    {
        key: 'sitemaps_latest_news',
        title: () => t('admin.settings.index.sitemapLinks.latestNewsSitemap'),
        path: '/sitemaps/latest-news.xml',
    },
    {
        key: 'dynamic_categories_news',
        title: () => t('admin.settings.index.sitemapLinks.categoryNewsPattern'),
        path: '/categories/{slugTree}/news.xml',
    },
    {
        key: 'dynamic_locations_news',
        title: () => t('admin.settings.index.sitemapLinks.locationNewsPattern'),
        path: '/locations/{slugTree}/news.xml',
    },
    {
        key: 'dynamic_events_news',
        title: () => t('admin.settings.index.sitemapLinks.eventNewsPattern'),
        path: '/events/{slug}/news.xml',
    },
    {
        key: 'dynamic_tags_news',
        title: () => t('admin.settings.index.sitemapLinks.tagNewsPattern'),
        path: '/tags/{slug}/news.xml',
    },
    {
        key: 'dynamic_contributors_news',
        title: () => t('admin.settings.index.sitemapLinks.contributorNewsPattern'),
        path: '/contributors/{slug}/news.xml',
    },
]

const sitemapLinks = computed(() => {
    return siteLanguages.value.flatMap((language) => {
        const prefix = getLanguagePrefix(language)

        return sitemapPatterns.map((item) => ({
            key: `${language.code}_${item.key}`,
            language: language.name,
            title: item.title(),
            text: `${appUrl}${prefix}${item.path}`,
            copyable: true,
        }))
    })
})

const feedTypes = [
    {
        key: 'rss',
        title: () => t('admin.settings.index.feedLinks.rssFeeds'),
        icon: faRss,
    },
    {
        key: 'atom',
        title: () => t('admin.settings.index.feedLinks.atomFeeds'),
        icon: faAtom,
    },
]

const feedPatterns = [
    {
        key: 'news',
        title: () => t('admin.settings.index.feedLinks.newsFeedPattern'),
        path: '/feeds/{type}/news.xml',
    },
    {
        key: 'latest_news',
        title: () => t('admin.settings.index.feedLinks.latestNewsFeedPattern'),
        path: '/feeds/{type}/latest-news.xml',
    },
    {
        key: 'category_news',
        title: () => t('admin.settings.index.feedLinks.categoryNewsFeedPattern'),
        path: '/feeds/{type}/categories/{slugTree}/news.xml',
    },
    {
        key: 'location_news',
        title: () => t('admin.settings.index.feedLinks.locationNewsFeedPattern'),
        path: '/feeds/{type}/locations/{slugTree}/news.xml',
    },
    {
        key: 'event_news',
        title: () => t('admin.settings.index.feedLinks.eventNewsFeedPattern'),
        path: '/feeds/{type}/events/{slug}/news.xml',
    },
    {
        key: 'tag_news',
        title: () => t('admin.settings.index.feedLinks.tagNewsFeedPattern'),
        path: '/feeds/{type}/tags/{slug}/news.xml',
    },
    {
        key: 'contributor_news',
        title: () => t('admin.settings.index.feedLinks.contributorNewsFeedPattern'),
        path: '/feeds/{type}/contributors/{slug}/news.xml',
    },
]

const feedGroups = computed(() => {
    return siteLanguages.value.flatMap((language) => {
        const prefix = getLanguagePrefix(language)

        return feedTypes.map((feedType) => ({
            key: `${language.code}_${feedType.key}`,
            language: language.name,
            title: `${feedType.title()} (${language.code.toUpperCase()})`,
            icon: feedType.icon,
            links: feedPatterns.map((pattern) => ({
                key: `${language.code}_${feedType.key}_${pattern.key}`,
                title: pattern.title(),
                text: `${appUrl}${prefix}${pattern.path.replace('{type}', feedType.key)}`,
                copyable: true,
            })),
        }))
    })
})

const loadLanguages = async () => {
    const apiUrl = route('site.languages')

    const response = await fetchFromApi(
        apiUrl,
        {},
        {
            key: `${apiCacheKey.API_LAYOUT_THEME}:${apiUrl}`,
            ttl: apiCacheTTL.SYSTEM_LONG,
        },
    )

    siteLanguages.value = response.items || []
}

const loadDefaultLanguage = async () => {
    const apiUrl = route('site.default-language')

    const response = await fetchFromApi(
        apiUrl,
        {},
        {
            key: `${apiCacheKey.API_LAYOUT_THEME}:${apiUrl}`,
            ttl: apiCacheTTL.SYSTEM_LONG,
        },
    )

    defaultLanguage.value = response
}

const copyToClipboard = async (item) => {
    if (!item?.copyable || !item?.text) return

    try {
        if (navigator?.clipboard?.writeText) {
            await navigator.clipboard.writeText(item.text)
        } else {
            const textarea = document.createElement('textarea')

            textarea.value = item.text
            textarea.setAttribute('readonly', '')
            textarea.style.position = 'absolute'
            textarea.style.left = '-9999px'

            document.body.appendChild(textarea)
            textarea.select()
            document.execCommand('copy')
            document.body.removeChild(textarea)
        }

        copiedKey.value = item.key

        setTimeout(() => {
            if (copiedKey.value === item.key) {
                copiedKey.value = null
            }
        }, 1500)
    } catch (error) {
        console.error('Copy failed:', error)
    }
}

const getLanguagePrefix = (language) => {
    if (!language || !defaultLanguage.value) {
        return ''
    }

    return language.code === defaultLanguage.value.code
        ? ''
        : `/${language.code}`
}

onMounted(async () => {
    await Promise.all([
        loadLanguages(),
        loadDefaultLanguage(),
    ])

    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                {
                    text: t('admin.settings.index.labels.setting'),
                    active: true,
                },
            ],
        }),
    )
})
</script>

<template>

    <Head :title="t('admin.settings.index.labels.setting')" />

    <div class="w-full space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-950">
                {{ t('admin.settings.index.labels.setting') }}
            </h2>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-4">
                <nav class="-mb-px flex flex-wrap gap-1">
                    <button v-for="tab in tabs" :key="tab.key" type="button"
                        class="inline-flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium transition"
                        :class="activeTabKey === tab.key
                            ? 'border-red-600 text-red-600'
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                            " @click="activeTab = tab.key">
                        <FontAwesomeIcon :icon="tab.icon" class="text-xs" />
                        <span>{{ tab.label }}</span>
                    </button>
                </nav>
            </div>

            <div class="p-4 sm:p-6">
                <div v-if="activeTabKey === 'queue'" class="space-y-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-950">
                            {{ t('admin.settings.index.labels.queueActions') }}
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            {{ t('admin.settings.index.labels.queueActionsDescription') }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <a v-for="item in queueActions" :key="item.key" :href="item.url"
                            class="group flex items-center justify-between rounded-xl border border-gray-200 bg-white p-4 transition hover:border-red-200 hover:bg-red-50">
                            <div class="flex min-w-0 items-center gap-3">
                                <span
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-red-50 text-red-600 transition group-hover:bg-white">
                                    <FontAwesomeIcon :icon="item.icon" class="text-sm" />
                                </span>

                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-950">
                                        {{ item.title }}
                                    </p>

                                    <p class="mt-1 truncate font-mono text-xs text-gray-500">
                                        {{ item.url }}
                                    </p>
                                </div>
                            </div>

                            <span class="text-xs font-semibold text-red-600">
                                {{ t('admin.settings.index.labels.run') }}
                            </span>
                        </a>
                    </div>
                </div>

                <div v-else-if="activeTabKey === 'schedule'" class="space-y-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-950">
                            {{ t('admin.settings.index.labels.scheduleActions') }}
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            {{ t('admin.settings.index.labels.scheduleActionsDescription') }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <a v-for="item in scheduleActions" :key="item.key" :href="item.url"
                            class="group flex items-center justify-between rounded-xl border border-gray-200 bg-white p-4 transition hover:border-red-200 hover:bg-red-50">
                            <div class="flex min-w-0 items-center gap-3">
                                <span
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-red-50 text-red-600 transition group-hover:bg-white">
                                    <FontAwesomeIcon :icon="item.icon" class="text-sm" />
                                </span>

                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-950">
                                        {{ item.title }}
                                    </p>

                                    <p class="mt-1 truncate font-mono text-xs text-gray-500">
                                        {{ item.url }}
                                    </p>
                                </div>
                            </div>

                            <span class="text-xs font-semibold text-red-600">
                                {{ t('admin.settings.index.labels.run') }}
                            </span>
                        </a>
                    </div>
                </div>

                <div v-else-if="activeTabKey === 'sitemap'" class="space-y-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-950">
                            {{ t('admin.settings.index.labels.sitemapLinks') }}
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            {{ t('admin.settings.index.labels.clickCopySitemapUrl') }}
                        </p>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-gray-200">
                        <div v-for="item in sitemapLinks" :key="item.key"
                            class="grid grid-cols-1 gap-3 border-b border-gray-100 p-4 last:border-b-0 md:grid-cols-12 md:items-center">
                            <div class="md:col-span-4">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                        <FontAwesomeIcon :icon="faSitemap" class="text-xs" />
                                    </span>

                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-950">
                                            {{ item.title }}
                                        </p>

                                        <p class="mt-1 text-xs text-blue-600">
                                            {{ item.language }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="md:col-span-6">
                                <p
                                    class="truncate rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 font-mono text-xs text-gray-700">
                                    {{ item.text }}
                                </p>
                            </div>

                            <div class="flex justify-end md:col-span-2">
                                <button type="button"
                                    class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700"
                                    @click="copyToClipboard(item)">
                                    <FontAwesomeIcon :icon="copiedKey === item.key ? faCheck : faCopy"
                                        class="text-xs" />

                                    <span>
                                        {{
                                            copiedKey === item.key
                                                ? t('common.labels.copied')
                                                : t('common.labels.copy')
                                        }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else-if="activeTabKey === 'feeds_rss'" class="space-y-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-950">
                            {{ t('admin.settings.index.labels.feedsRssLinks') }}
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            {{ t('admin.settings.index.labels.feedsRssPatternsAreDynamic') }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <div v-for="feedGroup in feedGroups" :key="feedGroup.key"
                            class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                            <div class="border-b border-gray-100 p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                            <FontAwesomeIcon :icon="feedGroup.icon" class="text-sm" />
                                        </span>

                                        <div>
                                            <h4 class="font-semibold text-gray-900">
                                                {{ feedGroup.title }}
                                            </h4>

                                            <p class="text-xs text-blue-600">
                                                {{ feedGroup.language }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div v-for="item in feedGroup.links" :key="item.key"
                                    class="border-b border-gray-100 p-4 last:border-b-0">
                                    <div class="space-y-3">
                                        <p class="text-sm font-semibold text-gray-900">
                                            {{ item.title }}
                                        </p>

                                        <p
                                            class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 font-mono text-xs text-gray-700 break-all">
                                            {{ item.text }}
                                        </p>

                                        <button type="button"
                                            class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700"
                                            @click="copyToClipboard(item)">
                                            <FontAwesomeIcon :icon="copiedKey === item.key ? faCheck : faCopy"
                                                class="text-xs" />

                                            <span>
                                                {{
                                                    copiedKey === item.key
                                                        ? t('common.labels.copied')
                                                        : t('common.labels.copy')
                                                }}
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else-if="activeTabKey === 'robots_txt'" class="space-y-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-950">
                            {{ t('admin.settings.index.tabs.robotsTxt') }}
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            {{ t('admin.settings.index.labels.robotsTxtEditDescription') }}
                        </p>
                    </div>

                    <a :href="robotsTxtAction.url"
                        class="group inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700">
                        <FontAwesomeIcon :icon="robotsTxtAction.icon" class="text-xs" />

                        <span>
                            {{ t('common.actions.edit') }}
                        </span>
                    </a>
                </div>

                <div v-else-if="activeTabKey === 'ads_txt'" class="space-y-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-950">
                            {{ t('admin.settings.index.tabs.adsTxt') }}
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            {{ t('admin.settings.index.labels.adsTxtEditDescription') }}
                        </p>
                    </div>

                    <a :href="adsTxtAction.url"
                        class="group inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700">
                        <FontAwesomeIcon :icon="adsTxtAction.icon" class="text-xs" />

                        <span>
                            {{ t('common.actions.edit') }}
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
