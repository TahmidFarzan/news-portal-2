<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import NewsPlacementSortableList from '@/components/back-office/news/NewsPlacementSortableList.vue'

import { Head, useForm, router } from '@inertiajs/vue3'
import { inject, onMounted, nextTick, ref, computed, watch } from 'vue'

defineOptions({ layout: Layout })

const pageReady = inject("pageReady")

const {
    news,
    homeLeadNewsPlacements,
    homeCategoryNewsPlacements,
    categoryLeadNewsPlacements
} = defineProps({
    news: Object,
    homeLeadNewsPlacements: { type: Array, default: () => [] },
    homeCategoryNewsPlacements: { type: Array, default: () => [] },
    categoryLeadNewsPlacements: { type: Array, default: () => [] },
})

const localHomeLeadNewsPlacements = ref([...homeLeadNewsPlacements])
const localHomeCategoryNewsPlacements = ref([...homeCategoryNewsPlacements])
const localCategoryLeadNewsPlacements = ref([...categoryLeadNewsPlacements])

const autoCreateProcessing = ref(false)

const saveForm = useForm({
    home_lead_news_ids_sequence: null,
    home_category_news_ids_sequence: null,
    category_lead_news_ids_sequence: null,
})

const shouldShowAutoCreate = computed(() => {
    return !localHomeLeadNewsPlacements.value.length ||
        !localHomeCategoryNewsPlacements.value.length ||
        !localCategoryLeadNewsPlacements.value.length
})

watch(
    () => [
        homeLeadNewsPlacements,
        homeCategoryNewsPlacements,
        categoryLeadNewsPlacements,
    ],
    () => {
        localHomeLeadNewsPlacements.value = [...homeLeadNewsPlacements]
        localHomeCategoryNewsPlacements.value = [...homeCategoryNewsPlacements]
        localCategoryLeadNewsPlacements.value = [...categoryLeadNewsPlacements]

        saveForm.home_lead_news_ids_sequence = null
        saveForm.home_category_news_ids_sequence = null
        saveForm.category_lead_news_ids_sequence = null
    },
    { deep: true }
)

function handleSequenceChange(fieldName, items) {
    saveForm[fieldName] = items.map(item => item.id)
}

function handleSave() {
    if (saveForm.processing) return

    saveForm.patch(
        route('back-office.newses.news-placements.update', {
            slug: news?.slug
        }),
        {
            preserveScroll: true,
            preserveState: true,

            onSuccess: () => {
                saveForm.reset()
                saveForm.clearErrors()
            },

            onError: (errors) => {
                saveForm.clearErrors()
                saveForm.setError(errors)
            }
        }
    )
}

function handleAutoCreate() {
    if (autoCreateProcessing.value) return

    autoCreateProcessing.value = true

    router.post(
        route('back-office.newses.news-placements.generate', {
            slug: news?.slug
        }),
        {},
        {
            preserveScroll: true,
            preserveState: false,

            only: [
                'homeLeadNewsPlacements',
                'homeCategoryNewsPlacements',
                'categoryLeadNewsPlacements',
                'flash'
            ],

            onFinish: () => {
                autoCreateProcessing.value = false
            }
        }
    )
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: 'Newses', href: route('back-office.newses.index') },
                { text: `${news?.title} news placement`, active: true }
            ],
        })
    )

    pageReady.value = true
})
</script>

<template>

    <Head :title="`${news?.title} news placement`" />

    <div class="w-full space-y-6">

        <div class="flex justify-end gap-3">

            <button v-if="shouldShowAutoCreate" type="button"
                class="inline-flex items-center px-5 py-2 rounded-lg text-sm font-medium transition bg-green-600 text-white hover:bg-green-700 disabled:opacity-60"
                :disabled="autoCreateProcessing" @click="handleAutoCreate">
                {{ autoCreateProcessing ? 'Auto Creating...' : 'Auto Create' }}
            </button>

            <button type="button"
                class="inline-flex items-center px-5 py-2 rounded-lg text-sm font-medium transition bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-60"
                :disabled="saveForm.processing" @click="handleSave">
                {{ saveForm.processing ? 'Saving...' : 'Save All' }}
            </button>

        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 items-start">

            <NewsPlacementSortableList title="Home Lead News Placements" v-model:items="localHomeLeadNewsPlacements"
                :news="news" field-name="home_lead_news_ids_sequence" @sequence-change="handleSequenceChange" />

            <NewsPlacementSortableList title="Home Category News Placements"
                v-model:items="localHomeCategoryNewsPlacements" :news="news"
                field-name="home_category_news_ids_sequence" @sequence-change="handleSequenceChange" />

            <NewsPlacementSortableList title="Category Lead News Placements"
                v-model:items="localCategoryLeadNewsPlacements" :news="news"
                field-name="category_lead_news_ids_sequence" @sequence-change="handleSequenceChange" />

        </div>

    </div>
</template>
