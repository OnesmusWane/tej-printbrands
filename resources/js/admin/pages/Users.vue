<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import api from '../api'
import { useAuthStore } from '../stores/auth'
import { useToastStore } from '../stores/toast'

const auth  = useAuthStore()
const toast = useToastStore()

// ─── types ────────────────────────────────────────────────────────────────────
interface AdminUser {
    id: number
    name: string
    email: string
    phone: string | null
    role: string
    permissions: string[]
    created_at: string
}

// ─── state ────────────────────────────────────────────────────────────────────
const users   = ref<AdminUser[]>([])
const loading = ref(true)
const search  = ref('')

// ─── modal ────────────────────────────────────────────────────────────────────
const showModal  = ref(false)
const saving     = ref(false)
const editTarget = ref<AdminUser | null>(null)

const emptyForm = () => ({
    name:        '',
    email:       '',
    phone:       '',
    role:        'staff' as string,
    password:    '',
    permissions: [] as string[],
})

const form = reactive(emptyForm())
const errors = ref<Record<string, string>>({})

// ─── delete confirm ───────────────────────────────────────────────────────────
const deletingId = ref<number | null>(null)

// ─── role config ──────────────────────────────────────────────────────────────
const roleOptions = [
    { value: 'super_admin', label: 'Super Admin' },
    { value: 'admin',       label: 'Admin' },
    { value: 'manager',     label: 'Manager' },
    { value: 'staff',       label: 'Staff' },
]

const roleBadge: Record<string, string> = {
    super_admin: 'bg-purple-100 text-purple-700 border-purple-200',
    admin:       'bg-cyan-100 text-cyan-700 border-cyan-200',
    manager:     'bg-blue-100 text-blue-700 border-blue-200',
    staff:       'bg-gray-100 text-gray-600 border-gray-200',
}

const roleLabel: Record<string, string> = {
    super_admin: 'Super Admin',
    admin:       'Admin',
    manager:     'Manager',
    staff:       'Staff',
}

// ─── filtered list ────────────────────────────────────────────────────────────
const filteredUsers = computed(() => {
    const q = search.value.toLowerCase().trim()
    if (!q) return users.value
    return users.value.filter(u =>
        u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
    )
})

// ─── load ─────────────────────────────────────────────────────────────────────
async function loadUsers() {
    loading.value = true
    try {
        const { data } = await api.get('/admin-users')
        users.value = data
    } catch {
        toast.add('Failed to load users.', 'error')
    } finally {
        loading.value = false
    }
}

// ─── open modal ───────────────────────────────────────────────────────────────
function openCreate() {
    editTarget.value = null
    Object.assign(form, emptyForm())
    errors.value = {}
    showModal.value = true
}

function openEdit(u: AdminUser) {
    editTarget.value = u
    Object.assign(form, {
        name:        u.name,
        email:       u.email,
        phone:       u.phone ?? '',
        role:        u.role,
        password:    '',
        permissions: [...(u.permissions ?? [])],
    })
    errors.value = {}
    showModal.value = true
}

// ─── permission toggle ────────────────────────────────────────────────────────
function togglePermission(key: string) {
    const idx = form.permissions.indexOf(key)
    if (idx === -1) form.permissions.push(key)
    else form.permissions.splice(idx, 1)
}

// ─── save ─────────────────────────────────────────────────────────────────────
async function save() {
    saving.value  = true
    errors.value  = {}
    const payload: Record<string, unknown> = {
        name:        form.name,
        email:       form.email,
        phone:       form.phone || null,
        role:        form.role,
        permissions: form.permissions,
    }
    if (form.password) payload.password = form.password

    try {
        if (editTarget.value) {
            const { data } = await api.patch(`/admin-users/${editTarget.value.id}`, payload)
            const idx = users.value.findIndex(u => u.id === data.id)
            if (idx !== -1) users.value[idx] = data
            toast.add('User updated successfully.')
        } else {
            payload.password = form.password
            const { data } = await api.post('/admin-users', payload)
            users.value.unshift(data)
            toast.add('User created successfully.')
        }
        showModal.value = false
    } catch (e: any) {
        const errs = e.response?.data?.errors ?? {}
        if (Object.keys(errs).length) {
            Object.entries(errs).forEach(([k, v]) => {
                errors.value[k] = (v as string[])[0]
            })
        } else {
            toast.add(e.response?.data?.message ?? 'Failed to save user.', 'error')
        }
    } finally {
        saving.value = false
    }
}

// ─── delete ───────────────────────────────────────────────────────────────────
async function deleteUser(u: AdminUser) {
    if (u.id === auth.user.id) return
    if (!confirm(`Delete ${u.name}? This cannot be undone.`)) return
    deletingId.value = u.id
    try {
        await api.delete(`/admin-users/${u.id}`)
        users.value = users.value.filter(x => x.id !== u.id)
        toast.add('User deleted.')
    } catch (e: any) {
        toast.add(e.response?.data?.message ?? 'Failed to delete user.', 'error')
    } finally {
        deletingId.value = null
    }
}

// ─── date format ──────────────────────────────────────────────────────────────
function fmtDate(iso: string) {
    return new Date(iso).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

function initials(name: string) {
    return name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase()
}

onMounted(loadUsers)
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Admin Users</h1>
        <p class="text-sm text-gray-500 mt-1">Manage admin accounts and their permissions</p>
      </div>
      <button
        v-if="auth.can('users.edit')"
        @click="openCreate"
        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white shadow-sm transition-all hover:-translate-y-0.5"
        style="background:#00BCD4;"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add User
      </button>
    </div>

    <!-- Search -->
    <div class="relative w-full sm:max-w-xs">
      <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="M21 21l-4.35-4.35"/></svg>
      <input
        v-model="search"
        type="text"
        placeholder="Search by name or email…"
        class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all bg-white"
      />
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center items-center py-24">
      <div class="w-10 h-10 border-4 border-t-transparent rounded-full animate-spin" style="border-color:#00BCD4;border-top-color:transparent;"></div>
    </div>

    <!-- Table -->
    <div v-else class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div v-if="!filteredUsers.length" class="flex flex-col items-center justify-center py-16 text-center">
        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        <p class="text-sm font-medium text-gray-500">No admin users found</p>
        <p class="text-xs text-gray-400 mt-1">{{ search ? 'Try a different search term.' : 'Create the first admin user above.' }}</p>
      </div>

      <table v-else class="w-full text-sm">
        <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500 border-b border-gray-100">
          <tr>
            <th class="px-4 py-3 text-left font-medium">User</th>
            <th class="px-4 py-3 text-left font-medium">Role</th>
            <th class="px-4 py-3 text-left font-medium hidden sm:table-cell">Permissions</th>
            <th class="px-4 py-3 text-left font-medium hidden md:table-cell">Joined</th>
            <th class="px-4 py-3 text-right font-medium">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-for="u in filteredUsers" :key="u.id" class="hover:bg-gray-50 transition-colors">
            <!-- Avatar + name/email -->
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <div
                  class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold text-xs shrink-0"
                  style="background:linear-gradient(135deg,#06b6d4,#3b82f6);"
                >
                  {{ initials(u.name) }}
                </div>
                <div class="min-w-0">
                  <p class="font-semibold text-gray-900 leading-tight truncate">
                    {{ u.name }}
                    <span v-if="u.id === auth.user.id" class="ml-1 text-xs font-normal text-gray-400">(you)</span>
                  </p>
                  <p class="text-xs text-gray-500 truncate">{{ u.email }}</p>
                  <p v-if="u.phone" class="text-xs text-gray-400">{{ u.phone }}</p>
                </div>
              </div>
            </td>

            <!-- Role badge -->
            <td class="px-4 py-3">
              <span :class="['px-2 py-0.5 rounded-full text-xs font-medium border', roleBadge[u.role] ?? 'bg-gray-100 text-gray-600 border-gray-200']">
                {{ roleLabel[u.role] ?? u.role }}
              </span>
            </td>

            <!-- Permissions count -->
            <td class="px-4 py-3 hidden sm:table-cell">
              <span v-if="u.role === 'super_admin'" class="text-xs text-purple-600 font-medium">All permissions</span>
              <span v-else class="text-xs text-gray-500">
                {{ (u.permissions ?? []).length }} permission{{ (u.permissions ?? []).length === 1 ? '' : 's' }}
              </span>
            </td>

            <!-- Joined date -->
            <td class="px-4 py-3 hidden md:table-cell text-xs text-gray-500">
              {{ fmtDate(u.created_at) }}
            </td>

            <!-- Actions -->
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-2">
                <button
                  v-if="auth.can('users.edit')"
                  @click="openEdit(u)"
                  class="p-1.5 text-gray-400 hover:text-cyan-600 hover:bg-cyan-50 rounded-lg transition-colors"
                  title="Edit user"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <button
                  v-if="auth.can('users.edit')"
                  @click="deleteUser(u)"
                  :disabled="u.id === auth.user.id || deletingId === u.id"
                  :title="u.id === auth.user.id ? 'Cannot delete your own account' : 'Delete user'"
                  class="p-1.5 rounded-lg transition-colors"
                  :class="u.id === auth.user.id ? 'text-gray-200 cursor-not-allowed' : 'text-gray-400 hover:text-red-600 hover:bg-red-50'"
                >
                  <svg v-if="deletingId === u.id" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                  <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ─── Add / Edit Modal ─────────────────────────────────────────────────── -->
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="showModal = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl max-h-[92vh] flex flex-col">

          <!-- Modal header -->
          <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0">
            <div>
              <h3 class="text-lg font-bold text-gray-900">{{ editTarget ? 'Edit User' : 'Add Admin User' }}</h3>
              <p class="text-xs text-gray-500 mt-0.5">{{ editTarget ? 'Update account details and permissions.' : 'Create a new admin account.' }}</p>
            </div>
            <button @click="showModal = false" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>

          <!-- Modal body -->
          <div class="overflow-y-auto flex-1 p-6 space-y-5">

            <!-- Name + Email -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                <input
                  v-model="form.name"
                  type="text"
                  placeholder="Full name"
                  class="w-full border rounded-lg px-3 py-2.5 text-sm outline-none transition-all"
                  :class="errors.name ? 'border-red-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20' : 'border-gray-300 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20'"
                />
                <p v-if="errors.name" class="text-xs text-red-500 mt-1">{{ errors.name }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                <input
                  v-model="form.email"
                  type="email"
                  placeholder="email@example.com"
                  class="w-full border rounded-lg px-3 py-2.5 text-sm outline-none transition-all"
                  :class="errors.email ? 'border-red-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20' : 'border-gray-300 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20'"
                />
                <p v-if="errors.email" class="text-xs text-red-500 mt-1">{{ errors.email }}</p>
              </div>
            </div>

            <!-- Phone + Role -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input
                  v-model="form.phone"
                  type="text"
                  placeholder="+254..."
                  class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role <span class="text-red-500">*</span></label>
                <select
                  v-model="form.role"
                  class="w-full border rounded-lg px-3 py-2.5 text-sm outline-none transition-all"
                  :class="errors.role ? 'border-red-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20' : 'border-gray-300 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20'"
                >
                  <option v-for="r in roleOptions" :key="r.value" :value="r.value">{{ r.label }}</option>
                </select>
                <p v-if="errors.role" class="text-xs text-red-500 mt-1">{{ errors.role }}</p>
              </div>
            </div>

            <!-- Password -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Password
                <span v-if="!editTarget" class="text-red-500">*</span>
                <span v-else class="text-xs font-normal text-gray-400 ml-1">(leave blank to keep current)</span>
              </label>
              <input
                v-model="form.password"
                type="password"
                placeholder="Min. 8 characters"
                class="w-full border rounded-lg px-3 py-2.5 text-sm outline-none transition-all"
                :class="errors.password ? 'border-red-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20' : 'border-gray-300 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20'"
              />
              <p v-if="errors.password" class="text-xs text-red-500 mt-1">{{ errors.password }}</p>
            </div>

            <!-- Permissions (hidden for super_admin) -->
            <div v-if="form.role !== 'super_admin'">
              <p class="text-sm font-medium text-gray-700 mb-3">Permissions</p>
              <div class="space-y-4">
                <div v-for="(perms, group) in auth.allPermissions" :key="group">
                  <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ group }}</p>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <label
                      v-for="(label, key) in perms"
                      :key="key"
                      class="flex items-center gap-2.5 cursor-pointer group"
                    >
                      <div
                        class="w-4 h-4 rounded border-2 flex items-center justify-center shrink-0 transition-colors"
                        :class="form.permissions.includes(key) ? 'border-cyan-500 bg-cyan-500' : 'border-gray-300 bg-white group-hover:border-cyan-400'"
                        @click="togglePermission(key)"
                      >
                        <svg v-if="form.permissions.includes(key)" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                      </div>
                      <span class="text-sm text-gray-700 select-none" @click="togglePermission(key)">{{ label }}</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="rounded-lg bg-purple-50 border border-purple-200 px-4 py-3 text-sm text-purple-700">
              Super Admin has access to all features — no individual permissions needed.
            </div>

          </div>

          <!-- Modal footer -->
          <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl flex items-center justify-end gap-3 shrink-0">
            <button
              type="button"
              @click="showModal = false"
              class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-100 transition-colors"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="save"
              :disabled="saving"
              class="px-6 py-2 rounded-lg text-sm font-semibold text-white flex items-center gap-2 transition-all hover:-translate-y-0.5 disabled:opacity-60 disabled:cursor-not-allowed"
              style="background:#00BCD4;"
            >
              <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
              {{ saving ? 'Saving…' : (editTarget ? 'Save Changes' : 'Create User') }}
            </button>
          </div>

        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
