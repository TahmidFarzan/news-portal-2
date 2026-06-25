<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import SelectInfinityLoadingApi from '@/components/common/multi-select/SelectInfinityLoadingApi.vue'
import SelectTaggable from '@/components/common/multi-select/SelectTaggable.vue'
import MediaRenderer from '@/components/common/media/MediaRenderer.vue'

import { computed, onMounted, nextTick, ref } from 'vue'
import { Head, useForm, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faSave, faEye, faEyeSlash, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faSave, faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const { googleAdsence } = defineProps({
    googleAdsence: {
        type: Object,
        default: () => ({})
    },
})

const seoKeywordsKey = ref(0)

const isUpdate = computed(() => !!googleAdsence?.slug)

const pageTitle = computed(() => {
    return isUpdate.value
        ? `${googleAdsence?.name} ${t('pages.back_office.google_adsences.create.labels.edit')}`
        : t('pages.back_office.google_adsences.create.form.create_page_title')
})

const saveForm = useForm({
    name: googleAdsence?.name || null,
    type: googleAdsence?.type || null,
    position: googleAdsence?.position || null,
    slot_id: googleAdsence?.slot_id || null,
    use_full_width_responsive: googleAdsence?.use_full_width_responsive || false,
})

function validateForm() {
    saveForm.clearErrors()

    let valid = true

    if (!saveForm.name) {
        saveForm.setError('name', t('pages.back_office.google_adsences.create.validation.name_is_required'))
        valid = false
    }

    if (!saveForm.slot_id) {
        saveForm.setError('slot_id', t('pages.back_office.google_adsences.create.validation.slot_id_is_required'))
        valid = false
    }

    if (!saveForm.position) {
        saveForm.setError('position', t('pages.back_office.google_adsences.create.validation.position_is_required'))
        valid = false
    }

    if (!saveForm.type) {
        saveForm.setError('type', t('pages.back_office.google_adsences.create.validation.type_is_required'))
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
            route('back-office.google-adsences.update', { slug: googleAdsence?.slug }),
            { ...saveForm.data(), _method: 'patch' },
            requestConfig
        )
    } else {
        saveForm.post(route('back-office.google-adsences.save'), requestConfig)
    }
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: t('pages.back_office.google_adsences.create.navigation.google_adsences'), href: route('back-office.google-adsences.index') },
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
                        {{ t('pages.back_office.google_adsences.create.labels.basic_information') }}
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('pages.back_office.google_adsences.create.labels.name') }} <span
                                    class="text-red-500">*</span>
                            </label>

                            <input v-model="saveForm.name"
                                :placeholder="t('pages.back_office.google_adsences.create.form.name_placeholder')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.name ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.name" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('pages.back_office.google_adsences.create.labels.slot_id') }} <span
                                    class="text-red-500">*</span>
                            </label>

                            <input v-model="saveForm.slot_id"
                                :placeholder="t('pages.back_office.google_adsences.create.form.slot_id_placeholder')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.slot_id ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.slot_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.slot_id }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('pages.back_office.google_adsences.create.labels.position') }}
                            </label>

                            <SelectInfinityLoadingApi :form="saveForm" fieldName="position"
                                :selectedItem="googleAdsence?.position" :apiUrl="route('search.google-adsence-positions')"
                                :error="saveForm.errors.position" :multiple="false"
                                :placeholder="t('pages.back_office.google_adsences.create.form.position_placeholder')" />

                            <p v-if="saveForm.errors.position" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.position }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('pages.back_office.google_adsences.create.labels.type') }}
                            </label>

                            <SelectInfinityLoadingApi :form="saveForm" fieldName="type"
                                :selectedItem="googleAdsence?.type" :apiUrl="route('search.google-adsence-types')"
                                :error="saveForm.errors.type" :multiple="false"
                                :placeholder="t('pages.back_office.google_adsences.create.form.type_placeholder')" />

                            <p v-if="saveForm.errors.type" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.type }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                {{ t('pages.back_office.google_adsences.create.labels.use_full_width_responsive') }}

                            </label>
                            <label class="inline-flex cursor-pointer items-center gap-3">
                                <input v-model="saveForm.use_full_width_responsive" type="checkbox" class="peer sr-only" :checked="saveForm.use_full_width_responsive"/>

                                <span class="relative h-7 w-14 rounded-full bg-gray-300 transition
                                    after:absolute after:left-1 after:top-1 after:h-5 after:w-5
                                    after:rounded-full after:bg-white after:transition-all after:content-['']
                                    peer-checked:bg-green-600 peer-checked:after:translate-x-7">
                                </span>

                                <span class="text-sm text-gray-600">
                                    {{ saveForm.use_full_width_responsive ? t('pages.back_office.google_adsences.create.labels.yes') :
                                        t('pages.back_office.google_adsences.create.labels.no') }}
                                </span>
                            </label>

                            <p v-if="saveForm.errors.use_full_width_responsive" class="mt-1 text-sm text-red-500">
                                {{ saveForm.errors.use_full_width_responsive }}
                            </p>
                        </div>

                    </div>
                </div>


                <div class="flex justify-center">
                    <button type="submit" :disabled="saveForm.processing"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md flex items-center gap-2 transition disabled:opacity-70 disabled:cursor-not-allowed">
                        <FontAwesomeIcon v-if="saveForm.processing" icon="spinner" spin />
                        <FontAwesomeIcon v-else icon="save" />

                        {{ saveForm.processing ? t('pages.back_office.google_adsences.create.actions.saving') :
                            t('pages.back_office.google_adsences.create.actions.save') }}
                    </button>
                </div>

            </form>

        </div>
    </div>
</template>
