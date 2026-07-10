import { useI18n } from 'vue-i18n'
import { i18n } from '@/i18n'

export const languages = {
    English: {
        Name: 'English',
        Slug: 'english',
        Code: 'en',
        Locale: 'en_US',
    },

    Bangla: {
        Name: 'বাংলা',
        Slug: 'bangla',
        Code: 'bn',
        Locale: 'bn_BD',
    },
}

export const useTranslate = () => {
    const { t, locale } = useI18n()

    return {
        t,
        locale,
    }
}

export const setSelectedLanguage = (language) => {
    i18n.global.locale.value =
        language?.code ?? language?.Code ?? languages.English.Code
}

export const getSelectedLanguage = () => {
    const currentLocale = i18n.global.locale.value

    return (
        Object.values(languages).find(
            (language) => language.Code === currentLocale,
        ) ?? languages.English
    )
}

export const getSelectedLanguageCode = () =>
    getSelectedLanguage().Code

export const getSelectLanguageCode = getSelectedLanguageCode

export const generateTranslationKey = (value) => {
    return String(value ?? '')
        .trim()
        .toLowerCase()
        .replaceAll(' ', '_')
}
