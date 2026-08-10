<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'
import RecentActivities from '@/components/back-office/activity-log/RecentModelActivityLogs.vue'
import { computed, inject, onMounted, nextTick } from 'vue'
import { Head } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faPen } from '@fortawesome/free-solid-svg-icons'
import { formatDateTime } from '@/composables/useDateTime'
import { canUpdateTheme } from '@/composables/useUserPermissions'
import { useTheme } from '@/composables/useTheme'
import { useTranslate } from '@/composables/useTranslate'
import { titleFormat } from '@/composables/useStringFormat'

FontAwesomeLibrary.add(faPen)

defineOptions({
    layout: Layout,
})

const { t } = useTranslate()

const { theme } = defineProps({
    theme: {
        type: Object,
        required: true,
    },
})

const authUser = inject('authUser')

const {
    themeOptionValueTypes,
    isTruthyValue,
    formatThemeValue,
} = useTheme()

const pageTitle = computed(() => {
    return `${theme?.name || t('common.labels.notAvailable')} ${t('common.actions.details')}`
})

const canUpdate = (theme) => {
    return canUpdateTheme(authUser?.value, theme)
}

const themeOptions = computed(() => {
    if (
        !theme?.options ||
        typeof theme.options !== 'object' ||
        Array.isArray(theme.options)
    ) {
        return {}
    }
    return theme.options
})

const optionCount = computed(() => {
    return Object.keys(themeOptions.value).length
})

const formatOptionLabel = (key) => {
    return titleFormat(
        String(key).replace(/[-_]+/g, ' ')
    )
}

const hasOptionValue = (value) => {
    return value !== null &&
        value !== undefined &&
        value !== ''
}

onMounted(async () => {
    await nextTick()
    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                {
                    text: t('common.labels.themes'),
                    href: route('back-office.themes.index'),
                },
                {
                    text: pageTitle.value,
                    active: true,
                },
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
                {{ t('admin.themes.details.title') }}
            </h2>
            <div class="flex gap-2">
                <a
                    v-if="canUpdate(theme)"
                    :href="route('back-office.themes.edit', { slug: theme?.slug })"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition"
                >
                    <FontAwesomeIcon icon="pen" />
                    {{ t('common.actions.edit') }}
                </a>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('common.labels.basicInformation') }}
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-3">
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">
                            {{ t('common.labels.name') }}
                        </span>
                        <span class="font-medium text-right">
                            {{ theme?.name || t('common.labels.notAvailable') }}
                        </span>
                    </div>
                </div>
                <div class="border border-gray-200 rounded-lg p-4 space-y-3">
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">
                            {{ t('common.labels.options') }}
                        </span>
                        <span class="font-medium">
                            {{ optionCount }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Options -->
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="p-5 border-b border-gray-200">
                <h3 class="text-base font-semibold">
                    {{ t('common.labels.options') }}
                </h3>
            </div>

            <div v-if="optionCount">
                <div
                    v-for="(option, key) in theme?.options"
                    :key="key"
                    class="border-b border-gray-200 last:border-b-0"
                >
                    <div class="grid grid-cols-1 md:grid-cols-12">
                        <div class="md:col-span-4 bg-gray-50 p-5 border-b md:border-b-0 md:border-r border-gray-200">
                            <div class="font-semibold text-gray-900 break-words">
                                {{ formatOptionLabel(key) }}
                            </div>
                        </div>
                        <div class="md:col-span-8 p-5">
                            <!-- Boolean -->
                            <span
                                v-if="option?.valueType === themeOptionValueTypes.BOOLEAN"
                                class="inline-flex px-2.5 py-1 rounded-md text-xs font-medium"
                                :class="isTruthyValue(option?.value)
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-red-100 text-red-700'"
                            >
                                {{
                                    isTruthyValue(option?.value)
                                        ? t('common.labels.true')
                                        : t('common.labels.false')
                                }}
                            </span>

                            <!-- URL -->
                            <a
                                v-else-if="
                                    option?.valueType === themeOptionValueTypes.URL &&
                                    hasOptionValue(option?.value)
                                "
                                :href="option.value"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-blue-600 hover:underline break-all"
                            >
                                {{ option.value }}
                            </a>

                            <!-- Image -->
                            <div
                                v-else-if="
                                    option?.valueType === themeOptionValueTypes.IMAGE &&
                                    hasOptionValue(option?.value)
                                "
                                class="space-y-2"
                            >
                                <img
                                    :src="option.value"
                                    :alt="formatOptionLabel(key)"
                                    class="max-w-xs max-h-40 object-contain rounded-md border border-gray-200"
                                >
                                <div class="text-xs text-gray-500 break-all">
                                    {{ option.value }}
                                </div>
                            </div>

                            <!-- Color -->
                            <div
                                v-else-if="
                                    option?.valueType === themeOptionValueTypes.COLOR &&
                                    hasOptionValue(option?.value)
                                "
                                class="flex items-center gap-3"
                            >
                                <span
                                    class="w-8 h-8 rounded-md border border-gray-300"
                                    :style="{ backgroundColor: option.value }"
                                ></span>
                                <span class="font-medium break-all">
                                    {{ option.value }}
                                </span>
                            </div>

                            <!-- JSON / Array -->
                            <pre
                                v-else-if="
                                    option?.valueType === themeOptionValueTypes.JSON ||
                                    option?.valueType === themeOptionValueTypes.ARRAY
                                "
                                class="bg-gray-50 border border-gray-200 rounded-md p-3 text-xs overflow-x-auto whitespace-pre-wrap break-words"
                            >{{ formatThemeValue(option?.value) }}</pre>

                            <!-- Other values -->
                            <span
                                v-else-if="hasOptionValue(option?.value)"
                                class="font-medium text-gray-900 break-all whitespace-pre-wrap"
                            >
                                {{ option.value }}
                            </span>

                            <!-- Empty -->
                            <span v-else class="text-gray-400 italic">
                                {{ t('common.labels.notAvailable') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="px-5 py-10 text-center text-gray-500">
                {{ t('common.labels.notAvailable') }}
            </div>
        </div>

        <!-- System Information -->
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('common.labels.systemInformation') }}
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">
                            {{ t('common.labels.createdAt') }}
                        </span>
                        <span class="font-medium text-right">
                            {{
                                theme?.created_at
                                    ? formatDateTime(theme.created_at)
                                    : t('common.labels.notAvailable')
                            }}
                        </span>
                    </div>
                </div>
                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">
                            {{ t('common.labels.updatedAt') }}
                        </span>
                        <span class="font-medium text-right">
                            {{
                                theme?.updated_at
                                    ? formatDateTime(theme.updated_at)
                                    : t('common.labels.notAvailable')
                            }}
                        </span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">
                            {{ t('common.labels.updatedBy') }}
                        </span>
                        <span class="font-medium text-right">
                            {{
                                theme?.latest_activity_log?.causer?.name ||
                                t('common.labels.notAvailable')
                            }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">
                {{ t('common.labels.activityLogs') }}
            </h3>
            <RecentActivities :model-slug="'theme'" :model="theme" />
        </div>
    </div>
</template>
