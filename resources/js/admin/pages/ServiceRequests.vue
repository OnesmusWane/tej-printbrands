<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api from '../api'

interface Booking { id: number; booking_number: string; delivery_date?: string; status: string }
interface ServiceRequest {
  id: number; request_number?: string; client: string; email: string; service: string
  budget?: string; timeline?: string; priority: string; status: string; description?: string
  created_at: string; booking?: Booking | null
}

// ─── state ────────────────────────────────────────────────────────────────────
const loading      = ref(true)
const requests     = ref<ServiceRequest[]>([])
const search       = ref('')
const activeFilter = ref('all')

// ─── detail drawer ────────────────────────────────────────────────────────────
const showDrawer   = ref(false)
const activeReq    = ref<ServiceRequest | null>(null)

function openDrawer(r: ServiceRequest) { activeReq.value = r; showDrawer.value = true }
function closeDrawer() { showDrawer.value = false; activeReq.value = null }

// ─── status update ────────────────────────────────────────────────────────────
const updatingId = ref<number | null>(null)

const statuses = [
  { value: 'new',        label: 'New' },
  { value: 'in_review',  label: 'In Review' },
  { value: 'quoted',     label: 'Quoted' },
  { value: 'confirmed',  label: 'Confirmed' },
  { value: 'completed',  label: 'Completed' },
  { value: 'rejected',   label: 'Rejected' },
]

async function updateStatus(r: ServiceRequest, status: string) {
  updatingId.value = r.id
  try {
    const { data } = await api.patch(`/service-requests/${r.id}/status`, { status })
    const idx = requests.value.findIndex(x => x.id === r.id)
    if (idx !== -1) requests.value[idx] = data.data ?? data
    if (activeReq.value?.id === r.id) activeReq.value = data.data ?? data
  } catch (e) { console.error(e) }
  finally { updatingId.value = null }
}

// ─── convert to booking modal ─────────────────────────────────────────────────
const showConvert  = ref(false)
const converting   = ref(false)
const convertError = ref('')
const convertSuccess = ref(false)
const convertForm  = ref({
  delivery_date:  '',
  preferred_date: '',
  phone:          '',
  location:       '',
  price:          0,
  notes:          '',
})

function openConvert(r: ServiceRequest) {
  activeReq.value   = r
  convertError.value = ''
  convertSuccess.value = false
  convertForm.value = { delivery_date: '', preferred_date: '', phone: '', location: '', price: 0, notes: '' }
  showConvert.value = true
}

async function submitConvert() {
  if (!convertForm.value.delivery_date) { convertError.value = 'Expected delivery date is required.'; return }
  converting.value = true; convertError.value = ''
  try {
    const { data } = await api.post(`/service-requests/${activeReq.value!.id}/convert`, convertForm.value)
    const idx = requests.value.findIndex(x => x.id === activeReq.value!.id)
    if (idx !== -1) requests.value[idx] = data.service_request ?? requests.value[idx]
    if (activeReq.value) activeReq.value = data.service_request ?? activeReq.value
    convertSuccess.value = true
    setTimeout(() => { showConvert.value = false }, 1500)
  } catch (e: any) {
    convertError.value = e.response?.data?.message ?? 'Failed to create booking.'
  } finally { converting.value = false }
}

// ─── computed / helpers ───────────────────────────────────────────────────────
const statCards = computed(() => [
  { label: 'Total',      filter: 'all',       count: requests.value.length },
  { label: 'New',        filter: 'new',        count: requests.value.filter(r => r.status === 'new').length },
  { label: 'In Review',  filter: 'in_review',  count: requests.value.filter(r => r.status === 'in_review').length },
  { label: 'Quoted',     filter: 'quoted',     count: requests.value.filter(r => r.status === 'quoted').length },
  { label: 'Confirmed',  filter: 'confirmed',  count: requests.value.filter(r => r.status === 'confirmed').length },
  { label: 'Completed',  filter: 'completed',  count: requests.value.filter(r => r.status === 'completed').length },
])

const filtered = computed(() => {
  let list = requests.value
  if (activeFilter.value !== 'all') list = list.filter(r => r.status === activeFilter.value)
  if (search.value.trim()) {
    const q = search.value.toLowerCase()
    list = list.filter(r =>
      r.client.toLowerCase().includes(q) ||
      (r.email ?? '').toLowerCase().includes(q) ||
      (r.service ?? '').toLowerCase().includes(q) ||
      (r.request_number ?? '').toLowerCase().includes(q)
    )
  }
  return list
})

function statusLabel(s: string) {
  return ({ new: 'New', in_review: 'In Review', quoted: 'Quoted', confirmed: 'Confirmed', completed: 'Completed', rejected: 'Rejected' })[s] ?? s
}

function statusClass(s: string) {
  return ({
    new:       'bg-blue-100 text-blue-700 border-blue-200',
    in_review: 'bg-orange-100 text-orange-700 border-orange-200',
    quoted:    'bg-purple-100 text-purple-700 border-purple-200',
    confirmed: 'bg-cyan-100 text-cyan-700 border-cyan-200',
    completed: 'bg-green-100 text-green-700 border-green-200',
    rejected:  'bg-red-100 text-red-700 border-red-200',
  } as Record<string,string>)[s] ?? 'bg-gray-100 text-gray-700 border-gray-200'
}

function priorityClass(p?: string) {
  return ({ high: 'bg-red-500', medium: 'bg-orange-500', low: 'bg-gray-400' } as Record<string,string>)[p ?? 'low'] ?? 'bg-gray-400'
}

function fmtDate(d?: string) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

onMounted(async () => {
  try {
    const { data } = await api.get('/service-requests')
    requests.value = Array.isArray(data) ? data : (data.data ?? [])
  } catch (e) { console.error(e) }
  finally { loading.value = false }
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Service Requests</h1>
      <p class="text-sm text-gray-500 mt-1">Review incoming requests and convert confirmed ones to bookings</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="w-8 h-8 border-4 border-t-transparent rounded-full animate-spin" style="border-color:#00BCD4;border-top-color:transparent;"></div>
    </div>

    <template v-else>
      <!-- Stat Filter Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3">
        <button v-for="stat in statCards" :key="stat.filter" @click="activeFilter = stat.filter"
          :class="['bg-white rounded-xl shadow-sm border p-4 text-left transition-all', activeFilter === stat.filter ? 'border-cyan-500 ring-1 ring-cyan-500/20' : 'border-gray-100 hover:border-gray-200']">
          <div class="text-2xl font-bold text-gray-900">{{ stat.count }}</div>
          <div class="text-xs text-gray-500 mt-1">{{ stat.label }}</div>
        </button>
      </div>

      <!-- Search -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="relative max-w-sm">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
          <input v-model="search" type="text" placeholder="Search requests..."
            class="w-full border border-gray-300 rounded-xl pl-9 pr-3 py-2 outline-none focus:border-cyan-500 text-sm"/>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div v-if="filtered.length === 0" class="text-sm text-gray-400 text-center py-12">No service requests found</div>
        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-6"></th>
                <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request ID</th>
                <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Budget</th>
                <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking</th>
                <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <tr v-for="req in filtered" :key="req.id" class="hover:bg-gray-50 transition-colors">
                <!-- Priority dot -->
                <td class="px-5 py-4">
                  <span :class="['w-2.5 h-2.5 rounded-full inline-block', priorityClass(req.priority)]" :title="req.priority ?? 'medium'"></span>
                </td>
                <!-- Request ID (clickable → open drawer) -->
                <td class="px-5 py-4">
                  <button @click="openDrawer(req)" class="text-sm font-mono font-semibold hover:underline" style="color:#00BCD4;">
                    {{ req.request_number ?? `#SR-${req.id}` }}
                  </button>
                </td>
                <td class="px-5 py-4">
                  <div class="text-sm font-semibold text-gray-900">{{ req.client }}</div>
                  <div class="text-xs text-gray-500">{{ req.email }}</div>
                </td>
                <td class="px-5 py-4 text-sm text-gray-700">{{ req.service }}</td>
                <td class="px-5 py-4 text-sm text-gray-700">{{ req.budget ?? '-' }}</td>
                <td class="px-5 py-4">
                  <!-- Inline status dropdown -->
                  <div class="relative">
                    <select
                      :value="req.status"
                      :disabled="updatingId === req.id"
                      @change="updateStatus(req, ($event.target as HTMLSelectElement).value)"
                      :class="['pr-7 pl-2.5 py-1 rounded-full text-xs font-semibold border appearance-none cursor-pointer disabled:opacity-50', statusClass(req.status)]">
                      <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                  </div>
                </td>
                <!-- Booking link / status -->
                <td class="px-5 py-4">
                  <span v-if="req.booking" class="flex items-center gap-1 text-xs text-green-700 font-medium">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ req.booking.booking_number }}
                  </span>
                  <span v-else class="text-xs text-gray-400">—</span>
                </td>
                <td class="px-5 py-4 text-sm text-gray-500 whitespace-nowrap">{{ fmtDate(req.created_at) }}</td>
                <td class="px-5 py-4">
                  <div class="flex items-center gap-2">
                    <button @click="openDrawer(req)" class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors" title="View details">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                    <button
                      v-if="!req.booking && req.status !== 'rejected'"
                      @click="openConvert(req)"
                      class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-white transition-colors"
                      style="background:#1F2937;" title="Confirm & create booking">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                      Book
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <!-- ─── Detail Drawer ─────────────────────────────────────────────────────── -->
    <Teleport to="body">
      <Transition name="slide">
        <div v-if="showDrawer" class="fixed inset-0 z-50 flex" @click.self="closeDrawer">
          <div class="ml-auto w-full max-w-md bg-white shadow-2xl flex flex-col h-full overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0">
              <div>
                <h3 class="text-sm font-bold font-mono" style="color:#00BCD4;">{{ activeReq?.request_number ?? `#SR-${activeReq?.id}` }}</h3>
                <p class="text-xs text-gray-500 mt-0.5">{{ fmtDate(activeReq?.created_at) }}</p>
              </div>
              <button @click="closeDrawer" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>

            <div v-if="activeReq" class="p-6 space-y-5 flex-1">
              <!-- Status row -->
              <div class="flex items-center justify-between">
                <span :class="['px-3 py-1 rounded-full text-xs font-semibold border', statusClass(activeReq.status)]">{{ statusLabel(activeReq.status) }}</span>
                <div class="flex gap-1.5 flex-wrap justify-end">
                  <button v-for="s in statuses.filter(x => x.value !== activeReq!.status)" :key="s.value"
                    @click="updateStatus(activeReq!, s.value)"
                    :disabled="updatingId === activeReq!.id"
                    :class="['px-2.5 py-1 rounded-lg text-xs font-medium border transition-colors disabled:opacity-50', statusClass(s.value)]">
                    → {{ s.label }}
                  </button>
                </div>
              </div>

              <!-- Client info -->
              <div class="bg-gray-50 rounded-xl p-4 space-y-2">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Client</p>
                <p class="font-semibold text-gray-900">{{ activeReq.client }}</p>
                <a :href="`mailto:${activeReq.email}`" class="text-sm text-cyan-600 hover:underline">{{ activeReq.email }}</a>
              </div>

              <!-- Request details -->
              <div class="space-y-3">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Service</span>
                  <span class="font-semibold text-gray-800">{{ activeReq.service }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Budget</span>
                  <span class="font-semibold text-gray-800">{{ activeReq.budget ?? '-' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Timeline</span>
                  <span class="font-semibold text-gray-800">{{ activeReq.timeline ?? '-' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Priority</span>
                  <span class="flex items-center gap-1.5 font-semibold text-gray-800">
                    <span :class="['w-2 h-2 rounded-full inline-block', priorityClass(activeReq.priority)]"></span>
                    {{ activeReq.priority ?? 'Medium' }}
                  </span>
                </div>
              </div>

              <!-- Description -->
              <div v-if="activeReq.description" class="bg-gray-50 rounded-xl p-4">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Description</p>
                <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap">{{ activeReq.description }}</p>
              </div>

              <!-- Linked booking -->
              <div v-if="activeReq.booking" class="bg-green-50 border border-green-200 rounded-xl p-4">
                <p class="text-xs font-semibold text-green-700 uppercase tracking-wider mb-2">Booking Created</p>
                <p class="text-sm font-mono font-semibold text-green-800">{{ activeReq.booking.booking_number }}</p>
                <p v-if="activeReq.booking.delivery_date" class="text-xs text-green-600 mt-1">
                  Delivery: {{ fmtDate(activeReq.booking.delivery_date) }}
                </p>
                <span :class="['mt-2 inline-block px-2.5 py-0.5 rounded-full text-xs font-medium border', statusClass(activeReq.booking.status)]">
                  {{ statusLabel(activeReq.booking.status) }}
                </span>
              </div>

              <!-- Convert button (if no booking yet) -->
              <div v-if="!activeReq.booking && activeReq.status !== 'rejected'" class="pt-2">
                <button @click="openConvert(activeReq)"
                  class="w-full py-3 rounded-xl font-semibold text-white flex items-center justify-center gap-2 transition-all hover:-translate-y-0.5"
                  style="background:#1F2937;">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                  Confirm & Create Booking
                </button>
              </div>
            </div>
          </div>
          <!-- Backdrop -->
          <div class="fixed inset-0 -z-10 bg-black/40" @click="closeDrawer"></div>
        </div>
      </Transition>
    </Teleport>

    <!-- ─── Convert to Booking Modal ──────────────────────────────────────────── -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showConvert" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="showConvert = false">
          <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
              <div>
                <h3 class="text-base font-bold text-gray-900">Confirm & Create Booking</h3>
                <p class="text-xs text-gray-500 mt-0.5">{{ activeReq?.client }} · {{ activeReq?.service }}</p>
              </div>
              <button @click="showConvert = false" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>

            <div class="p-6 space-y-4">
              <!-- Success -->
              <div v-if="convertSuccess" class="rounded-xl p-4 bg-green-50 text-green-700 border border-green-200 text-sm flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Booking created successfully!
              </div>
              <!-- Error -->
              <div v-if="convertError" class="rounded-xl p-3 bg-red-50 text-red-600 border border-red-200 text-sm">{{ convertError }}</div>

              <!-- Delivery date (required) -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Expected Delivery Date <span class="text-red-500">*</span>
                </label>
                <input v-model="convertForm.delivery_date" type="date"
                  class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                <p class="text-xs text-gray-400 mt-1">Date by which the work will be delivered to the client</p>
              </div>

              <!-- Preferred start date -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Preferred Start Date</label>
                <input v-model="convertForm.preferred_date" type="date"
                  class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"/>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                  <input v-model="convertForm.phone" type="text" placeholder="+254..."
                    class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"/>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Agreed Price (KES)</label>
                  <input v-model.number="convertForm.price" type="number" min="0" placeholder="0"
                    class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"/>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Location / Venue</label>
                <input v-model="convertForm.location" type="text" placeholder="e.g. Our studio, client premises"
                  class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all"/>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Internal Notes</label>
                <textarea v-model="convertForm.notes" rows="2" placeholder="Any notes for the production team..."
                  class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-cyan-500 transition-all resize-none"></textarea>
              </div>

              <!-- Summary -->
              <div class="bg-cyan-50 border border-cyan-100 rounded-xl p-3 text-xs text-gray-600 space-y-1">
                <p><span class="font-semibold text-gray-700">Client:</span> {{ activeReq?.client }} ({{ activeReq?.email }})</p>
                <p><span class="font-semibold text-gray-700">Service:</span> {{ activeReq?.service }}</p>
                <p v-if="activeReq?.budget"><span class="font-semibold text-gray-700">Budget:</span> {{ activeReq?.budget }}</p>
              </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl flex justify-end gap-3">
              <button @click="showConvert = false" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-100 transition-colors">Cancel</button>
              <button @click="submitConvert" :disabled="converting || convertSuccess"
                class="px-5 py-2 rounded-lg text-sm font-semibold text-white flex items-center gap-2 disabled:opacity-50 transition-all hover:-translate-y-0.5"
                style="background:#00BCD4;">
                <svg v-if="converting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                {{ converting ? 'Creating…' : 'Confirm & Create Booking' }}
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
.slide-enter-active, .slide-leave-active { transition: transform 0.25s ease; }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }
</style>
