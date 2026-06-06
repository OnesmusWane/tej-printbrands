<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api from '../api'

interface QItem { id: number; description: string; quantity: number; unit_price: number; total: number }
interface Quotation { id: number; quote_number: string; client: string; email: string; service?: string; total: number; items?: QItem[] }
interface Payment { id: number; payment_number: string; amount: number; method: string; reference?: string; paid_at?: string }
interface Invoice {
  id: number; invoice_number: string; client: string; email?: string; amount: number; paid_amount?: number
  status: string; due_date?: string; payment_method?: string; created_at: string
  quotation?: Quotation; payments?: Payment[]
}

const loading      = ref(true)
const saving       = ref(false)
const showCreate   = ref(false)
const showView     = ref(false)
const viewLoading  = ref(false)
const invoices     = ref<Invoice[]>([])
const quotations   = ref<Quotation[]>([])
const viewInv      = ref<Invoice | null>(null)
const search       = ref('')
const statusFilter = ref('')
const createError   = ref('')
const updatingInvId = ref<number | null>(null)
const showPayModal  = ref(false)
const paySaving     = ref(false)
const payError      = ref('')
const payForm = ref({
  invoice_id: 0 as number,
  client: '', amount: 0, method: 'cash' as string, reference: '', date: new Date().toISOString().slice(0, 10),
})

const form = ref({
  quotation_id: 0,
  client: '', email: '', amount: 0, due_date: '', payment_method: '', notes: '',
})

// ─── computed ────────────────────────────────────────────────────────────────
const filtered = computed(() => {
  let list = invoices.value
  if (statusFilter.value) list = list.filter(i => i.status === statusFilter.value)
  if (search.value.trim()) {
    const s = search.value.toLowerCase()
    list = list.filter(i => i.client.toLowerCase().includes(s) || i.invoice_number.toLowerCase().includes(s))
  }
  return list
})

function countStatus(s: string) { return invoices.value.filter(i => i.status === s).length }

// ─── open create modal ────────────────────────────────────────────────────────
function openCreate() {
  form.value = { quotation_id: 0, client: '', email: '', amount: 0, due_date: '', payment_method: '', notes: '' }
  createError.value = ''
  showCreate.value  = true
}

function onQuotationSelect() {
  if (!form.value.quotation_id) return
  const q = quotations.value.find(q => q.id === form.value.quotation_id)
  if (q) { form.value.client = q.client; form.value.email = q.email; form.value.amount = q.total }
}

async function submitCreate() {
  if (!form.value.client) { createError.value = 'Client name is required.'; return }
  if (!form.value.amount) { createError.value = 'Amount is required.'; return }
  saving.value = true; createError.value = ''
  try {
    const payload: any = { ...form.value }
    if (!payload.quotation_id) delete payload.quotation_id
    const { data } = await api.post('/invoices', payload)
    invoices.value.unshift(data.data ?? data)
    showCreate.value = false
  } catch (e: any) {
    createError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {})?.[0]?.[0] ?? 'Failed.'
  } finally { saving.value = false }
}

// ─── view / print ─────────────────────────────────────────────────────────────
async function openView(inv: Invoice) {
  showView.value = true; viewLoading.value = true; viewInv.value = inv
  try {
    const { data } = await api.get(`/invoices/${inv.id}`)
    viewInv.value = data.data ?? data
  } catch (e) { console.error(e) }
  finally { viewLoading.value = false }
}

function printInvoice() {
  if (!viewInv.value) return
  const inv = viewInv.value
  const q = inv.quotation
  const items = q?.items?.map(i => `
    <tr>
      <td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;">${i.description}</td>
      <td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;text-align:center;">${i.quantity}</td>
      <td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;text-align:right;">KES ${Number(i.unit_price).toLocaleString()}</td>
      <td style="padding:8px 12px;border-bottom:1px solid #f0f0f0;text-align:right;">KES ${Number(i.total).toLocaleString()}</td>
    </tr>`).join('') ?? `<tr><td colspan="4" style="padding:16px;text-align:center;color:#9CA3AF;">${q?.service ?? 'Service'}</td></tr>`

  const pmts = (inv.payments ?? []).map(p => `
    <tr>
      <td style="padding:6px 8px;">${p.payment_number}</td>
      <td style="padding:6px 8px;text-transform:capitalize;">${p.method}</td>
      <td style="padding:6px 8px;">${p.reference ?? '-'}</td>
      <td style="padding:6px 8px;text-align:right;">KES ${Number(p.amount).toLocaleString()}</td>
      <td style="padding:6px 8px;">${p.paid_at ? new Date(p.paid_at).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}) : '-'}</td>
    </tr>`).join('')

  const balanceDue = Math.max(0, Number(inv.amount) - Number(inv.paid_amount ?? 0))
  const html = `<!DOCTYPE html><html><head><title>Invoice ${inv.invoice_number}</title>
  <style>body{font-family:Arial,sans-serif;color:#1F2937;margin:0;padding:32px;}
  table{width:100%;border-collapse:collapse;}th{background:#f9fafb;padding:10px 12px;text-align:left;font-size:12px;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;}
  .header{border-bottom:3px solid #1F2937;padding-bottom:20px;margin-bottom:24px;}
  .company{font-size:22px;font-weight:700;color:#00BCD4;}.badge-paid{color:#16a34a;background:#dcfce7;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;}
  .badge-unpaid{color:#ea580c;background:#fff7ed;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;}
  </style></head><body>
  <div class="header">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;">
      <div><div class="company">Tej Printbrands</div><div style="font-size:12px;color:#6B7280;margin-top:4px;">Professional Print & Branding Solutions</div></div>
      <div style="text-align:right;">
        <div style="font-size:28px;font-weight:700;">INVOICE</div>
        <div style="font-size:13px;color:#6B7280;"># ${inv.invoice_number}</div>
      </div>
    </div>
  </div>
  <div style="display:flex;justify-content:space-between;margin-bottom:24px;">
    <div style="background:#f9fafb;padding:16px;border-radius:8px;min-width:220px;">
      <div style="font-size:11px;color:#6B7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">Bill To</div>
      <div style="font-weight:600;">${inv.client}</div>
      <div style="color:#6B7280;">${inv.email ?? ''}</div>
    </div>
    <div style="text-align:right;font-size:13px;">
      <div><strong>Date:</strong> ${new Date(inv.created_at).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'})}</div>
      ${inv.due_date ? `<div style="margin-top:4px;"><strong>Due:</strong> ${new Date(inv.due_date).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'})}</div>` : ''}
      <div style="margin-top:8px;"><span class="${inv.status === 'paid' ? 'badge-paid' : 'badge-unpaid'}">${inv.status.toUpperCase()}</span></div>
    </div>
  </div>
  ${q?.items?.length ? `
  <table style="margin-bottom:20px;">
    <thead><tr><th>Description</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Unit Price</th><th style="text-align:right;">Total</th></tr></thead>
    <tbody>${items}</tbody>
  </table>` : ''}
  <div style="display:flex;justify-content:flex-end;margin-bottom:24px;">
    <table style="width:260px;border-collapse:collapse;">
      <tr><td style="padding:6px 12px;">Amount</td><td style="text-align:right;padding:6px 12px;">KES ${Number(inv.amount).toLocaleString()}</td></tr>
      <tr><td style="padding:6px 12px;">Paid</td><td style="text-align:right;padding:6px 12px;color:#16a34a;">KES ${Number(inv.paid_amount ?? 0).toLocaleString()}</td></tr>
      <tr style="font-weight:700;border-top:2px solid #1F2937;"><td style="padding-top:8px;padding-left:12px;">Balance Due</td><td style="text-align:right;padding-top:8px;padding-right:12px;color:#00BCD4;">KES ${balanceDue.toLocaleString()}</td></tr>
    </table>
  </div>
  ${pmts ? `
  <div style="margin-bottom:16px;">
    <div style="font-size:13px;font-weight:600;margin-bottom:8px;color:#374151;">Payment History</div>
    <table style="width:100%;font-size:12px;">
      <thead><tr><th>Ref</th><th>Method</th><th>Code</th><th style="text-align:right;">Amount</th><th>Date</th></tr></thead>
      <tbody>${pmts}</tbody>
    </table>
  </div>` : ''}
  <div style="font-size:11px;color:#9CA3AF;border-top:1px solid #f0f0f0;padding-top:12px;text-align:center;">
    Tej Printbrands · Thank you for your business
  </div>
  </body></html>`
  const win = window.open('', '_blank')
  if (win) { win.document.write(html); win.document.close(); win.print() }
}

// ─── invoice status update ────────────────────────────────────────────────────
async function updateInvoiceStatus(inv: Invoice, status: string) {
  updatingInvId.value = inv.id
  try {
    const { data } = await api.patch(`/invoices/${inv.id}`, { status })
    const updated = data.data ?? data
    const idx = invoices.value.findIndex(i => i.id === inv.id)
    if (idx !== -1) invoices.value[idx] = { ...invoices.value[idx], ...updated }
    if (viewInv.value?.id === inv.id) viewInv.value = { ...viewInv.value!, status }
  } catch (e) { console.error(e) }
  finally { updatingInvId.value = null }
}

// ─── pay modal ────────────────────────────────────────────────────────────────
function openPayModal(inv: Invoice) {
  payForm.value = {
    invoice_id: inv.id,
    client:     inv.client,
    amount:     Math.max(0, Number(inv.amount) - Number(inv.paid_amount ?? 0)),
    method:     'cash',
    reference:  '',
    date:       new Date().toISOString().slice(0, 10),
  }
  payError.value    = ''
  showPayModal.value = true
}

async function submitInvoicePayment() {
  if (!payForm.value.amount) { payError.value = 'Amount is required.'; return }
  paySaving.value = true; payError.value = ''
  try {
    const payload: any = {
      invoice_id: payForm.value.invoice_id,
      client:     payForm.value.client,
      amount:     payForm.value.amount,
      method:     payForm.value.method,
      reference:  payForm.value.reference || null,
      paid_at:    payForm.value.date,
    }
    await api.post('/payments', payload)
    // Update invoice paid_amount + status locally
    const idx = invoices.value.findIndex(i => i.id === payForm.value.invoice_id)
    if (idx !== -1) {
      const newPaid = Number(invoices.value[idx].paid_amount ?? 0) + Number(payForm.value.amount)
      invoices.value[idx].paid_amount = newPaid
      invoices.value[idx].status = newPaid >= Number(invoices.value[idx].amount) ? 'paid' : 'partial'
    }
    if (viewInv.value?.id === payForm.value.invoice_id) {
      const newPaid = Number(viewInv.value.paid_amount ?? 0) + Number(payForm.value.amount)
      viewInv.value = { ...viewInv.value!, paid_amount: newPaid, status: newPaid >= Number(viewInv.value.amount) ? 'paid' : 'partial' }
    }
    showPayModal.value = false
  } catch (e: any) {
    payError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {})?.[0]?.[0] ?? 'Payment failed.'
  } finally { paySaving.value = false }
}

// ─── helpers ──────────────────────────────────────────────────────────────────
function statusClass(s: string) {
  const m: Record<string, string> = { paid: 'bg-green-100 text-green-700 border-green-200', unpaid: 'bg-orange-100 text-orange-700 border-orange-200', overdue: 'bg-red-100 text-red-700 border-red-200', partial: 'bg-blue-100 text-blue-700 border-blue-200' }
  return m[s] ?? 'bg-gray-100 text-gray-700 border-gray-200'
}
function capitalize(s: string) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : '' }
function fmt(v: any) { return Number(v ?? 0).toLocaleString() }
function fmtDate(d?: string) { if (!d) return '-'; return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) }

onMounted(async () => {
  try {
    const [invRes, qRes] = await Promise.all([api.get('/invoices'), api.get('/quotations?status=approved&per_page=100')])
    invoices.value   = Array.isArray(invRes.data) ? invRes.data : (invRes.data.data ?? [])
    quotations.value = Array.isArray(qRes.data) ? qRes.data : (qRes.data.data ?? [])
  } catch (e) { console.error(e) }
  finally { loading.value = false }
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Invoices</h1>
        <p class="text-sm text-gray-500 mt-1">Manage and track client invoices</p>
      </div>
      <button @click="openCreate"
        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white shadow-sm self-start sm:self-auto"
        style="background:#00BCD4;">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Create Invoice
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="w-8 h-8 border-4 border-t-transparent rounded-full animate-spin" style="border-color:#00BCD4;border-top-color:transparent;"></div>
    </div>

    <template v-else>
      <!-- Stat Cards -->
      <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <div class="text-2xl font-bold text-gray-900">{{ invoices.length }}</div>
          <div class="text-sm text-gray-500 mt-1">Total Invoices</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <div class="text-2xl font-bold text-green-600">{{ countStatus('paid') }}</div>
          <div class="text-sm text-gray-500 mt-1">Paid</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <div class="text-2xl font-bold text-orange-600">{{ countStatus('unpaid') }}</div>
          <div class="text-sm text-gray-500 mt-1">Unpaid</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <div class="text-2xl font-bold text-red-600">{{ countStatus('overdue') }}</div>
          <div class="text-sm text-gray-500 mt-1">Overdue</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1 max-w-sm">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
          <input v-model="search" type="text" placeholder="Search invoices..."
            class="w-full border border-gray-300 rounded-xl pl-9 pr-3 py-2 outline-none focus:border-cyan-500 text-sm"/>
        </div>
        <select v-model="statusFilter" class="border border-gray-300 rounded-xl px-3 py-2 outline-none focus:border-cyan-500 text-sm bg-white">
          <option value="">All Statuses</option>
          <option value="paid">Paid</option>
          <option value="unpaid">Unpaid</option>
          <option value="partial">Partial</option>
          <option value="overdue">Overdue</option>
        </select>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div v-if="filtered.length === 0" class="text-sm text-gray-400 text-center py-12">No invoices found</div>
        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="inv in filtered" :key="inv.id" class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4">
                  <button @click="openView(inv)" class="text-sm font-mono font-semibold hover:underline" style="color:#00BCD4;">
                    {{ inv.invoice_number }}
                  </button>
                  <div v-if="inv.quotation" class="text-xs text-gray-400 mt-0.5">from {{ inv.quotation.quote_number }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm font-semibold text-gray-900">{{ inv.client }}</div>
                  <div class="text-xs text-gray-500">{{ inv.email }}</div>
                </td>
                <td class="px-6 py-4 text-sm font-medium text-gray-900">KES {{ fmt(inv.amount) }}</td>
                <td class="px-6 py-4 text-sm text-green-600 font-medium">KES {{ fmt(inv.paid_amount ?? 0) }}</td>
                <td class="px-6 py-4">
                  <select :value="inv.status"
                    @change="updateInvoiceStatus(inv, ($event.target as HTMLSelectElement).value)"
                    :disabled="updatingInvId === inv.id"
                    :class="['px-2 py-1 rounded-lg text-xs font-medium border cursor-pointer outline-none transition-all disabled:opacity-60', statusClass(inv.status)]">
                    <option value="unpaid">Unpaid</option>
                    <option value="partial">Partial</option>
                    <option value="paid">Paid</option>
                    <option value="overdue">Overdue</option>
                  </select>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ fmtDate(inv.due_date) }}</td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-1">
                    <button @click="openView(inv)" class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors" title="View">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                    <button @click="printInvoice(); viewInv = inv" class="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded transition-colors" title="Print">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    </button>
                    <button v-if="inv.status !== 'paid'" @click="openPayModal(inv)" class="p-1.5 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded transition-colors" title="Record Payment">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <!-- ─── Create Invoice Modal ──────────────────────────────────────────────── -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showCreate" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="showCreate = false">
          <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
              <h3 class="text-base font-bold text-gray-900">Create Invoice</h3>
              <button @click="showCreate = false" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
            <div class="p-6 space-y-4">
              <div v-if="createError" class="rounded-xl p-3 bg-red-50 text-red-600 border border-red-200 text-sm">{{ createError }}</div>

              <!-- From quotation -->
              <div v-if="quotations.length">
                <label class="block text-sm font-medium text-gray-700 mb-1">From Quotation (optional)</label>
                <select v-model.number="form.quotation_id" @change="onQuotationSelect"
                  class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 bg-white transition-all">
                  <option :value="0">None — enter client manually</option>
                  <option v-for="q in quotations" :key="q.id" :value="q.id">{{ q.quote_number }} · {{ q.client }} · KES {{ fmt(q.total) }}</option>
                </select>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2 sm:col-span-1">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Client Name <span class="text-red-500">*</span></label>
                  <input v-model="form.client" type="text" placeholder="Full name"
                    class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"/>
                </div>
                <div class="col-span-2 sm:col-span-1">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Client Email</label>
                  <input v-model="form.email" type="email" placeholder="email@example.com"
                    class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"/>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Amount (KES) <span class="text-red-500">*</span></label>
                  <input v-model.number="form.amount" type="number" min="1" placeholder="0"
                    class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"/>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                  <input v-model="form.due_date" type="date"
                    class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"/>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea v-model="form.notes" rows="2" placeholder="Additional notes..."
                  class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all resize-none"></textarea>
              </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
              <button @click="showCreate = false" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-100 transition-colors">Cancel</button>
              <button @click="submitCreate" :disabled="saving"
                class="px-5 py-2 rounded-lg text-sm font-semibold text-white flex items-center gap-2 disabled:opacity-50 transition-all hover:-translate-y-0.5"
                style="background:#1F2937;">
                <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                Create Invoice
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ─── View / Print Modal ─────────────────────────────────────────────── -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showView" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="showView = false">
          <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl max-h-[92vh] flex flex-col">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0">
              <div>
                <h3 class="text-base font-bold font-mono" style="color:#00BCD4;">{{ viewInv?.invoice_number }}</h3>
                <p class="text-xs text-gray-500 mt-0.5">{{ viewInv?.client }}</p>
              </div>
              <div class="flex items-center gap-2">
                <button @click="printInvoice"
                  class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white"
                  style="background:#00BCD4;">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                  Print
                </button>
                <button @click="showView = false" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
              </div>
            </div>
            <div class="p-6 overflow-y-auto flex-1">
              <div v-if="viewLoading" class="flex justify-center py-10">
                <div class="w-7 h-7 border-4 border-t-transparent rounded-full animate-spin" style="border-color:#00BCD4;border-top-color:transparent;"></div>
              </div>
              <template v-else-if="viewInv">
                <!-- Status + actions -->
                <div class="flex items-center justify-between mb-5">
                  <select :value="viewInv.status"
                    @change="updateInvoiceStatus(viewInv, ($event.target as HTMLSelectElement).value)"
                    :disabled="updatingInvId === viewInv.id"
                    :class="['px-2.5 py-1 rounded-lg text-xs font-semibold border cursor-pointer outline-none disabled:opacity-60', statusClass(viewInv.status)]">
                    <option value="unpaid">Unpaid</option>
                    <option value="partial">Partial</option>
                    <option value="paid">Paid</option>
                    <option value="overdue">Overdue</option>
                  </select>
                  <button v-if="viewInv.status !== 'paid'" @click="openPayModal(viewInv)"
                    class="text-xs px-3 py-1.5 rounded-lg font-semibold text-white bg-emerald-600 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Record Payment
                  </button>
                </div>
                <!-- Client + dates -->
                <div class="bg-gray-50 rounded-xl p-4 mb-4 text-sm grid grid-cols-2 gap-3">
                  <div>
                    <p class="text-xs text-gray-400 mb-0.5">Client</p>
                    <p class="font-semibold text-gray-900">{{ viewInv.client }}</p>
                    <p class="text-gray-500 text-xs">{{ viewInv.email }}</p>
                  </div>
                  <div class="text-right">
                    <p class="text-xs text-gray-400 mb-0.5">Issued</p>
                    <p class="text-gray-700">{{ fmtDate(viewInv.created_at) }}</p>
                    <p v-if="viewInv.due_date" class="text-xs text-gray-500 mt-1">Due: {{ fmtDate(viewInv.due_date) }}</p>
                  </div>
                </div>
                <!-- Quotation items if linked -->
                <div v-if="viewInv.quotation?.items?.length" class="mb-4">
                  <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Line Items</p>
                  <table class="w-full text-sm border border-gray-100 rounded-xl overflow-hidden">
                    <thead class="bg-gray-50">
                      <tr>
                        <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                        <th class="px-4 py-2.5 text-center text-xs font-medium text-gray-500 uppercase">Qty</th>
                        <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                      <tr v-for="item in viewInv.quotation.items" :key="item.id">
                        <td class="px-4 py-2.5 text-gray-700">{{ item.description }}</td>
                        <td class="px-4 py-2.5 text-center text-gray-700">{{ item.quantity }}</td>
                        <td class="px-4 py-2.5 text-right font-medium text-gray-900">KES {{ fmt(item.total) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <!-- Totals -->
                <div class="flex flex-col items-end gap-1.5 text-sm mb-4">
                  <div class="flex gap-12 text-gray-600"><span>Invoice Amount</span><span>KES {{ fmt(viewInv.amount) }}</span></div>
                  <div class="flex gap-12 text-green-600"><span>Amount Paid</span><span>KES {{ fmt(viewInv.paid_amount ?? 0) }}</span></div>
                  <div class="flex gap-10 font-bold text-gray-900 border-t border-gray-200 pt-1.5"><span>Balance Due</span><span style="color:#00BCD4;">KES {{ fmt(Math.max(0, viewInv.amount - (viewInv.paid_amount ?? 0))) }}</span></div>
                </div>
                <!-- Payment history -->
                <div v-if="viewInv.payments?.length">
                  <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Payment History</p>
                  <div class="space-y-2">
                    <div v-for="p in viewInv.payments" :key="p.id"
                      class="flex items-center justify-between text-sm bg-gray-50 rounded-lg px-4 py-2.5">
                      <div>
                        <span class="font-mono text-xs text-gray-500">{{ p.payment_number }}</span>
                        <span class="ml-2 text-gray-700 capitalize">{{ p.method }}</span>
                        <span v-if="p.reference" class="ml-2 text-xs text-gray-400">{{ p.reference }}</span>
                      </div>
                      <span class="font-semibold text-green-600">KES {{ fmt(p.amount) }}</span>
                    </div>
                  </div>
                </div>
              </template>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
    <!-- ─── Record Payment Modal ─────────────────────────────────────────────── -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showPayModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="showPayModal = false">
          <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
              <div>
                <h3 class="text-base font-bold text-gray-900">Record Payment</h3>
                <p class="text-xs text-gray-500 mt-0.5">Balance due:
                  <span class="font-semibold" style="color:#00BCD4;">KES {{ Number(payForm.amount || 0).toLocaleString() }}</span>
                </p>
              </div>
              <button @click="showPayModal = false" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
            <div class="p-6 space-y-4">
              <div v-if="payError" class="rounded-xl p-3 bg-red-50 text-red-600 border border-red-200 text-sm">{{ payError }}</div>
              <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Client</label>
                  <input v-model="payForm.client" type="text" placeholder="Full name"
                    class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"/>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Amount (KES) <span class="text-red-500">*</span></label>
                  <input v-model.number="payForm.amount" type="number" min="1" placeholder="0"
                    class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"/>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                  <input v-model="payForm.date" type="date"
                    class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"/>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                <div class="grid grid-cols-3 gap-2">
                  <button v-for="m in [{ v:'cash', l:'Cash' }, { v:'mpesa', l:'M-Pesa' }, { v:'bank_transfer', l:'Bank Transfer' }]" :key="m.v"
                    type="button" @click="payForm.method = m.v"
                    :class="['py-2 rounded-xl border text-xs font-semibold transition-all', payForm.method === m.v ? 'border-cyan-500 bg-cyan-50 text-cyan-700' : 'border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50']">
                    {{ m.l }}
                  </button>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  {{ payForm.method === 'mpesa' ? 'M-Pesa Code' : payForm.method === 'bank_transfer' ? 'Bank Reference' : 'Reference (optional)' }}
                </label>
                <input v-model="payForm.reference" type="text"
                  :placeholder="payForm.method === 'mpesa' ? 'e.g. QH1234WXYZ' : payForm.method === 'bank_transfer' ? 'e.g. TXN-0012345' : 'Optional reference'"
                  :style="payForm.method === 'mpesa' ? 'text-transform:uppercase' : ''"
                  class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"/>
              </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
              <button @click="showPayModal = false" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-100 transition-colors">Cancel</button>
              <button @click="submitInvoicePayment" :disabled="paySaving"
                class="px-5 py-2 rounded-lg text-sm font-semibold text-white flex items-center gap-2 disabled:opacity-50 transition-all hover:-translate-y-0.5"
                style="background:#1F2937;">
                <svg v-if="paySaving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                {{ paySaving ? 'Processing…' : 'Record Payment' }}
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
