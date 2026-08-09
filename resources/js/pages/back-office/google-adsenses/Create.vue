<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import InfiniteScrollApiSelect from '@/components/common/multi-select/InfiniteScrollApiSelect.vue'
import TaggableSelect from '@/components/common/multi-select/TaggableSelect.vue'
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

const { googleAdsense } = defineProps({
    googleAdsense: {
        type: Object,
        default: () => ({})
    },
})

const seoKeywordsKey = ref(0)

const isUpdate = computed(() => !!googleAdsense?.slug)

const pageTitle = computed(() => {
    return isUpdate.value
        ? `${googleAdsense?.name} ${t('common.actions.edit')}`
        : t('admin.googleAdsenses.create.form.createPageTitle')
})

const saveForm = useForm({
    name: googleAdsense?.name || null,
    type: googleAdsense?.type || null,
    position: googleAdsense?.position || null,
    slot_id: googleAdsense?.slot_id || null,
    use_full_width_responsive: googleAdsense?.use_full_width_responsive || false,
})

function validateForm() {
    saveForm.clearErrors()

    let valid = true

    if (!saveForm.name) {
        saveForm.setError('name', t('common.validation.nameIsRequired'))
        valid = false
    }

    if (!saveForm.slot_id) {
        saveForm.setError('slot_id', t('admin.googleAdsenses.create.validation.slotIdIsRequired'))
        valid = false
    }

    if (!saveForm.position) {
        saveForm.setError('position', t('admin.googleAdsenses.create.validation.positionIsRequired'))
        valid = false
    }

    if (!saveForm.type) {
        saveForm.setError('type', t('common.validation.typeIsRequired'))
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
            route('back-office.google-adsenses.update', { slug: googleAdsense?.slug }),
            { ...saveForm.data(), _method: 'patch' },
            requestConfig
        )
    } else {
        saveForm.post(route('back-office.google-adsenses.save'), requestConfig)
    }
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: t('common.messages.googleAdsense'), href: route('back-office.google-adsenses.index') },
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
                                {{ t('common.labels.name') }} <span
                                    class="text-red-500">*</span>
                            </label>

                            <input v-model="saveForm.name"
                                :placeholder="t('common.placeholders.enterName')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.name ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.name" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.slotId') }} <span
                                    class="text-red-500">*</span>
                            </label>

                            <input v-model="saveForm.slot_id"
                                :placeholder="t('admin.googleAdsenses.create.form.slotIdPlaceholder')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.slot_id ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.slot_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.slot_id }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.position') }}
                            </label>

                            <InfiniteScrollApiSelect :form="saveForm" fieldName="position"
                                :selectedItem="googleAdsense?.position" :apiUrl="route('search.google-adsense-positions')"
                                :error="saveForm.errors.position" :multiple="false"
                                :placeholder="t('common.placeholders.selectPosition')" />

                            <p v-if="saveForm.errors.position" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.position }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.type') }}
                            </label>

                            <InfiniteScrollApiSelect :form="saveForm" fieldName="type"
                                :selectedItem="googleAdsense?.type" :apiUrl="route('search.google-adsense-types')"
                                :error="saveForm.errors.type" :multiple="false"
                                :placeholder="t('admin.googleAdsenses.create.form.typePlaceholder')" />

                            <p v-if="saveForm.errors.type" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.type }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                {{ t('common.labels.useFullWidthResponsive') }}

                            </label>
                            <label class="inline-flex cursor-pointer items-center gap-3">
                                <input v-model="saveForm.use_full_width_responsive" type="checkbox" class="peer sr-only" :checked="saveForm.use_full_width_responsive"/>

                                <span class="relative h-7 w-14 rounded-full bg-gray-300 transition
                                    after:absolute after:left-1 after:top-1 after:h-5 after:w-5
                                    after:rounded-full after:bg-white after:transition-all after:content-['']
                                    peer-checked:bg-green-600 peer-checked:after:translate-x-7">
                                </span>

                                <span class="text-sm text-gray-600">
                                    {{ saveForm.use_full_width_responsive ? t('common.boolean.yes') :
                                        t('common.boolean.no') }}
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

                        {{ saveForm.processing ? t('common.actions.saving') :
                            t('common.actions.save') }}
                    </button>
                </div>

            </form>

        </div>
    </div>
</template>
