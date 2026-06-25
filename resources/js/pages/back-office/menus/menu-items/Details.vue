<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import RecentActivities from '@/components/back-office/activity-log/RecentModelActivityLogs.vue'

import { ref, onMounted, nextTick, inject } from 'vue'
import { Head, router as inertiaJsRouter } from '@inertiajs/vue3'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faTrash, faPen, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { extractModelName } from '@/composables/useStringFormat'
import { formatDateTime } from '@/composables/useDateTime'
import { canEditMenuItem, canDeleteMenuItem } from '@/composables/useUserPermissions'
import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faTrash, faPen, faSpinner)

defineOptions({ layout: Layout })

const authUser = inject('authUser')
const { t } = useTranslate()

const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const { menu, menuItem } = defineProps({
    menu: {
        type: Object,
        default: null,
    },
    menuItem: {
        type: Object,
        default: null,
    },
})

const canEdit = (menuItem) => canEditMenuItem(authUser?.value, menuItem)
const canDelete = (menuItem) => canDeleteMenuItem(authUser?.value, menuItem)

const handleDelete = () => {
    if (deleteProcessing.value) return

    deleteProcessing.value = true

    inertiaJsRouter.delete(
        route('back-office.menus.menu-items.delete', {
            slug: menu?.slug,
            menuItemSlug: menuItem?.slug,
        }),
        {
            onFinish: () => {
                deleteProcessing.value = false
            },
        }
    )
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: t('pages.back_office.menus.menu_items.details.menus'), href: route('back-office.menus.index') },
                {
                    text: `${menu?.name} ${t('pages.back_office.menus.menu_items.details.labels.details')}`,
                    href: route('back-office.menus.details', { slug: menu?.slug }),
                },
                {
                    text: t('pages.back_office.menus.menu_items.details.menu_items'),
                    href: route('back-office.menus.menu-items.index', { slug: menu?.slug }),
                },
                { text: `${menuItem?.name} ${t('pages.back_office.menus.menu_items.details.labels.details')}`, active: true },
            ],
        })
    )
})
</script>

<template>

    <Head :title="`${menuItem?.name} ${t('pages.back_office.menus.menu_items.details.labels.details')}`" />

    <div class="w-full space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">
                {{ t('pages.back_office.menus.menu_items.details.title') }}
            </h2>

            <div class="flex gap-2">
                <a v-if="canEdit(menuItem)" :href="route('back-office.menus.menu-items.edit', {
                    slug: menu?.slug,
                    menuItemSlug: menuItem?.slug,
                })"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="pen" />
                    {{ t('pages.back_office.menus.menu_items.details.actions.edit') }}
                </a>

                <button v-if="canDelete(menuItem)" @click="showDeleteModal = true"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="trash" />
                    {{ t('pages.back_office.menus.menu_items.details.actions.delete') }}
                </button>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('pages.back_office.menus.menu_items.details.labels.basic_information') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.menus.menu_items.details.labels.name') }}</span>
                        <span class="font-medium">{{ menuItem?.name || t('pages.back_office.menus.menu_items.details.labels.not_available') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.menus.menu_items.details.labels.language') }}</span>
                        <span class="font-medium">{{ menuItem?.language?.name || t('pages.back_office.menus.menu_items.details.labels.not_available') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <div class="text-gray-500 mb-1">{{ t('pages.back_office.menus.menu_items.details.categories.form.parent') }}</div>
                        <div class="text-gray-700">{{ menuItem?.parent?.name || t('pages.back_office.menus.menu_items.details.labels.not_available') }}</div>
                    </div>

                    <div class="flex justify-between">
                        <div class="text-gray-500 mb-1">{{ t('pages.back_office.menus.menu_items.details.table.columns.position') }}</div>
                        <div class="text-gray-700">{{ menuItem?.position || t('pages.back_office.menus.menu_items.details.labels.not_available') }}</div>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">

                    <div class="flex justify-between">
                        <div class="text-gray-500 mb-1">{{ t('pages.back_office.menus.menu_items.details.form.model') }}</div>

                        <div class="text-gray-700">
                            {{ menuItem?.model_type ? extractModelName(menuItem.model_type) : t('pages.back_office.menus.menu_items.details.labels.not_available')
                            }}

                            <span v-if="menuItem?.model?.name">
                                - {{ menuItem?.model?.name }}
                            </span>
                        </div>
                    </div>

                    <div class="flex justify-between">
                        <div class="text-gray-500 mb-1">{{ t('pages.back_office.menus.menu_items.details.labels.url') }}</div>
                        <div class="text-gray-700">{{ menuItem?.url || t('pages.back_office.menus.menu_items.details.labels.not_available') }}</div>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500 mb-1">{{ t('pages.back_office.menus.menu_items.details.public_url') }}</span>
                        <span class="font-medium">{{ menuItem?.public_url || t('pages.back_office.menus.menu_items.details.labels.not_available') }}</span>
                    </div>
                </div>

            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('pages.back_office.menus.menu_items.details.medias.details.system_information') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.menus.menu_items.details.table.columns.created_at') }}</span>
                        <span class="font-medium">
                            {{ menuItem?.created_at ? formatDateTime(menuItem.created_at) : t('pages.back_office.menus.menu_items.details.labels.not_available') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.menus.menu_items.details.labels.created_by') }}</span>
                        <span class="font-medium">
                            {{ menuItem?.created_by?.name || t('pages.back_office.menus.menu_items.details.labels.not_available') }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.menus.menu_items.details.labels.updated_at') }}</span>
                        <span class="font-medium">
                            {{ menuItem?.updated_at ? formatDateTime(menuItem.updated_at) : t('pages.back_office.menus.menu_items.details.labels.not_available') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.menus.menu_items.details.labels.updated_by') }}</span>
                        <span class="font-medium">
                            {{ menuItem?.latest_activity_log?.causer?.name || t('pages.back_office.menus.menu_items.details.labels.not_available') }}
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('pages.back_office.menus.menu_items.details.activity_logs.index.title') }}
            </h3>

            <RecentActivities :model-slug="'menu-item'" :model="menuItem" />
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
                                {{ t('pages.back_office.menus.menu_items.details.delete_modal.title') }}
                            </h3>

                            <p class="text-sm font-medium">
                                {{ menuItem?.name }}
                            </p>

                            <p class="text-sm text-gray-500">
                                {{ t('pages.back_office.menus.menu_items.details.modals.delete_confirmation_modal.irreversible_body') }}
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button @click="showDeleteModal = false"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    {{ t('pages.back_office.menus.menu_items.details.actions.cancel') }}
                                </button>

                                <button @click="handleDelete" :disabled="deleteProcessing"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2">
                                    <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />

                                    {{ deleteProcessing ? t('pages.back_office.menus.menu_items.details.actions.deleting') : t('pages.back_office.menus.menu_items.details.actions.delete') }}
                                </button>
                            </div>
                        </div>
                    </Transition>

                </div>
            </Transition>
        </Teleport>
    </div>
</template>
