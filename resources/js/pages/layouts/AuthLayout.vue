<script setup>
import OffcanvasMenu from '@/components/common/layout/auth-layout/OffcanvasMenu.vue'
import Breadcrumbs from '@/components/common/layout/auth-layout/Breadcrumbs.vue'
import AuthTopbarDropdownMenu from '@/components/common/layout/auth-layout/AuthTopbarDropdownMenu.vue'
import ToasterMessage from '@/components/common/layout/ToasterMessage.vue'

import { usePage } from '@inertiajs/vue3'
import { computed, provide } from 'vue'
import { useTranslate } from '@/composables/useTranslate'

const { t } = useTranslate()

const appName = import.meta.env.VITE_APP_NAME
const appLogo = import.meta.env.VITE_APP_LOGO

const page = usePage()

const authUser = computed(() => page.props.auth?.user ?? null)
const flashMessage = computed(() => page.props.flashMessage ?? null)

provide('authUser', authUser)
</script>

<template>
    <div class="auth-layout flex flex-col min-h-screen">

        <header class="fixed top-0 left-0 w-full bg-white shadow-sm z-50">
            <div class="px-4 py-2 flex items-center justify-between">

                <a :href="route('home')" class="flex items-center gap-2 min-w-0">
                    <img v-if="appLogo" :src="appLogo" :alt="appName" class="h-8 flex-shrink-0">

                    <span class="font-semibold truncate">
                        {{ appName }}
                    </span>
                </a>

                <div class="flex items-center gap-3">

                    <!-- OFFCANVAS (trigger) -->
                    <OffcanvasMenu mode="trigger" :auth-user="authUser" />

                    <!-- USER MENU -->
                    <AuthTopbarDropdownMenu :auth-user="authUser" />

                </div>
            </div>
        </header>

        <main class="main flex-1 flex pt-16">


            <OffcanvasMenu mode="sidebar" :auth-user="authUser" />

            <div class="flex-1 p-4 min-w-0">

                <Breadcrumbs />

                <div v-if="authUser && !authUser.email_verified_at"
                    class="mb-4 p-3 bg-yellow-100 border border-yellow-300 text-yellow-800 rounded">
                    {{ t('pages.layouts.auth_layout.auth.account.not_verified') }}
                </div>

                <slot />

            </div>
        </main>

        <footer class="bg-white border-t border-gray-200 py-3 text-gray-500 text-sm">

            <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-2">

                <span class="text-center md:text-left w-full md:w-auto">
                    {{ t('pages.layouts.auth_layout.footer.copyright') }}
                    {{ new Date().getFullYear() }}
                    {{ appName }}
                </span>

                <span class="text-center md:text-right w-full md:w-auto">
                    {{ t('pages.layouts.auth_layout.footer.developed_by') }}
                    <a href="https://www.linkedin.com/in/sk-md-tahmid-farzan/" target="_blank" rel="noopener noreferrer"
                        class="text-blue-600 hover:underline font-medium">
                        {{ t('pages.layouts.auth_layout.footer.developer_name') }}
                    </a>
                </span>

            </div>
        </footer>

        <ToasterMessage :flash-message="flashMessage" />

    </div>
</template>
