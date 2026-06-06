<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api from '../api'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()

// ─── types ───────────────────────────────────────────────────────────────────
interface Kpi   { label: string; value: string | number; change: string; up: boolean; color: string; icon: string }
interface Bar   { label: string; revenue: number; expenses: number }
interface Order { id: number; order_number: string; client: string; email: string; service: string; total: string; status: string; payment_status: string; created_at: string }
interface Act   { text: string; time: string; color: string }
interface Prod  { id: number; name: string; price: number; unit: string; slug: string }
interface Client { id: number; name: string; email: string; phone: string }

// ─── dashboard state ─────────────────────────────────────────────────────────
const loading       = ref(true)
const kpis          = ref<Kpi[]>([])
const chartData     = ref<Bar[]>([])
const recentOrders  = ref<Order[]>([])
const recentActivity = ref<Act[]>([])

// ─── status helpers ───────────────────────────────────────────────────────────
const statusStyle: Record<string, string> = {
  pending:          'bg-orange-100 text-orange-700 border-orange-200',
  processing:       'bg-blue-100 text-blue-700 border-blue-200',
  completed:        'bg-green-100 text-green-700 border-green-200',
  cancelled:        'bg-red-100 text-red-700 border-red-200',
  awaiting_mpesa:   'bg-yellow-100 text-yellow-700 border-yellow-200',
  paid:             'bg-green-100 text-green-700 border-green-200',
  failed:           'bg-red-100 text-red-700 border-red-200',
  refunded:         'bg-gray-100 text-gray-700 border-gray-200',
}
function statusClass(s: string) { return statusStyle[s] ?? 'bg-gray-100 text-gray-700 border-gray-200' }
function statusLabel(s: string) { return s.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) }

// ─── date ────────────────────────────────────────────────────────────────────
const todayFormatted = new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })

// ─── chart computed ───────────────────────────────────────────────────────────
const maxVal = computed(() => Math.max(...chartData.value.flatMap(b => [b.revenue, b.expenses]), 1))
const chartBars = computed(() => {
  const W = 600, H = 160, n = chartData.value.length, groupW = W / n
  const barW = Math.max(12, (groupW - 24) / 2)
  return chartData.value.map((b, i) => {
    const cx = i * groupW + (groupW - barW * 2 - 4) / 2
    return {
      x: cx, w: barW,
      revenueH: Math.max(4, (b.revenue / maxVal.value) * H),
      expenseH: Math.max(4, (b.expenses / maxVal.value) * H),
      label: b.label,
    }
  })
})

// ─── donut ────────────────────────────────────────────────────────────────────
const donut = [
  { label: 'Graphic Design', pct: 35, color: '#06b6d4' },
  { label: 'Printing',       pct: 28, color: '#3b82f6' },
  { label: 'Signage',        pct: 22, color: '#8b5cf6' },
  { label: 'Promotional',    pct: 15, color: '#ef4444' },
]
function donutSegments() {
  const R = 60, cx = 80, cy = 80, strokeW = 28
  let offset = 0
  const circ = 2 * Math.PI * R
  return donut.map(d => {
    const len = (d.pct / 100) * circ
    const seg = { stroke: d.color, dasharray: `${len} ${circ - len}`, dashoffset: -offset }
    offset += len
    return seg
  })
}

// ─── load dashboard ───────────────────────────────────────────────────────────
async function loadDashboard() {
  loading.value = true
  try {
    const { data } = await api.get('/admin/dashboard')
    kpis.value          = (data.kpis ?? []).map((k: any) => ({
      ...k,
      changePositive: k.up,
      iconBg:    k.color === 'green'  ? 'bg-green-50'  : k.color === 'blue' ? 'bg-blue-50'  : k.color === 'cyan' ? 'bg-cyan-50' : 'bg-purple-50',
      iconColor: k.color === 'green'  ? 'text-green-600' : k.color === 'blue' ? 'text-blue-600' : k.color === 'cyan' ? 'text-cyan-600' : 'text-purple-600',
    }))
    chartData.value      = data.chart ?? []
    recentOrders.value   = data.recent_orders ?? []
    recentActivity.value = data.recent_activity ?? []
  } finally {
    loading.value = false
  }
}

// ─── New Order Modal ──────────────────────────────────────────────────────────
const showModal    = ref(false)
const savingOrder  = ref(false)
const products     = ref<Prod[]>([])
const clients      = ref<Client[]>([])
const orderError   = ref('')
const orderSuccess = ref(false)

const form = ref({
  client_name:     '',
  client_email:    '',
  client_phone:    '',
  delivery_method: 'delivery',
  delivery_address: '',
  payment_method:  'mpesa',
  payment_status:  'pending',
  status:          'pending',
  service_fee:     500,
  notes:           '',
  items: [] as { product_id: number; name: string; unit_price: number; quantity: number; note: string }[],
})

const subtotal = computed(() => form.value.items.reduce((s, i) => s + i.unit_price * i.quantity, 0))
const grandTotal = computed(() => subtotal.value + (Number(form.value.service_fee) || 0))

function addItem() {
  form.value.items.push({ product_id: 0, name: '', unit_price: 0, quantity: 1, note: '' })
}
function removeItem(idx: number) { form.value.items.splice(idx, 1) }
function onProductSelect(idx: number) {
  const p = products.value.find(p => p.id === form.value.items[idx].product_id)
  if (p) { form.value.items[idx].name = p.name; form.value.items[idx].unit_price = p.price }
}
function fillClient(c: Client) {
  form.value.client_name  = c.name
  form.value.client_email = c.email
  form.value.client_phone = c.phone
}

async function openModal() {
  showModal.value  = true
  orderError.value = ''
  orderSuccess.value = false
  form.value = { client_name:'', client_email:'', client_phone:'', delivery_method:'delivery', delivery_address:'', payment_method:'mpesa', payment_status:'pending', status:'pending', service_fee:500, notes:'', items:[] }
  addItem()
  if (!products.value.length) {
    const [pr, cl] = await Promise.all([api.get('/admin/orders/products'), api.get('/admin/orders/clients')])
    products.value = pr.data
    clients.value  = cl.data
  }
}

async function submitOrder() {
  if (!form.value.client_name) { orderError.value = 'Client name is required.'; return }
  if (!form.value.items.length || form.value.items.some(i => !i.product_id)) { orderError.value = 'Select a product for each line item.'; return }
  savingOrder.value = true; orderError.value = ''
  try {
    await api.post('/admin/orders/create', form.value)
    orderSuccess.value = true
    await loadDashboard()
    setTimeout(() => { showModal.value = false; orderSuccess.value = false }, 1500)
  } catch (e: any) {
    orderError.value = e.response?.data?.message ?? Object.values(e.response?.data?.errors ?? {})?.[0]?.[0] ?? 'Failed to create order.'
  } finally {
    savingOrder.value = false
  }
}

onMounted(loadDashboard)
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Welcome back, Admin 👋</h1>
        <p class="text-sm text-gray-500 mt-1">{{ todayFormatted }}</p>
      </div>
      <div class="flex gap-3">
        <button class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Export
        </button>
        <button @click="openModal" class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white shadow-sm transition-all hover:-translate-y-0.5" style="background:#00BCD4;">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          New Order
        </button>
      </div>
    </div>

    <!-- No permissions state -->
    <div v-if="!auth.hasAnyPermission && auth.user.role !== 'super_admin'" class="flex flex-col items-center justify-center py-24 text-center">
      <div class="w-16 h-16 rounded-full bg-orange-50 flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
      </div>
      <h2 class="text-xl font-bold text-gray-900 mb-2">No Permissions Granted</h2>
      <p class="text-sm text-gray-500 max-w-sm">Your account has not been assigned any permissions yet. Please contact your system administrator to get access.</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center items-center py-24">
      <div class="w-10 h-10 border-4 border-t-transparent rounded-full animate-spin" style="border-color:#00BCD4;border-top-color:transparent;"></div>
    </div>

    <template v-else>
      <!-- KPI Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div v-for="kpi in kpis" :key="kpi.label" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
          <div class="flex items-center justify-between mb-3">
            <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', kpi.iconBg]">
              <svg class="w-5 h-5" :class="kpi.iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="kpi.icon"/>
              </svg>
            </div>
            <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full flex items-center gap-0.5', kpi.changePositive ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600']">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="kpi.changePositive ? 'M5 10l7-7m0 0l7 7m-7-7v18' : 'M19 14l-7 7m0 0l-7-7m7 7V3'"/>
              </svg>
              {{ kpi.change }}
            </span>
          </div>
          <div class="text-2xl font-bold text-gray-900 mt-1">{{ kpi.value }}</div>
          <div class="text-sm text-gray-500 mt-1">{{ kpi.label }}</div>
        </div>
      </div>

      <!-- Charts -->
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <!-- Bar chart -->
        <div class="xl:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <div class="flex items-center justify-between mb-6">
            <div>
              <h2 class="text-base font-semibold text-gray-900">Revenue vs Expenses</h2>
              <p class="text-xs text-gray-500 mt-0.5">In thousands KES · Last 6 months</p>
            </div>
            <div class="flex items-center gap-4 text-xs text-gray-500">
              <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm inline-block" style="background:#06b6d4;"></span>Revenue</span>
              <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-gray-200 inline-block"></span>Expenses</span>
            </div>
          </div>
          <div v-if="chartData.length === 0 || maxVal <= 1" class="flex items-center justify-center h-48 text-gray-400 text-sm">No chart data yet</div>
          <div v-else>
            <svg viewBox="0 0 600 170" class="w-full h-44" preserveAspectRatio="none">
              <line v-for="y in [0,40,80,120,160]" :key="y" x1="0" :y1="y" x2="600" :y2="y" stroke="#f3f4f6" stroke-width="1"/>
              <g v-for="(bar, i) in chartBars" :key="i">
                <rect :x="bar.x"           :y="160 - bar.revenueH" :width="bar.w" :height="bar.revenueH" fill="#06b6d4" rx="3"/>
                <rect :x="bar.x + bar.w + 4" :y="160 - bar.expenseH" :width="bar.w" :height="bar.expenseH" fill="#e5e7eb" rx="3"/>
              </g>
            </svg>
            <div class="flex justify-around mt-2">
              <span v-for="bar in chartBars" :key="bar.label" class="text-xs text-gray-400">{{ bar.label }}</span>
            </div>
          </div>
        </div>

        <!-- Donut -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <h2 class="text-base font-semibold text-gray-900 mb-1">Service Distribution</h2>
          <p class="text-xs text-gray-500 mb-5">By order volume</p>
          <div class="flex justify-center mb-5">
            <svg viewBox="0 0 160 160" class="w-36 h-36">
              <circle cx="80" cy="80" r="60" fill="none" stroke="#f3f4f6" stroke-width="28"/>
              <circle v-for="(seg, i) in donutSegments()" :key="i" cx="80" cy="80" r="60" fill="none"
                :stroke="seg.stroke" stroke-width="28"
                :stroke-dasharray="seg.dasharray"
                :stroke-dashoffset="seg.dashoffset"
                transform="rotate(-90 80 80)"/>
              <text x="80" y="76" text-anchor="middle" font-size="18" font-weight="bold" fill="#1F2937">100%</text>
              <text x="80" y="94" text-anchor="middle" font-size="9" fill="#6B7280">TOTAL</text>
            </svg>
          </div>
          <div class="space-y-2">
            <div v-for="d in donut" :key="d.label" class="flex items-center justify-between text-sm">
              <span class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-sm inline-block" :style="{ background: d.color }"></span>
                <span class="text-gray-600">{{ d.label }}</span>
              </span>
              <span class="font-semibold text-gray-800">{{ d.pct }}%</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom row -->
      <div class="grid grid-cols-1 xl:grid-cols-5 gap-4">
        <!-- Recent Activity -->
        <div class="xl:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <h2 class="text-base font-semibold text-gray-900 mb-4">Recent Activity</h2>
          <div v-if="!recentActivity.length" class="text-sm text-gray-400 text-center py-6">No activity yet</div>
          <ul v-else class="space-y-4">
            <li v-for="(a, i) in recentActivity" :key="i" class="flex items-start gap-3">
              <span class="mt-1.5 w-2.5 h-2.5 rounded-full shrink-0" :class="a.color"></span>
              <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-700 leading-snug">{{ a.text }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ a.time }}</p>
              </div>
            </li>
          </ul>
        </div>

        <!-- Recent Orders -->
        <div class="xl:col-span-3 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="px-6 py-4 flex items-center justify-between border-b border-gray-100">
            <h2 class="text-base font-semibold text-gray-900">Recent Orders</h2>
            <button class="text-sm font-semibold hover:underline" style="color:#00BCD4;">View all</button>
          </div>
          <div v-if="!recentOrders.length" class="text-sm text-gray-400 text-center py-10">No orders yet</div>
          <table v-else class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
              <tr>
                <th class="px-4 py-3 text-left font-medium">Order</th>
                <th class="px-4 py-3 text-left font-medium">Amount</th>
                <th class="px-4 py-3 text-left font-medium">Status</th>
                <th class="px-4 py-3 text-left font-medium">Date</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="o in recentOrders" :key="o.id" class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3">
                  <p class="font-mono text-xs font-semibold" style="color:#00BCD4;">{{ o.order_number }}</p>
                  <p class="text-gray-600 text-xs mt-0.5 truncate max-w-40">{{ o.client }}</p>
                </td>
                <td class="px-4 py-3 font-semibold text-gray-800">{{ o.total }}</td>
                <td class="px-4 py-3">
                  <span :class="['px-2 py-0.5 rounded-full text-xs font-medium border', statusClass(o.status)]">
                    {{ statusLabel(o.status) }}
                  </span>
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs">{{ o.created_at }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <!-- ─── New Order Modal ──────────────────────────────────────────── -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="showModal = false">
          <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[92vh] flex flex-col">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0">
              <div>
                <h3 class="text-lg font-bold text-gray-900">New Manual Order</h3>
                <p class="text-xs text-gray-500 mt-0.5">Create an order on behalf of a client</p>
              </div>
              <button @click="showModal = false" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>

            <!-- Modal Body -->
            <div class="overflow-y-auto flex-1 p-6 space-y-5">
              <!-- Success -->
              <div v-if="orderSuccess" class="rounded-xl p-4 text-sm flex items-center gap-2 bg-green-50 text-green-700 border border-green-200">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Order created successfully!
              </div>
              <!-- Error -->
              <div v-if="orderError" class="rounded-xl p-4 text-sm bg-red-50 text-red-600 border border-red-200">{{ orderError }}</div>

              <!-- Existing Clients Quick-fill -->
              <div v-if="clients.length">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Quick-fill from existing client</label>
                <div class="flex flex-wrap gap-2">
                  <button v-for="c in clients.slice(0, 6)" :key="c.id" type="button"
                    @click="fillClient(c)"
                    class="px-3 py-1.5 bg-gray-100 hover:bg-cyan-50 hover:text-cyan-700 border border-gray-200 hover:border-cyan-300 rounded-lg text-xs font-medium transition-colors">
                    {{ c.name }}
                  </button>
                </div>
              </div>

              <!-- Client Info -->
              <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Client Information</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                    <input v-model="form.client_name" type="text" placeholder="Full name"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all">
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input v-model="form.client_email" type="email" placeholder="email@example.com"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all">
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input v-model="form.client_phone" type="text" placeholder="+254..."
                      class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all">
                  </div>
                </div>
              </div>

              <!-- Line Items -->
              <div>
                <div class="flex items-center justify-between mb-3">
                  <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Order Items</p>
                  <button type="button" @click="addItem" class="flex items-center gap-1 text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors" style="color:#00BCD4;background:rgba(0,188,212,0.08);">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Item
                  </button>
                </div>
                <div class="rounded-xl border border-gray-200 overflow-hidden">
                  <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                      <tr>
                        <th class="px-3 py-2.5 text-left font-medium">Product</th>
                        <th class="px-3 py-2.5 text-left font-medium w-20">Qty</th>
                        <th class="px-3 py-2.5 text-left font-medium w-28">Unit Price</th>
                        <th class="px-3 py-2.5 text-right font-medium w-24">Total</th>
                        <th class="w-8"></th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                      <tr v-for="(item, idx) in form.items" :key="idx">
                        <td class="px-3 py-2">
                          <select v-model="item.product_id" @change="onProductSelect(idx)"
                            class="w-full border border-gray-300 rounded-lg px-2 py-2 text-xs outline-none focus:border-cyan-500 transition-all">
                            <option value="0" disabled>Select product…</option>
                            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                          </select>
                        </td>
                        <td class="px-3 py-2">
                          <input v-model.number="item.quantity" type="number" min="1"
                            class="w-full border border-gray-300 rounded-lg px-2 py-2 text-xs text-center outline-none focus:border-cyan-500 transition-all">
                        </td>
                        <td class="px-3 py-2">
                          <input v-model.number="item.unit_price" type="number" min="0" step="100"
                            class="w-full border border-gray-300 rounded-lg px-2 py-2 text-xs outline-none focus:border-cyan-500 transition-all">
                        </td>
                        <td class="px-3 py-2 text-right font-semibold text-gray-800 text-xs">
                          KES {{ (item.unit_price * item.quantity).toLocaleString() }}
                        </td>
                        <td class="px-2 py-2">
                          <button type="button" @click="removeItem(idx)" v-if="form.items.length > 1"
                            class="p-1 text-gray-400 hover:text-red-500 rounded transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <!-- Totals -->
                <div class="mt-3 flex flex-col items-end gap-1 text-sm">
                  <div class="flex gap-12 text-gray-600">
                    <span>Subtotal</span>
                    <span class="font-medium">KES {{ subtotal.toLocaleString() }}</span>
                  </div>
                  <div class="flex gap-8 text-gray-600 items-center">
                    <span>Service Fee</span>
                    <input v-model.number="form.service_fee" type="number" min="0" step="100"
                      class="w-24 border border-gray-200 rounded-lg px-2 py-1 text-xs text-right outline-none focus:border-cyan-500 transition-all">
                  </div>
                  <div class="flex gap-8 font-bold text-gray-900 border-t border-gray-200 pt-1 mt-1">
                    <span>Total</span>
                    <span>KES {{ grandTotal.toLocaleString() }}</span>
                  </div>
                </div>
              </div>

              <!-- Delivery + Payment -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Delivery</p>
                  <div class="space-y-3">
                    <div class="flex rounded-lg border border-gray-200 overflow-hidden">
                      <button type="button"
                        :class="['flex-1 py-2 text-xs font-medium transition-colors', form.delivery_method==='delivery' ? 'text-white' : 'text-gray-500 hover:bg-gray-50']"
                        :style="form.delivery_method==='delivery' ? 'background:#00BCD4' : ''"
                        @click="form.delivery_method='delivery'">Delivery</button>
                      <button type="button"
                        :class="['flex-1 py-2 text-xs font-medium border-l border-gray-200 transition-colors', form.delivery_method==='pickup' ? 'text-white' : 'text-gray-500 hover:bg-gray-50']"
                        :style="form.delivery_method==='pickup' ? 'background:#00BCD4' : ''"
                        @click="form.delivery_method='pickup'">Pickup</button>
                    </div>
                    <input v-if="form.delivery_method==='delivery'" v-model="form.delivery_address" type="text" placeholder="Delivery address"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all">
                  </div>
                </div>
                <div>
                  <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Payment</p>
                  <div class="space-y-3">
                    <select v-model="form.payment_method"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all">
                      <option value="mpesa">M-Pesa</option>
                      <option value="cash">Cash</option>
                      <option value="bank_transfer">Bank Transfer</option>
                      <option value="card">Card</option>
                    </select>
                    <div class="flex gap-2">
                      <div class="flex-1">
                        <label class="block text-xs text-gray-500 mb-1">Payment Status</label>
                        <select v-model="form.payment_status"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-cyan-500 transition-all">
                          <option value="pending">Pending</option>
                          <option value="paid">Paid</option>
                          <option value="failed">Failed</option>
                        </select>
                      </div>
                      <div class="flex-1">
                        <label class="block text-xs text-gray-500 mb-1">Order Status</label>
                        <select v-model="form.status"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-cyan-500 transition-all">
                          <option value="pending">Pending</option>
                          <option value="processing">Processing</option>
                          <option value="completed">Completed</option>
                          <option value="cancelled">Cancelled</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Notes -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea v-model="form.notes" rows="2" placeholder="Any special instructions…"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all resize-none"></textarea>
              </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl flex items-center justify-between gap-3 shrink-0">
              <p class="text-sm font-bold text-gray-900">Total: <span style="color:#00BCD4;">KES {{ grandTotal.toLocaleString() }}</span></p>
              <div class="flex gap-3">
                <button type="button" @click="showModal = false"
                  class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-100 transition-colors">
                  Cancel
                </button>
                <button type="button" @click="submitOrder" :disabled="savingOrder"
                  class="px-6 py-2 rounded-lg text-sm font-semibold text-white flex items-center gap-2 transition-all hover:-translate-y-0.5 disabled:opacity-60 disabled:cursor-not-allowed"
                  style="background:#1F2937;">
                  <svg v-if="savingOrder" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                  {{ savingOrder ? 'Creating…' : 'Create Order' }}
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
