<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import RecentActivities from '@/components/back-office/activity-log/RecentModelActivityLogs.vue'

import { computed, onMounted, nextTick, inject } from 'vue'
import { Head } from '@inertiajs/vue3'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faPen } from '@fortawesome/free-solid-svg-icons'

import { formatDateTime } from '@/composables/useDateTime'
import { canEditTheme } from '@/composables/useAuthUserAccessPermissions'
import { useTheme } from '@/composables/useTheme'
import { useTranslate } from '@/composables/useTranslate'

FontAwesomeLibrary.add(faPen)

defineOptions({ layout: Layout })

const { t } = useTranslate()

const { theme } = defineProps({
    theme: Object,
})

const authUser = inject('authUser')

const {
    themeValueTypes,
    hasValue,
} = useTheme()

const pageTitle = computed(() => `${theme?.label} ${t('pages.back_office.themes.details.labels.details')}`)

const canEdit = (theme) => canEditTheme(authUser?.value, theme)

const formattedValue = computed(() => {
    const type = theme?.type
    const value = theme?.value

    if (!hasValue(value)) {
        return t('pages.back_office.themes.details.labels.not_available')
    }

    if (type === themeValueTypes.BOOLEAN) {
        return value === true || value === 'true' || value === 1 || value === '1'
            ? t('pages.back_office.themes.details.labels.true')
            : t('pages.back_office.themes.details.labels.false')
    }

    if (
        type === themeValueTypes.JSON ||
        type === themeValueTypes.ARRAY
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

const isTrueValue = computed(() => formattedValue.value === t('pages.back_office.themes.details.labels.true'))
const hasDisplayValue = computed(() => formattedValue.value !== t('pages.back_office.themes.details.labels.not_available'))

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: t('pages.back_office.themes.details.labels.themes'), href: route('back-office.themes.index') },
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
                {{ t('pages.back_office.themes.details.title') }}
            </h2>

            <div class="flex gap-2">
                <a v-if="canEdit(theme)" :href="route('back-office.themes.edit', { slug: theme?.slug })"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="pen" />
                    {{ t('pages.back_office.themes.details.actions.edit') }}
                </a>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('pages.back_office.themes.details.labels.basic_information') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.themes.details.labels.group') }}</span>
                        <span class="font-medium">{{ theme?.group || t('pages.back_office.themes.details.labels.not_available') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.themes.details.labels.label') }}</span>
                        <span class="font-medium">{{ theme?.label || t('pages.back_office.themes.details.labels.not_available') }}</span>
                    </div>

                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.themes.details.labels.type') }}</span>

                        <span class="font-medium px-2 py-1 rounded-md text-xs bg-gray-100 text-gray-700">
                            {{ theme?.type ?? t('pages.back_office.themes.details.labels.not_available') }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">{{ t('pages.back_office.themes.details.labels.value') }}</span>

                        <span v-if="theme?.type === themeValueTypes.BOOLEAN"
                            class="font-medium px-2 py-1 rounded-md text-xs" :class="isTrueValue
                                ? 'bg-green-100 text-green-700'
                                : 'bg-red-100 text-red-700'">
                            {{ formattedValue }}
                        </span>

                        <a v-else-if="theme?.type === themeValueTypes.URL && hasDisplayValue" :href="formattedValue"
                            target="_blank" class="font-medium text-blue-600 hover:underline break-all text-right">
                            {{ formattedValue }}
                        </a>

                        <img v-else-if="theme?.type === themeValueTypes.IMAGE && hasDisplayValue"
                            :src="formattedValue" :alt="t('pages.back_office.themes.details.theme_image_alt')"
                            class="w-20 h-20 object-cover rounded-md border">

                        <span v-else-if="theme?.type === themeValueTypes.COLOR && hasDisplayValue"
                            class="font-medium flex items-center gap-2">
                            <span class="w-5 h-5 rounded border" :style="{ backgroundColor: formattedValue }"></span>

                            {{ formattedValue }}
                        </span>

                        <pre v-else-if="
                            theme?.type === themeValueTypes.TEXT ||
                            theme?.type === themeValueTypes.JSON ||
                            theme?.type === themeValueTypes.ARRAY
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
                {{ t('pages.back_office.themes.details.labels.system_information') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.themes.details.labels.created_at') }}</span>
                        <span class="font-medium">
                            {{ theme?.created_at ? formatDateTime(theme.created_at) : t('pages.back_office.themes.details.labels.not_available') }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.themes.details.labels.updated_at') }}</span>
                        <span class="font-medium">
                            {{ theme?.updated_at ? formatDateTime(theme.updated_at) : t('pages.back_office.themes.details.labels.not_available') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ t('pages.back_office.themes.details.labels.updated_by') }}</span>
                        <span class="font-medium">
                            {{ theme?.latest_activity_log?.causer?.name || t('pages.back_office.themes.details.labels.not_available') }}
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('pages.back_office.themes.details.activity_logs.index.title') }}
            </h3>

            <RecentActivities :model-slug="'theme'" :model="theme" />
        </div>

    </div>
</template>
