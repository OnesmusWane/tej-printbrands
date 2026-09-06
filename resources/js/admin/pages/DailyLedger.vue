<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useResource } from '../composables/useResource'

interface LedgerEntry {
    id: number
    entry_date: string
    type: 'sale' | 'expense'
    category: string | null
    description: string | null
    amount: number
    created_at: string
}

const { items, loading, saving, load, save, remove } = useResource<LedgerEntry>('daily-ledger-entries')

function todayStr(): string {
    const d = new Date()
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

function daysAgoStr(n: number): string {
    const d = new Date()
    d.setDate(d.getDate() - n)
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

const selectedDate = ref(todayStr())
const rangeMode    = ref(false)
const rangeStart   = ref(daysAgoStr(6))
const rangeEnd     = ref(todayStr())
const showModal    = ref(false)
const editing      = ref<Partial<LedgerEntry>>({})

const filteredEntries = computed(() => {
    const list = rangeMode.value
        ? items.value.filter(e => e.entry_date >= rangeStart.value && e.entry_date <= rangeEnd.value)
        : items.value.filter(e => e.entry_date === selectedDate.value)

    return list.slice().sort((a, b) => {
        if (a.entry_date !== b.entry_date) return b.entry_date.localeCompare(a.entry_date)
        return b.created_at.localeCompare(a.created_at)
    })
})

const daySales    = computed(() => filteredEntries.value.filter(e => e.type === 'sale').reduce((sum, e) => sum + Number(e.amount), 0))
const dayExpenses = computed(() => filteredEntries.value.filter(e => e.type === 'expense').reduce((sum, e) => sum + Number(e.amount), 0))
const dayNet      = computed(() => daySales.value - dayExpenses.value)

interface DayGroup {
    date: string
    entries: LedgerEntry[]
    sales: number
    expenses: number
    net: number
}

// filteredEntries is already sorted newest-day-first / newest-time-first, and Map
// preserves insertion order, so groups come out in that same order for free.
const groupedByDay = computed<DayGroup[]>(() => {
    const map = new Map<string, LedgerEntry[]>()
    for (const e of filteredEntries.value) {
        const bucket = map.get(e.entry_date)
        if (bucket) bucket.push(e)
        else map.set(e.entry_date, [e])
    }
    return Array.from(map.entries()).map(([date, entries]) => {
        const sales = entries.filter(e => e.type === 'sale').reduce((sum, e) => sum + Number(e.amount), 0)
        const expenses = entries.filter(e => e.type === 'expense').reduce((sum, e) => sum + Number(e.amount), 0)
        return { date, entries, sales, expenses, net: sales - expenses }
    })
})

const expandedDates = ref<Set<string>>(new Set())
function toggleDay(date: string) {
    const next = new Set(expandedDates.value)
    if (next.has(date)) next.delete(date)
    else next.add(date)
    expandedDates.value = next
}

function isToday(): boolean {
    return selectedDate.value === todayStr()
}

function fmtDateShort(d: string): string {
    return new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

const periodLabel = computed(() => {
    if (!rangeMode.value) return isToday() ? '(Today)' : `(${fmtDateShort(selectedDate.value)})`
    if (rangeStart.value === rangeEnd.value) return `(${fmtDateShort(rangeStart.value)})`
    return `(${fmtDateShort(rangeStart.value)} – ${fmtDateShort(rangeEnd.value)})`
})

function openNew(type: 'sale' | 'expense') {
    const defaultDate = rangeMode.value ? rangeEnd.value : selectedDate.value
    editing.value = { entry_date: defaultDate, type, category: '', description: '', amount: 0 }
    showModal.value = true
}

function openEdit(e: LedgerEntry) {
    editing.value = { ...e }
    showModal.value = true
}

async function submit() {
    const payload = { ...editing.value, amount: Number(editing.value.amount) || 0 }
    const ok = await save(payload as any, editing.value.id)
    if (ok) {
        showModal.value = false
        // save() reloads with the composable's default page size; re-fetch the
        // fuller window this page relies on so older days don't drop out of view.
        await load({ per_page: 1000 })
    }
}

function fmt(n: number): string {
    return 'Ksh ' + Number(n).toLocaleString()
}

function fmtTime(iso: string): string {
    return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

onMounted(() => load({ per_page: 1000 }))
</script>

<template>
    <div>
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-dark">Daily Sales &amp; Expenses</h1>
                <p class="text-sm text-gray-500 mt-1">Log cash sales and expenses day by day.</p>
            </div>
            <div class="flex items-center gap-2">
                <button @click="openNew('sale')"
                        class="rounded-lg px-4 py-2 text-sm font-bold text-white bg-green-600 hover:bg-green-700 transition-colors">
                    + Log Sale
                </button>
                <button @click="openNew('expense')"
                        class="rounded-lg px-4 py-2 text-sm font-bold text-white bg-red-500 hover:bg-red-600 transition-colors">
                    + Log Expense
                </button>
            </div>
        </div>

        <!-- Date filter -->
        <div class="mb-6 flex flex-wrap items-center gap-3">
            <div class="flex rounded-lg border border-gray-300 overflow-hidden">
                <button type="button" @click="rangeMode = false"
                        :class="['px-3 py-2 text-xs font-semibold transition-colors', !rangeMode ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 hover:bg-gray-50']">
                    Single Day
                </button>
                <button type="button" @click="rangeMode = true"
                        :class="['px-3 py-2 text-xs font-semibold transition-colors', rangeMode ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 hover:bg-gray-50']">
                    Date Range
                </button>
            </div>

            <template v-if="!rangeMode">
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Viewing</label>
                <input type="date" v-model="selectedDate"
                       class="rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20">
                <button v-if="!isToday()" @click="selectedDate = todayStr()"
                        class="text-xs font-semibold text-cyan-600 hover:text-cyan-700">
                    Jump to today
                </button>
            </template>

            <template v-else>
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">From</label>
                <input type="date" v-model="rangeStart" :max="rangeEnd"
                       class="rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20">
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">To</label>
                <input type="date" v-model="rangeEnd" :min="rangeStart" :max="todayStr()"
                       class="rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20">
            </template>
        </div>

        <!-- Summary cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Sales {{ periodLabel }}</p>
                <p class="text-2xl font-bold text-green-600">{{ fmt(daySales) }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Expenses {{ periodLabel }}</p>
                <p class="text-2xl font-bold text-red-500">{{ fmt(dayExpenses) }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Net</p>
                <p class="text-2xl font-bold" :class="dayNet >= 0 ? 'text-gray-900' : 'text-red-500'">{{ fmt(dayNet) }}</p>
            </div>
        </div>

        <div v-if="loading" class="text-gray-400">Loading…</div>

        <!-- Date Range: cumulative-per-day rows, expandable to individual entries -->
        <div v-if="!loading && rangeMode" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-bold uppercase tracking-wider text-gray-500">
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Entries</th>
                        <th class="px-4 py-3">Sales</th>
                        <th class="px-4 py-3">Expenses</th>
                        <th class="px-4 py-3">Net</th>
                        <th class="px-4 py-3 w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template v-for="day in groupedByDay" :key="day.date">
                        <tr class="hover:bg-gray-50 transition-colors cursor-pointer select-none" @click="toggleDay(day.date)">
                            <td class="px-4 py-3 font-semibold text-gray-900 whitespace-nowrap">{{ fmtDateShort(day.date) }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ day.entries.length }}</td>
                            <td class="px-4 py-3 font-bold text-green-600">{{ fmt(day.sales) }}</td>
                            <td class="px-4 py-3 font-bold text-red-500">{{ fmt(day.expenses) }}</td>
                            <td class="px-4 py-3 font-bold" :class="day.net >= 0 ? 'text-gray-900' : 'text-red-500'">{{ fmt(day.net) }}</td>
                            <td class="px-4 py-3 text-right text-gray-400">
                                <svg :class="['w-4 h-4 transition-transform inline-block', expandedDates.has(day.date) ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </td>
                        </tr>
                        <tr v-if="expandedDates.has(day.date)">
                            <td colspan="6" class="p-0 bg-gray-50/60">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-[10px] font-bold uppercase tracking-wider text-gray-400">
                                            <th class="px-4 py-2 pl-12">Time</th>
                                            <th class="px-4 py-2">Type</th>
                                            <th class="px-4 py-2">Category</th>
                                            <th class="px-4 py-2">Description</th>
                                            <th class="px-4 py-2">Amount</th>
                                            <th class="px-4 py-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="e in day.entries" :key="e.id" class="hover:bg-white transition-colors">
                                            <td class="px-4 py-2.5 pl-12 text-gray-500 whitespace-nowrap">{{ fmtTime(e.created_at) }}</td>
                                            <td class="px-4 py-2.5">
                                                <span :class="['rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase', e.type === 'sale' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600']">
                                                    {{ e.type }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2.5 text-gray-700">{{ e.category || '-' }}</td>
                                            <td class="px-4 py-2.5 text-gray-500">{{ e.description || '-' }}</td>
                                            <td class="px-4 py-2.5 font-bold" :class="e.type === 'sale' ? 'text-green-600' : 'text-red-500'">
                                                {{ e.type === 'sale' ? '+' : '-' }}{{ fmt(e.amount) }}
                                            </td>
                                            <td class="px-4 py-2.5 text-right">
                                                <button @click="openEdit(e)" class="mr-2 text-xs font-medium text-gray-500 hover:text-gray-700">Edit</button>
                                                <button @click="remove(e.id)" class="text-xs font-medium text-red-500 hover:text-red-700">Delete</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </template>
                    <tr v-if="groupedByDay.length === 0">
                        <td colspan="6" class="px-4 py-10 text-center text-gray-400">No entries logged in this date range yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Single Day: flat breakdown of individual entries -->
        <div v-else-if="!loading" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-bold uppercase tracking-wider text-gray-500">
                        <th class="px-4 py-3">Time</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3">Amount</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="e in filteredEntries" :key="e.id" class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ fmtTime(e.created_at) }}</td>
                        <td class="px-4 py-3">
                            <span :class="['rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase', e.type === 'sale' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600']">
                                {{ e.type }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ e.category || '-' }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ e.description || '-' }}</td>
                        <td class="px-4 py-3 font-bold" :class="e.type === 'sale' ? 'text-green-600' : 'text-red-500'">
                            {{ e.type === 'sale' ? '+' : '-' }}{{ fmt(e.amount) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button @click="openEdit(e)" class="mr-2 text-xs font-medium text-gray-500 hover:text-gray-700">Edit</button>
                            <button @click="remove(e.id)" class="text-xs font-medium text-red-500 hover:text-red-700">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="filteredEntries.length === 0">
                        <td colspan="6" class="px-4 py-10 text-center text-gray-400">No entries logged for this day yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Entry Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showModal"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
                     @click.self="showModal = false">
                    <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl border border-gray-100">
                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                            <h2 class="text-lg font-extrabold text-gray-900">
                                {{ editing.id ? 'Edit Entry' : (editing.type === 'sale' ? 'Log Sale' : 'Log Expense') }}
                            </h2>
                            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">✕</button>
                        </div>

                        <form @submit.prevent="submit" class="px-6 py-5 space-y-4">
                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" @click="editing.type = 'sale'"
                                        :class="['rounded-lg px-3 py-2.5 text-sm font-bold border transition-colors', editing.type === 'sale' ? 'bg-green-600 border-green-600 text-white' : 'border-gray-300 text-gray-600']">
                                    Sale
                                </button>
                                <button type="button" @click="editing.type = 'expense'"
                                        :class="['rounded-lg px-3 py-2.5 text-sm font-bold border transition-colors', editing.type === 'expense' ? 'bg-red-500 border-red-500 text-white' : 'border-gray-300 text-gray-600']">
                                    Expense
                                </button>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                                <input type="date" v-model="editing.entry_date" required
                                       class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Amount (Ksh)</label>
                                <input type="number" v-model.number="editing.amount" required min="0"
                                       class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                <input v-model="editing.category" list="ledger-category-suggestions" placeholder="e.g. Walk-in Sale, Fuel, Rent"
                                       class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all">
                                <datalist id="ledger-category-suggestions">
                                    <option value="Cash Sale" /><option value="Walk-in Sale" /><option value="M-Pesa Sale" />
                                    <option value="Fuel" /><option value="Rent" /><option value="Electricity" /><option value="Water" />
                                    <option value="Supplies" /><option value="Wages" /><option value="Transport" /><option value="Maintenance" /><option value="Other" />
                                </datalist>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes <span class="text-gray-400 font-normal">(optional)</span></label>
                                <textarea v-model="editing.description" rows="2"
                                          class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"></textarea>
                            </div>
                        </form>

                        <div class="flex items-center justify-end gap-3 bg-gray-50 rounded-b-2xl border-t border-gray-100 px-6 py-4">
                            <button type="button" @click="showModal = false"
                                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100 transition-colors">
                                Cancel
                            </button>
                            <button @click="submit" :disabled="saving"
                                    class="rounded-lg px-5 py-2 text-sm font-bold text-white disabled:opacity-50 transition-colors"
                                    style="background:#1f2937">
                                {{ saving ? 'Saving…' : 'Save Entry' }}
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
