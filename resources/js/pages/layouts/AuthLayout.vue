<script setup>
import OffcanvasMenu from '@/components/common/layout/auth-layout/OffcanvasMenu.vue'
import Breadcrumbs from '@/components/common/layout/auth-layout/Breadcrumbs.vue'
import AuthTopbarDropdownMenu from '@/components/common/layout/auth-layout/AuthTopbarDropdownMenu.vue'
import ToasterMessage from '@/components/common/layout/ToasterMessage.vue'

import { usePage } from '@inertiajs/vue3'
import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { ref, reactive, computed, provide } from 'vue'

import {
    faSpinner
} from '@fortawesome/free-solid-svg-icons'

library.add(
    faSpinner
)

const pageReady = ref(false)

const componentReady = reactive({
    offcanvasTrigger: false,
    authTopbarDropdownMenu: false,
    offcanvasSidebar: false,
    breadcrumbs: false
})

const appName = import.meta.env.VITE_APP_NAME
const appLogo = import.meta.env.VITE_APP_LOGO

const page = usePage()

const authUser = computed(() => page.props.auth?.user ?? null)
const flashMessage = computed(() => page.props.flashMessage)

provide('pageReady', pageReady)
provide('authUser', authUser)

const checkPageReady = () => {
    pageReady.value = Object.values(componentReady).every(Boolean)
}

const markComponentReady = (componentName) => {
    componentReady[componentName] = true
    checkPageReady()
}
</script>

<template>
    <div class="auth-layout flex flex-col min-h-screen">
        <header class="fixed top-0 left-0 w-full bg-white shadow-sm z-50">
            <div class="px-4 py-2 flex items-center justify-between">
                <a :href="route('home')" class="flex items-center gap-2">
                    <img :src="appLogo" :alt="appName" class="h-8" />
                    <span class="font-semibold">{{ appName }}</span>
                </a>

                <div class="flex items-center gap-3">
                    <OffcanvasMenu mode="trigger" :auth-user="authUser"
                        @ready="markComponentReady('offcanvasTrigger')" />

                    <AuthTopbarDropdownMenu :auth-user="authUser"
                        @ready="markComponentReady('authTopbarDropdownMenu')" />
                </div>
            </div>
        </header>

        <main class="flex-1 flex pt-16">
            <OffcanvasMenu mode="sidebar" :auth-user="authUser" @ready="markComponentReady('offcanvasSidebar')" />

            <div class="flex-1 p-4 min-w-0">
                <div v-if="!pageReady" class="fixed inset-0 bg-white/90 flex items-center justify-center z-50">
                    <FontAwesomeIcon icon="spinner" spin class="text-2xl text-blue-500" />
                </div>

                <Breadcrumbs @ready="markComponentReady('breadcrumbs')" />

                <div v-if="authUser && !authUser.email_verified_at"
                    class="mb-4 p-3 bg-yellow-100 border border-yellow-300 text-yellow-800 rounded">
                    Verify your email to continue.
                </div>

                <slot />
            </div>
        </main>

        <footer class="bg-white border-t border-gray-200 py-3 text-gray-500 text-sm">
            <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-2">
                <span class="text-center md:text-left w-full md:w-auto">
                    © {{ new Date().getFullYear() }} {{ appName }}
                </span>

                <span class="text-center md:text-right w-full md:w-auto">
                    Developed by
                    <a href="https://www.linkedin.com/in/sk-md-tahmid-farzan/" target="_blank" rel="noopener noreferrer"
                        class="text-blue-600 hover:underline font-medium">
                        Seikh Md Tahmid Farzan
                    </a>
                </span>
            </div>
        </footer>

        <ToasterMessage :flash-message="flashMessage" />
    </div>
</template>
