export const settingValueTypes = Object.freeze({
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

export const settingGroups = Object.freeze({
    App: 'App',
    MENU: 'Menu',
    SOCIAL_LINK: 'Social Link',
})

export const settingOptions = Object.freeze({
    SHOW_FOOTER_MENU: 'Show Footer Menu',
    SHOW_TOPBAR_MENU: 'Show Topbar Menu',
    FB_SOCIAL_LINK: 'Fb Social Link',
    YOUTUBE_SOCIAL_LINK: 'Youtube Social Link',
    GOOGLE_NEWS_SOCIAL_LINK: 'Google News Link',
    SHOW_LOGO_ON_HEADER_MENU: 'Show Logo On Header Menu',
    SHOW_NAME_ON_HEADER_MENU: 'Show Name On Header Menu',
    SHOW_BREAKING_NEWS  : "Show Breaking News"
})

export function useSetting() {
    const settingValueTypeOptions = Object.values(settingValueTypes)

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
        if (type === settingValueTypes.BOOLEAN) {
            return false
        }

        if (type === settingValueTypes.ARRAY) {
            return '[]'
        }

        if (type === settingValueTypes.JSON) {
            return '{}'
        }

        if (type === settingValueTypes.COLOR) {
            return '#000000'
        }

        return null
    }

    return {
        settingValueTypes,
        settingValueTypeOptions,
        settingGroups,
        settingOptions,
        isEmpty,
        hasValue,
        isTruthyValue,
        getDefaultValueByType,
    }
}
