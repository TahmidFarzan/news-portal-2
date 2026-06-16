<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import MultiSelectInfinityLoadingApi from '@/components/common/multi-select/InfinityLoadingApi.vue'

import { computed, onMounted, nextTick } from 'vue'
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
import { useTranslate } from '@/composables/useTranslate'

import 'vue-tel-input/vue-tel-input.css'

FontAwesomeLibrary.add(faSave, faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const { user } = defineProps({
    user: Object,
})

const isUpdate = computed(() => !!user?.slug)

const pageTitle = computed(() => {
    return isUpdate.value
        ? `${user?.name} ${t('buttons.edit')}`
        : t('users.form.create_page_title')
})

const saveForm = useForm({
    name: user?.name || '',
    email: user?.email || '',
    gender: user?.gender || null,
    religion: user?.religion || null,
    birth_date: user?.birth_date ? formatDate(user?.birth_date, 'Y-m-d') : null,
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
        saveForm.setError('name', t('form.validation_errors.name_is_required'))
        valid = false
    }

    if (!saveForm.email) {
        saveForm.setError('email', t('form.validation_errors.email_is_required'))
        valid = false
    }

    if (!saveForm.gender) {
        saveForm.setError('gender', t('form.validation_errors.gender_is_required'))
        valid = false
    }

    if (!saveForm.marital_status) {
        saveForm.setError('marital_status', t('form.validation_errors.marital_status_is_required'))
        valid = false
    }

    if (!saveForm.religion) {
        saveForm.setError('religion', t('form.validation_errors.religion_is_required'))
        valid = false
    }

    if (!saveForm.user_role_id) {
        saveForm.setError('user_role_id', t('form.validation_errors.user_role_is_required'))
        valid = false
    }

    if (saveForm.change_password) {
        if (!saveForm.password) {
            saveForm.setError('password', t('form.validation_errors.password_is_required'))
            valid = false
        }

        if (!saveForm.password_confirmation) {
            saveForm.setError('password_confirmation', t('form.validation_errors.password_confirmation_is_required'))
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
            route('back-office.users.update', { slug: user?.slug }),
            { ...saveForm.data(), _method: 'patch' },
            requestConfig
        )
    } else {
        saveForm.post(route('back-office.users.save'), requestConfig)
    }
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: t('labels.users'), href: route('back-office.users.index') },
                { text: pageTitle.value, active: true }
            ],
        })
    )
})
</script>

<template>

    <Head :title="pageTitle" />

    <div class="w-full">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6">

            <form @submit.prevent="handleSave" class="space-y-6">

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">
                        {{ t('labels.basic_information') }}
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('labels.name') }} <span class="text-red-500">*</span>
                            </label>

                            <input v-model="saveForm.name" :placeholder="t('users.form.name_placeholder')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.name ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.name" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('labels.email') }} <span class="text-red-500">*</span>
                            </label>

                            <input v-model="saveForm.email" type="email"
                                :placeholder="t('users.form.email_placeholder')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.email ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.email" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.email }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('labels.birth_date') }}
                            </label>

                            <input type="date" v-model="saveForm.birth_date"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none border-gray-300" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('labels.gender') }} <span class="text-red-500">*</span>
                            </label>

                            <MultiSelectInfinityLoadingApi :form="saveForm" fieldName="gender"
                                :selectedItem="saveForm.gender" :apiUrl="route('search.genders')" :multiple="false"
                                :placeholder="t('buttons.select')" :error="saveForm.errors.gender" />

                            <p v-if="saveForm.errors.gender" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.gender }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('labels.religion') }} <span class="text-red-500">*</span>
                            </label>

                            <MultiSelectInfinityLoadingApi :form="saveForm" fieldName="religion"
                                :selectedItem="saveForm.religion" :apiUrl="route('search.religions')" :multiple="false"
                                :placeholder="t('buttons.select')" :error="saveForm.errors.religion" />

                            <p v-if="saveForm.errors.religion" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.religion }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('labels.marital_status') }} <span class="text-red-500">*</span>
                            </label>

                            <MultiSelectInfinityLoadingApi :form="saveForm" fieldName="marital_status"
                                :selectedItem="saveForm.marital_status" :apiUrl="route('search.marital-statuses')"
                                :multiple="false" :placeholder="t('buttons.select')"
                                :error="saveForm.errors.marital_status" />

                            <p v-if="saveForm.errors.marital_status" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.marital_status }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('labels.mobile') }}
                            </label>

                            <VueTelInput v-model="saveForm.mobile" class="w-full border rounded-md px-2 py-1"
                                :class="saveForm.errors.mobile ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.mobile" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.mobile }}
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">
                                {{ t('labels.address') }}
                            </label>

                            <textarea v-model="saveForm.address" rows="3"
                                :placeholder="t('users.form.address_placeholder')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none border-gray-300"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('users.form.user_role') }} <span class="text-red-500">*</span>
                            </label>

                            <MultiSelectInfinityLoadingApi :form="saveForm" fieldName="user_role_id"
                                :selectedItem="user?.user_role" :apiUrl="route('search.user-roles')" :multiple="false"
                                :placeholder="t('buttons.select')" :error="saveForm.errors.user_role_id" />

                            <p v-if="saveForm.errors.user_role_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.user_role_id }}
                            </p>
                        </div>

                        <div class="md:col-span-2 flex gap-6 items-center pt-2">

                            <label class="flex items-center gap-2">
                                <input type="checkbox" v-model="saveForm.set_as_verify_email" />
                                <span class="text-sm">{{ t('users.form.set_as_verify_email') }}</span>
                            </label>

                            <label class="flex items-center gap-2">
                                <input type="checkbox" v-model="saveForm.change_password" />
                                <span class="text-sm">{{ t('auth.account.change_password') }}</span>
                            </label>

                        </div>

                    </div>
                </div>

                <div v-if="saveForm.change_password" class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">
                        {{ t('auth.account.change_password') }}
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('auth.account.new_password') }} <span class="text-red-500">*</span>
                            </label>

                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'" v-model="saveForm.password"
                                    :placeholder="t('users.form.new_password_placeholder')"
                                    class="w-full border rounded-md px-3 py-2 text-sm pr-10 focus:ring-2 focus:ring-blue-500 focus:outline-none border-gray-300" />

                                <button type="button" @click="togglePasswordVisibility" class="absolute right-2 top-2"
                                    :title="showPassword ? t('auth.account.hide_password') : t('auth.account.show_password')">
                                    <FontAwesomeIcon :icon="showPassword ? 'eye-slash' : 'eye'" />
                                </button>
                            </div>

                            <p v-if="saveForm.errors.password" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.password }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('auth.account.confirm_password') }} <span class="text-red-500">*</span>
                            </label>

                            <div class="relative">
                                <input :type="showConfirmPassword ? 'text' : 'password'"
                                    v-model="saveForm.password_confirmation"
                                    :placeholder="t('users.form.confirm_password_placeholder')"
                                    class="w-full border rounded-md px-3 py-2 text-sm pr-10 focus:ring-2 focus:ring-blue-500 focus:outline-none border-gray-300" />

                                <button type="button" @click="toggleConfirmPasswordVisibility"
                                    class="absolute right-2 top-2"
                                    :title="showConfirmPassword ? t('auth.account.hide_confirm_password') : t('auth.account.show_confirm_password')">
                                    <FontAwesomeIcon :icon="showConfirmPassword ? 'eye-slash' : 'eye'" />
                                </button>
                            </div>

                            <p v-if="saveForm.errors.password_confirmation" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.password_confirmation }}
                            </p>
                        </div>

                    </div>
                </div>

                <div class="flex justify-center">
                    <button type="submit" :disabled="saveForm.processing"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md flex items-center gap-2 transition disabled:opacity-60 disabled:cursor-not-allowed">
                        <FontAwesomeIcon v-if="saveForm.processing" icon="spinner" spin />
                        <FontAwesomeIcon v-else icon="save" />
                        {{ saveForm.processing ? t('buttons.saving') : t('buttons.save') }}
                    </button>
                </div>

            </form>

        </div>
    </div>
</template>
