import axios from 'axios'

const api = axios.create({
    baseURL: '/api/admin',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
    },
})

api.interceptors.response.use(
    res => res,
    err => {
        if (err.response?.status === 401 || err.response?.status === 403) {
            window.location.href = '/login'
        }
        return Promise.reject(err)
    },
)

export default api

export type PaginatedResponse<T> = {
    data: T[]
    current_page: number
    last_page: number
    total: number
    per_page: number
}

export async function fetchAll<T>(resource: string, params: Record<string, unknown> = {}): Promise<PaginatedResponse<T>> {
    const { data } = await api.get(`/${resource}`, { params: { per_page: 200, ...params } })
    return data
}

export async function getOne<T>(resource: string, id: number): Promise<T> {
    const { data } = await api.get(`/${resource}/${id}`)
    return data
}

export const fetchOne = getOne

export async function create<T>(resource: string, payload: Record<string, unknown>): Promise<T> {
    const { data } = await api.post(`/${resource}`, payload)
    return data
}

export async function update<T>(resource: string, id: number, payload: Record<string, unknown>): Promise<T> {
    const { data } = await api.patch(`/${resource}/${id}`, payload)
    return data
}

export async function destroy(resource: string, id: number): Promise<void> {
    await api.delete(`/${resource}/${id}`)
}
