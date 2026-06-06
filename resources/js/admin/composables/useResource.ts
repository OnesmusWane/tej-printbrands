import { ref } from 'vue'
import { fetchAll, create, update, destroy } from '../api'
import { useToastStore } from '../stores/toast'

export function useResource<T extends { id: number }>(resource: string) {
    const toast = useToastStore()
    const items = ref<T[]>([])
    const loading = ref(false)
    const saving = ref(false)

    async function load(params: Record<string, unknown> = {}) {
        loading.value = true
        try {
            const res = await fetchAll<T>(resource, params)
            items.value = res.data
        } catch {
            toast.add('Failed to load data.', 'error')
        } finally {
            loading.value = false
        }
    }

    async function save(payload: Record<string, unknown>, id?: number): Promise<T | null> {
        saving.value = true
        try {
            const result = id
                ? await update<T>(resource, id, payload)
                : await create<T>(resource, payload)
            toast.add(id ? 'Updated successfully.' : 'Created successfully.')
            await load()
            return result
        } catch (err: any) {
            const msg = err?.response?.data?.message ?? 'Save failed.'
            toast.add(msg, 'error')
            return null
        } finally {
            saving.value = false
        }
    }

    async function remove(id: number) {
        try {
            await destroy(resource, id)
            items.value = items.value.filter(i => i.id !== id)
            toast.add('Deleted.')
        } catch {
            toast.add('Delete failed.', 'error')
        }
    }

    return { items, loading, saving, load, save, remove }
}
