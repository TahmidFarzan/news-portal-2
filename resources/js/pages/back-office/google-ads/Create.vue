<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import InfiniteScrollApiSelect from '@/components/common/multi-select/InfiniteScrollApiSelect.vue'

import { computed, onMounted, nextTick, ref } from 'vue'
import { Head, useForm, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faSave, faSpinner, faPlus, faTrash } from '@fortawesome/free-solid-svg-icons'

import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faSave, faSpinner, faPlus, faTrash)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const { googleAd } = defineProps({
    googleAd: {
        type: Object,
        default: () => ({})
    },
})

const isUpdate = computed(() => !!googleAd?.slug)

const pageTitle = computed(() => {
    return isUpdate.value
        ? `${googleAd?.name} ${t('common.actions.edit')}`
        : t('admin.googleAds.create.form.createPageTitle')
})

const normalizeAdSizes = (sizes) => {
    if (!Array.isArray(sizes) || !sizes.length) {
        return []
    }

    return sizes
        .filter(size => Array.isArray(size) && size.length >= 2)
        .map(size => ({
            width: size[0] ?? '',
            height: size[1] ?? '',
        }))
}

const adSizes = ref(normalizeAdSizes(googleAd?.ad_sizes))

const saveForm = useForm({
    name: googleAd?.name || null,
    ad_unit_code: googleAd?.ad_unit_code || null,
    gpt_slot_id: googleAd?.gpt_slot_id || null,
    ad_sizes: [],
    page: googleAd?.page || null,
    type: googleAd?.type || null,
    placement: googleAd?.placement || null,
})

const isPopup = computed(() => {
    return saveForm.type === 'Pop Up'
})

const placementApiUrl = computed(() => {
    return route('search.google-ad-placements', {
        page: saveForm.page || null,
        type: saveForm.type || null,
    })
})

const syncAdSizes = () => {
    saveForm.ad_sizes = adSizes.value
        .filter(size => {
            return (
                size.width !== null &&
                size.width !== '' &&
                size.height !== null &&
                size.height !== ''
            )
        })
        .map(size => [
            Number(size.width),
            Number(size.height),
        ])
}

const addAdSize = () => {
    adSizes.value.push({
        width: '',
        height: '',
    })
}

const removeAdSize = (index) => {
    adSizes.value.splice(index, 1)
}

function validateForm() {
    saveForm.clearErrors()

    let valid = true

    if (!saveForm.name) {
        saveForm.setError('name', t('common.validation.nameIsRequired'))
        valid = false
    }

    if (!saveForm.ad_unit_code) {
        saveForm.setError(
            'ad_unit_code',
            t('admin.googleAds.create.validation.adUnitCodeIsRequired')
        )
        valid = false
    }

    if (!saveForm.gpt_slot_id) {
        saveForm.setError(
            'gpt_slot_id',
            t('admin.googleAds.create.validation.gptSlotIdIsRequired')
        )
        valid = false
    }

    if (!saveForm.page) {
        saveForm.setError(
            'page',
            t('common.validation.pageIsRequired')
        )
        valid = false
    }

    if (!saveForm.type) {
        saveForm.setError(
            'type',
            t('common.validation.typeIsRequired')
        )
        valid = false
    }

    if (!isPopup.value && !saveForm.placement) {
        saveForm.setError(
            'placement',
            t('admin.googleAds.create.validation.placementIsRequired')
        )
        valid = false
    }

    syncAdSizes()

    for (let index = 0; index < adSizes.value.length; index++) {
        const size = adSizes.value[index]

        const hasWidth = size.width !== null && size.width !== ''
        const hasHeight = size.height !== null && size.height !== ''

        if (hasWidth !== hasHeight) {
            saveForm.setError(
                `ad_sizes.${index}`,
                t('admin.googleAds.create.validation.adSizeWidthAndHeightRequired')
            )
            valid = false
        }

        if (
            hasWidth &&
            hasHeight &&
            (
                Number(size.width) <= 0 ||
                Number(size.height) <= 0
            )
        ) {
            saveForm.setError(
                `ad_sizes.${index}`,
                t('admin.googleAds.create.validation.adSizeMustBeGreaterThanZero')
            )
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
            route('back-office.google-ads.update', {
                slug: googleAd?.slug
            }),
            {
                ...saveForm.data(),
                _method: 'patch'
            },
            requestConfig
        )
    } else {
        saveForm.post(
            route('back-office.google-ads.save'),
            requestConfig
        )
    }
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                {
                    text: t('common.messages.googleAd'),
                    href: route('back-office.google-ads.index')
                },
                {
                    text: pageTitle.value,
                    active: true
                }
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
                                {{ t('common.labels.name') }}
                                <span class="text-red-500">*</span>
                            </label>

                            <input v-model="saveForm.name" :placeholder="t('common.placeholders.enterName')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.name ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.name" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.adUnitCode') }}
                                <span class="text-red-500">*</span>
                            </label>

                            <input v-model="saveForm.ad_unit_code"
                                :placeholder="t('admin.googleAds.create.form.adUnitCodePlaceholder')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.ad_unit_code ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.ad_unit_code" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.ad_unit_code }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.gptSlotId') }}
                                <span class="text-red-500">*</span>
                            </label>

                            <input v-model="saveForm.gpt_slot_id"
                                :placeholder="t('admin.googleAds.create.form.gptSlotIdPlaceholder')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.gpt_slot_id ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.gpt_slot_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.gpt_slot_id }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.page') }}
                                <span class="text-red-500">*</span>
                            </label>

                            <InfiniteScrollApiSelect :form="saveForm" fieldName="page" :selectedItem="googleAd?.page"
                                :apiUrl="route('search.google-ad-pages')" :error="saveForm.errors.page"
                                :multiple="false" :placeholder="t('common.placeholders.selectPage')" />

                            <p v-if="saveForm.errors.page" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.page }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.type') }}
                                <span class="text-red-500">*</span>
                            </label>

                            <InfiniteScrollApiSelect :form="saveForm" fieldName="type" :selectedItem="googleAd?.type"
                                :apiUrl="route('search.google-ad-types')" :error="saveForm.errors.type"
                                :multiple="false" :placeholder="t('admin.googleAds.create.form.typePlaceholder')" />

                            <p v-if="saveForm.errors.type" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.type }}
                            </p>
                        </div>

                        <div v-if="!isPopup">
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.placement') }}
                                <span class="text-red-500">*</span>
                            </label>

                            <InfiniteScrollApiSelect :form="saveForm" fieldName="placement"
                                :selectedItem="googleAd?.placement" :apiUrl="placementApiUrl"
                                :error="saveForm.errors.placement" :multiple="false"
                                :placeholder="t('common.placeholders.selectPlacement')" />

                            <p v-if="saveForm.errors.placement" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.placement }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <div class="flex justify-between items-center gap-4">
                        <div>
                            <h3 class="text-base font-semibold">
                                {{ t('common.labels.adSizes') }}
                            </h3>

                            <p class="text-sm text-gray-500 mt-1">
                                {{ t('admin.googleAds.create.form.adSizesDescription') }}
                            </p>
                        </div>

                        <button type="button" @click="addAdSize"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                            <FontAwesomeIcon icon="plus" />
                            {{ t('common.actions.add') }}
                        </button>
                    </div>

                    <div v-if="adSizes.length" class="space-y-3">
                        <div v-for="(size, index) in adSizes" :key="index"
                            class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-3 items-start">
                            <div>
                                <label class="block text-sm font-medium mb-1">
                                    {{ t('common.labels.width') }}
                                </label>

                                <input v-model="size.width" type="number" min="1" step="1"
                                    class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                    :class="saveForm.errors[`ad_sizes.${index}`] ? 'border-red-500' : 'border-gray-300'" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">
                                    {{ t('common.labels.height') }}
                                </label>

                                <input v-model="size.height" type="number" min="1" step="1"
                                    class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                    :class="saveForm.errors[`ad_sizes.${index}`] ? 'border-red-500' : 'border-gray-300'" />
                            </div>

                            <div class="flex flex-col md:justify-end md:pt-7">
                                <button type="button" @click="removeAdSize(index)"
                                    class="w-full md:w-auto bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center justify-center gap-2 transition">
                                    <FontAwesomeIcon icon="trash" />
                                    {{ t('common.actions.remove') }}
                                </button>
                            </div>

                            <div v-if="saveForm.errors[`ad_sizes.${index}`]" class="md:col-span-3 text-red-500 text-sm">
                                {{ saveForm.errors[`ad_sizes.${index}`] }}
                            </div>
                        </div>
                    </div>

                    <div v-else
                        class="border border-dashed border-gray-300 rounded-lg py-6 text-center text-sm text-gray-500">
                        {{ t('common.labels.notAvailable') }}
                    </div>
                </div>

                <div class="flex justify-center">
                    <button type="submit" :disabled="saveForm.processing"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md flex items-center gap-2 transition disabled:opacity-70 disabled:cursor-not-allowed">
                        <FontAwesomeIcon v-if="saveForm.processing" icon="spinner" spin />

                        <FontAwesomeIcon v-else icon="save" />

                        {{
                            saveForm.processing
                                ? t('common.actions.saving')
                                : t('common.actions.save')
                        }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
