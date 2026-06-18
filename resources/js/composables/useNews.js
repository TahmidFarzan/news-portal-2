export const newsTypes = {
    Story: {
        Name: 'Story',
        Slug: 'story',
    },
    Video: {
        Name: 'Video',
        Slug: 'video',
    },
    ImageGallery: {
        Name: 'Image Gallery',
        Slug: 'image-gallery',
    },
}

export const getNewsTypeName = (newsType) => {
    return newsType?.name?.toLowerCase() ?? ''
}

export const getNewsTypeSlug = (newsType) => {
    return newsType?.slug?.toLowerCase() ?? ''
}

export const isStory = (newsType) => {
    return getNewsTypeName(newsType) === newsTypes.Story.Name.toLowerCase()
        || getNewsTypeSlug(newsType) === newsTypes.Story.Slug
}

export const isVideo = (newsType) => {
    return getNewsTypeName(newsType) === newsTypes.Video.Name.toLowerCase()
        || getNewsTypeSlug(newsType) === newsTypes.Video.Slug
}

export const isImageGallery = (newsType) => {
    return getNewsTypeName(newsType) === newsTypes.ImageGallery.Name.toLowerCase()
        || getNewsTypeSlug(newsType) === newsTypes.ImageGallery.Slug
}
