<script setup>
import { reactive, computed, onMounted, watch } from 'vue'

import FooterMenuItem from '@/components/common/layout/public-layout/FooterMenuItem.vue'
import { fetchFromApi } from '@/composables/useApiClient'
import { apiCacheKey, apiCacheTTL } from '@/composables/useApiCache'
import { useTranslate } from '@/composables/useTranslate'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faSpinner } from '@fortawesome/free-solid-svg-icons'

const { t } = useTranslate()

const {
    isDefaultLanguage = false,
    currentLanguage,
} = defineProps({
    isDefaultLanguage: {
        type: Boolean,
        default: false,
    },
    currentLanguage: {
        type: Object,
        required: true,
    },
})

const footerMenu = reactive({
    items: [],
    loading: false,
    loaded: false,
    error: null,
    page: 1,
    lastPage: 1,
})

const isInitialLoading = computed(() => {
    return footerMenu.loading && footerMenu.items.length === 0
})

const hasMorePages = computed(() => {
    return footerMenu.page < footerMenu.lastPage
})

const normalizeMenuItems = (items = []) => {
    if (!Array.isArray(items)) {
        return []
    }

    return items.map((item) => ({
        ...item,
        children: Array.isArray(item?.children)
            ? item.children
            : [],
    }))
}

const mergeUniqueMenuItems = (currentItems = [], newItems = []) => {
    const itemsMap = new Map()

        ;[...currentItems, ...newItems].forEach((item) => {
            if (!item) {
                return
            }

            const key =
                item.id ??
                item.url ??
                item.slug ??
                item.name ??
                JSON.stringify(item)

            itemsMap.set(key, item)
        })

    return Array.from(itemsMap.values())
}

const getFooterMenuApiUrl = (pageNumber = 1) => {
    if (isDefaultLanguage) {
        return route('site.menus.footer-menu-items', {
            page: pageNumber,
        })
    }

    const languageCode = currentLanguage?.code

    if (!languageCode) {
        throw new Error('Current language code is required.')
    }

    return route('localized.site.menus.footer-menu-items', {
        languageCode: languageCode,
        page: pageNumber,
    })
}

const normalizeApiResponse = (response, requestedPage = 1) => {
    const paginator =
        response?.data &&
            !Array.isArray(response.data) &&
            (
                Array.isArray(response.data.data) ||
                Array.isArray(response.data.items)
            )
            ? response.data
            : response

    const rawItems =
        paginator?.items ??
        paginator?.data ??
        []

    return {
        items: normalizeMenuItems(
            Array.isArray(rawItems) ? rawItems : []
        ),
        currentPage: Number(
            paginator?.current_page ??
            paginator?.currentPage ??
            requestedPage
        ),
        lastPage: Number(
            paginator?.last_page ??
            paginator?.lastPage ??
            requestedPage
        ),
    }
}

const getFooterMenuItems = async (pageNumber = 1, forceReset = false) => {
    const requestedPage = Number(pageNumber)

    if (
        footerMenu.loading ||
        !Number.isFinite(requestedPage) ||
        requestedPage < 1
    ) {
        return
    }

    if (
        !forceReset &&
        footerMenu.loaded &&
        requestedPage > footerMenu.lastPage
    ) {
        return
    }

    try {
        footerMenu.loading = true
        footerMenu.error = null

        if (forceReset || requestedPage === 1) {
            footerMenu.page = 1
            footerMenu.lastPage = 1

            if (forceReset) {
                footerMenu.items = []
                footerMenu.loaded = false
            }
        }

        const apiUrl = getFooterMenuApiUrl(requestedPage)

        const response = await fetchFromApi(
            apiUrl,
            {},
            {
                key: `${apiCacheKey.API_LAYOUT_FOOTER_MENU}:${apiUrl}`,
                ttl: apiCacheTTL.LAYOUT_FOOTER_MENU,
            }
        )

        const {
            items,
            currentPage,
            lastPage,
        } = normalizeApiResponse(response, requestedPage)

        if (requestedPage === 1 || forceReset) {
            footerMenu.items = items
        } else {
            footerMenu.items = mergeUniqueMenuItems(
                footerMenu.items,
                items
            )
        }

        footerMenu.page = Math.max(1, currentPage)
        footerMenu.lastPage = Math.max(
            footerMenu.page,
            lastPage
        )
    } catch (error) {
        footerMenu.error =
            error instanceof Error
                ? error.message
                : 'Failed to load footer menu.'

        console.error('Failed to fetch footer menu:', error)
    } finally {
        footerMenu.loading = false
        footerMenu.loaded = true
    }
}

const loadMoreFooterMenuItems = async () => {
    if (
        footerMenu.loading ||
        !hasMorePages.value
    ) {
        return
    }

    await getFooterMenuItems(
        footerMenu.page + 1
    )
}

onMounted(() => {
    getFooterMenuItems(1)
})

watch(
    () => [
        isDefaultLanguage,
        currentLanguage?.code,
    ],
    ([newIsDefault, newCode], [oldIsDefault, oldCode]) => {
        if (
            newIsDefault === oldIsDefault &&
            newCode === oldCode
        ) {
            return
        }

        if (
            !newIsDefault &&
            !newCode
        ) {
            return
        }

        getFooterMenuItems(1, true)
    }
)
</script>

<template>
    <nav class="w-full min-w-0 min-h-6 md:w-auto md:flex-1" aria-label="Footer menu"
        :aria-busy="footerMenu.loading ? 'true' : 'false'">
        <ul class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1 text-center">
            <template v-if="footerMenu.items.length">
                <FooterMenuItem v-for="item in footerMenu.items" :key="item.id ??
                    item.url ??
                    item.slug ??
                    item.name
                    " :item="item" />
            </template>

            <template v-else-if="isInitialLoading">
                <li v-for="index in 4" :key="`footer-menu-skeleton-${index}`"
                    class="h-4 w-16 animate-pulse rounded bg-gray-200" aria-hidden="true" />
            </template>
        </ul>

        <div v-if="footerMenu.loading && footerMenu.items.length"
            class="mt-2 flex items-center justify-center gap-2 text-xs text-gray-400" role="status" aria-live="polite">
            <FontAwesomeIcon :icon="faSpinner" spin class="text-base text-blue-500" />

            <span>
                {{ t('common.labels.loading') }}
            </span>
        </div>
    </nav>
</template>
