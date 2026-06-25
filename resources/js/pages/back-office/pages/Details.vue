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
} from '@/composables/useUserPermissions'

import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faTrash, faTrashCan, faPen, faEye, faSpinner)

defineOptions({ layout: Layout })

const { t } = useTranslate()

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

const closeTrashModal = () => showTrashModal.value = false
const closeRestoreModal = () => showRestoreModal.value = false
const closeDeleteModal = () => showDeleteModal.value = false

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
                { text: t('pages.back_office.pages.details.labels.page'), href: route('back-office.pages.index') },
                { text: `${page?.title} ${t('pages.back_office.pages.details.labels.details')}`, active: true }
            ],
        })
    )
})
</script>

<template>

    <Head :title="`${page?.title} ${t('pages.back_office.pages.details.labels.details')}`" />

    <div class="w-full space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">{{ t('pages.back_office.pages.details.form.edit_page_title') }}</h2>

            <div class="flex gap-2">

                <a v-if="canEdit(page)"
                    :href="route('back-office.pages.edit', { slug: page?.slug })"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="pen" />
                    {{ t('pages.back_office.pages.details.actions.edit') }}
                </a>

                <button v-if="canTrash(page)" @click="showTrashModal = true"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="trash" />
                    {{ t('pages.back_office.pages.details.actions.trash') }}
                </button>

                <button v-if="canRestore(page)" @click="showRestoreModal = true"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="eye" />
                    {{ t('pages.back_office.pages.details.actions.restore') }}
                </button>

                <button v-if="canDelete(page)" @click="showDeleteModal = true"
                    class="bg-red-700 hover:bg-red-800 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="trash-can" />
                    {{ t('pages.back_office.pages.details.actions.delete') }}
                </button>

            </div>
        </div>

        <!-- BASIC INFO -->
        <div class="bg-white border rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="font-semibold border-b pb-2">{{ t('pages.back_office.pages.details.labels.basic_information') }}</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">

                <div class="border rounded-lg p-4">
                    <span class="text-gray-500">{{ t('pages.back_office.pages.details.labels.language') }}</span>
                    <div class="font-medium">{{ page?.language?.name || t('pages.back_office.pages.details.labels.not_available') }}</div>
                </div>

                <div class="border rounded-lg p-4">
                    <span class="text-gray-500">{{ t('pages.back_office.pages.details.form.default_use_as') }}</span>
                    <div class="font-medium">{{ page?.default_use_as || t('pages.back_office.pages.details.labels.not_available') }}</div>
                </div>

                <div class="border rounded-lg p-4">
                    <span class="text-gray-500">{{ t('pages.back_office.pages.details.labels.title') }}</span>
                    <div class="font-medium">{{ page?.title || t('pages.back_office.pages.details.labels.not_available') }}</div>
                </div>

            </div>

        </div>

        <!-- BODY -->
        <div v-if="!page?.is_default" class="bg-white border rounded-xl p-5">
            <h3 class="font-semibold mb-2">{{ t('pages.back_office.pages.details.form.body') }}</h3>
            <div v-html="page?.body || t('pages.back_office.pages.details.labels.not_available')" />
        </div>

        <!-- TREE -->
        <div class="bg-white border rounded-xl p-5">
            <h3 class="font-semibold mb-2">{{ t('pages.back_office.pages.details.labels.tree') }}</h3>

            <div class="flex flex-wrap gap-2">
                <span v-for="node in page?.bloodline || []"
                    :key="node.id"
                    class="bg-blue-600 text-white text-xs px-3 py-1 rounded-md">
                    {{ node.title }}
                </span>
            </div>
        </div>

        <!-- SEO -->
        <div class="bg-white border rounded-xl p-5">
            <h3 class="font-semibold mb-3">{{ t('pages.back_office.pages.details.labels.seo_settings') }}</h3>

            <div class="space-y-2 text-sm">
                <div>
                    <div class="text-gray-500">{{ t('pages.back_office.pages.details.labels.title') }}</div>
                    <div>{{ page?.seo_title || t('pages.back_office.pages.details.labels.not_available') }}</div>
                </div>

                <div>
                    <div class="text-gray-500">{{ t('pages.back_office.pages.details.labels.brief') }}</div>
                    <div>{{ page?.seo_brief || t('pages.back_office.pages.details.labels.not_available') }}</div>
                </div>

                <div>
                    <div class="text-gray-500">{{ t('pages.back_office.pages.details.labels.keywords') }}</div>
                    <div>{{ page?.seo_keywords || t('pages.back_office.pages.details.labels.not_available') }}</div>
                </div>
            </div>
        </div>

        <!-- MODALS -->
        <Teleport to="body">

            <!-- TRASH -->
            <div v-if="showTrashModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
                <div class="bg-white p-6 rounded-xl w-[380px] space-y-4">
                    <h3 class="text-red-600 font-semibold">{{ t('pages.back_office.pages.details.actions.trash') }}</h3>
                    <p>{{ page?.title }}</p>

                    <div class="flex justify-end gap-2">
                        <button @click="closeTrashModal">{{ t('pages.back_office.pages.details.actions.cancel') }}</button>
                        <button @click="handleTrash" :disabled="trashProcessing">
                            {{ trashProcessing ? t('pages.back_office.pages.details.actions.trashing') : t('pages.back_office.pages.details.actions.trash') }}
                        </button>
                    </div>
                </div>
            </div>

        </Teleport>

    </div>
</template>
