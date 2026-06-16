<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import RecentActivities from '@/components/back-office/activity-log/RecentModelActivityLogs.vue'

import { computed, onMounted, nextTick, inject } from 'vue'
import { Head } from '@inertiajs/vue3'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faPen } from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'
import { canEditSetting } from '@/composables/useAuthUserAccessPermissions'
import { useSetting } from '@/composables/useSetting'
import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faPen)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const { setting } = defineProps({
    setting: Object,
})

const authUser = inject('authUser')

const {
    settingValueTypes,
    hasValue,
} = useSetting()

const pageTitle = computed(() => `${setting?.label} ${t('labels.details')}`)

const canEdit = (setting) => canEditSetting(authUser?.value, setting)

const formattedValue = computed(() => {
    const type = setting?.type
    const value = setting?.value

    if (!hasValue(value)) {
        return t('labels.not_available')
    }

    if (type === settingValueTypes.BOOLEAN) {
        return value === true || value === 'true' || value === 1 || value === '1'
            ? t('labels.true')
            : t('labels.false')
    }

    if (
        type === settingValueTypes.JSON ||
        type === settingValueTypes.ARRAY
    ) {
        try {
            const parsedValue = typeof value === 'string'
                ? JSON.parse(value)
                : value

            return JSON.stringify(parsedValue, null, 2)
        } catch {
            return value
        }
    }

    return value
})

const isTrueValue = computed(() => formattedValue.value === t('labels.true'))
const hasDisplayValue = computed(() => formattedValue.value !== t('labels.not_available'))

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

    <div class="w-full space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">
                {{ t('settings.details.title') }}
            </h2>

            <div class="flex gap-2">
                <a v-if="canEdit(setting)" :href="route('back-office.settings.edit', { slug: setting?.slug })"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="pen" />
                    {{ t('buttons.edit') }}
                </a>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('labels.basic_information') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('settings.labels.group') }}</span>
                        <span class="font-medium">{{ setting?.group || t('labels.not_available') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('settings.labels.label') }}</span>
                        <span class="font-medium">{{ setting?.label || t('labels.not_available') }}</span>
                    </div>

                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('settings.labels.type') }}</span>

                        <span class="font-medium px-2 py-1 rounded-md text-xs bg-gray-100 text-gray-700">
                            {{ setting?.type ?? t('labels.not_available') }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">{{ t('settings.labels.value') }}</span>

                        <span v-if="setting?.type === settingValueTypes.BOOLEAN"
                            class="font-medium px-2 py-1 rounded-md text-xs" :class="isTrueValue
                                ? 'bg-green-100 text-green-700'
                                : 'bg-red-100 text-red-700'">
                            {{ formattedValue }}
                        </span>

                        <a v-else-if="setting?.type === settingValueTypes.URL && hasDisplayValue" :href="formattedValue"
                            target="_blank" class="font-medium text-blue-600 hover:underline break-all text-right">
                            {{ formattedValue }}
                        </a>

                        <img v-else-if="setting?.type === settingValueTypes.IMAGE && hasDisplayValue"
                            :src="formattedValue" :alt="t('settings.details.setting_image_alt')"
                            class="w-20 h-20 object-cover rounded-md border">

                        <span v-else-if="setting?.type === settingValueTypes.COLOR && hasDisplayValue"
                            class="font-medium flex items-center gap-2">
                            <span class="w-5 h-5 rounded border" :style="{ backgroundColor: formattedValue }"></span>

                            {{ formattedValue }}
                        </span>

                        <pre v-else-if="
                            setting?.type === settingValueTypes.JSON ||
                            setting?.type === settingValueTypes.ARRAY
                        "
                            class="font-medium bg-gray-50 border rounded-md p-2 text-xs overflow-x-auto max-w-full text-right">{{ formattedValue }}</pre>

                        <span v-else class="font-medium break-all text-right">
                            {{ formattedValue }}
                        </span>
                    </div>

                </div>

            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('labels.system_information') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('labels.created_at') }}</span>
                        <span class="font-medium">
                            {{ setting?.created_at ? formatDateTime(setting.created_at) : t('labels.not_available') }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('labels.updated_at') }}</span>
                        <span class="font-medium">
                            {{ setting?.updated_at ? formatDateTime(setting.updated_at) : t('labels.not_available') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('labels.updated_by') }}</span>
                        <span class="font-medium">
                            {{ setting?.latest_activity_log?.causer?.name || t('labels.not_available') }}
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('activity_logs.index.title') }}
            </h3>

            <RecentActivities :model-slug="'setting'" :model="setting" />
        </div>

    </div>
</template>
