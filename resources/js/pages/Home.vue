<script setup>
import Layout from '@/pages/layouts/PublicLayout.vue'
import { Head } from '@inertiajs/vue3'

defineOptions({ layout: Layout })
</script>

<template>

    <Head title="Home" />

    <div class="bg-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="bg-white shadow rounded-2xl p-6">
                <h1 class="text-2xl font-semibold text-gray-800 mb-4">
                    Home
                </h1>
                <p class="text-gray-600">
                    Welcome to your TailwindCSS page.
                </p>
            </div>
        </div>
    </div>
</template>
