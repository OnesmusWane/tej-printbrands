<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import api from "../api";

interface QItem {
    id: number;
    description: string;
    quantity: number;
    unit_price: number;
    total: number;
}
interface Quotation {
    id: number;
    quote_number: string;
    client: string;
    email: string;
    service?: string;
    subtotal: number;
    tax: number;
    total: number;
    status: string;
    terms?: string;
    sent_at?: string;
    created_at: string;
    items?: QItem[];
}
interface QuoteRequest {
    id: number;
    request_number?: string;
    name: string;
    email: string;
    phone?: string;
    product?: string;
    quantity?: string;
    size?: string;
    delivery_method?: string;
    notes?: string;
    status: string;
    created_at: string;
}
interface FormItem {
    description: string;
    qty: number;
    unit_price: number;
}

const loading = ref(true);
const saving = ref(false);
const quotations = ref<Quotation[]>([]);
const requests = ref<QuoteRequest[]>([]);
const search = ref("");
const statusFilter = ref("");
const currentPage = ref(1);
const activeTab = ref<"quotations" | "requests">("quotations");
const perPage = 10;

// ─── create modal ──────────────────────────────────────────────────────────
const showCreate = ref(false);
const createError = ref("");
const defaultItem = (): FormItem => ({
    description: "",
    qty: 1,
    unit_price: 0,
});
const form = ref({
    client: "",
    email: "",
    service: "",
    terms: "",
    quote_request_id: null as number | null,
    items: [defaultItem()] as FormItem[],
});

const subtotal = computed(() =>
    form.value.items.reduce((s, i) => s + i.qty * i.unit_price, 0),
);
const vat = computed(() => Math.round(subtotal.value * 0.16));
const total = computed(() => subtotal.value + vat.value);

function addItem() {
    form.value.items.push(defaultItem());
}
function removeItem(i: number) {
    form.value.items.splice(i, 1);
}

function openCreate() {
    form.value = {
        client: "",
        email: "",
        service: "",
        terms: "",
        quote_request_id: null,
        items: [defaultItem()],
    };
    createError.value = "";
    showCreate.value = true;
}

async function submitCreate(status: string) {
    if (!form.value.client || !form.value.email) {
        createError.value = "Client name and email are required.";
        return;
    }
    saving.value = true;
    createError.value = "";
    try {
        const payload: Record<string, any> = {
            client: form.value.client,
            email: form.value.email,
            service: form.value.service,
            terms: form.value.terms,
            status,
            items: form.value.items.map((i) => ({
                description: i.description,
                quantity: i.qty,
                unit_price: i.unit_price,
            })),
        };
        if (form.value.quote_request_id) {
            payload.quote_request_id = form.value.quote_request_id;
        }
        const { data } = await api.post("/quotations", payload);
        quotations.value.unshift(data.data ?? data);

        // Auto-update quote request status to 'quoted' locally
        if (form.value.quote_request_id) {
            const idx = requests.value.findIndex(r => r.id === form.value.quote_request_id);
            if (idx !== -1) requests.value[idx] = { ...requests.value[idx], status: 'quoted' };
        }

        showCreate.value = false;
    } catch (e: any) {
        createError.value =
            e.response?.data?.message ??
            Object.values(e.response?.data?.errors ?? {})?.[0]?.[0] ??
            "Failed.";
    } finally {
        saving.value = false;
    }
}

// ─── view / print modal ────────────────────────────────────────────────────
const showView = ref(false);
const viewLoading = ref(false);
const viewQ = ref<Quotation | null>(null);

async function openView(q: Quotation) {
    showView.value = true;
    viewLoading.value = true;
    viewQ.value = q;
    try {
        const { data } = await api.get(`/quotations/${q.id}`);
        viewQ.value = data.data ?? data;
    } catch (e) {
        console.error(e);
    } finally {
        viewLoading.value = false;
    }
}

function printQuotation() {
    if (!viewQ.value) return;
    const q = viewQ.value;
    const items = (q.items ?? [])
        .map(
            (i) => `
    <tr>
      <td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;">${i.description}</td>
      <td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;text-align:center;">${i.quantity}</td>
      <td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;text-align:right;">KES ${Number(i.unit_price).toLocaleString()}</td>
      <td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;text-align:right;">KES ${Number(i.total).toLocaleString()}</td>
    </tr>`,
        )
        .join("");
    const html = `<!DOCTYPE html><html><head><title>Quotation ${q.quote_number}</title>
  <style>body{font-family:Arial,sans-serif;color:#1F2937;margin:0;padding:32px;}
  table{width:100%;border-collapse:collapse;}th{background:#f9fafb;padding:10px 12px;text-align:left;font-size:12px;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;}
  .header{border-bottom:3px solid #00BCD4;padding-bottom:20px;margin-bottom:24px;}
  .company{font-size:22px;font-weight:700;color:#00BCD4;}.totals td{padding:6px 12px;}
  </style></head><body>
  <div class="header">
    <div class="company">Tej Printbrands</div>
    <div style="font-size:12px;color:#6B7280;margin-top:4px;">Professional Print & Branding Solutions</div>
  </div>
  <div style="display:flex;justify-content:space-between;margin-bottom:24px;">
    <div>
      <div style="font-size:24px;font-weight:700;color:#1F2937;">QUOTATION</div>
      <div style="font-size:13px;color:#6B7280;margin-top:4px;"># ${q.quote_number}</div>
    </div>
    <div style="text-align:right;font-size:13px;">
      <div><strong>Date:</strong> ${new Date(q.created_at).toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" })}</div>
      <div style="margin-top:4px;"><strong>Status:</strong> ${q.status.charAt(0).toUpperCase() + q.status.slice(1)}</div>
    </div>
  </div>
  <div style="margin-bottom:24px;background:#f9fafb;padding:16px;border-radius:8px;">
    <div style="font-size:11px;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">Bill To</div>
    <div style="font-weight:600;">${q.client}</div>
    <div style="color:#6B7280;">${q.email}</div>
    ${q.service ? `<div style="color:#6B7280;margin-top:4px;">Service: ${q.service}</div>` : ""}
  </div>
  <table style="margin-bottom:24px;">
    <thead><tr><th>Description</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Unit Price</th><th style="text-align:right;">Total</th></tr></thead>
    <tbody>${items}</tbody>
  </table>
  <div style="display:flex;justify-content:flex-end;margin-bottom:24px;">
    <table style="width:260px;" class="totals">
      <tr><td>Subtotal</td><td style="text-align:right;">KES ${Number(q.subtotal).toLocaleString()}</td></tr>
      <tr><td>VAT (16%)</td><td style="text-align:right;">KES ${Number(q.tax).toLocaleString()}</td></tr>
      <tr style="font-weight:700;border-top:2px solid #1F2937;"><td style="padding-top:8px;">Total</td><td style="text-align:right;padding-top:8px;color:#00BCD4;">KES ${Number(q.total).toLocaleString()}</td></tr>
    </table>
  </div>
  ${q.terms ? `<div style="font-size:12px;color:#6B7280;border-top:1px solid #f0f0f0;padding-top:16px;"><strong>Terms & Conditions:</strong><br>${q.terms}</div>` : ""}
  </body></html>`;
    const win = window.open("", "_blank");
    if (win) {
        win.document.write(html);
        win.document.close();
        win.print();
    }
}

// ─── status actions ────────────────────────────────────────────────────────
async function updateStatus(q: Quotation, status: string) {
    try {
        const { data } = await api.patch(`/quotations/${q.id}`, { status });
        const idx = quotations.value.findIndex((x) => x.id === q.id);
        if (idx !== -1)
            quotations.value[idx] = {
                ...quotations.value[idx],
                ...(data.data ?? data),
            };
        if (viewQ.value?.id === q.id) viewQ.value = { ...viewQ.value, status };
    } catch (e) {
        console.error(e);
    }
}

async function deleteQuotation(id: number) {
    if (!confirm("Delete this quotation?")) return;
    try {
        await api.delete(`/quotations/${id}`);
        quotations.value = quotations.value.filter((q) => q.id !== id);
    } catch (e) {
        console.error(e);
    }
}

// ─── helpers ───────────────────────────────────────────────────────────────
const filtered = computed(() => {
    let list = quotations.value;
    if (statusFilter.value)
        list = list.filter((q) => q.status === statusFilter.value);
    if (search.value.trim()) {
        const s = search.value.toLowerCase();
        list = list.filter(
            (q) =>
                q.client.toLowerCase().includes(s) ||
                q.quote_number.toLowerCase().includes(s) ||
                (q.email ?? "").toLowerCase().includes(s),
        );
    }
    return list;
});
const totalPages = computed(() =>
    Math.max(1, Math.ceil(filtered.value.length / perPage)),
);
const paginated = computed(() =>
    filtered.value.slice(
        (currentPage.value - 1) * perPage,
        currentPage.value * perPage,
    ),
);

function countStatus(s: string) {
    return quotations.value.filter((q) => q.status === s).length;
}
function statusClass(s: string) {
    const m: Record<string, string> = {
        approved: "bg-green-100 text-green-700 border-green-200",
        pending: "bg-orange-100 text-orange-700 border-orange-200",
        rejected: "bg-red-100 text-red-700 border-red-200",
        draft: "bg-gray-100 text-gray-600 border-gray-200",
    };
    return m[s] ?? "bg-gray-100 text-gray-700 border-gray-200";
}
function capitalize(s: string) {
    return s ? s.charAt(0).toUpperCase() + s.slice(1) : "";
}
function fmt(v: any) {
    return Number(v ?? 0).toLocaleString();
}
function fmtDate(d?: string) {
    if (!d) return "";
    return new Date(d).toLocaleDateString("en-GB", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
}

function reqStatusClass(s: string) {
    const m: Record<string, string> = {
        new:      "bg-blue-100 text-blue-700 border-blue-200",
        quoted:   "bg-purple-100 text-purple-700 border-purple-200",
        rejected: "bg-red-100 text-red-700 border-red-200",
    };
    return m[s] ?? "bg-gray-100 text-gray-700 border-gray-200";
}

// ─── quote request status update ──────────────────────────────────────────────
const updatingReqId = ref<number | null>(null);

async function updateReqStatus(r: QuoteRequest, status: string) {
    updatingReqId.value = r.id;
    try {
        const { data } = await api.patch(`/quote-requests/${r.id}/status`, { status });
        const idx = requests.value.findIndex(x => x.id === r.id);
        if (idx !== -1) requests.value[idx] = { ...requests.value[idx], ...(data.data ?? data) };
    } catch (e) { console.error(e); }
    finally { updatingReqId.value = null; }
}

function createQuoteFromRequest(r: QuoteRequest) {
    form.value = {
        client: r.name,
        email: r.email,
        service: r.product ?? "",
        terms: "",
        quote_request_id: r.id,
        items: [
            {
                description: r.product ?? "Service",
                qty: Number(r.quantity ?? 1) || 1,
                unit_price: 0,
            },
        ],
    };
    createError.value = "";
    showCreate.value = true;
}

onMounted(async () => {
    try {
        const [qRes, rRes] = await Promise.all([
            api.get("/quotations"),
            api.get("/quote-requests"),
        ]);
        quotations.value = Array.isArray(qRes.data)
            ? qRes.data
            : (qRes.data.data ?? []);
        requests.value = Array.isArray(rRes.data)
            ? rRes.data
            : (rRes.data.data ?? []);
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div
            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
        >
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Quotations</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Create and manage client quotations
                </p>
            </div>
            <button
                @click="openCreate"
                class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white shadow-sm self-start sm:self-auto"
                style="background: #00bcd4"
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
                Create Quotation
            </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center items-center py-20">
            <div
                class="w-8 h-8 border-4 border-t-transparent rounded-full animate-spin"
                style="border-color: #00bcd4; border-top-color: transparent"
            ></div>
        </div>

        <template v-else>
            <!-- Stat Cards -->
            <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
                <div
                    class="bg-white rounded-xl shadow-sm border border-gray-100 p-5"
                >
                    <div class="text-2xl font-bold text-gray-900">
                        {{ quotations.length }}
                    </div>
                    <div class="text-sm text-gray-500 mt-1">Total Quotes</div>
                </div>
                <div
                    class="bg-white rounded-xl shadow-sm border border-gray-100 p-5"
                >
                    <div class="text-2xl font-bold text-orange-600">
                        {{ countStatus("pending") }}
                    </div>
                    <div class="text-sm text-gray-500 mt-1">Pending</div>
                </div>
                <div
                    class="bg-white rounded-xl shadow-sm border border-gray-100 p-5"
                >
                    <div class="text-2xl font-bold text-green-600">
                        {{ countStatus("approved") }}
                    </div>
                    <div class="text-sm text-gray-500 mt-1">Approved</div>
                </div>
                <div
                    class="bg-white rounded-xl shadow-sm border border-gray-100 p-5"
                >
                    <div class="text-2xl font-bold text-red-600">
                        {{ countStatus("rejected") }}
                    </div>
                    <div class="text-sm text-gray-500 mt-1">Rejected</div>
                </div>
            </div>

            <!-- Tabs -->
            <div
                class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"
            >
                <div class="flex border-b border-gray-100">
                    <button
                        @click="activeTab = 'quotations'"
                        :class="[
                            'px-5 py-3.5 text-sm font-medium transition-colors border-b-2',
                            activeTab === 'quotations'
                                ? 'border-cyan-500 text-cyan-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700',
                        ]"
                    >
                        Quotations
                        <span
                            class="ml-1.5 text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full"
                            >{{ quotations.length }}</span
                        >
                    </button>
                    <button
                        @click="activeTab = 'requests'"
                        :class="[
                            'px-5 py-3.5 text-sm font-medium transition-colors border-b-2',
                            activeTab === 'requests'
                                ? 'border-cyan-500 text-cyan-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700',
                        ]"
                    >
                        Quote Requests
                        <span
                            class="ml-1.5 text-xs bg-blue-100 text-blue-600 px-2 py-0.5 rounded-full"
                            >{{ requests.length }}</span
                        >
                    </button>
                </div>

                <!-- Quotations tab -->
                <template v-if="activeTab === 'quotations'">
                    <!-- Filters -->
                    <div
                        class="p-4 flex flex-col sm:flex-row gap-3 border-b border-gray-50"
                    >
                        <div class="relative flex-1 max-w-sm">
                            <svg
                                class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search quotations..."
                                class="w-full border border-gray-300 rounded-xl pl-9 pr-3 py-2 outline-none focus:border-cyan-500 text-sm"
                            />
                        </div>
                        <select
                            v-model="statusFilter"
                            class="border border-gray-300 rounded-xl px-3 py-2 outline-none focus:border-cyan-500 text-sm bg-white"
                        >
                            <option value="">All Statuses</option>
                            <option value="draft">Draft</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <div
                        v-if="filtered.length === 0"
                        class="text-sm text-gray-400 text-center py-12"
                    >
                        No quotations found
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Quote #
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Client
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Service
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Amount
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Status
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Date
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr
                                    v-for="q in paginated"
                                    :key="q.id"
                                    class="hover:bg-gray-50 transition-colors"
                                >
                                    <td class="px-6 py-4">
                                        <button
                                            @click="openView(q)"
                                            class="text-sm font-mono font-semibold hover:underline"
                                            style="color: #00bcd4"
                                        >
                                            {{ q.quote_number }}
                                        </button>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div
                                            class="text-sm font-semibold text-gray-900"
                                        >
                                            {{ q.client }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ q.email }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ q.service ?? "-" }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm font-medium text-gray-900"
                                    >
                                        KES {{ fmt(q.total) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <select
                                            :value="q.status"
                                            @change="updateStatus(q, ($event.target as HTMLSelectElement).value)"
                                            :class="['pr-7 pl-2.5 py-1 rounded-full text-xs font-semibold border appearance-none cursor-pointer', statusClass(q.status)]">
                                            <option value="draft">Draft</option>
                                            <option value="pending">Pending</option>
                                            <option value="approved">Approved</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap"
                                    >
                                        {{ fmtDate(q.created_at) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-1">
                                            <button
                                                @click="openView(q)"
                                                class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors"
                                                title="View / Print"
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
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                    />
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                                    />
                                                </svg>
                                            </button>
                                            <button
                                                v-if="q.status === 'draft'"
                                                @click="
                                                    updateStatus(q, 'pending')
                                                "
                                                class="p-1.5 text-gray-400 hover:text-cyan-600 hover:bg-cyan-50 rounded transition-colors"
                                                title="Send to client"
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
                                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                                    />
                                                </svg>
                                            </button>
                                            <button
                                                v-if="q.status === 'pending'"
                                                @click="
                                                    updateStatus(q, 'approved')
                                                "
                                                class="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded transition-colors"
                                                title="Approve"
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
                                                        d="M5 13l4 4L19 7"
                                                    />
                                                </svg>
                                            </button>
                                            <button
                                                @click="deleteQuotation(q.id)"
                                                class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded transition-colors"
                                                title="Delete"
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
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="totalPages > 1"
                        class="px-6 py-4 border-t border-gray-100 flex items-center justify-between"
                    >
                        <span class="text-sm text-gray-500"
                            >Page {{ currentPage }} of {{ totalPages }}</span
                        >
                        <div class="flex items-center gap-1">
                            <button
                                @click="
                                    currentPage = Math.max(1, currentPage - 1)
                                "
                                :disabled="currentPage === 1"
                                class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-40"
                            >
                                Prev
                            </button>
                            <button
                                v-for="p in totalPages"
                                :key="p"
                                @click="currentPage = p"
                                :class="[
                                    'px-3 py-1.5 text-sm rounded-lg',
                                    currentPage === p
                                        ? 'text-white'
                                        : 'border border-gray-200 hover:bg-gray-50',
                                ]"
                                :style="
                                    currentPage === p
                                        ? 'background:#00BCD4'
                                        : ''
                                "
                            >
                                {{ p }}
                            </button>
                            <button
                                @click="
                                    currentPage = Math.min(
                                        totalPages,
                                        currentPage + 1,
                                    )
                                "
                                :disabled="currentPage === totalPages"
                                class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-40"
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Quote Requests tab -->
                <template v-if="activeTab === 'requests'">
                    <div
                        v-if="requests.length === 0"
                        class="text-sm text-gray-400 text-center py-12"
                    >
                        No quote requests yet
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Request #
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Client
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Product / Service
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Qty / Size
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Delivery
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Status
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Date
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr
                                    v-for="r in requests"
                                    :key="r.id"
                                    class="hover:bg-gray-50 transition-colors"
                                >
                                    <td
                                        class="px-6 py-4 text-xs font-mono text-gray-500"
                                    >
                                        {{ r.request_number ?? `#RQ-${r.id}` }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div
                                            class="text-sm font-semibold text-gray-900"
                                        >
                                            {{ r.name }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ r.email }}
                                        </div>
                                        <div
                                            v-if="r.phone"
                                            class="text-xs text-gray-400"
                                        >
                                            {{ r.phone }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ r.product ?? "-" }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <div v-if="r.quantity">
                                            Qty: {{ r.quantity }}
                                        </div>
                                        <div
                                            v-if="r.size"
                                            class="text-xs text-gray-500"
                                        >
                                            {{ r.size }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ r.delivery_method ?? "-" }}
                                    </td>
                                    <!-- Status: dropdown for new/rejected; read-only badge for 'quoted' -->
                                    <td class="px-6 py-4">
                                        <span v-if="r.status === 'quoted'"
                                            :class="['px-2.5 py-1 rounded-full text-xs font-semibold border', reqStatusClass(r.status)]">
                                            Quoted
                                        </span>
                                        <select v-else
                                            :value="r.status"
                                            :disabled="updatingReqId === r.id"
                                            @change="updateReqStatus(r, ($event.target as HTMLSelectElement).value)"
                                            :class="['pr-7 pl-2.5 py-1 rounded-full text-xs font-semibold border appearance-none cursor-pointer disabled:opacity-50', reqStatusClass(r.status)]">
                                            <option value="new">New</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                        {{ fmtDate(r.created_at) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <!-- Only show Quote button if not already quoted or rejected -->
                                        <button v-if="r.status !== 'rejected'"
                                            @click="createQuoteFromRequest(r)"
                                            :class="['flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition-colors', r.status === 'quoted' ? 'opacity-60' : '']"
                                            style="background: #00bcd4"
                                            :title="r.status === 'quoted' ? 'Add another quotation' : 'Create quotation from this request'">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            {{ r.status === 'quoted' ? 'Re-quote' : 'Quote' }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>
        </template>

        <!-- ─── Create Quotation Modal ──────────────────────────────────────────── -->
        <Teleport to="body">
            <Transition name="fade">
                <div
                    v-if="showCreate"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                    @click.self="showCreate = false"
                >
                    <div
                        class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[92vh] flex flex-col"
                    >
                        <div
                            class="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0"
                        >
                            <h3 class="text-base font-bold text-gray-900">
                                Create Quotation
                            </h3>
                            <button
                                @click="showCreate = false"
                                class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors"
                            >
                                <svg
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                        <div class="p-6 overflow-y-auto flex-1 space-y-5">
                            <div
                                v-if="createError"
                                class="rounded-xl p-3 bg-red-50 text-red-600 border border-red-200 text-sm"
                            >
                                {{ createError }}
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                        >Client Name
                                        <span class="text-red-500"
                                            >*</span
                                        ></label
                                    >
                                    <input
                                        v-model="form.client"
                                        type="text"
                                        placeholder="Full name"
                                        class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                        >Client Email
                                        <span class="text-red-500"
                                            >*</span
                                        ></label
                                    >
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        placeholder="email@example.com"
                                        class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"
                                    />
                                </div>
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                    >Service / Description</label
                                >
                                <input
                                    v-model="form.service"
                                    type="text"
                                    placeholder="e.g. Brand Identity Package"
                                    class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"
                                />
                            </div>
                            <!-- Line Items -->
                            <div>
                                <div
                                    class="flex items-center justify-between mb-2"
                                >
                                    <label
                                        class="block text-sm font-medium text-gray-700"
                                        >Line Items</label
                                    >
                                    <button
                                        @click="addItem"
                                        class="flex items-center gap-1 text-xs font-semibold px-2.5 py-1.5 rounded-lg"
                                        style="
                                            color: #00bcd4;
                                            background: rgba(0, 188, 212, 0.08);
                                        "
                                    >
                                        <svg
                                            class="w-3 h-3"
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
                                        Add Item
                                    </button>
                                </div>
                                <div
                                    class="border border-gray-200 rounded-xl overflow-hidden"
                                >
                                    <table class="w-full text-sm">
                                        <thead
                                            class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider"
                                        >
                                            <tr>
                                                <th
                                                    class="px-3 py-2.5 text-left font-medium"
                                                >
                                                    Description
                                                </th>
                                                <th
                                                    class="px-3 py-2.5 text-left font-medium w-16"
                                                >
                                                    Qty
                                                </th>
                                                <th
                                                    class="px-3 py-2.5 text-left font-medium w-28"
                                                >
                                                    Unit Price
                                                </th>
                                                <th
                                                    class="px-3 py-2.5 text-right font-medium w-24"
                                                >
                                                    Total
                                                </th>
                                                <th class="w-8"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr
                                                v-for="(item, i) in form.items"
                                                :key="i"
                                            >
                                                <td class="px-2 py-1.5">
                                                    <input
                                                        v-model="
                                                            item.description
                                                        "
                                                        type="text"
                                                        placeholder="Item description"
                                                        class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs outline-none focus:border-cyan-500"
                                                    />
                                                </td>
                                                <td class="px-2 py-1.5">
                                                    <input
                                                        v-model.number="
                                                            item.qty
                                                        "
                                                        type="number"
                                                        min="1"
                                                        class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs text-center outline-none focus:border-cyan-500"
                                                    />
                                                </td>
                                                <td class="px-2 py-1.5">
                                                    <input
                                                        v-model.number="
                                                            item.unit_price
                                                        "
                                                        type="number"
                                                        min="0"
                                                        placeholder="0"
                                                        class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs outline-none focus:border-cyan-500"
                                                    />
                                                </td>
                                                <td
                                                    class="px-3 py-1.5 text-xs font-semibold text-gray-800 text-right"
                                                >
                                                    KES
                                                    {{
                                                        (
                                                            item.qty *
                                                            item.unit_price
                                                        ).toLocaleString()
                                                    }}
                                                </td>
                                                <td class="px-2 py-1.5">
                                                    <button
                                                        v-if="
                                                            form.items.length >
                                                            1
                                                        "
                                                        @click="removeItem(i)"
                                                        class="text-gray-600 hover:text-red-500 transition-colors"
                                                    >
                                                        <svg
                                                            class="w-3.5 h-3.5"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M6 18L18 6M6 6l12 12"
                                                            />
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- Totals -->
                                <div
                                    class="mt-3 flex flex-col items-end gap-1 text-sm"
                                >
                                    <div class="flex gap-12 text-gray-600">
                                        <span>Subtotal</span
                                        ><span
                                            >KES
                                            {{
                                                subtotal.toLocaleString()
                                            }}</span
                                        >
                                    </div>
                                    <div class="flex gap-12 text-gray-600">
                                        <span>VAT (16%)</span
                                        ><span
                                            >KES
                                            {{ vat.toLocaleString() }}</span
                                        >
                                    </div>
                                    <div
                                        class="flex gap-10 font-bold text-gray-900 border-t border-gray-200 pt-1 mt-1"
                                    >
                                        <span>Total</span
                                        ><span
                                            >KES
                                            {{ total.toLocaleString() }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                            <!-- Terms -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                    >Terms & Notes</label
                                >
                                <textarea
                                    v-model="form.terms"
                                    rows="3"
                                    placeholder="Payment terms, validity, notes..."
                                    class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all resize-none"
                                ></textarea>
                            </div>
                        </div>
                        <div
                            class="px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl flex items-center justify-between shrink-0"
                        >
                            <p class="text-sm font-bold text-gray-900">
                                Total:
                                <span style="color: #00bcd4"
                                    >KES {{ total.toLocaleString() }}</span
                                >
                            </p>
                            <div class="flex gap-2">
                                <button
                                    @click="showCreate = false"
                                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-100 transition-colors"
                                >
                                    Cancel
                                </button>
                                <button
                                    @click="submitCreate('draft')"
                                    :disabled="saving"
                                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-100 transition-colors disabled:opacity-50"
                                >
                                    Save Draft
                                </button>
                                <button
                                    @click="submitCreate('pending')"
                                    :disabled="saving"
                                    class="px-5 py-2 rounded-lg text-sm font-semibold text-white flex items-center gap-2 disabled:opacity-50 transition-all hover:-translate-y-0.5"
                                    style="background: #1f2937"
                                >
                                    <svg
                                        v-if="saving"
                                        class="w-4 h-4 animate-spin"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle
                                            class="opacity-25"
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            stroke-width="4"
                                        />
                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                        />
                                    </svg>
                                    Send to Client
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ─── View / Print Modal ─────────────────────────────────────────────── -->
        <Teleport to="body">
            <Transition name="fade">
                <div
                    v-if="showView"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                    @click.self="showView = false"
                >
                    <div
                        class="bg-white rounded-2xl shadow-2xl w-full max-w-xl max-h-[92vh] flex flex-col"
                    >
                        <div
                            class="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0"
                        >
                            <div>
                                <h3
                                    class="text-base font-bold text-gray-900 font-mono"
                                    style="color: #00bcd4"
                                >
                                    {{ viewQ?.quote_number }}
                                </h3>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ viewQ?.client }} · {{ viewQ?.email }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    @click="printQuotation"
                                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition-colors"
                                    style="background: #00bcd4"
                                >
                                    <svg
                                        class="w-3.5 h-3.5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                                        />
                                    </svg>
                                    Print
                                </button>
                                <button
                                    @click="showView = false"
                                    class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors"
                                >
                                    <svg
                                        class="w-5 h-5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="p-6 overflow-y-auto flex-1">
                            <div
                                v-if="viewLoading"
                                class="flex justify-center py-10"
                            >
                                <div
                                    class="w-7 h-7 border-4 border-t-transparent rounded-full animate-spin"
                                    style="
                                        border-color: #00bcd4;
                                        border-top-color: transparent;
                                    "
                                ></div>
                            </div>
                            <template v-else-if="viewQ">
                                <!-- Status + meta -->
                                <div
                                    class="flex items-center justify-between mb-5"
                                >
                                    <span
                                        :class="[
                                            'px-3 py-1 rounded-full text-xs font-semibold border',
                                            statusClass(viewQ.status),
                                        ]"
                                        >{{ capitalize(viewQ.status) }}</span
                                    >
                                    <div class="flex gap-2">
                                        <button
                                            v-if="viewQ.status === 'draft'"
                                            @click="
                                                updateStatus(viewQ, 'pending')
                                            "
                                            class="text-xs px-3 py-1.5 rounded-lg font-medium text-white"
                                            style="background: #00bcd4"
                                        >
                                            Send
                                        </button>
                                        <button
                                            v-if="viewQ.status === 'pending'"
                                            @click="
                                                updateStatus(viewQ, 'approved')
                                            "
                                            class="text-xs px-3 py-1.5 rounded-lg font-medium bg-green-600 text-white"
                                        >
                                            Approve
                                        </button>
                                        <button
                                            v-if="viewQ.status === 'pending'"
                                            @click="
                                                updateStatus(viewQ, 'rejected')
                                            "
                                            class="text-xs px-3 py-1.5 rounded-lg font-medium bg-red-500 text-white"
                                        >
                                            Reject
                                        </button>
                                    </div>
                                </div>
                                <!-- Client -->
                                <div
                                    class="bg-gray-50 rounded-xl p-4 mb-4 text-sm"
                                >
                                    <p class="font-semibold text-gray-900">
                                        {{ viewQ.client }}
                                    </p>
                                    <p class="text-gray-500">
                                        {{ viewQ.email }}
                                    </p>
                                    <p
                                        v-if="viewQ.service"
                                        class="text-gray-500 mt-1"
                                    >
                                        {{ viewQ.service }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ fmtDate(viewQ.created_at) }}
                                    </p>
                                </div>
                                <!-- Items -->
                                <table
                                    class="w-full text-sm mb-4 border border-gray-100 rounded-xl overflow-hidden"
                                >
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                            >
                                                Description
                                            </th>
                                            <th
                                                class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase"
                                            >
                                                Qty
                                            </th>
                                            <th
                                                class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                                            >
                                                Price
                                            </th>
                                            <th
                                                class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                                            >
                                                Total
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <tr
                                            v-for="item in viewQ.items ?? []"
                                            :key="item.id"
                                        >
                                            <td class="px-4 py-3 text-gray-700">
                                                {{ item.description }}
                                            </td>
                                            <td
                                                class="px-4 py-3 text-center text-gray-700"
                                            >
                                                {{ item.quantity }}
                                            </td>
                                            <td
                                                class="px-4 py-3 text-right text-gray-700"
                                            >
                                                {{ fmt(item.unit_price) }}
                                            </td>
                                            <td
                                                class="px-4 py-3 text-right font-semibold text-gray-900"
                                            >
                                                {{ fmt(item.total) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <!-- Totals -->
                                <div
                                    class="flex flex-col items-end gap-1.5 text-sm mb-4"
                                >
                                    <div class="flex gap-16 text-gray-600">
                                        <span>Subtotal</span
                                        ><span
                                            >KES {{ fmt(viewQ.subtotal) }}</span
                                        >
                                    </div>
                                    <div class="flex gap-16 text-gray-600">
                                        <span>VAT (16%)</span
                                        ><span>KES {{ fmt(viewQ.tax) }}</span>
                                    </div>
                                    <div
                                        class="flex gap-12 font-bold text-gray-900 border-t border-gray-200 pt-1.5"
                                    >
                                        <span>Total</span
                                        ><span style="color: #00bcd4"
                                            >KES {{ fmt(viewQ.total) }}</span
                                        >
                                    </div>
                                </div>
                                <!-- Terms -->
                                <div
                                    v-if="viewQ.terms"
                                    class="text-xs text-gray-500 border-t border-gray-100 pt-4"
                                >
                                    <p class="font-semibold text-gray-700 mb-1">
                                        Terms & Conditions
                                    </p>
                                    <p class="whitespace-pre-wrap">
                                        {{ viewQ.terms }}
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
