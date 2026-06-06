<script setup lang="ts">
defineProps<{ title: string; open: boolean }>()
const emit = defineEmits<{ close: [] }>()
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm" @click.self="emit('close')">
                <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl bg-slate-800 shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-700 px-6 py-4">
                        <h2 class="text-lg font-extrabold text-white">{{ title }}</h2>
                        <button @click="emit('close')" class="text-slate-400 hover:text-white text-xl leading-none">✕</button>
                    </div>
                    <div class="px-6 py-5">
                        <slot />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.96); }
</style>
