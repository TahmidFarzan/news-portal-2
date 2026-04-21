<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import RecentActivities from '@/components/back-office/activity-log/RecentModelActivityLogs.vue'

import { ref, onMounted, nextTick, inject } from 'vue'
import { Head, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faTrash, faPen, faEye, faEyeSlash, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { formatDate, formatDateTime } from '@/composables/useDateTime'
import { canEditUser, canDeleteUser, canActiveInactiveUser } from '@/composables/useAuthUserAccessPermissions'

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

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">User Details</h2>

            <div class="flex gap-2">
                <a v-if="canEdit(user)" :href="route('back-office.users.edit', { slug: user?.slug })"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="pen" />
                    Edit
                </a>

                <button v-if="user?.is_active && canActiveInactive(user)" @click="handleInactive"
                    :disabled="inactiveProcessing"
                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon v-if="inactiveProcessing" icon="spinner" spin />
                    <FontAwesomeIcon v-else icon="eye-slash" />
                    Inactive
                </button>

                <button v-if="!user?.is_active && canActiveInactive(user)" @click="handleActive"
                    :disabled="activeProcessing"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon v-if="activeProcessing" icon="spinner" spin />
                    <FontAwesomeIcon v-else icon="eye" />
                    Active
                </button>

                <button v-if="canDelete(user)" @click="showDeleteModal = true"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="trash" />
                    Delete
                </button>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">Basic Information</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Name</span>
                        <span class="font-medium">{{ user?.name || 'N/A' }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Email</span>
                        <span class="font-medium">{{ user?.email || 'N/A' }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Mobile</span>
                        <span class="font-medium">{{ user?.mobile || 'N/A' }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Status</span>
                        <span :class="user?.is_active ? 'text-green-600' : 'text-red-500'" class="font-medium">
                            {{ user?.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 flex items-center justify-center">
                    <img :src="user?.profile_image?.media_url || '/uploads/icons/auth/user.png'"
                        class="w-40 h-40 object-cover rounded-xl border" />
                </div>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Birth Date</span>
                        <span class="font-medium">
                            {{ user?.birth_date ? formatDate(user?.birth_date) : 'N/A' }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Gender</span>
                        <span class="font-medium">{{ user?.gender?.name || 'N/A' }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Age</span>
                        <span class="font-medium">{{ user?.age || 'N/A' }}</span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Religion</span>
                        <span class="font-medium">{{ user?.religion?.name || 'N/A' }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Marital Status</span>
                        <span class="font-medium">{{ user?.marital_status?.name || 'N/A' }}</span>
                    </div>
                </div>

            </div>

            <div class="border border-gray-200 rounded-lg p-4 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">User Role</span>
                    <span class="font-medium">{{ user?.user_role?.name || 'N/A' }}</span>
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
                            {{ user?.created_at ? formatDateTime(user.created_at) : 'N/A' }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Created By</span>
                        <span class="font-medium">
                            {{ user?.created_by?.name || 'N/A' }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Updated At</span>
                        <span class="font-medium">
                            {{ user?.updated_at ? formatDateTime(user.updated_at) : 'N/A' }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Updated By</span>
                        <span class="font-medium">
                            {{ user?.latest_activity_log?.causer?.name || 'N/A' }}
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">Activity Logs</h3>
            <RecentActivities :model-slug="'user'" :model="user" />
        </div>

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
                            Delete User
                        </h3>

                        <p class="text-sm font-medium">
                            {{ user?.name }}
                        </p>

                        <p class="text-sm text-gray-500">
                            This action cannot be undone.
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

    </div>
</template>
