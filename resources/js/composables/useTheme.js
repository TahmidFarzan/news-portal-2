export const themeValueTypes = Object.freeze({
    TEXT: 'Text',
    STRING: 'String',
    BOOLEAN: 'Boolean',
    INTEGER: 'Integer',
    FLOAT: 'Float',
    DECIMAL: 'Decimal',
    JSON: 'Json',
    ARRAY: 'Array',
    URL: 'Url',
    IMAGE: 'Image',
    COLOR: 'Color',
})

export const themeGroups = Object.freeze({
    App: 'App',
    MENU: 'Menu',
    SOCIAL_LINK: 'Social Link',
})

export const themeOptions = Object.freeze({
    SHOW_FOOTER_MENU: 'Show Footer Menu',
    SHOW_TOPBAR_MENU: 'Show Topbar Menu',
    FB_SOCIAL_LINK: 'Fb Social Link',
    YOUTUBE_SOCIAL_LINK: 'Youtube Social Link',
    GOOGLE_NEWS_SOCIAL_LINK: 'Google News Link',
    SHOW_LOGO_ON_HEADER_MENU: 'Show Logo On Header Menu',
    SHOW_NAME_ON_HEADER_MENU: 'Show Name On Header Menu',
    SHOW_BREAKING_NEWS  : "Show Breaking News",
    SHOW_GOOGLE_AD  : "Show Google Ad",
    SHOW_TRENDS : "Show Trends",
    SHOW_SURVEYS : "Show Surveys",
    SHOW_QUIZZES : "Show Quizzes",
    GOOGLE_SEARCH_CONSOLE_HEADER  : "Google Search Console Header",
    GOOGLE_ANALYTIC_HEADER  : "Google Analytic Header",
    GOOGLE_TAG_MANAGER_HEADER  : "Google Tag Manager Header",
    GOOGLE_TAG_MANAGER_BODY  : "Google Tag Manager Body",
    GOOGLE_ADSENSE_CLIENT_ID: "Google GoogleAdsense Client Id"
})

export function useTheme() {
    const themeValueTypeOptions = Object.values(themeValueTypes)

    const isEmpty = (value) => {
        return value === null ||
            value === undefined ||
            (typeof value === 'string' && value.trim() === '')
    }

    const hasValue = (value) => {
        return value !== null && value !== undefined && value !== ''
    }

    const isTruthyValue = (value) => {
        return value === true ||
            value === 1 ||
            value === '1' ||
            String(value).toLowerCase() === 'true' ||
            String(value).toLowerCase() === 'yes' ||
            String(value).toLowerCase() === 'on'
    }

    const getDefaultValueByType = (type) => {
        if (type === themeValueTypes.BOOLEAN) {
            return false
        }

        if (type === themeValueTypes.ARRAY) {
            return '[]'
        }

        if (type === themeValueTypes.JSON) {
            return '{}'
        }

        if (type === themeValueTypes.COLOR) {
            return '#000000'
        }

        return null
    }

    return {
        themeValueTypes,
        themeValueTypeOptions,
        themeGroups,
        themeOptions,
        isEmpty,
        hasValue,
        isTruthyValue,
        getDefaultValueByType,
    }
}
