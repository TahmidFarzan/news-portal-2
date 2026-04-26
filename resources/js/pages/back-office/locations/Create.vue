<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import MultiSelectInfinityLoadingApi from '@/components/common/multi-select/InfinityLoadingApi.vue'
import MultiSelectTaggableSelect from '@/components/common/multi-select/TaggableSelect.vue'

import { computed, onMounted, nextTick, inject } from 'vue'
import { Head, useForm, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faSave, faEye, faEyeSlash, faSpinner } from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faSave, faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const pageReady = inject("pageReady")

const { location } = defineProps({
    location: Object,
})

const isUpdate = computed(() => !!location?.slug)

const saveForm = useForm({
    name: location?.name || null,
    details: location?.details || null,
    has_parent: location?.has_parent || false,
    parent_id: location?.parent_id || null,
    language_id: location?.language_id || null,
    category_id: location?.category_id || null,
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
        saveForm.setError('parent_id', 'Parent location is required.')
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
            route('back-office.locations.update', { slug: location?.slug }),
            { ...saveForm.data(), _method: 'patch' },
            requestConfig
        )
    } else {
        saveForm.post(route('back-office.locations.save'), requestConfig)
    }
}


onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: 'Dashboard', href: route('auth-user.dashboard.index') },
                { text: 'Locations', href: route('back-office.locations.index') },
                { text: isUpdate.value ? `${location?.name} edit` : 'Location create', active: true }
            ],
        })
    )

    pageReady.value = true
})
</script>

<template>

    <Head :title="isUpdate ? `${location?.name} edit` : 'Location create'" />

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
                                :selectedItem="location?.language" :apiUrl="route('search.locations')" :error="saveForm.errors.language_id"
                                :multiple="false" placeholder="Select language" />
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

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">
                                Details
                            </label>

                            <textarea v-if="pageReady" v-model="saveForm.details" rows="4" placeholder="Enter details"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.details ? 'border-red-500' : 'border-gray-300'"></textarea>

                            <p v-if="saveForm.errors.details" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.details }}
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

                            <MultiSelectInfinityLoadingApi v-if="pageReady" :selectedItem="location?.parent"
                                fieldName="parent_id" :form="saveForm" :apiUrl="route('search.location-tree')"
                                :error="saveForm.errors.parent_id" selectedLabelKey="indentation_name"
                                selectedValueKey="id" apiLabelKey="indentation_name" apiValueKey="id"
                                :multiple="false" placeholder="Select parent" />

                            <p v-if="saveForm.errors.parent_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.parent_id }}
                            </p>
                        </div>

                    </div>
                </div>

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">Category</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Category
                            </label>

                            <MultiSelectInfinityLoadingApi v-if="pageReady" :selectedItem="location?.category"
                                fieldName="category_id" :form="saveForm" :apiUrl="route('search.location-tree')"
                                :error="saveForm.errors.category_id" selectedLabelKey="indentation_name"
                                selectedValueKey="id" apiLabelKey="indentation_name" apiValueKey="id"
                                :multiple="false" placeholder="Select category" />

                            <p v-if="saveForm.errors.category_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.category_id }}
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
