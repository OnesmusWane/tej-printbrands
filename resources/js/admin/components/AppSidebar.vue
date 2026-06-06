<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const emit = defineEmits<{ close: [] }>()
const route = useRoute()
const auth = useAuthStore()

const navGroups = [
  {
    section: 'Overview',
    items: [
      { label: 'Dashboard',       path: '/',                 perm: 'dashboard.view',        icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
      { label: 'Tasks',           path: '/tasks',            perm: 'tasks.view',            icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4' },
    ],
  },
  {
    section: 'Business',
    items: [
      { label: 'Service Requests', path: '/service-requests', perm: 'service_requests.view', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' },
      { label: 'Bookings',         path: '/bookings',         perm: 'bookings.view',         icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
      { label: 'Quotations',       path: '/quotations',       perm: 'quotations.view',       icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
      { label: 'Invoices',         path: '/invoices',         perm: 'invoices.view',         icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z' },
      { label: 'Payments',         path: '/payments',         perm: 'payments.view',         icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z' },
    ],
  },
  {
    section: 'Content',
    items: [
      { label: 'Page Content',   path: '/page-content',   perm: 'content.view',  icon: 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z' },
      { label: 'Services',       path: '/services',       perm: 'content.view',  icon: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
      { label: 'Pricing',        path: '/pricing',        perm: 'content.view',  icon: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z' },
      { label: 'Portfolio',      path: '/portfolio',      perm: 'content.view',  icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' },
      { label: 'Gallery',        path: '/gallery',        perm: 'content.view',  icon: 'M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z M15 13a3 3 0 11-6 0 3 3 0 016 0z' },
      { label: 'Testimonials',   path: '/testimonials',   perm: 'content.view',  icon: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z' },
      { label: 'Brands',         path: '/brands',         perm: 'content.view',  icon: 'M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z' },
      { label: 'FAQs',           path: '/faqs',           perm: 'content.view',  icon: 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
      { label: 'Products',       path: '/products',       perm: 'products.view', icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' },
    ],
  },
  {
    section: 'System',
    items: [
      { label: 'Settings', path: '/settings', perm: 'settings.view', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z' },
      { label: 'Users',    path: '/users',    perm: 'users.view',    icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
    ],
  },
]

const roleLabel: Record<string, string> = {
  super_admin: 'Super Admin',
  admin: 'Admin',
  manager: 'Manager',
  staff: 'Staff',
  customer: 'Customer',
}

function isActive(path: string): boolean {
  if (path === '/') return route.path === '/'
  return route.path.startsWith(path)
}

onMounted(() => {
  const el = document.getElementById('csrf-tk') as HTMLInputElement | null
  if (el) el.value = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
})
</script>

<template>
  <div class="w-full h-full flex flex-col overflow-hidden" style="background-color:#1F2937;color:#D1D5DB;">
    <div class="p-6 flex items-center justify-between border-b border-gray-700">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold text-xl" style="background-color:#00BCD4;">TJ</div>
        <div>
          <h2 class="text-white font-bold tracking-tight leading-tight">Tej Printbrands</h2>
          <p class="text-xs font-medium uppercase tracking-wider" style="color:#00BCD4;">Admin Panel</p>
        </div>
      </div>
      <button class="md:hidden text-gray-400 hover:text-white" @click="emit('close')">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-6">
      <template v-for="group in navGroups" :key="group.section">
      <div v-if="group.items.filter(i => auth.can(i.perm)).length > 0">
        <h3 class="text-xs font-semibold uppercase tracking-wider mb-2 px-3" style="color:#6B7280;">{{ group.section }}</h3>
        <ul class="space-y-1">
          <li v-for="item in group.items.filter(i => auth.can(i.perm))" :key="item.label">
            <router-link
              :to="item.path"
              class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors text-sm"
              :class="isActive(item.path) ? 'font-semibold' : 'text-gray-400 hover:text-white'"
              :style="isActive(item.path) ? 'background:rgba(0,188,212,0.2);color:#00BCD4;' : ''"
              @click="emit('close')"
            >
              <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
              </svg>
              <span class="flex-1 text-left">{{ item.label }}</span>
            </router-link>
          </li>
        </ul>
      </div>
      </template>
    </nav>
    <div class="p-4 border-t border-gray-700 space-y-1">
      <!-- User info with role badge -->
      <div class="flex items-center gap-3 px-3 py-2 mb-1">
        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-500 to-blue-500 flex items-center justify-center text-white font-bold text-xs shrink-0">
          {{ (auth.user?.name ?? 'A').charAt(0).toUpperCase() }}
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-white truncate leading-tight">{{ auth.user?.name ?? 'Admin' }}</p>
          <span class="inline-block text-xs px-1.5 py-0.5 rounded font-medium mt-0.5" style="background:rgba(0,188,212,0.2);color:#00BCD4;">
            {{ roleLabel[auth.user?.role] ?? auth.user?.role ?? 'Admin' }}
          </span>
        </div>
      </div>
      <a href="/" class="w-full flex items-center gap-3 px-3 py-2.5 text-gray-400 hover:text-white rounded-lg transition-colors text-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Website
      </a>
      <form method="POST" action="/logout" class="w-full">
        <input type="hidden" name="_token" id="csrf-tk" />
        <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 text-gray-400 hover:text-red-400 rounded-lg transition-colors text-sm text-left">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          Sign Out
        </button>
      </form>
    </div>
  </div>
</template>
