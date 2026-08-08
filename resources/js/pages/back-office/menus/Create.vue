<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import InfiniteScrollApiSelect from '@/components/common/multi-select/InfiniteScrollApiSelect.vue'

import { computed, onMounted, nextTick } from 'vue'
import { Head, useForm, router as inertiaJsRouter } from '@inertiajs/vue3'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faSave, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faSave, faSpinner)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const { menu } = defineProps({
    menu: {
        type: Object,
        default: null,
    },
})

const isUpdate = computed(() => !!menu?.slug)

const pageTitle = computed(() => {
    return isUpdate.value
        ? `${menu?.name} ${t('common.actions.edit')}`
        : t('admin.menus.create.form.createPageTitle')
})

const saveForm = useForm({
    name: menu?.name || null,
    language_id: menu?.language_id || null,
    menu_type_id: menu?.menu_type_id || null,
})

function validateForm() {
    saveForm.clearErrors()

    let valid = true

    if (!saveForm.name) {
        saveForm.setError('name', t('common.validation.nameIsRequired'))
        valid = false
    }

    if (!saveForm.language_id) {
        saveForm.setError('language_id', t('common.validation.languageIsRequired'))
        valid = false
    }

    if (!saveForm.menu_type_id) {
        saveForm.setError('menu_type_id', t('admin.menus.create.validation.menuTypeIsRequired'))
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
            if (!isUpdate.value) {
                saveForm.reset()
            }

            saveForm.clearErrors()
        },
        onError: (errors) => {
            saveForm.clearErrors()
            saveForm.setError(errors)
        },
        onFinish: () => {
            saveForm.processing = false
        },
    }

    if (isUpdate.value) {
        inertiaJsRouter.post(
            route('back-office.menus.update', { slug: menu?.slug }),
            { ...saveForm.data(), _method: 'patch' },
            requestConfig
        )
    } else {
        saveForm.post(route('back-office.menus.save'), requestConfig)
    }
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: t('common.labels.menus'), href: route('back-office.menus.index') },
                { text: pageTitle.value, active: true },
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
                                {{ t('common.labels.language') }}
                                <span class="text-red-500">*</span>
                            </label>

                            <InfiniteScrollApiSelect :form="saveForm" fieldName="language_id"
                                :selectedItem="menu?.language" :apiUrl="route('search.languages')"
                                :error="saveForm.errors.language_id" :multiple="false"
                                :placeholder="t('common.placeholders.selectLanguage')" />

                            <p v-if="saveForm.errors.language_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.language_id }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.name') }}
                                <span class="text-red-500">*</span>
                            </label>

                            <input v-model="saveForm.name"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.name ? 'border-red-500' : 'border-gray-300'"
                                :placeholder="t('common.placeholders.enterName')" />

                            <p v-if="saveForm.errors.name" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.menuType') }}
                                <span class="text-red-500">*</span>
                            </label>

                            <InfiniteScrollApiSelect :form="saveForm" fieldName="menu_type_id"
                                :selectedItem="menu?.menu_type" :apiUrl="route('search.menu-types')"
                                :error="saveForm.errors.menu_type_id" :multiple="false"
                                :placeholder="t('admin.menus.create.form.menuTypePlaceholder')" />

                            <p v-if="saveForm.errors.menu_type_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.menu_type_id }}
                            </p>
                        </div>

                    </div>
                </div>

                <div class="flex justify-center">
                    <button type="submit" :disabled="saveForm.processing"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md flex items-center gap-2 transition">
                        <FontAwesomeIcon v-if="saveForm.processing" icon="spinner" spin />
                        <FontAwesomeIcon v-else icon="save" />

                        {{ saveForm.processing ? t('common.actions.saving') : t('common.actions.save') }}
                    </button>
                </div>

            </form>

        </div>
    </div>
</template>
