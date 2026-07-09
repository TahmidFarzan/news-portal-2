<script setup>
import { ref, reactive, watch, nextTick, onMounted } from 'vue'
import SelectInfinityLoadingApi from '@/components/common/multi-select/SelectInfinityLoadingApi.vue'
import { fetchFromApi, postToApi } from '@/composables/useSystemApi'
import { setSelectedLanguage, useTranslate } from '@/composables/useTranslate'

const language = ref(null)
const isReady = ref(false)

const { t } = useTranslate()

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

            window.location.href = route('home')
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
        <SelectInfinityLoadingApi v-if="language" :key="language?.id" :selectedItem="language"
            fieldName="language_id" :form="languageChangeForm" :apiUrl="route('site.languages')"
            :error="languageChangeForm.errors.language_id" selectedLabelKey="name" selectedValueKey="id"
            apiLabelKey="name" apiValueKey="id" :multiple="false" :placeholder="t('common.labels.language')" :compactDesign="true"
            :useDarkTheme="true" />
    </div>
</template>
