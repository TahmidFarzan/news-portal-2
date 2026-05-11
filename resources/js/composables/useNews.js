export const newsTypes = {
    STORY: 'Story',
    VIDEO: 'Video',
    IMAGEGALLERY: "Image Gallery",
}

export const getNewsTypeName = (newsType) => {
    return newsType?.name?.toLowerCase() ?? ''
}

export const isStory = (newsType) => {
    return getNewsTypeName(newsType) === newsTypes.STORY.toLowerCase()
}

export const isVideo = (newsType) => {
    return getNewsTypeName(newsType) === newsTypes.VIDEO.toLowerCase()
}

export const isImageGallery = (newsType) => {
    return getNewsTypeName(newsType) === newsTypes.IMAGEGALLERY.toLowerCase()
}
