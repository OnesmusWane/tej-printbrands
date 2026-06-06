<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import api from "../api";
import { useToastStore } from "../stores/toast";

interface GalleryItem {
    id: number;
    image_url: string;
    category: string;
    file_size?: string | number;
    original_name?: string;
}

const toast = useToastStore();
const items = ref<GalleryItem[]>([]);
const loading = ref(false);
const uploading = ref(false);
const selectedIds = ref<Set<number>>(new Set());
const activeCategory = ref("All");
const fileInputRef = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const uploadCategory = ref("Branding");
const deletingSelected = ref(false);

const categories = ["All", "Branding", "Printing", "Graphic Design", "Signage"];

const filtered = computed(() => {
    if (activeCategory.value === "All") return items.value;
    return items.value.filter((i) => i.category === activeCategory.value);
});

function isSelected(id: number) {
    return selectedIds.value.has(id);
}

function toggleSelect(id: number) {
    const s = new Set(selectedIds.value);
    if (s.has(id)) s.delete(id);
    else s.add(id);
    selectedIds.value = s;
}

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get("/gallery-items", {
            params: { per_page: 500 },
        });
        items.value = Array.isArray(data) ? data : (data.data ?? []);
    } catch {
        toast.add("Failed to load gallery.", "error");
    } finally {
        loading.value = false;
    }
}

function triggerUpload() {
    fileInputRef.value?.click();
}

function onDragOver(e: DragEvent) {
    e.preventDefault();
    isDragging.value = true;
}
function onDragLeave() {
    isDragging.value = false;
}
function onDrop(e: DragEvent) {
    e.preventDefault();
    isDragging.value = false;
    const files = Array.from(e.dataTransfer?.files ?? []).filter((f) =>
        f.type.startsWith("image/"),
    );
    if (files.length) uploadFiles(files);
}

function onFileChange(e: Event) {
    const files = Array.from((e.target as HTMLInputElement).files ?? []);
    if (files.length) uploadFiles(files);
    if (fileInputRef.value) fileInputRef.value.value = "";
}

async function uploadFiles(files: File[]) {
    uploading.value = true;
    try {
        await Promise.all(
            files.map((file) => {
                const form = new FormData();
                form.append("image", file);
                form.append("category", uploadCategory.value);
                return api.post("/gallery-items", form, {
                    headers: { "Content-Type": "multipart/form-data" },
                });
            }),
        );
        toast.add(
            `${files.length} image${files.length > 1 ? "s" : ""} uploaded.`,
        );
        await load();
    } catch {
        toast.add("Upload failed.", "error");
    } finally {
        uploading.value = false;
    }
}

async function deleteSelected() {
    if (!selectedIds.value.size) return;
    if (
        !confirm(
            `Delete ${selectedIds.value.size} selected image${selectedIds.value.size > 1 ? "s" : ""}?`,
        )
    )
        return;
    deletingSelected.value = true;
    try {
        await Promise.all(
            Array.from(selectedIds.value).map((id) =>
                api.delete(`/gallery-items/${id}`),
            ),
        );
        items.value = items.value.filter((i) => !selectedIds.value.has(i.id));
        selectedIds.value = new Set();
        toast.add("Selected images deleted.");
    } catch {
        toast.add("Delete failed.", "error");
    } finally {
        deletingSelected.value = false;
    }
}

function formatSize(bytes: string | number | undefined): string {
    if (!bytes) return "";
    const n = Number(bytes);
    if (n >= 1024 * 1024) return `${(n / (1024 * 1024)).toFixed(1)} MB`;
    if (n >= 1024) return `${(n / 1024).toFixed(0)} KB`;
    return `${n} B`;
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
                <h1 class="text-2xl font-bold text-dark">Gallery</h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ filtered.length }}
                    {{
                        activeCategory === "All" ? "" : activeCategory + " "
                    }}images
                </p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <button
                    v-if="selectedIds.size > 0"
                    @click="deleteSelected"
                    :disabled="deletingSelected"
                    class="inline-flex items-center gap-2 bg-red-500 hover:bg-red-600 disabled:opacity-60 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete Selected ({{ selectedIds.size }})
                </button>
            </div>
        </div>

        <!-- Upload panel -->
        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <!-- Category tabs -->
            <div class="flex gap-1 p-3 border-b border-gray-100 bg-gray-50">
                <span class="text-xs font-semibold text-gray-500 self-center mr-2">Upload to:</span>
                <button
                    v-for="cat in categories.slice(1)"
                    :key="cat"
                    @click="uploadCategory = cat"
                    :class="[
                        'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors',
                        uploadCategory === cat
                            ? 'bg-cyan-500 text-white shadow-sm'
                            : 'text-gray-600 hover:bg-gray-100'
                    ]"
                >{{ cat }}</button>
            </div>

            <!-- Drop zone -->
            <div
                :class="[
                    'p-8 text-center transition-colors cursor-pointer',
                    isDragging ? 'bg-cyan-50' : 'hover:bg-gray-50',
                ]"
                @click="triggerUpload"
                @dragover="onDragOver"
                @dragleave="onDragLeave"
                @drop="onDrop"
            >
                <div v-if="uploading" class="flex flex-col items-center gap-3">
                    <div class="w-8 h-8 border-4 border-cyan-500 border-t-transparent rounded-full animate-spin"></div>
                    <p class="text-sm text-gray-500">Uploading to <span class="font-semibold text-cyan-600">{{ uploadCategory }}</span>…</p>
                </div>
                <template v-else>
                    <svg class="w-10 h-10 mx-auto mb-3" :class="isDragging ? 'text-cyan-500' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <p class="text-sm text-gray-500">
                        <span class="font-semibold text-cyan-600">Click to upload</span> or drag and drop
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                        Images will be added to <span class="font-semibold text-gray-600">{{ uploadCategory }}</span> · PNG, JPG, WebP · max 8 MB
                    </p>
                </template>
            </div>
            <input
                ref="fileInputRef"
                type="file"
                accept="image/*"
                multiple
                class="hidden"
                @change="onFileChange"
            />
        </div>

        <!-- Category filters -->
        <div class="flex gap-2 flex-wrap">
            <button
                v-for="cat in categories"
                :key="cat"
                @click="activeCategory = cat"
                :class="[
                    'px-4 py-1.5 rounded-full text-sm font-medium transition-colors',
                    activeCategory === cat
                        ? 'bg-cyan-500 text-white'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200',
                ]"
            >
                {{ cat }}
                <span class="ml-1 text-xs">
                    {{
                        cat === "All"
                            ? items.length
                            : items.filter((i) => i.category === cat).length
                    }}
                </span>
            </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-16">
            <div
                class="w-8 h-8 border-4 border-cyan-500 border-t-transparent rounded-full animate-spin"
            ></div>
        </div>

        <!-- Gallery grid -->
        <div
            v-else
            class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3"
        >
            <div
                v-for="item in filtered"
                :key="item.id"
                :class="[
                    'relative aspect-square rounded-xl overflow-hidden cursor-pointer group transition-all',
                    isSelected(item.id)
                        ? 'ring-4 ring-cyan-500 ring-offset-2'
                        : '',
                ]"
                @click="toggleSelect(item.id)"
            >
                <img
                    :src="item.image_url"
                    :alt="item.category"
                    class="w-full h-full object-cover"
                />

                <!-- Selected checkmark -->
                <div
                    v-if="isSelected(item.id)"
                    class="absolute top-2 left-2 w-6 h-6 rounded-full bg-cyan-500 flex items-center justify-center shadow-md"
                >
                    <svg
                        class="w-3.5 h-3.5 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="3"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                </div>

                <!-- Hover overlay -->
                <div
                    class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-end p-2"
                >
                    <span class="text-white text-[10px] font-semibold">{{
                        item.category
                    }}</span>
                    <span
                        v-if="item.file_size"
                        class="text-white/70 text-[10px]"
                        >{{ formatSize(item.file_size) }}</span
                    >
                </div>
            </div>

            <div
                v-if="filtered.length === 0"
                class="col-span-full text-center py-16 text-gray-400 text-sm"
            >
                No images in this category.
            </div>
        </div>
    </div>
</template>
