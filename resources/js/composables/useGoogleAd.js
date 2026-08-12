export const popupLabel = 'Pop Up'
export const popupAdTypeSeparator = ' - '

export const defaultAdSizes = Object.freeze([
    [300, 250],
])

export const popupAdPages = Object.freeze({
    HOME: 'Home Page',
    LATEST: 'Latest Page',
    SEARCH: 'Search Page',
    VIDEO: 'Video Page',
    IMAGE_GALLERY: 'Image Gallery Page',
    CATEGORY: 'Category Page',
    TAG: 'Tag Page',
    EVENT: 'Event Page',
    LOCATION: 'Location Page',
    NEWS_DETAILS: 'News Details Page',
    CONTACT: 'Contact Page',
    ABOUT: 'About Page',
    OTHER: 'Other Page',
})

export const adTypes = Object.freeze({
    SECTION: 'Section',
    SIDEBAR: 'Sidebar',

    POPUP_HOME_PAGE: `${popupLabel}${popupAdTypeSeparator}${popupAdPages.HOME}`,
    POPUP_LATEST_PAGE: `${popupLabel}${popupAdTypeSeparator}${popupAdPages.LATEST}`,
    POPUP_SEARCH_PAGE: `${popupLabel}${popupAdTypeSeparator}${popupAdPages.SEARCH}`,
    POPUP_VIDEO_PAGE: `${popupLabel}${popupAdTypeSeparator}${popupAdPages.VIDEO}`,
    POPUP_IMAGE_GALLERY_PAGE: `${popupLabel}${popupAdTypeSeparator}${popupAdPages.IMAGE_GALLERY}`,
    POPUP_CATEGORY_PAGE: `${popupLabel}${popupAdTypeSeparator}${popupAdPages.CATEGORY}`,
    POPUP_TAG_PAGE: `${popupLabel}${popupAdTypeSeparator}${popupAdPages.TAG}`,
    POPUP_EVENT_PAGE: `${popupLabel}${popupAdTypeSeparator}${popupAdPages.EVENT}`,
    POPUP_LOCATION_PAGE: `${popupLabel}${popupAdTypeSeparator}${popupAdPages.LOCATION}`,
    POPUP_NEWS_DETAILS_PAGE: `${popupLabel}${popupAdTypeSeparator}${popupAdPages.NEWS_DETAILS}`,
    POPUP_CONTACT_PAGE: `${popupLabel}${popupAdTypeSeparator}${popupAdPages.CONTACT}`,
    POPUP_ABOUT_PAGE: `${popupLabel}${popupAdTypeSeparator}${popupAdPages.ABOUT}`,
    POPUP_OTHER_PAGE: `${popupLabel}${popupAdTypeSeparator}${popupAdPages.OTHER}`,
})

export const adPositions = Object.freeze({
    TOP: 'Top',
    BETWEEN: 'Between',
    BOTTOM: 'Bottom',
})
