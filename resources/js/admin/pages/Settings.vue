<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '../api'
import { useToastStore } from '../stores/toast'
import ImageUpload from '../components/ImageUpload.vue'

const toast = useToastStore()

const company  = ref({ company_name: '', tagline: '', description: '', logo_url: '', favicon_url: '' })
const contact  = ref({ email: '', phone: '', phone_secondary: '', support_email: '', hours: '', address: '', website: '', whatsapp_number: '', whatsapp_message: '' })
const socials  = ref({ facebook: '', instagram: '', twitter: '', linkedin: '', youtube: '', tiktok: '' })
const business = ref({ currency: 'KES', tax_rate: '', mpesa_shortcode: '', paybill_account: '', mpesa_env: 'sandbox', mpesa_consumer_key: '', mpesa_consumer_secret: '', mpesa_passkey: '' })
const password = ref({ current_password: '', password: '', password_confirmation: '' })
const passwordErrors = ref<Record<string, string[]>>({})
const showMpesaKeys = ref(false)

const loadingSettings = ref(false)
const savingSection   = ref<string | null>(null)
const savingPassword  = ref(false)

async function load() {
    loadingSettings.value = true
    try {
        const { data } = await api.get('/site-settings')
        const records: { key: string; value: Record<string, string> }[] = Array.isArray(data) ? data : data.data ?? []
        for (const record of records) {
            const val = typeof record.value === 'string' ? JSON.parse(record.value) : record.value
            if (record.key === 'company')  Object.assign(company.value, val)
            else if (record.key === 'contact')  Object.assign(contact.value, val)
            else if (record.key === 'socials')  Object.assign(socials.value, val)
            else if (record.key === 'business') Object.assign(business.value, val)
        }
    } catch {
        toast.add('Failed to load settings.', 'error')
    } finally {
        loadingSettings.value = false
    }
}

async function saveSection(key: string, value: Record<string, unknown>) {
    savingSection.value = key
    try {
        await api.patch(`/site-settings/${key}`, { value })
        toast.add('Settings saved.')
    } catch (err: any) {
        toast.add(err?.response?.data?.message ?? 'Save failed.', 'error')
    } finally {
        savingSection.value = null
    }
}

async function changePassword() {
    passwordErrors.value = {}
    savingPassword.value = true
    try {
        await api.post('/profile/change-password', password.value)
        toast.add('Password changed successfully.')
        password.value = { current_password: '', password: '', password_confirmation: '' }
    } catch (err: any) {
        if (err?.response?.status === 422 && err.response.data?.errors) {
            passwordErrors.value = err.response.data.errors
        } else {
            toast.add(err?.response?.data?.message ?? 'Password change failed.', 'error')
        }
    } finally {
        savingPassword.value = false
    }
}

function fieldError(field: string): string {
    return passwordErrors.value[field]?.[0] ?? ''
}

onMounted(load)
</script>

<template>
    <div class="p-6 space-y-6 max-w-4xl">
        <div>
            <h1 class="text-2xl font-bold text-dark">Settings</h1>
            <p class="text-sm text-gray-500 mt-1">Configure your site and business settings.</p>
        </div>

        <div v-if="loadingSettings" class="flex justify-center py-16">
            <div class="w-8 h-8 border-4 border-cyan-500 border-t-transparent rounded-full animate-spin"></div>
        </div>

        <template v-else>

            <!-- ── 1. Company Information ──────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="bg-gray-50 px-5 py-3 border-b border-gray-100 flex items-center gap-3">
                    <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span class="text-sm font-semibold text-dark">Company Information</span>
                </div>
                <form @submit.prevent="saveSection('company', company)" class="p-5 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Company Name</label>
                            <input v-model="company.company_name" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Tagline</label>
                            <input v-model="company.tagline" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Description</label>
                        <textarea v-model="company.description" rows="3" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all resize-none"></textarea>
                    </div>

                    <!-- Logo + Favicon side by side -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <ImageUpload v-model="company.logo_url" label="Company Logo" height="h-28"/>
                        <div>
                            <ImageUpload v-model="company.favicon_url" label="Browser Favicon (ICO / PNG, 32×32 recommended)" height="h-28"/>
                            <p class="text-[11px] text-gray-400 mt-1.5">Shown as the browser tab icon. Upload a square .ico or .png file.</p>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" :disabled="savingSection === 'company'" class="inline-flex items-center gap-2 bg-cyan-500 hover:bg-cyan-600 disabled:opacity-60 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                            <svg v-if="savingSection === 'company'" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                            {{ savingSection === 'company' ? 'Saving…' : 'Save Company Info' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- ── 2. Contact Details ─────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="bg-gray-50 px-5 py-3 border-b border-gray-100 flex items-center gap-3">
                    <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    <span class="text-sm font-semibold text-dark">Contact Details</span>
                </div>
                <form @submit.prevent="saveSection('contact', contact)" class="p-5 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Email</label>
                            <input v-model="contact.email" type="email" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Support Email</label>
                            <input v-model="contact.support_email" type="email" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Phone</label>
                            <input v-model="contact.phone" placeholder="+254 700 000 000" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Business Hours</label>
                            <input v-model="contact.hours" placeholder="Mon-Fri 8am-6pm" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Phone 2 (Secondary)</label>
                            <input v-model="contact.phone_secondary" placeholder="+254 700 000 001" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Website URL</label>
                            <input v-model="contact.website" type="url" placeholder="https://www.tejprintbrands.com" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Address</label>
                        <textarea v-model="contact.address" rows="2" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all resize-none"></textarea>
                    </div>

                    <!-- WhatsApp -->
                    <div class="border-t border-gray-100 pt-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            WhatsApp Settings
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">WhatsApp Number</label>
                                <input v-model="contact.whatsapp_number" placeholder="254700000000 (no + or spaces)" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                                <p class="text-[11px] text-gray-400 mt-1">Include country code, no +. e.g. 254712345678</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Pre-filled WhatsApp Message</label>
                                <input v-model="contact.whatsapp_message" placeholder="Hello, I'd like to inquire about your services." class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" :disabled="savingSection === 'contact'" class="inline-flex items-center gap-2 bg-cyan-500 hover:bg-cyan-600 disabled:opacity-60 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                            <svg v-if="savingSection === 'contact'" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                            {{ savingSection === 'contact' ? 'Saving…' : 'Save Contact Details' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- ── 3. Social Media ────────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="bg-gray-50 px-5 py-3 border-b border-gray-100 flex items-center gap-3">
                    <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    <span class="text-sm font-semibold text-dark">Social Media</span>
                </div>
                <form @submit.prevent="saveSection('socials', socials)" class="p-5 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Facebook</label>
                            <input v-model="socials.facebook" type="url" placeholder="https://facebook.com/…" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Instagram</label>
                            <input v-model="socials.instagram" type="url" placeholder="https://instagram.com/…" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Twitter / X</label>
                            <input v-model="socials.twitter" type="url" placeholder="https://twitter.com/…" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">LinkedIn</label>
                            <input v-model="socials.linkedin" type="url" placeholder="https://linkedin.com/…" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">YouTube</label>
                            <input v-model="socials.youtube" type="url" placeholder="https://youtube.com/…" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">TikTok</label>
                            <input v-model="socials.tiktok" type="url" placeholder="https://tiktok.com/@…" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="savingSection === 'socials'" class="inline-flex items-center gap-2 bg-cyan-500 hover:bg-cyan-600 disabled:opacity-60 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                            <svg v-if="savingSection === 'socials'" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                            {{ savingSection === 'socials' ? 'Saving…' : 'Save Social Links' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- ── 4. Business Settings ───────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="bg-gray-50 px-5 py-3 border-b border-gray-100 flex items-center gap-3">
                    <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm font-semibold text-dark">Business Settings</span>
                </div>
                <form @submit.prevent="saveSection('business', business)" class="p-5 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Currency</label>
                            <select v-model="business.currency" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all bg-white">
                                <option value="KES">KES – Kenyan Shilling</option>
                                <option value="USD">USD – US Dollar</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Tax Rate (%)</label>
                            <input v-model="business.tax_rate" type="number" min="0" max="100" step="0.1" placeholder="16" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                        </div>
                    </div>

                    <!-- M-Pesa / STK Push -->
                    <div class="border-t border-gray-100 pt-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            M-Pesa STK Push
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Paybill / Till Number</label>
                                <input v-model="business.mpesa_shortcode" placeholder="174379" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Environment</label>
                                <select v-model="business.mpesa_env" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all bg-white">
                                    <option value="sandbox">Sandbox (testing)</option>
                                    <option value="live">Live (production)</option>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Paybill Account Number</label>
                                <input v-model="business.paybill_account" placeholder="e.g. 5001411001" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all"/>
                                <p class="text-[11px] text-gray-400 mt-1">Account number shown on printed quotations and invoices.</p>
                            </div>
                        </div>

                        <!-- API credentials — collapsed by default -->
                        <div class="mt-3">
                            <button type="button" @click="showMpesaKeys = !showMpesaKeys"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-500 hover:text-gray-800 transition-colors">
                                <svg :class="['w-3.5 h-3.5 transition-transform', showMpesaKeys ? 'rotate-90' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                {{ showMpesaKeys ? 'Hide' : 'Show' }} API credentials
                            </button>
                        </div>
                        <div v-if="showMpesaKeys" class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Consumer Key</label>
                                <input v-model="business.mpesa_consumer_key" type="password" autocomplete="off" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all font-mono"/>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Consumer Secret</label>
                                <input v-model="business.mpesa_consumer_secret" type="password" autocomplete="off" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all font-mono"/>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Passkey</label>
                                <input v-model="business.mpesa_passkey" type="password" autocomplete="off" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 transition-all font-mono"/>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" :disabled="savingSection === 'business'" class="inline-flex items-center gap-2 bg-cyan-500 hover:bg-cyan-600 disabled:opacity-60 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                            <svg v-if="savingSection === 'business'" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                            {{ savingSection === 'business' ? 'Saving…' : 'Save Business Settings' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- ── 5. Security ────────────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="bg-gray-50 px-5 py-3 border-b border-gray-100 flex items-center gap-3">
                    <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span class="text-sm font-semibold text-dark">Security — Change Password</span>
                </div>
                <form @submit.prevent="changePassword" class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Current Password</label>
                        <input v-model="password.current_password" type="password" required
                            :class="['w-full border rounded-xl px-4 py-3 text-sm outline-none transition-all', fieldError('current_password') ? 'border-red-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20' : 'border-gray-300 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20']"/>
                        <p v-if="fieldError('current_password')" class="text-xs text-red-500 mt-1">{{ fieldError('current_password') }}</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">New Password</label>
                            <input v-model="password.password" type="password" required
                                :class="['w-full border rounded-xl px-4 py-3 text-sm outline-none transition-all', fieldError('password') ? 'border-red-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20' : 'border-gray-300 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20']"/>
                            <p v-if="fieldError('password')" class="text-xs text-red-500 mt-1">{{ fieldError('password') }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Confirm New Password</label>
                            <input v-model="password.password_confirmation" type="password" required
                                :class="['w-full border rounded-xl px-4 py-3 text-sm outline-none transition-all', fieldError('password_confirmation') ? 'border-red-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20' : 'border-gray-300 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20']"/>
                            <p v-if="fieldError('password_confirmation')" class="text-xs text-red-500 mt-1">{{ fieldError('password_confirmation') }}</p>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="savingPassword" class="inline-flex items-center gap-2 bg-cyan-500 hover:bg-cyan-600 disabled:opacity-60 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                            <svg v-if="savingPassword" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                            {{ savingPassword ? 'Changing…' : 'Change Password' }}
                        </button>
                    </div>
                </form>
            </div>

        </template>
    </div>
</template>
