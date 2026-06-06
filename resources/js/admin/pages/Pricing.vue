<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api from '../api'
import { useToastStore } from '../stores/toast'

interface PricingTier {
    id: number
    slug: string
    name: string
    price: string | number
    old_price?: string | number
    description: string
    features: string[] | string
    is_popular: boolean
    highlighted: boolean   // appended alias for is_popular
    color: 'gray' | 'cyan' | 'dark'
    orders_count?: number
    is_visible: boolean
}

const toast    = useToastStore()
const items    = ref<PricingTier[]>([])
const loading  = ref(false)
const saving   = ref(false)
const toggling = ref<number | null>(null)
const showModal = ref(false)
const editing  = ref<Partial<PricingTier & { is_popular: boolean }>>({})

const totalOrders = computed(() => items.value.reduce((s, t) => s + (t.orders_count ?? 0), 0))
const mostPopular = computed(() => items.value.find(t => t.is_popular || t.highlighted)?.name ?? '–')
const avgValue    = computed(() => {
    const prices = items.value.map(t => parseFloat(String(t.price).replace(/[^0-9.]/g, ''))).filter(Boolean)
    if (!prices.length) return '–'
    return Math.round(prices.reduce((a, b) => a + b, 0) / prices.length).toLocaleString()
})

const featuresText = computed({
    get() {
        if (!editing.value.features) return ''
        return Array.isArray(editing.value.features)
            ? editing.value.features.join('\n')
            : editing.value.features
    },
    set(val: string) { editing.value.features = val },
})

function isPopular(t: PricingTier) { return t.is_popular || t.highlighted }

async function load() {
    loading.value = true
    try {
        const { data } = await api.get('/pricing-tiers', { params: { per_page: 200 } })
        items.value = Array.isArray(data) ? data : (data.data ?? [])
    } catch {
        toast.add('Failed to load pricing tiers.', 'error')
    } finally {
        loading.value = false
    }
}

function openNew() {
    editing.value = { is_popular: false, color: 'gray', features: [], is_visible: true }
    showModal.value = true
}
function openEdit(t: PricingTier) {
    editing.value = {
        ...t,
        is_popular: t.is_popular || t.highlighted,
        features: Array.isArray(t.features) ? t.features.join('\n') : (t.features ?? ''),
    }
    showModal.value = true
}

async function togglePopular(tier: PricingTier) {
    const nowPopular = !isPopular(tier)
    toggling.value = tier.id
    try {
        await api.patch(`/pricing-tiers/${tier.id}`, { is_popular: nowPopular })
        // The backend enforces exclusivity — reload to reflect all changes
        await load()
        toast.add(nowPopular ? `${tier.name} marked as most popular.` : 'Popular badge removed.')
    } catch {
        toast.add('Update failed.', 'error')
    } finally {
        toggling.value = null
    }
}

async function submit() {
    saving.value = true
    try {
        const featuresArr = typeof editing.value.features === 'string'
            ? editing.value.features.split('\n').map(f => f.trim()).filter(Boolean)
            : (editing.value.features ?? [])
        const payload = { ...editing.value, features: featuresArr }
        if (editing.value.id) {
            await api.patch(`/pricing-tiers/${editing.value.id}`, payload as any)
            toast.add('Tier updated.')
        } else {
            await api.post('/pricing-tiers', payload as any)
            toast.add('Tier created.')
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
    if (!confirm('Delete this pricing tier?')) return
    try {
        await api.delete(`/pricing-tiers/${id}`)
        items.value = items.value.filter(i => i.id !== id)
        toast.add('Tier deleted.')
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
                <h1 class="text-2xl font-bold text-gray-900">Pricing</h1>
                <p class="text-sm text-gray-500 mt-1">Manage pricing packages shown on the website.</p>
            </div>
            <button @click="openNew"
                class="inline-flex items-center gap-2 bg-cyan-500 hover:bg-cyan-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors shadow-sm self-start sm:self-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Package
            </button>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <p class="text-xs text-gray-500 mb-1">Total Packages</p>
                <p class="text-2xl font-bold text-gray-900">{{ items.length }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <p class="text-xs text-gray-500 mb-1">Total Orders</p>
                <p class="text-2xl font-bold text-gray-900">{{ totalOrders }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <p class="text-xs text-gray-500 mb-1">Most Popular</p>
                <p class="text-base font-bold text-gray-900 truncate">{{ mostPopular }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <p class="text-xs text-gray-500 mb-1">Avg Price</p>
                <p class="text-2xl font-bold text-gray-900">{{ avgValue !== '–' ? `KES ${avgValue}` : '–' }}</p>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-16">
            <div class="w-8 h-8 border-4 border-cyan-500 border-t-transparent rounded-full animate-spin"></div>
        </div>

        <!-- Cards -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <div v-for="tier in items" :key="tier.id"
                :class="['relative bg-white rounded-2xl border overflow-hidden flex flex-col transition-all',
                    isPopular(tier) ? 'border-cyan-400 shadow-xl ring-2 ring-cyan-400/20' : 'border-gray-100 shadow-sm hover:shadow-md']">

                <!-- Most popular banner -->
                <div v-if="isPopular(tier)" class="bg-gradient-to-r from-cyan-500 to-cyan-400 text-white text-xs font-bold text-center py-2 tracking-widest uppercase">
                    ★ Most Popular
                </div>

                <!-- Popular toggle (top-right star) -->
                <button @click="togglePopular(tier)" :disabled="toggling === tier.id"
                    :title="isPopular(tier) ? 'Remove most popular' : 'Mark as most popular'"
                    :class="['absolute top-2 right-2 w-8 h-8 flex items-center justify-center rounded-full transition-all',
                        isPopular(tier) ? 'bg-cyan-100 text-cyan-600 hover:bg-cyan-200' : 'bg-gray-100 text-gray-400 hover:bg-yellow-50 hover:text-yellow-500',
                        toggling === tier.id ? 'opacity-50 cursor-not-allowed' : '']">
                    <svg v-if="toggling === tier.id" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    <svg v-else-if="isPopular(tier)" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                </button>

                <div class="p-5 flex flex-col flex-1 pt-8">
                    <!-- Name + price -->
                    <div class="mb-1">
                        <h3 class="text-xl font-bold text-gray-900">{{ tier.name }}</h3>
                        <div class="flex items-baseline gap-2 mt-1">
                            <span :class="['text-2xl font-extrabold', isPopular(tier) ? 'text-cyan-600' : 'text-gray-900']">{{ tier.price }}</span>
                            <span v-if="tier.old_price" class="text-sm text-gray-400 line-through">{{ tier.old_price }}</span>
                        </div>
                    </div>

                    <p class="text-sm text-gray-500 mt-2 mb-4">{{ tier.description }}</p>

                    <!-- Features -->
                    <ul class="space-y-2 mb-5 flex-1">
                        <li v-for="(feat, i) in (Array.isArray(tier.features) ? tier.features : String(tier.features ?? '').split('\n').filter(Boolean))"
                            :key="i" class="flex items-start gap-2 text-sm text-gray-700">
                            <svg class="w-4 h-4 text-cyan-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ feat }}
                        </li>
                    </ul>

                    <!-- Orders + visibility badges -->
                    <div class="flex items-center justify-between text-xs text-gray-500 bg-gray-50 rounded-lg px-3 py-2 mb-4">
                        <span><span class="font-semibold text-gray-900">{{ tier.orders_count ?? 0 }}</span> orders</span>
                        <span :class="tier.is_visible ? 'text-green-600 font-semibold' : 'text-gray-400'">
                            {{ tier.is_visible ? 'Visible' : 'Hidden' }}
                        </span>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <button @click="openEdit(tier)"
                            class="flex-1 text-xs font-semibold text-gray-600 border border-gray-200 rounded-lg py-2 hover:bg-gray-50 transition-colors">
                            Edit
                        </button>
                        <button @click="remove(tier.id)"
                            class="flex-1 text-xs font-semibold text-red-500 border border-red-100 rounded-lg py-2 hover:bg-red-50 transition-colors">
                            Delete
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="items.length === 0" class="col-span-full text-center py-16 text-gray-400 text-sm">
                No pricing packages yet. Add your first one.
            </div>
        </div>

        <!-- ── Modal ──────────────────────────────────────────────────────────── -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                    @click.self="showModal = false">
                    <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                            <h2 class="text-lg font-bold text-gray-900">{{ editing.id ? 'Edit Package' : 'Add Package' }}</h2>
                            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                        </div>
                        <form @submit.prevent="submit" class="px-6 py-5 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Package Name <span class="text-red-400">*</span></label>
                                    <input v-model="editing.name" required
                                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"
                                        placeholder="e.g. Starter, Professional, Enterprise"/>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Price <span class="text-red-400">*</span></label>
                                    <input v-model="editing.price" required
                                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"
                                        placeholder="From KES 15,000"/>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Old / Crossed Price <span class="text-gray-400 font-normal">(optional)</span></label>
                                    <input v-model="editing.old_price"
                                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"
                                        placeholder="KES 60,000"/>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Card Color</label>
                                    <select v-model="editing.color"
                                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all bg-white">
                                        <option value="gray">Gray (default)</option>
                                        <option value="cyan">Cyan (highlighted)</option>
                                        <option value="dark">Dark</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Description</label>
                                <textarea v-model="editing.description" rows="2"
                                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all resize-none"
                                    placeholder="Short description of what's included…"></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                                    Features <span class="text-gray-400 font-normal">(one per line)</span>
                                </label>
                                <textarea v-model="featuresText" rows="6"
                                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all resize-none"
                                    placeholder="Basic Logo Design&#10;100 Business Cards&#10;2 Revisions"></textarea>
                            </div>

                            <!-- Popular + Visible toggles -->
                            <div class="grid grid-cols-2 gap-4 pt-1">
                                <label class="flex items-center gap-3 cursor-pointer select-none">
                                    <input type="checkbox" v-model="editing.is_popular"
                                        class="w-4 h-4 rounded border-gray-300 text-cyan-500 focus:ring-cyan-500"/>
                                    <span class="text-sm font-semibold text-gray-700">
                                        ★ Mark as Most Popular
                                    </span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer select-none">
                                    <input type="checkbox" v-model="editing.is_visible"
                                        class="w-4 h-4 rounded border-gray-300 text-cyan-500 focus:ring-cyan-500"/>
                                    <span class="text-sm text-gray-700">Visible on site</span>
                                </label>
                            </div>

                            <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                                <button type="button" @click="showModal = false"
                                    class="px-4 py-2 text-sm font-semibold text-gray-600 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="saving"
                                    class="px-5 py-2 text-sm font-semibold bg-cyan-500 hover:bg-cyan-600 disabled:opacity-60 text-white rounded-xl transition-colors">
                                    {{ saving ? 'Saving…' : (editing.id ? 'Update Package' : 'Create Package') }}
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
