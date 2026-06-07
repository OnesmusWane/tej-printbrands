<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api from '../api'

interface Invoice { id: number; invoice_number: string; client: string; amount: number; paid_amount?: number; status: string }
interface ServiceItem { id: number; label: string; type: 'order' | 'booking' | 'invoice'; amount?: number; client?: string }
interface Payment {
  id: number; payment_number?: string; invoice_id?: number; invoice_number?: string
  client?: string; client_name?: string; amount: number; method?: string; payment_method?: string
  reference?: string; status?: string; payment_date?: string; paid_at?: string; created_at?: string
}

const loading   = ref(true)
const saving         = ref(false)
const showModal      = ref(false)
const updatingPayId  = ref<number | null>(null)
const payments  = ref<Payment[]>([])
const invoices  = ref<Invoice[]>([])
const search    = ref('')
const activeTab = ref('all')

const tabs = [
  { label: 'All',           value: 'all' },
  { label: 'Cash',          value: 'cash' },
  { label: 'M-Pesa',        value: 'mpesa' },
  { label: 'Bank Transfer', value: 'bank_transfer' },
]

const form = ref({
  invoice_id: 0,
  client:     '',
  amount:     0,
  method:     'cash' as string,
  reference:  '',
  date:       new Date().toISOString().slice(0, 10),
})

const formError = ref('')

function onInvoiceSelect() {
  if (!form.value.invoice_id) return
  const inv = invoices.value.find(i => i.id === form.value.invoice_id)
  if (inv) {
    form.value.client = inv.client
    form.value.amount = Math.max(0, inv.amount - (inv.paid_amount ?? 0))
  }
}

// ─── computed ────────────────────────────────────────────────────────────────
function normalizeMethod(m?: string) { return (m ?? '').toLowerCase().replace(/[\s-]/g, '_') }

const filtered = computed(() => {
  let list = payments.value
  if (activeTab.value !== 'all') {
    list = list.filter(p => normalizeMethod(p.method ?? p.payment_method) === activeTab.value)
  }
  if (search.value.trim()) {
    const q = search.value.toLowerCase()
    list = list.filter(p =>
      (p.client ?? p.client_name ?? '').toLowerCase().includes(q) ||
      (p.reference ?? '').toLowerCase().includes(q) ||
      (p.invoice_number ?? '').toLowerCase().includes(q) ||
      (p.payment_number ?? '').toLowerCase().includes(q)
    )
  }
  return list
})

function methodTotal(method: string) {
  return payments.value.filter(p => normalizeMethod(p.method ?? p.payment_method) === method).reduce((s, p) => s + Number(p.amount ?? 0), 0)
}
function countMethod(method: string) {
  return payments.value.filter(p => normalizeMethod(p.method ?? p.payment_method) === method).length
}
function totalRevenue() { return payments.value.reduce((s, p) => s + Number(p.amount ?? 0), 0) }

function methodLabel(m?: string) {
  const key = normalizeMethod(m)
  return ({ cash: 'Cash', mpesa: 'M-Pesa', bank_transfer: 'Bank Transfer' })[key] ?? (m ?? '-')
}
function methodClass(m?: string) {
  const key = normalizeMethod(m)
  return ({ cash: 'bg-green-100 text-green-700 border-green-200', mpesa: 'bg-emerald-100 text-emerald-700 border-emerald-200', bank_transfer: 'bg-blue-100 text-blue-700 border-blue-200' })[key] ?? 'bg-gray-100 text-gray-700 border-gray-200'
}
function fmt(v: any) { return Number(v ?? 0).toLocaleString() }
function fmtDate(d?: string) { if (!d) return '-'; return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) }

// ─── modal ────────────────────────────────────────────────────────────────────
function openModal() {
  form.value = { invoice_id: 0, client: '', amount: 0, method: 'cash', reference: '', date: new Date().toISOString().slice(0, 10) }
  formError.value = ''
  showModal.value = true
}
function closeModal() { showModal.value = false }

async function updatePaymentStatus(pmt: Payment, status: string) {
  updatingPayId.value = pmt.id
  try {
    const { data } = await api.patch(`/payments/${pmt.id}`, { status })
    const idx = payments.value.findIndex(p => p.id === pmt.id)
    if (idx !== -1) payments.value[idx] = { ...payments.value[idx], ...(data.data ?? data) }
  } catch (e) { console.error(e) }
  finally { updatingPayId.value = null }
}

async function submitPayment() {
  if (!form.value.client) { formError.value = 'Client name is required.'; return }
  if (!form.value.amount) { formError.value = 'Amount is required.'; return }
  saving.value = true; formError.value = ''
  try {
    const payload: any = {
      client:     form.value.client,
      amount:     form.value.amount,
      method:     form.value.method,
      reference:  form.value.reference || null,
      paid_at:    form.value.date,
    }
    if (form.value.invoice_id) payload.invoice_id = form.value.invoice_id
    const { data } = await api.post('/payments', payload)
    payments.value.unshift(data.data ?? data)
    // Update invoice paid amount locally
    if (form.value.invoice_id) {
      const idx = invoices.value.findIndex(i => i.id === form.value.invoice_id)
      if (idx !== -1) {
        invoices.value[idx].paid_amount = (invoices.value[idx].paid_amount ?? 0) + form.value.amount
        if (invoices.value[idx].paid_amount >= invoices.value[idx].amount) invoices.value[idx].status = 'paid'
        else invoices.value[idx].status = 'partial'
      }
    }
    closeModal()
  } catch (e: any) {
    formError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {})?.[0]?.[0] ?? 'Payment failed.'
  } finally { saving.value = false }
}

function amountInWords(n: number): string {
  const ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
    'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen']
  const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety']
  function tw(num: number): string {
    if (num === 0) return ''
    if (num < 20) return ones[num]
    if (num < 100) return tens[Math.floor(num / 10)] + (num % 10 ? '-' + ones[num % 10] : '')
    if (num < 1000) return ones[Math.floor(num / 100)] + ' Hundred' + (num % 100 ? ' ' + tw(num % 100) : '')
    if (num < 1000000) return tw(Math.floor(num / 1000)) + ' Thousand' + (num % 1000 ? ' ' + tw(num % 1000) : '')
    return tw(Math.floor(num / 1000000)) + ' Million' + (num % 1000000 ? ' ' + tw(num % 1000000) : '')
  }
  const whole = Math.floor(n)
  const cents = Math.round((n - whole) * 100)
  return (tw(whole) || 'Zero') + ' Shillings' + (cents > 0 ? ' and ' + tw(cents) + ' Cents' : ' Only')
}

async function printReceipt(pmt: Payment) {
  const s: Record<string, any> = {}
  try {
    const { data } = await api.get('/site-settings')
    for (const r of (Array.isArray(data) ? data : data.data ?? [])) {
      s[r.key] = typeof r.value === 'string' ? JSON.parse(r.value) : r.value
    }
  } catch {}

  const companyName    = s.company?.name || s.company?.company_name || 'Tej Printbrands'
  const logoUrl        = s.company?.logo_url || ''
  const phone          = s.contact?.phone || ''
  const phoneSecondary = s.contact?.phone_secondary || ''
  const email          = s.contact?.email || ''
  const website        = s.contact?.website || ''

  const receiptNo  = pmt.payment_number ?? `RCT-${pmt.id}`
  const clientName = pmt.client ?? pmt.client_name ?? ''
  const amount     = Number(pmt.amount ?? 0)
  const method     = (pmt.method ?? pmt.payment_method ?? '').toLowerCase().replace(/[\s-]/g, '_')
  const reference  = pmt.reference ?? ''
  const paidDate   = new Date(pmt.paid_at ?? pmt.payment_date ?? pmt.created_at ?? '').toLocaleDateString('en-GB', { day: '2-digit', month: 'long', year: 'numeric' })
  const invoiceRef = pmt.invoice_number ?? (pmt.invoice_id ? `Invoice #${pmt.invoice_id}` : '')

  const isCash   = method === 'cash'
  const isMpesa  = method === 'mpesa'
  const isCheque = method === 'bank_transfer' || method === 'cheque'
  const box = (checked: boolean) => checked
    ? `<span style="display:inline-block;width:14px;height:14px;border:1.5px solid #1a237e;background:#1a237e;margin-right:3px;vertical-align:middle;text-align:center;line-height:12px;font-size:11px;color:#fff;">&#10003;</span>`
    : `<span style="display:inline-block;width:14px;height:14px;border:1.5px solid #555;margin-right:3px;vertical-align:middle;"></span>`
  const dotLine = (len = 280) => `<span style="display:inline-block;width:${len}px;border-bottom:1.5px dotted #555;vertical-align:bottom;margin-left:6px;"></span>`

  const html = `<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Receipt ${receiptNo}</title>
<style>
@page{size:A5 landscape;margin:12mm 14mm}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,Helvetica,sans-serif;color:#111;background:#fff;font-size:13px}
</style>
</head><body>

<!-- TOP HEADER -->
<div style="display:flex;align-items:stretch;border:2px solid #1a237e;margin-bottom:14px;">
  <!-- Logo block -->
  <div style="padding:10px 14px;border-right:2px solid #1a237e;display:flex;align-items:center;justify-content:center;min-width:90px;">
    ${logoUrl
      ? `<img src="${logoUrl}" style="max-width:72px;max-height:56px;object-fit:contain;" alt="">`
      : `<div style="font-size:16px;font-weight:900;color:#1a237e;letter-spacing:1px;">${companyName.split(' ')[0].toUpperCase()}</div>`}
  </div>
  <!-- Receipt title -->
  <div style="padding:10px 20px;border-right:2px solid #1a237e;display:flex;align-items:center;justify-content:center;flex:1;">
    <div style="text-align:center;">
      <div style="font-size:28px;font-weight:900;color:#1a237e;letter-spacing:6px;">RECEIPT</div>
      <div style="font-size:10px;color:#6B7280;margin-top:2px;">No. ${receiptNo}</div>
    </div>
  </div>
  <!-- Contact block -->
  <div style="padding:10px 14px;display:flex;flex-direction:column;justify-content:center;font-size:11px;min-width:170px;">
    <div style="margin-bottom:3px;"><strong>Tel:</strong> ${phone}${phoneSecondary ? ' / ' + phoneSecondary : ''}</div>
    ${website ? `<div style="margin-bottom:3px;"><strong>Web:</strong> ${website}</div>` : ''}
    <div><strong>Email:</strong> ${email}</div>
  </div>
</div>

<!-- DATE ROW -->
<div style="display:flex;justify-content:flex-end;margin-bottom:10px;font-size:12px;">
  <span><strong>Date:</strong> ${paidDate}</span>
</div>

<!-- RECEIVED FROM -->
<div style="display:flex;align-items:flex-end;margin-bottom:9px;">
  <span style="white-space:nowrap;font-weight:600;">Received from</span>
  <span style="flex:1;border-bottom:1.5px dotted #555;margin:0 8px;min-width:100px;">&nbsp;${clientName}&nbsp;</span>
  <div style="border:1.5px solid #1a237e;padding:5px 12px;min-width:110px;text-align:center;font-weight:700;font-size:13px;">
    Kshs &nbsp;<span style="font-size:15px;">${amount.toLocaleString()}</span>
  </div>
</div>

<!-- SHILLINGS IN WORDS -->
<div style="display:flex;align-items:flex-end;margin-bottom:9px;">
  <span style="white-space:nowrap;font-weight:600;">Kenyan Shillings</span>
  ${dotLine(330)}
  <span style="white-space:nowrap;margin-left:6px;font-size:11px;font-style:italic;">${amountInWords(amount)}</span>
</div>

<!-- DESCRIPTION -->
<div style="display:flex;align-items:flex-end;margin-bottom:9px;">
  <span style="white-space:nowrap;color:#555;">(description of goods/services)</span>
  ${dotLine(260)}
</div>

<!-- BEING PAYMENT OF -->
<div style="display:flex;align-items:flex-end;margin-bottom:16px;">
  <span style="white-space:nowrap;font-weight:600;">Being payment of</span>
  <span style="flex:1;border-bottom:1.5px dotted #555;margin:0 8px;">&nbsp;${invoiceRef}&nbsp;</span>
  ${reference ? `<span style="font-size:11px;color:#555;">Ref: ${reference}</span>` : ''}
</div>

<!-- PAYMENT METHOD + SIGNATURE ROW -->
<div style="display:flex;justify-content:space-between;align-items:flex-end;border-top:1.5px solid #1a237e;padding-top:10px;">
  <div style="font-size:13px;">
    ${box(isCash)} <span style="margin-right:16px;">Cash</span>
    ${box(isMpesa)} <span style="margin-right:16px;">M-Pesa</span>
    ${box(isCheque)} Cheque No. <span style="display:inline-block;width:90px;border-bottom:1.5px dotted #555;margin-left:4px;">&nbsp;${isCheque ? reference : ''}&nbsp;</span>
  </div>
  <div style="text-align:right;">
    <div style="font-size:11px;color:#555;margin-bottom:4px;">Authorised Signature</div>
    <div style="width:130px;border-bottom:1.5px solid #555;"></div>
    <div style="font-size:12px;font-weight:700;margin-top:4px;color:#1a237e;">FOR: ${companyName.split(' ')[0].toUpperCase()}</div>
  </div>
</div>

<!-- TAGLINE -->
<div style="margin-top:12px;border-top:2px solid #1a237e;padding-top:8px;text-align:center;font-size:9px;color:#6B7280;text-transform:uppercase;letter-spacing:2px;">
  Graphic Design &bull; T-Shirts &bull; General Branding &bull; Digital Printing &bull; Signage &bull; Promotional Items
</div>

</body></html>`

  const win = window.open('', '_blank')
  if (win) { win.document.write(html); win.document.close(); setTimeout(() => win.print(), 250) }
}

onMounted(async () => {
  try {
    const [pRes, iRes] = await Promise.all([api.get('/payments'), api.get('/invoices?status=unpaid&per_page=200')])
    payments.value = Array.isArray(pRes.data) ? pRes.data : (pRes.data.data ?? [])
    invoices.value = Array.isArray(iRes.data) ? iRes.data : (iRes.data.data ?? [])
  } catch (e) { console.error(e) }
  finally { loading.value = false }
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Payments</h1>
        <p class="text-sm text-gray-500 mt-1">Track and record all payment transactions</p>
      </div>
      <button @click="openModal"
        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white shadow-sm self-start sm:self-auto"
        style="background:#00BCD4;">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Record Payment
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="w-8 h-8 border-4 border-t-transparent rounded-full animate-spin" style="border-color:#00BCD4;border-top-color:transparent;"></div>
    </div>

    <template v-else>
      <!-- Summary Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <div class="flex items-center gap-3 mb-2">
            <div class="w-9 h-9 rounded-lg bg-cyan-50 flex items-center justify-center">
              <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <span class="text-sm font-medium text-gray-600">Total Collected</span>
          </div>
          <div class="text-xl font-bold text-gray-900">KES {{ fmt(totalRevenue()) }}</div>
          <div class="text-xs text-gray-500 mt-1">{{ payments.length }} transactions</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <div class="flex items-center gap-3 mb-2">
            <div class="w-9 h-9 rounded-lg bg-green-50 flex items-center justify-center">
              <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <span class="text-sm font-medium text-gray-600">Cash</span>
          </div>
          <div class="text-xl font-bold text-gray-900">KES {{ fmt(methodTotal('cash')) }}</div>
          <div class="text-xs text-gray-500 mt-1">{{ countMethod('cash') }} transactions</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <div class="flex items-center gap-3 mb-2">
            <div class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center">
              <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            </div>
            <span class="text-sm font-medium text-gray-600">M-Pesa</span>
          </div>
          <div class="text-xl font-bold text-gray-900">KES {{ fmt(methodTotal('mpesa')) }}</div>
          <div class="text-xs text-gray-500 mt-1">{{ countMethod('mpesa') }} transactions</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <div class="flex items-center gap-3 mb-2">
            <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <span class="text-sm font-medium text-gray-600">Bank Transfer</span>
          </div>
          <div class="text-xl font-bold text-gray-900">KES {{ fmt(methodTotal('bank_transfer')) }}</div>
          <div class="text-xs text-gray-500 mt-1">{{ countMethod('bank_transfer') }} transactions</div>
        </div>
      </div>

      <!-- Table with tabs + search -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="flex items-center justify-between border-b border-gray-100 px-6 flex-wrap gap-2">
          <div class="flex">
            <button v-for="tab in tabs" :key="tab.value" @click="activeTab = tab.value"
              :class="['px-4 py-4 text-sm font-medium transition-colors border-b-2', activeTab === tab.value ? 'border-cyan-500 text-cyan-600' : 'text-gray-500 hover:text-gray-700 border-transparent']">
              {{ tab.label }}
            </button>
          </div>
          <div class="relative py-2">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input v-model="search" type="text" placeholder="Search payments..."
              class="border border-gray-300 rounded-xl pl-9 pr-3 py-2 outline-none focus:border-cyan-500 text-sm"/>
          </div>
        </div>

        <div v-if="filtered.length === 0" class="text-sm text-gray-400 text-center py-12">No payments found</div>
        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment #</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                <th class="px-6 py-4"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="pmt in filtered" :key="pmt.id" class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 text-sm font-mono text-gray-700">{{ pmt.payment_number ?? `#PMT-${pmt.id}` }}</td>
                <td class="px-6 py-4 text-sm font-mono" style="color:#00BCD4;">
                  {{ pmt.invoice_number ?? (pmt.invoice_id ? `INV #${pmt.invoice_id}` : '-') }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ pmt.client ?? pmt.client_name }}</td>
                <td class="px-6 py-4 text-sm font-semibold text-gray-900">KES {{ fmt(pmt.amount) }}</td>
                <td class="px-6 py-4">
                  <span :class="['px-2.5 py-1 rounded-full text-xs font-medium border', methodClass(pmt.method ?? pmt.payment_method)]">
                    {{ methodLabel(pmt.method ?? pmt.payment_method) }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <select :value="pmt.status ?? 'completed'"
                    @change="updatePaymentStatus(pmt, ($event.target as HTMLSelectElement).value)"
                    :disabled="updatingPayId === pmt.id"
                    class="border border-gray-200 rounded-lg px-2 py-1 text-xs outline-none focus:border-cyan-500 bg-white text-gray-700 cursor-pointer disabled:opacity-60">
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                    <option value="refunded">Refunded</option>
                    <option value="failed">Failed</option>
                  </select>
                </td>
                <td class="px-6 py-4 text-sm font-mono text-gray-600">{{ pmt.reference ?? '-' }}</td>
                <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ fmtDate(pmt.paid_at ?? pmt.payment_date ?? pmt.created_at) }}</td>
                <td class="px-6 py-4">
                  <button @click="printReceipt(pmt)" title="Print Receipt"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-cyan-600 hover:bg-cyan-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <!-- ─── Record Payment Modal ─────────────────────────────────────────────── -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="closeModal">
          <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
              <h3 class="text-base font-bold text-gray-900">Record Payment</h3>
              <button @click="closeModal" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
            <div class="p-6 space-y-4">
              <div v-if="formError" class="rounded-xl p-3 bg-red-50 text-red-600 border border-red-200 text-sm">{{ formError }}</div>

              <!-- Link to invoice -->
              <div v-if="invoices.length">
                <label class="block text-sm font-medium text-gray-700 mb-1">Link to Invoice (optional)</label>
                <select v-model.number="form.invoice_id" @change="onInvoiceSelect"
                  class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 bg-white transition-all">
                  <option :value="0">None — standalone payment</option>
                  <option v-for="inv in invoices" :key="inv.id" :value="inv.id">
                    {{ inv.invoice_number }} · {{ inv.client }} · KES {{ fmt(inv.amount - (inv.paid_amount ?? 0)) }} due
                  </option>
                </select>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Client Name <span class="text-red-500">*</span></label>
                  <input v-model="form.client" type="text" placeholder="Full name"
                    class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"/>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Amount (KES) <span class="text-red-500">*</span></label>
                  <input v-model.number="form.amount" type="number" min="1" placeholder="0"
                    class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"/>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                  <input v-model="form.date" type="date"
                    class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"/>
                </div>
              </div>

              <!-- Method -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                <div class="grid grid-cols-3 gap-2">
                  <button v-for="m in [{ v:'cash', l:'Cash' }, { v:'mpesa', l:'M-Pesa' }, { v:'bank_transfer', l:'Bank Transfer' }]" :key="m.v"
                    type="button" @click="form.method = m.v"
                    :class="['py-2 rounded-xl border text-xs font-semibold transition-all', form.method === m.v ? 'border-cyan-500 bg-cyan-50 text-cyan-700' : 'border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50']">
                    {{ m.l }}
                  </button>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  {{ form.method === 'mpesa' ? 'M-Pesa Code' : form.method === 'bank_transfer' ? 'Bank Reference' : 'Reference (optional)' }}
                </label>
                <input v-model="form.reference" type="text"
                  :placeholder="form.method === 'mpesa' ? 'e.g. QH1234WXYZ' : form.method === 'bank_transfer' ? 'e.g. TXN-0012345' : 'Optional reference'"
                  :style="form.method === 'mpesa' ? 'text-transform:uppercase' : ''"
                  class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"/>
              </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl flex justify-between items-center gap-3">
              <p class="text-sm font-bold text-gray-900">KES <span style="color:#00BCD4;">{{ Number(form.amount || 0).toLocaleString() }}</span></p>
              <div class="flex gap-2">
                <button @click="closeModal" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-100 transition-colors">Cancel</button>
                <button @click="submitPayment" :disabled="saving"
                  class="px-5 py-2 rounded-lg text-sm font-semibold text-white flex items-center gap-2 disabled:opacity-50 transition-all hover:-translate-y-0.5"
                  style="background:#1F2937;">
                  <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                  {{ saving ? 'Recording…' : 'Record Payment' }}
                </button>
              </div>
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
