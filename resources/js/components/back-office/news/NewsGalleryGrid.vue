<script setup>
import NewsGalleryImageCard from './NewsGalleryImageCard.vue'

import { ref, computed, onBeforeUnmount } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { library as FontAwesomeLibrary } from '@fortawesome/fontawesome-svg-core'
import { faPlus, faSpinner, faXmark } from '@fortawesome/free-solid-svg-icons'

FontAwesomeLibrary.add(faPlus, faSpinner, faXmark)

const { news } = defineProps({
    news: {
        type: Object,
        required: true,
    },
})

const showCreateModal = ref(false)
const imagePreviewUrl = ref(null)
const imageInputRef = ref(null)

const galleryImages = computed(() => news?.gallery_images || [])

const saveForm = useForm({
    image: null,
    caption: '',
    alt: '',
})

function openModal() {
    showCreateModal.value = true
}

function clearImagePreview() {
    if (imagePreviewUrl.value) {
        URL.revokeObjectURL(imagePreviewUrl.value)
        imagePreviewUrl.value = null
    }
}

function resetForm() {
    saveForm.reset()
    saveForm.clearErrors()
    clearImagePreview()

    if (imageInputRef.value) {
        imageInputRef.value.value = ''
    }
}

function closeModal(force = false) {
    if (saveForm.processing && !force) return

    showCreateModal.value = false
    resetForm()
}

function handleImageChange(event) {
    const file = event.target.files?.[0] || null

    saveForm.image = file
    saveForm.clearErrors('image')

    clearImagePreview()

    if (file) {
        imagePreviewUrl.value = URL.createObjectURL(file)
    }
}

function validateForm() {
    saveForm.clearErrors()

    let valid = true

    if (!saveForm.image) {
        saveForm.setError('image', 'Image is required.')
        valid = false
    }

    return valid
}

function refreshGalleryImages() {
    router.reload({
        only: ['news'],
        preserveScroll: true,
        preserveState: true,
    })
}

function handleSave() {
    if (saveForm.processing) return

    if (!validateForm()) return

    saveForm.post(
        route('back-office.newses.gallery-images.save', {
            slug: news?.slug,
        }),
        {
            preserveScroll: true,
            preserveState: true,
            forceFormData: true,

            onSuccess: () => {
                closeModal(true)
                refreshGalleryImages()
            },

            onError: (errors) => {
                saveForm.clearErrors()
                saveForm.setError(errors)
            },
        }
    )
}

onBeforeUnmount(() => {
    clearImagePreview()
})
</script>

<template>
    <div>
        <div class="mb-3 flex items-center justify-between gap-3">
            <div class="text-gray-500">
                Gallery Images
            </div>

            <button type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                @click="openModal">
                <FontAwesomeIcon icon="plus" />
                Add Image
            </button>
        </div>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
            <NewsGalleryImageCard v-for="galleryImage in galleryImages" :key="galleryImage.id" :news="news"
                :gallery-image="galleryImage" @refresh-gallery-images="refreshGalleryImages" />
        </div>

        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
                @click.self="closeModal">
                <Transition appear enter-active-class="transition duration-200 ease-out"
                    enter-from-class="scale-95 opacity-0" enter-to-class="scale-100 opacity-100"
                    leave-active-class="transition duration-150 ease-in" leave-from-class="scale-100 opacity-100"
                    leave-to-class="scale-95 opacity-0">
                    <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-800">
                                Add Gallery Image
                            </h2>

                            <button type="button"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                                @click="closeModal">
                                <FontAwesomeIcon icon="xmark" />
                            </button>
                        </div>

                        <form @submit.prevent="handleSave">
                            <div class="space-y-4">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">
                                        Image <span class="text-red-500">*</span>
                                    </label>

                                    <input ref="imageInputRef" type="file" accept="image/*"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:font-medium hover:file:bg-gray-200"
                                        @change="handleImageChange">

                                    <div v-if="saveForm.errors.image" class="mt-1 text-sm text-red-600">
                                        {{ saveForm.errors.image }}
                                    </div>

                                    <div v-if="imagePreviewUrl" class="mt-3">
                                        <div class="mb-1 text-sm text-gray-500">
                                            Preview
                                        </div>

                                        <img :src="imagePreviewUrl" alt="Selected image preview"
                                            class="h-28 w-28 rounded-lg border object-cover">
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">
                                        Caption
                                    </label>

                                    <input v-model="saveForm.caption" type="text"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        placeholder="Enter caption">

                                    <div v-if="saveForm.errors.caption" class="mt-1 text-sm text-red-600">
                                        {{ saveForm.errors.caption }}
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">
                                        Alt Text
                                    </label>

                                    <input v-model="saveForm.alt" type="text"
                                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                        placeholder="Enter alt text">

                                    <div v-if="saveForm.errors.alt" class="mt-1 text-sm text-red-600">
                                        {{ saveForm.errors.alt }}
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end gap-3">
                                <button type="button"
                                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="saveForm.processing" @click="closeModal">
                                    Cancel
                                </button>

                                <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="saveForm.processing">
                                    <FontAwesomeIcon v-if="saveForm.processing" icon="spinner" class="animate-spin" />

                                    <span>
                                        {{ saveForm.processing ? 'Saving...' : 'Save' }}
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>
    </div>
</template>
