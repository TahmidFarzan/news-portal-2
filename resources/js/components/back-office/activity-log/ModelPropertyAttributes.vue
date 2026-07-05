<script setup>
import { computed, ref } from 'vue'

import ModelPropertyAttributes from '@/components/back-office/activity-log/ModelPropertyAttributes.vue'

import { titleFormat } from '@/composables/useUtil'
import { formatDateTime } from '@/composables/useDateTime'
import { fetchUser } from '@/composables/useSystemApi'

const { property } = defineProps({
    property: {
        required: true,
    },
    activityLog: {
        type: Object,
    },
})

const tUser = ref({})

function isObject(val) {
    return Object.prototype.toString.call(val) === '[object Object]'
}

function isBase64Image(val) {
    return typeof val === 'string' && val.startsWith('data:image')
}

function isEmptyValue(value) {
    if (value === null || value === undefined) return true

    if (typeof value === 'string') {
        return value.trim() === ''
    }

    if (Array.isArray(value)) {
        return value.length === 0
    }

    if (isObject(value)) {
        return Object.values(value).every(isEmptyValue)
    }

    return false
}

const filteredProperty = computed(() => {
    if (!isObject(property)) {
        return {}
    }

    return Object.fromEntries(
        Object.entries(property).filter(([, value]) => !isEmptyValue(value)),
    )
})

function formatValue(key, value) {
    if (
        ['created_at', 'updated_at', 'deleted_at', 'email_verified_at'].includes(key) &&
        value
    ) {
        return formatDateTime(value)
    }

    if (
        (key === 'user_id' || key === 'created_by_id') &&
        Number.isInteger(Number(value))
    ) {
        if (!(value in tUser.value)) {
            tUser.value[value] = null

            fetchUser(value)
                .then((user) => {
                    tUser.value[value] = user || null
                })
                .catch(() => {
                    tUser.value[value] = null
                })

            return 'Loading...'
        }

        return tUser.value[value]?.name || 'Unknown'
    }

    return value
}
</script>

<template>
    <div v-if="Object.keys(filteredProperty).length" class="space-y-2">
        <template v-for="(value, key) in filteredProperty" :key="key">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                <div class="mb-1 text-sm font-semibold text-gray-700">
                    {{ titleFormat(key) }}
                </div>

                <div class="text-sm text-gray-800">
                    <template v-if="isObject(value)">
                        <div class="mt-2 ml-4 border-l-2 border-gray-200 pl-3">
                            <ModelPropertyAttributes :property="value" :activityLog="activityLog" />
                        </div>
                    </template>

                    <template v-else-if="Array.isArray(value)">
                        <ul class="ml-4 list-disc space-y-1">
                            <li v-for="(item, index) in value" :key="index">
                                <template v-if="isObject(item)">
                                    <div class="rounded border border-gray-200 bg-white p-2">
                                        <ModelPropertyAttributes :property="item" :activityLog="activityLog" />
                                    </div>
                                </template>

                                <template v-else-if="Array.isArray(item)">
                                    <div class="rounded border border-gray-200 bg-white p-2">
                                        {{ item.join(', ') }}
                                    </div>
                                </template>

                                <template v-else>
                                    {{ formatValue(key, item) }}
                                </template>
                            </li>
                        </ul>
                    </template>

                    <template v-else-if="isBase64Image(value)">
                        <div class="mt-2">
                            <img :src="value" class="max-w-[150px] rounded-lg border shadow-sm">
                        </div>
                    </template>

                    <template v-else-if="key === 'content'">
                        <div class="prose prose-sm max-w-none" v-html="value" />
                    </template>

                    <template v-else>
                        {{ formatValue(key, value) }}
                    </template>
                </div>
            </div>
        </template>
    </div>

    <div v-else-if="property !== null && property !== undefined" class="text-sm text-gray-800">
        {{ property }}
    </div>
</template>
