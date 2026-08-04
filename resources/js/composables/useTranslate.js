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

export const translateMonth = (month, short = false) => {
    return translate(
        `date.${short ? 'months_short' : 'months'}.${Number(month)}`
    )
}

export const translateWeekday = (weekday, short = false) => {
    return translate(
        `date.${short ? 'weekdays_short' : 'weekdays'}.${Number(weekday)}`
    )
}

export const translateMeridiem = (hour) => {
    return translate(hour < 12 ? 'date.am' : 'date.pm')
}

export const translateDate = ( date, format = 'DD-MMMM-YYYY') => {
    const d = new Date(date)

    if (Number.isNaN(d.getTime())) {
        return ''
    }

    const values = {
        DD: translateNumerText(d.getDate().toString().padStart(2, '0')),
        D: translateNumerText(d.getDate()),
        MMMM: translateMonth(d.getMonth() + 1),
        MMM: translateMonth(d.getMonth() + 1, true),
        YYYY: translateNumerText(d.getFullYear()),
        YY: translateNumerText(String(d.getFullYear()).slice(-2)),
        dddd: translateWeekday(d.getDay()),
        ddd: translateWeekday(d.getDay(), true),
    }

    let result = format

    Object.entries(values).forEach(([key, value]) => {
        result = result.replaceAll(key, value)
    })

    return result
}

export const translateTime = ( date, format = 'hh:mm A') => {
    const d = new Date(date)

    if (Number.isNaN(d.getTime())) {
        return ''
    }

    const hour24 = d.getHours()
    const hour12 = hour24 % 12 || 12

    const values = {
        HH: translateNumerText(String(hour24).padStart(2, '0')),
        H: translateNumerText(hour24),
        hh: translateNumerText(String(hour12).padStart(2, '0')),
        h: translateNumerText(hour12),
        mm: translateNumerText(String(d.getMinutes()).padStart(2, '0')),
        ss: translateNumerText(String(d.getSeconds()).padStart(2, '0')),
        A: translateMeridiem(hour24),
    }

    let result = format

    Object.entries(values).forEach(([key, value]) => {
        result = result.replaceAll(key, value)
    })

    return result
}
