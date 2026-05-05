const newsTypes = {
    Story: 'Story',
    Video: 'Video',
}

export const isStory = (newsType = '') =>
    newsType.toLowerCase() === newsTypes.Story.toLowerCase()

export const isVideo = (newsType = '') =>
    newsType.toLowerCase() === newsTypes.Video.toLowerCase()
