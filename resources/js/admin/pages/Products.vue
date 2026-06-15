<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useResource } from '../composables/useResource'
import ImageUpload from '../components/ImageUpload.vue'

interface Product {
    id: number; slug: string; name: string; price: number; unit: string
    description: string; image_url: string; images: string[]; features: string[]
    is_visible: boolean; sort_order: number; stock_quantity: number | null
}

const { items, loading, saving, load, save, remove } = useResource<Product>('products')
const showModal  = ref(false)
const editing    = ref<Partial<Product> & { images: string[] }>({ images: [] })
const trackStock = ref(false)

function openNew() {
    editing.value  = { is_visible: true, features: [], sort_order: 0, price: 0, stock_quantity: null, images: [] }
    trackStock.value = false
    showModal.value = true
}

function openEdit(p: Product) {
    const imgs: string[] = Array.isArray(p.images) && p.images.filter(Boolean).length
        ? p.images.filter(Boolean)
        : (p.image_url ? [p.image_url] : [])
    editing.value    = { ...p, images: imgs }
    trackStock.value = p.stock_quantity !== null && p.stock_quantity !== undefined
    showModal.value  = true
}

function addImageSlot() {
    editing.value.images.push('')
}

function removeImage(idx: number) {
    editing.value.images.splice(idx, 1)
}

async function submit() {
    const cleanImages = editing.value.images.filter(Boolean)
    const payload = {
        ...editing.value,
        images:         cleanImages,
        image_url:      cleanImages[0] ?? '',
        price:          Number(editing.value.price),
        stock_quantity: trackStock.value ? (Number(editing.value.stock_quantity) || 0) : null,
        features: typeof editing.value.features === 'string'
            ? (editing.value.features as any as string).split('\n').map((f: string) => f.trim()).filter(Boolean)
            : editing.value.features,
    }
    const ok = await save(payload as any, editing.value.id)
    if (ok) showModal.value = false
}

function stockLabel(p: Product) {
    if (p.stock_quantity === null || p.stock_quantity === undefined) return '∞'
    return p.stock_quantity
}

function stockClass(p: Product) {
    if (p.stock_quantity === null || p.stock_quantity === undefined) return 'text-gray-400'
    if (p.stock_quantity === 0) return 'text-red-600 font-bold'
    if (p.stock_quantity <= 5) return 'text-orange-600 font-bold'
    return 'text-green-600'
}

function coverImage(p: Product): string {
    const imgs = Array.isArray(p.images) ? p.images.filter(Boolean) : []
    return imgs[0] ?? p.image_url ?? ''
}

onMounted(() => load())
</script>

<template>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <p class="text-sm text-gray-500">{{ items.length }} products</p>
            <button @click="openNew"
                    class="rounded-lg px-4 py-2 text-sm font-bold text-white"
                    style="background:#00bcd4">
                + New Product
            </button>
        </div>

        <div v-if="loading" class="text-gray-400">Loading…</div>

        <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-bold uppercase tracking-wider text-gray-500">
                        <th class="px-4 py-3">Product</th>
                        <th class="px-4 py-3">Price</th>
                        <th class="px-4 py-3">Unit</th>
                        <th class="px-4 py-3">Stock</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="p in items" :key="p.id" class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="relative shrink-0">
                                    <img v-if="coverImage(p)" :src="coverImage(p)" :alt="p.name"
                                         class="h-10 w-10 rounded-lg object-cover bg-gray-100">
                                    <div v-else class="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <span v-if="(p.images?.filter(Boolean).length ?? 0) > 1"
                                          class="absolute -bottom-1 -right-1 text-white text-[9px] font-bold rounded-full w-4 h-4 flex items-center justify-center"
                                          style="background:#00bcd4">
                                        {{ p.images.filter(Boolean).length }}
                                    </span>
                                </div>
                                <span class="font-semibold text-gray-900">{{ p.name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-bold" style="color:#00bcd4">KES {{ Number(p.price).toLocaleString() }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ p.unit }}</td>
                        <td class="px-4 py-3">
                            <span :class="['text-sm font-semibold', stockClass(p)]">{{ stockLabel(p) }}</span>
                            <span v-if="p.stock_quantity !== null && p.stock_quantity !== undefined" class="ml-1 text-xs text-gray-400">units</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase"
                                :class="p.is_visible
                                    ? 'bg-green-100 text-green-700 border-green-200'
                                    : 'bg-gray-100 text-gray-500 border-gray-200'">
                                {{ p.is_visible ? 'visible' : 'hidden' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button @click="openEdit(p)" class="mr-2 text-xs font-medium text-gray-500 hover:text-gray-700">Edit</button>
                            <button @click="remove(p.id)" class="text-xs font-medium text-red-500 hover:text-red-700">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Product Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showModal"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
                     @click.self="showModal = false">
                    <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl border border-gray-100">
                        <!-- Modal header -->
                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                            <h2 class="text-lg font-extrabold text-gray-900">
                                {{ editing.id ? 'Edit Product' : 'New Product' }}
                            </h2>
                            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">✕</button>
                        </div>

                        <!-- Modal body -->
                        <form @submit.prevent="submit" class="px-6 py-5 space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                    <input v-model="editing.name" required
                                           class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Price (KES)</label>
                                    <input type="number" v-model.number="editing.price" required min="0"
                                           class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Unit / pricing note</label>
                                <input v-model="editing.unit" placeholder="per 100 cards"
                                       class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all">
                            </div>

                            <!-- Multi-image upload -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-sm font-medium text-gray-700">
                                        Product Images
                                        <span class="ml-1 text-gray-400 font-normal text-xs">(first = cover)</span>
                                    </label>
                                    <button type="button" @click="addImageSlot"
                                            class="text-xs font-medium flex items-center gap-1"
                                            style="color:#00bcd4">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        Add Image
                                    </button>
                                </div>

                                <div v-if="editing.images.length === 0"
                                     class="rounded-xl border-2 border-dashed border-gray-200 py-8 text-center">
                                    <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <p class="text-xs text-gray-400">No images yet.</p>
                                    <button type="button" @click="addImageSlot"
                                            class="mt-2 text-xs font-medium" style="color:#00bcd4">
                                        + Add first image
                                    </button>
                                </div>

                                <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                    <div v-for="(img, idx) in editing.images" :key="idx" class="relative">
                                        <div class="absolute -top-1.5 left-1 z-10">
                                            <span class="rounded-full bg-white border border-gray-200 px-1.5 py-0.5 text-[9px] font-bold text-gray-500 leading-none shadow-sm">
                                                {{ idx === 0 ? 'Cover' : `#${idx + 1}` }}
                                            </span>
                                        </div>
                                        <ImageUpload
                                            :modelValue="img"
                                            @update:modelValue="editing.images[idx] = $event"
                                            height="h-28"
                                        />
                                        <button type="button" @click="removeImage(idx)"
                                                class="absolute top-1 right-1 z-10 w-5 h-5 rounded-full bg-red-500 text-white text-xs flex items-center justify-center hover:bg-red-600 leading-none">
                                            ×
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                <textarea v-model="editing.description" rows="3"
                                          class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Features (one per line)</label>
                                <textarea
                                    :value="Array.isArray(editing.features) ? editing.features.join('\n') : editing.features"
                                    @input="e => editing.features = (e.target as HTMLTextAreaElement).value as any"
                                    rows="4"
                                    class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"
                                ></textarea>
                            </div>

                            <!-- Stock -->
                            <div class="rounded-xl border border-gray-200 p-3 space-y-3">
                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                    <input type="checkbox" v-model="trackStock" class="accent-cyan-500">
                                    <span class="font-semibold">Track stock / inventory</span>
                                </label>
                                <div v-if="trackStock">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Stock Quantity</label>
                                    <input type="number" v-model.number="editing.stock_quantity" min="0" placeholder="0"
                                           class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all">
                                    <p class="text-xs text-gray-400 mt-1">0 = out of stock. Leave tracking off for unlimited items.</p>
                                </div>
                                <p v-else class="text-xs text-gray-400">Unlimited stock (no tracking).</p>
                            </div>

                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" v-model="editing.is_visible" class="accent-cyan-500">
                                Visible on site
                            </label>
                        </form>

                        <!-- Modal footer -->
                        <div class="flex items-center justify-end gap-3 bg-gray-50 rounded-b-2xl border-t border-gray-100 px-6 py-4">
                            <button type="button" @click="showModal = false"
                                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100 transition-colors">
                                Cancel
                            </button>
                            <button @click="submit" :disabled="saving"
                                    class="rounded-lg px-5 py-2 text-sm font-bold text-white disabled:opacity-50 transition-colors"
                                    style="background:#1f2937">
                                {{ saving ? 'Saving…' : 'Save Product' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.96); }
</style>
