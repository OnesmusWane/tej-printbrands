<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api from '../api'
import { useToastStore } from '../stores/toast'
import ImageUpload from '../components/ImageUpload.vue'
import RichTextEditor from '../components/RichTextEditor.vue'

interface BlogPost {
    id: number
    slug: string
    title: string
    excerpt: string | null
    content: string | null
    image_url: string | null
    author: string | null
    category: string | null
    is_published: boolean
    published_at: string | null
    sort_order: number
    read_time: string | null
    created_at: string
}

const toast   = useToastStore()
const items   = ref<BlogPost[]>([])
const loading = ref(false)
const saving  = ref(false)
const showModal = ref(false)
const editing   = ref<Partial<BlogPost>>({})

async function load() {
    loading.value = true
    try {
        const { data } = await api.get('/blog-posts', { params: { per_page: 200 } })
        items.value = Array.isArray(data) ? data : (data.data ?? [])
    } catch {
        toast.add('Failed to load blog posts.', 'error')
    } finally {
        loading.value = false
    }
}

function openNew() {
    editing.value = { is_published: false, sort_order: 0, read_time: null }
    showModal.value = true
}

function openEdit(post: BlogPost) {
    editing.value = {
        ...post,
        published_at: post.published_at ? post.published_at.slice(0, 10) : null,
    }
    showModal.value = true
}

async function submit() {
    saving.value = true
    try {
        const payload = { ...editing.value }
        // Clear published_at when not published
        if (!payload.is_published) payload.published_at = null

        if (editing.value.id) {
            await api.patch(`/blog-posts/${editing.value.id}`, payload as any)
            toast.add('Post updated.')
        } else {
            await api.post('/blog-posts', payload as any)
            toast.add('Post created.')
        }
        showModal.value = false
        await load()
    } catch (err: any) {
        toast.add(err?.response?.data?.message ?? 'Save failed.', 'error')
    } finally {
        saving.value = false
    }
}

async function remove(id: number) {
    if (!confirm('Delete this post?')) return
    try {
        await api.delete(`/blog-posts/${id}`)
        items.value = items.value.filter(p => p.id !== id)
        toast.add('Post deleted.')
    } catch {
        toast.add('Delete failed.', 'error')
    }
}

async function togglePublished(post: BlogPost) {
    try {
        await api.patch(`/blog-posts/${post.id}`, { is_published: !post.is_published })
        post.is_published = !post.is_published
        toast.add(post.is_published ? 'Post published.' : 'Post set to draft.')
    } catch {
        toast.add('Update failed.', 'error')
    }
}

const statusBadge = computed(() => (published: boolean) =>
    published
        ? 'bg-green-100 text-green-700'
        : 'bg-gray-100 text-gray-500'
)

function formatDate(d?: string | null) {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('en-KE', { day: 'numeric', month: 'short', year: 'numeric' })
}

onMounted(load)
</script>

<template>
    <div class="p-6 space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-dark">Blog</h1>
                <p class="text-sm text-gray-500 mt-1">Manage blog posts shown on the website.</p>
            </div>
            <button
                @click="openNew"
                class="inline-flex items-center gap-2 bg-cyan-500 hover:bg-cyan-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors shadow-sm"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Post
            </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-16">
            <div class="w-8 h-8 border-4 border-cyan-500 border-t-transparent rounded-full animate-spin"></div>
        </div>

        <!-- Table -->
        <div v-else class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <table v-if="items.length > 0" class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Post</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Category</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Author</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Published</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-for="post in items" :key="post.id" class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div v-if="post.image_url" class="w-12 h-9 rounded-lg overflow-hidden shrink-0 bg-gray-100">
                                    <img :src="post.image_url" :alt="post.title" class="w-full h-full object-cover" />
                                </div>
                                <div v-else class="w-12 h-9 rounded-lg bg-gradient-to-br from-cyan-50 to-slate-100 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-cyan-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-dark truncate max-w-[200px]">{{ post.title }}</p>
                                    <p v-if="post.excerpt" class="text-xs text-gray-400 truncate max-w-[200px] mt-0.5">{{ post.excerpt }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 hidden md:table-cell">
                            <span v-if="post.category" class="text-xs bg-cyan-50 text-cyan-700 font-semibold px-2 py-0.5 rounded-full">{{ post.category }}</span>
                            <span v-else class="text-gray-400">—</span>
                        </td>
                        <td class="px-5 py-4 text-gray-500 hidden lg:table-cell">{{ post.author ?? '—' }}</td>
                        <td class="px-5 py-4">
                            <button
                                @click="togglePublished(post)"
                                :class="['text-xs font-semibold px-2.5 py-1 rounded-full transition-colors', post.is_published ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200']"
                            >{{ post.is_published ? 'Published' : 'Draft' }}</button>
                        </td>
                        <td class="px-5 py-4 text-gray-500 hidden lg:table-cell text-xs">{{ formatDate(post.published_at) }}</td>
                        <td class="px-5 py-4 text-right">
                            <div class="inline-flex gap-2">
                                <button @click="openEdit(post)" class="text-xs font-semibold text-gray-600 border border-gray-200 rounded-lg px-3 py-1.5 hover:bg-gray-50 transition-colors">Edit</button>
                                <button @click="remove(post.id)" class="text-xs font-semibold text-red-500 border border-red-100 rounded-lg px-3 py-1.5 hover:bg-red-50 transition-colors">Delete</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="text-center py-16 text-gray-400 text-sm">
                No blog posts yet. Create your first post.
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                    @click.self="showModal = false"
                >
                    <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl">
                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                            <h2 class="text-lg font-bold text-dark">{{ editing.id ? 'Edit Post' : 'New Post' }}</h2>
                            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                        </div>
                        <form @submit.prevent="submit" class="px-6 py-5 space-y-4">
                            <!-- Title -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Title <span class="text-red-400">*</span></label>
                                <input
                                    v-model="editing.title"
                                    required
                                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"
                                />
                            </div>

                            <!-- Excerpt -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Excerpt <span class="text-gray-400 font-normal">(short summary)</span></label>
                                <textarea
                                    v-model="editing.excerpt"
                                    rows="2"
                                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all resize-none"
                                ></textarea>
                            </div>

                            <!-- Content -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Content</label>
                                <RichTextEditor v-model="editing.content" />
                            </div>

                            <!-- Cover Image -->
                            <ImageUpload v-model="editing.image_url" label="Cover Image" height="h-48" />

                            <!-- Author + Category + Read Time -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Author</label>
                                    <input
                                        v-model="editing.author"
                                        placeholder="Tej Printbrands"
                                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Category</label>
                                    <input
                                        v-model="editing.category"
                                        placeholder="e.g. Design Tips"
                                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Read Time</label>
                                    <input
                                        v-model="editing.read_time"
                                        placeholder="5 min read"
                                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"
                                    />
                                </div>
                            </div>

                            <!-- Published toggle -->
                            <div class="flex items-center gap-3">
                                <input
                                    type="checkbox"
                                    id="blog-published"
                                    v-model="editing.is_published"
                                    class="w-4 h-4 rounded border-gray-300 text-cyan-500 focus:ring-cyan-500"
                                />
                                <label for="blog-published" class="text-sm text-gray-700">Published</label>
                            </div>

                            <!-- Published at (only when published) -->
                            <div v-if="editing.is_published">
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Publish Date</label>
                                <input
                                    type="date"
                                    v-model="editing.published_at"
                                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"
                                />
                            </div>

                            <!-- Actions -->
                            <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                                <button
                                    type="button"
                                    @click="showModal = false"
                                    class="px-4 py-2 text-sm font-semibold text-gray-600 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors"
                                >Cancel</button>
                                <button
                                    type="submit"
                                    :disabled="saving"
                                    class="px-5 py-2 text-sm font-semibold bg-cyan-500 hover:bg-cyan-600 disabled:opacity-60 text-white rounded-xl transition-colors"
                                >{{ saving ? 'Saving…' : editing.id ? 'Update' : 'Create' }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: all 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
    transform: scale(0.97);
}
</style>
