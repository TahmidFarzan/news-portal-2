export const themeNames = Object.freeze({
    HEADER_MENU: 'Header Menu',
    GOOGLE_AD: 'Google Ad',
    SITE_EXTRA_FEATURE: 'Site Extra Feature',
    TOPBAR_FOOTER_MENU: 'Topbar & Footer Menu',
    SOCIAL_LINK: 'Social Link',
    GOOGLE_SEO_SERVICE: 'Google SEO Service',
})

export const themeOptionValueTypes = Object.freeze({
    TEXT: 'text',
    STRING: 'string',
    BOOLEAN: 'boolean',
    INTEGER: 'integer',
    FLOAT: 'float',
    DECIMAL: 'decimal',
    JSON: 'json',
    ARRAY: 'array',
    URL: 'url',
    IMAGE: 'image',
    COLOR: 'color',
})

export const themeOptions = Object.freeze({
    SHOW_FOOTER_MENU: 'show-footer-menu',
    SHOW_TOPBAR_MENU: 'show-topbar-menu',
    FB_SOCIAL_LINK: 'fb-social-link',
    YOUTUBE_SOCIAL_LINK: 'youtube-social-link',
    GOOGLE_NEWS_SOCIAL_LINK: 'google-news-social-link',
    SHOW_LOGO_ON_HEADER_MENU: 'show-logo-on-header-menu',
    SHOW_NAME_ON_HEADER_MENU: 'show-name-on-header-menu',
    SHOW_BREAKING_NEWS: 'show-breaking-news',
    SHOW_TRENDS: 'show-trends',
    SHOW_SURVEYS: 'show-surveys',
    SHOW_QUIZZES: 'show-quizzes',
    GOOGLE_AD_ENABLE: 'google-ad-enable',
    GOOGLE_AD_ADSENSE_CLIENT_ID: 'google-ad-adsense-client-id',
    GOOGLE_SEARCH_CONSOLE_HEADER: 'google-search-console-header',
    GOOGLE_ANALYTIC_HEADER: 'google-analytic-header',
    GOOGLE_TAG_MANAGER_HEADER: 'google-tag-manager-header',
    GOOGLE_TAG_MANAGER_BODY: 'google-tag-manager-body',
})

export const formatThemeValue = (value) => {
    if (value === null || value === undefined) {
        return ''
    }
    if (typeof value !== 'object') {
        return String(value)
    }
    return JSON.stringify(value, null, 2)
}

export function useTheme() {
    const themeOptionValueTypeOptions = Object.values(themeOptionValueTypes)

    const isEmpty = (value) => {
        return value === null ||
            value === undefined ||
            (typeof value === 'string' && value.trim() === '')
    }

    const hasValue = (value) => {
        return value !== null &&
            value !== undefined &&
            value !== ''
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
        if (type === themeOptionValueTypes.BOOLEAN) {
            return false
        }
        if (type === themeOptionValueTypes.ARRAY) {
            return []
        }
        if (type === themeOptionValueTypes.JSON) {
            return {}
        }
        if (type === themeOptionValueTypes.COLOR) {
            return '#000000'
        }
        return null
    }

    return {
        themeOptionValueTypes,
        themeOptionValueTypeOptions,
        themeOptions,
        themeNames,
        isEmpty,
        hasValue,
        isTruthyValue,
        getDefaultValueByType,
        formatThemeValue,
    }
}
