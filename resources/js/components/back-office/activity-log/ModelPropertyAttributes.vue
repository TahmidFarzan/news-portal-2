<script setup>
import ModelPropertyAttributes from '@/components/back-office/activity-log/ModelPropertyAttributes.vue'

import { ref, computed } from 'vue'

import { titleFormat } from '@/composables/useUtil'
import { formatDateTime } from '@/composables/useDateTime'
import { fetchUser } from '@/composables/useSystemApi'

const { property } = defineProps({
    property: { type: [Object, Array], required: true },
    activityLog: { type: Object }
})

const tUser = ref({})

function isObject(val) {
    return typeof val === 'object' && val !== null && !Array.isArray(val)
}

function isBase64Image(val) {
    return typeof val === 'string' && val.startsWith('data:image')
}

function isEmptyValue(value) {
    if (value === null || value === undefined) return true
    if (typeof value === 'string' && value.trim() === '') return true
    if (Array.isArray(value) && value.length === 0) return true

    if (isObject(value)) {
        return Object.values(value).every(isEmptyValue)
    }

    return false
}

const filteredProperty = computed(() => {
    if (!property || typeof property !== 'object') return {}

    return Object.fromEntries(
        Object.entries(property).filter(([, value]) => !isEmptyValue(value))
    )
})

function formatValue(key, value) {
    if (['created_at', 'updated_at', 'deleted_at', 'email_verified_at'].includes(key) && value) {
        return formatDateTime(value)
    }

    if ((key === 'user_id' || key === 'created_by_id') && Number.isInteger(Number(value))) {
        if (!(value in tUser.value)) {
            tUser.value[value] = null
            fetchUser(value)
                .then((rUser) => (tUser.value[value] = rUser || null))
                .catch(() => (tUser.value[value] = null))
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

            <div class="border border-gray-200 rounded-lg p-3 bg-gray-50">

                <div class="text-sm font-semibold text-gray-700 mb-1">
                    {{ titleFormat(key) }}
                </div>

                <div class="text-sm text-gray-800">

                    <template v-if="isObject(value)">
                        <div class="ml-4 mt-2 border-l-2 border-gray-200 pl-3">
                            <ModelPropertyAttributes :property="value" />
                        </div>
                    </template>

                    <template v-else-if="Array.isArray(value)">
                        <ul class="ml-4 list-disc space-y-1">
                            <li v-for="(item, index) in value" :key="index">

                                <template v-if="isObject(item)">
                                    <div class="border border-gray-200 rounded p-2 bg-white">
                                        <ModelPropertyAttributes :property="item" />
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
                            <img :src="value" class="max-w-[150px] border rounded-lg shadow-sm" />
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
</template>
