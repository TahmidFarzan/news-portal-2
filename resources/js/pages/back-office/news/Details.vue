<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import RecentActivities from '@/components/back-office/activity-log/RecentModelActivityLogs.vue'
import MediaRenderer from '@/components/common/media/MediaRenderer.vue'
import NewsImageGalleryGrid from '@/components/back-office/news/NewsImageGalleryGrid.vue'
import NewsPlacementList from '@/components/back-office/news/NewsPlacementList.vue'
import RelatedOrRelevantNewsList from '@/components/back-office/news/RelatedOrRelevantNewsList.vue'

import { ref, onMounted, nextTick, inject } from 'vue'
import { Head, useForm, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faTrash, faPen, faEye, faEyeSlash, faSpinner, faFire } from '@fortawesome/free-solid-svg-icons'

import { formatDate, formatDateTime } from '@/composables/useDateTime'
import { canEditNews, canDeleteNews, canRestoreNews } from '@/composables/useAuthUserAccessPermissions'
import { isStory as checkIsStory, isVideo as checkIsVideo, isImageGallery as checkIsImageGallery } from '@/composables/useNews'

FontAwesomeLibrary.add(faTrash, faPen, faEye, faEyeSlash, faSpinner, faFire)

defineOptions({ layout: Layout })

const authUser = inject("authUser")

const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const showRestoreModal = ref(false)
const restoreProcessing = ref(false)

const { news } = defineProps({
    news: Object,
})

const canEdit = (news) => canEditNews(authUser?.value, news)
const canDelete = (news) => canDeleteNews(authUser?.value, news)
const canRestore = (news) => canRestoreNews(authUser?.value, news)


const handleDelete = () => {
    if (deleteProcessing.value) return
    deleteProcessing.value = true

    intertiaJsRoute.patch(route('back-office.news.delete', { slug: news?.slug }), {
        onFinish: () => deleteProcessing.value = false
    })
}

const handleRestore = () => {
    if (restoreProcessing.value) return
    restoreProcessing.value = true

    intertiaJsRoute.patch(route('back-office.news.restore', { slug: news?.slug }), {
        onFinish: () => restoreProcessing.value = false
    })
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: 'News', href: route('back-office.news.index') },
                { text: `${news?.title} details`, active: true }
            ],
        })
    )
})
</script>

<template>

    <Head :title="`${news?.title} details`" />

    <div class="w-full space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">News Details</h2>

            <div class="flex gap-2">
                <a v-if="canEdit(news)" :href="route('back-office.news.edit', { slug: news?.slug })"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="pen" />
                    Edit
                </a>

                <button v-if="canDelete(news)" @click="showDeleteModal = true"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="trash" />
                    Delete
                </button>

                <button v-if="canRestore(news)" @click="showRestoreModal = true"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="eye" />
                    Restore
                </button>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">Basic Information</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">News type</span>
                        <span class="font-medium">{{ news?.news_type?.name || 'N/A' }}</span>
                    </div>

                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Language</span>
                        <span class="font-medium">{{ news?.language?.name || 'N/A' }}</span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Category</span>
                        <span class="font-medium">{{ news?.category?.name || 'N/A' }}</span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Location</span>
                        <span class="font-medium">{{ news?.location?.name || 'N/A' }}</span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Event</span>
                        <span class="font-medium">{{ news?.event?.name || 'N/A' }}</span>
                    </div>
                </div>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Title</span>
                        <span class="font-medium">{{ news?.title || 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Sub title</span>
                        <span class="font-medium">{{ news?.sub_title || 'N/A' }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Content shoulder</span>
                        <span class="font-medium">{{ news?.content_shoulder || 'N/A' }}</span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div>
                        <div class="text-gray-500 mb-1">Brief</div>
                        <div class="text-gray-700">{{ news?.brief || 'N/A' }}</div>
                    </div>
                </div>
            </div>

            <div v-if="checkIsStory(news.news_type)" class="grid grid-cols-1 md:grid-cols-1 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div>
                        <div class="text-gray-500 mb-1">Body</div>
                        <div class="text-gray-700">
                            <div v-html="news?.body || 'N/A'"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="checkIsVideo(news.news_type)" class="grid grid-cols-1 md:grid-cols-1 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div>
                        <div class="text-gray-500 mb-1">Video url</div>
                        <div class="text-gray-700">
                            {{ news?.video_url || 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="checkIsImageGallery(news.news_type)" class="grid grid-cols-1 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-3">
                    <NewsImageGalleryGrid :news="news" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Tags</span>
                        <div class="flex flex-wrap gap-2">
                            <template v-if="news?.tags && news.tags.length">
                                <span v-for="tag in news.tags" :key="tag.id ?? tag.name"
                                    class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 border border-gray-200">
                                    {{ tag?.name }}

                                    <FontAwesomeIcon icon="fire" v-if="tag?.trend" />
                                </span>
                            </template>

                            <span v-else class="text-sm text-gray-400 italic">
                                No tag added
                            </span>
                        </div>
                    </div>
                </div>

                <div v-if="checkIsStory(news.news_type)" class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Contributors</span>
                        <div class="flex flex-wrap gap-2">
                            <template v-if="news?.contributors && news.contributors.length">
                                <span v-for="contributor in news.contributors" :key="contributor.id ?? contributor.name"
                                    class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 border border-gray-200">
                                    {{ contributor?.name }}
                                    <FontAwesomeIcon icon="fire" v-if="contributor?.trend" />
                                </span>
                            </template>

                            <span v-else class="text-sm text-gray-400 italic">
                                No contributor added
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div>
                        <div class="text-gray-500 mb-1">Feature Image</div>
                        <div class="text-gray-700 w-100">
                            <MediaRenderer v-if="news?.feature_image" :media="news?.feature_image" />
                            <img v-else-if="checkIsStory(news.news_type)" :src="'/uploads/images/news/story-feature-image.png'"
                                class="object-cover rounded-xl border border-gray-200" />
                            <img v-else-if="checkIsVideo(news.news_type)" :src="'/uploads/images/news/video-feature-image.png'"
                                class="object-cover rounded-xl border border-gray-200" />
                            <img v-else-if="checkIsImageGallery(news.news_type)" :src="'/uploads/images/news/image-gallery-feature-image.png'"
                                class="object-cover rounded-xl border border-gray-200" />
                            <img v-else :src="'/uploads/images/news/story-feature-image.png'"
                                class="object-cover rounded-xl border border-gray-200" />
                        </div>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div>
                        <div class="text-gray-500 mb-1">Feature Image (Mobile)</div>
                        <div class="text-gray-700 w-100">
                            <MediaRenderer v-if="news?.feature_image_mobile" :media="news?.feature_image_mobile" />
                            <img v-else-if="checkIsStory(news.news_type)" :src="'/uploads/images/news/story-feature-image-mobile.png'"
                                class="object-cover rounded-xl border border-gray-200" />
                            <img v-else-if="checkIsVideo(news.news_type)" :src="'/uploads/images/news/video-feature-image-mobile.png'"
                                class="object-cover rounded-xl border border-gray-200" />
                            <img v-else-if="checkIsImageGallery(news.news_type)" :src="'/uploads/images/news/image-gallery-feature-image-mobile.png'"
                                class="object-cover rounded-xl border border-gray-200" />
                            <img v-else :src="'/uploads/images/news/story-feature-image-mobile.png'"
                                class="object-cover rounded-xl border border-gray-200" />
                        </div>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="text-gray-500 mb-2">SEO</div>

                    <div class="space-y-3 text-sm">
                        <div>
                            <div class="text-gray-500 mb-1">Title</div>
                            <div class="font-medium text-gray-700">
                                {{ news?.seo_title || 'N/A' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-gray-500 mb-1">Brief</div>
                            <div class="font-medium text-gray-700">
                                {{ news?.seo_brief || 'N/A' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-gray-500 mb-1">Keywords</div>
                            <div class="font-medium text-gray-700">
                                {{ news?.seo_keywords || 'N/A' }}
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div v-if="checkIsStory(news.news_type)" class="border border-gray-200 rounded-lg p-4 space-y-2">

                    <div class="flex justify-between">
                        <span class="text-gray-500">Source</span>
                        <span class="font-medium">{{ news?.source || 'N/A' }}</span>
                    </div>
                </div>

                <div v-if="checkIsStory(news.news_type)" class="border border-gray-200 rounded-lg p-4 space-y-2">

                    <div class="flex justify-between">
                        <span class="text-gray-500">Writer</span>
                        <span class="font-medium">{{ news?.writer || 'N/A' }}</span>
                    </div>

                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">

                    <div class="flex justify-between">
                        <span class="text-gray-500">Published</span>
                        <span class="font-medium">{{ news?.is_published ? "Yes" : "No" }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">System Information</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Created At</span>
                        <span class="font-medium">
                            {{ news?.created_at ? formatDateTime(news.created_at) : 'N/A' }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Created By</span>
                        <span class="font-medium">
                            {{ news?.created_by?.name || 'N/A' }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Updated At</span>
                        <span class="font-medium">
                            {{ news?.updated_at ? formatDateTime(news.updated_at) : 'N/A' }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Updated By</span>
                        <span class="font-medium">
                            {{ news?.latest_activity_log?.causer?.name || 'N/A' }}
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div v-if="news?.breaking_news" class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">Breaking News</h3>

            <div class="flex justify-between">
                <span class="text-gray-500">Title</span>
                <span class="font-medium">{{ news?.breaking_news?.title || 'N/A' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Published</span>
                <span class="font-medium">{{ news?.breaking_news?.is_published ? "Yes" : "No" }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Created at</span>
                <span class="font-medium">
                    {{ news?.breaking_news?.created_at ? formatDateTime(news?.breaking_news?.created_at) : 'N/A' }}
                </span>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">News placements</h3>
            <NewsPlacementList :news="news" :news-placements="news.news_placements" />
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">Relevant News</h3>
            <RelatedOrRelevantNewsList :news="news?.relevant_news" />
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">Related News</h3>
            <RelatedOrRelevantNewsList :news="news?.related_news" />
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">Activity Logs</h3>
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
                                Delete News
                            </h3>

                            <p class="text-sm font-medium">
                                {{ news?.title }}
                            </p>

                            <p class="text-sm text-gray-500">
                                This action can be undone by restoring news.
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button @click="showDeleteModal = false"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    Cancel
                                </button>

                                <button @click="handleDelete" :disabled="deleteProcessing"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2">
                                    <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />
                                    Delete
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
                            <h3 class="text-lg font-semibold text-red-600">
                                Restore News
                            </h3>

                            <p class="text-sm font-medium">
                                {{ news?.title }}
                            </p>

                            <p class="text-sm text-gray-500">
                                This action can be undone by deleting news.
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button @click="showRestoreModal = false"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    Cancel
                                </button>

                                <button @click="handleRestore" :disabled="restoreProcessing"
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm flex items-center gap-2">
                                    <FontAwesomeIcon v-if="restoreProcessing" icon="spinner" spin />
                                    Restore
                                </button>
                            </div>
                        </div>
                    </Transition>

                </div>
            </Transition>
        </Teleport>

    </div>
</template>
