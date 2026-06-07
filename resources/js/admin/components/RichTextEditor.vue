<script setup lang="ts">
import { watch, onBeforeUnmount } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'

const props = defineProps<{ modelValue: string | null }>()
const emit  = defineEmits<{ (e: 'update:modelValue', v: string): void }>()

const editor = useEditor({
    content: props.modelValue ?? '',
    extensions: [
        StarterKit,
        Link.configure({ openOnClick: false }),
    ],
    editorProps: {
        attributes: { class: 'prose prose-slate max-w-none min-h-[220px] outline-none px-4 py-3 text-sm text-slate-700' },
    },
    onUpdate({ editor }) {
        emit('update:modelValue', editor.getHTML())
    },
})

watch(() => props.modelValue, (val) => {
    if (!editor.value) return
    const current = editor.value.getHTML()
    if (current !== (val ?? '')) {
        editor.value.commands.setContent(val ?? '', false)
    }
})

onBeforeUnmount(() => editor.value?.destroy())

function setLink() {
    if (!editor.value) return
    const prev = editor.value.getAttributes('link').href ?? ''
    const url = window.prompt('URL', prev)
    if (url === null) return
    if (url === '') {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run()
    } else {
        editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
    }
}
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-gray-300 bg-white transition-all focus-within:border-cyan-500 focus-within:ring-2 focus-within:ring-cyan-500/20">
        <!-- Toolbar -->
        <div class="flex flex-wrap items-center gap-0.5 border-b border-gray-200 bg-gray-50 px-2 py-1.5">
            <!-- Text style -->
            <button type="button" title="Bold" @click="editor?.chain().focus().toggleBold().run()" :class="['toolbar-btn', editor?.isActive('bold') ? 'is-active' : '']">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M6 4h8a4 4 0 0 1 0 8H6zm0 8h9a4 4 0 0 1 0 8H6z"/></svg>
            </button>
            <button type="button" title="Italic" @click="editor?.chain().focus().toggleItalic().run()" :class="['toolbar-btn', editor?.isActive('italic') ? 'is-active' : '']">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M11.49 3h7V5h-2.75L10.26 19H13v2H6v-2h2.76L14.75 5H12z"/></svg>
            </button>
            <button type="button" title="Strike" @click="editor?.chain().focus().toggleStrike().run()" :class="['toolbar-btn', editor?.isActive('strike') ? 'is-active' : '']">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M13 16h-2v4H9v-4H5v-2h14v2zM10 9.5c-.83 0-1.5.67-1.5 1.5H7a3 3 0 0 1 3-3h4a3 3 0 0 1 0 6h-4a1.5 1.5 0 0 1 0-3h4c.83 0 1.5-.67 1.5-1.5S14.83 8 14 8h-4z"/></svg>
            </button>

            <div class="mx-1 h-5 w-px bg-gray-300"></div>

            <!-- Headings -->
            <button type="button" title="Heading 1" @click="editor?.chain().focus().toggleHeading({ level: 1 }).run()" :class="['toolbar-btn font-bold text-xs', editor?.isActive('heading', { level: 1 }) ? 'is-active' : '']">H1</button>
            <button type="button" title="Heading 2" @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()" :class="['toolbar-btn font-bold text-xs', editor?.isActive('heading', { level: 2 }) ? 'is-active' : '']">H2</button>
            <button type="button" title="Heading 3" @click="editor?.chain().focus().toggleHeading({ level: 3 }).run()" :class="['toolbar-btn font-bold text-xs', editor?.isActive('heading', { level: 3 }) ? 'is-active' : '']">H3</button>

            <div class="mx-1 h-5 w-px bg-gray-300"></div>

            <!-- Lists -->
            <button type="button" title="Bullet list" @click="editor?.chain().focus().toggleBulletList().run()" :class="['toolbar-btn', editor?.isActive('bulletList') ? 'is-active' : '']">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="3" cy="6" r="1" fill="currentColor" stroke="none"/><circle cx="3" cy="12" r="1" fill="currentColor" stroke="none"/><circle cx="3" cy="18" r="1" fill="currentColor" stroke="none"/></svg>
            </button>
            <button type="button" title="Ordered list" @click="editor?.chain().focus().toggleOrderedList().run()" :class="['toolbar-btn', editor?.isActive('orderedList') ? 'is-active' : '']">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><path d="M4 6h1v4" stroke="currentColor" stroke-linecap="round"/><path d="M4 10h2" stroke="currentColor" stroke-linecap="round"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1" stroke="currentColor" stroke-linecap="round"/></svg>
            </button>

            <div class="mx-1 h-5 w-px bg-gray-300"></div>

            <!-- Blockquote + HR + Link -->
            <button type="button" title="Blockquote" @click="editor?.chain().focus().toggleBlockquote().run()" :class="['toolbar-btn', editor?.isActive('blockquote') ? 'is-active' : '']">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1zm12 0c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z"/></svg>
            </button>
            <button type="button" title="Link" @click="setLink" :class="['toolbar-btn', editor?.isActive('link') ? 'is-active' : '']">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/></svg>
            </button>
            <button type="button" title="Horizontal rule" @click="editor?.chain().focus().setHorizontalRule().run()" class="toolbar-btn">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/></svg>
            </button>

            <div class="mx-1 h-5 w-px bg-gray-300"></div>

            <!-- Undo / Redo -->
            <button type="button" title="Undo" @click="editor?.chain().focus().undo().run()" :disabled="!editor?.can().undo()" class="toolbar-btn disabled:opacity-30">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v6h6"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 13A9 9 0 1 0 5.47 6.36"/></svg>
            </button>
            <button type="button" title="Redo" @click="editor?.chain().focus().redo().run()" :disabled="!editor?.can().redo()" class="toolbar-btn disabled:opacity-30">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7v6h-6"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 13A9 9 0 1 1 18.53 6.36"/></svg>
            </button>
        </div>

        <!-- Editor area -->
        <EditorContent :editor="editor" />
    </div>
</template>

<style scoped>
.toolbar-btn {
    display: flex;
    height: 1.75rem;
    width: 1.75rem;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
    color: #6b7280;
    transition: color 150ms, background-color 150ms;
}
.toolbar-btn:hover {
    background-color: #e5e7eb;
    color: #111827;
}
.toolbar-btn.is-active {
    background-color: #cffafe;
    color: #0e7490;
}
</style>

<style>
/* Unscoped so they reach the Tiptap ProseMirror DOM */
.ProseMirror a { color: #00BCD4; text-decoration: underline; }
.ProseMirror p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    color: #9ca3af;
    float: left;
    height: 0;
    pointer-events: none;
}
</style>
