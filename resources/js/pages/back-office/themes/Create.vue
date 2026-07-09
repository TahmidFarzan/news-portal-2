<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'

import { computed, onMounted, nextTick, watch } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'

import { useTheme } from '@/composables/useTheme'
import { useTranslate } from '@/composables/useTranslate'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faSave, faSpinner } from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faSave, faSpinner)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const { theme } = defineProps({
    theme: Object,
})

const {
    themeValueTypes,
    isEmpty,
    hasValue,
    getDefaultValueByType,
} = useTheme()

const isUpdate = computed(() => !!theme?.slug)

const pageTitle = computed(() => {
    return isUpdate.value
        ? `${theme?.label} ${t('common.actions.edit')}`
        : t('admin.themes.create.form.createPageTitle')
})

const saveForm = useForm({
    group: theme?.group ?? null,
    label: theme?.label ?? null,
    type: theme?.type ?? null,
    value: theme?.value ?? null,
})

const hasThemeIdentity = computed(() => {
    return !isEmpty(saveForm.group) && !isEmpty(saveForm.label)
})

const canSubmit = computed(() => {
    return hasThemeIdentity.value &&
        !isEmpty(saveForm.label) &&
        !isEmpty(saveForm.type) &&
        hasValue(saveForm.value) &&
        !saveForm.processing
})

watch(
    () => saveForm.type,
    (type) => {
        saveForm.clearErrors('value')

        const defaultValue = getDefaultValueByType(type)

        if (isEmpty(saveForm.value) && defaultValue !== null) {
            saveForm.value = defaultValue
        }
    }
)

function handleImageChange(event) {
    const file = event.target.files?.[0] ?? null
    saveForm.value = file
}

function validateForm() {
    saveForm.clearErrors()

    let valid = true

    if (isEmpty(saveForm.group)) {
        saveForm.setError('group', t('admin.themes.create.form.validation.groupRequired'))
        valid = false
    }

    if (isEmpty(saveForm.label)) {
        saveForm.setError('label', t('admin.themes.create.form.validation.labelRequired'))
        valid = false
    }

    if (isEmpty(saveForm.type)) {
        saveForm.setError('type', t('common.validation.typeIsRequired'))
        valid = false
    }

    if (!hasValue(saveForm.value)) {
        saveForm.setError('value', t('admin.themes.create.form.validation.valueRequired'))
        valid = false
    }

    if (hasValue(saveForm.value) && saveForm.type === themeValueTypes.INTEGER) {
        if (!Number.isInteger(Number(saveForm.value))) {
            saveForm.setError('value', t('admin.themes.create.form.validation.valueMustBeInteger'))
            valid = false
        }
    }

    if (hasValue(saveForm.value) && saveForm.type === themeValueTypes.TEXT) {
        if (saveForm.value) {
            saveForm.setError('value', t('admin.themes.create.form.validation.valueMustBeText'))
            valid = false
        }
    }

    if (hasValue(saveForm.value) && [themeValueTypes.FLOAT, themeValueTypes.DECIMAL].includes(saveForm.type)) {
        if (Number.isNaN(Number(saveForm.value))) {
            saveForm.setError('value', t('admin.themes.create.form.validation.valueMustBeValidNumber'))
            valid = false
        }
    }

    if (hasValue(saveForm.value) && [themeValueTypes.JSON, themeValueTypes.ARRAY].includes(saveForm.type)) {
        try {
            const parsedValue = JSON.parse(saveForm.value)

            if (saveForm.type === themeValueTypes.ARRAY && !Array.isArray(parsedValue)) {
                saveForm.setError('value', t('admin.themes.create.form.validation.valueMustBeValidJsonArray'))
                valid = false
            }
        } catch {
            saveForm.setError('value', t('admin.themes.create.form.validation.valueMustBeValidJson'))
            valid = false
        }
    }

    if (hasValue(saveForm.value) && saveForm.type === themeValueTypes.URL) {
        try {
            new URL(saveForm.value)
        } catch {
            saveForm.setError('value', t('admin.themes.create.form.validation.valueMustBeValidUrl'))
            valid = false
        }
    }

    if (hasValue(saveForm.value) && saveForm.type === themeValueTypes.COLOR) {
        const colorRegex = /^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/

        if (!colorRegex.test(saveForm.value)) {
            saveForm.setError('value', t('admin.themes.create.form.validation.valueMustBeValidColor'))
            valid = false
        }
    }

    return valid
}

function handleSave() {
    if (saveForm.processing) return

    if (!validateForm()) return

    saveForm
        .transform((data) => ({
            ...data,
            _method: 'patch',
        }))
        .post(route('back-office.themes.update', { slug: theme?.slug }), {
            preserveScroll: true,
            preserveState: true,
            forceFormData: true,
            onSuccess: () => {
                saveForm.clearErrors()
            },
        })
}

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: t('common.labels.themes'), href: route('back-office.themes.index') },
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

            <div v-if="!hasThemeIdentity" class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-4">
                <h3 class="font-semibold mb-1">
                    {{ t('admin.themes.create.form.invalidThemeData') }}
                </h3>

                <p class="text-sm">
                    {{ t('admin.themes.create.form.invalidThemeDataBody') }}
                </p>

                <div class="mt-3 space-y-1 text-sm">
                    <p v-if="saveForm.errors.group">{{ saveForm.errors.group }}</p>
                    <p v-if="saveForm.errors.label">{{ saveForm.errors.label }}</p>
                </div>
            </div>

            <form v-else @submit.prevent="handleSave" class="space-y-6">
                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">
                        {{ t('common.labels.basicInformation') }}
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ saveForm.label }} <span class="text-red-500">*</span>
                            </label>

                            <textarea v-if="saveForm.type === themeValueTypes.TEXT" v-model="saveForm.value" rows="4"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.value ? 'border-red-500' : 'border-gray-300'"></textarea>

                            <input v-else-if="saveForm.type === themeValueTypes.STRING" v-model="saveForm.value"
                                type="text"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.value ? 'border-red-500' : 'border-gray-300'" />

                            <select v-else-if="saveForm.type === themeValueTypes.BOOLEAN" v-model="saveForm.value"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.value ? 'border-red-500' : 'border-gray-300'">
                                <option :value="true">{{ t('common.labels.true') }}</option>
                                <option :value="false">{{ t('common.labels.false') }}</option>
                            </select>

                            <input v-else-if="saveForm.type === themeValueTypes.INTEGER" v-model="saveForm.value"
                                type="number" step="1"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.value ? 'border-red-500' : 'border-gray-300'" />

                            <input v-else-if="
                                saveForm.type === themeValueTypes.FLOAT ||
                                saveForm.type === themeValueTypes.DECIMAL
                            " v-model="saveForm.value" type="number" step="any"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.value ? 'border-red-500' : 'border-gray-300'" />

                            <textarea v-else-if="
                                saveForm.type === themeValueTypes.JSON ||
                                saveForm.type === themeValueTypes.ARRAY
                            " v-model="saveForm.value" rows="6"
                                :placeholder="t('admin.themes.create.form.jsonPlaceholder')"
                                class="w-full border rounded-md px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.value ? 'border-red-500' : 'border-gray-300'"></textarea>

                            <input v-else-if="saveForm.type === themeValueTypes.URL" v-model="saveForm.value" type="url"
                                :placeholder="t('admin.themes.create.form.urlPlaceholder')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.value ? 'border-red-500' : 'border-gray-300'" />

                            <input v-else-if="saveForm.type === themeValueTypes.IMAGE" type="file" accept="image/*"
                                @change="handleImageChange"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.value ? 'border-red-500' : 'border-gray-300'" />

                            <div v-else-if="saveForm.type === themeValueTypes.COLOR" class="flex gap-3">
                                <input v-model="saveForm.value" type="color" class="w-16 h-10 border rounded-md" />

                                <input v-model="saveForm.value" type="text"
                                    :placeholder="t('admin.themes.create.form.colorPlaceholder')"
                                    class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                    :class="saveForm.errors.value ? 'border-red-500' : 'border-gray-300'" />
                            </div>

                            <input v-else v-model="saveForm.value" type="text"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.value ? 'border-red-500' : 'border-gray-300'" />

                            <p v-if="saveForm.errors.value" class="text-red-500 text-sm mt-1">
                                {{ saveForm.errors.value }}
                            </p>
                        </div>

                    </div>
                </div>

                <div class="flex justify-center">
                    <button type="submit" :disabled="!canSubmit"
                        class="text-white px-6 py-2 rounded-md flex items-center gap-2 transition" :class="canSubmit
                            ? 'bg-green-600 hover:bg-green-700'
                            : 'bg-gray-400 cursor-not-allowed'">
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
