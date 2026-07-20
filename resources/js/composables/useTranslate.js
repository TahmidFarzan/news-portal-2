import { useI18n } from 'vue-i18n'
import { i18n } from '@/i18n'

export const languages = {
    English: {
        Name: 'English',
        Slug: 'english',
        Code: 'en',
        Locale: 'en_US',
        IsDefault: true,
    },

    Bangla: {
        Name: 'বাংলা',
        Slug: 'bangla',
        Code: 'bn',
        Locale: 'bn_BD',
        IsDefault: false,
    },
}
const translate = i18n.global.t

export const useTranslate = () => {
    const { t, locale } = useI18n()

    return {
        t,
        locale,
    }
}

export const setSelectedLanguage = (language) => {
    i18n.global.locale.value = language?.code ?? languages.English.Code
}

export const getSelectedLanguage = () => {
    const currentLocale = i18n.global.locale.value

    return (
        Object.values(languages).find(
            (language) => language.Code === currentLocale,
        ) ?? languages.English
    )
}

export const getSelectedLanguageCode = () => getSelectedLanguage().Code

export const getSelectLanguageCode = getSelectedLanguageCode

export const generateTranslationKey = (value) => {
    return String(value ?? '')
        .trim()
        .toLowerCase()
        .replaceAll(' ', '_')
}

export const translateNumerText = (text) => {
    return String(text)
        .split('')
        .map((char) => translate(`numbers.${char}`))
        .join('')
}
