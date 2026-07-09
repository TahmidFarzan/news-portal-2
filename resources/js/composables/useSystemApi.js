import axios from 'axios'
import { smartCacheKey, smartCacheTTL, useApiSmartCache } from '@/composables/useApiSmartCache'

const { remember, rememberApi, remove } = useApiSmartCache()

const fetchJson = async (url, params = {}) => {
    const res = await axios.get(url, { params })
    return res.data || []
}

export async function fetchFromApi(url, params = {}, options = {}) {
    try {
        return await rememberApi(
            { url, params },
            () => fetchJson(url, params),
            {
                key: options.key || smartCacheKey.DEFAULT,
                ttl: options.ttl ?? smartCacheTTL.SYSTEM_SHORT,
                force: options.force ?? false,
            }
        )
    } catch (error) {
        console.error(`Failed to fetch from ${url}.`, error);
        return [];
    }
}

export async function fetchUser(userSlugOrId) {
    if (!userSlugOrId) return null

    const cacheKey = `${smartCacheKey.API_USER}:${userSlugOrId}`

    try {
        return await remember(
            cacheKey,
            async () => {
                const response = await axios.get(route('search.user', { slugOrId: userSlugOrId }))
                return response.data || null
            },
            {
                ttl: smartCacheTTL.SYSTEM_SHORT,
            }
        )
    }
    catch (error) {
        console.error(`Failed to fetch user ${userSlugOrId}.`, error)
        remove(cacheKey)
        return null
    }
}

export async function postToApi(url, data = {}, config = {}) {
    try {
        const res = await axios.post(url, data, config)
        return res.data || null
    } catch (error) {
        console.error(`Failed to post to ${url}.`, error)
        return null
    }
}
