<template>
    <div>
        <BButton v-if="!disableOpenMediaButton" variant="light" size="sm" class="border border-dark mb-2"
            @click="openModal" :hidden="hideDefaultOpenButton">
            Open Media Library
        </BButton>

        <BModal v-model="showModal" :title="`${galleryTitle} gallery`" size="xl" scrollable hide-header-close
            no-close-on-backdrop teleport="body" @hide="resetSelection">
            <BRow>
                <BCol md="6" class="mb-2">
                    <BFormInput v-model="search" size="sm" placeholder="Search..." @input="handleSearch" />
                </BCol>

                <BCol cols="12">
                    <BRow class="g-2">
                        <BCol v-if="!loading && mediaList.length === 0" cols="12" class="text-center text-muted">
                            No media found.
                        </BCol>

                        <BCol v-for="media in mediaList" :key="media.id" cols="12" md="4" lg="3">
                            <BCard :class="[
                                'h-100',
                                'media-item',
                                { 'border-primary': isSelected(media.id), 'border-2': isSelected(media.id) }
                            ]" @click="toggleMedia(media)">
                                <MediaRenderer :media="media" />
                            </BCard>
                        </BCol>
                    </BRow>
                </BCol>

                <BCol cols="12" class="mt-3 text-center" v-if="lastPage !== null && page < lastPage">
                    <BButton variant="secondary" size="sm" @click="loadMore" :disabled="loading">
                        <BSpinner small class="me-2" v-if="loading" /> {{ loading ? 'Loading...' : 'Load more' }}
                    </BButton>
                </BCol>
            </BRow>

            <template #footer>
                <BButton variant="secondary" size="sm" @click="showModal = false">Close</BButton>
                <BButton variant="success" size="sm" @click="confirmSelection" :disabled="!hasSelection">Select
                </BButton>
            </template>
        </BModal>
    </div>
</template>

<script setup>
import MediaRenderer from './MediaRenderer.vue'
import { ref, watch, computed } from 'vue'
import axios from 'axios'
import { BModal, BButton, BFormInput, BRow, BCol, BCard, BSpinner } from 'bootstrap-vue-next'

const {
    disableOpenMediaButton = false,
    inputPrefix,
    mediaType = 'image',
    galleryTitle = 'Media',
    fetchUrl,
    multiple = false,
    hideDefaultOpenButton = false
} = defineProps({
    disableOpenMediaButton: { type: Boolean, default: false },
    inputPrefix: { type: String, required: true },
    mediaType: { type: String, default: 'image' },
    galleryTitle: { type: String, default: 'Media' },
    fetchUrl: { type: String, required: true },
    multiple: { type: Boolean, default: false },
    hideDefaultOpenButton: { type: Boolean, default: false }
})

const emit = defineEmits(['media-selected'])

const showModal = ref(false)
const mediaList = ref([])
const selectedMediaList = ref([])
const search = ref('')
const page = ref(1)
const lastPage = ref(null)
const perPage = 10
const loading = ref(false)
const loadedPages = new Set()

const hasSelection = computed(() => selectedMediaList.value.length > 0)

const openModal = () => (showModal.value = true)

const resetSelection = () => (selectedMediaList.value = [])

const handleSearch = () => {
    page.value = 1
    loadedPages.clear()
    loadMedia(true)
}

const loadMore = () => {
    if (page.value < lastPage.value) {
        page.value++
        loadMedia()
    }
}

const isSelected = id => selectedMediaList.value.some(m => m.id === id)

const toggleMedia = media => {
    if (multiple) {
        const index = selectedMediaList.value.findIndex(m => m.id === media.id)
        if (index >= 0) selectedMediaList.value.splice(index, 1)
        else selectedMediaList.value.push(media)
    } else {
        selectedMediaList.value = [media]
    }
}

const confirmSelection = () => {
    emit('media-selected', multiple ? selectedMediaList.value : selectedMediaList.value[0])
    showModal.value = false
}

const loadMedia = async (clear = false) => {
    if (loading.value || loadedPages.has(page.value)) return
    loading.value = true

    try {
        const { data } = await axios.get(fetchUrl, {
            params: { page: page.value, per_page: perPage, search: search.value, media_type: mediaType }
        })
        if (clear) mediaList.value = []
        mediaList.value.push(...data.items)
        loadedPages.add(page.value)
        lastPage.value = data.last_page
    } catch (err) {
        console.error('Media load failed:', err)
    } finally {
        loading.value = false
    }
}

watch(showModal, value => {
    if (value) {
        page.value = 1
        loadedPages.clear()
        loadMedia(true)
    }
})

defineExpose({ openModal })
</script>

<style scoped>
.media-item {
    cursor: pointer;
    transition: border-color 0.3s, transform 0.2s;
}

.media-item:hover {
    transform: scale(1.02);
}

.media-item.border-primary {
    border: 2px solid #0d6efd !important;
}
</style>
