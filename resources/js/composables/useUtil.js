export const titleFormat = (text) => {
    if (!text) return "";

    return String(text)
        .replace(/([a-z])([A-Z])/g, "$1 $2")
        .replace(/[_\-]+/g, " ")
        .trim()
        .toLowerCase()
        .replace(/^\w/, (c) => c.toUpperCase());
};

export const extractModelName = (fullClassName) => {
    if (!fullClassName) return '';
    return fullClassName.split(/\\+/).pop();
}

export const replaceAllOccurrences = (text, search, replace) => {
    if (!text || !search) return text;
    const escapedSearch = search.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp(escapedSearch, 'g');

    return text.replace(regex, replace);
};

export const itemListFilterParameters = (filterForm = {}) => {
    return Object.fromEntries(
        Object.entries(filterForm)
            .filter(([key, val]) =>
                key !== 'page' &&
                val !== null &&
                val !== undefined &&
                !(typeof val === 'string' && val.trim() === '')
            )
    )
}

export const formatAmountWithCurrency = (val, currency) => {
    return `${Number(val).toLocaleString()} ${currency}`
}

export const calculatePercentOf = (val, total) => {
    return total ? ((val / total) * 100).toFixed(1) : 0;
}

export const capitalize = (str) => {
    if (!str) return 'N/A'
    return str.charAt(0).toUpperCase() + str.slice(1)
}
