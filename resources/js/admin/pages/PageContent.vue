<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api from '../api'
import { useToastStore } from '../stores/toast'
import ImageUpload from '../components/ImageUpload.vue'

interface SiteSection {
    id: number
    key: string
    label?: string
    heading: string
    subtext: string
    image_url?: string
    _settings: Record<string, string | number>
}

interface ProcessStep {
    id: number           // negative = unsaved new step
    number: string
    title: string
    description: string
    sort_order: number
    is_visible: boolean
    _saving?: boolean
}

const toast = useToastStore()

const pages = [
    { slug: 'home',     label: 'Home Page' },
    { slug: 'services', label: 'Services' },
    { slug: 'work',     label: 'Work' },
    { slug: 'shop',     label: 'Shop' },
    { slug: 'gallery',  label: 'Gallery' },
    { slug: 'contact',  label: 'Contact' },
]

const pageIcons: Record<string, string> = {
    home:     'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    services: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
    work:     'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
    shop:     'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
    gallery:  'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
    contact:  'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.948V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 8V5z',
}

// Human-friendly labels for common settings keys
const SETTING_LABELS: Record<string, string> = {
    cta_primary:   'Primary Button Label',
    cta_secondary: 'Secondary Button Label',
    cta_tertiary:  'Tertiary Button Label',
    cta:           'CTA Button Label',
    cta_label:     'Button Label',
    cta_url:       'Button URL',
    button_label:  'Button Label',
    button_url:    'Button URL',
    quote_label:   'Quote Button Label',
}

function settingLabel(key: string): string {
    return SETTING_LABELS[key] ?? key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

// Parse API response settings into a usable object (handle array [], object {}, or string edge cases)
function parseSettings(raw: any): Record<string, string | number> {
    if (!raw) return {}
    if (Array.isArray(raw)) return {}
    if (typeof raw === 'object') return Object.fromEntries(
        Object.entries(raw).filter(([, v]) => typeof v === 'string' || typeof v === 'number')
    ) as Record<string, string | number>
    if (typeof raw === 'string') {
        try {
            const parsed = JSON.parse(raw)
            if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
                return Object.fromEntries(
                    Object.entries(parsed).filter(([, v]) => typeof v === 'string' || typeof v === 'number')
                ) as Record<string, string | number>
            }
        } catch { /* ignore */ }
    }
    return {}
}

// Only entries that are simple strings/numbers are shown as individual inputs
function settingsEntries(s: SiteSection): Array<[string, string | number]> {
    return Object.entries(s._settings) as Array<[string, string | number]>
}

function updateSetting(s: SiteSection, key: string, value: string) {
    s._settings[key] = value
}

const activePage  = ref('home')
const sectionsMap = ref<Record<string, SiteSection[]>>({})
const loading     = ref(false)
const saving      = ref(false)
const savedFlash  = ref(false)

const sections = computed(() => sectionsMap.value[activePage.value] ?? [])

function sectionTitle(s: SiteSection): string {
    if (s.label) return s.label
    return s.key.replace(/_|-/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) + ' Section'
}

async function loadPage(slug: string) {
    if (sectionsMap.value[slug]) return
    loading.value = true
    try {
        const { data } = await api.get('/site-sections', { params: { page_slug: slug } })
        const rows: SiteSection[] = (Array.isArray(data) ? data : (data.data ?? [])).map((r: any) => ({
            id:        r.id,
            key:       r.key,
            label:     r.label,
            heading:   r.heading ?? '',
            subtext:   r.subtext ?? '',
            image_url: r.image_url ?? '',
            _settings: parseSettings(r.settings),
        }))
        sectionsMap.value[slug] = rows
    } catch {
        toast.add('Failed to load sections.', 'error')
    } finally {
        loading.value = false
    }
}

async function switchPage(slug: string) {
    activePage.value = slug
    await loadPage(slug)
    if (slug === 'services') loadProcessSteps()
}

async function saveAll() {
    saving.value = true
    const rows = sectionsMap.value[activePage.value] ?? []
    try {
        await Promise.all(
            rows.map(s => {
                const payload: Record<string, any> = {
                    heading:  s.heading,
                    subtext:  s.subtext,
                    settings: s._settings,
                }
                // Only patch image_url for hero sections — avoids accidentally wiping images managed elsewhere
                if (s.key === 'hero') {
                    payload.image_url = s.image_url || null
                }
                return api.patch(`/site-sections/${s.id}`, payload)
            })
        )
        toast.add('Changes saved successfully.')
        savedFlash.value = true
        setTimeout(() => { savedFlash.value = false }, 2500)
    } catch {
        toast.add('Save failed. Please try again.', 'error')
    } finally {
        saving.value = false
    }
}

// ─── Process Steps (Services page) ───────────────────────────────────────────

const processSteps     = ref<ProcessStep[]>([])
const stepsLoaded      = ref(false)
let   nextTmpId        = -1

async function loadProcessSteps() {
    if (stepsLoaded.value) return
    try {
        const { data } = await api.get('/process-steps')
        processSteps.value = (Array.isArray(data) ? data : (data.data ?? [])).map((r: any) => ({
            id:          r.id,
            number:      r.number ?? '',
            title:       r.title ?? '',
            description: r.description ?? '',
            sort_order:  r.sort_order ?? 0,
            is_visible:  r.is_visible ?? true,
        }))
        stepsLoaded.value = true
    } catch {
        toast.add('Failed to load process steps.', 'error')
    }
}

function addStep() {
    processSteps.value.push({
        id:          nextTmpId--,
        number:      String(processSteps.value.length + 1).padStart(2, '0'),
        title:       '',
        description: '',
        sort_order:  processSteps.value.length,
        is_visible:  true,
    })
}

async function saveStep(step: ProcessStep) {
    if (!step.title.trim()) {
        toast.add('Title is required.', 'error')
        return
    }
    step._saving = true
    try {
        const payload = {
            number:      step.number,
            title:       step.title,
            description: step.description,
            sort_order:  step.sort_order,
            is_visible:  step.is_visible,
        }
        if (step.id < 0) {
            const { data } = await api.post('/process-steps', payload)
            step.id = (data.data ?? data).id
        } else {
            await api.patch(`/process-steps/${step.id}`, payload)
        }
        toast.add('Step saved.')
    } catch {
        toast.add('Failed to save step.', 'error')
    } finally {
        step._saving = false
    }
}

async function deleteStep(step: ProcessStep) {
    if (step.id > 0 && !confirm('Delete this step?')) return
    if (step.id > 0) {
        try {
            await api.delete(`/process-steps/${step.id}`)
        } catch {
            toast.add('Failed to delete step.', 'error')
            return
        }
    }
    processSteps.value = processSteps.value.filter(s => s.id !== step.id)
    // Re-number sort_order
    processSteps.value.forEach((s, i) => { s.sort_order = i })
}

function moveStep(index: number, dir: -1 | 1) {
    const target = index + dir
    if (target < 0 || target >= processSteps.value.length) return
    const a = processSteps.value[index]
    const b = processSteps.value[target]
    ;[a.sort_order, b.sort_order] = [b.sort_order, a.sort_order]
    processSteps.value.splice(index, 1)
    processSteps.value.splice(target, 0, a)
}

// ─────────────────────────────────────────────────────────────────────────────

onMounted(() => loadPage('home'))
</script>

<template>
    <div class="flex h-full min-h-screen bg-gray-50">

        <!-- ── Sidebar ──────────────────────────────────────────────────────── -->
        <aside class="w-52 shrink-0 bg-white border-r border-gray-100 py-6 hidden md:flex flex-col gap-1 px-3">
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider px-2 mb-3">Pages</p>
            <button
                v-for="p in pages" :key="p.slug"
                @click="switchPage(p.slug)"
                :class="[
                    'flex items-center gap-2.5 w-full text-left px-3 py-2.5 rounded-xl text-sm font-medium transition-all',
                    activePage === p.slug
                        ? 'bg-cyan-50 text-cyan-700 border border-cyan-200'
                        : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                ]"
            >
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" :d="pageIcons[p.slug]"/>
                </svg>
                {{ p.label }}
            </button>
        </aside>

        <!-- ── Content ──────────────────────────────────────────────────────── -->
        <div class="flex-1 min-w-0 p-6 space-y-6">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Page Content</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Editing <span class="font-semibold text-cyan-600">{{ pages.find(p => p.slug === activePage)?.label }}</span>
                    </p>
                </div>
                <button
                    @click="saveAll"
                    :disabled="saving"
                    class="inline-flex items-center gap-2 bg-cyan-500 hover:bg-cyan-600 disabled:opacity-60 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm self-start sm:self-auto"
                >
                    <template v-if="saving">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        Saving…
                    </template>
                    <template v-else-if="savedFlash">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        Saved!
                    </template>
                    <template v-else>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        Save Changes
                    </template>
                </button>
            </div>

            <!-- Mobile tab bar -->
            <div class="md:hidden border-b border-gray-200 -mx-6 px-4 overflow-x-auto">
                <nav class="flex gap-1 -mb-px">
                    <button
                        v-for="p in pages" :key="p.slug"
                        @click="switchPage(p.slug)"
                        :class="[
                            'px-4 py-3 text-sm font-medium whitespace-nowrap transition-colors border-b-2',
                            activePage === p.slug
                                ? 'border-cyan-500 text-cyan-600'
                                : 'text-gray-500 hover:text-gray-800 border-transparent'
                        ]"
                    >
                        {{ p.label }}
                    </button>
                </nav>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="flex justify-center py-20">
                <div class="w-9 h-9 border-[3px] border-cyan-500 border-t-transparent rounded-full animate-spin"></div>
            </div>

            <!-- Empty -->
            <div v-else-if="sections.length === 0" class="text-center py-20 text-gray-400 text-sm">
                No sections found for this page.
            </div>

            <!-- Sections -->
            <div v-else class="space-y-5">

                <!-- ── Process Steps (Services page only) ───────────────── -->
                <div v-if="activePage === 'services'" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="bg-gray-50 px-5 py-3 border-b border-gray-100 flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-cyan-500 shrink-0"></div>
                        <span class="text-sm font-semibold text-gray-900">Process Steps</span>
                        <span class="ml-auto">
                            <button @click="addStep" class="inline-flex items-center gap-1.5 text-xs font-semibold text-cyan-600 hover:text-cyan-700 border border-cyan-200 hover:border-cyan-400 rounded-lg px-3 py-1.5 transition-colors bg-white">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Step
                            </button>
                        </span>
                    </div>
                    <div class="p-5 space-y-3">
                        <p v-if="processSteps.length === 0" class="text-sm text-gray-400 text-center py-6">No steps yet. Click "Add Step" to create one.</p>
                        <div
                            v-for="(step, idx) in processSteps"
                            :key="step.id"
                            class="rounded-xl border border-gray-200 p-4 bg-gray-50/60"
                        >
                            <div class="flex items-start gap-3">
                                <!-- Sort controls -->
                                <div class="flex flex-col gap-1 pt-6 shrink-0">
                                    <button @click="moveStep(idx, -1)" :disabled="idx === 0" class="w-6 h-6 flex items-center justify-center rounded text-gray-400 hover:text-gray-700 hover:bg-white disabled:opacity-30 transition-colors border border-gray-200">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                    </button>
                                    <button @click="moveStep(idx, 1)" :disabled="idx === processSteps.length - 1" class="w-6 h-6 flex items-center justify-center rounded text-gray-400 hover:text-gray-700 hover:bg-white disabled:opacity-30 transition-colors border border-gray-200">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                </div>

                                <!-- Fields -->
                                <div class="flex-1 grid grid-cols-1 sm:grid-cols-[80px_1fr_2fr] gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">No.</label>
                                        <input v-model="step.number" type="text" maxlength="4" placeholder="01"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all text-center font-bold bg-white"/>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Title</label>
                                        <input v-model="step.title" type="text" placeholder="Step title"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all bg-white"/>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Description</label>
                                        <textarea v-model="step.description" rows="2" placeholder="Brief description of this step…"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all resize-none bg-white"></textarea>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex flex-col gap-2 pt-6 shrink-0">
                                    <!-- Visibility toggle -->
                                    <button @click="step.is_visible = !step.is_visible" :title="step.is_visible ? 'Visible — click to hide' : 'Hidden — click to show'"
                                        class="w-7 h-7 flex items-center justify-center rounded-lg border transition-colors"
                                        :class="step.is_visible ? 'border-cyan-200 text-cyan-600 bg-cyan-50 hover:bg-cyan-100' : 'border-gray-200 text-gray-400 bg-white hover:bg-gray-50'"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path v-if="step.is_visible" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                        </svg>
                                    </button>
                                    <!-- Save -->
                                    <button @click="saveStep(step)" :disabled="step._saving"
                                        class="w-7 h-7 flex items-center justify-center rounded-lg border border-cyan-200 text-cyan-600 bg-cyan-50 hover:bg-cyan-100 disabled:opacity-50 transition-colors"
                                        title="Save this step"
                                    >
                                        <svg v-if="step._saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                    <!-- Delete -->
                                    <button @click="deleteStep(step)"
                                        class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:border-red-200 hover:text-red-500 hover:bg-red-50 transition-colors"
                                        title="Delete this step"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ─────────────────────────────────────────────────────── -->

                <div
                    v-for="section in sections"
                    :key="section.id"
                    class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"
                >
                    <!-- Card header -->
                    <div class="bg-gray-50 px-5 py-3 border-b border-gray-100 flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-cyan-500 shrink-0"></div>
                        <span class="text-sm font-semibold text-gray-900">{{ sectionTitle(section) }}</span>
                        <span class="ml-auto text-xs text-gray-400 font-mono bg-gray-100 px-2 py-0.5 rounded">{{ section.key }}</span>
                    </div>

                    <!-- Card body -->
                    <div class="p-5 space-y-4">

                        <!-- Image upload — only for hero sections -->
                        <ImageUpload
                            v-if="section.key === 'hero'"
                            v-model="section.image_url"
                            label="Background / Feature Image"
                            height="h-48"
                        />

                        <!-- Heading + Subtext -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Heading</label>
                                <input
                                    v-model="section.heading"
                                    type="text"
                                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"
                                    placeholder="Section heading…"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Subtext</label>
                                <textarea
                                    v-model="section.subtext"
                                    rows="3"
                                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all resize-none"
                                    placeholder="Supporting text…"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Settings fields — auto-rendered per key (e.g. CTA labels) -->
                        <div v-if="settingsEntries(section).length" class="border-t border-gray-100 pt-4">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Additional Settings</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div v-for="[key, val] in settingsEntries(section)" :key="key">
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">{{ settingLabel(key) }}</label>
                                    <input
                                        :value="val"
                                        @input="updateSetting(section, key, ($event.target as HTMLInputElement).value)"
                                        type="text"
                                        class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"
                                        :placeholder="settingLabel(key)"
                                    />
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
