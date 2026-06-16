<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import RecentActivities from '@/components/back-office/activity-log/RecentModelActivityLogs.vue'
import MediaRenderer from '@/components/common/media/MediaRenderer.vue'
import NewsImageGalleryGrid from '@/components/back-office/news/NewsImageGalleryGrid.vue'
import NewsPlacementList from '@/components/back-office/news/NewsPlacementList.vue'
import RelatedOrRelevantNewsList from '@/components/back-office/news/RelatedOrRelevantNewsList.vue'

import { ref, onMounted, nextTick, inject, computed } from 'vue'
import { Head, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faTrash, faPen, faEye, faSpinner, faFire } from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'
import { canEditNews, canDeleteNews, canRestoreNews } from '@/composables/useAuthUserAccessPermissions'
import { isStory as checkIsStory, isVideo as checkIsVideo, isImageGallery as checkIsImageGallery } from '@/composables/useNews'
import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faTrash, faPen, faEye, faSpinner, faFire)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const authUser = inject('authUser')

const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const showRestoreModal = ref(false)
const restoreProcessing = ref(false)

const { news = {} } = defineProps({
    news: {
        type: Object,
        default: () => ({}),
    },
})

const notAvailable = computed(() => t('pages.back_office.news.details.labels.not_available'))

const pageTitle = computed(() => `${news?.title} ${t('pages.back_office.news.details.labels.details')}`)

const canEdit = news => canEditNews(authUser?.value, news)
const canDelete = news => canDeleteNews(authUser?.value, news)
const canRestore = news => canRestoreNews(authUser?.value, news)

const handleDelete = () => {
    if (deleteProcessing.value) return

    deleteProcessing.value = true

    intertiaJsRoute.patch(route('back-office.news.delete', { slug: news?.slug }), {
        onFinish: () => deleteProcessing.value = false,
    })
}

const handleRestore = () => {
    if (restoreProcessing.value) return

    restoreProcessing.value = true

    intertiaJsRoute.patch(route('back-office.news.restore', { slug: news?.slug }), {
        onFinish: () => restoreProcessing.value = false,
    })
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: t('pages.back_office.news.details.labels.news'), href: route('back-office.news.index') },
                { text: pageTitle.value, active: true },
            ],
        })
    )
})
</script>

<template>

    <Head :title="pageTitle" />

    <div class="w-full space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">
                {{ t('pages.back_office.news.details.page_title') }}
            </h2>

            <div class="flex gap-2">
                <a v-if="canEdit(news)" :href="route('back-office.news.edit', { slug: news?.slug })"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="pen" />
                    {{ t('pages.back_office.news.details.actions.edit') }}
                </a>

                <button v-if="canDelete(news)" @click="showDeleteModal = true"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="trash" />
                    {{ t('pages.back_office.news.details.actions.delete') }}
                </button>

                <button v-if="canRestore(news)" @click="showRestoreModal = true"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="eye" />
                    {{ t('pages.back_office.news.details.actions.restore') }}
                </button>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('pages.back_office.news.details.labels.basic_information') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.news.details.labels.news_type') }}</span>
                        <span class="font-medium">{{ news?.news_type?.name || notAvailable }}</span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.news.details.labels.language') }}</span>
                        <span class="font-medium">{{ news?.language?.name || notAvailable }}</span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.news.details.form.category') }}</span>
                        <span class="font-medium">{{ news?.category?.name || notAvailable }}</span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.news.details.form.location') }}</span>
                        <span class="font-medium">{{ news?.location?.name || notAvailable }}</span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.news.details.form.event') }}</span>
                        <span class="font-medium">{{ news?.event?.name || notAvailable }}</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.news.details.labels.title') }}</span>
                        <span class="font-medium">{{ news?.title || notAvailable }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.news.details.form.sub_title') }}</span>
                        <span class="font-medium">{{ news?.sub_title || notAvailable }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.news.details.form.content_shoulder') }}</span>
                        <span class="font-medium">{{ news?.content_shoulder || notAvailable }}</span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div>
                        <div class="text-gray-500 mb-1">{{ t('pages.back_office.news.details.form.brief') }}</div>
                        <div class="text-gray-700">{{ news?.brief || notAvailable }}</div>
                    </div>
                </div>
            </div>

            <div v-if="checkIsStory(news?.news_type)" class="grid grid-cols-1 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div>
                        <div class="text-gray-500 mb-1">{{ t('pages.back_office.news.details.form.body') }}</div>
                        <div class="text-gray-700">
                            <div v-html="news?.body || notAvailable"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="checkIsVideo(news?.news_type)" class="grid grid-cols-1 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div>
                        <div class="text-gray-500 mb-1">{{ t('pages.back_office.news.details.form.video_url') }}</div>
                        <div class="text-gray-700">
                            {{ news?.video_url || notAvailable }}
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="checkIsImageGallery(news?.news_type)" class="grid grid-cols-1 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-3">
                    <NewsImageGalleryGrid :news="news" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.news.details.form.tags') }}</span>

                        <div class="flex flex-wrap gap-2">
                            <template v-if="news?.tags && news.tags.length">
                                <span v-for="tag in news.tags" :key="tag.id ?? tag.name"
                                    class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 border border-gray-200">
                                    {{ tag?.name }}
                                    <FontAwesomeIcon v-if="tag?.trend" icon="fire" />
                                </span>
                            </template>

                            <span v-else class="text-sm text-gray-400 italic">
                                {{ t('pages.back_office.news.details.no_tag_added') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div v-if="checkIsStory(news?.news_type)" class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.news.details.labels.contributors') }}</span>

                        <div class="flex flex-wrap gap-2">
                            <template v-if="news?.contributors && news.contributors.length">
                                <span v-for="contributor in news.contributors" :key="contributor.id ?? contributor.name"
                                    class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 border border-gray-200">
                                    {{ contributor?.name }}
                                    <FontAwesomeIcon v-if="contributor?.trend" icon="fire" />
                                </span>
                            </template>

                            <span v-else class="text-sm text-gray-400 italic">
                                {{ t('pages.back_office.news.details.no_contributor_added') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div>
                        <div class="text-gray-500 mb-1">{{ t('pages.back_office.news.details.form.feature_image') }}</div>

                        <div class="text-gray-700 w-100">
                            <MediaRenderer v-if="news?.feature_image" :media="news?.feature_image" />

                            <img v-else-if="checkIsStory(news?.news_type)"
                                :src="'/uploads/images/news/story-feature-image.png'"
                                class="object-cover rounded-xl border border-gray-200" />

                            <img v-else-if="checkIsVideo(news?.news_type)"
                                :src="'/uploads/images/news/video-feature-image.png'"
                                class="object-cover rounded-xl border border-gray-200" />

                            <img v-else-if="checkIsImageGallery(news?.news_type)"
                                :src="'/uploads/images/news/image-gallery-feature-image.png'"
                                class="object-cover rounded-xl border border-gray-200" />

                            <img v-else :src="'/uploads/images/news/story-feature-image.png'"
                                class="object-cover rounded-xl border border-gray-200" />
                        </div>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div>
                        <div class="text-gray-500 mb-1">{{ t('pages.back_office.news.details.form.feature_image_mobile') }}</div>

                        <div class="text-gray-700 w-100">
                            <MediaRenderer v-if="news?.feature_image_mobile" :media="news?.feature_image_mobile" />

                            <img v-else-if="checkIsStory(news?.news_type)"
                                :src="'/uploads/images/news/story-feature-image-mobile.png'"
                                class="object-cover rounded-xl border border-gray-200" />

                            <img v-else-if="checkIsVideo(news?.news_type)"
                                :src="'/uploads/images/news/video-feature-image-mobile.png'"
                                class="object-cover rounded-xl border border-gray-200" />

                            <img v-else-if="checkIsImageGallery(news?.news_type)"
                                :src="'/uploads/images/news/image-gallery-feature-image-mobile.png'"
                                class="object-cover rounded-xl border border-gray-200" />

                            <img v-else :src="'/uploads/images/news/story-feature-image-mobile.png'"
                                class="object-cover rounded-xl border border-gray-200" />
                        </div>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="text-gray-500 mb-2">{{ t('pages.back_office.news.details.form.seo_settings') }}</div>

                    <div class="space-y-3 text-sm">
                        <div>
                            <div class="text-gray-500 mb-1">{{ t('pages.back_office.news.details.labels.title') }}</div>
                            <div class="font-medium text-gray-700">
                                {{ news?.seo_title || notAvailable }}
                            </div>
                        </div>

                        <div>
                            <div class="text-gray-500 mb-1">{{ t('pages.back_office.news.details.form.brief') }}</div>
                            <div class="font-medium text-gray-700">
                                {{ news?.seo_brief || notAvailable }}
                            </div>
                        </div>

                        <div>
                            <div class="text-gray-500 mb-1">{{ t('pages.back_office.news.details.form.seo_keywords') }}</div>
                            <div class="font-medium text-gray-700">
                                {{ news?.seo_keywords || notAvailable }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div v-if="checkIsStory(news?.news_type)" class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.news.details.labels.source') }}</span>
                        <span class="font-medium">{{ news?.source || notAvailable }}</span>
                    </div>
                </div>

                <div v-if="checkIsStory(news?.news_type)" class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.news.details.form.writer') }}</span>
                        <span class="font-medium">{{ news?.writer || notAvailable }}</span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.news.details.labels.published') }}</span>
                        <span class="font-medium">{{ news?.is_published ? t('pages.back_office.news.details.labels.yes') : t('pages.back_office.news.details.labels.no') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('pages.back_office.news.details.medias.details.system_information') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.news.details.created_at') }}</span>
                        <span class="font-medium">
                            {{ news?.created_at ? formatDateTime(news.created_at) : notAvailable }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.news.details.labels.created_by') }}</span>
                        <span class="font-medium">
                            {{ news?.created_by?.name || notAvailable }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.news.details.labels.updated_at') }}</span>
                        <span class="font-medium">
                            {{ news?.updated_at ? formatDateTime(news.updated_at) : notAvailable }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.news.details.labels.updated_by') }}</span>
                        <span class="font-medium">
                            {{ news?.latest_activity_log?.causer?.name || notAvailable }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="news?.breaking_news" class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('pages.back_office.news.details.app.breaking_news') }}
            </h3>

            <div class="flex justify-between">
                <span class="text-gray-500">{{ t('pages.back_office.news.details.labels.title') }}</span>
                <span class="font-medium">{{ news?.breaking_news?.title || notAvailable }}</span>
            </div>

            <div class="flex justify-between">
                <span class="text-gray-500">{{ t('pages.back_office.news.details.labels.published') }}</span>
                <span class="font-medium">
                    {{ news?.breaking_news?.is_published ? t('pages.back_office.news.details.labels.yes') : t('pages.back_office.news.details.labels.no') }}
                </span>
            </div>

            <div class="flex justify-between">
                <span class="text-gray-500">{{ t('pages.back_office.news.details.created_at') }}</span>
                <span class="font-medium">
                    {{ news?.breaking_news?.created_at ? formatDateTime(news?.breaking_news?.created_at) : notAvailable
                    }}
                </span>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('pages.back_office.news.details.news_placements') }}
            </h3>

            <NewsPlacementList :news="news" :news-placements="news?.news_placements" />
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('pages.back_office.news.details.labels.relevant_news') }}
            </h3>

            <RelatedOrRelevantNewsList :news="news?.relevant_news" />
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('pages.back_office.news.details.labels.related_news') }}
            </h3>

            <RelatedOrRelevantNewsList :news="news?.related_news" />
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('pages.back_office.news.details.activity_logs.index.title') }}
            </h3>

            <RecentActivities :model-slug="'news'" :model="news" />
        </div>

        <Teleport to="body">
            <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showDeleteModal"
                    class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50">
                    <Transition enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-4">
                        <div v-if="showDeleteModal" class="bg-white rounded-xl shadow-lg w-[380px] p-6 space-y-4">
                            <h3 class="text-lg font-semibold text-red-600">
                                {{ t('pages.back_office.news.details.delete_modal.title') }}
                            </h3>

                            <p class="text-sm font-medium">
                                {{ news?.title }}
                            </p>

                            <p class="text-sm text-gray-500">
                                {{ t('pages.back_office.news.details.delete_modal.body') }}
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button @click="showDeleteModal = false"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    {{ t('pages.back_office.news.details.actions.cancel') }}
                                </button>

                                <button @click="handleDelete" :disabled="deleteProcessing"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2 disabled:opacity-60">
                                    <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />
                                    {{ deleteProcessing ? t('pages.back_office.news.details.actions.deleting') : t('pages.back_office.news.details.actions.delete') }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>

            <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showRestoreModal"
                    class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50">
                    <Transition enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-4">
                        <div v-if="showRestoreModal" class="bg-white rounded-xl shadow-lg w-[380px] p-6 space-y-4">
                            <h3 class="text-lg font-semibold text-green-600">
                                {{ t('pages.back_office.news.details.restore_modal.title') }}
                            </h3>

                            <p class="text-sm font-medium">
                                {{ news?.title }}
                            </p>

                            <p class="text-sm text-gray-500">
                                {{ t('pages.back_office.news.details.restore_modal.body') }}
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button @click="showRestoreModal = false"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    {{ t('pages.back_office.news.details.actions.cancel') }}
                                </button>

                                <button @click="handleRestore" :disabled="restoreProcessing"
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm flex items-center gap-2 disabled:opacity-60">
                                    <FontAwesomeIcon v-if="restoreProcessing" icon="spinner" spin />
                                    {{ restoreProcessing ? t('pages.back_office.news.details.actions.restoring') : t('pages.back_office.news.details.actions.restore') }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
