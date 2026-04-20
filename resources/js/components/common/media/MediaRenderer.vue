<script setup>
const { media } = defineProps({
    media: { type: Object, required: true }
})
</script>

<template>
    <div>

        <figure v-if="media?.mime_type?.startsWith('image/')" class="flex flex-col items-center">
            <img :src="media?.media_url" :alt="media?.custom_properties?.alt" :srcset="media?.media_srcset" loading="lazy"
                class="w-1/4 max-w-full rounded-lg border border-gray-200 shadow-sm" />
            <figcaption class="mt-2 text-sm text-gray-500 text-center">
                {{ media?.custom_properties?.caption }}
            </figcaption>
        </figure>

        <iframe v-else-if="media?.mime_type.startsWith('video/')"
            :src="media?.file_type === 'Upload' ? media?.getUrl() : media?.url"
            class="w-full rounded-lg border border-gray-200 min-h-[320px]"></iframe>

        <audio v-else-if="media?.mime_type.startsWith('audio/')" controls class="w-full">
            <source :src="media?.media_url" />
            Browser failed to support audio.
        </audio>

        <iframe v-else-if="media?.mime_type === 'application/pdf'" :src="media?.media_url"
            class="w-full rounded-lg border border-gray-200 min-h-[600px]"></iframe>

        <iframe v-else-if="['application/json', 'text/plain', 'text/csv'].includes(media?.mime_type)"
            :src="media?.media_url" class="w-full rounded-lg border border-gray-200 min-h-[500px]"></iframe>

        <div v-else-if="['application/zip', 'application/x-rar-compressed'].includes(media?.mime_type)">
            <a :href="media?.media_url" download
                class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                Download
            </a>
        </div>

        <div v-else class="text-sm text-gray-500">
            Browser failed to display file type.
        </div>

    </div>
</template>
