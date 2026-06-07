<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api from '../api'

interface Task {
    id: number
    title: string
    description?: string
    task_column_id: number
    project_id?: number | null
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

interface Project {
    id: number
    name: string
    client?: string | null
    status: string
}

const tasks      = ref<Task[]>([])
const columns    = ref<Column[]>([])
const staff      = ref<StaffUser[]>([])
const projects   = ref<Project[]>([])
const loading    = ref(true)
const saving     = ref(false)
const showModal  = ref(false)
const editTarget = ref<Task | null>(null)

// ── filters ──────────────────────────────────────────────────────────────────
const fProject  = ref<number | ''>('')
const fAssignee = ref('')
const fStatus   = ref<number | ''>('')   // column id
const fDateFrom = ref('')
const fDateTo   = ref('')

const activeFilterCount = computed(() =>
    [fProject.value !== '', fAssignee.value !== '', fStatus.value !== '', fDateFrom.value !== '', fDateTo.value !== '']
        .filter(Boolean).length
)

function clearFilters() {
    fProject.value  = ''
    fAssignee.value = ''
    fStatus.value   = ''
    fDateFrom.value = ''
    fDateTo.value   = ''
}

const filteredTasks = computed(() => {
    return tasks.value.filter(t => {
        if (fProject.value  !== '' && t.project_id !== fProject.value)  return false
        if (fAssignee.value !== '' && t.assignee   !== fAssignee.value)  return false
        if (fStatus.value   !== '' && t.task_column_id !== fStatus.value) return false
        if (fDateFrom.value && t.due_date && t.due_date < fDateFrom.value) return false
        if (fDateTo.value   && t.due_date && t.due_date > fDateTo.value)   return false
        return true
    })
})

// unique assignees from loaded tasks
const assigneeOptions = computed(() =>
    [...new Set(tasks.value.map(t => t.assignee).filter(Boolean))] as string[]
)

const draggedId    = ref<number | null>(null)
const dragOverCol  = ref<number | null>(null)

const emptyForm = (): Omit<Task, 'id'> => ({
    title:          '',
    description:    '',
    task_column_id: columns.value[0]?.id ?? 1,
    project_id:     null,
    priority:       'medium',
    due_date:       '',
    assignee:       '',
})

const form = ref(emptyForm())

// ── helpers ───────────────────────────────────────────────────────────────────

function tasksFor(colId: number) {
    return filteredTasks.value.filter(t => Number(t.task_column_id) === colId)
}

const colBadge: Record<string, string> = {
    todo:          'bg-gray-100 text-gray-600',
    'in-progress': 'bg-blue-100 text-blue-700',
    completed:     'bg-green-100 text-green-700',
    'on-hold':     'bg-orange-100 text-orange-700',
}

const colDrop: Record<string, string> = {
    todo:          'border-gray-300 bg-gray-50/60',
    'in-progress': 'border-blue-300 bg-blue-50/60',
    completed:     'border-green-300 bg-green-50/60',
    'on-hold':     'border-orange-300 bg-orange-50/60',
}

const priorityBadge: Record<string, string> = {
    high:   'bg-red-100 text-red-700',
    medium: 'bg-orange-100 text-orange-700',
    low:    'bg-green-100 text-green-700',
}

function colClass(col: Column) { return colBadge[col.slug] ?? 'bg-gray-100 text-gray-600' }
function priorityClass(p?: string) { return priorityBadge[p ?? 'low'] ?? 'bg-gray-100 text-gray-700' }
function initials(name: string) { return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase() }

function fmtDate(d?: string | null) {
    if (!d) return ''
    return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' })
}

type DueStatus = 'overdue' | 'due-today' | 'due-soon' | null

function dueStatus(d?: string | null): DueStatus {
    if (!d) return null
    const today = new Date(); today.setHours(0, 0, 0, 0)
    const due   = new Date(d); due.setHours(0, 0, 0, 0)
    const diff  = Math.round((due.getTime() - today.getTime()) / 86400000)
    if (diff < 0)   return 'overdue'
    if (diff === 0) return 'due-today'
    if (diff === 1) return 'due-soon'
    return null
}

function cardBorderCls(d?: string | null): string {
    const s = dueStatus(d)
    if (s === 'overdue')   return 'border-l-[3px] border-l-red-400'
    if (s === 'due-today') return 'border-l-[3px] border-l-amber-500'
    if (s === 'due-soon')  return 'border-l-[3px] border-l-amber-300'
    return ''
}

function projectName(id?: number | null) {
    if (!id) return null
    return projects.value.find(p => p.id === id)?.name ?? null
}

// ── modal ──────────────────────────────────────────────────────────────────────

function openModal(colId?: number, task?: Task) {
    editTarget.value = task ?? null
    if (task) {
        form.value = {
            title:          task.title,
            description:    task.description ?? '',
            task_column_id: task.task_column_id,
            project_id:     task.project_id ?? null,
            priority:       task.priority ?? 'medium',
            due_date:       task.due_date ?? '',
            assignee:       task.assignee ?? '',
        }
    } else {
        form.value = emptyForm()
        if (colId) form.value.task_column_id = colId
        if (fProject.value !== '') form.value.project_id = fProject.value
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
            project_id:     form.value.project_id || null,
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

// ── status change ──────────────────────────────────────────────────────────────

async function moveToColumn(task: Task, colId: number) {
    if (task.task_column_id === colId) return
    task.task_column_id = colId
    await api.patch(`/tasks/${task.id}`, { task_column_id: colId })
}

// ── drag & drop ───────────────────────────────────────────────────────────────

function onDragStart(e: DragEvent, taskId: number) {
    draggedId.value = taskId
    if (e.dataTransfer) e.dataTransfer.effectAllowed = 'move'
}

function onDragOver(e: DragEvent, colId: number) {
    e.preventDefault()
    dragOverCol.value = colId
    if (e.dataTransfer) e.dataTransfer.dropEffect = 'move'
}

function onDragLeave() { dragOverCol.value = null }

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

// ── load ───────────────────────────────────────────────────────────────────────

onMounted(async () => {
    try {
        const [colsRes, tasksRes, usersRes, projRes] = await Promise.all([
            api.get('/task-columns'),
            api.get('/tasks'),
            api.get('/admin-users'),
            api.get('/projects'),
        ])
        columns.value  = (colsRes.data?.data ?? colsRes.data ?? []).sort((a: Column, b: Column) => a.sort_order - b.sort_order)
        const raw = tasksRes.data
        tasks.value    = Array.isArray(raw) ? raw : (raw.data ?? [])
        staff.value    = (Array.isArray(usersRes.data) ? usersRes.data : (usersRes.data?.data ?? []))
        projects.value = (Array.isArray(projRes.data)  ? projRes.data  : (projRes.data?.data  ?? []))
    } finally {
        loading.value = false
    }
})
</script>

<template>
    <div class="space-y-5 p-6">
        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Tasks</h1>
                <p class="mt-1 text-sm text-gray-500">Drag cards between columns or use the status menu to move them</p>
            </div>
            <button
                @click="openModal()"
                class="inline-flex items-center gap-2 self-start rounded-lg bg-cyan-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-cyan-600 sm:self-auto"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Task
            </button>
        </div>

        <!-- Filter bar -->
        <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-end gap-3">

                <!-- Project -->
                <div class="flex flex-col gap-1 min-w-[160px]">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Project</label>
                    <select
                        v-model="fProject"
                        class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20"
                    >
                        <option value="">All projects</option>
                        <option :value="null">No project</option>
                        <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                </div>

                <!-- Status (column) -->
                <div class="flex flex-col gap-1 min-w-[140px]">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</label>
                    <select
                        v-model="fStatus"
                        class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20"
                    >
                        <option value="">All statuses</option>
                        <option v-for="col in columns" :key="col.id" :value="col.id">{{ col.title }}</option>
                    </select>
                </div>

                <!-- Assignee -->
                <div class="flex flex-col gap-1 min-w-[140px]">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Assignee</label>
                    <select
                        v-model="fAssignee"
                        class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20"
                    >
                        <option value="">All assignees</option>
                        <option v-for="name in assigneeOptions" :key="name" :value="name">{{ name }}</option>
                        <option v-for="u in staff" :key="u.id" :value="u.name">{{ u.name }}</option>
                    </select>
                </div>

                <!-- Date from -->
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Due from</label>
                    <input
                        v-model="fDateFrom"
                        type="date"
                        class="rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20"
                    />
                </div>

                <!-- Date to -->
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Due to</label>
                    <input
                        v-model="fDateTo"
                        type="date"
                        class="rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20"
                    />
                </div>

                <!-- Clear -->
                <button
                    v-if="activeFilterCount > 0"
                    @click="clearFilters"
                    class="mb-0.5 inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-500 transition-colors hover:bg-gray-50"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    Clear <span class="rounded-full bg-cyan-100 px-1.5 text-xs font-bold text-cyan-700">{{ activeFilterCount }}</span>
                </button>
            </div>

            <!-- Active project highlight -->
            <div v-if="fProject !== '' && fProject !== null" class="mt-3 flex items-center gap-2 text-sm">
                <span class="text-gray-400">Showing tasks for:</span>
                <span class="rounded-full bg-cyan-100 px-3 py-0.5 text-xs font-bold text-cyan-700">
                    {{ projects.find(p => p.id === fProject)?.name ?? 'Unknown' }}
                </span>
                <span class="text-gray-400 text-xs">· {{ filteredTasks.length }} task{{ filteredTasks.length === 1 ? '' : 's' }}</span>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-20">
            <div class="h-8 w-8 animate-spin rounded-full border-4 border-cyan-500 border-t-transparent"></div>
        </div>

        <!-- Kanban board -->
        <div v-else class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">
            <div
                v-for="col in columns"
                :key="col.id"
                class="flex min-h-[420px] flex-col rounded-xl border-2 transition-colors"
                :class="dragOverCol === col.id ? (colDrop[col.slug] ?? 'border-cyan-300 bg-cyan-50/60') : 'border-gray-200 bg-gray-50/40'"
                @dragover="onDragOver($event, col.id)"
                @dragleave="onDragLeave"
                @drop.prevent="onDrop(col.id)"
            >
                <!-- Column header -->
                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                    <div class="flex items-center gap-2">
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="colClass(col)">{{ col.title }}</span>
                        <span class="text-xs font-medium text-gray-400">{{ tasksFor(col.id).length }}</span>
                    </div>
                    <button
                        @click="openModal(col.id)"
                        class="flex h-6 w-6 items-center justify-center rounded-md text-gray-400 transition-colors hover:bg-cyan-50 hover:text-cyan-600"
                        title="Add task to this column"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>

                <!-- Cards -->
                <div class="flex-1 space-y-3 p-3">
                    <!-- Empty state -->
                    <div v-if="tasksFor(col.id).length === 0" class="flex flex-col items-center justify-center py-10 text-gray-300">
                        <svg class="mb-2 h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <p class="text-xs">No tasks</p>
                    </div>

                    <!-- Task card -->
                    <div
                        v-for="task in tasksFor(col.id)"
                        :key="task.id"
                        draggable="true"
                        @dragstart="onDragStart($event, task.id)"
                        @dragend="onDragEnd"
                        class="group cursor-grab select-none overflow-hidden rounded-lg border border-gray-100 bg-white shadow-sm transition-shadow hover:shadow-md active:cursor-grabbing"
                        :class="[draggedId === task.id ? 'opacity-50' : '', cardBorderCls(task.due_date)]"
                    >
                        <!-- Overdue / due-today / due-soon strip -->
                        <div v-if="dueStatus(task.due_date) === 'overdue'" class="flex items-center gap-1.5 border-b border-red-100 bg-red-50 px-3.5 py-1.5">
                            <svg class="h-3 w-3 animate-pulse text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            <span class="text-[11px] font-bold tracking-wide text-red-600">OVERDUE</span>
                        </div>
                        <div v-else-if="dueStatus(task.due_date) === 'due-today'" class="flex items-center gap-1.5 border-b border-amber-100 bg-amber-50 px-3.5 py-1.5">
                            <svg class="h-3 w-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <span class="text-[11px] font-bold tracking-wide text-amber-600">DUE TODAY</span>
                        </div>
                        <div v-else-if="dueStatus(task.due_date) === 'due-soon'" class="flex items-center gap-1.5 border-b border-amber-100/70 bg-amber-50/60 px-3.5 py-1.5">
                            <svg class="h-3 w-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <span class="text-[11px] font-semibold text-amber-500">Due Tomorrow</span>
                        </div>

                        <!-- Card body -->
                        <div class="p-3.5">
                        <!-- Priority + actions -->
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="priorityClass(task.priority)">
                                {{ (task.priority ?? 'low').charAt(0).toUpperCase() + (task.priority ?? 'low').slice(1) }}
                            </span>
                            <div class="flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                <button @click="openModal(undefined, task)" class="flex h-6 w-6 items-center justify-center rounded text-gray-400 transition-colors hover:bg-cyan-50 hover:text-cyan-600" title="Edit">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </button>
                                <button @click="deleteTask(task.id)" class="flex h-6 w-6 items-center justify-center rounded text-gray-400 transition-colors hover:bg-red-50 hover:text-red-500" title="Delete">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Project badge -->
                        <div v-if="projectName(task.project_id)" class="mb-1.5">
                            <span class="inline-flex items-center gap-1 rounded-full bg-violet-50 px-2 py-0.5 text-[10px] font-semibold text-violet-600">
                                <svg class="h-2.5 w-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
                                {{ projectName(task.project_id) }}
                            </span>
                        </div>

                        <!-- Title -->
                        <p class="mb-1 text-sm font-semibold leading-snug text-gray-900">{{ task.title }}</p>

                        <!-- Description preview -->
                        <p v-if="task.description" class="mb-2 line-clamp-2 text-xs leading-relaxed text-gray-400">{{ task.description }}</p>

                        <!-- Footer -->
                        <div class="mt-2 flex items-center justify-between gap-2">
                            <span v-if="task.due_date" class="flex items-center gap-1 text-xs font-medium"
                                :class="{
                                    'text-red-500':   dueStatus(task.due_date) === 'overdue',
                                    'text-amber-600': dueStatus(task.due_date) === 'due-today',
                                    'text-amber-500': dueStatus(task.due_date) === 'due-soon',
                                    'text-gray-400':  !dueStatus(task.due_date),
                                }"
                            >
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                {{ fmtDate(task.due_date) }}
                            </span>
                            <span v-else></span>

                            <div class="flex items-center gap-1.5">
                                <!-- Move to column -->
                                <select
                                    :value="task.task_column_id"
                                    @change="moveToColumn(task, Number(($event.target as HTMLSelectElement).value))"
                                    @click.stop
                                    class="cursor-pointer rounded border border-gray-200 bg-white px-1.5 py-0.5 text-xs text-gray-500 outline-none focus:border-cyan-400"
                                    title="Move to column"
                                >
                                    <option v-for="c in columns" :key="c.id" :value="c.id">{{ c.title }}</option>
                                </select>
                                <!-- Assignee avatar -->
                                <div v-if="task.assignee" class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-[10px] font-bold text-cyan-700" :title="task.assignee">
                                    {{ initials(task.assignee) }}
                                </div>
                            </div>
                        </div>
                        </div><!-- end card body -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm" @mousedown.self="closeModal">
                <div class="flex max-h-[90vh] w-full max-w-lg flex-col rounded-xl bg-white shadow-2xl">
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                        <h2 class="text-base font-bold text-gray-900">{{ editTarget ? 'Edit Task' : 'New Task' }}</h2>
                        <button @click="closeModal" class="rounded-lg p-1 text-gray-400 transition-colors hover:text-gray-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="flex-1 space-y-4 overflow-y-auto p-6">
                        <!-- Title -->
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Title <span class="text-red-400">*</span></label>
                            <input v-model="form.title" type="text" placeholder="Task title" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20"/>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                            <textarea v-model="form.description" rows="3" placeholder="Optional details…" class="w-full resize-none rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20"></textarea>
                        </div>

                        <!-- Project -->
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Project</label>
                            <select v-model="form.project_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-cyan-500">
                                <option :value="null">No project</option>
                                <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}{{ p.client ? ' · ' + p.client : '' }}</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <!-- Column -->
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                                <select v-model="form.task_column_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-cyan-500">
                                    <option v-for="col in columns" :key="col.id" :value="col.id">{{ col.title }}</option>
                                </select>
                            </div>

                            <!-- Priority -->
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Priority</label>
                                <select v-model="form.priority" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-cyan-500">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <!-- Due date -->
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Due Date</label>
                                <input v-model="form.due_date" type="date" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-cyan-500"/>
                            </div>

                            <!-- Assignee -->
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Assignee</label>
                                <select v-model="form.assignee" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-cyan-500">
                                    <option value="">Unassigned</option>
                                    <option v-for="u in staff" :key="u.id" :value="u.name">{{ u.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end gap-3 rounded-b-xl border-t border-gray-100 bg-gray-50 px-6 py-4">
                        <button @click="closeModal" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100">Cancel</button>
                        <button
                            @click="submitTask"
                            :disabled="saving || !form.title.trim()"
                            class="inline-flex items-center gap-2 rounded-lg bg-cyan-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-cyan-600 disabled:opacity-50"
                        >
                            <div v-if="saving" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                            {{ editTarget ? 'Save Changes' : 'Add Task' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
