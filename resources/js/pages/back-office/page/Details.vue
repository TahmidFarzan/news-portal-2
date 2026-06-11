<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'

import { ref, onMounted, nextTick, inject } from 'vue'
import { Head, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faTrash, faTrashCan, faPen, faEye, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'
import {
    canEditPage,
    canTrashPage,
    canRestorePage,
    canDeletePage
} from '@/composables/useAuthUserAccessPermissions'

FontAwesomeLibrary.add(faTrash, faTrashCan, faPen, faEye, faSpinner)

defineOptions({ layout: Layout })

const authUser = inject("authUser")

const showTrashModal = ref(false)
const trashProcessing = ref(false)

const showRestoreModal = ref(false)
const restoreProcessing = ref(false)

const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const { page } = defineProps({
    page: Object,
})

const canEdit = (page) => canEditPage(authUser?.value, page)
const canTrash = (page) => canTrashPage(authUser?.value, page)
const canRestore = (page) => canRestorePage(authUser?.value, page)
const canDelete = (page) => canDeletePage(authUser?.value, page)

const closeTrashModal = () => {
    showTrashModal.value = false
}

const closeRestoreModal = () => {
    showRestoreModal.value = false
}

const closeDeleteModal = () => {
    showDeleteModal.value = false
}

const handleTrash = () => {
    if (trashProcessing.value) return

    trashProcessing.value = true

    intertiaJsRoute.patch(route('back-office.pages.trash', { slug: page?.slug }), {}, {
        preserveScroll: true,
        onFinish: () => {
            trashProcessing.value = false
            closeTrashModal()
        }
    })
}

const handleRestore = () => {
    if (restoreProcessing.value) return

    restoreProcessing.value = true

    intertiaJsRoute.patch(route('back-office.pages.restore', { slug: page?.slug }), {}, {
        preserveScroll: true,
        onFinish: () => {
            restoreProcessing.value = false
            closeRestoreModal()
        }
    })
}

const handleDelete = () => {
    if (deleteProcessing.value) return

    deleteProcessing.value = true

    intertiaJsRoute.delete(route('back-office.pages.delete', { slug: page?.slug }), {
        preserveScroll: true,
        onFinish: () => {
            deleteProcessing.value = false
            closeDeleteModal()
        }
    })
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: 'Page', href: route('back-office.pages.index') },
                { text: `${page?.title} details`, active: true }
            ],
        })
    )
})
</script>

<template>

    <Head :title="`${page?.title} details`" />

    <div class="w-full space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">Page Details</h2>

            <div class="flex gap-2">
                <a v-if="canEdit(page)" :href="route('back-office.pages.edit', { slug: page?.slug })"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="pen" />
                    Edit
                </a>

                <button v-if="canTrash(page)" type="button" @click="showTrashModal = true"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="trash" />
                    Trash
                </button>

                <button v-if="canRestore(page)" type="button" @click="showRestoreModal = true"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="eye" />
                    Restore
                </button>

                <button v-if="canDelete(page)" type="button" @click="showDeleteModal = true"
                    class="bg-red-700 hover:bg-red-800 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="trash-can" />
                    Delete
                </button>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">Basic Information</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Language</span>
                        <span class="font-medium">{{ page?.language?.name || 'N/A' }}</span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Default</span>
                        <span class="font-medium">{{ page?.is_default ? "Yes" : "No" }}</span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Default use as</span>
                        <span class="font-medium">{{ page?.default_use_as || "N/A" }}</span>
                    </div>
                </div>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Title</span>
                        <span class="font-medium">{{ page?.title || 'N/A' }}</span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div>
                        <div class="text-gray-500 mb-1">Brief</div>
                        <div class="text-gray-700">{{ page?.brief || 'N/A' }}</div>
                    </div>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-500">Parent</span>
                    <span class="font-medium">{{ page?.parent?.title || 'N/A' }}</span>
                </div>
            </div>

            <div v-if="!page?.is_default" class="grid grid-cols-1 md:grid-cols-1 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div>
                        <div class="text-gray-500 mb-1">Body</div>
                        <div class="text-gray-700">
                            <div v-html="page?.body || 'N/A'"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border border-gray-200 rounded-lg p-4 space-y-3">
                <div class="text-gray-500">Tree</div>

                <div class="flex flex-wrap gap-2">
                    <span v-for="node in page?.bloodline || []" :key="node.id"
                        class="bg-blue-600 text-white text-xs px-3 py-1 rounded-md">
                        {{ node.title }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="text-gray-500 mb-2">SEO</div>

                    <div class="space-y-3 text-sm">
                        <div>
                            <div class="text-gray-500 mb-1">Title</div>
                            <div class="font-medium text-gray-700">
                                {{ page?.seo_title || 'N/A' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-gray-500 mb-1">Brief</div>
                            <div class="font-medium text-gray-700">
                                {{ page?.seo_brief || 'N/A' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-gray-500 mb-1">Keywords</div>
                            <div class="font-medium text-gray-700">
                                {{ page?.seo_keywords || 'N/A' }}
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Published</span>
                        <span class="font-medium">{{ page?.is_published ? "Yes" : "No" }}</span>
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
                            {{ page?.created_at ? formatDateTime(page.created_at) : 'N/A' }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Created By</span>
                        <span class="font-medium">
                            {{ page?.created_by?.name || 'N/A' }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Updated At</span>
                        <span class="font-medium">
                            {{ page?.updated_at ? formatDateTime(page.updated_at) : 'N/A' }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Updated By</span>
                        <span class="font-medium">
                            {{ page?.latest_activity_log?.causer?.name || 'N/A' }}
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <Teleport to="body">
            <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showTrashModal"
                    class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50">

                    <Transition enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-4">
                        <div v-if="showTrashModal" class="bg-white rounded-xl shadow-lg w-[380px] p-6 space-y-4">
                            <h3 class="text-lg font-semibold text-red-600">
                                Trash Page
                            </h3>

                            <p class="text-sm font-medium">
                                {{ page?.title }}
                            </p>

                            <p class="text-sm text-gray-500">
                                This action can be undone by restoring page.
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="closeTrashModal"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    Cancel
                                </button>

                                <button type="button" @click="handleTrash" :disabled="trashProcessing"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                                    <FontAwesomeIcon v-if="trashProcessing" icon="spinner" spin />
                                    Trash
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
                                Restore Page
                            </h3>

                            <p class="text-sm font-medium">
                                {{ page?.title }}
                            </p>

                            <p class="text-sm text-gray-500">
                                This action can be undone by trashing page.
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="closeRestoreModal"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    Cancel
                                </button>

                                <button type="button" @click="handleRestore" :disabled="restoreProcessing"
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm flex items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                                    <FontAwesomeIcon v-if="restoreProcessing" icon="spinner" spin />
                                    Restore
                                </button>
                            </div>
                        </div>
                    </Transition>

                </div>
            </Transition>

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
                            <h3 class="text-lg font-semibold text-red-700">
                                Delete Page
                            </h3>

                            <p class="text-sm font-medium">
                                {{ page?.title }}
                            </p>

                            <p class="text-sm text-gray-500">
                                This action cannot be undone.
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="closeDeleteModal"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    Cancel
                                </button>

                                <button type="button" @click="handleDelete" :disabled="deleteProcessing"
                                    class="px-4 py-2 bg-red-700 hover:bg-red-800 text-white rounded-md text-sm flex items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                                    <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />
                                    Delete
                                </button>
                            </div>
                        </div>
                    </Transition>

                </div>
            </Transition>
        </Teleport>

    </div>
</template>
