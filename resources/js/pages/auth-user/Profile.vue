<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import RecentActivities from '@/components/back-office/activity-log/RecentModelActivityLogs.vue'
import SelectInfinityLoadingApi from '@/components/common/multi-select/SelectInfinityLoadingApi.vue'
import MediaRenderer from '@/components/common/media/MediaRenderer.vue'

import { ref, onMounted, nextTick } from 'vue'
import { Head, useForm, router as inertiaJsRoute } from '@inertiajs/vue3'
import { useTranslate } from '@/composables/useTranslate'

import { VueTelInput } from 'vue-tel-input'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faPlus, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { formatDate } from '@/composables/useDateTime'

import 'vue-tel-input/vue-tel-input.css'

FontAwesomeLibrary.add(faPlus, faSpinner)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const { user } = defineProps({
    user: Object,
})

const activeTab = ref('info')

const profileUpdateForm = useForm({
    name: user?.name || '',
    gender: user?.gender || null,
    religion: user?.religion || null,
    birth_date: user?.birth_date ? formatDate(user?.birth_date, 'Y-m-d') : null,
    marital_status: user?.marital_status || null,
    mobile: user?.mobile || '',
    profile_image: null,
    address: user?.address || '',
})

function validateForm() {
    profileUpdateForm.clearErrors()

    let valid = true

    if (!profileUpdateForm.name || profileUpdateForm.name.trim() === '') {
        profileUpdateForm.setError('name', t('common.validation.nameIsRequired'))
        valid = false
    }

    if (!profileUpdateForm.gender) {
        profileUpdateForm.setError('gender', t('common.validation.genderIsRequired'))
        valid = false
    }

    if (!profileUpdateForm.marital_status) {
        profileUpdateForm.setError('marital_status', t('common.validation.maritalStatusIsRequired'))
        valid = false
    }

    if (!profileUpdateForm.religion) {
        profileUpdateForm.setError('religion', t('common.validation.religionIsRequired'))
        valid = false
    }

    return valid
}

function handleProfileUpdate() {
    if (profileUpdateForm.processing) return
    if (!validateForm()) return

    const payload = {
        ...profileUpdateForm.data(),
        _method: 'patch',
    }

    inertiaJsRoute.post(route('auth-user.profile.update'), payload, {
        forceFormData: true,
        preserveScroll: true,
        preserveState: true,

        onSuccess: () => {
            profileUpdateForm.clearErrors()
        },

        onError: (errors) => {
            profileUpdateForm.clearErrors()
            profileUpdateForm.setError(errors)
        },
    })
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: t('common.labels.profile'), active: true },
            ],
        })
    )
})
</script>

<template>

    <Head :title="t('common.labels.profile')" />

    <div class="w-full">

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6">

            <div class="border-b border-gray-200 flex gap-4 px-2">

                <button @click="activeTab = 'info'" class="px-4 py-2 text-sm font-medium transition" :class="activeTab === 'info'
                    ? 'text-blue-600 border-b-2 border-blue-500'
                    : 'text-gray-500 hover:text-gray-700'">
                    {{ t('common.labels.profile') }}
                </button>

                <button @click="activeTab = 'update'" class="px-4 py-2 text-sm font-medium transition" :class="activeTab === 'update'
                    ? 'text-blue-600 border-b-2 border-blue-500'
                    : 'text-gray-500 hover:text-gray-700'">
                    {{ t('common.actions.update') }}
                </button>

                <button @click="activeTab = 'logs'" class="px-4 py-2 text-sm font-medium transition" :class="activeTab === 'logs'
                    ? 'text-blue-600 border-b-2 border-blue-500'
                    : 'text-gray-500 hover:text-gray-700'">
                    {{ t('common.labels.activityLogs') }}
                </button>

            </div>

            <transition enter-active-class="transition duration-200" enter-from-class="opacity-0 translate-y-1"
                enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150"
                leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-1" mode="out-in">

                <div v-if="activeTab === 'info'" key="profile" class="p-6">

                    <div class="flex flex-col md:flex-row gap-6">

                        <div class="w-32 h-32">
                            <MediaRenderer v-if="user?.profile_image" :media="user?.profile_image" />

                            <img v-else :src="'/uploads/icons/auth/user.png'" :alt="t('common.messages.userProfileImage')"
                                class="object-cover rounded-xl border border-gray-200" />
                        </div>

                        <div class="space-y-2 text-sm">
                            <div>
                                <span class="font-medium text-gray-600">
                                    {{ t('common.labels.name') }}:
                                </span>
                                {{ user?.name }}
                            </div>

                            <div>
                                <span class="font-medium text-gray-600">
                                    {{ t('common.labels.gender') }}:
                                </span>
                                {{ user?.gender || t('common.labels.notAvailable') }}
                            </div>

                            <div>
                                <span class="font-medium text-gray-600">
                                    {{ t('common.labels.maritalStatus') }}:
                                </span>
                                {{ user?.marital_status || t('common.labels.notAvailable') }}
                            </div>

                            <div>
                                <span class="font-medium text-gray-600">
                                    {{ t('common.labels.mobile') }}:
                                </span>
                                {{ user?.mobile || t('common.labels.notAvailable') }}
                            </div>

                            <div>
                                <span class="font-medium text-gray-600">
                                    {{ t('common.labels.birthDate') }}:
                                </span>
                                {{ user?.birth_date || t('common.labels.notAvailable') }}
                            </div>

                            <div v-if="user?.birth_date">
                                <span class="font-medium text-gray-600">
                                    {{ t('common.labels.age') }}:
                                </span>
                                {{ user?.age }}
                            </div>

                            <div>
                                <span class="font-medium text-gray-600">
                                    {{ t('common.labels.address') }}:
                                </span>
                                {{ user?.address || t('common.labels.notAvailable') }}
                            </div>
                        </div>

                    </div>

                </div>

                <div v-else-if="activeTab === 'update'" key="update" class="p-6">

                    <form @submit.prevent="handleProfileUpdate" class="space-y-4">

                        <div class="grid md:grid-cols-2 gap-4">

                            <div>
                                <label class="block text-sm font-medium mb-1">
                                    {{ t('common.labels.name') }}
                                    <span class="text-red-500">*</span>
                                </label>

                                <input v-model="profileUpdateForm.name" class="border rounded px-3 py-2 w-full"
                                    :class="profileUpdateForm.errors.name ? 'border-red-500' : 'border-gray-300'"
                                    :placeholder="t('common.placeholders.enterName')" />

                                <p v-if="profileUpdateForm.errors.name" class="text-red-500 text-sm mt-1">
                                    {{ profileUpdateForm.errors.name }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">
                                    {{ t('common.labels.gender') }}
                                    <span class="text-red-500">*</span>
                                </label>

                                <SelectInfinityLoadingApi :form="profileUpdateForm" fieldName="gender"
                                    :selectedItem="profileUpdateForm.gender" :apiUrl="route('search.genders')"
                                    :multiple="false" selectedLabelKey="name" selectedValueKey="id" apiLabelKey="name"
                                    apiValueKey="id" :placeholder="t('profile.genderPlaceholder')"
                                    :error="profileUpdateForm.errors.gender" />

                                <p v-if="profileUpdateForm.errors.gender" class="text-red-500 text-sm mt-1">
                                    {{ profileUpdateForm.errors.gender }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">
                                    {{ t('common.labels.religion') }}
                                    <span class="text-red-500">*</span>
                                </label>

                                <SelectInfinityLoadingApi :form="profileUpdateForm" fieldName="religion"
                                    :selectedItem="profileUpdateForm.religion" :apiUrl="route('search.religions')"
                                    :multiple="false" selectedLabelKey="name" selectedValueKey="id" apiLabelKey="name"
                                    apiValueKey="id" :placeholder="t('profile.religionPlaceholder')"
                                    :error="profileUpdateForm.errors.religion" />

                                <p v-if="profileUpdateForm.errors.religion" class="text-red-500 text-sm mt-1">
                                    {{ profileUpdateForm.errors.religion }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">
                                    {{ t('common.labels.maritalStatus') }}
                                    <span class="text-red-500">*</span>
                                </label>

                                <SelectInfinityLoadingApi :form="profileUpdateForm" fieldName="marital_status"
                                    :selectedItem="profileUpdateForm.marital_status"
                                    :apiUrl="route('search.marital-statuses')" :multiple="false" selectedLabelKey="name"
                                    selectedValueKey="id" apiLabelKey="name" apiValueKey="id"
                                    :placeholder="t('profile.maritalStatusPlaceholder')"
                                    :error="profileUpdateForm.errors.marital_status" />

                                <p v-if="profileUpdateForm.errors.marital_status" class="text-red-500 text-sm mt-1">
                                    {{ profileUpdateForm.errors.marital_status }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">
                                    {{ t('common.labels.birthDate') }}
                                </label>

                                <input v-model="profileUpdateForm.birth_date" type="date"
                                    class="border rounded px-3 py-2 w-full"
                                    :class="profileUpdateForm.errors.birth_date ? 'border-red-500' : 'border-gray-300'" />

                                <p v-if="profileUpdateForm.errors.birth_date" class="text-red-500 text-sm mt-1">
                                    {{ profileUpdateForm.errors.birth_date }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">
                                    {{ t('common.labels.mobile') }}
                                </label>

                                <VueTelInput v-model="profileUpdateForm.mobile"
                                    :class="profileUpdateForm.errors.mobile ? 'border border-red-500 rounded' : ''" />

                                <p v-if="profileUpdateForm.errors.mobile" class="text-red-500 text-sm mt-1">
                                    {{ profileUpdateForm.errors.mobile }}
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium mb-1">
                                    {{ t('common.labels.address') }}
                                </label>

                                <textarea v-model="profileUpdateForm.address" rows="3"
                                    class="border rounded px-3 py-2 w-full resize-none"
                                    :class="profileUpdateForm.errors.address ? 'border-red-500' : 'border-gray-300'"
                                    :placeholder="t('common.placeholders.enterAddress')"></textarea>

                                <p v-if="profileUpdateForm.errors.address" class="text-red-500 text-sm mt-1">
                                    {{ profileUpdateForm.errors.address }}
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium mb-1">
                                    {{ t('common.labels.profileImage') }}
                                </label>

                                <input type="file" class="border rounded px-3 py-2 w-full"
                                    :class="profileUpdateForm.errors.profile_image ? 'border-red-500' : 'border-gray-300'"
                                    @change="e => profileUpdateForm.profile_image = e.target.files[0]" />

                                <p v-if="profileUpdateForm.errors.profile_image" class="text-red-500 text-sm mt-1">
                                    {{ profileUpdateForm.errors.profile_image }}
                                </p>
                            </div>

                        </div>

                        <button type="submit" :disabled="profileUpdateForm.processing"
                            class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white px-4 py-2 rounded flex items-center gap-2">
                            <FontAwesomeIcon v-if="profileUpdateForm.processing" icon="spinner" spin />

                            {{
                                profileUpdateForm.processing
                                    ? t('common.messages.updating')
                                    : t('common.actions.update')
                            }}
                        </button>

                    </form>

                </div>

                <div v-else key="logs" class="p-4">
                    <RecentActivities :model-slug="'user'" :model="user" />
                </div>

            </transition>

        </div>

    </div>
</template>
