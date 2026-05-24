export const newsTypes = {
    Story: 'Story',
    Video: 'Video',
    ImageGallery: "Image Gallery",
}

export const getNewsTypeName = (newsType) => {
    return newsType?.name?.toLowerCase() ?? ''
}

export const isStory = (newsType) => {
    return getNewsTypeName(newsType) === newsTypes.Story.toLowerCase()
}

export const isVideo = (newsType) => {
    return getNewsTypeName(newsType) === newsTypes.Video.toLowerCase()
}

export const isImageGallery = (newsType) => {
    return getNewsTypeName(newsType) === newsTypes.ImageGallery.toLowerCase()
}
