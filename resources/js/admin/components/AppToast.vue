<script setup lang="ts">
import { useToastStore } from '../stores/toast'
const toast = useToastStore()
</script>

<template>
    <Teleport to="body">
        <div class="fixed bottom-6 right-6 z-[100] flex flex-col gap-2">
            <TransitionGroup name="toast">
                <div
                    v-for="t in toast.toasts"
                    :key="t.id"
                    class="flex items-center gap-3 rounded-xl px-5 py-3 shadow-xl text-sm font-semibold"
                    :class="{
                        'bg-emerald-600 text-white': t.type === 'success',
                        'bg-red-600 text-white':     t.type === 'error',
                        'bg-slate-700 text-white':   t.type === 'info',
                    }"
                >
                    <span>{{ t.message }}</span>
                    <button @click="toast.remove(t.id)" class="ml-2 opacity-70 hover:opacity-100">✕</button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(12px); }
</style>
