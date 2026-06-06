<script setup lang="ts">
import { ref } from 'vue'
import AppSidebar from './components/AppSidebar.vue'
import AppToast from './components/AppToast.vue'
import { useAuthStore } from './stores/auth'

const mobileSidebarOpen = ref(false)
const auth = useAuthStore()
const adminUser = auth.user
</script>

<template>
  <div class="flex h-screen w-full overflow-hidden bg-gray-50 font-sans text-gray-900" style="--tw-bg-opacity:1">
    <div class="hidden md:block w-72 h-full shrink-0 z-20 shadow-xl">
      <AppSidebar @close="mobileSidebarOpen = false" />
    </div>
    <div v-if="mobileSidebarOpen" class="md:hidden fixed inset-0 z-50 flex">
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="mobileSidebarOpen = false" />
      <div class="relative w-72 h-full shadow-2xl">
        <AppSidebar @close="mobileSidebarOpen = false" />
      </div>
    </div>
    <div class="flex-1 flex flex-col h-full overflow-hidden relative z-10">
      <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 sm:px-6 shrink-0 z-10">
        <div class="flex items-center gap-4">
          <button class="md:hidden p-2 -ml-2 text-gray-500 hover:text-dark rounded-lg hover:bg-gray-100" @click="mobileSidebarOpen = true">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
          </button>
          <div class="hidden sm:flex relative w-64 lg:w-96">
            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="M21 21l-4.35-4.35"/></svg>
            <input type="text" placeholder="Search anything..." class="w-full pl-9 pr-4 py-2 bg-gray-100 border-transparent rounded-lg text-sm focus:bg-white focus:border-cyan focus:ring-2 focus:ring-cyan/20 outline-none transition-all border" />
          </div>
        </div>
        <div class="flex items-center gap-3 sm:gap-5">
          <button class="relative p-2 text-gray-500 hover:text-dark rounded-full hover:bg-gray-100 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red rounded-full border border-white"></span>
          </button>
          <div class="h-8 w-px bg-gray-200"></div>
          <div class="flex items-center gap-3">
            <div class="hidden sm:block text-right">
              <p class="text-sm font-bold text-dark leading-none">{{ adminUser?.name ?? 'Admin' }}</p>
              <p class="text-xs text-gray-500 mt-1">{{ auth.user.role?.replace('_', ' ').replace(/\b\w/g, (c: string) => c.toUpperCase()) ?? 'Admin' }}</p>
            </div>
            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-cyan to-blue-500 flex items-center justify-center text-white font-bold text-sm shadow-sm border-2 border-white">
              {{ (adminUser?.name ?? 'A').charAt(0).toUpperCase() }}
            </div>
          </div>
        </div>
      </header>
      <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
          <RouterView />
        </div>
      </main>
    </div>
    <AppToast />
  </div>
</template>
