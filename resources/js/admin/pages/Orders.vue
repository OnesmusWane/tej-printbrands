<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api from '../api'

interface CatalogProduct {
    id: number
    name: string
    price: number
    unit: string
    slug: string
    stock_quantity: number | null
}

interface SaleItem {
    product: CatalogProduct
    quantity: number
    unit_price: number
    note: string
}

interface OrderProduct {
    name: string
    slug: string
    price: number
    category: string
    image: string
    unit: string
}

interface OrderItem {
    key: string
    product: OrderProduct
    quantity: number
    unit_price: number
    line_total: number
    note?: string
}

interface OrderUser {
    id: number
    name: string
    email: string
    phone?: string
}

interface Order {
    id: number
    order_number: string
    user_id: number
    user?: OrderUser
    items: OrderItem[]
    subtotal: number
    service_fee: number
    total: number
    payment_method: string
    payment_status: string
    status: string
    mpesa_phone?: string
    mpesa_code?: string
    delivery_method: string
    delivery_address?: string
    notes?: string
    created_at: string
    updated_at: string
}

const loading   = ref(true)
const saving    = ref(false)
const orders    = ref<Order[]>([])
const showModal = ref(false)
const selected  = ref<Order | null>(null)

const editStatus        = ref('')
const editPaymentStatus = ref('')
const editMpesaCode     = ref('')
const editNotes         = ref('')

const searchQ       = ref('')
const filterStatus  = ref('')
const filterPayment = ref('')

const saveError   = ref('')
const saveSuccess = ref(false)

function statusClass(s: string) {
    const m: Record<string, string> = {
        pending_payment: 'bg-orange-100 text-orange-700 border-orange-200',
        processing:      'bg-blue-100 text-blue-700 border-blue-200',
        completed:       'bg-green-100 text-green-700 border-green-200',
        cancelled:       'bg-red-100 text-red-700 border-red-200',
    }
    return m[s] ?? 'bg-gray-100 text-gray-700 border-gray-200'
}

function paymentClass(s: string) {
    const m: Record<string, string> = {
        pending:  'bg-orange-100 text-orange-700 border-orange-200',
        paid:     'bg-green-100 text-green-700 border-green-200',
        failed:   'bg-red-100 text-red-700 border-red-200',
        refunded: 'bg-purple-100 text-purple-700 border-purple-200',
    }
    return m[s] ?? 'bg-gray-100 text-gray-700 border-gray-200'
}

const STATUS_LABELS: Record<string, string>  = { pending_payment: 'Pending Payment', processing: 'Processing', completed: 'Completed', cancelled: 'Cancelled' }
const PAYMENT_LABELS: Record<string, string> = { pending: 'Pending', paid: 'Paid', failed: 'Failed', refunded: 'Refunded' }
const METHOD_LABELS: Record<string, string>  = { mpesa: 'M-Pesa', cash: 'Cash', bank_transfer: 'Bank Transfer', card: 'Card' }

const filtered = computed(() => {
    const q = searchQ.value.toLowerCase()
    return orders.value.filter(o => {
        const matchQ = !q
            || o.order_number.toLowerCase().includes(q)
            || (o.user?.name ?? '').toLowerCase().includes(q)
            || (o.user?.email ?? '').toLowerCase().includes(q)
        return matchQ
            && (!filterStatus.value  || o.status === filterStatus.value)
            && (!filterPayment.value || o.payment_status === filterPayment.value)
    })
})

function countStatus(s: string)  { return orders.value.filter(o => o.status === s).length }
function countPayment(s: string) { return orders.value.filter(o => o.payment_status === s).length }
function totalRevenue()          { return orders.value.filter(o => o.payment_status === 'paid').reduce((s, o) => s + (o.total ?? 0), 0) }

async function load() {
    loading.value = true
    try {
        const { data } = await api.get('/orders', { params: { per_page: 500 } })
        orders.value = (Array.isArray(data) ? data : (data.data ?? [])).map((o: any) => ({
            ...o,
            items: typeof o.items === 'string' ? JSON.parse(o.items) : (o.items ?? []),
        }))
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

function openOrder(order: Order) {
    selected.value          = order
    editStatus.value        = order.status
    editPaymentStatus.value = order.payment_status
    editMpesaCode.value     = order.mpesa_code ?? ''
    editNotes.value         = order.notes ?? ''
    saveError.value         = ''
    saveSuccess.value       = false
    showModal.value         = true
}

async function saveOrder() {
    if (!selected.value) return
    saving.value      = true
    saveError.value   = ''
    saveSuccess.value = false
    try {
        await api.patch(`/orders/${selected.value.id}`, {
            status:         editStatus.value,
            payment_status: editPaymentStatus.value,
            mpesa_code:     editMpesaCode.value || null,
            notes:          editNotes.value     || null,
        })
        const idx = orders.value.findIndex(o => o.id === selected.value!.id)
        if (idx !== -1) {
            orders.value[idx] = {
                ...orders.value[idx],
                status:         editStatus.value,
                payment_status: editPaymentStatus.value,
                mpesa_code:     editMpesaCode.value  || undefined,
                notes:          editNotes.value      || undefined,
            }
            selected.value = orders.value[idx]
        }
        saveSuccess.value = true
        setTimeout(() => { showModal.value = false }, 1200)
    } catch (e: any) {
        saveError.value = e?.response?.data?.message ?? 'Update failed.'
    } finally {
        saving.value = false
    }
}

async function deleteOrder(id: number) {
    if (!confirm('Delete this order? This cannot be undone.')) return
    try {
        await api.delete(`/orders/${id}`)
        orders.value = orders.value.filter(o => o.id !== id)
        if (selected.value?.id === id) showModal.value = false
    } catch (e) {
        console.error(e)
    }
}

function fmt(n: number) {
    return 'KES ' + Number(n).toLocaleString('en-KE', { minimumFractionDigits: 0 })
}

function fmtDate(d: string) {
    return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

function itemsPreview(items: OrderItem[]) {
    return items.slice(0, 2).map(i => i.product?.name ?? i.key).join(', ')
        + (items.length > 2 ? ` +${items.length - 2} more` : '')
}

async function printReceipt(order: Order) {
    let co: any = {}, ct: any = {}
    try {
        const { data } = await api.get('/site-settings')
        const rec: Record<string, any> = {}
        ;(data as any[]).forEach((r: any) => { rec[r.key] = r.value })
        co = rec['company'] ?? {}
        ct = rec['contact']  ?? {}
    } catch {}

    const companyName = co.name        ?? 'Tej Printbrands'
    const logoUrl     = co.logo_url    ?? ''
    const address     = ct.address     ?? ''
    const phone       = ct.phone       ?? ''
    const email       = ct.email       ?? ''
    const website     = ct.website     ?? ''
    const paybill     = ct.mpesa_shortcode ?? co.mpesa_shortcode ?? ''
    const paybillAcct = ct.paybill_account ?? ''

    const fmtAmt = (n: number) => 'KES ' + Number(n).toLocaleString('en-KE', { minimumFractionDigits: 0 })

    const rowsHtml = (order.items ?? []).map(item => `
        <tr>
            <td style="padding:8px 10px;font-size:12px;border-bottom:1px solid #e5e7eb;">${item.product?.name ?? item.key}${item.note ? `<br><span style="color:#9CA3AF;font-size:10px;">${item.note}</span>` : ''}</td>
            <td style="padding:8px 10px;font-size:12px;text-align:center;border-bottom:1px solid #e5e7eb;">${item.quantity}</td>
            <td style="padding:8px 10px;font-size:12px;text-align:right;border-bottom:1px solid #e5e7eb;">${fmtAmt(item.unit_price)}</td>
            <td style="padding:8px 10px;font-size:12px;text-align:right;font-weight:700;border-bottom:1px solid #e5e7eb;">${fmtAmt(item.line_total)}</td>
        </tr>
    `).join('')

    const subtotal   = order.subtotal   ?? 0
    const serviceFee = order.service_fee ?? 0
    const total      = order.total       ?? 0
    const paidBadge  = order.payment_status === 'paid'
        ? '<span style="background:#d1fae5;color:#065f46;padding:3px 12px;border-radius:99px;font-size:11px;font-weight:700;">PAID</span>'
        : '<span style="background:#fef3c7;color:#92400e;padding:3px 12px;border-radius:99px;font-size:11px;font-weight:700;">PENDING PAYMENT</span>'

    const win = window.open('', '_blank', 'width=900,height=700')
    if (!win) return
    win.document.write(`<!DOCTYPE html><html><head>
        <title>Receipt — ${order.order_number}</title>
        <style>@page{size:A4;margin:14mm 16mm}body{font-family:'Helvetica Neue',Arial,sans-serif;color:#1F2937;margin:0}*{box-sizing:border-box}</style>
    </head><body>
        <table width="100%" style="border-bottom:3px solid #00BCD4;padding-bottom:16px;margin-bottom:16px;"><tr>
            <td>${logoUrl ? `<img src="${logoUrl}" style="height:42px;object-fit:contain;display:block;margin-bottom:4px;">` : `<div style="font-size:22px;font-weight:900;color:#1a237e;">${companyName}</div>`}
                ${address ? `<div style="font-size:11px;color:#6B7280;">${address}</div>` : ''}
                ${phone   ? `<div style="font-size:11px;color:#6B7280;">Tel: ${phone}</div>` : ''}
                ${email   ? `<div style="font-size:11px;color:#6B7280;">${email}</div>` : ''}
            </td>
            <td style="text-align:right;">
                <div style="font-size:30px;font-weight:900;color:#1a237e;letter-spacing:2px;">RECEIPT</div>
                <div style="font-size:12px;color:#6B7280;margin-top:4px;"># <strong style="color:#111827;">${order.order_number}</strong></div>
                <div style="font-size:11px;color:#6B7280;">Date: ${fmtDate(order.created_at)}</div>
                <div style="margin-top:6px;">${paidBadge}</div>
            </td>
        </tr></table>
        <table width="100%" style="margin-bottom:16px;"><tr>
            <td width="48%" style="border:1px solid #e5e7eb;border-radius:8px;padding:12px 14px;vertical-align:top;">
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#9CA3AF;margin-bottom:5px;">Bill To</div>
                <div style="font-size:13px;font-weight:700;color:#111827;">${order.user?.name ?? '—'}</div>
                ${order.user?.email ? `<div style="font-size:11px;color:#6B7280;">${order.user.email}</div>` : ''}
                ${(order.user?.phone || order.mpesa_phone) ? `<div style="font-size:11px;color:#6B7280;">${order.user?.phone ?? order.mpesa_phone}</div>` : ''}
            </td>
            <td width="4%"></td>
            <td width="48%" style="border:1px solid #e5e7eb;border-radius:8px;padding:12px 14px;vertical-align:top;">
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#9CA3AF;margin-bottom:5px;">Delivery</div>
                <div style="font-size:13px;font-weight:700;color:#111827;text-transform:capitalize;">${order.delivery_method}</div>
                ${order.delivery_address ? `<div style="font-size:11px;color:#6B7280;">${order.delivery_address}</div>` : ''}
            </td>
        </tr></table>
        <table width="100%" style="border-collapse:collapse;margin-bottom:16px;">
            <thead><tr style="background:#1a237e;">
                <th style="padding:10px;font-size:11px;color:white;text-align:left;">Product</th>
                <th style="padding:10px;font-size:11px;color:white;text-align:center;">Qty</th>
                <th style="padding:10px;font-size:11px;color:white;text-align:right;">Unit Price</th>
                <th style="padding:10px;font-size:11px;color:white;text-align:right;">Total</th>
            </tr></thead>
            <tbody>${rowsHtml}</tbody>
        </table>
        <table style="float:right;width:260px;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;border-collapse:collapse;margin-bottom:16px;">
            <tr><td style="padding:8px 14px;font-size:12px;border-bottom:1px solid #f3f4f6;">Subtotal</td><td style="padding:8px 14px;font-size:12px;text-align:right;border-bottom:1px solid #f3f4f6;">${fmtAmt(subtotal)}</td></tr>
            ${serviceFee > 0 ? `<tr><td style="padding:8px 14px;font-size:12px;border-bottom:1px solid #f3f4f6;">Service Fee</td><td style="padding:8px 14px;font-size:12px;text-align:right;border-bottom:1px solid #f3f4f6;">${fmtAmt(serviceFee)}</td></tr>` : ''}
            <tr style="background:#1a237e;"><td style="padding:10px 14px;font-size:14px;font-weight:700;color:white;">TOTAL</td><td style="padding:10px 14px;font-size:14px;font-weight:700;color:white;text-align:right;">${fmtAmt(total)}</td></tr>
        </table>
        <div style="clear:both;"></div>
        <table width="100%" style="border:1px solid #e5e7eb;border-radius:8px;padding:12px 14px;margin-bottom:14px;border-collapse:collapse;"><tr>
            <td style="padding:0 16px 0 0;vertical-align:top;"><div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#9CA3AF;margin-bottom:3px;">Payment Method</div><div style="font-size:13px;font-weight:700;color:#111827;">${METHOD_LABELS[order.payment_method] ?? order.payment_method}</div></td>
            ${order.mpesa_phone ? `<td style="padding:0 16px 0 0;vertical-align:top;"><div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#9CA3AF;margin-bottom:3px;">M-Pesa Phone</div><div style="font-size:13px;font-weight:700;color:#111827;">${order.mpesa_phone}</div></td>` : ''}
            ${order.mpesa_code  ? `<td style="padding:0 16px 0 0;vertical-align:top;"><div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#9CA3AF;margin-bottom:3px;">M-Pesa Code</div><div style="font-size:13px;font-weight:700;color:#111827;font-family:monospace;">${order.mpesa_code}</div></td>` : ''}
            ${paybill ? `<td style="vertical-align:top;"><div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#9CA3AF;margin-bottom:3px;">Paybill / Account</div><div style="font-size:13px;font-weight:700;color:#111827;">${paybill}${paybillAcct ? ' / ' + paybillAcct : ''}</div></td>` : ''}
        </tr></table>
        ${order.notes ? `<div style="padding:10px 14px;background:#f9fafb;border-radius:8px;border:1px solid #e5e7eb;font-size:12px;color:#4B5563;margin-bottom:14px;"><strong>Notes:</strong> ${order.notes}</div>` : ''}
        <table width="100%" style="border-top:1px solid #e5e7eb;padding-top:10px;"><tr>
            <td style="font-size:10px;color:#9CA3AF;">${companyName}${address ? ' · ' + address : ''}</td>
            <td style="font-size:10px;color:#9CA3AF;text-align:right;">${email}${website ? ' · ' + website : ''}</td>
        </tr></table>
    </body></html>`)
    win.document.close()
    win.focus()
    setTimeout(() => win.print(), 400)
}

// ─── New Sale ─────────────────────────────────────────────────────────────────
const showSaleModal   = ref(false)
const saleLoading     = ref(false)
const saleError       = ref('')
const saleSuccess     = ref(false)
const catalog         = ref<CatalogProduct[]>([])
const catalogLoading  = ref(false)
const productSearch   = ref('')

const saleForm = ref({
    client_name:      '',
    client_email:     '',
    client_phone:     '',
    payment_method:   'cash' as 'cash' | 'mpesa' | 'bank_transfer' | 'card',
    mpesa_code:       '',
    delivery_method:  'pickup' as 'pickup' | 'delivery',
    delivery_address: '',
    notes:            '',
    payment_status:   'paid' as 'paid' | 'pending',
})

const saleItems = ref<SaleItem[]>([])

const filteredCatalog = computed(() => {
    const q = productSearch.value.toLowerCase()
    return !q ? catalog.value : catalog.value.filter(p => p.name.toLowerCase().includes(q))
})

const saleSubtotal = computed(() => saleItems.value.reduce((s, i) => s + i.unit_price * i.quantity, 0))
const saleTotal    = computed(() => saleSubtotal.value)

async function openSaleModal() {
    saleItems.value       = []
    saleError.value       = ''
    saleSuccess.value     = false
    productSearch.value   = ''
    saleForm.value = {
        client_name: '', client_email: '', client_phone: '',
        payment_method: 'cash', mpesa_code: '',
        delivery_method: 'pickup', delivery_address: '', notes: '',
        payment_status: 'paid',
    }
    showSaleModal.value = true

    if (catalog.value.length === 0) {
        catalogLoading.value = true
        try {
            const { data } = await api.get('/orders/products')
            catalog.value = data
        } catch {}
        finally { catalogLoading.value = false }
    }
}

function addToSale(product: CatalogProduct) {
    const existing = saleItems.value.find(i => i.product.id === product.id)
    if (existing) {
        existing.quantity++
    } else {
        saleItems.value.push({ product, quantity: 1, unit_price: product.price, note: '' })
    }
}

function removeFromSale(idx: number) {
    saleItems.value.splice(idx, 1)
}

function saleItemStock(item: SaleItem) {
    const stock = item.product.stock_quantity
    if (stock === null || stock === undefined) return null
    return stock
}

async function submitSale() {
    if (!saleForm.value.client_name) { saleError.value = 'Client name is required.'; return }
    if (saleItems.value.length === 0) { saleError.value = 'Add at least one product.'; return }

    saleLoading.value = true
    saleError.value   = ''
    try {
        const { data } = await api.post('/orders/create', {
            client_name:      saleForm.value.client_name,
            client_email:     saleForm.value.client_email || undefined,
            client_phone:     saleForm.value.client_phone || undefined,
            items: saleItems.value.map(i => ({
                product_id: i.product.id,
                quantity:   i.quantity,
                unit_price: i.unit_price,
                note:       i.note || undefined,
            })),
            payment_method:   saleForm.value.payment_method,
            payment_status:   saleForm.value.payment_status,
            status:           saleForm.value.payment_status === 'paid' ? 'processing' : 'pending_payment',
            delivery_method:  saleForm.value.delivery_method,
            delivery_address: saleForm.value.delivery_address || undefined,
            notes:            saleForm.value.mpesa_code
                ? `M-Pesa: ${saleForm.value.mpesa_code}${saleForm.value.notes ? '\n' + saleForm.value.notes : ''}`
                : (saleForm.value.notes || undefined),
        })

        // Update local catalog stock
        saleItems.value.forEach(item => {
            const cat = catalog.value.find(p => p.id === item.product.id)
            if (cat && cat.stock_quantity !== null) {
                cat.stock_quantity = Math.max(0, (cat.stock_quantity ?? 0) - item.quantity)
            }
        })

        orders.value.unshift({
            ...data,
            items: typeof data.items === 'string' ? JSON.parse(data.items) : (data.items ?? []),
        })
        saleSuccess.value = true
        setTimeout(() => { showSaleModal.value = false }, 1400)
    } catch (e: any) {
        saleError.value = e?.response?.data?.message ?? 'Failed to create order.'
    } finally {
        saleLoading.value = false
    }
}

onMounted(load)
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Orders</h1>
                <p class="text-sm text-gray-500 mt-1">Product orders placed from the website</p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="load"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Refresh
                </button>
                <button @click="openSaleModal"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white transition-all hover:-translate-y-0.5"
                        style="background:#00bcd4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Sale
                </button>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center items-center py-20">
            <div class="w-8 h-8 border-4 border-t-transparent rounded-full animate-spin"
                 style="border-color:#00bcd4;border-top-color:transparent"></div>
        </div>

        <template v-else>
            <!-- Stat Cards -->
            <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <div class="text-2xl font-bold text-gray-900">{{ orders.length }}</div>
                    <div class="text-sm text-gray-500 mt-1 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-blue-500 inline-block"></span>
                        Total Orders
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <div class="text-2xl font-bold text-orange-600">{{ countStatus('pending_payment') }}</div>
                    <div class="text-sm text-gray-500 mt-1 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-orange-400 inline-block"></span>
                        Pending Payment
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <div class="text-2xl font-bold text-green-600">{{ countPayment('paid') }}</div>
                    <div class="text-sm text-gray-500 mt-1 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
                        Paid
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <div class="text-2xl font-bold" style="color:#00bcd4">{{ fmt(totalRevenue()) }}</div>
                    <div class="text-sm text-gray-500 mt-1 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full inline-block" style="background:#00bcd4"></span>
                        Revenue
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3">
                <input v-model="searchQ" placeholder="Search order # or customer…"
                       class="flex-1 min-w-52 border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all" />
                <select v-model="filterStatus"
                        class="border border-gray-300 rounded-xl px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-cyan-500 transition-all">
                    <option value="">All Statuses</option>
                    <option value="pending_payment">Pending Payment</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <select v-model="filterPayment"
                        class="border border-gray-300 rounded-xl px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-cyan-500 transition-all">
                    <option value="">All Payments</option>
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="failed">Failed</option>
                    <option value="refunded">Refunded</option>
                </select>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div v-if="filtered.length === 0" class="text-sm text-gray-400 text-center py-12">
                    No orders found
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="order in filtered" :key="order.id"
                                class="hover:bg-gray-50 transition-colors cursor-pointer"
                                @click="openOrder(order)">
                                <td class="px-6 py-4">
                                    <div class="text-xs font-mono font-semibold" style="color:#00bcd4">{{ order.order_number }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-gray-900">{{ order.user?.name ?? '—' }}</div>
                                    <div class="text-xs text-gray-500">{{ order.user?.email }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ order.items?.length ?? 0 }} item{{ (order.items?.length ?? 0) !== 1 ? 's' : '' }}
                                    </div>
                                    <div class="text-xs text-gray-400 max-w-40 truncate">{{ itemsPreview(order.items ?? []) }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900 whitespace-nowrap">{{ fmt(order.total) }}</td>
                                <td class="px-6 py-4">
                                    <span :class="['px-2.5 py-0.5 rounded-full text-xs font-semibold border', paymentClass(order.payment_status)]">
                                        {{ PAYMENT_LABELS[order.payment_status] ?? order.payment_status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['px-2.5 py-0.5 rounded-full text-xs font-semibold border', statusClass(order.status)]">
                                        {{ STATUS_LABELS[order.status] ?? order.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ fmtDate(order.created_at) }}</td>
                                <td class="px-6 py-4" @click.stop>
                                    <div class="flex items-center gap-2">
                                        <button @click="printReceipt(order)"
                                                class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors"
                                                title="Print Receipt">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                            </svg>
                                        </button>
                                        <button @click="deleteOrder(order.id)"
                                                class="p-1.5 text-gray-400 hover:text-red-500 rounded-lg hover:bg-red-50 transition-colors"
                                                title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <!-- Order Detail / Edit Modal -->
        <Teleport to="body">
            <Transition name="fade">
                <div v-if="showModal && selected"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                     @click.self="showModal = false">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

                        <!-- Modal header -->
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white z-10">
                            <div>
                                <h3 class="text-base font-bold text-gray-900">
                                    Order <span class="font-mono" style="color:#00bcd4">{{ selected.order_number }}</span>
                                </h3>
                                <p class="text-xs text-gray-500 mt-0.5">{{ fmtDate(selected.created_at) }}</p>
                            </div>
                            <button @click="showModal = false"
                                    class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <div class="p-6 space-y-5">
                            <!-- Success / Error banners -->
                            <div v-if="saveSuccess"
                                 class="rounded-xl p-4 bg-green-50 text-green-700 border border-green-200 text-sm flex items-center gap-2">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Order updated successfully!
                            </div>
                            <div v-if="saveError"
                                 class="rounded-xl p-3 bg-red-50 text-red-600 border border-red-200 text-sm">
                                {{ saveError }}
                            </div>

                            <!-- Customer + Delivery -->
                            <div class="grid sm:grid-cols-2 gap-4">
                                <div class="rounded-xl bg-gray-50 border border-gray-100 p-4">
                                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Customer</h4>
                                    <p class="font-semibold text-gray-900">{{ selected.user?.name ?? '—' }}</p>
                                    <p v-if="selected.user?.email" class="text-sm text-gray-500">{{ selected.user.email }}</p>
                                    <p v-if="selected.user?.phone || selected.mpesa_phone" class="text-sm text-gray-500">
                                        {{ selected.user?.phone ?? selected.mpesa_phone }}
                                    </p>
                                </div>
                                <div class="rounded-xl bg-gray-50 border border-gray-100 p-4">
                                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Delivery</h4>
                                    <p class="font-semibold text-gray-900 capitalize">{{ selected.delivery_method }}</p>
                                    <p v-if="selected.delivery_address" class="text-sm text-gray-500">{{ selected.delivery_address }}</p>
                                </div>
                            </div>

                            <!-- Items table -->
                            <div>
                                <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Items</h4>
                                <div class="rounded-xl border border-gray-100 overflow-hidden">
                                    <table class="w-full">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Product</th>
                                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500">Qty</th>
                                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Unit Price</th>
                                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50">
                                            <tr v-for="(item, i) in selected.items" :key="i">
                                                <td class="px-4 py-3">
                                                    <div class="text-sm font-medium text-gray-900">{{ item.product?.name ?? item.key }}</div>
                                                    <div v-if="item.note" class="text-xs text-gray-400">{{ item.note }}</div>
                                                </td>
                                                <td class="px-4 py-3 text-center text-sm text-gray-700">{{ item.quantity }}</td>
                                                <td class="px-4 py-3 text-right text-sm text-gray-700">{{ fmt(item.unit_price) }}</td>
                                                <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900">{{ fmt(item.line_total) }}</td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr v-if="(selected.service_fee ?? 0) > 0" class="border-t border-gray-100">
                                                <td colspan="3" class="px-4 py-2 text-right text-xs text-gray-400">Service Fee</td>
                                                <td class="px-4 py-2 text-right text-sm text-gray-700">{{ fmt(selected.service_fee) }}</td>
                                            </tr>
                                            <tr class="border-t-2 border-gray-200 bg-gray-50">
                                                <td colspan="3" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Total</td>
                                                <td class="px-4 py-3 text-right text-base font-bold text-gray-900">{{ fmt(selected.total) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <!-- Status updates -->
                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Order Status</label>
                                    <select v-model="editStatus"
                                            class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all">
                                        <option value="pending_payment">Pending Payment</option>
                                        <option value="processing">Processing</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Status</label>
                                    <select v-model="editPaymentStatus"
                                            class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all">
                                        <option value="pending">Pending</option>
                                        <option value="paid">Paid</option>
                                        <option value="failed">Failed</option>
                                        <option value="refunded">Refunded</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Payment info + M-Pesa code -->
                            <div class="grid sm:grid-cols-2 gap-4">
                                <div class="rounded-xl bg-gray-50 border border-gray-100 p-4">
                                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Payment Method</h4>
                                    <p class="font-semibold text-gray-900">{{ METHOD_LABELS[selected.payment_method] ?? selected.payment_method }}</p>
                                    <p v-if="selected.mpesa_phone" class="text-sm text-gray-500 mt-1">Phone: {{ selected.mpesa_phone }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">M-Pesa Confirmation Code</label>
                                    <input v-model="editMpesaCode"
                                           placeholder="e.g. QDK3XXXXXXX"
                                           class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all font-mono"
                                           style="text-transform:uppercase" />
                                    <p class="text-xs text-gray-400 mt-1">Enter the M-Pesa code to confirm payment.</p>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea v-model="editNotes" rows="3"
                                          placeholder="Internal notes…"
                                          class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all resize-none"></textarea>
                            </div>
                        </div>

                        <!-- Modal footer -->
                        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl flex items-center justify-between gap-3">
                            <button @click="printReceipt(selected)"
                                    class="flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-white transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                                Print Receipt
                            </button>
                            <div class="flex gap-3">
                                <button @click="showModal = false"
                                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-white transition-colors">
                                    Cancel
                                </button>
                                <button @click="saveOrder" :disabled="saving"
                                        class="px-5 py-2 rounded-lg text-sm font-semibold text-white flex items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed transition-all hover:-translate-y-0.5"
                                        style="background:#1f2937">
                                    <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    {{ saving ? 'Saving…' : 'Save Changes' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- New Sale Modal -->
        <Teleport to="body">
            <Transition name="fade">
                <div v-if="showSaleModal"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                     @click.self="showSaleModal = false">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[92vh] overflow-y-auto">

                        <!-- Header -->
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white z-10">
                            <h3 class="text-base font-bold text-gray-900">New Sale</h3>
                            <button @click="showSaleModal = false"
                                    class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <div class="p-6 space-y-6">
                            <!-- Success / Error -->
                            <div v-if="saleSuccess"
                                 class="rounded-xl p-4 bg-green-50 text-green-700 border border-green-200 text-sm flex items-center gap-2">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Order created successfully!
                            </div>
                            <div v-if="saleError"
                                 class="rounded-xl p-3 bg-red-50 text-red-600 border border-red-200 text-sm">
                                {{ saleError }}
                            </div>

                            <div class="grid lg:grid-cols-2 gap-6">
                                <!-- Left: Client + Payment -->
                                <div class="space-y-4">
                                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Client</h4>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                                        <input v-model="saleForm.client_name" placeholder="Full name"
                                               class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                        <input v-model="saleForm.client_email" type="email" placeholder="client@email.com"
                                               class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                        <input v-model="saleForm.client_phone" placeholder="+254…"
                                               class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all" />
                                    </div>

                                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider pt-2">Payment</h4>
                                    <div class="grid grid-cols-2 gap-2">
                                        <button v-for="m in [{key:'cash',label:'Cash'},{key:'mpesa',label:'M-Pesa'},{key:'bank_transfer',label:'Bank Transfer'},{key:'card',label:'Card'}]"
                                                :key="m.key" type="button"
                                                @click="saleForm.payment_method = m.key as any"
                                                :class="['px-3 py-2 rounded-xl border text-sm font-medium transition-all',
                                                         saleForm.payment_method === m.key
                                                             ? 'border-cyan-500 bg-cyan-50 text-cyan-700'
                                                             : 'border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50']">
                                            {{ m.label }}
                                        </button>
                                    </div>
                                    <div v-if="saleForm.payment_method === 'mpesa'">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">M-Pesa Code</label>
                                        <input v-model="saleForm.mpesa_code" placeholder="e.g. QDK3XXXXXXX"
                                               class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 font-mono"
                                               style="text-transform:uppercase" />
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Status</label>
                                            <select v-model="saleForm.payment_status"
                                                    class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-cyan-500 transition-all">
                                                <option value="paid">Paid</option>
                                                <option value="pending">Pending</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Delivery</label>
                                            <select v-model="saleForm.delivery_method"
                                                    class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm text-gray-700 outline-none focus:border-cyan-500 transition-all">
                                                <option value="pickup">Pickup</option>
                                                <option value="delivery">Delivery</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div v-if="saleForm.delivery_method === 'delivery'">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Address</label>
                                        <input v-model="saleForm.delivery_address" placeholder="Address…"
                                               class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                        <textarea v-model="saleForm.notes" rows="2" placeholder="Internal notes…"
                                                  class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all resize-none"></textarea>
                                    </div>
                                </div>

                                <!-- Right: Product selection -->
                                <div class="space-y-4">
                                    <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Products</h4>

                                    <!-- Search catalog -->
                                    <input v-model="productSearch" placeholder="Search products…"
                                           class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all" />

                                    <div v-if="catalogLoading" class="text-center py-4 text-sm text-gray-400">Loading products…</div>
                                    <div v-else class="max-h-44 overflow-y-auto rounded-xl border border-gray-100 divide-y divide-gray-50">
                                        <div v-for="p in filteredCatalog" :key="p.id"
                                             class="flex items-center justify-between px-3 py-2.5 hover:bg-gray-50 transition-colors cursor-pointer"
                                             @click="addToSale(p)">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ p.name }}</div>
                                                <div class="text-xs text-gray-400">
                                                    KES {{ Number(p.price).toLocaleString() }}
                                                    <span v-if="p.unit"> · {{ p.unit }}</span>
                                                    <span v-if="p.stock_quantity !== null && p.stock_quantity !== undefined"
                                                          :class="['ml-2 font-semibold', p.stock_quantity === 0 ? 'text-red-500' : p.stock_quantity <= 5 ? 'text-orange-500' : 'text-emerald-600']">
                                                        {{ p.stock_quantity === 0 ? 'Out of stock' : `${p.stock_quantity} left` }}
                                                    </span>
                                                </div>
                                            </div>
                                            <button type="button"
                                                    :disabled="p.stock_quantity === 0"
                                                    class="w-7 h-7 rounded-full flex items-center justify-center text-white text-lg font-bold disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                                                    style="background:#00bcd4">+</button>
                                        </div>
                                        <div v-if="filteredCatalog.length === 0" class="py-6 text-center text-sm text-gray-400">No products found</div>
                                    </div>

                                    <!-- Cart -->
                                    <div v-if="saleItems.length > 0" class="space-y-2">
                                        <h5 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Cart</h5>
                                        <div class="rounded-xl border border-gray-100 divide-y divide-gray-50">
                                            <div v-for="(item, idx) in saleItems" :key="idx" class="px-3 py-2.5 space-y-1.5">
                                                <div class="flex items-start justify-between gap-2">
                                                    <div class="flex-1 min-w-0">
                                                        <div class="text-sm font-medium text-gray-900 truncate">{{ item.product.name }}</div>
                                                        <div v-if="item.product.stock_quantity !== null && item.product.stock_quantity !== undefined"
                                                             class="text-xs text-gray-400">
                                                            Stock: {{ item.product.stock_quantity }}
                                                        </div>
                                                    </div>
                                                    <button @click="removeFromSale(idx)"
                                                            class="text-gray-400 hover:text-red-500 transition-colors text-lg leading-none shrink-0">&times;</button>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <div class="flex items-center gap-1 border border-gray-200 rounded-lg overflow-hidden">
                                                        <button type="button" @click="item.quantity = Math.max(1, item.quantity - 1)"
                                                                class="px-2.5 py-1 text-gray-500 hover:bg-gray-50 text-sm">−</button>
                                                        <span class="px-2 text-sm font-semibold text-gray-900 min-w-6 text-center">{{ item.quantity }}</span>
                                                        <button type="button"
                                                                @click="item.quantity = saleItemStock(item) !== null ? Math.min(saleItemStock(item)!, item.quantity + 1) : item.quantity + 1"
                                                                class="px-2.5 py-1 text-gray-500 hover:bg-gray-50 text-sm">+</button>
                                                    </div>
                                                    <span class="text-xs text-gray-400">×</span>
                                                    <input type="number" v-model.number="item.unit_price" min="0"
                                                           class="w-24 border border-gray-200 rounded-lg px-2 py-1 text-sm outline-none focus:border-cyan-500 text-gray-700" />
                                                    <span class="text-sm font-semibold text-gray-900 ml-auto">
                                                        {{ fmt(item.unit_price * item.quantity) }}
                                                    </span>
                                                </div>
                                                <input v-model="item.note" placeholder="Note (optional)"
                                                       class="w-full border border-gray-100 rounded-lg px-2 py-1 text-xs outline-none focus:border-cyan-500 text-gray-600" />
                                            </div>
                                        </div>

                                        <!-- Total -->
                                        <div class="flex items-center justify-between rounded-xl bg-gray-50 border border-gray-100 px-4 py-3">
                                            <span class="text-sm font-semibold text-gray-700">Total</span>
                                            <span class="text-lg font-bold text-gray-900">{{ fmt(saleTotal) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
                            <button @click="showSaleModal = false"
                                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-white transition-colors">
                                Cancel
                            </button>
                            <button @click="submitSale" :disabled="saleLoading || saleSuccess"
                                    class="px-5 py-2 rounded-lg text-sm font-semibold text-white flex items-center gap-2 disabled:opacity-60 transition-all hover:-translate-y-0.5"
                                    style="background:#1f2937">
                                <svg v-if="saleLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                {{ saleLoading ? 'Creating…' : 'Create Order' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
