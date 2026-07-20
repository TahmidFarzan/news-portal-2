<script setup>
import { computed, ref, watch, onMounted } from 'vue'

import { useTranslate } from '@/composables/useTranslate'
import { fetchFromApi } from '@/composables/useApiClient'
import { apiCacheKey, apiCacheTTL } from '@/composables/useApiCache'

import GridCard from '@/components/common/news/GridCard.vue'
import ListCard from '@/components/common/news/ListCard.vue'
import CategoryHasLocationSection from '@/components/common/page/CategoryHasLocationSection.vue'

const { t } = useTranslate()

const {
    categorySlug,
    currentLanguage,
    style,
    limit,
} = defineProps({
    categorySlug: {
        type: [String],
        required: true,
    },
    currentLanguage: {
        type: Object,
        required: true,
    },
    style: {
        type: [String, Number],
        default: 1,
    },
    limit: {
        type: [String, Number],
        default: 4,
    },

})

const loading = ref(false)
const category = ref(null)
const newsItems = ref([])

const sectionStyle = computed(() => Number(style || 1))

const sectionTitle = computed(() => {
    return category.value?.name ?? ''
})

const firstNews = computed(() => newsItems.value[0] ?? null)
const remainingNews = computed(() => newsItems.value.slice(1))
const firstTwoNews = computed(() => newsItems.value.slice(0, 2))
const afterTwoNews = computed(() => newsItems.value.slice(2))

const normalizeResponseData = (response) => {
    if (Array.isArray(response)) return response
    if (Array.isArray(response?.data)) return response.data
    if (Array.isArray(response?.records)) return response.records
    return []
}

const getHomeCategoryApiUrl = () => {
    return currentLanguage?.is_default
        ? route('home.category', {
            slug: categorySlug,
        })
        : route('localized.home.category', {
            languageCode: currentLanguage?.code,
            slug: categorySlug,
        });
}

const getHomeCategoryNewsApiUrl = () => {
    return currentLanguage?.is_default
        ? route('home.category-news', {
            slug: categorySlug,
            limit,
        })
        : route('localized.home.category-news', {
            languageCode: currentLanguage?.code,
            slug: categorySlug,
            limit,
        });
}

const loadCategorySection = async () => {
    if (!categorySlug || !currentLanguage) return

    loading.value = true

    try {
        const categoryApiUrl = getHomeCategoryApiUrl()

        const categoryResponse = await fetchFromApi(
            categoryApiUrl,
            {},
            {
                key: `${apiCacheKey.API_HOME_PAGE}:${categoryApiUrl}`,
                ttl: apiCacheTTL.HOME_PAGE,
            }
        )

        category.value = categoryResponse

        const newsApiUrl = getHomeCategoryNewsApiUrl()

        const newsResponse = await fetchFromApi(
            newsApiUrl,
            {},
            {
                key: `${apiCacheKey.API_HOME_PAGE}:${newsApiUrl}`,
                ttl: apiCacheTTL.HOME_PAGE,
            }
        )

        newsItems.value = normalizeResponseData(newsResponse)
    } catch (error) {
        category.value = null
        newsItems.value = []
    } finally {
        loading.value = false
    }
}

onMounted(loadCategorySection)

watch(
    () => [
        categorySlug,
        currentLanguage?.code,
        currentLanguage?.is_default
    ],
    loadCategorySection,
)
</script>

<template>
    <section v-if="loading || newsItems.length || category" class="category-section mt-4">
        <div v-if="category"
            class="category-heading mb-3 flex items-center justify-between border-b border-slate-100 pb-2">
            <h2 class="text-xl font-bold text-gray-950">
                {{ sectionTitle }}
            </h2>
        </div>

        <div v-if="loading" class="category-loading rounded-2xl border border-slate-100 p-4 text-sm text-gray-500">
            {{ t("common.labels.loading") }}
        </div>

        <template v-else>
            <CategoryHasLocationSection v-if="category" :category="category" :isOnSidebar="false"
                :currentLanguage="currentLanguage" class="mb-3" />

            <div v-if="sectionStyle === 1">
                <div class="hidden gap-3 lg:grid lg:grid-cols-4">
                    <GridCard v-for="(perNews, index) in newsItems" :key="perNews?.id || perNews?.slug || index"
                        :news="perNews" :hideCategory="true" :hideEvent="true" :hideLocation="true" :hideBrief="true"
                        :isCompact="false" :useFullHeight="true" />
                </div>

                <div class="hidden md:block lg:hidden">
                    <div class="grid grid-cols-2 gap-3">
                        <GridCard v-for="(perNews, index) in firstTwoNews" :key="perNews?.id || perNews?.slug || index"
                            :news="perNews" :hideCategory="true" :hideEvent="true" :hideLocation="true"
                            :hideBrief="true" :isCompact="false" :useFullHeight="true" />
                    </div>

                    <div v-if="afterTwoNews.length" class="grid grid-cols-1 gap-3">
                        <ListCard v-for="(perNews, index) in afterTwoNews" :key="perNews?.id || perNews?.slug || index"
                            :news="perNews" :hideSubtitle="true" :hideBrief="true" :hideCategory="true"
                            :hideEvent="true" :hideLocation="true" :hideFeatureImage="false" :isCompact="true" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 md:hidden">
                    <GridCard v-if="firstNews" :news="firstNews" :hideCategory="true" :hideEvent="true"
                        :hideLocation="true" :hideBrief="true" :isCompact="false" :useFullHeight="true" />

                    <ListCard v-for="(perNews, index) in remainingNews" :key="perNews?.id || perNews?.slug || index"
                        :news="perNews" :hideSubtitle="true" :hideBrief="true" :hideCategory="true" :hideEvent="true"
                        :hideLocation="true" :hideFeatureImage="false" :isCompact="true" />
                </div>
            </div>

            <div v-else-if="sectionStyle === 2" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <GridCard v-if="firstNews" :news="firstNews" :hideCategory="true" :hideEvent="true" :hideLocation="true"
                    :hideBrief="true" :isCompact="false" :useFullHeight="true" />

                <div class="grid grid-cols-1 gap-3">
                    <ListCard v-for="(perNews, index) in remainingNews" :key="perNews?.id || perNews?.slug || index"
                        :news="perNews" :hideSubtitle="true" :hideBrief="true" :hideCategory="true" :hideEvent="true"
                        :hideLocation="true" :hideFeatureImage="false" :isCompact="true" />
                </div>
            </div>

            <div v-else-if="sectionStyle === 3" class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                <div class="lg:col-span-5">
                    <GridCard v-if="firstNews" :news="firstNews" :hideCategory="true" :hideEvent="true"
                        :hideLocation="true" :hideBrief="true" :isCompact="false" :useFullHeight="true" />
                </div>

                <div class="grid grid-cols-1 gap-3 lg:col-span-7">
                    <ListCard v-for="(perNews, index) in remainingNews" :key="perNews?.id || perNews?.slug || index"
                        :news="perNews" :hideSubtitle="true" :hideBrief="true" :hideCategory="true" :hideEvent="true"
                        :hideLocation="true" :hideFeatureImage="false" :isCompact="true" />
                </div>
            </div>

            <div v-else-if="sectionStyle === 4" class="grid grid-cols-1 gap-3">
                <GridCard v-if="firstNews" :news="firstNews" :hideCategory="true" :hideEvent="true" :hideLocation="true"
                    :hideBrief="true" :isCompact="false" :useFullHeight="true" />

                <ListCard v-for="(perNews, index) in remainingNews" :key="perNews?.id || perNews?.slug || index"
                    :news="perNews" :hideSubtitle="true" :hideBrief="true" :hideCategory="true" :hideEvent="true"
                    :hideLocation="true" :hideFeatureImage="true" :isCompact="true" />
            </div>
        </template>
    </section>
</template>

<style scoped>
.category-section {
    border-radius: var(--news-radius);
    background: transparent;
}

.category-heading {
    position: relative;
    border-color: var(--news-border);
}

.category-heading::before {
    content: '';
    width: 0.35rem;
    height: 1.6rem;
    border-radius: 999px;
    background: var(--news-primary);
}

.category-heading h2 {
    flex: 1;
    margin-inline-start: 0.65rem;
    letter-spacing: 0;
}

.category-loading {
    border-color: var(--news-border);
    background: var(--news-surface);
}
</style>
