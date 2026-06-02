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

FontAwesomeLibrary.add(faPen)

defineOptions({ layout: Layout })

const { setting } = defineProps({
    setting: Object,
})

const authUser = inject('authUser')

const {
    settingTypes,
    hasValue,
} = useSetting()

const canEdit = (setting) => canEditSetting(authUser?.value, setting)

const formattedValue = computed(() => {
    const type = setting?.type
    const value = setting?.value

    if (!hasValue(value)) {
        return 'N/A'
    }

    if (type === settingTypes.BOOLEAN) {
        return value === true || value === 'true' || value === 1 || value === '1'
            ? 'True'
            : 'False'
    }

    if (
        type === settingTypes.JSON ||
        type === settingTypes.ARRAY
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

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: 'Settings', href: route('back-office.settings.index') },
                { text: `${setting?.label} details`, active: true },
            ],
        })
    )
})
</script>

<template>

    <Head :title="`${setting?.label} details`" />

    <div class="w-full space-y-6">

        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold">Setting Details</h2>

            <div class="flex gap-2">
                <a v-if="canEdit(setting)" :href="route('back-office.settings.edit', { slug: setting?.slug })"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md flex items-center gap-2 transition">
                    <FontAwesomeIcon icon="pen" />
                    Edit
                </a>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">Basic Information</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">

                    <div class="flex justify-between">
                        <span class="text-gray-500">Group</span>
                        <span class="font-medium">{{ setting?.group || 'N/A' }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Label</span>
                        <span class="font-medium">{{ setting?.label || 'N/A' }}</span>
                    </div>

                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">

                    <div class="flex justify-between">
                        <span class="text-gray-500">Type</span>

                        <span class="font-medium px-2 py-1 rounded-md text-xs bg-gray-100 text-gray-700">
                            {{ setting?.type ?? 'N/A' }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-4">
                        <span class="text-gray-500">Value</span>

                        <span v-if="setting?.type === settingTypes.BOOLEAN"
                            class="font-medium px-2 py-1 rounded-md text-xs" :class="formattedValue === 'True'
                                ? 'bg-green-100 text-green-700'
                                : 'bg-red-100 text-red-700'">
                            {{ formattedValue }}
                        </span>

                        <a v-else-if="setting?.type === settingTypes.URL && formattedValue !== 'N/A'"
                            :href="formattedValue" target="_blank"
                            class="font-medium text-blue-600 hover:underline break-all text-right">
                            {{ formattedValue }}
                        </a>

                        <img v-else-if="setting?.type === settingTypes.IMAGE && formattedValue !== 'N/A'"
                            :src="formattedValue" alt="Setting image" class="w-20 h-20 object-cover rounded-md border">

                        <span v-else-if="setting?.type === settingTypes.COLOR && formattedValue !== 'N/A'"
                            class="font-medium flex items-center gap-2">
                            <span class="w-5 h-5 rounded border" :style="{ backgroundColor: formattedValue }"></span>

                            {{ formattedValue }}
                        </span>

                        <pre v-else-if="
                            setting?.type === settingTypes.JSON ||
                            setting?.type === settingTypes.ARRAY
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
            <h3 class="text-base font-semibold border-b pb-2">System Information</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Created At</span>
                        <span class="font-medium">
                            {{ setting?.created_at ? formatDateTime(setting.created_at) : 'N/A' }}
                        </span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Updated At</span>
                        <span class="font-medium">
                            {{ setting?.updated_at ? formatDateTime(setting.updated_at) : 'N/A' }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-500">Updated By</span>
                        <span class="font-medium">
                            {{ setting?.latest_activity_log?.causer?.name || 'N/A' }}
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-base font-semibold border-b pb-2">Activity Logs</h3>

            <RecentActivities :model-slug="'setting'" :model="setting" />
        </div>

    </div>
</template>
