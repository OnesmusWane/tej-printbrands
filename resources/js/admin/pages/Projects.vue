<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue'
import api from '../api'
import { useToastStore } from '../stores/toast'

interface TaskColumn {
    id: number
    title: string
    slug: string
}

interface Task {
    id: number
    title: string
    description?: string | null
    task_column_id: number
    project_id?: number | null
    priority?: string
    due_date?: string | null
    assignee?: string | null
    column?: TaskColumn | null
}

interface Project {
    id: number
    name: string
    description: string | null
    client: string | null
    owner: string | null
    status: 'active' | 'completed' | 'on-hold'
    start_date: string | null
    end_date: string | null
    tasks: Task[]
    bookings_count: number
    service_requests_count: number
    created_at: string
}

interface StaffUser { id: number; name: string; role: string }

const toast   = useToastStore()
const items   = ref<Project[]>([])
const loading = ref(false)
const saving  = ref(false)
const staff   = ref<StaffUser[]>([])
const columns = ref<TaskColumn[]>([])
const expanded = ref<Set<number>>(new Set())

// ── project modal ─────────────────────────────────────────────────────────────
const showProjectModal = ref(false)
const editingProject   = ref<Partial<Project>>({})

// ── task modal ────────────────────────────────────────────────────────────────
const showTaskModal  = ref(false)
const savingTask     = ref(false)
const editingTask    = ref<Partial<Task & { project_id: number | null }>>({})
const taskProjectId  = ref<number | null>(null)  // which project this task belongs to

// ── filters ───────────────────────────────────────────────────────────────────
const search       = ref('')
const filterStatus = ref('')

let searchTimer: ReturnType<typeof setTimeout>
function onSearchInput() {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(load, 350)
}

// ── computed ──────────────────────────────────────────────────────────────────
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase()
    return items.value.filter(p => {
        if (filterStatus.value && p.status !== filterStatus.value) return false
        if (q) {
            const haystack = `${p.name} ${p.client ?? ''} ${p.owner ?? ''}`.toLowerCase()
            if (!haystack.includes(q)) return false
        }
        return true
    })
})

// ── status helpers ────────────────────────────────────────────────────────────
const STATUS_OPTS = [
    { value: 'active',    label: 'Active',    cls: 'bg-cyan-100 text-cyan-700 border-cyan-200' },
    { value: 'completed', label: 'Completed', cls: 'bg-green-100 text-green-700 border-green-200' },
    { value: 'on-hold',   label: 'On Hold',   cls: 'bg-orange-100 text-orange-700 border-orange-200' },
]

function statusCls(status: string) {
    return STATUS_OPTS.find(s => s.value === status)?.cls ?? 'bg-gray-100 text-gray-500'
}

function statusLabel(status: string) {
    return STATUS_OPTS.find(s => s.value === status)?.label ?? status
}

const PRIORITY_CLS: Record<string, string> = {
    high:   'bg-red-100 text-red-700',
    medium: 'bg-orange-100 text-orange-700',
    low:    'bg-green-100 text-green-700',
}

const COL_CLS: Record<string, string> = {
    todo:          'bg-gray-100 text-gray-600',
    'in-progress': 'bg-blue-100 text-blue-700',
    completed:     'bg-green-100 text-green-700',
    'on-hold':     'bg-orange-100 text-orange-700',
}

function colCls(task: Task) {
    return COL_CLS[task.column?.slug ?? ''] ?? 'bg-gray-100 text-gray-500'
}

function initials(name?: string | null) {
    if (!name) return '?'
    return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase()
}

function fmtDate(d?: string | null) {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('en-KE', { day: 'numeric', month: 'short', year: 'numeric' })
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

// ── load ──────────────────────────────────────────────────────────────────────
async function load() {
    loading.value = true
    try {
        const params: Record<string, string> = {}
        if (search.value.trim()) params.search = search.value.trim()
        const [projRes, colsRes, usersRes] = await Promise.all([
            api.get('/projects', { params }),
            columns.value.length ? Promise.resolve(null) : api.get('/task-columns'),
            staff.value.length   ? Promise.resolve(null) : api.get('/admin-users'),
        ])
        items.value = Array.isArray(projRes.data) ? projRes.data : (projRes.data.data ?? [])
        if (colsRes) columns.value = (colsRes.data?.data ?? colsRes.data ?? []).sort((a: TaskColumn, b: TaskColumn) => (a as any).sort_order - (b as any).sort_order)
        if (usersRes) staff.value  = Array.isArray(usersRes.data) ? usersRes.data : (usersRes.data?.data ?? [])
    } catch {
        toast.add('Failed to load projects.', 'error')
    } finally {
        loading.value = false
    }
}

// ── project CRUD ──────────────────────────────────────────────────────────────
function openNewProject() {
    editingProject.value = { status: 'active', start_date: null, end_date: null, owner: null, client: null, description: null }
    showProjectModal.value = true
}

function openEditProject(p: Project) {
    editingProject.value = {
        ...p,
        start_date: p.start_date ? p.start_date.slice(0, 10) : null,
        end_date:   p.end_date   ? p.end_date.slice(0, 10)   : null,
    }
    showProjectModal.value = true
}

async function submitProject() {
    saving.value = true
    try {
        if (editingProject.value.id) {
            const { data } = await api.patch(`/projects/${editingProject.value.id}`, editingProject.value as any)
            const idx = items.value.findIndex(p => p.id === editingProject.value.id)
            if (idx !== -1) items.value[idx] = data
        } else {
            const { data } = await api.post('/projects', editingProject.value as any)
            items.value.unshift(data)
            expanded.value.add(data.id)
        }
        showProjectModal.value = false
        toast.add(editingProject.value.id ? 'Project updated.' : 'Project created.')
    } catch (err: any) {
        toast.add(err?.response?.data?.message ?? 'Save failed.', 'error')
    } finally {
        saving.value = false
    }
}

async function deleteProject(id: number) {
    if (!confirm('Delete this project? Tasks will be unlinked but not deleted.')) return
    try {
        await api.delete(`/projects/${id}`)
        items.value = items.value.filter(p => p.id !== id)
        expanded.value.delete(id)
        toast.add('Project deleted.')
    } catch {
        toast.add('Delete failed.', 'error')
    }
}

// ── task CRUD ─────────────────────────────────────────────────────────────────
function openNewTask(projectId: number) {
    taskProjectId.value  = projectId
    editingTask.value = {
        title:          '',
        description:    '',
        task_column_id: columns.value[0]?.id ?? 1,
        project_id:     projectId,
        priority:       'medium',
        due_date:       null,
        assignee:       null,
    }
    showTaskModal.value = true
}

function openEditTask(task: Task) {
    taskProjectId.value = task.project_id ?? null
    editingTask.value = {
        ...task,
        due_date: task.due_date ? task.due_date.slice(0, 10) : null,
    }
    showTaskModal.value = true
}

async function submitTask() {
    if (!editingTask.value.title?.trim()) return
    savingTask.value = true
    try {
        const payload = {
            title:          editingTask.value.title,
            description:    editingTask.value.description || null,
            task_column_id: editingTask.value.task_column_id,
            project_id:     editingTask.value.project_id ?? null,
            priority:       editingTask.value.priority,
            due_date:       editingTask.value.due_date || null,
            assignee:       editingTask.value.assignee || null,
        }

        let savedTask: Task
        if (editingTask.value.id) {
            const { data } = await api.patch(`/tasks/${editingTask.value.id}`, payload)
            savedTask = data.data ?? data
            // update in-place across projects
            items.value.forEach(p => {
                const idx = p.tasks.findIndex(t => t.id === savedTask.id)
                if (idx !== -1) p.tasks[idx] = { ...savedTask, column: columns.value.find(c => c.id === savedTask.task_column_id) ?? null }
            })
        } else {
            const { data } = await api.post('/tasks', payload)
            savedTask = data.data ?? data
            const proj = items.value.find(p => p.id === taskProjectId.value)
            if (proj) proj.tasks.push({ ...savedTask, column: columns.value.find(c => c.id === savedTask.task_column_id) ?? null })
        }

        showTaskModal.value = false
        toast.add(editingTask.value.id ? 'Task updated.' : 'Task added.')
    } catch (err: any) {
        toast.add(err?.response?.data?.message ?? 'Save failed.', 'error')
    } finally {
        savingTask.value = false
    }
}

async function deleteTask(task: Task) {
    if (!confirm('Delete this task?')) return
    try {
        await api.delete(`/tasks/${task.id}`)
        items.value.forEach(p => {
            p.tasks = p.tasks.filter(t => t.id !== task.id)
        })
        toast.add('Task deleted.')
    } catch {
        toast.add('Delete failed.', 'error')
    }
}

// ── toggle expand ─────────────────────────────────────────────────────────────
function toggleExpand(id: number) {
    if (expanded.value.has(id)) expanded.value.delete(id)
    else expanded.value.add(id)
}

onMounted(load)
</script>

<template>
    <div class="space-y-6 p-6">

        <!-- ── Header ── -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-dark">Projects</h1>
                <p class="mt-1 text-sm text-gray-500">Track work by grouping tasks into projects.</p>
            </div>
            <button
                @click="openNewProject"
                class="inline-flex items-center gap-2 self-start rounded-xl bg-cyan-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-cyan-600 sm:self-auto"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Project
            </button>
        </div>

        <!-- ── Filters ── -->
        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1 max-w-sm">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input
                    v-model="search"
                    @input="onSearchInput"
                    type="text"
                    placeholder="Search name, client, owner…"
                    class="w-full rounded-xl border border-gray-200 py-2.5 pl-9 pr-4 text-sm outline-none transition-all focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20"
                />
            </div>
            <div class="flex gap-2">
                <button
                    v-for="opt in [{ value: '', label: 'All' }, ...STATUS_OPTS]"
                    :key="opt.value"
                    @click="filterStatus = opt.value"
                    :class="[
                        'rounded-xl border px-4 py-2.5 text-xs font-semibold transition-colors',
                        filterStatus === opt.value
                            ? 'border-cyan-500 bg-cyan-500 text-white'
                            : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'
                    ]"
                >{{ opt.label }}</button>
            </div>
        </div>

        <!-- ── Loading ── -->
        <div v-if="loading" class="flex justify-center py-16">
            <div class="h-8 w-8 animate-spin rounded-full border-4 border-cyan-500 border-t-transparent"></div>
        </div>

        <!-- ── Empty ── -->
        <div v-else-if="filtered.length === 0" class="rounded-xl border border-dashed border-gray-200 py-20 text-center text-sm text-gray-400">
            No projects found. Create your first project.
        </div>

        <!-- ── Project cards ── -->
        <div v-else class="space-y-4">
            <div
                v-for="project in filtered"
                :key="project.id"
                class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition-shadow hover:shadow-md"
            >
                <!-- Project header row -->
                <div
                    class="flex cursor-pointer flex-wrap items-center gap-4 px-5 py-4 sm:flex-nowrap"
                    @click="toggleExpand(project.id)"
                >
                    <!-- Expand chevron -->
                    <svg
                        class="h-4 w-4 shrink-0 text-gray-400 transition-transform"
                        :class="expanded.has(project.id) ? 'rotate-90' : ''"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>

                    <!-- Status badge -->
                    <span :class="['shrink-0 rounded-full border px-3 py-1 text-xs font-bold', statusCls(project.status)]">
                        {{ statusLabel(project.status) }}
                    </span>

                    <!-- Name + client -->
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-bold text-dark">{{ project.name }}</p>
                        <p v-if="project.client" class="truncate text-xs text-gray-400">{{ project.client }}</p>
                    </div>

                    <!-- Owner -->
                    <div v-if="project.owner" class="hidden shrink-0 items-center gap-2 sm:flex">
                        <div class="flex h-7 w-7 items-center justify-center rounded-full bg-violet-100 text-[10px] font-bold text-violet-700" :title="project.owner">
                            {{ initials(project.owner) }}
                        </div>
                        <span class="text-xs font-medium text-gray-500">{{ project.owner }}</span>
                    </div>

                    <!-- Dates + deadline alert -->
                    <div class="hidden shrink-0 text-right text-xs lg:block">
                        <p v-if="project.start_date" class="text-gray-400">{{ fmtDate(project.start_date) }}</p>
                        <p v-if="project.end_date" class="text-[11px]"
                            :class="{
                                'font-bold text-red-500':   dueStatus(project.end_date) === 'overdue',
                                'font-bold text-amber-600': dueStatus(project.end_date) === 'due-today',
                                'font-semibold text-amber-500': dueStatus(project.end_date) === 'due-soon',
                                'text-gray-400': !dueStatus(project.end_date),
                            }"
                        >→ {{ fmtDate(project.end_date) }}</p>
                    </div>

                    <!-- Deadline badge (overdue / due today / due soon) -->
                    <div v-if="dueStatus(project.end_date) === 'overdue'" class="hidden shrink-0 items-center gap-1 rounded-full bg-red-100 px-2.5 py-1 sm:flex">
                        <svg class="h-3 w-3 animate-pulse text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span class="text-[11px] font-bold text-red-600">Overdue</span>
                    </div>
                    <div v-else-if="dueStatus(project.end_date) === 'due-today'" class="hidden shrink-0 items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 sm:flex">
                        <svg class="h-3 w-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span class="text-[11px] font-bold text-amber-600">Due Today</span>
                    </div>
                    <div v-else-if="dueStatus(project.end_date) === 'due-soon'" class="hidden shrink-0 items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 sm:flex">
                        <svg class="h-3 w-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span class="text-[11px] font-semibold text-amber-500">Due Tomorrow</span>
                    </div>

                    <!-- Task count -->
                    <div class="shrink-0 text-center">
                        <p class="text-lg font-extrabold text-dark leading-none">{{ project.tasks.length }}</p>
                        <p class="text-[10px] font-medium uppercase tracking-wide text-gray-400">tasks</p>
                    </div>

                    <!-- Actions (stop propagation so clicks don't toggle expand) -->
                    <div class="flex shrink-0 gap-2" @click.stop>
                        <button
                            @click="openNewTask(project.id); expanded.add(project.id)"
                            class="rounded-lg border border-cyan-200 bg-cyan-50 px-3 py-1.5 text-xs font-semibold text-cyan-700 transition-colors hover:bg-cyan-100"
                            title="Add task"
                        >+ Task</button>
                        <button @click="openEditProject(project)" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-600 transition-colors hover:bg-gray-50">Edit</button>
                        <button @click="deleteProject(project.id)" class="rounded-lg border border-red-100 px-3 py-1.5 text-xs font-semibold text-red-500 transition-colors hover:bg-red-50">Delete</button>
                    </div>
                </div>

                <!-- ── Expanded task list ── -->
                <div v-if="expanded.has(project.id)" class="border-t border-gray-100">

                    <!-- Description banner -->
                    <div v-if="project.description" class="border-b border-gray-50 bg-gray-50/60 px-6 py-3 text-sm text-gray-500">
                        {{ project.description }}
                    </div>

                    <!-- Project meta row (mobile owner + dates) -->
                    <div class="flex flex-wrap gap-4 border-b border-gray-50 bg-gray-50/40 px-6 py-3 text-xs text-gray-500 sm:hidden">
                        <span v-if="project.owner" class="flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            {{ project.owner }}
                        </span>
                        <span v-if="project.start_date" class="flex items-center gap-1">{{ fmtDate(project.start_date) }} → {{ fmtDate(project.end_date) }}</span>
                    </div>

                    <!-- Task rows -->
                    <div v-if="project.tasks.length > 0" class="divide-y divide-gray-50">
                        <div
                            v-for="task in project.tasks"
                            :key="task.id"
                            class="group flex flex-wrap items-center gap-3 px-6 py-3 transition-colors hover:bg-gray-50/70 sm:flex-nowrap"
                            :class="{
                                'bg-red-50/40 hover:bg-red-50/60':   dueStatus(task.due_date) === 'overdue',
                                'bg-amber-50/30 hover:bg-amber-50/50': dueStatus(task.due_date) === 'due-today' || dueStatus(task.due_date) === 'due-soon',
                            }"
                        >
                            <!-- Priority dot -->
                            <span
                                :class="['h-2 w-2 shrink-0 rounded-full', task.priority === 'high' ? 'bg-red-400' : task.priority === 'medium' ? 'bg-orange-400' : 'bg-green-400']"
                                :title="task.priority"
                            ></span>

                            <!-- Title -->
                            <p class="min-w-0 flex-1 text-sm font-semibold text-dark">{{ task.title }}</p>

                            <!-- Column / status badge -->
                            <span :class="['shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold', colCls(task)]">
                                {{ task.column?.title ?? '—' }}
                            </span>

                            <!-- Due date + urgency label -->
                            <template v-if="task.due_date">
                                <span v-if="dueStatus(task.due_date) === 'overdue'"
                                    class="flex shrink-0 items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-bold text-red-600">
                                    <svg class="h-3 w-3 animate-pulse" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                    Overdue · {{ fmtDate(task.due_date) }}
                                </span>
                                <span v-else-if="dueStatus(task.due_date) === 'due-today'"
                                    class="flex shrink-0 items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-700">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    Due Today
                                </span>
                                <span v-else-if="dueStatus(task.due_date) === 'due-soon'"
                                    class="flex shrink-0 items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-600">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    Due Tomorrow
                                </span>
                                <span v-else class="shrink-0 text-xs font-medium text-gray-400">{{ fmtDate(task.due_date) }}</span>
                            </template>

                            <!-- Assignee avatar -->
                            <div
                                v-if="task.assignee"
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-[10px] font-bold text-cyan-700"
                                :title="task.assignee"
                            >{{ initials(task.assignee) }}</div>
                            <div v-else class="h-6 w-6 shrink-0"></div>

                            <!-- Actions -->
                            <div class="flex shrink-0 gap-1.5 opacity-0 transition-opacity group-hover:opacity-100">
                                <button @click="openEditTask(task)" class="rounded-md p-1 text-gray-400 transition-colors hover:bg-cyan-50 hover:text-cyan-600" title="Edit task">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </button>
                                <button @click="deleteTask(task)" class="rounded-md p-1 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-500" title="Delete task">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Empty task state -->
                    <div v-else class="flex flex-col items-center gap-2 py-8 text-sm text-gray-400">
                        <svg class="h-8 w-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        No tasks yet.
                        <button @click="openNewTask(project.id)" class="text-cyan-600 underline-offset-2 hover:underline">Add the first task</button>
                    </div>

                    <!-- Add task quick-link -->
                    <div v-if="project.tasks.length > 0" class="border-t border-gray-50 px-6 py-2.5">
                        <button @click="openNewTask(project.id)" class="flex items-center gap-1.5 text-xs font-semibold text-gray-400 transition-colors hover:text-cyan-600">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add task
                        </button>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- ── Project modal ── -->
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="showProjectModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
                @click.self="showProjectModal = false"
            >
                <div class="w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                        <h2 class="text-lg font-bold text-dark">{{ editingProject.id ? 'Edit Project' : 'New Project' }}</h2>
                        <button @click="showProjectModal = false" class="text-xl leading-none text-gray-400 hover:text-gray-600">&times;</button>
                    </div>
                    <form @submit.prevent="submitProject" class="space-y-4 px-6 py-5">

                        <!-- Name -->
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-gray-600">Project Name <span class="text-red-400">*</span></label>
                            <input v-model="editingProject.name" required class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <!-- Client -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-gray-600">Client</label>
                                <input v-model="editingProject.client" placeholder="Client or company" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                            </div>

                            <!-- Owner -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-gray-600">Owner / Manager</label>
                                <select v-model="editingProject.owner" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all">
                                    <option :value="null">— None —</option>
                                    <option v-for="u in staff" :key="u.id" :value="u.name">{{ u.name }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-gray-600">Description</label>
                            <textarea v-model="editingProject.description" rows="3" class="w-full resize-none rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"></textarea>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="mb-2 block text-xs font-semibold text-gray-600">Status</label>
                            <div class="flex gap-2">
                                <button
                                    v-for="opt in STATUS_OPTS"
                                    :key="opt.value"
                                    type="button"
                                    @click="editingProject.status = opt.value as any"
                                    :class="[
                                        'flex-1 rounded-xl border px-3 py-2.5 text-xs font-bold transition-all',
                                        editingProject.status === opt.value ? opt.cls + ' shadow-sm' : 'border-gray-200 text-gray-500 hover:bg-gray-50'
                                    ]"
                                >{{ opt.label }}</button>
                            </div>
                        </div>

                        <!-- Dates -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-gray-600">Start Date</label>
                                <input type="date" v-model="editingProject.start_date" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-gray-600">End Date</label>
                                <input type="date" v-model="editingProject.end_date" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-gray-100 pt-2">
                            <button type="button" @click="showProjectModal = false" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">Cancel</button>
                            <button type="submit" :disabled="saving" class="rounded-xl bg-cyan-500 px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-cyan-600 disabled:opacity-60">
                                {{ saving ? 'Saving…' : editingProject.id ? 'Update' : 'Create Project' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </Teleport>

    <!-- ── Task modal ── -->
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="showTaskModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
                @click.self="showTaskModal = false"
            >
                <div class="w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                        <div>
                            <h2 class="text-lg font-bold text-dark">{{ editingTask.id ? 'Edit Task' : 'New Task' }}</h2>
                            <p class="mt-0.5 text-xs text-gray-400">
                                Project: <span class="font-semibold text-violet-600">{{ items.find(p => p.id === taskProjectId)?.name ?? '—' }}</span>
                            </p>
                        </div>
                        <button @click="showTaskModal = false" class="text-xl leading-none text-gray-400 hover:text-gray-600">&times;</button>
                    </div>
                    <form @submit.prevent="submitTask" class="space-y-4 px-6 py-5">

                        <!-- Title -->
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-gray-600">Title <span class="text-red-400">*</span></label>
                            <input v-model="editingTask.title" required placeholder="Task title" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-gray-600">Description</label>
                            <textarea v-model="editingTask.description" rows="3" placeholder="Optional details…" class="w-full resize-none rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <!-- Status (column) -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-gray-600">Status</label>
                                <select v-model="editingTask.task_column_id" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none focus:border-cyan-500 transition-all">
                                    <option v-for="col in columns" :key="col.id" :value="col.id">{{ col.title }}</option>
                                </select>
                            </div>

                            <!-- Priority -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-gray-600">Priority</label>
                                <select v-model="editingTask.priority" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none focus:border-cyan-500 transition-all">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <!-- Due date -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-gray-600">Due Date</label>
                                <input type="date" v-model="editingTask.due_date" class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none focus:border-cyan-500 transition-all"/>
                            </div>

                            <!-- Assignee -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-gray-600">Assignee</label>
                                <select v-model="editingTask.assignee" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm outline-none focus:border-cyan-500 transition-all">
                                    <option :value="null">Unassigned</option>
                                    <option v-for="u in staff" :key="u.id" :value="u.name">{{ u.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-gray-100 pt-2">
                            <button type="button" @click="showTaskModal = false" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-colors">Cancel</button>
                            <button type="submit" :disabled="savingTask" class="rounded-xl bg-cyan-500 px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-cyan-600 disabled:opacity-60">
                                {{ savingTask ? 'Saving…' : editingTask.id ? 'Update Task' : 'Add Task' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.97); }
</style>
