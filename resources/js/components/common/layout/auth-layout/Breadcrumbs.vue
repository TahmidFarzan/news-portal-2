<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'

const breadcrumbItems = ref([])

const setBreadcrumb = (event) => {
    breadcrumbItems.value = Array.isArray(event.detail) ? event.detail : []
}

onMounted(() => {
    window.addEventListener('set-breadcrumb', setBreadcrumb)
})

onBeforeUnmount(() => {
    window.removeEventListener('set-breadcrumb', setBreadcrumb)
})
</script>

<template>
    <div v-if="breadcrumbItems.length" class="mb-4 border border-gray-200 rounded-lg p-3 bg-white shadow-sm">
        <div class="text-sm text-gray-600 flex flex-wrap gap-2">
            <template v-for="(item, index) in breadcrumbItems" :key="index">
                <a v-if="!item.active" :href="item.href" class="hover:underline">
                    {{ item.text }}
                </a>

                <span v-else class="font-medium">
                    {{ item.text }}
                </span>

                <span v-if="index < breadcrumbItems.length - 1">/</span>
            </template>
        </div>
    </div>
</template>
