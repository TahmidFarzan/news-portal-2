<template>
    <div class="w-full">
        <Editor v-if="form" v-model="form[inputField]" :init="editorInit" :license-key="tinymceLicenceKey"
            class="border border-gray-300 rounded-md overflow-hidden" />

        <input v-if="form" v-model="form['editor_media_ids']" type="hidden" class="hidden" />

        <SelectMediaFromMediaLibery ref="mediaLibrary" v-model:showModal="showMediaLibrary"
            :fetch-url="route('search.medias')" :media-type="'All'" :multiple="true"
            :cache-key="apiCacheKey.API_TINYMCE" :cache-ttl="apiCacheTTL.API_TINYMCE"
            @media-selected="handleMediaSelected" :hide-default-open-button="true" />
    </div>
</template>

<script setup>
import { computed, inject, ref } from 'vue'
import Editor from '@tinymce/tinymce-vue'
import tinymce from 'tinymce/tinymce'
import SelectMediaFromMediaLibery from '@/components/common/media/MediaLibrarySelector.vue'
import axios from 'axios'
import { apiCacheKey, apiCacheTTL } from '@/composables/useApiCache'
import { useTranslate } from '@/composables/useTranslate'

import 'tinymce/tinymce'
import 'tinymce/models/dom'
import 'tinymce/themes/silver'
import 'tinymce/icons/default'
import 'tinymce/plugins/lists'
import 'tinymce/plugins/link'
import 'tinymce/plugins/table'
import 'tinymce/plugins/code'

import 'tinymce/plugins/wordcount'
import 'tinymce/plugins/preview'
import 'tinymce/plugins/fullscreen'
import 'tinymce/plugins/media'
import 'tinymce/plugins/charmap'

import 'tinymce/plugins/searchreplace'
import 'tinymce/plugins/autosave'
import 'tinymce/plugins/pagebreak'
import 'tinymce/plugins/importcss'
import 'tinymce/plugins/visualblocks'
import 'tinymce/plugins/visualchars'
import 'tinymce/plugins/codesample'
import 'tinymce/plugins/anchor'
import 'tinymce/plugins/advlist'


import 'tinymce/skins/ui/oxide/skin.css'
import 'tinymce/skins/content/default/content.css'

const {
    form,
    inputField,
    errorField = '',
    isSimple = true,
    textBoxHeight = 450,
    enableMediaUpload = false,
    enableSelectFormMediaLibery = false
} = defineProps({
    form: { type: Object, required: true },
    inputField: { type: String, required: true },
    errorField: { type: String, default: '' },
    isSimple: { type: Boolean, default: true },
    textBoxHeight: { type: Number, default: 450 },
    enableMediaUpload: { type: Boolean, default: false },
    enableSelectFormMediaLibery: { type: Boolean, default: false },
})



const tinymceLicenceKey = import.meta.env.VITE_TINY_MCE_TEXT_EDITOR_LICENSE_KEY || 'gpl'
const { t } = useTranslate()

const showMediaLibrary = ref(false)
const mediaLibrary = ref(null)

const editorInit = computed(() => {
    const config = {
        skin: false,
        content_css: false,
        height: textBoxHeight,
        toolbar_sticky: false,
        menubar: false,
        automatic_uploads: false,
        relative_urls: false,
        remove_script_host: false,
        convert_urls: false,
        media_live_embeds: true,
        valid_elements: '*[*]',
        extended_valid_elements: '*[*]',
        entity_encoding: 'raw',

        plugins:
            'lists link table code wordcount preview fullscreen media charmap searchreplace autosave pagebreak importcss visualblocks visualchars codesample anchor advlist',
        toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | table | code',
    }

    if (!isSimple) {
        config.menubar = 'file edit view insert format tools table'
        config.toolbar =
            (enableSelectFormMediaLibery ? 'openMediaLibraryButton | ' : '') +
            (enableMediaUpload
                ? 'insertImageButton insertFileButton insertVideoButton insertAudioButton | '
                : '') +
            'undo redo | bold italic underline strikethrough | fontfamily fontsize blocks | ' +
            'alignleft aligncenter alignright alignjustify | outdent indent | numlist bullist | ' +
            'forecolor backcolor removeformat | pagebreak | charmap | fullscreen preview save print | media link anchor codesample | ltr rtl'

        config.setup = (editor) => {
            if (enableSelectFormMediaLibery) {
                editor.ui.registry.addButton('openMediaLibraryButton', {
                    text: t('media.mediaSelectFromMediaLibery.actions.openMediaLibrary'),
                    onAction: () => {
                        showMediaLibrary.value = true
                        if (mediaLibrary.value) mediaLibrary.value.openModal()
                    },
                })
            }

            if (enableMediaUpload) {
                const uploadUrl = '/back-office/medias/quick-save'
                const addButton = (name, text, type, accept) => {
                    editor.ui.registry.addButton(name, {
                        text,
                        onAction: async () => {
                            const input = document.createElement('input')
                            input.type = 'file'
                            input.accept = accept
                            input.style.display = 'none'
                            document.body.appendChild(input)

                            input.onchange = async (e) => {
                                const file = e.target.files[0]
                                if (!file) return input.remove()

                                const caption = prompt(t('components.common.tinymce.editor.prompts.caption', { type }), '') || ''
                                const alt = prompt(t('components.common.tinymce.editor.prompts.altText', { type }), '') || ''

                                const formData = new FormData()
                                formData.append('caption', caption)
                                formData.append('alt', alt)
                                formData.append('media', file)

                                try {
                                    const response = await axios.post(uploadUrl, formData, {
                                        headers: { 'Content-Type': 'multipart/form-data' },
                                    })
                                    const media = response.data?.media || {}
                                    const mediaUrl = media?.preview_url || media?.original_url || ''
                                    const id = media?.id || ''
                                    let html = ''

                                    switch (type) {
                                        case 'image':
                                            html = `<a data-fancybox="content-images" data-src="${mediaUrl}" data-caption="${caption}" class="mb-2 mt-2"><img src="${mediaUrl}" class="img img-fluid object-fit-scale border border-gray-300 rounded d-block"></a>`
                                            break
                                        case 'video':
                                            html = `<div class="relative w-full pt-[56.25%] mb-2 mt-2"><video controls src="${mediaUrl}" class="absolute top-0 left-0 w-full h-full object-cover border border-gray-300 rounded"></video></div>`
                                            break
                                        case 'audio':
                                            html = `<div class="relative w-full pt-[56.25%] mb-2 mt-2"><audio controls src="${mediaUrl}" class="absolute top-0 left-0 w-full h-full object-cover border border-gray-300 rounded"></audio></div>`
                                            break
                                        case 'file':
                                            const embed = confirm(t('components.common.tinymce.editor.prompts.embedFile'))
                                            if (embed) {
                                                html = `<div class="w-full h-auto"><iframe src="${mediaUrl}" title="${caption}" width="100%" height="500"></iframe></div>`
                                            } else {
                                                const anchorText = prompt(t('components.common.tinymce.editor.prompts.linkText'), media?.name || t('components.common.tinymce.editor.labels.downloadFile'))
                                                const target = confirm(t('components.common.tinymce.editor.prompts.openInNewTab')) ? ' target="_blank"' : ''
                                                html = `<a href="${mediaUrl}"${target} class="inline-block px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">${anchorText}</a>`
                                            }
                                            break
                                    }

                                    editor.insertContent(html)
                                    if (id) updateEditorMediaIds(id)
                                } catch (err) {
                                    console.error(err)
                                    editor.notificationManager.open({
                                        text: t('components.common.tinymce.editor.messages.uploadFailed', { message: err.message }),
                                        type: 'error',
                                        timeout: 5000,
                                    })
                                } finally {
                                    input.remove()
                                }
                            }

                            input.click()
                        },
                    })
                }

                addButton('insertImageButton', t('components.common.tinymce.editor.actions.insertImage'), 'image', 'image/*')
                addButton('insertFileButton', t('components.common.tinymce.editor.actions.insertFile'), 'file', '.csv,.pdf,.doc,.docx,.txt,.xlsx,.xls')
                addButton('insertVideoButton', t('components.common.tinymce.editor.actions.insertVideo'), 'video', 'video/*')
                addButton('insertAudioButton', t('components.common.tinymce.editor.actions.insertAudio'), 'audio', 'audio/*')

                const updateEditorMediaIds = (id) => {
                    if (!form['editor_media_ids']) form['editor_media_ids'] = ''
                    if (form['editor_media_ids'].length > 0) form['editor_media_ids'] += ','
                    form['editor_media_ids'] += id
                }
            }
        }
    }

    return config
})

const handleMediaSelected = (selected) => {
    const editor = tinymce.activeEditor
    if (!editor) return

    const selectedArray = Array.isArray(selected) ? selected : [selected]

    selectedArray.forEach((media) => {
        const type = media.media_type || media.type || media.mime_type?.split('/')[0] || 'file'
        const id = media.id
        const url = media.preview_url || media.original_url
        const caption = media.caption || media.alt || ''
        let html = ''

        switch (type) {
            case 'image':
                html = `<a data-fancybox="content-images" data-src="${url}" data-caption="${caption}" class="block my-2"><img src="${url}" class="w-full h-auto object-cover border border-gray-300 rounded"></a>`
                break
            case 'video':
                html = `<div class="relative w-full pt-[56.25%] my-2"><video controls src="${url}" class="absolute top-0 left-0 w-full h-full object-cover border border-gray-300 rounded"></video></div>`
                break
            case 'audio':
                html = `<div class="relative w-full pt-[56.25%] my-2"><audio controls src="${url}" class="absolute top-0 left-0 w-full h-full object-cover border border-gray-300 rounded"></audio></div>`
                break
            default:
                html = `<div class="w-full h-auto my-2"><iframe src="${url}" title="${caption}" width="100%" height="500"></iframe></div>`
                break
        }

        editor.insertContent(html)
        if (!form['editor_media_ids']) form['editor_media_ids'] = ''
        if (form['editor_media_ids'].length > 0) form['editor_media_ids'] += ','
        form['editor_media_ids'] += id
    })
}
</script>
