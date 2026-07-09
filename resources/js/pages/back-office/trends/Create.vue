<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import SelectInfinityLoadingApi from '@/components/common/multi-select/SelectInfinityLoadingApi.vue'

import { computed, onMounted, nextTick } from 'vue'
import { Head, useForm, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faSave, faEye, faEyeSlash, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faSave, faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const { trend } = defineProps({
    trend: Object,
})

const isUpdate = computed(() => !!trend?.slug)

const pageTitle = computed(() => {
    return isUpdate.value
        ? `${trend?.tag?.name} ${t('common.actions.edit')}`
        : t('admin.trends.create.form.createPageTitle')
})

const saveForm = useForm({
    is_current: trend?.is_current || false,
    tag_id: trend?.tag_id || null,
})

function validateForm() {
    saveForm.clearErrors()

    let valid = true

    if (!saveForm.tag_id) {
        saveForm.setError('tag_id', t('admin.trends.create.validation.tagIsRequired'))
        valid = false
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
            route('back-office.trends.update', { slug: trend?.slug }),
            { ...saveForm.data(), _method: 'patch' },
            requestConfig
        )
    } else {
        saveForm.post(route('back-office.trends.save'), requestConfig)
    }
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: t('common.labels.trends'), href: route('back-office.trends.index') },
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
                        {{ t('common.labels.basicInformation') }}
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.tag') }} <span class="text-red-500">*</span>
                            </label>

                            <SelectInfinityLoadingApi
                                :form="saveForm"
                                fieldName="tag_id"
                                :selectedItem="trend?.tag"
                                :apiUrl="route('search.tags')"
                                :error="saveForm.errors.tag_id"
                                :multiple="false"
                                :placeholder="t('admin.trends.create.form.tagPlaceholder')"
                            />

                            <p v-if="saveForm.errors.tag_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.tag_id }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('admin.trends.create.form.isCurrent') }}
                            </label>

                            <input
                                type="checkbox"
                                v-model="saveForm.is_current"
                                :class="saveForm.errors.is_current ? 'border-red-500' : 'border-gray-300'"
                            >

                            <p v-if="saveForm.errors.is_current" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.is_current }}
                            </p>
                        </div>

                    </div>
                </div>

                <div class="flex justify-center">
                    <button
                        type="submit"
                        :disabled="saveForm.processing"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md flex items-center gap-2 transition disabled:opacity-60 disabled:cursor-not-allowed"
                    >
                        <FontAwesomeIcon v-if="saveForm.processing" icon="spinner" spin />
                        <FontAwesomeIcon v-else icon="save" />
                        {{ saveForm.processing ? t('common.actions.saving') : t('common.actions.save') }}
                    </button>
                </div>

            </form>

        </div>
    </div>
</template>
