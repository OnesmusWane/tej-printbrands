import { defineStore } from 'pinia'
import { ref } from 'vue'

export type Toast = { id: number; message: string; type: 'success' | 'error' | 'info' }

export const useToastStore = defineStore('toast', () => {
    const toasts = ref<Toast[]>([])
    let seq = 0

    function add(message: string, type: Toast['type'] = 'success') {
        const id = ++seq
        toasts.value.push({ id, message, type })
        setTimeout(() => remove(id), 4000)
    }

    function remove(id: number) {
        toasts.value = toasts.value.filter(t => t.id !== id)
    }

    return { toasts, add, remove }
})
