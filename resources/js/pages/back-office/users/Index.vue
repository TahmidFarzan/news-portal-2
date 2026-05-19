<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import ModelPagination from '@/components/common/model/Pagination.vue'
import MultiSelectInfinityLoadingApi from '@/components/common/multi-select/InfinityLoadingApi.vue'

import { ref, computed, onMounted, nextTick, inject } from 'vue'
import { Head, useForm, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faTrash, faFilter, faInfo,
    faPlus, faPen, faEye, faEyeSlash, faSpinner
} from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'
import { itemListFilterParameters } from '@/composables/useUtil'
import { fetchFromApi } from '@/composables/useSystemApi'

import { canEditUser, canDeleteUser, canActiveInactiveUser } from '@/composables/useAuthUserAccessPermissions'

FontAwesomeLibrary.add(faTrash, faFilter, faInfo, faPlus, faPen, faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const pageReady = inject("pageReady")
const authUser = inject("authUser")

const deletingRow = ref(null)
const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const activeProcessing = ref(null)
const inactiveProcessing = ref(null)

const { users } = defineProps({
    users: Object,
})

const paginationOnly = computed(() => {
    if (!users) return {}
    const { data, ...rest } = users
    return rest
})

const filterForm = useForm({
    per_page: null,
    created_by_id: null,
    user_role_id: null,
    date: '',
    search: '',
    is_active: null,
})

const applyFilter = () => {
    if (filterForm.processing) return

    const cleanParams = itemListFilterParameters(filterForm.data())
    intertiaJsRoute.get(route('back-office.users.index'), cleanParams, {
        replace: true,
        preserveScroll: true,
        preserveState: true,
        onFinish: () => filterForm.processing = false,
    })
}

const confirmDelete = (user) => {
    deletingRow.value = user
    showDeleteModal.value = true
}

const canEdit = (user) => canEditUser(authUser?.value, user)
const canDelete = (user) => canDeleteUser(authUser?.value, user)
const canActiveInactive = (user) => canActiveInactiveUser(authUser?.value, user)

const handleDelete = (user) => {
    if (!user || deleteProcessing.value) return

    deleteProcessing.value = true
    intertiaJsRoute.delete(route('back-office.users.delete', { slug: user?.slug }), {
        onFinish: () => {
            showDeleteModal.value = false
            deletingRow.value = null
            deleteProcessing.value = false
        }
    })
}

const handleInactive = (user) => {
    if (inactiveProcessing.value) return
    inactiveProcessing.value = user.slug

    intertiaJsRoute.patch(route('back-office.users.inactive', { slug: user?.slug }), {
        onFinish: () => inactiveProcessing.value = null,
    })
}

const handleActive = (user) => {
    if (activeProcessing.value) return
    activeProcessing.value = user.slug

    intertiaJsRoute.patch(route('back-office.users.active', { slug: user?.slug }), {
        onFinish: () => activeProcessing.value = null,
    })
}

onMounted(async () => {

    const urlParams = new URLSearchParams(window.location.search)

    filterForm.per_page = urlParams.get('per_page') || ''
    filterForm.created_by_id = urlParams.get('created_by_id') || ''
    filterForm.user_role_id = urlParams.get('user_role_id') || ''
    filterForm.date = urlParams.get('date') || ''
    filterForm.search = urlParams.get('search') || ''

    if (filterForm.created_by_id) {
        const rCreatedBy = await fetchFromApi(
            route('search.user', { slugOrId: filterForm.created_by_id })
        )
        filterForm.created_by_id = rCreatedBy || null
    }

    if (filterForm.user_role_id) {
        const rUserRole = await fetchFromApi(
            route('search.user-role', { slugOrId: filterForm.user_role_id })
        )
        filterForm.created_by_id = rUserRole || null
    }

    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: 'Users', active: true },
            ],
        })
    )

    pageReady.value = true
})
</script>

<template>

    <Head title="Users" />

    <div class="w-full space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">Users</h2>

            <a :href="route('back-office.users.create')"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                <FontAwesomeIcon icon="plus" />
                Create
            </a>
        </div>

        <form @submit.prevent="applyFilter" class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <MultiSelectInfinityLoadingApi v-if="pageReady" :form="filterForm" fieldName="per_page"
                    :selectedItem="filterForm.per_page" :apiUrl="route('search.per-pages')" :multiple="false"
                    placeholder="Per page" />

                <MultiSelectInfinityLoadingApi v-if="pageReady" :form="filterForm" fieldName="created_by_id"
                    :selectedItem="filterForm.created_by_id" :apiUrl="route('search.users')" :multiple="false"
                    placeholder="Created by" />

                <MultiSelectInfinityLoadingApi v-if="pageReady" :form="filterForm" fieldName="user_role_id"
                    :selectedItem="filterForm.user_role_id" :apiUrl="route('search.user-roles')" :multiple="false"
                    placeholder="User Role" />

                <input type="date" v-model="filterForm.date"
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />

                <input type="search" v-model="filterForm.search" placeholder="Search user..."
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />

                <div class="flex items-center gap-4 text-sm">
                    <label class="flex items-center gap-1">
                        <input type="radio" v-model="filterForm.is_active" :value="null" />
                        All
                    </label>
                    <label class="flex items-center gap-1">
                        <input type="radio" v-model="filterForm.is_active" :value="true" />
                        Active
                    </label>
                    <label class="flex items-center gap-1">
                        <input type="radio" v-model="filterForm.is_active" :value="false" />
                        Inactive
                    </label>
                </div>

            </div>

            <div class="flex justify-end">
                <button type="submit" :disabled="filterForm.processing"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon v-if="filterForm.processing" icon="spinner" spin />
                    <FontAwesomeIcon icon="filter" />
                    Apply Filter
                </button>
            </div>
        </form>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">

                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">#</th>
                            <th class="px-4 py-3 text-left">Name</th>
                            <th class="px-4 py-3 text-left">Role</th>
                            <th class="px-4 py-3 text-left">Created</th>
                            <th class="px-4 py-3 text-left">Active</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <tr v-for="(item, index) in users?.data" :key="item.id" class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">{{ index + 1 }}</td>
                            <td class="px-4 py-3 font-medium">{{ item.name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ item?.user_role?.name }}</td>
                            <td class="px-4 py-3 text-gray-500">
                                {{ formatDateTime(item.created_at) }}
                            </td>
                            <td class="px-4 py-3">
                                <span :class="item.is_active ? 'text-green-600' : 'text-red-500'"
                                    class="text-xs font-medium">
                                    {{ item.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">

                                    <a :href="route('back-office.users.details', { slug: item.slug })"
                                        class="p-2 rounded-md text-blue-600 hover:bg-blue-50 border">
                                        <FontAwesomeIcon icon="info" />
                                    </a>

                                    <a v-if="canEdit(item)" :href="route('back-office.users.edit', { slug: item.slug })"
                                        class="p-2 rounded-md text-yellow-600 hover:bg-yellow-50 border">
                                        <FontAwesomeIcon icon="pen" />
                                    </a>

                                    <button v-if="item.is_active && canActiveInactive(item)"
                                        @click="handleInactive(item)" :disabled="inactiveProcessing === item.slug"
                                        class="p-2 rounded-md text-gray-600 hover:bg-gray-100 border">
                                        <FontAwesomeIcon v-if="inactiveProcessing === item.slug" icon="spinner" spin />
                                        <FontAwesomeIcon v-else icon="eye-slash" />
                                    </button>

                                    <button v-if="!item.is_active && canActiveInactive(item)"
                                        @click="handleActive(item)" :disabled="activeProcessing === item.slug"
                                        class="p-2 rounded-md text-green-600 hover:bg-green-50 border">
                                        <FontAwesomeIcon v-if="activeProcessing === item.slug" icon="spinner" spin />
                                        <FontAwesomeIcon v-else icon="eye" />
                                    </button>

                                    <button v-if="canDelete(item)" @click="confirmDelete(item)"
                                        class="p-2 rounded-md text-red-600 hover:bg-red-50 border">
                                        <FontAwesomeIcon icon="trash" />
                                    </button>

                                </div>
                            </td>
                        </tr>
                    </tbody>

                </table>
            </div>
        </div>

        <ModelPagination :pagination="paginationOnly" />

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
                                Delete User
                            </h3>

                            <p class="text-sm font-medium">
                                {{ deletingRow?.name }}
                            </p>

                            <p class="text-sm text-gray-500">
                                This action cannot be undone.
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button @click="showDeleteModal = false"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    Cancel
                                </button>

                                <button @click="handleDelete(deletingRow)" :disabled="deleteProcessing"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2">
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
