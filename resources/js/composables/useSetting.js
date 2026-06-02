export const settingTypes = Object.freeze({
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

export const settingFields = Object.freeze({
    SHOW_FOOTER_MENU: 'Show Footer Menu',
    SHOW_TOPBAR_MENU: 'Show Topbar Menu',
    FB_SOCIAL_LINK: 'Fb Social Link',
    YOUTUBE_SOCIAL_LINK: 'Youtube Social Link',
    GOOGLE_NEWS_SOCIAL_LINK: 'Google News Link',
    SHOW_LOGO_ON_HEADER_MENU: 'Show Logo On Header Menu',
    SHOW_NAME_ON_HEADER_MENU: 'Show Name On Header Menu',
})

export function useSetting() {
    const settingTypeOptions = Object.values(settingTypes)

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
        if (type === settingTypes.BOOLEAN) {
            return false
        }

        if (type === settingTypes.ARRAY) {
            return '[]'
        }

        if (type === settingTypes.JSON) {
            return '{}'
        }

        if (type === settingTypes.COLOR) {
            return '#000000'
        }

        return null
    }

    return {
        settingTypes,
        settingTypeOptions,
        settingGroups,
        settingFields,
        isEmpty,
        hasValue,
        isTruthyValue,
        getDefaultValueByType,
    }
}
