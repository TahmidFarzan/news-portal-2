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

import { useTranslate } from '@/composables/useTranslate'

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

const tabs = computed(() => [
    {
        key: 'queue',
        label: t('pages.back_office.settings.index.tabs.queue'),
        icon: faClock,
    },
    {
        key: 'schedule',
        label: t('pages.back_office.settings.index.tabs.schedule'),
        icon: faCalendarDays,
    },
    {
        key: 'sitemap',
        label: t('pages.back_office.settings.index.tabs.sitemap'),
        icon: faSitemap,
    },
    {
        key: 'feeds_rss',
        label: t('pages.back_office.settings.index.tabs.feeds_rss'),
        icon: faRss,
    },
    {
        key: 'robots_txt',
        label: t('pages.back_office.settings.index.tabs.robots_txt'),
        icon: faGlobe,
    },
    {
        key: 'ads_txt',
        label: t('pages.back_office.settings.index.tabs.ads_txt'),
        icon: faFileCode,
    },
])

const activeTabKey = computed(() => {
    return activeTab.value ?? tabs.value[0]?.key ?? null
})

const queueActions = computed(() => [
    {
        key: 'queue_start',
        title: t('pages.back_office.settings.index.queue_actions.start_queue'),
        url: route('back-office.settings.queue.start'),
        icon: faPlay,
    },
    {
        key: 'queue_restart',
        title: t('pages.back_office.settings.index.queue_actions.restart_queue'),
        url: route('back-office.settings.queue.restart'),
        icon: faRotateRight,
    },
    {
        key: 'queue_clear',
        title: t('pages.back_office.settings.index.queue_actions.clear_queue'),
        url: route('back-office.settings.queue.clear'),
        icon: faBroom,
    },
    {
        key: 'queue_flush',
        title: t('pages.back_office.settings.index.queue_actions.flush_queue'),
        url: route('back-office.settings.queue.flush'),
        icon: faTrashCan,
    },
    {
        key: 'queue_monitor_stale',
        title: t('pages.back_office.settings.index.queue_actions.monitor_stale'),
        url: route('back-office.settings.queue.monitor.stale'),
        icon: faTriangleExclamation,
    },
    {
        key: 'queue_monitor_purge',
        title: t('pages.back_office.settings.index.queue_actions.monitor_purge'),
        url: route('back-office.settings.queue.monitor.purge'),
        icon: faShieldHalved,
    },
])

const scheduleActions = computed(() => [
    {
        key: 'schedule_start',
        title: t('pages.back_office.settings.index.schedule_actions.start_schedule'),
        url: route('back-office.settings.schedule.start'),
        icon: faPlay,
    },
    {
        key: 'schedule_stop',
        title: t('pages.back_office.settings.index.schedule_actions.stop_schedule'),
        url: route('back-office.settings.schedule.stop'),
        icon: faStop,
    },
])

const robotsTxtAction = computed(() => ({
    key: 'robots_txt_edit',
    title: t('pages.back_office.settings.index.labels.edit_robots_txt'),
    url: route('back-office.settings.robots-txt.edit'),
    icon: faPenToSquare,
}))

const adsTxtAction = computed(() => ({
    key: 'ads_txt_edit',
    title: t('pages.back_office.settings.index.labels.edit_ads_txt'),
    url: route('back-office.settings.ads-txt.edit'),
    icon: faPenToSquare,
}))

const sitemapLinks = computed(() => [
    {
        key: 'sitemaps_index',
        title: t('pages.back_office.settings.index.sitemap_links.sitemap_index'),
        text: `${appUrl}/sitemaps.xml`,
        copyable: true,
    },
    {
        key: 'sitemaps_categories',
        title: t('pages.back_office.settings.index.sitemap_links.categories_sitemap'),
        text: `${appUrl}/sitemaps/categories.xml`,
        copyable: true,
    },
    {
        key: 'sitemaps_tags',
        title: t('pages.back_office.settings.index.sitemap_links.tags_sitemap'),
        text: `${appUrl}/sitemaps/tags.xml`,
        copyable: true,
    },
    {
        key: 'sitemaps_events',
        title: t('pages.back_office.settings.index.sitemap_links.events_sitemap'),
        text: `${appUrl}/sitemaps/events.xml`,
        copyable: true,
    },
    {
        key: 'sitemaps_contributors',
        title: t('pages.back_office.settings.index.sitemap_links.contributors_sitemap'),
        text: `${appUrl}/sitemaps/contributors.xml`,
        copyable: true,
    },
    {
        key: 'sitemaps_news',
        title: t('pages.back_office.settings.index.sitemap_links.news_sitemap'),
        text: `${appUrl}/sitemaps/news.xml`,
        copyable: true,
    },
    {
        key: 'sitemaps_latest_news',
        title: t('pages.back_office.settings.index.sitemap_links.latest_news_sitemap'),
        text: `${appUrl}/sitemaps/latest-news.xml`,
        copyable: true,
    },
    {
        key: 'dynamic_categories_news',
        title: t('pages.back_office.settings.index.sitemap_links.category_news_pattern'),
        text: `${appUrl}/categories/{slugTree}/news.xml`,
        copyable: true,
    },
    {
        key: 'dynamic_locations_news',
        title: t('pages.back_office.settings.index.sitemap_links.location_news_pattern'),
        text: `${appUrl}/locations/{slugTree}/news.xml`,
        copyable: true,
    },
    {
        key: 'dynamic_events_news',
        title: t('pages.back_office.settings.index.sitemap_links.event_news_pattern'),
        text: `${appUrl}/events/{slug}/news.xml`,
        copyable: true,
    },
    {
        key: 'dynamic_tags_news',
        title: t('pages.back_office.settings.index.sitemap_links.tag_news_pattern'),
        text: `${appUrl}/tags/{slug}/news.xml`,
        copyable: true,
    },
    {
        key: 'dynamic_contributors_news',
        title: t('pages.back_office.settings.index.sitemap_links.contributor_news_pattern'),
        text: `${appUrl}/contributors/{slug}/news.xml`,
        copyable: true,
    },
])

const feedTypes = computed(() => [
    {
        key: 'rss',
        title: t('pages.back_office.settings.index.feed_links.rss_feeds'),
        icon: faRss,
    },
    {
        key: 'atom',
        title: t('pages.back_office.settings.index.feed_links.atom_feeds'),
        icon: faAtom,
    },
])

const feedLinkPatterns = computed(() => [
    {
        key: 'news',
        title: t('pages.back_office.settings.index.feed_links.news_feed_pattern'),
        text: `${appUrl}/feeds/{type}/news.xml`,
    },
    {
        key: 'latest_news',
        title: t('pages.back_office.settings.index.feed_links.latest_news_feed_pattern'),
        text: `${appUrl}/feeds/{type}/latest-news.xml`,
    },
    {
        key: 'category_news',
        title: t('pages.back_office.settings.index.feed_links.category_news_feed_pattern'),
        text: `${appUrl}/feeds/{type}/categories/{slugTree}/news.xml`,
    },
    {
        key: 'location_news',
        title: t('pages.back_office.settings.index.feed_links.location_news_feed_pattern'),
        text: `${appUrl}/feeds/{type}/locations/{slugTree}/news.xml`,
    },
    {
        key: 'event_news',
        title: t('pages.back_office.settings.index.feed_links.event_news_feed_pattern'),
        text: `${appUrl}/feeds/{type}/events/{slug}/news.xml`,
    },
    {
        key: 'tag_news',
        title: t('pages.back_office.settings.index.feed_links.tag_news_feed_pattern'),
        text: `${appUrl}/feeds/{type}/tags/{slug}/news.xml`,
    },
    {
        key: 'contributor_news',
        title: t('pages.back_office.settings.index.feed_links.contributor_news_feed_pattern'),
        text: `${appUrl}/feeds/{type}/contributors/{slug}/news.xml`,
    },
])

const feedGroups = computed(() => {
    return feedTypes.value.map((feedType) => ({
        ...feedType,
        links: feedLinkPatterns.value.map((item) => ({
            key: `${feedType.key}_${item.key}`,
            title: item.title,
            text: item.text.replace('{type}', feedType.key),
            copyable: true,
        })),
    }))
})

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

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                {
                    text: t('pages.back_office.settings.index.labels.setting'),
                    active: true,
                },
            ],
        }),
    )
})
</script>

<template>

    <Head :title="t('pages.back_office.settings.index.labels.setting')" />

    <div class="w-full space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-950">
                {{ t('pages.back_office.settings.index.labels.setting') }}
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
                            {{ t('pages.back_office.settings.index.labels.queue_actions') }}
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            {{ t('pages.back_office.settings.index.labels.queue_actions_description') }}
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
                                {{ t('pages.back_office.settings.index.labels.run') }}
                            </span>
                        </a>
                    </div>
                </div>

                <div v-else-if="activeTabKey === 'schedule'" class="space-y-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-950">
                            {{ t('pages.back_office.settings.index.labels.schedule_actions') }}
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            {{ t('pages.back_office.settings.index.labels.schedule_actions_description') }}
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
                                {{ t('pages.back_office.settings.index.labels.run') }}
                            </span>
                        </a>
                    </div>
                </div>

                <div v-else-if="activeTabKey === 'sitemap'" class="space-y-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-950">
                            {{ t('pages.back_office.settings.index.labels.sitemap_links') }}
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            {{ t('pages.back_office.settings.index.labels.click_copy_sitemap_url') }}
                        </p>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-gray-200">
                        <div v-for="item in sitemapLinks" :key="item.key"
                            class="grid grid-cols-1 gap-3 border-b border-gray-100 p-4 last:border-b-0 md:grid-cols-12 md:items-center"
                            :class="item.copyable ? 'bg-white' : 'bg-gray-50'">
                            <div class="md:col-span-4">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg" :class="item.copyable
                                            ? 'bg-red-50 text-red-600'
                                            : 'bg-gray-100 text-gray-400'
                                        ">
                                        <FontAwesomeIcon :icon="faSitemap" class="text-xs" />
                                    </span>

                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-950">
                                            {{ item.title }}
                                        </p>

                                        <p class="mt-0.5 text-xs"
                                            :class="item.copyable ? 'text-green-600' : 'text-gray-400'">
                                            {{
                                                item.copyable
                                                    ? t('pages.back_office.settings.index.labels.copy_enabled')
                                                    : t('pages.back_office.settings.index.labels.pattern_only')
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="min-w-0 md:col-span-6">
                                <p class="truncate rounded-lg border px-3 py-2 font-mono text-xs" :class="item.copyable
                                        ? 'border-gray-200 bg-gray-50 text-gray-700'
                                        : 'border-gray-100 bg-white text-gray-400'
                                    ">
                                    {{ item.text }}
                                </p>
                            </div>

                            <div class="flex justify-start md:col-span-2 md:justify-end">
                                <button type="button"
                                    class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition"
                                    :class="item.copyable
                                            ? 'bg-red-600 text-white hover:bg-red-700'
                                            : 'cursor-not-allowed bg-gray-100 text-gray-400'
                                        " :disabled="!item.copyable" @click="copyToClipboard(item)">
                                    <FontAwesomeIcon :icon="copiedKey === item.key ? faCheck : faCopy"
                                        class="text-xs" />

                                    <span>
                                        {{
                                            copiedKey === item.key
                                                ? t('pages.back_office.settings.index.labels.copied')
                                                : t('pages.back_office.settings.index.labels.copy')
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
                            {{ t('pages.back_office.settings.index.labels.feeds_rss_links') }}
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            {{ t('pages.back_office.settings.index.labels.feeds_rss_patterns_are_dynamic') }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <div v-for="feedGroup in feedGroups" :key="feedGroup.key"
                            class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                            <div class="flex items-center justify-between border-b border-gray-100 p-4">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                        <FontAwesomeIcon :icon="feedGroup.icon" class="text-sm" />
                                    </span>

                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-950">
                                            {{ feedGroup.title }}
                                        </h4>

                                        <p class="text-xs text-green-600">
                                            {{ t('pages.back_office.settings.index.labels.copy_enabled') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div v-for="item in feedGroup.links" :key="item.key"
                                    class="grid grid-cols-1 gap-3 border-b border-gray-100 p-4 last:border-b-0">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-950">
                                            {{ item.title }}
                                        </p>

                                        <p
                                            class="mt-2 truncate rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 font-mono text-xs text-gray-700">
                                            {{ item.text }}
                                        </p>
                                    </div>

                                    <div>
                                        <button type="button"
                                            class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition"
                                            :class="item.copyable
                                                    ? 'bg-red-600 text-white hover:bg-red-700'
                                                    : 'cursor-not-allowed bg-gray-100 text-gray-400'
                                                " :disabled="!item.copyable" @click="copyToClipboard(item)">
                                            <FontAwesomeIcon :icon="copiedKey === item.key ? faCheck : faCopy"
                                                class="text-xs" />

                                            <span>
                                                {{
                                                    copiedKey === item.key
                                                        ? t('pages.back_office.settings.index.labels.copied')
                                                        : t('pages.back_office.settings.index.labels.copy')
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
                            {{ t('pages.back_office.settings.index.tabs.robots_txt') }}
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            {{ t('pages.back_office.settings.index.labels.robots_txt_edit_description') }}
                        </p>
                    </div>

                    <a :href="robotsTxtAction.url"
                        class="group inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700">
                        <FontAwesomeIcon :icon="robotsTxtAction.icon" class="text-xs" />

                        <span>
                            {{ t('pages.back_office.settings.index.labels.edit') }}
                        </span>
                    </a>
                </div>

                <div v-else-if="activeTabKey === 'ads_txt'" class="space-y-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-950">
                            {{ t('pages.back_office.settings.index.tabs.ads_txt') }}
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            {{ t('pages.back_office.settings.index.labels.ads_txt_edit_description') }}
                        </p>
                    </div>

                    <a :href="adsTxtAction.url"
                        class="group inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700">
                        <FontAwesomeIcon :icon="adsTxtAction.icon" class="text-xs" />

                        <span>
                            {{ t('pages.back_office.settings.index.labels.edit') }}
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
