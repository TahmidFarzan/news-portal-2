<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'

import { computed, onMounted, nextTick, watch } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'

import { useSetting } from '@/composables/useSetting'
import { useTranslate } from '@/composables/useTranslate'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faSave, faSpinner } from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faSave, faSpinner)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const { setting } = defineProps({
    setting: Object,
})

const {
    settingValueTypes,
    isEmpty,
    hasValue,
    getDefaultValueByType,
} = useSetting()

const isUpdate = computed(() => !!setting?.slug)

const pageTitle = computed(() => {
    return isUpdate.value
        ? `${setting?.label} ${t('buttons.edit')}`
        : t('settings.form.create_page_title')
})

const saveForm = useForm({
    group: setting?.group ?? null,
    label: setting?.label ?? null,
    type: setting?.type ?? null,
    value: setting?.value ?? null,
})

const hasSettingIdentity = computed(() => {
    return !isEmpty(saveForm.group) && !isEmpty(saveForm.label)
})

const canSubmit = computed(() => {
    return hasSettingIdentity.value &&
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
        saveForm.setError('group', t('settings.form.validation.group_required'))
        valid = false
    }

    if (isEmpty(saveForm.label)) {
        saveForm.setError('label', t('settings.form.validation.label_required'))
        valid = false
    }

    if (isEmpty(saveForm.type)) {
        saveForm.setError('type', t('settings.form.validation.type_required'))
        valid = false
    }

    if (!hasValue(saveForm.value)) {
        saveForm.setError('value', t('settings.form.validation.value_required'))
        valid = false
    }

    if (hasValue(saveForm.value) && saveForm.type === settingValueTypes.INTEGER) {
        if (!Number.isInteger(Number(saveForm.value))) {
            saveForm.setError('value', t('settings.form.validation.value_must_be_integer'))
            valid = false
        }
    }

    if (
        hasValue(saveForm.value) &&
        [settingValueTypes.FLOAT, settingValueTypes.DECIMAL].includes(saveForm.type)
    ) {
        if (Number.isNaN(Number(saveForm.value))) {
            saveForm.setError('value', t('settings.form.validation.value_must_be_valid_number'))
            valid = false
        }
    }

    if (
        hasValue(saveForm.value) &&
        [settingValueTypes.JSON, settingValueTypes.ARRAY].includes(saveForm.type)
    ) {
        try {
            const parsedValue = JSON.parse(saveForm.value)

            if (saveForm.type === settingValueTypes.ARRAY && !Array.isArray(parsedValue)) {
                saveForm.setError('value', t('settings.form.validation.value_must_be_valid_json_array'))
                valid = false
            }
        } catch {
            saveForm.setError('value', t('settings.form.validation.value_must_be_valid_json'))
            valid = false
        }
    }

    if (hasValue(saveForm.value) && saveForm.type === settingValueTypes.URL) {
        try {
            new URL(saveForm.value)
        } catch {
            saveForm.setError('value', t('settings.form.validation.value_must_be_valid_url'))
            valid = false
        }
    }

    if (hasValue(saveForm.value) && saveForm.type === settingValueTypes.COLOR) {
        const colorRegex = /^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/

        if (!colorRegex.test(saveForm.value)) {
            saveForm.setError('value', t('settings.form.validation.value_must_be_valid_color'))
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
        .post(route('back-office.settings.update', { slug: setting?.slug }), {
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
                { text: t('labels.settings'), href: route('back-office.settings.index') },
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

            <div v-if="!hasSettingIdentity" class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-4">
                <h3 class="font-semibold mb-1">
                    {{ t('settings.form.invalid_setting_data') }}
                </h3>

                <p class="text-sm">
                    {{ t('settings.form.invalid_setting_data_body') }}
                </p>

                <div class="mt-3 space-y-1 text-sm">
                    <p v-if="saveForm.errors.group">{{ saveForm.errors.group }}</p>
                    <p v-if="saveForm.errors.label">{{ saveForm.errors.label }}</p>
                </div>
            </div>

            <form v-else @submit.prevent="handleSave" class="space-y-6">
                <div class="bg-white border rounded-xl p-5 shadow-sm space-y-4">
                    <h3 class="text-base font-semibold">
                        {{ t('labels.basic_information') }}
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium mb-1">
                                {{ saveForm.label }} <span class="text-red-500">*</span>
                            </label>

                            <textarea v-if="saveForm.type === settingValueTypes.TEXT" v-model="saveForm.value" rows="4"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.value ? 'border-red-500' : 'border-gray-300'"></textarea>

                            <input v-else-if="saveForm.type === settingValueTypes.STRING" v-model="saveForm.value"
                                type="text"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.value ? 'border-red-500' : 'border-gray-300'" />

                            <select v-else-if="saveForm.type === settingValueTypes.BOOLEAN" v-model="saveForm.value"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.value ? 'border-red-500' : 'border-gray-300'">
                                <option :value="true">{{ t('labels.true') }}</option>
                                <option :value="false">{{ t('labels.false') }}</option>
                            </select>

                            <input v-else-if="saveForm.type === settingValueTypes.INTEGER" v-model="saveForm.value"
                                type="number" step="1"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.value ? 'border-red-500' : 'border-gray-300'" />

                            <input v-else-if="
                                saveForm.type === settingValueTypes.FLOAT ||
                                saveForm.type === settingValueTypes.DECIMAL
                            " v-model="saveForm.value" type="number" step="any"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.value ? 'border-red-500' : 'border-gray-300'" />

                            <textarea v-else-if="
                                saveForm.type === settingValueTypes.JSON ||
                                saveForm.type === settingValueTypes.ARRAY
                            " v-model="saveForm.value" rows="6" :placeholder="t('settings.form.json_placeholder')"
                                class="w-full border rounded-md px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.value ? 'border-red-500' : 'border-gray-300'"></textarea>

                            <input v-else-if="saveForm.type === settingValueTypes.URL" v-model="saveForm.value"
                                type="url" :placeholder="t('settings.form.url_placeholder')"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.value ? 'border-red-500' : 'border-gray-300'" />

                            <input v-else-if="saveForm.type === settingValueTypes.IMAGE" type="file" accept="image/*"
                                @change="handleImageChange"
                                class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                :class="saveForm.errors.value ? 'border-red-500' : 'border-gray-300'" />

                            <div v-else-if="saveForm.type === settingValueTypes.COLOR" class="flex gap-3">
                                <input v-model="saveForm.value" type="color" class="w-16 h-10 border rounded-md" />

                                <input v-model="saveForm.value" type="text"
                                    :placeholder="t('settings.form.color_placeholder')"
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
                        {{ saveForm.processing ? t('buttons.saving') : t('buttons.save') }}
                    </button>
                </div>
            </form>

        </div>
    </div>
</template>
