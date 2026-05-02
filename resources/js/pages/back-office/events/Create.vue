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

const { event } = defineProps({
    event: Object,
})

const isUpdate = computed(() => !!event?.slug)

const saveForm = useForm({
    name: event?.name || null,
    brief: event?.brief || null,
    language_id: event?.language_id || null,
    desktop_banner_image: null,
    mobile_banner_image: null,
    seo_brief: event?.seo_brief || null,
    seo_title: event?.seo_title || null,
    seo_keywords: event?.seo_keywords ? event.seo_keywords.split(',') : [],
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
            route('back-office.events.update', { slug: event?.slug }),
            { ...saveForm.data(), _method: 'patch' },
            requestConfig
        )
    } else {
        saveForm.post(route('back-office.events.save'), requestConfig)
    }
}


onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: 'Dashboard', href: route('auth-user.dashboard.index') },
                { text: 'Events', href: route('back-office.events.index') },
                { text: isUpdate.value ? `${event?.name} edit` : 'Event create', active: true }
            ],
        })
    )

    pageReady.value = true
})
</script>

<template>

    <Head :title="isUpdate ? `${event?.name} edit` : 'Event create'" />

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
                                :selectedItem="event?.language" :apiUrl="route('search.events')"
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

                            <textarea v-if="pageReady" v-model="saveForm.brief" rows="4" placeholder="Enter brief"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.brief ? 'border-red-500' : 'border-gray-300'"></textarea>

                            <p v-if="saveForm.errors.brief" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.brief }}
                            </p>
                        </div>

                    </div>
                </div>

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">Banner Image</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Desktop Banner Image
                            </label>
                            <input type="file" @change="e => saveForm.desktop_banner_image = e.target.files[0]"
                                class="border rounded px-3 py-2 w-full"
                                :class="saveForm.errors.desktop_banner_image ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.desktop_banner_image" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.desktop_banner_image }}
                            </p>

                            <img :src="event?.desktop_banner_image?.media_url || '/uploads/images/event-desktop.png'"
                            class="object-cover rounded-xl border border-gray-200 mt-2"/>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                Mobile Banner Image
                            </label>
                            <input type="file" @change="e => saveForm.mobile_banner_image = e.target.files[0]"
                                class="border rounded px-3 py-2 w-full"
                                :class="saveForm.errors.mobile_banner_image ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.mobile_banner_image" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.mobile_banner_image }}
                            </p>

                            <img :src="event?.mobile_banner_image?.media_url || '/uploads/images/event-mobile.png'"
                            class="object-cover rounded-xl border border-gray-200 mt-2"/>
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

                            <textarea v-if="pageReady" v-model="saveForm.seo_brief" rows="3"
                                placeholder="Enter SEO brief"
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
