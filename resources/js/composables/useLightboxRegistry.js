import { reactive } from 'vue'

const lightboxGroups = reactive({})

export const useLightboxRegistry = () => {
    const getGroup = (name = 'Image gallery') => {
        if (!lightboxGroups[name]) {
            lightboxGroups[name] = []
        }

        return lightboxGroups[name]
    }

    const registerToLightbox = (name, image) => {
        if (!image?.uid || !image?.src) {
            return
        }

        const group = getGroup(name)
        const existingIndex = group.findIndex((item) => item.uid === image.uid)

        if (existingIndex >= 0) {
            group.splice(existingIndex, 1, image)
        } else {
            group.push(image)
        }
    }

    const unregisterFromLightbox = (name, uid) => {
        const group = getGroup(name)
        const existingIndex = group.findIndex((item) => item.uid === uid)

        if (existingIndex >= 0) {
            group.splice(existingIndex, 1)
        }
    }

    return {
        getGroup,
        registerToLightbox,
        unregisterFromLightbox,
    }
}
