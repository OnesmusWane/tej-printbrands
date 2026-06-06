<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '../api'
import { useToastStore } from '../stores/toast'
import ImageUpload from '../components/ImageUpload.vue'

interface Brand {
    id: number
    name: string
    logo_url?: string
    domain?: string
    industry?: string
}

const toast = useToastStore()
const items = ref<Brand[]>([])
const loading = ref(false)
const saving = ref(false)
const showModal = ref(false)
const editing = ref<Partial<Brand>>({})

const bgColors = [
    'bg-cyan-100 text-cyan-600',
    'bg-purple-100 text-purple-600',
    'bg-orange-100 text-orange-600',
    'bg-green-100 text-green-600',
    'bg-pink-100 text-pink-600',
    'bg-blue-100 text-blue-600',
    'bg-yellow-100 text-yellow-700',
    'bg-red-100 text-red-600',
]

function brandColor(id: number): string {
    return bgColors[id % bgColors.length]
}

async function load() {
    loading.value = true
    try {
        const { data } = await api.get('/brands', { params: { per_page: 200 } })
        items.value = Array.isArray(data) ? data : data.data ?? []
    } catch {
        toast.add('Failed to load brands.', 'error')
    } finally {
        loading.value = false
    }
}

function openNew() {
    editing.value = {}
    showModal.value = true
}

function openEdit(b: Brand) {
    editing.value = { ...b }
    showModal.value = true
}

async function submit() {
    saving.value = true
    try {
        if (editing.value.id) {
            await api.patch(`/brands/${editing.value.id}`, editing.value as any)
            toast.add('Brand updated.')
        } else {
            await api.post('/brands', editing.value as any)
            toast.add('Brand created.')
        }
        showModal.value = false
        await load()
    } catch (err: any) {
        toast.add(err?.response?.data?.message ?? 'Save failed.', 'error')
    } finally {
        saving.value = false
    }
}

async function remove(id: number) {
    if (!confirm('Delete this brand?')) return
    try {
        await api.delete(`/brands/${id}`)
        items.value = items.value.filter(i => i.id !== id)
        toast.add('Brand deleted.')
    } catch {
        toast.add('Delete failed.', 'error')
    }
}

onMounted(load)
</script>

<template>
    <div class="p-6 space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-dark">Brands</h1>
                <p class="text-sm text-gray-500 mt-1">{{ items.length }} brand{{ items.length !== 1 ? 's' : '' }}</p>
            </div>
            <button
                @click="openNew"
                class="inline-flex items-center gap-2 bg-cyan-500 hover:bg-cyan-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors shadow-sm"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Brand
            </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-16">
            <div class="w-8 h-8 border-4 border-cyan-500 border-t-transparent rounded-full animate-spin"></div>
        </div>

        <!-- Grid -->
        <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-4">
            <div
                v-for="brand in items"
                :key="brand.id"
                class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex flex-col items-center gap-3 relative group hover:shadow-md transition-shadow"
            >
                <!-- Edit / Delete on hover -->
                <div class="absolute top-2 right-2 hidden group-hover:flex gap-1">
                    <button
                        @click="openEdit(brand)"
                        class="w-7 h-7 rounded-full bg-gray-100 hover:bg-cyan-100 flex items-center justify-center transition-colors"
                        title="Edit"
                    >
                        <svg class="w-3.5 h-3.5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    <button
                        @click="remove(brand.id)"
                        class="w-7 h-7 rounded-full bg-gray-100 hover:bg-red-100 flex items-center justify-center transition-colors"
                        title="Delete"
                    >
                        <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>

                <!-- Logo or initial -->
                <div class="w-24 h-16 flex items-center justify-center">
                    <img
                        v-if="brand.logo_url"
                        :src="brand.logo_url"
                        :alt="brand.name"
                        class="w-24 h-16 object-contain"
                        @error="e => (e.target as HTMLImageElement).style.display='none'"
                    />
                    <div
                        v-else
                        :class="['w-14 h-14 rounded-full flex items-center justify-center text-xl font-bold', brandColor(brand.id)]"
                    >
                        {{ brand.name.charAt(0).toUpperCase() }}
                    </div>
                </div>

                <div class="text-center">
                    <p class="font-semibold text-dark text-sm">{{ brand.name }}</p>
                    <p v-if="brand.industry" class="text-xs text-gray-400 mt-0.5">{{ brand.industry }}</p>
                    <p v-if="brand.domain" class="text-xs text-cyan-500 mt-0.5 truncate max-w-[120px]">{{ brand.domain }}</p>
                </div>
            </div>

            <div v-if="items.length === 0" class="col-span-full text-center py-16 text-gray-400 text-sm">
                No brands yet. Add your first brand.
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                    @click.self="showModal = false"
                >
                    <div class="w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                            <h2 class="text-lg font-bold text-dark">{{ editing.id ? 'Edit Brand' : 'Add Brand' }}</h2>
                            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                        </div>
                        <form @submit.prevent="submit" class="px-6 py-5 space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Brand Name <span class="text-red-400">*</span></label>
                                <input v-model="editing.name" required class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                            </div>
                            <ImageUpload
                                v-model="editing.logo_url"
                                label="Brand Logo"
                                height="h-32"
                            />
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Domain <span class="text-gray-400 font-normal">(optional)</span></label>
                                    <input v-model="editing.domain" placeholder="example.com" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Industry <span class="text-gray-400 font-normal">(optional)</span></label>
                                    <input v-model="editing.industry" placeholder="Technology, Retail…" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                                </div>
                            </div>
                            <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                                <button type="button" @click="showModal = false" class="px-4 py-2 text-sm font-semibold text-gray-600 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">Cancel</button>
                                <button type="submit" :disabled="saving" class="px-5 py-2 text-sm font-semibold bg-cyan-500 hover:bg-cyan-600 disabled:opacity-60 text-white rounded-xl transition-colors">
                                    {{ saving ? 'Saving…' : (editing.id ? 'Update' : 'Create') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.97); }
</style>
