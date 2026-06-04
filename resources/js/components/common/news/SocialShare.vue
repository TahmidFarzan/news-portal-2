<script setup>
import { computed, ref } from 'vue'
import { ShareNetwork } from 'vue3-social-sharing'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'

import {
    faLink,
    faCheck,
} from '@fortawesome/free-solid-svg-icons'

import {
    faFacebookF,
    faXTwitter,
    faLinkedinIn,
    faWhatsapp,
    faTelegram,
} from '@fortawesome/free-brands-svg-icons'

FontAwesomeLibrary.add(
    faLink,
    faCheck,
    faFacebookF,
    faXTwitter,
    faLinkedinIn,
    faWhatsapp,
    faTelegram,
)

const { news} = defineProps({
    news: {
        type: Object,
        required: true,
    },
})

const isCopied = ref(false)

const shareUrl = computed(() => {
    if (news?.public_url) {
        return news.public_url
    }

    if (typeof window !== 'undefined') {
        return window.location.href
    }

    return ''
})

const shareTitle = computed(() => {
    return news?.seo_title || news?.title || 'News'
})

const shareDescription = computed(() => {
    return news?.seo_brief || news?.brief || news?.sub_title || ''
})

const shareMedia = computed(() => {
    return (
        news?.feature_image?.media_url ||
        news?.feature_image?.original_url ||
        news?.feature_image_mobile?.media_url ||
        news?.feature_image_mobile?.original_url ||
        ''
    )
})

const networks = computed(() => {
    return [
        {
            key: 'facebook',
            name: 'Facebook',
            icon: faFacebookF,
        },
        {
            key: 'x',
            name: 'X',
            icon: faXTwitter,
        },
        {
            key: 'linkedin',
            name: 'LinkedIn',
            icon: faLinkedinIn,
        },
        {
            key: 'whatsapp',
            name: 'WhatsApp',
            icon: faWhatsapp,
        },
        {
            key: 'telegram',
            name: 'Telegram',
            icon: faTelegram,
        },
    ]
})

const copyShareLink = async () => {
    if (!shareUrl.value || typeof navigator === 'undefined') {
        return
    }

    await navigator.clipboard.writeText(shareUrl.value)

    isCopied.value = true

    setTimeout(() => {
        isCopied.value = false
    }, 1500)
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
            <span class="text-gray-400">
                Share:
            </span>

            <ShareNetwork v-for="network in networks" :key="network.key" :network="network.key" :url="shareUrl"
                :title="shareTitle" :description="shareDescription" :media="shareMedia" v-slot="{ share }">
                <button type="button" :aria-label="`Share on ${network.name}`"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 text-gray-500 transition hover:border-gray-900 hover:bg-gray-900 hover:text-white"
                    @click="share">
                    <FontAwesomeIcon :icon="network.icon" class="text-sm" />
                </button>
            </ShareNetwork>

            <button type="button"
                class="inline-flex h-8 items-center gap-1.5 rounded-full border border-gray-200 px-3 text-gray-500 transition hover:border-gray-900 hover:bg-gray-900 hover:text-white"
                @click="copyShareLink">
                <FontAwesomeIcon :icon="isCopied ? faCheck : faLink" class="text-xs" />

                <span>
                    {{ isCopied ? 'Copied' : 'Copy' }}
                </span>
            </button>
        </div>
</template>
