import { useI18n } from 'vue-i18n'
import { i18n } from '@/i18n'

export const useTranslate = () => {
    const { t, locale } = useI18n()

    return {
        t,
        locale,
    }
}

export const setSelectedLanguage = (language) => {
    i18n.global.locale.value = language?.code ?? 'en'
}
