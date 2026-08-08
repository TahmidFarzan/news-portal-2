<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import InfiniteScrollApiSelect from '@/components/common/multi-select/InfiniteScrollApiSelect.vue'

import { computed, onMounted, nextTick, ref, watch } from 'vue'
import { Head, useForm, router as inertiaJsRouter } from '@inertiajs/vue3'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faSave, faSpinner } from '@fortawesome/free-solid-svg-icons'

import { extractModelName } from '@/composables/useStringFormat'
import { useTranslate } from '@/composables/useTranslate'
import { menuModels } from '@/composables/useMenu'

FontAwesomeLibrary.add(faSave, faSpinner)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const { menu, menuItem } = defineProps({
    menu: {
        type: Object,
        default: null,
    },
    menuItem: {
        type: Object,
        default: null,
    },
})

const isUpdate = computed(() => !!menuItem?.slug)

const pageTitle = computed(() => {
    return isUpdate.value
        ? `${menuItem?.name} ${t('common.actions.edit')}`
        : t('admin.menus.menuItems.create.form.createPageTitle')
})

const copiedRoute = ref(null)

const seoKeywordsKey = ref(0)
const modelTypeKey = ref(0)
const modelIdKey = ref(0)
const parentKey = ref(0)

const saveForm = useForm({
    name: menuItem?.name || null,
    model_type: extractModelName(menuItem?.model_type) || null,
    model_id: menuItem?.model_id || null,
    parent_id: menuItem?.parent_id || null,
    url: menuItem?.url || null,
    language_id: menuItem?.language_id || null,
    has_parent: menuItem?.has_parent || false,
    is_custom_url: menuItem?.is_custom_url || false,
    position: menuItem?.position || null,
})

const menuItemApiUrl = computed(() => {
    if (!saveForm.language_id) {
        return route('search.menu-item-tree')
    }

    return route('search.menu-item-tree') + `?language_id=${saveForm.language_id}`
})

const categoryApiUrl = computed(() => {
    if (!saveForm.language_id) {
        return route('search.category-tree')
    }

    return route('search.category-tree') + `?language_id=${saveForm.language_id}`
})

const pageApiUrl = computed(() => {
    if (!saveForm.language_id) {
        return route('search.page-tree')
    }

    return route('search.page-tree') + `?language_id=${saveForm.language_id}`
})

const tagApiUrl = computed(() => {
    if (!saveForm.language_id) {
        return route('search.tags')
    }

    return route('search.tags') + `?language_id=${saveForm.language_id}`
})

const copyUrl = async (routeName) => {
    const url = route(routeName)

    await navigator.clipboard.writeText(url)

    copiedRoute.value = routeName

    setTimeout(() => {
        copiedRoute.value = null
    }, 1500)
}

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

    if (saveForm.has_parent && !saveForm.parent_id) {
        saveForm.setError('parent_id', t('admin.menus.menuItems.create.validation.parentMenuItemIsRequired'))
        valid = false
    }

    if (saveForm.is_custom_url && !saveForm.url) {
        saveForm.setError('url', t('admin.menus.menuItems.create.validation.urlIsRequired'))
        valid = false
    }

    if (!saveForm.is_custom_url) {
        if (!saveForm.model_type) {
            saveForm.setError('model_type', t('admin.menus.menuItems.create.validation.modelIsRequired'))
            valid = false
        }

        if (!saveForm.model_id) {
            saveForm.setError('model_id', t('admin.menus.menuItems.create.validation.modelRecordIsRequired'))
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
            route('back-office.menus.menu-items.update', {
                slug: menu?.slug,
                menuItemSlug: menuItem?.slug,
            }),
            { ...saveForm.data(), _method: 'patch' },
            requestConfig
        )
    } else {
        saveForm.post(
            route('back-office.menus.menu-items.save', { slug: menu?.slug }),
            requestConfig
        )
    }
}

watch(
    () => saveForm.language_id,
    () => {
        saveForm.name = null
        saveForm.position = null
        saveForm.url = null
        saveForm.parent_id = null
        saveForm.model_id = null
        saveForm.model_type = null

        saveForm.seo_title = null
        saveForm.seo_brief = null
        saveForm.seo_keywords = []

        seoKeywordsKey.value++
        modelTypeKey.value++
        modelIdKey.value++
        parentKey.value++

        saveForm.clearErrors(
            'parent_id',
            'model_id',
            'model_type',
        )
    }
)

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: t('common.labels.menus'), href: route('back-office.menus.index') },
                {
                    text: `${menu?.name} ${t('common.actions.details')}`,
                    href: route('back-office.menus.details', { slug: menu?.slug }),
                },
                {
                    text: t('common.messages.menuItems'),
                    href: route('back-office.menus.menu-items.index', { slug: menu?.slug }),
                },
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
                            {{ t('admin.menus.menuItems.create.form.latestUrl') }}:

                            <code class="cursor-pointer bg-gray-100 px-2 py-1 rounded" @click="copyUrl('latest')">
                                {{ route('latest') }}
                            </code>

                            <span v-if="copiedRoute === 'latest'" class="text-green-600 ml-2">
                                {{ t('common.labels.copied') }}
                            </span>
                        </div>

                        <div>
                            {{ t('admin.menus.menuItems.create.form.homeUrl') }}:

                            <code class="cursor-pointer bg-gray-100 px-2 py-1 rounded" @click="copyUrl('home')">
                                {{ route('home') }}
                            </code>

                            <span v-if="copiedRoute === 'home'" class="text-green-600 ml-2">
                                {{ t('common.labels.copied') }}
                            </span>
                        </div>

                        <div>
                            {{ t('common.labels.videoUrl') }}:

                            <code class="cursor-pointer bg-gray-100 px-2 py-1 rounded" @click="copyUrl('video')">
                                {{ route('video') }}
                            </code>

                            <span v-if="copiedRoute === 'video'" class="text-green-600 ml-2">
                                {{ t('common.labels.copied') }}
                            </span>
                        </div>

                        <div>
                            {{ t('admin.menus.menuItems.create.form.imageGalleryUrl') }}:

                            <code class="cursor-pointer bg-gray-100 px-2 py-1 rounded" @click="copyUrl('image-gallery')">
                                {{ route('image-gallery') }}
                            </code>

                            <span v-if="copiedRoute === 'image-gallery'" class="text-green-600 ml-2">
                                {{ t('common.labels.copied') }}
                            </span>
                        </div>

                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.language') }}
                                <span class="text-red-500">*</span>
                            </label>

                            <InfiniteScrollApiSelect :form="saveForm" fieldName="language_id"
                                :selectedItem="menuItem?.language" :apiUrl="route('search.languages')"
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
                                {{ t('common.labels.position') }}
                            </label>

                            <input v-model="saveForm.position"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.position ? 'border-red-500' : 'border-gray-300'" type="number"
                                :placeholder="t('admin.menus.menuItems.create.form.positionPlaceholder')" />

                            <p v-if="saveForm.errors.position" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.position }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">
                                {{ t('admin.menus.menuItems.create.form.isCustomUrl') }}
                            </label>

                            <label class="inline-flex cursor-pointer items-center gap-3">
                                <input v-model="saveForm.is_custom_url" type="checkbox" class="peer sr-only" />

                                <span class="relative h-7 w-14 rounded-full bg-gray-300 transition
                                    after:absolute after:left-1 after:top-1 after:h-5 after:w-5
                                    after:rounded-full after:bg-white after:transition-all after:content-['']
                                    peer-checked:bg-green-600 peer-checked:after:translate-x-7">
                                </span>

                                <span class="text-sm text-gray-600">
                                    {{ saveForm.is_custom_url ? t('common.boolean.yes') : t('common.boolean.no') }}
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
                                {{ t('common.labels.model') }}
                                <span class="text-red-500">*</span>
                            </label>

                            <InfiniteScrollApiSelect :key="modelTypeKey" :form="saveForm" fieldName="model_type"
                                :selectedItem="saveForm?.model_type" :apiUrl="route('search.menu-item-models')"
                                :error="saveForm.errors.model_type" :multiple="false"
                                :placeholder="t('admin.menus.menuItems.create.form.modelPlaceholder')" />

                            <p v-if="saveForm.errors.model_type" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.model_type }}
                            </p>
                        </div>

                        <div v-if="!saveForm.is_custom_url && saveForm.model_type">
                            <label class="block text-sm font-medium mb-1">
                                {{ t('admin.menus.menuItems.create.form.modelId') }}
                                <span class="text-red-500">*</span>
                            </label>

                            <InfiniteScrollApiSelect v-if="saveForm?.model_type == menuModels.TAG"
                                :key="`tag-${modelIdKey}`" :form="saveForm" fieldName="model_id"
                                :selectedItem="saveForm?.model_id" :apiUrl="tagApiUrl" :error="saveForm.errors.model_id"
                                selectedLabelKey="name" selectedValueKey="id" apiLabelKey="name" apiValueKey="id"
                                :multiple="false" :placeholder="t('admin.menus.menuItems.create.form.tagPlaceholder')" />

                            <InfiniteScrollApiSelect v-if="saveForm?.model_type == menuModels.CATEGORY"
                                :key="`category-${modelIdKey}`" :form="saveForm" fieldName="model_id"
                                :selectedItem="menuItem?.model" :apiUrl="categoryApiUrl"
                                :error="saveForm.errors.model_id" selectedLabelKey="indentation_name"
                                selectedValueKey="id" apiLabelKey="indentation_name" apiValueKey="id" :multiple="false"
                                :placeholder="t('admin.menus.menuItems.create.form.categoryPlaceholder')" />

                            <InfiniteScrollApiSelect v-if="saveForm?.model_type == menuModels.PAGE"
                                :key="`page-${modelIdKey}`" :form="saveForm" fieldName="model_id"
                                :selectedItem="menuItem?.menu_model" :apiUrl="pageApiUrl" :error="saveForm.errors.model_id"
                                selectedLabelKey="indentation_title" selectedValueKey="id"
                                apiLabelKey="indentation_title" apiValueKey="id" :multiple="false"
                                :placeholder="t('admin.menus.menuItems.create.form.pagePlaceholder')" />

                            <p v-if="saveForm.errors.model_id" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.model_id }}
                            </p>
                        </div>
                    </div>

                    <div v-if="saveForm.is_custom_url" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ t('common.labels.url') }}
                                <span class="text-red-500">*</span>
                            </label>

                            <input v-model="saveForm.url"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.url ? 'border-red-500' : 'border-gray-300'" type="url"
                                :placeholder="t('admin.menus.menuItems.create.form.urlPlaceholder')" />

                            <p v-if="saveForm.errors.url" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.url }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">
                        {{ t('common.labels.hierarchy') }}
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">

                        <div>
                            <label class="block text-sm font-medium mb-2">
                                {{ t('common.labels.hasParent') }}
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
                                {{ t('common.placeholders.parent') }}
                                <span class="text-red-500">*</span>
                            </label>

                            <InfiniteScrollApiSelect :key="parentKey" :selectedItem="menuItem?.parent"
                                fieldName="parent_id" :form="saveForm" :apiUrl="menuItemApiUrl"
                                :error="saveForm.errors.parent_id" selectedLabelKey="indentation_name"
                                selectedValueKey="id" apiLabelKey="indentation_name" apiValueKey="id" :multiple="false"
                                :placeholder="t('admin.menus.menuItems.create.form.parentPlaceholder')" />

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

                        {{ saveForm.processing ? t('common.actions.saving') : t('common.actions.save') }}
                    </button>
                </div>

            </form>

        </div>
    </div>
</template>
