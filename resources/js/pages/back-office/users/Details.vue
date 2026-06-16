<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import RecentActivities from '@/components/back-office/activity-log/RecentModelActivityLogs.vue'

import { ref, computed, onMounted, nextTick, inject } from 'vue'
import { Head, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faTrash, faPen, faEye, faEyeSlash, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { formatDate, formatDateTime } from '@/composables/useDateTime'
import { canEditUser, canDeleteUser, canActiveInactiveUser } from '@/composables/useAuthUserAccessPermissions'
import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faTrash, faPen, faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const authUser = inject("authUser")

const showDeleteModal = ref(false)
const deleteProcessing = ref(false)
const activeProcessing = ref(false)
const inactiveProcessing = ref(false)

const { user } = defineProps({
    user: Object,
})

const pageTitle = computed(() => `${user?.name} ${t('pages.back_office.users.details.labels.details')}`)

const canEdit = (user) => canEditUser(authUser?.value, user)
const canDelete = (user) => canDeleteUser(authUser?.value, user)
const canActiveInactive = (user) => canActiveInactiveUser(authUser?.value, user)

const closeDeleteModal = () => {
    showDeleteModal.value = false
}

const handleDelete = () => {
    if (deleteProcessing.value) return

    deleteProcessing.value = true

    intertiaJsRoute.delete(route('back-office.users.delete', { slug: user?.slug }), {
        onFinish: () => {
            deleteProcessing.value = false
            closeDeleteModal()
        }
    })
}

const handleActive = () => {
    if (activeProcessing.value) return

    activeProcessing.value = true

    intertiaJsRoute.patch(route('back-office.users.active', { slug: user?.slug }), {
        onFinish: () => activeProcessing.value = false
    })
}

const handleInactive = () => {
    if (inactiveProcessing.value) return

    inactiveProcessing.value = true

    intertiaJsRoute.patch(route('back-office.users.inactive', { slug: user?.slug }), {
        onFinish: () => inactiveProcessing.value = false
    })
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: t('pages.back_office.users.details.labels.users'), href: route('back-office.users.index') },
                { text: pageTitle.value, active: true }
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
                {{ t('pages.back_office.users.details.title') }}
            </h2>

            <div class="flex gap-2">
                <a v-if="canEdit(user)" :href="route('back-office.users.edit', { slug: user?.slug })"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="pen" />
                    {{ t('pages.back_office.users.details.actions.edit') }}
                </a>

                <button v-if="user?.is_active && canActiveInactive(user)" type="button" @click="handleInactive"
                    :disabled="inactiveProcessing"
                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition disabled:opacity-60 disabled:cursor-not-allowed">
                    <FontAwesomeIcon v-if="inactiveProcessing" icon="spinner" spin />
                    <FontAwesomeIcon v-else icon="eye-slash" />
                    {{ inactiveProcessing ? t('pages.back_office.users.details.buttons.inactivating') : t('pages.back_office.users.details.buttons.inactive') }}
                </button>

                <button v-if="!user?.is_active && canActiveInactive(user)" type="button" @click="handleActive"
                    :disabled="activeProcessing"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition disabled:opacity-60 disabled:cursor-not-allowed">
                    <FontAwesomeIcon v-if="activeProcessing" icon="spinner" spin />
                    <FontAwesomeIcon v-else icon="eye" />
                    {{ activeProcessing ? t('pages.back_office.users.details.buttons.activating') : t('pages.back_office.users.details.buttons.active') }}
                </button>

                <button v-if="canDelete(user)" type="button" @click="showDeleteModal = true"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="trash" />
                    {{ t('pages.back_office.users.details.actions.delete') }}
                </button>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('pages.back_office.users.details.labels.basic_information') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.users.details.labels.name') }}</span>
                        <span class="font-medium">{{ user?.name || t('pages.back_office.users.details.labels.not_available') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.users.details.labels.email') }}</span>
                        <span class="font-medium">{{ user?.email || t('pages.back_office.users.details.labels.not_available') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.users.details.labels.mobile') }}</span>
                        <span class="font-medium">{{ user?.mobile || t('pages.back_office.users.details.labels.not_available') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.users.details.status') }}</span>
                        <span :class="user?.is_active ? 'text-green-600' : 'text-red-500'" class="font-medium">
                            {{ user?.is_active ? t('pages.back_office.users.details.buttons.active') : t('pages.back_office.users.details.buttons.inactive') }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 flex items-center justify-center">
                    <img :src="user?.profile_image?.media_url || '/uploads/icons/auth/user.png'"
                        :alt="t('pages.back_office.users.details.auth.profile.profile_image_alt')" class="w-40 h-40 object-cover rounded-xl border" />
                </div>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.users.details.labels.birth_date') }}</span>
                        <span class="font-medium">
                            {{ user?.birth_date ? formatDate(user?.birth_date) : t('pages.back_office.users.details.labels.not_available') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.users.details.labels.gender') }}</span>
                        <span class="font-medium">{{ user?.gender?.name || t('pages.back_office.users.details.labels.not_available') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.users.details.labels.age') }}</span>
                        <span class="font-medium">{{ user?.age || t('pages.back_office.users.details.labels.not_available') }}</span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.users.details.labels.religion') }}</span>
                        <span class="font-medium">{{ user?.religion?.name || t('pages.back_office.users.details.labels.not_available') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.users.details.labels.marital_status') }}</span>
                        <span class="font-medium">{{ user?.marital_status?.name || t('pages.back_office.users.details.labels.not_available') }}</span>
                    </div>
                </div>

            </div>

            <div class="border border-gray-200 rounded-lg p-4 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">{{ t('pages.back_office.users.details.form.user_role') }}</span>
                    <span class="font-medium">{{ user?.user_role?.name || t('pages.back_office.users.details.labels.not_available') }}</span>
                </div>
            </div>

        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('pages.back_office.users.details.labels.system_information') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.users.details.labels.created_at') }}</span>
                        <span class="font-medium">
                            {{ user?.created_at ? formatDateTime(user.created_at) : t('pages.back_office.users.details.labels.not_available') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.users.details.labels.created_by') }}</span>
                        <span class="font-medium">
                            {{ user?.created_by?.name || t('pages.back_office.users.details.labels.not_available') }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.users.details.labels.updated_at') }}</span>
                        <span class="font-medium">
                            {{ user?.updated_at ? formatDateTime(user.updated_at) : t('pages.back_office.users.details.labels.not_available') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.users.details.labels.updated_by') }}</span>
                        <span class="font-medium">
                            {{ user?.latest_activity_log?.causer?.name || t('pages.back_office.users.details.labels.not_available') }}
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('pages.back_office.users.details.activity_logs.index.title') }}
            </h3>

            <RecentActivities :model-slug="'user'" :model="user" />
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
                                {{ t('pages.back_office.users.details.delete_modal.title') }}
                            </h3>

                            <p class="text-sm font-medium">
                                {{ user?.name }}
                            </p>

                            <p class="text-sm text-gray-500">
                                {{ t('pages.back_office.users.details.modals.delete_confirmation_modal.irreversible_body') }}
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="closeDeleteModal"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    {{ t('pages.back_office.users.details.actions.cancel') }}
                                </button>

                                <button type="button" @click="handleDelete" :disabled="deleteProcessing"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                                    <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />
                                    {{ deleteProcessing ? t('pages.back_office.users.details.actions.deleting') : t('pages.back_office.users.details.actions.delete') }}
                                </button>
                            </div>
                        </div>
                    </Transition>

                </div>
            </Transition>
        </Teleport>
    </div>
</template>
