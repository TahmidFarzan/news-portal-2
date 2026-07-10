<script setup>
import { ref, reactive, watch, nextTick, onMounted } from 'vue'
import SelectInfinityLoadingApi from '@/components/common/multi-select/SelectInfinityLoadingApi.vue'
import { fetchFromApi, postToApi } from '@/composables/useSystemApi'
import { smartCacheKey, useApiSmartCache } from '@/composables/useApiSmartCache'
import { setSelectedLanguage, useTranslate } from '@/composables/useTranslate'

const language = ref(null)
const isReady = ref(false)

const { t } = useTranslate()
const { clearByPrefix } = useApiSmartCache()

const layoutCacheKeys = [
    smartCacheKey.API_LAYOUT_THEME,
    smartCacheKey.API_LAYOUT_TOPBAR_MENU,
    smartCacheKey.API_LAYOUT_HEADER_MENU,
    smartCacheKey.API_LAYOUT_OFFCANVAS_MENU,
    smartCacheKey.API_LAYOUT_FOOTER_MENU,
]

const languageChangeForm = reactive({
    language_id: null,
    errors: {
        language_id: null,
    },
})

const loadLanguage = async () => {
    const response = await fetchFromApi(route('site.language'), {}, { cache: false })

    language.value = response

    setSelectedLanguage(language.value)

    languageChangeForm.language_id = language.value?.id ?? null

    await nextTick()

    isReady.value = true
}

const languageChange = async () => {
    if (!languageChangeForm.language_id || !isReady.value) return

    languageChangeForm.errors.language_id = null

    try {
        const response = await postToApi(
            route('site.language-change', {
                slugOrId: languageChangeForm.language_id,
            })
        )

        if (response?.status) {
            language.value = response?.data ?? language.value

            setSelectedLanguage(language.value)

            await Promise.all(layoutCacheKeys.map((cacheKey) => clearByPrefix(cacheKey)))

            document.cookie = "fresh_response=1; path=/; max-age=60; SameSite=Lax"

            window.location.reload()
        }

    } catch (error) {
        languageChangeForm.errors.language_id =
            error?.response?.data?.errors?.language_id?.[0] ??
            error?.response?.data?.message ??
            'Unable to set language'
    }
}

watch(
    () => languageChangeForm.language_id,
    async () => {
        await languageChange()
    }
)

onMounted(async () => {
    await loadLanguage()
})
</script>

<template>
    <div class="w-40 max-[450px]:w-32">
        <SelectInfinityLoadingApi v-if="language" :key="language?.id" :selectedItem="language" fieldName="language_id"
            :form="languageChangeForm" :apiUrl="route('site.languages')" :error="languageChangeForm.errors.language_id"
            selectedLabelKey="name" selectedValueKey="id" apiLabelKey="name" apiValueKey="id" :multiple="false"
            :placeholder="t('common.labels.language')" :compactDesign="true" :useDarkTheme="true" />
    </div>
</template>
