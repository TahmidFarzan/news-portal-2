<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'

import { onMounted, nextTick } from 'vue'
import { Head } from '@inertiajs/vue3'
import { useTranslate } from '@/composables/useTranslate'

defineOptions({ layout: Layout })

const { t } = useTranslate()

onMounted(async () => {
    await nextTick()

    window.dispatchEvent(new CustomEvent('set-breadcrumb', {
        detail: [{ text: t('common.labels.dashboard'), active: true }]
    }))
})
</script>

<template>
    <Head :title="t('common.labels.dashboard')" />

    <div class="grid grid-cols-1 gap-4">

        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
            {{ t('common.labels.dashboard') }}
        </div>

    </div>
</template>
