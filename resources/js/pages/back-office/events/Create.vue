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

const { event } = defineProps({
    event: {
        type: Object,
        default: () => ({})
    },
})

const seoKeywordsKey = ref(0)

const isUpdate = computed(() => !!event?.slug)

const pageTitle = computed(() => {
    return isUpdate.value
        ? `${event?.name} ${t('pages.back_office.events.create.labels.edit')}`
        : t('pages.back_office.events.create.form.create_page_title')
})

const saveForm = useForm({
    name: event?.name || null,
    position: event?.position || null,
    brief: event?.brief || null,
    language_id: event?.language_id || null,
    desktop_banner_image: null,
    mobile_banner_image: null,
    is_current: event?.is_current || false,
    seo_brief: event?.seo_brief || null,
    seo_title: event?.seo_title || null,
    seo_keywords: event?.seo_keywords ? event.seo_keywords.split(',') : [],
})

function validateForm() {
    saveForm.clearErrors()

    let valid = true

    if (!saveForm.name) {
        saveForm.setError('name', t('pages.back_office.events.create.validation.name_is_required'))
        valid = false
    }

    if (!saveForm.language_id) {
        saveForm.setError('language_id', t('pages.back_office.events.create.validation.language_is_required'))
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
                { text: t('pages.back_office.events.create.navigation.events'), href: route('back-office.events.index') },
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
                        {{ t('pages.back_office.events.create.labels.basic_information') }}
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('pages.back_office.events.create.labels.language') }} <span
                                    class="text-red-500">*</span>
                            </label>

                            <SelectInfinityLoadingApi :form="saveForm" fieldName="language_id"
                                :selectedItem="event?.language" :apiUrl="route('search.languages')"
                                :error="saveForm.errors.language_id" :multiple="false"
                                :placeholder="t('pages.back_office.events.create.form.language_placeholder')" />

                            <p v-if="saveForm.errors.language_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.language_id }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('pages.back_office.events.create.labels.name') }} <span
                                    class="text-red-500">*</span>
                            </label>

                            <input v-model="saveForm.name"
                                :placeholder="t('pages.back_office.events.create.form.name_placeholder')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.name ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.name" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.name }}
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">
                                {{ t('pages.back_office.events.create.form.brief') }}
                            </label>

                            <textarea v-model="saveForm.brief" rows="4"
                                :placeholder="t('pages.back_office.events.create.form.brief_placeholder')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.brief ? 'border-red-500' : 'border-gray-300'"></textarea>

                            <p v-if="saveForm.errors.brief" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.brief }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('pages.back_office.events.create.labels.position') }}
                            </label>

                            <SelectInfinityLoadingApi :form="saveForm" fieldName="position"
                                :selectedItem="event?.position" :apiUrl="route('search.event-positions')"
                                :error="saveForm.errors.position" :multiple="false"
                                :placeholder="t('pages.back_office.events.create.form.position_placeholder')" />

                            <p v-if="saveForm.errors.position" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.position }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                {{ t('pages.back_office.events.create.labels.is_current') }}

                            </label>
                            <label class="inline-flex cursor-pointer items-center gap-3">
                                <input v-model="saveForm.is_current" type="checkbox" class="peer sr-only" :checked="saveForm.is_current"/>

                                <span class="relative h-7 w-14 rounded-full bg-gray-300 transition
                                    after:absolute after:left-1 after:top-1 after:h-5 after:w-5
                                    after:rounded-full after:bg-white after:transition-all after:content-['']
                                    peer-checked:bg-green-600 peer-checked:after:translate-x-7">
                                </span>

                                <span class="text-sm text-gray-600">
                                    {{ saveForm.is_current ? t('pages.back_office.events.create.labels.yes') :
                                        t('pages.back_office.events.create.labels.no') }}
                                </span>
                            </label>

                            <p v-if="saveForm.errors.is_current" class="mt-1 text-sm text-red-500">
                                {{ saveForm.errors.is_current }}
                            </p>
                        </div>

                    </div>
                </div>

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">
                        {{ t('pages.back_office.events.create.form.banner_image_section') }}
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('pages.back_office.events.create.form.desktop_banner_image') }}
                            </label>

                            <input type="file" @change="e => saveForm.desktop_banner_image = e.target.files[0]"
                                class="border rounded px-3 py-2 w-full"
                                :class="saveForm.errors.desktop_banner_image ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.desktop_banner_image" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.desktop_banner_image }}
                            </p>

                            <MediaRenderer v-if="event?.desktop_banner_image" :media="event?.desktop_banner_image" />

                            <img v-else :src="'/uploads/images/event/desktop.png'"
                                :alt="t('pages.back_office.events.create.form.desktop_banner_image')"
                                class="object-cover rounded-xl border border-gray-200 mt-2" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('pages.back_office.events.create.form.mobile_banner_image') }}
                            </label>

                            <input type="file" @change="e => saveForm.mobile_banner_image = e.target.files[0]"
                                class="border rounded px-3 py-2 w-full"
                                :class="saveForm.errors.mobile_banner_image ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.mobile_banner_image" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.mobile_banner_image }}
                            </p>

                            <MediaRenderer v-if="event?.mobile_banner_image" :media="event?.mobile_banner_image" />

                            <img v-else :src="'/uploads/images/event/mobile.png'"
                                :alt="t('pages.back_office.events.create.form.mobile_banner_image')"
                                class="object-cover rounded-xl border border-gray-200 mt-2" />
                        </div>

                    </div>
                </div>

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">
                        {{ t('pages.back_office.events.create.form.seo_settings') }}
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('pages.back_office.events.create.form.seo_title') }}
                            </label>

                            <input v-model="saveForm.seo_title" type="text"
                                :placeholder="t('pages.back_office.events.create.form.seo_title_placeholder')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.seo_title ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.seo_title" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.seo_title }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('pages.back_office.events.create.form.seo_brief') }}
                            </label>

                            <textarea v-model="saveForm.seo_brief" rows="3"
                                :placeholder="t('pages.back_office.events.create.form.seo_brief_placeholder')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.seo_brief ? 'border-red-500' : 'border-gray-300'"></textarea>

                            <p v-if="saveForm.errors.seo_brief" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.seo_brief }}
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">
                                {{ t('pages.back_office.events.create.form.seo_keywords') }}
                            </label>

                            <SelectTaggable :key="seoKeywordsKey" :selectedItem="saveForm.seo_keywords"
                                fieldName="seo_keywords" :form="saveForm" :error="saveForm.errors.seo_keywords"
                                :placeholder="t('pages.back_office.events.create.form.seo_keywords_placeholder')" />

                            <p v-if="saveForm.errors.seo_keywords" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.seo_keywords }}
                            </p>
                        </div>

                    </div>
                </div>

                <div class="flex justify-center">
                    <button type="submit" :disabled="saveForm.processing"
                        class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md flex items-center gap-2 transition disabled:opacity-70 disabled:cursor-not-allowed">
                        <FontAwesomeIcon v-if="saveForm.processing" icon="spinner" spin />
                        <FontAwesomeIcon v-else icon="save" />

                        {{ saveForm.processing ? t('pages.back_office.events.create.actions.saving') :
                            t('pages.back_office.events.create.actions.save') }}
                    </button>
                </div>

            </form>

        </div>
    </div>
</template>
