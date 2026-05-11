<script setup>
import { ref } from 'vue'
import { router as inertiaJsRoute, useForm } from '@inertiajs/vue3'
import MediaRenderer from '@/components/common/media/MediaRenderer.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faTrash, faPen, faEye, faSpinner } from '@fortawesome/free-solid-svg-icons'

const { news, galleryImage } = defineProps({
    news: {
        type: Object,
        required: true,
    },
    galleryImage: {
        type: Object,
        required: true,
    },
})

const selectedGalleryImage = ref(null)

const showUpdateModal = ref(false)
const showDeleteModal = ref(false)
const deleteProcessing = ref(false)

const updateForm = useForm({
    caption: galleryImage?.custom_properties?.caption || '',
    alt: galleryImage?.custom_properties?.alt || '',
    order_column: galleryImage?.order_column || '',
})

const openUpdateModal = () => {
    updateForm.caption = galleryImage?.custom_properties?.caption || ''
    updateForm.alt = galleryImage?.custom_properties?.alt || ''
    updateForm.order_column = galleryImage?.order_column || ''
    updateForm.clearErrors()

    showUpdateModal.value = true
}

const closeUpdateModal = () => {
    if (updateForm.processing) return

    showUpdateModal.value = false
}

const updateGalleryImage = () => {
    updateForm.patch(
        route('back-office.newses.gallery-images.update', {
            slug: news?.slug,
            mediaSlug: galleryImage?.slug,
        }),
        {
            onSuccess: () => {
                showUpdateModal.value = false
            },
        }
    )
}

const openDeleteModal = () => {
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    if (deleteProcessing.value) return

    showDeleteModal.value = false
}

const deleteGalleryImage = () => {
    deleteProcessing.value = true

    inertiaJsRoute.patch(
        route('back-office.newses.gallery-images.delete', {
            slug: news?.slug,
            mediaSlug: galleryImage?.slug,
        }),
        {},
        {
            onFinish: () => {
                deleteProcessing.value = false
                showDeleteModal.value = false
            },
        }
    )
}
</script>

<template>
    <div class="relative border border-gray-100 rounded-lg overflow-hidden">
        <button type="button" class="block w-full cursor-pointer" @click="selectedGalleryImage = galleryImage">
            <MediaRenderer v-if="galleryImage" :media="galleryImage" :mediaClass="'w-full h-40 object-cover'" />
        </button>

        <div class="absolute top-2 right-2 flex items-center gap-2">
            <button type="button"
                class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-gray-700 shadow hover:bg-gray-100"
                @click.stop="selectedGalleryImage = galleryImage">
                <FontAwesomeIcon :icon="faEye" />
            </button>

            <button type="button"
                class="flex h-8 w-8 items-center justify-center rounded-full bg-yellow-500 text-white shadow hover:bg-yellow-600 disabled:opacity-60"
                :disabled="updateForm.processing" @click.stop="openUpdateModal">
                <FontAwesomeIcon :icon="faPen" />
            </button>

            <button type="button"
                class="flex h-8 w-8 items-center justify-center rounded-full bg-red-600 text-white shadow hover:bg-red-700 disabled:opacity-60"
                :disabled="deleteProcessing" @click.stop="openDeleteModal">
                <FontAwesomeIcon :icon="faTrash" />
            </button>
        </div>
    </div>

    <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100" leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
        <div v-if="selectedGalleryImage" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
            @click.self="selectedGalleryImage = null">
            <div class="relative max-w-5xl w-full">
                <button type="button" class="absolute -top-10 right-0 text-white text-3xl"
                    @click="selectedGalleryImage = null">
                    &times;
                </button>

                <img :src="selectedGalleryImage.original_url || selectedGalleryImage.media_url"
                    :alt="selectedGalleryImage.custom_properties?.alt || selectedGalleryImage.name"
                    class="w-full max-h-[85vh] object-contain rounded-lg" />

                <div v-if="selectedGalleryImage.custom_properties?.caption" class="mt-3 text-center text-white text-sm">
                    {{ selectedGalleryImage.custom_properties.caption }}
                </div>
            </div>
        </div>
    </Transition>

    <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100" leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
        <div v-if="showUpdateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
            @click.self="closeUpdateModal">
            <div class="w-full max-w-md rounded-lg bg-white p-5 shadow-lg">
                <h3 class="text-lg font-semibold text-gray-800">
                    Update Gallery Image
                </h3>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Caption
                        </label>

                        <input v-model="updateForm.caption" type="text"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" />

                        <p v-if="updateForm.errors.caption" class="mt-1 text-xs text-red-600">
                            {{ updateForm.errors.caption }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Alt
                        </label>

                        <input v-model="updateForm.alt" type="text"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" />

                        <p v-if="updateForm.errors.alt" class="mt-1 text-xs text-red-600">
                            {{ updateForm.errors.alt }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Order Column
                        </label>

                        <input v-model="updateForm.order_column" type="number" min="1"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" />

                        <p v-if="updateForm.errors.order_column" class="mt-1 text-xs text-red-600">
                            {{ updateForm.errors.order_column }}
                        </p>
                    </div>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button type="button"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-60"
                        :disabled="updateForm.processing" @click="closeUpdateModal">
                        Cancel
                    </button>

                    <button type="button"
                        class="rounded-md bg-yellow-500 px-4 py-2 text-sm text-white hover:bg-yellow-600 disabled:opacity-60"
                        :disabled="updateForm.processing" @click="updateGalleryImage">
                        <FontAwesomeIcon v-if="updateForm.processing" :icon="faSpinner" class="mr-1 animate-spin" />
                        Update
                    </button>
                </div>
            </div>
        </div>
    </Transition>

    <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100" leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4"
            @click.self="closeDeleteModal">
            <div class="w-full max-w-md rounded-lg bg-white p-5 shadow-lg">
                <h3 class="text-lg font-semibold text-gray-800">
                    Delete Gallery Image?
                </h3>

                <p class="mt-2 text-sm text-gray-600">
                    Are you sure you want to delete this gallery image?
                </p>

                <div class="mt-5 flex justify-end gap-2">
                    <button type="button"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-60"
                        :disabled="deleteProcessing" @click="closeDeleteModal">
                        Cancel
                    </button>

                    <button type="button"
                        class="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700 disabled:opacity-60"
                        :disabled="deleteProcessing" @click="deleteGalleryImage">
                        <FontAwesomeIcon v-if="deleteProcessing" :icon="faSpinner" class="mr-1 animate-spin" />
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
