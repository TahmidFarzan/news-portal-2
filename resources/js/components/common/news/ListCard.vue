<script setup>
import { computed } from 'vue'

const {
    news,
    enableTitleLineClamp = true,
    enableSubTitleLineClamp = true,
    hideFeatureImage = false,
} = defineProps({
    news: {
        type: Object,
        required: true,
    },

    enableTitleLineClamp: {
        type: Boolean,
        default: true,
    },

    enableSubTitleLineClamp: {
        type: Boolean,
        default: true,
    },

    hideFeatureImage: {
        type: Boolean,
        default: false,
    },

    hideCategory: {
        type: Boolean,
        default: false,
    },
    hideSubtitle: {
        type: Boolean,
        default: false,
    },
})

const imageSrc = computed(() => {
    return (
        news?.feature_image_mobile?.media_url ||
        news?.feature_image_mobile?.original_url ||
        news?.feature_image?.media_url ||
        news?.feature_image?.original_url ||
        ''
    )
})

const imageAlt = computed(() => {
    return (
        news?.feature_image_mobile?.custom_properties?.alt ||
        news?.feature_image?.custom_properties?.alt ||
        news?.title ||
        'News image'
    )
})

const cardTag = computed(() => {
    return news?.public_url ? 'a' : 'article'
})

const shouldShowFeatureImage = computed(() => {
    return !hideFeatureImage && imageSrc.value
})
</script>

<template>
    <component :is="cardTag" :href="news?.public_url || undefined"
        class="group flex h-full gap-3 rounded-2xl border border-gray-200 bg-white p-3 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md">
        <div v-if="shouldShowFeatureImage"
            class="h-24 w-32 shrink-0 overflow-hidden rounded-xl bg-gray-100 sm:h-28 sm:w-40">
            <img :src="imageSrc" :alt="imageAlt"
                class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy" />
        </div>

        <div class="min-w-0 flex flex-1 flex-col justify-center space-y-2">
            <p v-if="news?.category?.name && !hideCategory" class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                {{ news?.category?.name }}
            </p>

            <h3 class="break-words text-base font-bold leading-snug text-gray-950 group-hover:text-red-600"
                :class="{ 'line-clamp-2': enableTitleLineClamp }">
                <b v-if="news?.content_shoulder" class="mr-1 text-sm font-semibold text-red-600">
                    {{ news?.content_shoulder }}
                </b>

                {{ news?.title }}
            </h3>

            <p v-if="news?.sub_title && !hideSubtitle" class="break-words text-sm leading-6 text-gray-600"
                :class="{ 'line-clamp-2': enableSubTitleLineClamp }">
                {{ news?.sub_title }}
            </p>
        </div>
    </component>
</template>
