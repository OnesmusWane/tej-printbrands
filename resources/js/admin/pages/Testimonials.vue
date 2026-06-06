<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api from '../api'
import { useToastStore } from '../stores/toast'

interface Testimonial {
    id: number
    name: string
    role: string
    text: string
    rating: number
    status: 'pending' | 'approved' | 'rejected'
    created_at: string
}

const toast = useToastStore()
const items = ref<Testimonial[]>([])
const loading = ref(false)
const saving = ref(false)
const showModal = ref(false)
const editing = ref<Partial<Testimonial>>({})
const activeFilter = ref<'all' | 'pending' | 'approved' | 'rejected'>('all')
const hoverRating = ref(0)

const filters: { key: 'all' | 'pending' | 'approved' | 'rejected'; label: string }[] = [
    { key: 'all', label: 'All' },
    { key: 'pending', label: 'Pending Review' },
    { key: 'approved', label: 'Approved' },
    { key: 'rejected', label: 'Rejected' },
]

function count(key: string): number {
    if (key === 'all') return items.value.length
    return items.value.filter(t => t.status === key).length
}

const filtered = computed(() => {
    if (activeFilter.value === 'all') return items.value
    return items.value.filter(t => t.status === activeFilter.value)
})

const avatarColors = [
    'from-cyan-400 to-cyan-600',
    'from-purple-400 to-purple-600',
    'from-pink-400 to-pink-600',
    'from-orange-400 to-orange-600',
    'from-green-400 to-green-600',
    'from-blue-400 to-blue-600',
]

function avatarColor(id: number): string {
    return avatarColors[id % avatarColors.length]
}

function statusClass(status: string): string {
    if (status === 'approved') return 'bg-green-50 text-green-600'
    if (status === 'rejected') return 'bg-red-50 text-red-500'
    return 'bg-yellow-50 text-yellow-600'
}

function statusLabel(status: string): string {
    if (status === 'approved') return 'Approved'
    if (status === 'rejected') return 'Rejected'
    return 'Pending'
}

function formatDate(d: string): string {
    if (!d) return ''
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
}

async function load() {
    loading.value = true
    try {
        const { data } = await api.get('/testimonials', { params: { per_page: 200 } })
        items.value = Array.isArray(data) ? data : data.data ?? []
    } catch {
        toast.add('Failed to load testimonials.', 'error')
    } finally {
        loading.value = false
    }
}

function openNew() {
    editing.value = { rating: 5, status: 'pending' }
    hoverRating.value = 0
    showModal.value = true
}

function openEdit(t: Testimonial) {
    editing.value = { ...t }
    hoverRating.value = 0
    showModal.value = true
}

async function setStatus(id: number, status: 'approved' | 'rejected') {
    try {
        await api.patch(`/testimonials/${id}`, { status })
        const t = items.value.find(i => i.id === id)
        if (t) t.status = status
        toast.add(status === 'approved' ? 'Testimonial approved.' : 'Testimonial rejected.')
    } catch {
        toast.add('Update failed.', 'error')
    }
}

async function submit() {
    saving.value = true
    try {
        if (editing.value.id) {
            await api.patch(`/testimonials/${editing.value.id}`, editing.value as any)
            toast.add('Testimonial updated.')
        } else {
            await api.post('/testimonials', editing.value as any)
            toast.add('Testimonial created.')
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
    if (!confirm('Delete this testimonial?')) return
    try {
        await api.delete(`/testimonials/${id}`)
        items.value = items.value.filter(i => i.id !== id)
        toast.add('Testimonial deleted.')
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
                <h1 class="text-2xl font-bold text-dark">Testimonials</h1>
                <p class="text-sm text-gray-500 mt-1">Manage customer reviews and testimonials.</p>
            </div>
            <button
                @click="openNew"
                class="inline-flex items-center gap-2 bg-cyan-500 hover:bg-cyan-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors shadow-sm"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Testimonial
            </button>
        </div>

        <!-- Filter buttons -->
        <div class="flex gap-2 flex-wrap">
            <button
                v-for="f in filters"
                :key="f.key"
                @click="activeFilter = f.key"
                :class="[
                    'inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-sm font-medium transition-colors',
                    activeFilter === f.key
                        ? 'bg-cyan-500 text-white'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                ]"
            >
                {{ f.label }}
                <span :class="['text-xs rounded-full px-1.5 py-0.5 font-semibold', activeFilter === f.key ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600']">
                    {{ count(f.key) }}
                </span>
            </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-16">
            <div class="w-8 h-8 border-4 border-cyan-500 border-t-transparent rounded-full animate-spin"></div>
        </div>

        <!-- Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <div
                v-for="t in filtered"
                :key="t.id"
                class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex flex-col gap-3 relative"
            >
                <!-- Status badge -->
                <span :class="['absolute top-4 right-4 text-[10px] font-bold px-2 py-0.5 rounded-full', statusClass(t.status)]">
                    {{ statusLabel(t.status) }}
                </span>

                <!-- Avatar + name row -->
                <div class="flex items-center gap-3">
                    <div :class="['w-10 h-10 rounded-full bg-gradient-to-br flex items-center justify-center text-white font-bold text-sm flex-shrink-0', avatarColor(t.id)]">
                        {{ t.name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <p class="font-semibold text-dark text-sm leading-snug">{{ t.name }}</p>
                        <p class="text-xs text-gray-500">{{ t.role }}</p>
                    </div>
                </div>

                <!-- Star rating -->
                <div class="flex gap-0.5">
                    <svg
                        v-for="star in 5"
                        :key="star"
                        :class="['w-4 h-4', star <= t.rating ? 'text-yellow-400' : 'text-gray-200']"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                    </svg>
                </div>

                <!-- Quote text -->
                <p class="text-sm text-gray-600 italic leading-relaxed line-clamp-3">"{{ t.text }}"</p>

                <!-- Date -->
                <p class="text-xs text-gray-400">{{ formatDate(t.created_at) }}</p>

                <!-- Actions -->
                <div class="flex items-center gap-2 pt-1 border-t border-gray-100">
                    <button
                        v-if="t.status !== 'approved'"
                        @click="setStatus(t.id, 'approved')"
                        class="flex-1 text-xs font-semibold text-green-600 border border-green-200 rounded-lg py-1.5 hover:bg-green-50 transition-colors"
                    >Approve</button>
                    <button
                        v-if="t.status !== 'rejected'"
                        @click="setStatus(t.id, 'rejected')"
                        class="flex-1 text-xs font-semibold text-red-500 border border-red-100 rounded-lg py-1.5 hover:bg-red-50 transition-colors"
                    >Reject</button>
                    <button
                        @click="openEdit(t)"
                        title="Edit"
                        class="w-8 h-8 shrink-0 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 transition-colors"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    <button
                        @click="remove(t.id)"
                        title="Delete"
                        class="w-8 h-8 shrink-0 flex items-center justify-center rounded-lg border border-red-100 text-red-400 hover:bg-red-50 transition-colors"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div v-if="filtered.length === 0" class="col-span-full text-center py-16 text-gray-400 text-sm">
                No testimonials in this category.
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
                            <h2 class="text-lg font-bold text-dark">{{ editing.id ? 'Edit Testimonial' : 'Add Testimonial' }}</h2>
                            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                        </div>
                        <form @submit.prevent="submit" class="px-6 py-5 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Name <span class="text-red-400">*</span></label>
                                    <input v-model="editing.name" required class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Role / Company</label>
                                    <input v-model="editing.role" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all" placeholder="CEO, Acme Corp"/>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Review Text <span class="text-red-400">*</span></label>
                                <textarea v-model="editing.text" rows="4" required class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all resize-none"></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-2">Rating</label>
                                <div class="flex gap-1">
                                    <button
                                        v-for="star in 5"
                                        :key="star"
                                        type="button"
                                        @click="editing.rating = star"
                                        @mouseenter="hoverRating = star"
                                        @mouseleave="hoverRating = 0"
                                        class="focus:outline-none"
                                    >
                                        <svg
                                            :class="['w-7 h-7 transition-colors', star <= (hoverRating || editing.rating || 0) ? 'text-yellow-400' : 'text-gray-200']"
                                            fill="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Status</label>
                                <select v-model="editing.status" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all bg-white">
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
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
