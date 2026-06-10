<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, watch } from 'vue'

import RelevantNewsList from '@/Components/common/news/RelevantNewsList.vue'

import { Fancybox } from '@fancyapps/ui/dist/fancybox/'
import '@fancyapps/ui/dist/fancybox/fancybox.css'

const {
    news,
} = defineProps({
    news: {
        type: Object,
        required: true,
    },
})

const fancyboxSelector = '[data-fancybox="news-body-gallery"]'


const bodyImageCount = computed(() => {
    const body = news?.body || ''

    return body.match(/<img\b[^>]*>/gi)?.length || 0
})

const bodyWithLightboxImages = computed(() => {
    const body = news?.body || ''

    if (!body) {
        return ''
    }

    if (typeof DOMParser === 'undefined') {
        return body
    }

    const parser = new DOMParser()
    const documentBody = parser.parseFromString(body, 'text/html').body
    const images = Array.from(documentBody.querySelectorAll('img'))

    images.forEach((image, index) => {
        const src = image.getAttribute('src')

        if (!src) {
            return
        }

        const alt = image.getAttribute('alt') || `Image ${index + 1}`
        const existingLink = image.closest('a')

        image.classList.add('cursor-zoom-in')

        if (existingLink) {
            existingLink.setAttribute('href', src)
            existingLink.setAttribute('data-src', src)
            existingLink.setAttribute('data-fancybox', 'news-body-gallery')
            existingLink.setAttribute('data-caption', alt)
            existingLink.classList.add('cursor-zoom-in')

            return
        }

        const lightboxLink = documentBody.ownerDocument.createElement('a')

        lightboxLink.setAttribute('href', src)
        lightboxLink.setAttribute('data-src', src)
        lightboxLink.setAttribute('data-fancybox', 'news-body-gallery')
        lightboxLink.setAttribute('data-caption', alt)
        lightboxLink.classList.add('block', 'cursor-zoom-in')

        image.parentNode.insertBefore(lightboxLink, image)
        lightboxLink.appendChild(image)
    })

    return documentBody.innerHTML
})

const splitNewsBody = computed(() => {
    const body = bodyWithLightboxImages.value

    if (!body) {
        return {
            beforeHtml: '',
            afterHtml: '',
        }
    }

    const paragraphRegex = /<p\b[^>]*>[\s\S]*?<\/p>/gi
    const paragraphs = body.match(paragraphRegex) || []

    if (!paragraphs.length) {
        return {
            beforeHtml: body,
            afterHtml: '',
        }
    }

    const middleParagraphIndex = Math.ceil(paragraphs.length / 2)

    let paragraphCount = 0
    let splitIndex = body.length

    body.replace(paragraphRegex, (match, offset) => {
        paragraphCount++

        if (paragraphCount === middleParagraphIndex) {
            splitIndex = offset + match.length
        }

        return match
    })

    return {
        beforeHtml: body.slice(0, splitIndex),
        afterHtml: body.slice(splitIndex),
    }
})

const bindFancybox = async () => {
    await nextTick()

    Fancybox.unbind(fancyboxSelector)

    Fancybox.bind(fancyboxSelector, {
        animated: true,
        closeButton: true,
        dragToClose: true,

        Toolbar: {
            display: {
                left: [],
                middle: [],
                right: ['iterateZoom', 'slideshow', 'fullscreen', 'close'],
            },
        },
    })
}

onMounted(() => {
    bindFancybox()
})

watch(
    () => bodyWithLightboxImages.value,
    () => {
        bindFancybox()
    },
)

onBeforeUnmount(() => {
    Fancybox.unbind(fancyboxSelector)
    Fancybox.close()
})
</script>

<template>
    <div v-if="news?.body" class="space-y-8">

        <section
            v-if="splitNewsBody.beforeHtml"
            class="prose prose-lg max-w-none"
            v-html="splitNewsBody.beforeHtml"
        />

        <RelevantNewsList
            v-if="news.relevant_news.length > 0"
            :news="news"
        />

        <section
            v-if="splitNewsBody.afterHtml"
            class="prose prose-lg max-w-none"
            v-html="splitNewsBody.afterHtml"
        />
    </div>
</template>
