<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import RecentActivities from '@/components/back-office/activity-log/RecentModelActivityLogs.vue'

import { ref, onMounted, nextTick, inject } from 'vue'
import { Head, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faTrash, faPen, faEye, faEyeSlash, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { formatDate, formatDateTime } from '@/composables/useDateTime'
import { canEditUser, canDeleteUser, canActiveInactiveUser } from '@/composables/useauthuseraccesspermissions'

FontAwesomeLibrary.add(faTrash, faPen, faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const pageReady = inject("pageReady")
const authUser = inject("authUser")

const showDeleteModal = ref(false)
const deleteProcessing = ref(false)
const activeProcessing = ref(false)
const inactiveProcessing = ref(false)

const { user } = defineProps({
    user: Object,
})

const canEdit = (user) => canEditUser(authUser?.value, user)
const canDelete = (user) => canDeleteUser(authUser?.value, user)
const canActiveInactive = (user) => canActiveInactiveUser(authUser?.value, user)

const handleDelete = () => {
    if (deleteProcessing.value) return
    deleteProcessing.value = true

    intertiaJsRoute.delete(route('back-office.users.delete', { slug: user?.slug }), {
        onFinish: () => deleteProcessing.value = false
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
                { text: 'Dashboard', href: route('auth-user.dashboard.index') },
                { text: 'Users', href: route('back-office.users.index') },
                { text: `${user?.name} details`, active: true }
            ],
        })
    )

    pageReady.value = true
})
</script>

<template>

    <Head :title="`${user?.name} details`" />

    <div class="w-full space-y-6">

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6">
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">Basic Information</h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div class="border border-gray-200 rounded-xl p-4 space-y-2 text-sm">
                    <div>
                        <span class="font-medium text-gray-600">Name:</span>
                        {{ user?.name || 'N/A' }}
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Email:</span>
                        {{ user?.email || 'N/A' }}
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Mobile:</span>
                        {{ user?.mobile || 'N/A' }}
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Active:</span>
                        {{ user?.is_active ? 'Yes' : 'No' }}
                    </div>
                </div>

                <div class="border border-gray-200 rounded-xl p-4 flex justify-center">
                    <img :src="user?.profile_image?.media_url || '/uploads/icons/auth/user.png'"
                        class="w-40 h-40 object-cover rounded-xl border" />
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4 mt-4">
                <div class="border border-gray-200 rounded-xl p-4 space-y-2 text-sm">
                    <div>
                        <span class="font-medium text-gray-600">Birth date:</span>
                        {{ user?.birth_date ? formatDate(user?.birth_date) : 'N/A' }}
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Gender:</span>
                        {{ user?.gender?.name || 'N/A' }}
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Age:</span>
                        {{ user?.age || 'N/A' }}
                    </div>
                </div>

                <div class="border border-gray-200 rounded-xl p-4 space-y-2 text-sm">
                    <div>
                        <span class="font-medium text-gray-600">Religion:</span>
                        {{ user?.religion?.name || 'N/A' }}
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Marital status:</span>
                        {{ user?.marital_status?.name || 'N/A' }}
                    </div>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4 mt-4">
                <div class="border border-gray-200 rounded-xl p-4 text-sm">
                    <span class="font-medium text-gray-600">User Role:</span>
                    {{ user?.user_role?.name || 'N/A' }}
                </div>
            </div>

        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6">
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">System Information</h3>
            <div class="grid md:grid-cols-2 gap-4 text-sm">
                <div class="border border-gray-200 rounded-xl p-4 space-y-2">
                    <div>
                        <span class="font-medium text-gray-600">Created At:</span>
                        {{ user?.created_at ? formatDateTime(user.created_at) : 'N/A' }}
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Created By:</span>
                        {{ user?.created_by?.name || 'N/A'}}
                    </div>
                </div>

                <div class="border border-gray-200 rounded-xl p-4 space-y-2">
                    <div>
                        <span class="font-medium text-gray-600">Updated At:</span>
                        {{ user?.updated_at ? formatDateTime(user.updated_at) : 'N/A' }}
                    </div>
                    <div>
                        <span class="font-medium text-gray-600">Updated By:</span>
                        {{ user?.updated_by?.name || 'N/A' }}
                    </div>
                </div>
            </div>

        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6">
            <h3 class="text-lg font-semibold mb-4 border-b pb-2">Activity Logs</h3>
            <RecentActivities :model-slug="'user'" :model="user" />
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 flex justify-end gap-2">

            <a v-if="canEdit(user)" :href="route('back-office.users.edit', { slug: user?.slug })"
                class="px-4 py-2 border border-blue-500 text-blue-600 rounded hover:bg-blue-50 flex items-center gap-2">
                <FontAwesomeIcon icon="pen" /> Edit
            </a>

            <button v-if="user?.is_active && canActiveInactive(user)" @click="handleInactive"
                :disabled="inactiveProcessing"
                class="px-4 py-2 border border-gray-400 text-gray-700 rounded hover:bg-gray-100 flex items-center gap-2">
                <FontAwesomeIcon v-if="inactiveProcessing" icon="spinner" spin />
                <FontAwesomeIcon icon="eye-slash" v-else />
                Inactive
            </button>

            <button v-if="!user?.is_active && canActiveInactive(user)" @click="handleActive"
                :disabled="activeProcessing"
                class="px-4 py-2 border border-gray-400 text-gray-700 rounded hover:bg-gray-100 flex items-center gap-2">
                <FontAwesomeIcon v-if="activeProcessing" icon="spinner" spin />
                <FontAwesomeIcon icon="eye" v-else />
                Active
            </button>

            <button v-if="canDelete(user)" @click="showDeleteModal = true"
                class="px-4 py-2 border border-red-500 text-red-600 rounded hover:bg-red-50 flex items-center gap-2">
                <FontAwesomeIcon icon="trash" /> Delete
            </button>

        </div>

        <div v-if="showDeleteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">

            <div class="bg-white p-6 rounded-xl shadow-lg w-96">
                <div class="font-semibold mb-2">Delete Confirmation</div>
                <p class="mb-2">{{ user?.name }}</p>
                <p class="text-sm text-gray-600 mb-4">
                    This action cannot be undone.
                </p>

                <div class="flex justify-end gap-2">
                    <button @click="showDeleteModal = false" class="px-3 py-1 bg-gray-200 rounded">
                        Cancel
                    </button>
                    <button @click="handleDelete" :disabled="deleteProcessing" class="px-3 py-1 bg-red-500 text-white rounded flex items-center gap-1">
                        <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
