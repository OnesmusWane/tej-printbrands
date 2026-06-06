<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useResource } from '../composables/useResource'
import AppModal from '../components/AppModal.vue'

interface Message {
    id: number; name: string; email: string; phone: string
    subject: string; message: string; status: string; is_starred: boolean
    created_at: string
}

const { items, loading, saving, load, save, remove } = useResource<Message>('contact-messages')
const filter   = ref('all')
const viewing  = ref<Message | null>(null)

const filtered = computed(() =>
    filter.value === 'all' ? items.value : items.value.filter(m => m.status === filter.value)
)

async function markStatus(m: Message, status: string) {
    await save({ status }, m.id)
}

async function toggleStar(m: Message) {
    await save({ is_starred: !m.is_starred }, m.id)
}

function view(m: Message) {
    viewing.value = m
    if (m.status === 'new') markStatus(m, 'read')
}

onMounted(() => load())
</script>

<template>
    <div>
        <div class="mb-6 flex items-center gap-3">
            <button v-for="s in ['all', 'new', 'read', 'replied', 'archived']" :key="s"
                @click="filter = s"
                class="rounded-full px-3 py-1 text-xs font-bold capitalize transition"
                :class="filter === s ? 'bg-cyan-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600'">
                {{ s }}
            </button>
        </div>

        <div v-if="loading" class="text-slate-400">Loading…</div>

        <div v-else class="space-y-2">
            <article v-for="m in filtered" :key="m.id"
                class="flex cursor-pointer items-start gap-4 rounded-xl bg-slate-800 p-4 hover:bg-slate-700/60"
                :class="{ 'border-l-2 border-cyan-500': m.status === 'new' }"
                @click="view(m)">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-cyan-600/20 font-bold text-cyan-400">
                    {{ m.name?.charAt(0) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="font-semibold text-white">{{ m.name }}</p>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase"
                            :class="m.status === 'new' ? 'bg-cyan-600/20 text-cyan-400' : 'bg-slate-700 text-slate-400'">
                            {{ m.status }}
                        </span>
                        <span v-if="m.is_starred" class="text-amber-400 text-sm">★</span>
                    </div>
                    <p class="text-sm text-slate-400 truncate">{{ m.subject }}</p>
                    <p class="mt-0.5 text-xs text-slate-500 truncate">{{ m.message }}</p>
                </div>
                <div class="shrink-0 text-right">
                    <p class="text-xs text-slate-500">{{ new Date(m.created_at).toLocaleDateString() }}</p>
                    <button @click.stop="remove(m.id)" class="mt-2 text-xs text-red-400 hover:text-red-300">Del</button>
                </div>
            </article>
        </div>

        <!-- Message viewer -->
        <AppModal :open="!!viewing" :title="viewing?.subject ?? 'Message'" @close="viewing = null">
            <div v-if="viewing" class="space-y-4">
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div><span class="text-slate-400">From:</span> <span class="text-white font-semibold">{{ viewing.name }}</span></div>
                    <div><span class="text-slate-400">Email:</span> <span class="text-white">{{ viewing.email }}</span></div>
                    <div><span class="text-slate-400">Phone:</span> <span class="text-white">{{ viewing.phone || '—' }}</span></div>
                    <div><span class="text-slate-400">Status:</span> <span class="text-white">{{ viewing.status }}</span></div>
                </div>
                <div class="rounded-xl bg-slate-700 p-4 text-sm leading-relaxed text-slate-200 whitespace-pre-wrap">{{ viewing.message }}</div>
                <div class="flex flex-wrap gap-2 pt-2">
                    <button @click="markStatus(viewing, 'replied'); viewing = null" class="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-600">Mark Replied</button>
                    <button @click="markStatus(viewing, 'archived'); viewing = null" class="rounded-lg bg-slate-600 px-4 py-2 text-xs font-bold text-white hover:bg-slate-500">Archive</button>
                    <button @click="toggleStar(viewing)" class="rounded-lg border border-amber-600 px-4 py-2 text-xs font-bold text-amber-400 hover:bg-amber-900/30">
                        {{ viewing.is_starred ? 'Unstar' : 'Star' }}
                    </button>
                    <a :href="`mailto:${viewing.email}`" class="rounded-lg bg-cyan-700 px-4 py-2 text-xs font-bold text-white hover:bg-cyan-600">Reply via Email</a>
                </div>
            </div>
        </AppModal>
    </div>
</template>
