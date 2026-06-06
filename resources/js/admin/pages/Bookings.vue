<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import api from "../api";

interface ServiceRequest {
    id: number;
    request_number?: string;
}
interface Booking {
    id: number;
    booking_number?: string;
    client?: string;
    client_name?: string;
    email?: string;
    client_email?: string;
    service: string;
    booking_date?: string;
    preferred_date?: string;
    delivery_date?: string;
    date?: string;
    booking_time?: string;
    preferred_time?: string;
    time?: string;
    location?: string;
    budget?: string;
    price?: number;
    status: string;
    serviceRequest?: ServiceRequest | null;
}

const loading = ref(true);
const bookings = ref<Booking[]>([]);
const viewMode = ref<"list" | "calendar">("list");
const updatingId = ref<number | null>(null);

// ─── Delivery date inline edit ────────────────────────────────────────────────
const editingDeliveryId = ref<number | null>(null);
const editDeliveryVal = ref("");

function startEditDelivery(b: Booking) {
    editingDeliveryId.value = b.id;
    editDeliveryVal.value = b.delivery_date ?? "";
}

async function saveDelivery(b: Booking) {
    if (editDeliveryVal.value === (b.delivery_date ?? "")) {
        editingDeliveryId.value = null;
        return;
    }
    updatingId.value = b.id;
    try {
        const { data } = await api.patch(`/service-bookings/${b.id}`, {
            delivery_date: editDeliveryVal.value || null,
        });
        const idx = bookings.value.findIndex((x) => x.id === b.id);
        if (idx !== -1)
            bookings.value[idx] = {
                ...bookings.value[idx],
                ...(data.data ?? data),
            };
    } catch (e) {
        console.error(e);
    } finally {
        updatingId.value = null;
        editingDeliveryId.value = null;
    }
}

const today = new Date();
const calMonth = ref(new Date(today.getFullYear(), today.getMonth(), 1));

const monthTitle = computed(() =>
    calMonth.value.toLocaleDateString("en-US", {
        month: "long",
        year: "numeric",
    }),
);

function prevMonth() {
    calMonth.value = new Date(
        calMonth.value.getFullYear(),
        calMonth.value.getMonth() - 1,
        1,
    );
}
function nextMonth() {
    calMonth.value = new Date(
        calMonth.value.getFullYear(),
        calMonth.value.getMonth() + 1,
        1,
    );
}

const calendarCells = computed(() => {
    const year = calMonth.value.getFullYear();
    const month = calMonth.value.getMonth();
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const daysInPrev = new Date(year, month, 0).getDate();
    const cells: {
        day: number;
        date: Date;
        isCurrentMonth: boolean;
        isToday: boolean;
        bookings: Booking[];
    }[] = [];
    for (let i = firstDay - 1; i >= 0; i--) {
        const d = new Date(year, month - 1, daysInPrev - i);
        cells.push({
            day: daysInPrev - i,
            date: d,
            isCurrentMonth: false,
            isToday: false,
            bookings: [],
        });
    }
    for (let d = 1; d <= daysInMonth; d++) {
        const date = new Date(year, month, d);
        const isToday = date.toDateString() === today.toDateString();
        const dayBookings = bookings.value.filter((b) => {
            const bd = b.preferred_date ?? b.booking_date ?? b.date;
            if (!bd) return false;
            return new Date(bd).toDateString() === date.toDateString();
        });
        cells.push({
            day: d,
            date,
            isCurrentMonth: true,
            isToday,
            bookings: dayBookings,
        });
    }
    const remaining = 42 - cells.length;
    for (let d = 1; d <= remaining; d++) {
        cells.push({
            day: d,
            date: new Date(year, month + 1, d),
            isCurrentMonth: false,
            isToday: false,
            bookings: [],
        });
    }
    return cells;
});

function countStatus(s: string) {
    return bookings.value.filter((b) => b.status === s).length;
}

function statusClass(s: string) {
    const m: Record<string, string> = {
        confirmed: "bg-green-100 text-green-700 border-green-200",
        pending: "bg-orange-100 text-orange-700 border-orange-200",
        cancelled: "bg-red-100 text-red-700 border-red-200",
    };
    return m[s] ?? "bg-gray-100 text-gray-700 border-gray-200";
}

function dotColorClass(s: string) {
    const m: Record<string, string> = {
        confirmed: "bg-green-100 text-green-700",
        pending: "bg-orange-100 text-orange-700",
        cancelled: "bg-red-100 text-red-700",
    };
    return m[s] ?? "bg-gray-100 text-gray-700";
}

function capitalize(s: string) {
    return s ? s.charAt(0).toUpperCase() + s.slice(1) : "";
}

function formatDate(d?: string) {
    if (!d) return "-";
    return new Date(d).toLocaleDateString("en-GB", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
}

function clientName(b: Booking) {
    return b.client ?? b.client_name ?? "Guest";
}
function clientEmail(b: Booking) {
    return b.email ?? b.client_email ?? "";
}
function bookingDate(b: Booking) {
    return b.preferred_date ?? b.booking_date ?? b.date;
}
function bookingTime(b: Booking) {
    return b.preferred_time ?? b.booking_time ?? b.time;
}

async function updateStatus(id: number, status: string) {
    updatingId.value = id;
    try {
        const { data } = await api.patch(`/service-bookings/${id}`, { status });
        const idx = bookings.value.findIndex((b) => b.id === id);
        if (idx !== -1)
            bookings.value[idx] = {
                ...bookings.value[idx],
                ...(data.data ?? data),
            };
    } catch (e) {
        console.error(e);
    } finally {
        updatingId.value = null;
    }
}

// ─── Payment modal ────────────────────────────────────────────────────────────
const showPayModal = ref(false);
const payBooking = ref<Booking | null>(null);
const payMethod = ref<"cash" | "mpesa_stk" | "mpesa_code" | "bank_transfer">(
    "cash",
);
const payAmount = ref(0);
const payPhone = ref("");
const payRef = ref("");
const payCode = ref("");
const payLoading = ref(false);
const paySuccess = ref(false);
const payError = ref("");
const stkSent = ref(false);

const payMethods = [
    {
        key: "cash",
        label: "Cash",
        icon: "M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z",
    },
    {
        key: "mpesa_stk",
        label: "M-Pesa STK",
        icon: "M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z",
    },
    {
        key: "mpesa_code",
        label: "M-Pesa Code",
        icon: "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2",
    },
    {
        key: "bank_transfer",
        label: "Bank Transfer",
        icon: "M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z",
    },
];

function openPayModal(b: Booking) {
    payBooking.value = b;
    payAmount.value = b.price ?? 0;
    payMethod.value = "cash";
    payPhone.value = "";
    payRef.value = "";
    payCode.value = "";
    payError.value = "";
    paySuccess.value = false;
    stkSent.value = false;
    showPayModal.value = true;
}

function closePayModal() {
    showPayModal.value = false;
    payBooking.value = null;
}

async function simulateStkPush() {
    if (!payPhone.value) {
        payError.value = "Enter phone number.";
        return;
    }
    payLoading.value = true;
    payError.value = "";
    await new Promise((r) => setTimeout(r, 1500));
    stkSent.value = true;
    payLoading.value = false;
}

async function submitPayment() {
    if (!payBooking.value) return;
    if (payAmount.value <= 0) {
        payError.value = "Enter a valid amount.";
        return;
    }
    if (payMethod.value === "mpesa_stk" && !stkSent.value) {
        payError.value = "Send STK push first.";
        return;
    }
    if (payMethod.value === "mpesa_code" && !payCode.value) {
        payError.value = "Enter M-Pesa code.";
        return;
    }
    if (payMethod.value === "bank_transfer" && !payRef.value) {
        payError.value = "Enter bank reference.";
        return;
    }

    payLoading.value = true;
    payError.value = "";
    try {
        const method =
            payMethod.value === "mpesa_stk" || payMethod.value === "mpesa_code"
                ? "mpesa"
                : payMethod.value;
        const reference =
            payMethod.value === "mpesa_stk"
                ? payPhone.value
                : payMethod.value === "mpesa_code"
                  ? payCode.value
                  : payMethod.value === "bank_transfer"
                    ? payRef.value
                    : null;

        await api.post(`/service-bookings/${payBooking.value.id}/pay`, {
            method,
            amount: payAmount.value,
            reference,
            phone: payMethod.value === "mpesa_stk" ? payPhone.value : undefined,
        });

        paySuccess.value = true;
        const idx = bookings.value.findIndex(
            (b) => b.id === payBooking.value!.id,
        );
        if (idx !== -1)
            bookings.value[idx] = {
                ...bookings.value[idx],
                status: "confirmed",
                price: payAmount.value,
            };
        setTimeout(closePayModal, 1500);
    } catch (e: any) {
        payError.value = e.response?.data?.message ?? "Payment failed.";
    } finally {
        payLoading.value = false;
    }
}

// ─── New Booking modal ────────────────────────────────────────────────────────
const showNewModal = ref(false);
const newLoading = ref(false);
const newError = ref("");
const newForm = ref({
    client: "",
    email: "",
    phone: "",
    service: "",
    preferred_date: "",
    delivery_date: "",
    location: "",
    price: 0,
    notes: "",
    description: "",
});

function openNewModal() {
    newError.value = "";
    newForm.value = {
        client: "",
        email: "",
        phone: "",
        service: "",
        preferred_date: "",
        delivery_date: "",
        location: "",
        price: 0,
        notes: "",
        description: "",
    };
    showNewModal.value = true;
}

async function submitNewBooking() {
    if (
        !newForm.value.client ||
        !newForm.value.email ||
        !newForm.value.service
    ) {
        newError.value = "Client name, email and service are required.";
        return;
    }
    newLoading.value = true;
    newError.value = "";
    try {
        const { data } = await api.post("/service-bookings", newForm.value);
        bookings.value.unshift(data);
        showNewModal.value = false;
    } catch (e: any) {
        newError.value =
            e.response?.data?.message ?? "Failed to create booking.";
    } finally {
        newLoading.value = false;
    }
}

// ─── Booking statuses ─────────────────────────────────────────────────────────
const bookingStatuses = [
    { value: "pending", label: "Pending" },
    { value: "confirmed", label: "Confirmed" },
    { value: "completed", label: "Completed" },
    { value: "cancelled", label: "Cancelled" },
];

onMounted(async () => {
    try {
        const { data } = await api.get("/service-bookings");
        bookings.value = Array.isArray(data) ? data : (data.data ?? []);
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
                <h1 class="text-2xl font-bold text-gray-900">
                    Service Bookings
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Manage client appointments and consultations
                </p>
            </div>
            <div class="flex items-center gap-3">
                <div
                    class="flex bg-white border border-gray-200 rounded-lg p-0.5"
                >
                    <button
                        @click="viewMode = 'list'"
                        :class="[
                            'px-3 py-1.5 rounded-md text-sm font-medium transition-colors',
                            viewMode === 'list'
                                ? 'bg-gray-900 text-white'
                                : 'text-gray-500 hover:text-gray-700',
                        ]"
                    >
                        List
                    </button>
                    <button
                        @click="viewMode = 'calendar'"
                        :class="[
                            'px-3 py-1.5 rounded-md text-sm font-medium transition-colors',
                            viewMode === 'calendar'
                                ? 'bg-gray-900 text-white'
                                : 'text-gray-500 hover:text-gray-700',
                        ]"
                    >
                        Calendar
                    </button>
                </div>
                <button
                    @click="openNewModal"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white transition-all hover:-translate-y-0.5"
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
                    New Booking
                </button>
            </div>
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
                        {{ bookings.length }}
                    </div>
                    <div
                        class="text-sm text-gray-500 mt-1 flex items-center gap-1.5"
                    >
                        <span
                            class="w-2 h-2 rounded-full bg-blue-500 inline-block"
                        ></span
                        >Total Bookings
                    </div>
                </div>
                <div
                    class="bg-white rounded-xl shadow-sm border border-gray-100 p-5"
                >
                    <div class="text-2xl font-bold text-orange-600">
                        {{ countStatus("pending") }}
                    </div>
                    <div
                        class="text-sm text-gray-500 mt-1 flex items-center gap-1.5"
                    >
                        <span
                            class="w-2 h-2 rounded-full bg-orange-400 inline-block"
                        ></span
                        >Pending
                    </div>
                </div>
                <div
                    class="bg-white rounded-xl shadow-sm border border-gray-100 p-5"
                >
                    <div class="text-2xl font-bold text-green-600">
                        {{ countStatus("confirmed") }}
                    </div>
                    <div
                        class="text-sm text-gray-500 mt-1 flex items-center gap-1.5"
                    >
                        <span
                            class="w-2 h-2 rounded-full bg-green-500 inline-block"
                        ></span
                        >Confirmed
                    </div>
                </div>
                <div
                    class="bg-white rounded-xl shadow-sm border border-gray-100 p-5"
                >
                    <div class="text-2xl font-bold text-red-600">
                        {{ countStatus("cancelled") }}
                    </div>
                    <div
                        class="text-sm text-gray-500 mt-1 flex items-center gap-1.5"
                    >
                        <span
                            class="w-2 h-2 rounded-full bg-red-400 inline-block"
                        ></span
                        >Cancelled
                    </div>
                </div>
            </div>

            <!-- List View -->
            <div
                v-if="viewMode === 'list'"
                class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"
            >
                <div
                    v-if="bookings.length === 0"
                    class="text-sm text-gray-400 text-center py-12"
                >
                    No bookings found
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Booking
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
                                    Preferred Date
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Delivery Date
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Price
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Status
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
                                v-for="booking in bookings"
                                :key="booking.id"
                                class="hover:bg-gray-50 transition-colors"
                            >
                                <!-- Booking number + source request -->
                                <td class="px-6 py-4">
                                    <div
                                        class="text-xs font-mono font-semibold"
                                        style="color: #00bcd4"
                                    >
                                        {{
                                            booking.booking_number ??
                                            `#BK-${booking.id}`
                                        }}
                                    </div>
                                    <div
                                        v-if="booking.serviceRequest"
                                        class="text-xs text-gray-400 mt-0.5"
                                    >
                                        From:
                                        {{
                                            booking.serviceRequest
                                                .request_number ??
                                            `#SR-${booking.serviceRequest.id}`
                                        }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div
                                        class="text-sm font-semibold text-gray-900"
                                    >
                                        {{ clientName(booking) }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ clientEmail(booking) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ booking.service }}
                                </td>
                                <td
                                    class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap"
                                >
                                    {{ formatDate(bookingDate(booking)) }}
                                    <span
                                        v-if="bookingTime(booking)"
                                        class="text-gray-400 ml-1"
                                        >{{ bookingTime(booking) }}</span
                                    >
                                </td>
                                <!-- Delivery date: inline editable -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div
                                        v-if="editingDeliveryId === booking.id"
                                        class="flex items-center gap-1"
                                    >
                                        <input
                                            v-model="editDeliveryVal"
                                            type="date"
                                            class="border border-cyan-400 rounded-lg px-2 py-1 text-xs outline-none focus:ring-1 focus:ring-cyan-500"
                                            @keyup.enter="saveDelivery(booking)"
                                            @blur="saveDelivery(booking)"
                                        />
                                        <button
                                            @click="saveDelivery(booking)"
                                            class="p-1 text-cyan-600 hover:text-cyan-800 transition-colors"
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
                                                    d="M5 13l4 4L19 7"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                    <button
                                        v-else
                                        @click="startEditDelivery(booking)"
                                        :class="[
                                            'group flex items-center gap-1 text-sm transition-colors',
                                            booking.delivery_date
                                                ? 'text-gray-700 hover:text-cyan-600'
                                                : 'text-gray-400 hover:text-cyan-500',
                                        ]"
                                        title="Click to set delivery date"
                                    >
                                        {{
                                            booking.delivery_date
                                                ? formatDate(
                                                      booking.delivery_date,
                                                  )
                                                : "— set date"
                                        }}
                                        <svg
                                            class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"
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
                                </td>
                                <td
                                    class="px-6 py-4 text-sm font-medium text-gray-900"
                                >
                                    {{
                                        booking.price
                                            ? "KES " +
                                              Number(
                                                  booking.price,
                                              ).toLocaleString()
                                            : (booking.budget ?? "-")
                                    }}
                                </td>
                                <!-- Inline status dropdown -->
                                <td class="px-6 py-4">
                                    <select
                                        :value="booking.status"
                                        :disabled="updatingId === booking.id"
                                        @change="
                                            updateStatus(
                                                booking.id,
                                                (
                                                    $event.target as HTMLSelectElement
                                                ).value,
                                            )
                                        "
                                        :class="[
                                            'pr-7 pl-2.5 py-1 rounded-full text-xs font-semibold border appearance-none cursor-pointer disabled:opacity-50',
                                            statusClass(booking.status),
                                        ]"
                                    >
                                        <option
                                            v-for="s in bookingStatuses"
                                            :key="s.value"
                                            :value="s.value"
                                        >
                                            {{ s.label }}
                                        </option>
                                    </select>
                                </td>
                                <td class="px-6 py-4">
                                    <button
                                        v-if="booking.status !== 'cancelled'"
                                        @click="openPayModal(booking)"
                                        class="flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-white rounded-lg transition-colors"
                                        style="background: #00bcd4"
                                        title="Collect Payment"
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
                                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                                            />
                                        </svg>
                                        Pay
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Calendar View -->
            <div
                v-else
                class="bg-white rounded-xl shadow-sm border border-gray-100 p-6"
            >
                <div class="flex items-center justify-between mb-6">
                    <button
                        @click="prevMonth"
                        class="p-2 rounded-lg hover:bg-gray-100 transition-colors"
                    >
                        <svg
                            class="w-4 h-4 text-gray-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 19l-7-7 7-7"
                            />
                        </svg>
                    </button>
                    <h2 class="text-base font-semibold text-gray-900">
                        {{ monthTitle }}
                    </h2>
                    <button
                        @click="nextMonth"
                        class="p-2 rounded-lg hover:bg-gray-100 transition-colors"
                    >
                        <svg
                            class="w-4 h-4 text-gray-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </button>
                </div>
                <div class="grid grid-cols-7 mb-2">
                    <div
                        v-for="day in [
                            'Sun',
                            'Mon',
                            'Tue',
                            'Wed',
                            'Thu',
                            'Fri',
                            'Sat',
                        ]"
                        :key="day"
                        class="text-center text-xs font-medium text-gray-400 py-2"
                    >
                        {{ day }}
                    </div>
                </div>
                <div class="grid grid-cols-7 gap-1">
                    <div
                        v-for="(cell, i) in calendarCells"
                        :key="i"
                        :class="[
                            'min-h-18 rounded-lg p-1.5',
                            cell.isCurrentMonth ? 'bg-white' : 'bg-gray-50',
                            cell.isToday ? 'ring-2 ring-cyan-400' : '',
                        ]"
                    >
                        <span
                            :class="[
                                'text-xs font-medium block mb-1',
                                cell.isCurrentMonth
                                    ? 'text-gray-700'
                                    : 'text-gray-600',
                                cell.isToday ? 'text-cyan-600' : '',
                            ]"
                        >
                            {{ cell.day }}
                        </span>
                        <div class="space-y-0.5">
                            <div
                                v-for="b in cell.bookings"
                                :key="b.id"
                                :class="[
                                    'text-xs px-1 py-0.5 rounded truncate',
                                    dotColorClass(b.status),
                                ]"
                            >
                                {{ clientName(b) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- ─── Payment Modal ─────────────────────────────────────────────────── -->
        <Teleport to="body">
            <Transition name="fade">
                <div
                    v-if="showPayModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                    @click.self="closePayModal"
                >
                    <div
                        class="bg-white rounded-2xl shadow-2xl w-full max-w-md"
                    >
                        <!-- Header -->
                        <div
                            class="px-6 py-4 border-b border-gray-100 flex items-center justify-between"
                        >
                            <div>
                                <h3 class="text-base font-bold text-gray-900">
                                    Collect Payment
                                </h3>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{
                                        payBooking
                                            ? clientName(payBooking) +
                                              " — " +
                                              payBooking.service
                                            : ""
                                    }}
                                </p>
                            </div>
                            <button
                                @click="closePayModal"
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

                        <div class="p-6 space-y-5">
                            <!-- Success -->
                            <div
                                v-if="paySuccess"
                                class="rounded-xl p-4 bg-green-50 text-green-700 border border-green-200 text-sm flex items-center gap-2"
                            >
                                <svg
                                    class="w-5 h-5 shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                Payment recorded successfully!
                            </div>
                            <!-- Error -->
                            <div
                                v-if="payError"
                                class="rounded-xl p-3 bg-red-50 text-red-600 border border-red-200 text-sm"
                            >
                                {{ payError }}
                            </div>

                            <!-- Amount -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                    >Amount (KES)
                                    <span class="text-red-500">*</span></label
                                >
                                <input
                                    v-model.number="payAmount"
                                    type="number"
                                    min="1"
                                    step="100"
                                    placeholder="0"
                                    class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"
                                />
                            </div>

                            <!-- Method selector -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                    >Payment Method</label
                                >
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        v-for="m in payMethods"
                                        :key="m.key"
                                        type="button"
                                        @click="
                                            payMethod = m.key as any;
                                            stkSent = false;
                                            payError = '';
                                        "
                                        :class="[
                                            'flex items-center gap-2 px-3 py-2.5 rounded-xl border text-sm font-medium transition-all',
                                            payMethod === m.key
                                                ? 'border-cyan-500 bg-cyan-50 text-cyan-700'
                                                : 'border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50',
                                        ]"
                                    >
                                        <svg
                                            class="w-4 h-4 shrink-0"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                :d="m.icon"
                                            />
                                        </svg>
                                        {{ m.label }}
                                    </button>
                                </div>
                            </div>

                            <!-- Method-specific fields -->
                            <!-- Cash: just amount, no extra field needed -->
                            <div
                                v-if="payMethod === 'cash'"
                                class="rounded-xl bg-green-50 border border-green-200 p-3 text-sm text-green-700"
                            >
                                Record cash payment. Ensure cash is collected
                                before confirming.
                            </div>

                            <!-- M-Pesa STK Push -->
                            <div
                                v-if="payMethod === 'mpesa_stk'"
                                class="space-y-3"
                            >
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                        >Customer Phone</label
                                    >
                                    <div class="flex gap-2">
                                        <input
                                            v-model="payPhone"
                                            type="text"
                                            placeholder="254712345678"
                                            class="flex-1 border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"
                                        />
                                        <button
                                            type="button"
                                            @click="simulateStkPush"
                                            :disabled="payLoading || stkSent"
                                            class="px-3 py-2 rounded-xl text-sm font-semibold text-white disabled:opacity-50 transition-colors"
                                            style="background: #1f2937"
                                        >
                                            {{
                                                payLoading
                                                    ? "…"
                                                    : stkSent
                                                      ? "Sent ✓"
                                                      : "Push"
                                            }}
                                        </button>
                                    </div>
                                </div>
                                <div
                                    v-if="stkSent"
                                    class="rounded-xl bg-blue-50 border border-blue-200 p-3 text-sm text-blue-700"
                                >
                                    STK push sent to {{ payPhone }}. Customer
                                    should enter their M-Pesa PIN. Click "Record
                                    Payment" once they confirm.
                                </div>
                            </div>

                            <!-- M-Pesa Code -->
                            <div v-if="payMethod === 'mpesa_code'">
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                    >M-Pesa Transaction Code</label
                                >
                                <input
                                    v-model="payCode"
                                    type="text"
                                    placeholder="e.g. QH1234WXYZ"
                                    style="text-transform: uppercase"
                                    class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"
                                />
                                <p class="text-xs text-gray-400 mt-1">
                                    Enter the confirmation code from the
                                    customer's M-Pesa SMS
                                </p>
                            </div>

                            <!-- Bank Transfer -->
                            <div v-if="payMethod === 'bank_transfer'">
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                    >Bank Reference / Cheque No.</label
                                >
                                <input
                                    v-model="payRef"
                                    type="text"
                                    placeholder="e.g. TXN-001 or CHQ-00123"
                                    class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"
                                />
                            </div>
                        </div>

                        <!-- Footer -->
                        <div
                            class="px-6 py-4 border-t border-gray-100 flex items-center justify-between gap-3"
                        >
                            <p class="text-sm font-bold text-gray-900">
                                KES
                                <span style="color: #00bcd4">{{
                                    payAmount.toLocaleString()
                                }}</span>
                            </p>
                            <div class="flex gap-3">
                                <button
                                    @click="closePayModal"
                                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors"
                                >
                                    Cancel
                                </button>
                                <button
                                    @click="submitPayment"
                                    :disabled="payLoading || paySuccess"
                                    class="px-5 py-2 rounded-lg text-sm font-semibold text-white flex items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed transition-all hover:-translate-y-0.5"
                                    style="background: #1f2937"
                                >
                                    <svg
                                        v-if="payLoading"
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
                                    {{
                                        payLoading
                                            ? "Recording…"
                                            : "Record Payment"
                                    }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ─── New Booking Modal ──────────────────────────────────────────────── -->
        <Teleport to="body">
            <Transition name="fade">
                <div
                    v-if="showNewModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                    @click.self="showNewModal = false"
                >
                    <div
                        class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto"
                    >
                        <div
                            class="px-6 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white z-10"
                        >
                            <h3 class="text-base font-bold text-gray-900">
                                New Service Booking
                            </h3>
                            <button
                                @click="showNewModal = false"
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

                        <div class="p-6 space-y-4">
                            <div
                                v-if="newError"
                                class="rounded-xl p-3 bg-red-50 text-red-600 border border-red-200 text-sm"
                            >
                                {{ newError }}
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="col-span-2">
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                        >Client Name
                                        <span class="text-red-500"
                                            >*</span
                                        ></label
                                    >
                                    <input
                                        v-model="newForm.client"
                                        type="text"
                                        placeholder="Full name"
                                        class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all text-gray-700"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                        >Email
                                        <span class="text-red-500"
                                            >*</span
                                        ></label
                                    >
                                    <input
                                        v-model="newForm.email"
                                        type="email"
                                        placeholder="client@email.com"
                                        class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                        >Phone</label
                                    >
                                    <input
                                        v-model="newForm.phone"
                                        type="text"
                                        placeholder="+254..."
                                        class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"
                                    />
                                </div>
                                <div class="col-span-2">
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                        >Service
                                        <span class="text-red-500"
                                            >*</span
                                        ></label
                                    >
                                    <input
                                        v-model="newForm.service"
                                        type="text"
                                        placeholder="e.g. Brand Identity Package"
                                        class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                        >Preferred Date</label
                                    >
                                    <input
                                        v-model="newForm.preferred_date"
                                        type="date"
                                        class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                        >Delivery Date</label
                                    >
                                    <input
                                        v-model="newForm.delivery_date"
                                        type="date"
                                        class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                        >Location / Venue</label
                                    >
                                    <input
                                        v-model="newForm.location"
                                        type="text"
                                        placeholder="Studio / Client site"
                                        class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                        >Agreed Price (KES)</label
                                    >
                                    <input
                                        v-model.number="newForm.price"
                                        type="number"
                                        min="0"
                                        placeholder="0"
                                        class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"
                                    />
                                </div>
                                <div class="col-span-2">
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                        >Notes</label
                                    >
                                    <textarea
                                        v-model="newForm.notes"
                                        rows="2"
                                        placeholder="Internal notes or project details..."
                                        class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all resize-none"
                                    ></textarea>
                                </div>
                            </div>

                            <p class="text-xs text-gray-400">
                                A linked service request will be auto-created
                                and marked as confirmed.
                            </p>
                        </div>

                        <div
                            class="px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl flex justify-end gap-3"
                        >
                            <button
                                @click="showNewModal = false"
                                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-100 transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                @click="submitNewBooking"
                                :disabled="newLoading"
                                class="px-5 py-2 rounded-lg text-sm font-semibold text-white flex items-center gap-2 disabled:opacity-50 transition-all hover:-translate-y-0.5"
                                style="background: #00bcd4"
                            >
                                <svg
                                    v-if="newLoading"
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
                                {{
                                    newLoading ? "Creating…" : "Create Booking"
                                }}
                            </button>
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
