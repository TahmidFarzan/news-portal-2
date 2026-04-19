<script setup>
import Layout from '@/pages/layouts/AuthLayout.vue'

import { onMounted, nextTick, inject } from 'vue'
import { Head } from '@inertiajs/vue3'

defineOptions({ layout: Layout })

const pageReady = inject("pageReady")

onMounted(async () => {
    await nextTick()
    window.dispatchEvent(
        new CustomEvent('set-breadcrumb', {
            detail: [
                { text: 'Dashboard', href: route('auth-user.dashboard.index') },
                { text: 'User manager', active: true },
            ],
        })
    )

    pageReady.value = true
})
</script>

<template>

    <Head title="User" />

    <div class="w-full">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 md:p-6">

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">

                <a :href="route('back-office.user-manager.users.index')"
                    class="flex flex-col items-center justify-center border border-gray-200 rounded-xl p-4 hover:shadow-md transition">
                    <img src="/public/uploads/icons/user/user.png" class="w-40 h-40 object-contain mb-3" />

                    <span class="text-sm text-gray-600">
                        Users
                    </span>
                </a>

            </div>

        </div>
    </div>
</template>

<style scoped></style>
