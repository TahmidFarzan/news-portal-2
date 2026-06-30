<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import ModelPagination from '@/components/common/model/Pagination.vue'
import SelectInfinityLoadingApi from '@/components/common/multi-select/SelectInfinityLoadingApi.vue'

import { ref, computed, onMounted, nextTick, inject } from 'vue'
import { Head, useForm, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import {
    faTrash,
    faFilter,
    faInfo,
    faPlus,
    faPen,
    faEye,
    faEyeSlash,
    faSpinner
} from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'
import { itemListFilterParameters } from '@/composables/useDataTable'
import { fetchFromApi } from '@/composables/useSystemApi'
import { useTranslate } from '@/composables/useTranslate'

import {
    canUpdateUser,
    canDeleteUser,
    canActiveInactiveUser
} from '@/composables/useUserPermissions'

FontAwesomeLibrary.add(
    faTrash,
    faFilter,
    faInfo,
    faPlus,
    faPen,
    faEye,
    faEyeSlash,
    faSpinner
)

defineOptions({
    layout: Layout
})

const { t } = useTranslate()

const authUser = inject('authUser')

const deletingRow = ref(null)
const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const statusRow = ref(null)
const statusAction = ref(null)
const showStatusModal = ref(false)

const activeProcessing = ref(null)
const inactiveProcessing = ref(null)

const { users } = defineProps({
    users: Object
})

const paginationOnly = computed(() => {
    if (!users) return {}

    const { data, ...rest } = users

    return rest
})

const filterForm = useForm({
    per_page: null,
    created_by_id: null,
    user_permission_id: null,
    date: '',
    search: '',
    is_active: null
})

const applyFilter = () => {
    if (filterForm.processing) return

    const cleanParams = itemListFilterParameters(
        filterForm.data()
    )

    intertiaJsRoute.get(
        route('back-office.users.index'),
        cleanParams,
        {
            replace: true,
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                filterForm.processing = false
            }
        }
    )
}

const confirmDelete = (user) => {
    deletingRow.value = user
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    deletingRow.value = null
    showDeleteModal.value = false
}

const confirmStatus = (user, action) => {
    statusRow.value = user
    statusAction.value = action
    showStatusModal.value = true
}

const closeStatusModal = () => {
    statusRow.value = null
    statusAction.value = null
    showStatusModal.value = false
}

const canUpdate = (user) => canUpdateUser(authUser?.value, user)
const canDelete = (user) => canDeleteUser(authUser?.value, user)
const canActiveInactive = (user) => canActiveInactiveUser(authUser?.value, user)

const handleDelete = (user) => {
    if (!user || deleteProcessing.value) return

    deleteProcessing.value = true

    intertiaJsRoute.delete(
        route(
            'back-office.users.delete',
            {
                slug: user.slug
            }
        ),
        {
            onFinish: () => {
                deleteProcessing.value = false
                closeDeleteModal()
            }
        }
    )
}

const executeStatusAction = () => {
    if (!statusRow.value) return

    const slug = statusRow.value.slug
    const isInactive =
        statusAction.value === 'inactive'

    if (isInactive) {
        inactiveProcessing.value = slug
    } else {
        activeProcessing.value = slug
    }

    intertiaJsRoute.patch(
        route(
            isInactive
                ? 'back-office.users.inactive'
                : 'back-office.users.active',
            { slug }
        ),
        {},
        {
            preserveScroll: true,

            onSuccess: () => {
                closeStatusModal()
            },

            onFinish: () => {
                activeProcessing.value = null
                inactiveProcessing.value = null
            },

            onError: () => {
                activeProcessing.value = null
                inactiveProcessing.value = null
            }
        }
    )
}

onMounted(async () => {
    const urlParams = new URLSearchParams( window.location.search )

    filterForm.per_page = urlParams.get('per_page') || ''
    filterForm.created_by_id = urlParams.get( 'created_by_id' ) || ''
    filterForm.user_permission_id = urlParams.get( 'user_permission_id' ) || ''
    filterForm.date = urlParams.get('date') || ''
    filterForm.search = urlParams.get('search') || ''

    if (filterForm.created_by_id) {
        filterForm.created_by_id = await fetchFromApi( route( 'search.user', { slugOrId: filterForm.created_by_id}))
    }

    if ( filterForm.user_permission_id ) {
        filterForm.user_permission_id = await fetchFromApi( route( 'search.user-permission', { slugOrId: filterForm.user_permission_id } ) )
    }

    await nextTick()

    window.dispatchEvent(
        new CustomEvent(
            'set-breadcrumb',
            {
                detail: [
                    {
                        text: t( 'pages.back_office.users.index.labels.users' ), active: true
                    }
                ]
            }
        )
    )
})
</script>

<template>

    <Head :title="t('pages.back_office.users.index.labels.users')" />

    <div class="w-full space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">
                {{ t('pages.back_office.users.index.labels.users') }}
            </h2>

            <a :href="route('back-office.users.create')"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                <FontAwesomeIcon icon="plus" />
                {{ t('pages.back_office.users.index.actions.create') }}
            </a>
        </div>

        <form @submit.prevent="applyFilter" class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <SelectInfinityLoadingApi :form="filterForm" fieldName="per_page"
                    :selectedItem="filterForm.per_page" :apiUrl="route('search.per-pages')" :multiple="false"
                    :placeholder="t('pages.back_office.users.index.labels.per_page')" />

                <SelectInfinityLoadingApi :form="filterForm" fieldName="created_by_id"
                    :selectedItem="filterForm.created_by_id" :apiUrl="route('search.users')" :multiple="false"
                    :placeholder="t('pages.back_office.users.index.labels.created_by')" />

                <SelectInfinityLoadingApi :form="filterForm" fieldName="user_permission_id"
                    :selectedItem="filterForm.user_permission_id" :apiUrl="route('search.user-permissions')"
                    :multiple="false" :placeholder="t('pages.back_office.users.index.form.user_permission')" />

                <input type="date" v-model="filterForm.date"
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />

                <input type="search" v-model="filterForm.search"
                    :placeholder="t('pages.back_office.users.index.search_placeholder')"
                    class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />

                <div class="flex items-center gap-4 text-sm">
                    <label class="flex items-center gap-1">
                        <input type="radio" v-model="filterForm.is_active" :value="null" />
                        {{ t('pages.back_office.users.index.labels.all') }}
                    </label>

                    <label class="flex items-center gap-1">
                        <input type="radio" v-model="filterForm.is_active" :value="true" />
                        {{ t('pages.back_office.users.index.buttons.active') }}
                    </label>

                    <label class="flex items-center gap-1">
                        <input type="radio" v-model="filterForm.is_active" :value="false" />
                        {{ t('pages.back_office.users.index.buttons.inactive') }}
                    </label>
                </div>

            </div>

            <div class="flex justify-end">
                <button type="submit" :disabled="filterForm.processing"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md flex items-center gap-2 transition disabled:opacity-60 disabled:cursor-not-allowed">
                    <FontAwesomeIcon v-if="filterForm.processing" icon="spinner" spin />
                    <FontAwesomeIcon icon="filter" />
                    {{ filterForm.processing ? t('pages.back_office.users.index.applying_filter') :
                        t('pages.back_office.users.index.apply_filter') }}
                </button>
            </div>
        </form>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">

                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">#</th>
                            <th class="px-4 py-3 text-left">{{ t('pages.back_office.users.index.labels.name') }}</th>
                            <th class="px-4 py-3 text-left">{{ t('pages.back_office.users.index.created') }}</th>
                            <th class="px-4 py-3 text-left">{{ t('pages.back_office.users.index.buttons.active') }}</th>
                            <th class="px-4 py-3 text-right">{{ t('pages.back_office.users.index.news.index.actions') }}
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <tr v-for="(item, index) in users?.data" :key="item.id" class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">{{ index + 1 }}</td>

                            <td class="px-4 py-3 font-medium">
                                {{ item.name || t('pages.back_office.users.index.labels.not_available') }}
                            </td>

                            <td class="px-4 py-3 text-gray-500">
                                {{ item.created_at ? formatDateTime(item.created_at) :
                                    t('pages.back_office.users.index.labels.not_available') }}
                            </td>

                            <td class="px-4 py-3">
                                <span :class="item.is_active ? 'text-green-600' : 'text-red-500'"
                                    class="text-xs font-medium">
                                    {{ item.is_active ? t('pages.back_office.users.index.buttons.active') :
                                        t('pages.back_office.users.index.buttons.inactive') }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">

                                    <a :href="route('back-office.users.details', { slug: item.slug })"
                                        class="p-2 rounded-md text-blue-600 hover:bg-blue-50 border"
                                        :title="t('pages.back_office.users.index.table.menus.details')">
                                        <FontAwesomeIcon icon="info" />
                                    </a>

                                    <a v-if="canUpdate(item)" :href="route('back-office.users.edit', { slug: item.slug })"
                                        class="p-2 rounded-md text-yellow-600 hover:bg-yellow-50 border"
                                        :title="t('pages.back_office.users.index.actions.edit')">
                                        <FontAwesomeIcon icon="pen" />
                                    </a>

                                    <button v-if="item.is_active && canActiveInactive(item)" type="button"
                                        @click="confirmStatus(item, 'inactive')"
                                        :disabled="inactiveProcessing === item.slug"
                                        class="p-2 rounded-md text-gray-600 hover:bg-gray-100 border disabled:opacity-60 disabled:cursor-not-allowed"
                                        :title="t('pages.back_office.users.index.buttons.inactive')">
                                        <FontAwesomeIcon v-if="inactiveProcessing === item.slug" icon="spinner" spin />
                                        <FontAwesomeIcon v-else icon="eye-slash" />
                                    </button>

                                    <button v-if="!item.is_active && canActiveInactive(item)" type="button"
                                        @click="confirmStatus(item, 'active')"
                                        :disabled="activeProcessing === item.slug"
                                        class="p-2 rounded-md text-green-600 hover:bg-green-50 border disabled:opacity-60 disabled:cursor-not-allowed"
                                        :title="t('pages.back_office.users.index.buttons.active')">
                                        <FontAwesomeIcon v-if="activeProcessing === item.slug" icon="spinner" spin />
                                        <FontAwesomeIcon v-else icon="eye" />
                                    </button>

                                    <button v-if="canDelete(item)" type="button" @click="confirmDelete(item)"
                                        class="p-2 rounded-md text-red-600 hover:bg-red-50 border"
                                        :title="t('pages.back_office.users.index.actions.delete')">
                                        <FontAwesomeIcon icon="trash" />
                                    </button>

                                </div>
                            </td>
                        </tr>

                        <tr v-if="!users?.data?.length">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                {{ t('pages.back_office.users.index.no_user_found') }}
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
                                {{ t('pages.back_office.users.index.delete_modal.title') }}
                            </h3>

                            <p class="text-sm font-medium">
                                {{ deletingRow?.name }}
                            </p>

                            <p class="text-sm text-gray-500">
                                {{ t('pages.back_office.users.index.modals.delete_confirmation_modal.irreversible_body')
                                }}
                            </p>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="closeDeleteModal"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm">
                                    {{ t('pages.back_office.users.index.actions.cancel') }}
                                </button>

                                <button type="button" @click="handleDelete(deletingRow)" :disabled="deleteProcessing"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-sm flex items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                                    <FontAwesomeIcon v-if="deleteProcessing" icon="spinner" spin />
                                    {{ deleteProcessing ? t('pages.back_office.users.index.actions.deleting') :
                                        t('pages.back_office.users.index.actions.delete') }}
                                </button>
                            </div>
                        </div>
                    </Transition>

                </div>
            </Transition>

            <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showStatusModal"
                    class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50">
                    <div class="bg-white rounded-xl shadow-lg w-[380px] p-6 space-y-4">
                        <h3 class="text-lg font-semibold">
                            {{
                                statusAction === 'active'
                                    ? t('pages.back_office.users.index.buttons.active')
                                    : t('pages.back_office.users.index.buttons.inactive')
                            }}
                        </h3>

                        <p class="font-medium">
                            {{ statusRow?.name }}
                        </p>

                        <p class="text-sm text-gray-500">
                            {{
                                statusAction === 'active'
                                    ? 'Are you sure you want to activate this user?'
                                    : 'Are you sure you want to inactive this user?'
                            }}
                        </p>

                        <div class="flex justify-end gap-2">
                            <button @click="closeStatusModal" class="px-4 py-2 bg-gray-100 rounded">
                                {{
                                    t('pages.back_office.users.index.actions.cancel')
                                }}
                            </button>

                            <button type="button" @click="executeStatusAction" :disabled="activeProcessing ||
                                inactiveProcessing
                                " :class="[
                                    statusAction === 'inactive'
                                        ? 'bg-yellow-500 hover:bg-yellow-600'
                                        : 'bg-green-500 hover:bg-green-600',
                                    'px-4 py-2 text-white rounded-md text-sm flex items-center gap-2 transition disabled:opacity-60 disabled:cursor-not-allowed'
                                ]">
                                <FontAwesomeIcon v-if="
                                    activeProcessing ||
                                    inactiveProcessing
                                " icon="spinner" spin />

                                {{
                                    activeProcessing || inactiveProcessing
                                        ? (
                                            statusAction === 'inactive'
                                                ? 'Inactivating...'
                                                : 'Activating...'
                                        )
                                        : (
                                            statusAction === 'inactive'
                                                ? t('pages.back_office.users.index.buttons.inactive')
                                                : t('pages.back_office.users.index.buttons.active')
                                        )
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
