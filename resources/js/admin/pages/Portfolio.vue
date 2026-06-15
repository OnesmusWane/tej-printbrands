<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import api from "../api";
import { useToastStore } from "../stores/toast";
import ImageUpload from "../components/ImageUpload.vue";

interface Project {
    id: number;
    title: string;
    category: string;
    client: string;
    image_url: string;
    gallery: string[];
    description: string;
    project_date: string;
    services: string[];
    is_featured: boolean;
    is_case_study: boolean;
    is_visible: boolean;
}

const toast = useToastStore();
const items = ref<Project[]>([]);
const loading = ref(false);
const saving = ref(false);
const showModal = ref(false);
const editing = ref<Partial<Project> & { gallery: string[] }>({ gallery: [] });
const searchQuery = ref("");

const categories = [
    "Branding",
    "Printing",
    "Graphic Design",
    "Signage",
    "Digital",
    "Photography",
];

const filtered = computed(() => {
    if (!searchQuery.value) return items.value;
    const q = searchQuery.value.toLowerCase();
    return items.value.filter(
        (p) =>
            p.title.toLowerCase().includes(q) ||
            p.category.toLowerCase().includes(q) ||
            (p.client ?? "").toLowerCase().includes(q),
    );
});

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get("/portfolio-projects", {
            params: { per_page: 200 },
        });
        items.value = Array.isArray(data) ? data : (data.data ?? []);
    } catch {
        toast.add("Failed to load portfolio.", "error");
    } finally {
        loading.value = false;
    }
}

function openNew() {
    editing.value = {
        is_featured: false,
        is_case_study: false,
        is_visible: true,
        category: categories[0],
        gallery: [],
        services: [],
    };
    showModal.value = true;
}

function openEdit(p: Project) {
    editing.value = {
        ...p,
        gallery: Array.isArray(p.gallery) ? p.gallery.filter(Boolean) : [],
        services: Array.isArray(p.services) ? p.services : [],
    };
    showModal.value = true;
}

function addGallerySlot() {
    editing.value.gallery.push('');
}

function removeGallery(idx: number) {
    editing.value.gallery.splice(idx, 1);
}

async function submit() {
    saving.value = true;
    try {
        const payload = {
            ...editing.value,
            gallery: editing.value.gallery.filter(Boolean),
            services: typeof editing.value.services === 'string'
                ? (editing.value.services as any as string).split('\n').map((s: string) => s.trim()).filter(Boolean)
                : (editing.value.services ?? []),
        };
        if (editing.value.id) {
            await api.patch(`/portfolio-projects/${editing.value.id}`, payload as any);
            toast.add("Project updated.");
        } else {
            await api.post("/portfolio-projects", payload as any);
            toast.add("Project created.");
        }
        showModal.value = false;
        await load();
    } catch (err: any) {
        toast.add(err?.response?.data?.message ?? "Save failed.", "error");
    } finally {
        saving.value = false;
    }
}

async function remove(id: number) {
    if (!confirm("Delete this project?")) return;
    try {
        await api.delete(`/portfolio-projects/${id}`);
        items.value = items.value.filter((i) => i.id !== id);
        toast.add("Project deleted.");
    } catch {
        toast.add("Delete failed.", "error");
    }
}

onMounted(load);
</script>

<template>
    <div class="p-6 space-y-6">
        <!-- Header -->
        <div
            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
        >
            <div>
                <h1 class="text-2xl font-bold text-dark">Portfolio</h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ items.length }} projects
                </p>
            </div>
            <div class="flex gap-3">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search projects…"
                    class="border border-gray-300 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"
                />
                <button
                    @click="openNew"
                    class="inline-flex items-center gap-2 bg-cyan-500 hover:bg-cyan-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors shadow-sm"
                >
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>
                    Add Project
                </button>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-16">
            <div
                class="w-8 h-8 border-4 border-cyan-500 border-t-transparent rounded-full animate-spin"
            ></div>
        </div>

        <!-- Grid -->
        <div
            v-else
            class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5"
        >
            <div
                v-for="project in filtered"
                :key="project.id"
                class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden group"
            >
                <!-- Image with hover overlay -->
                <div class="relative aspect-[4/3] bg-gray-100">
                    <img
                        v-if="project.image_url"
                        :src="project.image_url"
                        :alt="project.title"
                        class="w-full h-full object-cover"
                    />
                    <div
                        v-else
                        class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200"
                    >
                        <svg
                            class="w-12 h-12 text-gray-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                    </div>
                    <!-- Gallery count badge -->
                    <div v-if="(project.gallery?.filter(Boolean).length ?? 0) > 0"
                         class="absolute bottom-2 right-2 flex items-center gap-1 rounded-full bg-black/60 px-2 py-0.5 text-[10px] font-bold text-white backdrop-blur-sm">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        {{ project.gallery.filter(Boolean).length }}
                    </div>

                    <!-- Hover overlay with actions -->
                    <div
                        class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3"
                    >
                        <button
                            @click="openEdit(project)"
                            class="w-10 h-10 rounded-full bg-white flex items-center justify-center shadow-md hover:bg-cyan-50 transition-colors"
                            title="Edit"
                        >
                            <svg
                                class="w-4 h-4 text-gray-700"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                />
                            </svg>
                        </button>
                        <button
                            @click="remove(project.id)"
                            class="w-10 h-10 rounded-full bg-white flex items-center justify-center shadow-md hover:bg-red-50 transition-colors"
                            title="Delete"
                        >
                            <svg
                                class="w-4 h-4 text-red-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                />
                            </svg>
                        </button>
                    </div>

                    <!-- Featured badge -->
                    <div
                        v-if="project.is_featured"
                        class="absolute top-2 left-2 bg-yellow-400 text-yellow-900 text-[10px] font-bold px-2 py-0.5 rounded-full"
                    >
                        Featured
                    </div>
                </div>

                <!-- Card footer -->
                <div class="p-4">
                    <div class="flex items-start justify-between gap-2 mb-1">
                        <h3
                            class="font-semibold text-dark text-sm leading-snug"
                        >
                            {{ project.title }}
                        </h3>
                        <span
                            class="text-[10px] font-semibold bg-cyan-50 text-cyan-600 px-2 py-0.5 rounded-full whitespace-nowrap flex-shrink-0"
                            >{{ project.category }}</span
                        >
                    </div>
                    <p v-if="project.client" class="text-xs text-gray-500">
                        {{ project.client }}
                    </p>
                </div>
            </div>

            <div
                v-if="filtered.length === 0"
                class="col-span-full text-center py-16 text-gray-400 text-sm"
            >
                {{
                    searchQuery
                        ? "No projects match your search."
                        : "No projects yet. Add your first project."
                }}
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                    @click.self="showModal = false"
                >
                    <div
                        class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-100 px-6 py-4"
                        >
                            <h2 class="text-lg font-bold text-dark">
                                {{
                                    editing.id ? "Edit Project" : "Add Project"
                                }}
                            </h2>
                            <button
                                @click="showModal = false"
                                class="text-gray-400 hover:text-gray-600 text-xl leading-none"
                            >
                                &times;
                            </button>
                        </div>
                        <form
                            @submit.prevent="submit"
                            class="px-6 py-5 space-y-4"
                        >
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-gray-600 mb-1.5"
                                        >Title
                                        <span class="text-red-400"
                                            >*</span
                                        ></label
                                    >
                                    <input
                                        v-model="editing.title"
                                        required
                                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-gray-600 mb-1.5"
                                        >Category</label
                                    >
                                    <select
                                        v-model="editing.category"
                                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all bg-white"
                                    >
                                        <option
                                            v-for="cat in categories"
                                            :key="cat"
                                            :value="cat"
                                        >
                                            {{ cat }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-gray-600 mb-1.5"
                                        >Client</label
                                    >
                                    <input
                                        v-model="editing.client"
                                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-gray-600 mb-1.5"
                                        >Project Date</label
                                    >
                                    <input
                                        v-model="editing.project_date"
                                        type="date"
                                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"
                                    />
                                </div>
                            </div>
                            <!-- Cover image -->
                            <ImageUpload
                                v-model="editing.image_url"
                                label="Cover Image"
                                height="h-40"
                            />

                            <!-- Gallery images -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-xs font-semibold text-gray-600">
                                        Project Gallery
                                        <span class="ml-1 text-gray-400 font-normal">(shown in modal)</span>
                                    </label>
                                    <button type="button" @click="addGallerySlot"
                                            class="text-xs font-medium flex items-center gap-1 text-cyan-500 hover:text-cyan-600">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        Add Image
                                    </button>
                                </div>

                                <div v-if="editing.gallery.length === 0"
                                     class="rounded-xl border-2 border-dashed border-gray-200 py-6 text-center">
                                    <svg class="w-7 h-7 text-gray-300 mx-auto mb-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <p class="text-xs text-gray-400">No gallery images yet.</p>
                                    <button type="button" @click="addGallerySlot"
                                            class="mt-1.5 text-xs font-medium text-cyan-500 hover:text-cyan-600">
                                        + Add first image
                                    </button>
                                </div>

                                <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                    <div v-for="(img, idx) in editing.gallery" :key="idx" class="relative">
                                        <div class="absolute -top-1.5 left-1 z-10">
                                            <span class="rounded-full bg-white border border-gray-200 px-1.5 py-0.5 text-[9px] font-bold text-gray-500 leading-none shadow-sm">
                                                #{{ idx + 1 }}
                                            </span>
                                        </div>
                                        <ImageUpload
                                            :modelValue="img"
                                            @update:modelValue="editing.gallery[idx] = $event"
                                            height="h-24"
                                        />
                                        <button type="button" @click="removeGallery(idx)"
                                                class="absolute top-1 right-1 z-10 w-5 h-5 rounded-full bg-red-500 text-white text-xs flex items-center justify-center hover:bg-red-600 leading-none">
                                            ×
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Description</label>
                                <textarea
                                    v-model="editing.description"
                                    rows="3"
                                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all resize-none"
                                ></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                                    Services Provided
                                    <span class="font-normal text-gray-400 ml-1">(one per line)</span>
                                </label>
                                <textarea
                                    :value="Array.isArray(editing.services) ? editing.services.join('\n') : editing.services"
                                    @input="e => editing.services = (e.target as HTMLTextAreaElement).value as any"
                                    rows="3"
                                    placeholder="e.g. Logo Design&#10;Business Cards&#10;Signage"
                                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all resize-none"
                                ></textarea>
                            </div>
                            <div class="flex flex-wrap items-center gap-6">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" id="proj-featured" v-model="editing.is_featured"
                                           class="w-4 h-4 rounded border-gray-300 text-cyan-500 focus:ring-cyan-500"/>
                                    <label for="proj-featured" class="text-sm text-gray-700">Featured</label>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" id="proj-case" v-model="editing.is_case_study"
                                           class="w-4 h-4 rounded border-gray-300 text-cyan-500 focus:ring-cyan-500"/>
                                    <label for="proj-case" class="text-sm text-gray-700">Case Study</label>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" id="proj-visible" v-model="editing.is_visible"
                                           class="w-4 h-4 rounded border-gray-300 text-cyan-500 focus:ring-cyan-500"/>
                                    <label for="proj-visible" class="text-sm text-gray-700">Visible</label>
                                </div>
                            </div>
                            <div
                                class="flex justify-end gap-3 pt-2 border-t border-gray-100"
                            >
                                <button
                                    type="button"
                                    @click="showModal = false"
                                    class="px-4 py-2 text-sm font-semibold text-gray-600 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="saving"
                                    class="px-5 py-2 text-sm font-semibold bg-cyan-500 hover:bg-cyan-600 disabled:opacity-60 text-white rounded-xl transition-colors"
                                >
                                    {{
                                        saving
                                            ? "Saving…"
                                            : editing.id
                                              ? "Update"
                                              : "Create"
                                    }}
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
.modal-enter-active,
.modal-leave-active {
    transition: all 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
    transform: scale(0.97);
}
</style>
