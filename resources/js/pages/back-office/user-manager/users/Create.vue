<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import MultiSelectInfinityLoadingApi from '@/components/common/multi-select/InfinityLoadingApi.vue'

import { computed, onMounted, nextTick, inject } from 'vue'
import { Head, useForm, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faSave, faEye, faEyeSlash, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { VueTelInput } from 'vue-tel-input'

import { formatDate } from '@/composables/useDateTime'
import {
    showPassword,
    showConfirmPassword,
    togglePasswordVisibility,
    toggleConfirmPasswordVisibility,
} from '@/composables/usePassword'

import 'vue-tel-input/vue-tel-input.css'

FontAwesomeLibrary.add(faSave, faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const pageReady = inject("pageReady")

const { user } = defineProps({
    user: Object,
})

const isUpdate = computed(() => !!user?.slug)

const saveForm = useForm({
    name: user?.name || '',
    email: user?.email || '',
    gender: user?.gender || null,
    religion: user?.religion || null,
    birth_date: user?.birth_date ? formatDate(user?.birth_date, 'Y-m-d') : null,
    profession: user?.profession || '',
    marital_status: user?.marital_status || null,
    mobile: user?.mobile || '',
    address: user?.address || '',
    user_role_id: user?.user_role_id || null,

    password: '',
    password_confirmation: '',
    change_password: false,
    set_as_verify_email: false,
})

function validateForm() {
    saveForm.clearErrors()
    let valid = true

    if (!saveForm.name) {
        saveForm.setError('name', 'Name is required.')
        valid = false
    }

    if (!saveForm.email) {
        saveForm.setError('email', 'Email is required.')
        valid = false
    }

    if (!saveForm.gender) {
        saveForm.setError('gender', 'Gender is required.')
        valid = false
    }

    if (!saveForm.marital_status) {
        saveForm.setError('marital_status', 'Marital status is required.')
        valid = false
    }

    if (!saveForm.religion) {
        saveForm.setError('religion', 'Religion is required.')
        valid = false
    }

    if (!saveForm.user_role_id) {
        saveForm.setError('user_role_id', 'User role is required.')
        valid = false
    }

    if (saveForm.change_password) {
        if (!saveForm.password) {
            saveForm.setError('password', 'Password is required.')
            valid = false
        }

        if (!saveForm.password_confirmation) {
            saveForm.setError('password_confirmation', 'Password confirmation is required.')
            valid = false
        }
    }

    return valid
}


function handleSave() {
    if (saveForm.processing) return

    if (!validateForm()) return

    saveForm.processing = true

    const requestConfig = {
        preserveScroll: true,
        preserveState: true,
        forceFormData: true,
        onSuccess: () => {
            saveForm.reset()
            saveForm.clearErrors()
        },
        onError: (errors) => {
            saveForm.clearErrors()
            saveForm.setError(errors)
        },
        onFinish: () => {
            saveForm.processing = false
        }
    }

    if (isUpdate.value) {
        intertiaJsRoute.post(
            route('back-office.user-manager.users.update', { slug: user?.slug }),
            { ...saveForm.data(), _method: 'patch' },
            requestConfig
        )
    } else {
        saveForm.post(route('back-office.user-manager.users.save'), requestConfig)
    }
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: 'Dashboard', href: route('auth-user.dashboard.index') },
                { text: 'User manager', href: route('back-office.user-manager.index') },
                { text: isUpdate.value ? `${user?.name} edit` : 'Client create', active: true }
            ],
        })
    )

    pageReady.value = true
})
</script>

<template>

    <Head :title="isUpdate ? `${user?.name} edit` : 'Client create'" />

    <div class="w-full">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6">

            <form @submit.prevent="handleSave" class="space-y-4">

                <div class="grid md:grid-cols-2 gap-4">

                    <div>
                        <label class="block text-sm font-medium mb-1">Name *</label>
                        <input v-model="saveForm.name" class="border rounded px-3 py-2 w-full"
                            :class="saveForm.errors.name ? 'border-red-500' : 'border-gray-300'" />
                        <p v-if="saveForm.errors.name" class="text-red-500 text-sm">{{ saveForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Email *</label>
                        <input v-model="saveForm.email" type="email" class="border rounded px-3 py-2 w-full"
                            :class="saveForm.errors.email ? 'border-red-500' : 'border-gray-300'" />
                        <p v-if="saveForm.errors.email" class="text-red-500 text-sm">{{ saveForm.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Birth Date</label>
                        <input type="date" v-model="saveForm.birth_date" class="border rounded px-3 py-2 w-full" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Gender *</label>
                        <MultiSelectInfinityLoadingApi :form="saveForm" fieldName="gender"
                            :selectedItem="saveForm.gender" :apiUrl="route('search.genders')" :multiple="false"
                            placeholder="Select" :error="saveForm.errors.gender" v-if="pageReady" />
                        <p v-if="saveForm.errors.gender" class="text-red-500 text-sm">{{ saveForm.errors.gender }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Religion *</label>
                        <MultiSelectInfinityLoadingApi :form="saveForm" fieldName="religion"
                            :selectedItem="saveForm.religion" :apiUrl="route('search.religions')" :multiple="false"
                            placeholder="Select" :error="saveForm.errors.religion" v-if="pageReady" />
                        <p v-if="saveForm.errors.religion" class="text-red-500 text-sm">{{ saveForm.errors.religion }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Marital Status *</label>
                        <MultiSelectInfinityLoadingApi :form="saveForm" fieldName="marital_status"
                            :selectedItem="saveForm.marital_status" :apiUrl="route('search.marital-statuses')"
                            :multiple="false" placeholder="Select" :error="saveForm.errors.marital_status"
                            v-if="pageReady" />
                        <p v-if="saveForm.errors.marital_status" class="text-red-500 text-sm">{{
                            saveForm.errors.marital_status }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Mobile</label>
                        <VueTelInput v-model="saveForm.mobile"
                            :class="saveForm.errors.mobile ? 'border border-red-500 rounded' : ''" />
                        <p v-if="saveForm.errors.mobile" class="text-red-500 text-sm">{{ saveForm.errors.mobile }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Profession</label>
                        <input v-model="saveForm.profession" class="border rounded px-3 py-2 w-full" />
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1">Address</label>
                        <textarea v-model="saveForm.address" rows="3"
                            class="border rounded px-3 py-2 w-full resize-none"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">User Role *</label>
                        <MultiSelectInfinityLoadingApi :form="saveForm" fieldName="user_role_id"
                            :selectedItem="user?.user_role" :apiUrl="route('search.user-roles')" :multiple="false"
                            placeholder="Select" :error="saveForm.errors.user_role_id" v-if="pageReady" />
                        <p v-if="saveForm.errors.user_role_id" class="text-red-500 text-sm">{{
                            saveForm.errors.user_role_id }}</p>
                    </div>

                    <div class="flex gap-6 items-center md:col-span-2">

                        <label class="flex items-center gap-2">
                            <input type="checkbox" v-model="saveForm.set_as_verify_email" />
                            <span class="text-sm">Set as verify email</span>
                        </label>

                        <label class="flex items-center gap-2">
                            <input type="checkbox" v-model="saveForm.change_password" />
                            <span class="text-sm">Change password</span>
                        </label>

                    </div>

                    <div v-if="saveForm.change_password" class="grid md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium mb-1">New Password *</label>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'" v-model="saveForm.password"
                                    class="border rounded px-3 py-2 w-full pr-10" />
                                <button type="button" @click="togglePasswordVisibility" class="absolute right-2 top-2">
                                    <FontAwesomeIcon :icon="showPassword ? 'eye-slash' : 'eye'" />
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Confirm Password *</label>
                            <div class="relative">
                                <input :type="showConfirmPassword ? 'text' : 'password'"
                                    v-model="saveForm.password_confirmation"
                                    class="border rounded px-3 py-2 w-full pr-10" />
                                <button type="button" @click="toggleConfirmPasswordVisibility"
                                    class="absolute right-2 top-2">
                                    <FontAwesomeIcon :icon="showConfirmPassword ? 'eye-slash' : 'eye'" />
                                </button>
                            </div>
                        </div>

                    </div>

                    <div class="text-center pt-2">
                        <button type="submit" :disabled="saveForm.processing"
                            class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded flex items-center gap-2 mx-auto">
                            <FontAwesomeIcon v-if="saveForm.processing" icon="spinner" spin />
                            <FontAwesomeIcon v-else icon="save" />
                            Save
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>
</template>
