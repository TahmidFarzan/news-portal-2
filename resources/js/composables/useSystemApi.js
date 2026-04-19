import axios from 'axios'

export async function fetchFromApi(url, params = {}) {
    try {
        const res = await axios.get(url, { params });
        return res.data || [];
    } catch (error) {
        console.error(`Failed to fetch from ${url}.`, error);
        return [];
    }
}

export async function fetchUser(userSlugOrId) {
    if (!userSlugOrId) return null

    const cachedUser = getUserCache(userSlugOrId)
    const expiredUserCache = checkExpiredUserCache(userSlugOrId)
    if (!expiredUserCache && cachedUser) return cachedUser

    try {
        const response = await axios.get(route('search.user', { slugOrId: userSlugOrId }))
        const user = response.data || null
        if (user) {
            saveUserCache(userSlugOrId, user)
        }

        return user
    }
    catch (error) {
        console.error(`Failed to fetch user ${userSlugOrId}.`, error)
        deleteUserCache(userSlugOrId)
        return null
    }
}
