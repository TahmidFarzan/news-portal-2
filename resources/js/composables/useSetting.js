export const SETTING_TYPES = Object.freeze({
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

export function useSetting() {
    const settingTypes = Object.values(SETTING_TYPES)

    const isEmpty = (value) => {
        return value === null ||
            value === undefined ||
            (typeof value === 'string' && value.trim() === '')
    }

    const hasValue = (value) => {
        return value !== null && value !== undefined && value !== ''
    }

    const getDefaultValueByType = (type) => {
        if (type === SETTING_TYPES.BOOLEAN) {
            return false
        }

        if (type === SETTING_TYPES.ARRAY) {
            return '[]'
        }

        if (type === SETTING_TYPES.JSON) {
            return '{}'
        }

        if (type === SETTING_TYPES.COLOR) {
            return '#000000'
        }

        return null
    }

    return {
        SETTING_TYPES,
        settingTypes,
        isEmpty,
        hasValue,
        getDefaultValueByType,
    }
}
