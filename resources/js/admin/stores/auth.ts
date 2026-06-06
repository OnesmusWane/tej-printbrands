import { defineStore } from 'pinia'

interface AdminUser {
    id: number
    name: string
    email: string
    role: string
    is_admin: boolean
    permissions: string[]
}

export const useAuthStore = defineStore('auth', () => {
    const user = ((window as any).__ADMIN_USER__ ?? {}) as AdminUser
    const allPermissions = (window as any).__PERMISSIONS__ as Record<string, Record<string, string>>

    function can(key: string): boolean {
        if (!user?.role) return false
        if (user.role === 'super_admin') return true
        return Array.isArray(user.permissions) && user.permissions.includes(key)
    }

    function canAny(...keys: string[]): boolean {
        return keys.some(k => can(k))
    }

    const hasAnyPermission = Object.keys(allPermissions ?? {})
        .flatMap(g => Object.keys((allPermissions as any)[g]))
        .some(k => can(k))

    return { user, allPermissions, can, canAny, hasAnyPermission }
})
