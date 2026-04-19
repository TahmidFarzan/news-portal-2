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
