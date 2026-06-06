<script setup lang="ts">
import { ref } from 'vue'
import api from '../api'

const props = defineProps<{
  modelValue?: string
  label?: string
  height?: string
}>()
const emit = defineEmits<{ 'update:modelValue': [url: string] }>()

const uploading = ref(false)
const dragOver  = ref(false)
const error     = ref('')
const fileInput = ref<HTMLInputElement>()

async function upload(file: File) {
  if (!file.type.startsWith('image/')) { error.value = 'Please select an image file.'; return }
  if (file.size > 8 * 1024 * 1024) { error.value = 'File must be under 8 MB.'; return }
  uploading.value = true; error.value = ''
  try {
    const fd = new FormData()
    fd.append('file', file)
    const { data } = await api.post('/upload', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    emit('update:modelValue', data.url)
  } catch (e: any) {
    error.value = e.response?.data?.message ?? 'Upload failed.'
  } finally {
    uploading.value = false
  }
}

function onFileChange(e: Event) {
  const f = (e.target as HTMLInputElement).files?.[0]
  if (f) upload(f)
}

function onDrop(e: DragEvent) {
  dragOver.value = false
  const f = e.dataTransfer?.files?.[0]
  if (f) upload(f)
}

function clear() {
  emit('update:modelValue', '')
  if (fileInput.value) fileInput.value.value = ''
}
</script>

<template>
  <div>
    <label v-if="label" class="block text-xs font-semibold text-gray-600 mb-1.5">{{ label }}</label>

    <!-- Drop zone -->
    <div
      @click="fileInput?.click()"
      @dragover.prevent="dragOver = true"
      @dragleave.prevent="dragOver = false"
      @drop.prevent="onDrop"
      :class="[
        'relative rounded-xl border-2 border-dashed cursor-pointer transition-all overflow-hidden select-none',
        dragOver ? 'border-cyan-400 bg-cyan-50/60' : 'border-gray-300 hover:border-cyan-400 hover:bg-gray-50',
        height ?? 'h-36',
      ]"
    >
      <!-- Existing image -->
      <img v-if="modelValue" :src="modelValue" class="absolute inset-0 w-full h-full object-cover pointer-events-none" />

      <!-- Overlay / empty state -->
      <div :class="['absolute inset-0 flex flex-col items-center justify-center gap-1.5 transition-all', modelValue ? 'bg-black/40 opacity-0 hover:opacity-100' : '']">
        <div v-if="uploading" class="flex flex-col items-center gap-2">
          <div class="w-7 h-7 border-[3px] border-white border-t-transparent rounded-full animate-spin"></div>
          <span class="text-xs font-medium text-white">Uploading…</span>
        </div>
        <template v-else>
          <svg class="w-7 h-7" :class="modelValue ? 'text-white' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
          </svg>
          <span class="text-xs font-medium" :class="modelValue ? 'text-white' : 'text-gray-500'">
            {{ modelValue ? 'Click or drag to replace' : 'Drop image or click to browse' }}
          </span>
          <span v-if="!modelValue" class="text-[11px] text-gray-400">JPG, PNG, WEBP — max 8 MB</span>
        </template>
      </div>
    </div>

    <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onFileChange" />

    <div class="flex items-center justify-between mt-1 min-h-[20px]">
      <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
      <button v-if="modelValue && !uploading" type="button" @click.stop="clear"
        class="text-xs text-red-400 hover:text-red-600 transition-colors ml-auto">
        Remove image
      </button>
    </div>
  </div>
</template>
