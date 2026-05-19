<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import MultiSelectInfinityLoadingApi from '@/components/common/multi-select/InfinityLoadingApi.vue'

import { computed, onMounted, nextTick, inject } from 'vue'
import { Head, useForm, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faSave, faEye, faEyeSlash, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { extractModelName } from '@/composables/useUtil'

FontAwesomeLibrary.add(faSave, faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const pageReady = inject("pageReady")

const { menu, menuItem } = defineProps({
    menu: Object,
    menuItem: Object,
})

const isUpdate = computed(() => !!menuItem?.slug)

const saveForm = useForm({
    name: menuItem?.name || null,
    model_type: extractModelName(menuItem?.model_type) || null,
    model_id: menuItem?.model_id || null,
    parent_id: menuItem?.parent_id || null,
    url: menuItem?.url || null,
    language_id: menuItem?.language_id || null,
    has_parent: menuItem?.has_parent || false,
    is_custom_url: menuItem?.is_custom_url || false,
})

function validateForm() {
    saveForm.clearErrors()
    let valid = true

    if (!saveForm.name) {
        saveForm.setError('name', 'Name is required.')
        valid = false
    }

    if (!saveForm.language_id) {
        saveForm.setError('language_id', 'Language is required.')
        valid = false
    }

    if (saveForm.has_parent && !saveForm.parent_id) {
        saveForm.setError('parent_id', 'Parent menu item is required.')
        valid = false
    }

    if (saveForm.is_custom_url && !saveForm.url) {
        saveForm.setError('url', 'Url is required.')
        valid = false
    }

    if (!saveForm.is_custom_url) {
        if (!saveForm.model_type) {
            saveForm.setError('model_type', 'Model is required.')
            valid = false
        }

        if (!saveForm.model_id) {
            saveForm.setError('model_id', 'Model record is required.')
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
            route('back-office.menus.menu-items.update', { slug: menu?.slug, menuItemSlug: menuItem?.slug }),
            { ...saveForm.data(), _method: 'patch' },
            requestConfig
        )
    } else {
        saveForm.post(route('back-office.menus.menu-items.save', { slug: menu?.slug }), requestConfig)
    }
}


onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: 'Dashboard', href: route('auth-user.dashboard.index') },
                { text: 'Menus', href: route('back-office.menus.index') },
                { text: `${menu?.name} details`, href: route('back-office.menus.details', { slug: menu?.slug }) },
                { text: 'Menu Items', href: route('back-office.menus.menu-items.index', { slug: menu?.slug }) },
                { text: isUpdate.value ? `${menuItem?.name} edit` : 'Menu item create', active: true }
            ],
        })
    )

    pageReady.value = true
})
</script>

<template>

    <Head :title="isUpdate ? `${menuItem?.name} edit` : 'Menu Item create'" />

    <div class="w-full">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6">

            <form @submit.prevent="handleSave" class="space-y-6">

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">Basic Information</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Language <span class="text-red-500">*</span>
                            </label>

                            <MultiSelectInfinityLoadingApi v-if="pageReady" :form="saveForm" fieldName="language_id"
                                :selectedItem="menuItem?.language" :apiUrl="route('search.languages')"
                                :error="saveForm.errors.language_id" :multiple="false" placeholder="Select language" />
                            <p v-if="saveForm.errors.language_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.language_id }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Name <span class="text-red-500">*</span>
                            </label>

                            <input v-model="saveForm.name"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.name ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.name" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                Is custom url
                            </label>

                            <label class="inline-flex cursor-pointer items-center gap-3">
                                <input v-model="saveForm.is_custom_url" type="checkbox" class="peer sr-only" />

                                <span class="relative h-7 w-14 rounded-full bg-gray-300 transition
                                    after:absolute after:left-1 after:top-1 after:h-5 after:w-5
                                    after:rounded-full after:bg-white after:transition-all after:content-['']
                                    peer-checked:bg-green-600 peer-checked:after:translate-x-7">
                                </span>

                                <span class="text-sm text-gray-600">
                                    {{ saveForm.is_custom_url ? 'Yes' : 'No' }}
                                </span>
                            </label>

                            <p v-if="saveForm.errors.is_custom_url" class="mt-1 text-sm text-red-500">
                                {{ saveForm.errors.is_custom_url }}
                            </p>
                        </div>
                    </div>

                    <div v-if="!saveForm.is_custom_url" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Model <span class="text-red-500">*</span>
                            </label>

                            <MultiSelectInfinityLoadingApi v-if="pageReady" :form="saveForm" fieldName="model_type"
                                :selectedItem="saveForm?.model_type" :apiUrl="route('search.menu-item-models')"
                                :error="saveForm.errors.model_type" :multiple="false" placeholder="Select model" />
                            <p v-if="saveForm.errors.model_type" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.model_type }}
                            </p>

                        </div>

                        <div v-if="!saveForm.is_custom_url && saveForm.model_type">
                            <label class="block text-sm font-medium mb-1">
                                Model id <span class="text-red-500">*</span>
                            </label>

                            <MultiSelectInfinityLoadingApi v-if="pageReady && saveForm?.model_type == 'Tag'" :form="saveForm" fieldName="model_id"
                                :selectedItem="saveForm?.model_id" :apiUrl="route('search.search.tags')"
                                :error="saveForm.errors.model_id" selectedLabelKey="name"
                                selectedValueKey="id" apiLabelKey="name" apiValueKey="id" :multiple="false" placeholder="Select model record" />

                            <MultiSelectInfinityLoadingApi v-if="pageReady && saveForm?.model_type== 'Category'" :form="saveForm" fieldName="model_id"
                                :selectedItem="menuItem?.model" :apiUrl="route('search.category-tree')"
                                :error="saveForm.errors.model_id" selectedLabelKey="indentation_name"
                                selectedValueKey="id" apiLabelKey="indentation_name" apiValueKey="id" :multiple="false" placeholder="Select model record" />
                            <p v-if="saveForm.errors.model_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.model_id }}
                            </p>

                        </div>
                    </div>

                    <div v-if="saveForm.is_custom_url" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Url <span class="text-red-500">*</span>
                            </label>

                            <input v-model="saveForm.url"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.url ? 'border-red-500' : 'border-gray-300'" type="url" />

                            <p v-if="saveForm.errors.url" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.url }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">Hierarchy</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">

                        <div>
                            <label class="block text-sm font-medium mb-2">
                                Has Parent
                            </label>

                            <button type="button" @click="saveForm.has_parent = !saveForm.has_parent" :class="[
                                'relative inline-flex h-6 w-11 items-center rounded-full transition',
                                saveForm.has_parent ? 'bg-blue-600' : 'bg-gray-300'
                            ]">
                                <span :class="[
                                    'inline-block h-5 w-5 transform rounded-full bg-white transition',
                                    saveForm.has_parent ? 'translate-x-5' : 'translate-x-1'
                                ]" />
                            </button>
                        </div>

                        <div v-if="saveForm.has_parent">
                            <label class="block text-sm font-medium mb-1">
                                Parent <span class="text-red-500">*</span>
                            </label>

                            <MultiSelectInfinityLoadingApi v-if="pageReady" :selectedItem="menuItem?.parent"
                                fieldName="parent_id" :form="saveForm" :apiUrl="route('search.menu-item-tree')"
                                :error="saveForm.errors.parent_id" selectedLabelKey="indentation_name"
                                selectedValueKey="id" apiLabelKey="indentation_name" apiValueKey="id" :multiple="false"
                                placeholder="Select menu item" />

                            <p v-if="saveForm.errors.parent_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.parent_id }}
                            </p>
                        </div>

                    </div>
                </div>

                <div class="flex justify-center">
                    <button type="submit" :disabled="saveForm.processing"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md flex items-center gap-2 transition">
                        <FontAwesomeIcon v-if="saveForm.processing" icon="spinner" spin />
                        <FontAwesomeIcon v-else icon="save" />
                        Save
                    </button>
                </div>

            </form>

        </div>
    </div>
</template>
