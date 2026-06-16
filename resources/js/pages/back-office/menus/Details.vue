<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import RecentActivities from '@/components/back-office/activity-log/RecentModelActivityLogs.vue'
import MenuItems from '@/components/back-office/menu/MenuItems.vue'

import { ref, onMounted, nextTick, inject } from 'vue'
import { Head, router as inertiaJsRouter } from '@inertiajs/vue3'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faTrash, faPen, faSpinner, faPlus } from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'
import { canEditMenu, canDeleteMenu } from '@/composables/useAuthUserAccessPermissions'
import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faTrash, faPen, faSpinner, faPlus)

defineOptions({ layout: Layout })

const authUser = inject('authUser')
const { t } = useTranslate()

const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const { menu } = defineProps({
    menu: {
        type: Object,
        default: null,
    },
})

const canEdit = (menu) => canEditMenu(authUser?.value, menu)
const canDelete = (menu) => canDeleteMenu(authUser?.value, menu)

const handleDelete = () => {
    if (deleteProcessing.value) return

    deleteProcessing.value = true

    inertiaJsRouter.delete(route('back-office.menus.delete', { slug: menu?.slug }), {
        onFinish: () => deleteProcessing.value = false,
    })
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: t('menus.menus'), href: route('back-office.menus.index') },
                { text: `${menu?.name} ${t('labels.details')}`, active: true },
            ],
        })
    )
})
</script>

<template>

    <Head :title="`${menu?.name} ${t('labels.details')}`" />

    <div class="w-full space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">
                {{ t('menus.details.title') }}
            </h2>

            <div class="flex gap-2">
                <a v-if="canEdit(menu)" :href="route('back-office.menus.edit', { slug: menu?.slug })"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="pen" />
                    {{ t('buttons.edit') }}
                </a>

                <button v-if="canDelete(menu)" @click="showDeleteModal = true"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="trash" />
                    {{ t('buttons.delete') }}
                </button>

                <a :href="route('back-office.menus.menu-items.create', { slug: menu?.slug })"
                    class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="plus" />
                    {{ t('menus.details.add_menu_item') }}
                </a>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('labels.basic_information') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('labels.name') }}</span>
                        <span class="font-medium">{{ menu?.name || t('labels.not_available') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('labels.language') }}</span>
                        <span class="font-medium">{{ menu?.language?.name || t('labels.not_available') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('menus.form.menu_type') }}</span>
                        <span class="font-medium">{{ menu?.menu_type?.name || t('labels.not_available') }}</span>
                    </div>
                </div>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-1 gap-4 text-sm">
                <h3 class="text-base font-semibold border-b pb-2">
                    {{ t('menus.details.menu_items') }}
                </h3>

                <div>
                    <a :href="route('back-office.menus.menu-items.create', { slug: menu?.slug })"
                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md inline-flex items-center gap-2 transition">
                        <FontAwesomeIcon icon="plus" />
                        {{ t('menus.details.add_menu_item') }}
                    </a>
                </div>

                <MenuItems :menu="menu" />
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('medias.details.system_information') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('table.columns.created_at') }}</span>
                        <span class="font-medium">
                            {{ menu?.created_at ? formatDateTime(menu.created_at) : t('labels.not_available') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('labels.created_by') }}</span>
                        <span class="font-medium">
                            {{ menu?.created_by?.name || t('labels.not_available') }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('labels.updated_at') }}</span>
                        <span class="font-medium">
                            {{ menu?.updated_at ? formatDateTime(menu.updated_at) : t('labels.not_available') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('labels.updated_by') }}</span>
                        <span class="font-medium">
                            {{ menu?.latest_activity_log?.causer?.name || t('labels.not_available') }}
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('activity_logs.index.title') }}
            </h3>

            <RecentActivities :model-slug="'menu'" :model="menu" />
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
                                {{ t('menus.delete_modal.title') }}
                            </h3>

                            <p class="text-sm font-medium">
                                {{ menu?.name }}
                            </p>

                            <p class="text-sm text-gray-500">
                                {{ t('delete_confirmation_modal.irreversible_body') }}
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button @click="showDeleteModal = false"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    {{ t('buttons.cancel') }}
                                </button>

                                <button @click="handleDelete" :disabled="deleteProcessing"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2">
                                    <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />

                                    {{ deleteProcessing ? t('buttons.deleting') : t('buttons.delete') }}
                                </button>
                            </div>
                        </div>
                    </Transition>

                </div>
            </Transition>
        </Teleport>
    </div>
</template>
