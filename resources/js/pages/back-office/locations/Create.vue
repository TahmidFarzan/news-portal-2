<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import MultiSelectInfinityLoadingApi from '@/components/common/multi-select/InfinityLoadingApi.vue'
import MultiSelectTaggableSelect from '@/components/common/multi-select/TaggableSelect.vue'

import { computed, onMounted, nextTick, inject, watch, ref } from 'vue'
import { Head, useForm, router as intertiaJsRoute } from '@inertiajs/vue3'

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome"
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faSave, faEye, faEyeSlash, faSpinner } from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faSave, faEye, faEyeSlash, faSpinner)

defineOptions({ layout: Layout })

const { location } = defineProps({
    location: Object,
})

const seoKeywordsKey = ref(0)

const isUpdate = computed(() => !!location?.slug)

const saveForm = useForm({
    name: location?.name || null,
    brief: location?.brief || null,
    has_parent: location?.has_parent || false,
    parent_id: location?.parent_id || null,
    language_id: location?.language_id || null,
    category_id: location?.category_id || null,

    latitude: location?.latitude || null,
    longitude: location?.longitude || null,
    boundary_geojson: location?.boundary_geojson
        ? typeof location.boundary_geojson === 'string'
            ? location.boundary_geojson
            : JSON.stringify(location.boundary_geojson, null, 2)
        : null,
    boundary_north: location?.boundary_north || null,
    boundary_south: location?.boundary_south || null,
    boundary_east: location?.boundary_east || null,
    boundary_west: location?.boundary_west || null,

    seo_brief: location?.seo_brief || null,
    seo_title: location?.seo_title || null,
    seo_keywords: location?.seo_keywords ? location.seo_keywords.split(',') : [],
})

const categoryApiUrl = computed(() => {
    if (!saveForm.language_id) {
        return route('search.category-tree')
    }

    return route('search.category-tree') + `?language_id=${saveForm.language_id}`
})

const locationApiUrl = computed(() => {
    if (!saveForm.language_id) {
        return route('search.location-tree')
    }

    return route('search.location-tree') + `?language_id=${saveForm.language_id}`
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

watch(
    () => saveForm.language_id,
    () => {
        saveForm.name = null
        saveForm.brief = null

        saveForm.category_id = null
        saveForm.parent_id = null

        saveForm.latitude = null
        saveForm.longitude = null
        saveForm.boundary_geojson = null
        saveForm.boundary_north = null
        saveForm.boundary_south = null
        saveForm.boundary_east = null
        saveForm.boundary_west = null

        saveForm.seo_title = null
        saveForm.seo_brief = null
        saveForm.seo_keywords = []
        seoKeywordsKey.value++

        saveForm.clearErrors(
            'category_id',
            'parent_id',
            'latitude',
            'longitude',
            'boundary_geojson',
            'boundary_north',
            'boundary_south',
            'boundary_east',
            'boundary_west',
        )
    }
)

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
                { text: 'Locations', href: route('back-office.locations.index') },
                { text: isUpdate.value ? `${location?.name} edit` : 'Location create', active: true }
            ],
        })
    )

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

                            <MultiSelectInfinityLoadingApi :form="saveForm" fieldName="language_id"
                                :selectedItem="location?.language" :apiUrl="route('search.languages')"
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

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">
                                Brief
                            </label>

                            <textarea v-model="saveForm.brief" rows="4" placeholder="Enter brief"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.brief ? 'border-red-500' : 'border-gray-300'"></textarea>

                            <p v-if="saveForm.errors.brief" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.brief }}
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

                            <MultiSelectInfinityLoadingApi :selectedItem="location?.parent" fieldName="parent_id"
                                :form="saveForm" :apiUrl="locationApiUrl" :error="saveForm.errors.parent_id"
                                selectedLabelKey="indentation_name" selectedValueKey="id" apiLabelKey="indentation_name"
                                apiValueKey="id" :multiple="false" placeholder="Select parent" />

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

                            <MultiSelectInfinityLoadingApi :selectedItem="location?.category" fieldName="category_id"
                                :form="saveForm" :apiUrl="categoryApiUrl" :error="saveForm.errors.category_id"
                                selectedLabelKey="indentation_name" selectedValueKey="id" apiLabelKey="indentation_name"
                                apiValueKey="id" :multiple="false" placeholder="Select category" />

                            <p v-if="saveForm.errors.category_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.category_id }}
                            </p>
                        </div>

                    </div>
                </div>

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">Map Information</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Latitude
                            </label>

                            <input v-model="saveForm.latitude" type="number" step="any" placeholder="Enter latitude"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.latitude ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.latitude" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.latitude }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Longitude
                            </label>

                            <input v-model="saveForm.longitude" type="number" step="any" placeholder="Enter longitude"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.longitude ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.longitude" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.longitude }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Boundary North
                            </label>

                            <input v-model="saveForm.boundary_north" type="number" step="any"
                                placeholder="Enter boundary north"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.boundary_north ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.boundary_north" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.boundary_north }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Boundary South
                            </label>

                            <input v-model="saveForm.boundary_south" type="number" step="any"
                                placeholder="Enter boundary south"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.boundary_south ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.boundary_south" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.boundary_south }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Boundary East
                            </label>

                            <input v-model="saveForm.boundary_east" type="number" step="any"
                                placeholder="Enter boundary east"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.boundary_east ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.boundary_east" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.boundary_east }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Boundary West
                            </label>

                            <input v-model="saveForm.boundary_west" type="number" step="any"
                                placeholder="Enter boundary west"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.boundary_west ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.boundary_west" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.boundary_west }}
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">
                                Boundary GeoJSON
                            </label>

                            <textarea v-model="saveForm.boundary_geojson" rows="10" placeholder="Enter boundary GeoJSON"
                                class="w-full border rounded-md px-3 py-2 font-mono text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.boundary_geojson ? 'border-red-500' : 'border-gray-300'"></textarea>

                            <p v-if="saveForm.errors.boundary_geojson" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.boundary_geojson }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">SEO Settings</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                SEO Title
                            </label>

                            <input v-model="saveForm.seo_title" type="text" placeholder="Enter SEO title"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.seo_title ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.seo_title" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.seo_title }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                SEO Brief
                            </label>

                            <textarea v-model="saveForm.seo_brief" rows="3" placeholder="Enter SEO brief"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.seo_brief ? 'border-red-500' : 'border-gray-300'"></textarea>

                            <p v-if="saveForm.errors.seo_brief" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.seo_brief }}
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">
                                SEO Keywords
                            </label>

                            <MultiSelectTaggableSelect :selectedItem="saveForm.seo_keywords" fieldName="seo_keywords"
                                :form="saveForm" :error="saveForm.errors.seo_keywords" placeholder="Add keywords" />

                            <p v-if="saveForm.errors.seo_keywords" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.seo_keywords }}
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
