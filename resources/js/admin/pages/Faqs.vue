<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useResource } from '../composables/useResource'
import AppModal from '../components/AppModal.vue'

interface Faq {
    id: number; page_slug: string; question: string; answer: string
    is_visible: boolean; sort_order: number
}

const { items, loading, saving, load, save, remove } = useResource<Faq>('faqs')
const showModal = ref(false)
const editing   = ref<Partial<Faq>>({})

function openNew() {
    editing.value = { is_visible: true, sort_order: 0, page_slug: 'contact' }
    showModal.value = true
}

function openEdit(f: Faq) {
    editing.value = { ...f }
    showModal.value = true
}

async function submit() {
    const ok = await save(editing.value as any, editing.value.id)
    if (ok) showModal.value = false
}

onMounted(() => load())
</script>

<template>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <p class="text-sm text-slate-400">{{ items.length }} FAQs</p>
            <button @click="openNew" class="rounded-lg bg-cyan-600 px-4 py-2 text-sm font-bold text-white hover:bg-cyan-500">+ Add FAQ</button>
        </div>

        <div v-if="loading" class="text-slate-400">Loading…</div>

        <div v-else class="space-y-3">
            <article v-for="f in items" :key="f.id" class="rounded-xl bg-slate-800 p-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <p class="font-semibold text-white">{{ f.question }}</p>
                        <p class="mt-1 text-sm text-slate-400 line-clamp-2">{{ f.answer }}</p>
                        <span class="mt-2 inline-block text-[10px] font-bold uppercase text-slate-500">page: {{ f.page_slug }}</span>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <button @click="openEdit(f)" class="text-xs text-cyan-400 hover:text-cyan-300">Edit</button>
                        <button @click="remove(f.id)" class="text-xs text-red-400 hover:text-red-300">Del</button>
                    </div>
                </div>
            </article>
        </div>

        <AppModal :open="showModal" :title="editing.id ? 'Edit FAQ' : 'New FAQ'" @close="showModal = false">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">Page</label>
                    <select v-model="editing.page_slug" class="w-full rounded-lg bg-slate-700 border border-slate-600 px-3 py-2 text-sm text-white outline-none focus:border-cyan-500">
                        <option value="contact">Contact page</option>
                        <option value="services">Services page</option>
                        <option value="home">Home page</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">Question</label>
                    <input v-model="editing.question" required class="w-full rounded-lg bg-slate-700 border border-slate-600 px-3 py-2 text-sm text-white outline-none focus:border-cyan-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-1">Answer</label>
                    <textarea v-model="editing.answer" rows="4" required class="w-full rounded-lg bg-slate-700 border border-slate-600 px-3 py-2 text-sm text-white outline-none focus:border-cyan-500"></textarea>
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
