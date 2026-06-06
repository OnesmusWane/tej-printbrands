<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useResource } from '../composables/useResource'
import AppModal from '../components/AppModal.vue'

interface Product {
    id: number; slug: string; name: string; price: number; unit: string
    description: string; image_url: string; features: string[]
    is_visible: boolean; sort_order: number
}

const { items, loading, saving, load, save, remove } = useResource<Product>('products')
const showModal = ref(false)
const editing   = ref<Partial<Product>>({})

function openNew() {
    editing.value = { is_visible: true, features: [], sort_order: 0, price: 0 }
    showModal.value = true
}

function openEdit(p: Product) {
    editing.value = { ...p }
    showModal.value = true
}

async function submit() {
    const payload = {
        ...editing.value,
        price: Number(editing.value.price),
        features: typeof editing.value.features === 'string'
            ? (editing.value.features as any as string).split('\n').map(f => f.trim()).filter(Boolean)
            : editing.value.features,
    }
    const ok = await save(payload as any, editing.value.id)
    if (ok) showModal.value = false
}

onMounted(() => load())
</script>

<template>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <p class="text-sm text-slate-400">{{ items.length }} products</p>
            <button @click="openNew" class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-bold text-white hover:bg-cyan-500">+ New Product</button>
        </div>

        <div v-if="loading" class="text-slate-400">Loading…</div>

        <div v-else class="overflow-x-auto rounded-xl">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-700 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                        <th class="pb-3 pr-4">Product</th>
                        <th class="pb-3 pr-4">Price</th>
                        <th class="pb-3 pr-4">Unit</th>
                        <th class="pb-3 pr-4">Status</th>
                        <th class="pb-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in items" :key="p.id" class="border-b border-slate-800 hover:bg-slate-800/40">
                        <td class="py-3 pr-4">
                            <div class="flex items-center gap-3">
                                <img v-if="p.image_url" :src="p.image_url" :alt="p.name" class="h-10 w-10 rounded-lg object-cover bg-slate-700">
                                <span class="font-semibold text-white">{{ p.name }}</span>
                            </div>
                        </td>
                        <td class="py-3 pr-4 font-bold text-cyan-400">KES {{ Number(p.price).toLocaleString() }}</td>
                        <td class="py-3 pr-4 text-slate-400">{{ p.unit }}</td>
                        <td class="py-3 pr-4">
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase"
                                :class="p.is_visible ? 'bg-emerald-600/20 text-emerald-400' : 'bg-slate-700 text-slate-400'">
                                {{ p.is_visible ? 'visible' : 'hidden' }}
                            </span>
                        </td>
                        <td class="py-3 text-right">
                            <button @click="openEdit(p)" class="mr-2 text-xs text-cyan-400 hover:text-cyan-300">Edit</button>
                            <button @click="remove(p.id)" class="text-xs text-red-400 hover:text-red-300">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <AppModal :open="showModal" :title="editing.id ? 'Edit Product' : 'New Product'" @close="showModal = false">
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1">Name</label>
                        <input v-model="editing.name" required class="w-full rounded-lg bg-slate-700 border border-slate-600 px-3 py-2 text-sm text-white outline-none focus:border-cyan-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1">Price (KES)</label>
                        <input type="number" v-model.number="editing.price" required min="0" class="w-full rounded-lg bg-slate-700 border border-slate-600 px-3 py-2 text-sm text-white outline-none focus:border-cyan-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">Unit / pricing note</label>
                    <input v-model="editing.unit" placeholder="per 100 cards" class="w-full rounded-lg bg-slate-700 border border-slate-600 px-3 py-2 text-sm text-white outline-none focus:border-cyan-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">Image URL</label>
                    <input v-model="editing.image_url" class="w-full rounded-lg bg-slate-700 border border-slate-600 px-3 py-2 text-sm text-white outline-none focus:border-cyan-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">Description</label>
                    <textarea v-model="editing.description" rows="3" class="w-full rounded-lg bg-slate-700 border border-slate-600 px-3 py-2 text-sm text-white outline-none focus:border-cyan-500"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">Features (one per line)</label>
                    <textarea
                        :value="Array.isArray(editing.features) ? editing.features.join('\n') : editing.features"
                        @input="e => editing.features = (e.target as HTMLTextAreaElement).value as any"
                        rows="4" class="w-full rounded-lg bg-slate-700 border border-slate-600 px-3 py-2 text-sm text-white outline-none focus:border-cyan-500"
                    ></textarea>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-300">
                    <input type="checkbox" v-model="editing.is_visible"> Visible on site
                </label>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showModal = false" class="rounded-lg border border-slate-600 px-4 py-2 text-sm font-bold text-slate-300 hover:bg-slate-700">Cancel</button>
                    <button type="submit" :disabled="saving" class="rounded-lg bg-cyan-600 px-5 py-2 text-sm font-bold text-white hover:bg-cyan-500 disabled:opacity-50">
                        {{ saving ? 'Saving…' : 'Save' }}
                    </button>
                </div>
            </form>
        </AppModal>
    </div>
</template>
