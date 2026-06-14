<script setup lang="ts">
import { ref, computed, onMounted, toRaw } from "vue";
import api from "../api";
import { useToastStore } from "../stores/toast";
import ImageUpload from "../components/ImageUpload.vue";

interface NestedSubService {
    title: string;
    description?: string;
    image_url?: string;
    images?: string[];
    price_type?: 'fixed' | 'from' | '';
    price?: number | null;
}

interface SubService {
    title: string;
    description?: string;
    image_url?: string;
    images?: string[];
    price_type?: 'fixed' | 'from' | '';
    price?: number | null;
    sub_services?: NestedSubService[];
}

interface Service {
    id: number;
    slug: string;
    title: string;
    description: string;
    icon: string;
    image_url: string;
    starting_price: string;
    features: string[] | string;
    sub_services?: SubService[];
    is_visible: boolean;
    sort_order: number;
}

const toast = useToastStore();
const items = ref<Service[]>([]);
const loading = ref(false);
const saving = ref(false);
const showModal = ref(false);
const editing = ref<Partial<Service>>({});
const confirmDeleteId = ref<number | null>(null);

const iconColors = [
    "bg-cyan-100 text-cyan-600",
    "bg-purple-100 text-purple-600",
    "bg-orange-100 text-orange-600",
    "bg-green-100 text-green-600",
    "bg-pink-100 text-pink-600",
    "bg-blue-100 text-blue-600",
];

function iconColor(index: number) {
    return iconColors[index % iconColors.length];
}

const featuresText = computed({
    get() {
        if (!editing.value.features) return "";
        return Array.isArray(editing.value.features)
            ? editing.value.features.join("\n")
            : editing.value.features;
    },
    set(val: string) {
        editing.value.features = val;
    },
});

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get("/services", {
            params: { per_page: 200 },
        });
        items.value = Array.isArray(data) ? data : (data.data ?? []);
    } catch {
        toast.add("Failed to load services.", "error");
    } finally {
        loading.value = false;
    }
}

function openNew() {
    editing.value = { is_visible: true, features: [], sort_order: 0, sub_services: [] };
    showModal.value = true;
}

function addSubService() {
    if (!editing.value.sub_services) editing.value.sub_services = [];
    editing.value.sub_services.push({ title: '', description: '', image_url: '', images: [], price_type: '', price: null, sub_services: [] });
}

function removeSubService(index: number) {
    editing.value.sub_services?.splice(index, 1);
}

function addNestedSubService(parentIdx: number) {
    const parent = editing.value.sub_services![parentIdx];
    if (!parent.sub_services) parent.sub_services = [];
    parent.sub_services.push({ title: '', description: '', image_url: '', images: [], price_type: '', price: null });
}

function removeNestedSubService(parentIdx: number, nestedIdx: number) {
    editing.value.sub_services![parentIdx].sub_services?.splice(nestedIdx, 1);
}

function normalizeSubServices(raw: any[]): SubService[] {
    return (raw ?? []).map(sub => {
        if (typeof sub === 'string') return { title: sub, description: '', image_url: '', images: [], price_type: '' as const, price: null, sub_services: [] };
        // Migrate: seed images[] from image_url if images is missing/empty
        const imgs: string[] = Array.isArray(sub.images) && sub.images.filter(Boolean).length
            ? sub.images.filter(Boolean)
            : (sub.image_url ? [sub.image_url] : []);
        return {
            title: sub.title ?? '',
            description: sub.description ?? '',
            image_url: imgs[0] ?? '',
            images: imgs,
            price_type: (sub.price_type ?? '') as 'fixed' | 'from' | '',
            price: sub.price != null ? Number(sub.price) : null,
            sub_services: (sub.sub_services ?? []).map((ns: any) => {
                const nsImgs: string[] = Array.isArray(ns.images) && ns.images.filter(Boolean).length
                    ? ns.images.filter(Boolean)
                    : (ns.image_url ? [ns.image_url] : []);
                return {
                    title: ns.title ?? '',
                    description: ns.description ?? '',
                    image_url: nsImgs[0] ?? '',
                    images: nsImgs,
                    price_type: (ns.price_type ?? '') as 'fixed' | 'from' | '',
                    price: ns.price != null ? Number(ns.price) : null,
                };
            }),
        };
    });
}

function computeStartingPrice(): string {
    const subs = editing.value.sub_services ?? [];
    const prices: number[] = [];
    for (const sub of subs) {
        if (sub.price != null && Number(sub.price) > 0) prices.push(Number(sub.price));
        for (const ns of sub.sub_services ?? []) {
            if (ns.price != null && Number(ns.price) > 0) prices.push(Number(ns.price));
        }
    }
    if (prices.length === 0) return editing.value.starting_price ?? '';
    return `From KES ${Math.min(...prices).toLocaleString()}`;
}

function openEdit(s: Service) {
    editing.value = {
        ...s,
        features: Array.isArray(s.features)
            ? s.features.join("\n")
            : (s.features ?? ""),
        sub_services: normalizeSubServices(s.sub_services as any ?? []),
    };
    showModal.value = true;
}

async function toggleVisible(s: Service) {
    try {
        await api.patch(`/services/${s.id}`, { is_visible: !s.is_visible });
        s.is_visible = !s.is_visible;
        toast.add(s.is_visible ? "Service shown." : "Service hidden.");
    } catch {
        toast.add("Update failed.", "error");
    }
}

async function submit() {
    saving.value = true;
    try {
        const featuresArr =
            typeof editing.value.features === "string"
                ? editing.value.features
                      .split("\n")
                      .map((f) => f.trim())
                      .filter(Boolean)
                : (editing.value.features ?? []);
        const rawSubs = (editing.value.sub_services ?? []).map(s => toRaw(s));
        const autoPrice = computeStartingPrice();
        const payload = { ...editing.value, features: featuresArr, sub_services: rawSubs, starting_price: autoPrice };
        if (editing.value.id) {
            await api.patch(`/services/${editing.value.id}`, payload as any);
            toast.add("Service updated.");
        } else {
            await api.post("/services", payload as any);
            toast.add("Service created.");
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
    if (!confirm("Delete this service?")) return;
    try {
        await api.delete(`/services/${id}`);
        items.value = items.value.filter((i) => i.id !== id);
        toast.add("Service deleted.");
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
                <h1 class="text-2xl font-bold text-dark">Services</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Manage your service offerings.
                </p>
            </div>
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
                Add Service
            </button>
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
            class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-5"
        >
            <div
                v-for="(service, index) in items"
                :key="service.id"
                :class="[
                    'bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden flex flex-col transition-all',
                    !service.is_visible && 'opacity-60',
                ]"
            >
                <!-- Image area -->
                <div class="relative h-40 bg-gray-100">
                    <img
                        v-if="service.image_url"
                        :src="service.image_url"
                        :alt="service.title"
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
                    <!-- Eye toggle -->
                    <button
                        @click="toggleVisible(service)"
                        class="absolute top-2 right-2 w-8 h-8 rounded-full bg-white/90 shadow flex items-center justify-center hover:bg-white transition-colors"
                        :title="
                            service.is_visible ? 'Hide service' : 'Show service'
                        "
                    >
                        <svg
                            v-if="service.is_visible"
                            class="w-4 h-4 text-gray-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                            />
                        </svg>
                        <svg
                            v-else
                            class="w-4 h-4 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                            />
                        </svg>
                    </button>
                    <!-- Icon circle -->
                    <div
                        :class="[
                            'absolute bottom-3 left-3 w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold shadow',
                            iconColor(index),
                        ]"
                    >
                        {{
                            service.icon
                                ? service.icon.charAt(0).toUpperCase()
                                : service.title.charAt(0)
                        }}
                    </div>
                </div>

                <!-- Content -->
                <div class="p-4 flex flex-col flex-1">
                    <div class="flex items-start justify-between gap-2 mb-1">
                        <h3
                            class="font-semibold text-dark text-sm leading-snug"
                        >
                            {{ service.title }}
                        </h3>
                        <span
                            v-if="service.starting_price"
                            class="text-xs font-semibold text-cyan-600 whitespace-nowrap"
                            >{{ service.starting_price }}</span
                        >
                    </div>
                    <p
                        class="text-xs text-gray-500 leading-relaxed line-clamp-2 mb-3"
                    >
                        {{ service.description }}
                    </p>

                    <!-- Sub-service chips -->
                    <div
                        v-if="
                            service.sub_services && service.sub_services.length
                        "
                        class="flex flex-wrap gap-1 mb-3"
                    >
                        <span
                            v-for="sub in service.sub_services.slice(0, 3)"
                            :key="sub.title"
                            class="text-[10px] bg-gray-100 text-gray-600 rounded-full px-2 py-0.5"
                            >{{ sub.title }}</span
                        >
                        <span
                            v-if="service.sub_services.length > 3"
                            class="text-[10px] bg-gray-100 text-gray-500 rounded-full px-2 py-0.5"
                        >
                            +{{ service.sub_services.length - 3 }}
                        </span>
                    </div>

                    <!-- Footer actions -->
                    <div
                        class="mt-auto pt-3 border-t border-gray-100 flex gap-2"
                    >
                        <button
                            @click="openEdit(service)"
                            class="flex-1 text-xs font-semibold text-gray-600 border border-gray-200 rounded-lg py-1.5 hover:bg-gray-50 transition-colors"
                        >
                            Edit
                        </button>
                        <button
                            @click="remove(service.id)"
                            class="flex-1 text-xs font-semibold text-red-500 border border-red-100 rounded-lg py-1.5 hover:bg-red-50 transition-colors"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>

            <div
                v-if="items.length === 0"
                class="col-span-full text-center py-16 text-gray-400 text-sm"
            >
                No services yet. Add your first service.
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
                                    editing.id ? "Edit Service" : "Add Service"
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
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Title <span class="text-red-400">*</span></label>
                                <input
                                    v-model="editing.title"
                                    required
                                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-semibold text-gray-600 mb-1.5"
                                    >Description
                                    <span class="text-red-400">*</span></label
                                >
                                <textarea
                                    v-model="editing.description"
                                    rows="3"
                                    required
                                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all resize-none"
                                ></textarea>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <ImageUpload
                                        v-model="editing.image_url"
                                        label="Service Image"
                                        height="h-36"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-gray-600 mb-1.5"
                                        >Icon Keyword</label
                                    >
                                    <input
                                        v-model="editing.icon"
                                        placeholder="e.g. palette"
                                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"
                                    />
                                </div>
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-semibold text-gray-600 mb-1.5"
                                    >Features
                                    <span class="text-gray-400 font-normal"
                                        >(one per line)</span
                                    ></label
                                >
                                <textarea
                                    v-model="featuresText"
                                    rows="4"
                                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all resize-none"
                                    placeholder="Feature one&#10;Feature two&#10;Feature three"
                                ></textarea>
                            </div>
                            <!-- Sub-services -->
                            <div class="border border-gray-200 rounded-xl p-4 space-y-4">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-semibold text-gray-600">Sub-services</label>
                                    <button type="button" @click="addSubService" class="inline-flex items-center gap-1 text-xs font-semibold text-cyan-600 hover:text-cyan-700">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                        Add Sub-service
                                    </button>
                                </div>
                                <div v-if="!editing.sub_services?.length" class="text-xs text-gray-400 text-center py-2">No sub-services yet — click Add Sub-service.</div>
                                <div v-for="(sub, idx) in editing.sub_services" :key="idx" class="rounded-lg border border-gray-100 bg-gray-50 p-3 space-y-2">
                                    <!-- Title + delete -->
                                    <div class="flex items-center gap-2">
                                        <input v-model="sub.title" placeholder="Title *" required class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500/20" />
                                        <button type="button" @click="removeSubService(idx)" class="text-red-400 hover:text-red-600 p-1 shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                    <!-- Description -->
                                    <input v-model="sub.description" placeholder="Short description (optional)" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500/20" />
                                    <!-- Price type + amount -->
                                    <div class="flex gap-2">
                                        <select v-model="sub.price_type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500/20 w-36">
                                            <option value="">No price</option>
                                            <option value="fixed">Fixed (KES X)</option>
                                            <option value="from">From (From KES X)</option>
                                        </select>
                                        <input
                                            v-if="sub.price_type"
                                            v-model.number="sub.price"
                                            type="number"
                                            min="0"
                                            placeholder="e.g. 5000"
                                            class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500/20"
                                        />
                                    </div>
                                    <!-- Images (multiple) -->
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-medium text-gray-500">Images</span>
                                            <button type="button"
                                                    @click="() => { if (!sub.images) sub.images = []; sub.images.push('') }"
                                                    class="inline-flex items-center gap-1 text-xs font-semibold text-cyan-600 hover:text-cyan-700">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                                Add Image
                                            </button>
                                        </div>
                                        <p v-if="!sub.images?.length" class="text-[11px] text-gray-400 text-center py-2 border border-dashed border-gray-200 rounded-lg">No images yet.</p>
                                        <div v-for="(_, imgIdx) in sub.images" :key="imgIdx" class="relative">
                                            <ImageUpload
                                                :modelValue="sub.images![imgIdx]"
                                                @update:modelValue="(url: string) => { if (sub.images) sub.images[imgIdx] = url }"
                                                :label="imgIdx === 0 ? 'Cover image' : `Image ${imgIdx + 1}`"
                                                height="h-24"
                                            />
                                            <button type="button"
                                                    @click="sub.images?.splice(imgIdx, 1)"
                                                    class="absolute top-2 right-2 z-10 w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center text-sm leading-none shadow-md">
                                                &times;
                                            </button>
                                        </div>
                                    </div>
                                    <!-- Nested sub-services -->
                                    <div class="border border-gray-200 rounded-lg bg-white p-2 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-[11px] font-semibold text-gray-500">Nested Sub-services</span>
                                            <button type="button" @click="addNestedSubService(idx)" class="inline-flex items-center gap-1 text-[11px] font-semibold text-cyan-600 hover:text-cyan-700">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                                Add Nested
                                            </button>
                                        </div>
                                        <div v-if="!sub.sub_services?.length" class="text-[11px] text-gray-400 text-center py-1">None yet.</div>
                                        <div v-for="(ns, nIdx) in sub.sub_services" :key="nIdx" class="rounded border border-gray-100 bg-gray-50 p-2 space-y-1.5">
                                            <div class="flex items-center gap-2">
                                                <input v-model="ns.title" placeholder="Nested title *" class="flex-1 border border-gray-300 rounded px-2 py-1.5 text-xs outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500/20" />
                                                <button type="button" @click="removeNestedSubService(idx, nIdx)" class="text-red-400 hover:text-red-600 p-0.5 shrink-0">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>
                                            <input v-model="ns.description" placeholder="Description (optional)" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500/20" />
                                            <div class="flex gap-2">
                                                <select v-model="ns.price_type" class="border border-gray-300 rounded px-2 py-1.5 text-xs outline-none focus:border-cyan-500 w-32">
                                                    <option value="">No price</option>
                                                    <option value="fixed">Fixed</option>
                                                    <option value="from">From</option>
                                                </select>
                                                <input
                                                    v-if="ns.price_type"
                                                    v-model.number="ns.price"
                                                    type="number"
                                                    min="0"
                                                    placeholder="e.g. 2000"
                                                    class="flex-1 border border-gray-300 rounded px-2 py-1.5 text-xs outline-none focus:border-cyan-500"
                                                />
                                            </div>
                                            <!-- Nested images (multiple) -->
                                            <div class="space-y-1.5">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[10px] font-medium text-gray-400">Images</span>
                                                    <button type="button"
                                                            @click="() => { if (!ns.images) ns.images = []; ns.images.push('') }"
                                                            class="inline-flex items-center gap-0.5 text-[10px] font-semibold text-cyan-600 hover:text-cyan-700">
                                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                                        Add
                                                    </button>
                                                </div>
                                                <p v-if="!ns.images?.length" class="text-[10px] text-gray-400 text-center py-1 border border-dashed border-gray-200 rounded">No images.</p>
                                                <div v-for="(_, nsImgIdx) in ns.images" :key="nsImgIdx" class="relative">
                                                    <ImageUpload
                                                        :modelValue="ns.images![nsImgIdx]"
                                                        @update:modelValue="(url: string) => { if (ns.images) ns.images[nsImgIdx] = url }"
                                                        :label="nsImgIdx === 0 ? 'Cover' : `Img ${nsImgIdx + 1}`"
                                                        height="h-16"
                                                    />
                                                    <button type="button"
                                                            @click="ns.images?.splice(nsImgIdx, 1)"
                                                            class="absolute top-1.5 right-1.5 z-10 w-5 h-5 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center text-xs leading-none shadow">
                                                        &times;
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <input
                                    type="checkbox"
                                    id="svc-visible"
                                    v-model="editing.is_visible"
                                    class="w-4 h-4 rounded border-gray-300 text-cyan-500 focus:ring-cyan-500"
                                />
                                <label
                                    for="svc-visible"
                                    class="text-sm text-gray-700"
                                    >Visible on site</label
                                >
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
