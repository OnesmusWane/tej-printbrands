<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '../api'

interface Task {
    id: number
    title: string
    description?: string
    task_column_id: number
    priority?: string
    due_date?: string
    assignee?: string
}

interface Column {
    id: number
    title: string
    slug: string
    sort_order: number
}

interface StaffUser {
    id: number
    name: string
    role: string
}

const tasks      = ref<Task[]>([])
const columns    = ref<Column[]>([])
const staff      = ref<StaffUser[]>([])
const loading    = ref(true)
const saving     = ref(false)
const showModal  = ref(false)
const editTarget = ref<Task | null>(null)

const draggedId    = ref<number | null>(null)
const dragOverCol  = ref<number | null>(null)

const emptyForm = (): Omit<Task, 'id'> => ({
    title:          '',
    description:    '',
    task_column_id: columns.value[0]?.id ?? 1,
    priority:       'medium',
    due_date:       '',
    assignee:       '',
})

const form = ref(emptyForm())

// ─── helpers ─────────────────────────────────────────────────────────────────

function tasksFor(colId: number) {
    return tasks.value.filter(t => Number(t.task_column_id) === colId)
}

const colBadge: Record<string, string> = {
    todo:         'bg-gray-100 text-gray-600',
    'in-progress':'bg-blue-100 text-blue-700',
    completed:    'bg-green-100 text-green-700',
    'on-hold':    'bg-orange-100 text-orange-700',
}

const colDrop: Record<string, string> = {
    todo:         'border-gray-300 bg-gray-50/60',
    'in-progress':'border-blue-300 bg-blue-50/60',
    completed:    'border-green-300 bg-green-50/60',
    'on-hold':    'border-orange-300 bg-orange-50/60',
}

const priorityBadge: Record<string, string> = {
    high:   'bg-red-100 text-red-700',
    medium: 'bg-orange-100 text-orange-700',
    low:    'bg-green-100 text-green-700',
}

function colClass(col: Column) {
    return colBadge[col.slug] ?? 'bg-gray-100 text-gray-600'
}

function priorityClass(p?: string) {
    return priorityBadge[p ?? 'low'] ?? 'bg-gray-100 text-gray-700'
}

function initials(name: string) {
    return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase()
}

function fmtDate(d?: string) {
    if (!d) return ''
    return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' })
}

function isDue(d?: string) {
    if (!d) return false
    return new Date(d) < new Date()
}

// ─── modal ───────────────────────────────────────────────────────────────────

function openModal(colId?: number, task?: Task) {
    editTarget.value = task ?? null
    if (task) {
        form.value = {
            title:          task.title,
            description:    task.description ?? '',
            task_column_id: task.task_column_id,
            priority:       task.priority ?? 'medium',
            due_date:       task.due_date ?? '',
            assignee:       task.assignee ?? '',
        }
    } else {
        form.value = emptyForm()
        if (colId) form.value.task_column_id = colId
    }
    showModal.value = true
}

function closeModal() {
    showModal.value = false
    editTarget.value = null
}

async function submitTask() {
    if (!form.value.title.trim()) return
    saving.value = true
    try {
        const payload = {
            title:          form.value.title,
            description:    form.value.description || null,
            task_column_id: form.value.task_column_id,
            priority:       form.value.priority,
            due_date:       form.value.due_date || null,
            assignee:       form.value.assignee || null,
        }
        if (editTarget.value) {
            const { data } = await api.patch(`/tasks/${editTarget.value.id}`, payload)
            const idx = tasks.value.findIndex(t => t.id === editTarget.value!.id)
            if (idx > -1) tasks.value[idx] = data.data ?? data
        } else {
            const { data } = await api.post('/tasks', payload)
            tasks.value.push(data.data ?? data)
        }
        closeModal()
    } finally {
        saving.value = false
    }
}

async function deleteTask(id: number) {
    if (!confirm('Delete this task?')) return
    await api.delete(`/tasks/${id}`)
    tasks.value = tasks.value.filter(t => t.id !== id)
}

// ─── status change ───────────────────────────────────────────────────────────

async function moveToColumn(task: Task, colId: number) {
    if (task.task_column_id === colId) return
    task.task_column_id = colId
    await api.patch(`/tasks/${task.id}`, { task_column_id: colId })
}

// ─── drag & drop ─────────────────────────────────────────────────────────────

function onDragStart(e: DragEvent, taskId: number) {
    draggedId.value = taskId
    if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move'
    }
}

function onDragOver(e: DragEvent, colId: number) {
    e.preventDefault()
    dragOverCol.value = colId
    if (e.dataTransfer) e.dataTransfer.dropEffect = 'move'
}

function onDragLeave() {
    dragOverCol.value = null
}

async function onDrop(colId: number) {
    if (draggedId.value !== null) {
        const task = tasks.value.find(t => t.id === draggedId.value)
        if (task) await moveToColumn(task, colId)
    }
    draggedId.value  = null
    dragOverCol.value = null
}

function onDragEnd() {
    draggedId.value  = null
    dragOverCol.value = null
}

// ─── load ────────────────────────────────────────────────────────────────────

onMounted(async () => {
    try {
        const [colsRes, tasksRes, usersRes] = await Promise.all([
            api.get('/task-columns'),
            api.get('/tasks'),
            api.get('/admin-users'),
        ])
        columns.value = (colsRes.data?.data ?? colsRes.data ?? []).sort((a: Column, b: Column) => a.sort_order - b.sort_order)
        const raw = tasksRes.data
        tasks.value  = Array.isArray(raw) ? raw : (raw.data ?? [])
        staff.value  = (Array.isArray(usersRes.data) ? usersRes.data : (usersRes.data?.data ?? []))
    } finally {
        loading.value = false
    }
})
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Tasks</h1>
                <p class="text-sm text-gray-500 mt-1">Drag cards between columns or use the status menu to move them</p>
            </div>
            <button @click="openModal()" class="inline-flex items-center gap-2 bg-cyan-500 hover:bg-cyan-600 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition-colors self-start sm:self-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Task
            </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-20">
            <div class="w-8 h-8 border-4 border-cyan-500 border-t-transparent rounded-full animate-spin"></div>
        </div>

        <!-- Kanban board -->
        <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div
                v-for="col in columns"
                :key="col.id"
                class="flex flex-col rounded-xl border-2 transition-colors min-h-[420px]"
                :class="dragOverCol === col.id ? colDrop[col.slug] ?? 'border-cyan-300 bg-cyan-50/60' : 'border-gray-200 bg-gray-50/40'"
                @dragover="onDragOver($event, col.id)"
                @dragleave="onDragLeave"
                @drop.prevent="onDrop(col.id)"
            >
                <!-- Column header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold" :class="colClass(col)">{{ col.title }}</span>
                        <span class="text-xs text-gray-400 font-medium">{{ tasksFor(col.id).length }}</span>
                    </div>
                    <button @click="openModal(col.id)" class="w-6 h-6 flex items-center justify-center rounded-md text-gray-400 hover:text-cyan-600 hover:bg-cyan-50 transition-colors" title="Add task to this column">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>

                <!-- Cards -->
                <div class="flex-1 p-3 space-y-3">
                    <!-- Empty -->
                    <div v-if="tasksFor(col.id).length === 0" class="flex flex-col items-center justify-center py-10 text-gray-300">
                        <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <p class="text-xs">No tasks</p>
                    </div>

                    <!-- Task card -->
                    <div
                        v-for="task in tasksFor(col.id)"
                        :key="task.id"
                        draggable="true"
                        @dragstart="onDragStart($event, task.id)"
                        @dragend="onDragEnd"
                        class="bg-white rounded-lg border border-gray-100 shadow-sm p-3.5 cursor-grab active:cursor-grabbing hover:shadow-md transition-shadow group select-none"
                        :class="{ 'opacity-50': draggedId === task.id }"
                    >
                        <!-- Priority + actions row -->
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="priorityClass(task.priority)">
                                {{ (task.priority ?? 'low').charAt(0).toUpperCase() + (task.priority ?? 'low').slice(1) }}
                            </span>
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="openModal(undefined, task)" class="w-6 h-6 flex items-center justify-center rounded text-gray-400 hover:text-cyan-600 hover:bg-cyan-50 transition-colors" title="Edit">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </button>
                                <button @click="deleteTask(task.id)" class="w-6 h-6 flex items-center justify-center rounded text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors" title="Delete">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Title -->
                        <p class="text-sm font-semibold text-gray-900 leading-snug mb-1">{{ task.title }}</p>

                        <!-- Description preview -->
                        <p v-if="task.description" class="text-xs text-gray-400 leading-relaxed mb-2 line-clamp-2">{{ task.description }}</p>

                        <!-- Footer: due date + assignee + move -->
                        <div class="flex items-center justify-between mt-2 gap-2">
                            <div class="flex items-center gap-1.5">
                                <span v-if="task.due_date" class="flex items-center gap-1 text-xs font-medium" :class="isDue(task.due_date) ? 'text-red-500' : 'text-gray-400'">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    {{ fmtDate(task.due_date) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <!-- Move to column -->
                                <select
                                    :value="task.task_column_id"
                                    @change="moveToColumn(task, Number(($event.target as HTMLSelectElement).value))"
                                    @click.stop
                                    class="text-xs border border-gray-200 rounded px-1.5 py-0.5 bg-white text-gray-500 focus:outline-none focus:border-cyan-400 cursor-pointer"
                                    title="Move to column"
                                >
                                    <option v-for="c in columns" :key="c.id" :value="c.id">{{ c.title }}</option>
                                </select>
                                <!-- Assignee avatar -->
                                <div v-if="task.assignee" class="w-6 h-6 rounded-full bg-cyan-100 text-cyan-700 text-[10px] font-bold flex items-center justify-center shrink-0" :title="task.assignee">
                                    {{ initials(task.assignee) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @mousedown.self="closeModal">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg flex flex-col max-h-[90vh]">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                        <h2 class="text-base font-bold text-gray-900">{{ editTarget ? 'Edit Task' : 'New Task' }}</h2>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors rounded-lg p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-6 space-y-4 overflow-y-auto flex-1">
                        <!-- Title -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-400">*</span></label>
                            <input v-model="form.title" type="text" placeholder="Task title" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20"/>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea v-model="form.description" rows="3" placeholder="Optional details…" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 resize-none"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <!-- Column -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Column</label>
                                <select v-model="form.task_column_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white outline-none focus:border-cyan-500">
                                    <option v-for="col in columns" :key="col.id" :value="col.id">{{ col.title }}</option>
                                </select>
                            </div>

                            <!-- Priority -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                                <select v-model="form.priority" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white outline-none focus:border-cyan-500">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <!-- Due date -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                                <input v-model="form.due_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-cyan-500"/>
                            </div>

                            <!-- Assignee — staff dropdown -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Assignee</label>
                                <select v-model="form.assignee" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white outline-none focus:border-cyan-500">
                                    <option value="">Unassigned</option>
                                    <option v-for="u in staff" :key="u.id" :value="u.name">{{ u.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-xl">
                        <button @click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-200 rounded-lg hover:bg-gray-100 transition-colors">Cancel</button>
                        <button
                            @click="submitTask"
                            :disabled="saving || !form.title.trim()"
                            class="inline-flex items-center gap-2 bg-cyan-500 hover:bg-cyan-600 disabled:opacity-50 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition-colors"
                        >
                            <div v-if="saving" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                            {{ editTarget ? 'Save Changes' : 'Add Task' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
