import { defineStore } from 'pinia'
import api from '@/services/api'
import type { Attachment, UploadedFileMeta } from '@/utils/attachment'
import type { ProjectStatus } from '@/stores/projects'

export type ClientStatus = 'active' | 'inactive' | 'past'

export interface ClientContact {
  id: string
  name: string
  role: string
  email: string
  phone: string
  isPrimary: boolean
}

export type ClientDocument = Attachment

export interface ClientNote {
  id: string
  text: string
  createdAt: string
}

export interface ClientActivityEntry {
  id: string
  text: string
  createdAt: string
}

export interface ClientPayment {
  id: string
  description: string
  amount: number
  date: string
  status: 'paid' | 'pending'
}

export interface ClientProjectSummary {
  id: string
  name: string
  status: ProjectStatus
  progress: number
}

export interface PortalAccount {
  email: string
  portalEnabled: boolean
  mustChangePassword: boolean
  lastLoginAt: string | null
}

export interface PortalActionResult {
  temporaryPassword: string | null
  emailSent: boolean | null
}

export interface Client {
  id: string
  name: string
  industry: string
  city: string
  status: ClientStatus
  lastInteraction: string
  activeProjectsCount: number
  revenue: number
  contacts: ClientContact[]
  documents: ClientDocument[]
  notes: ClientNote[]
  activity: ClientActivityEntry[]
  payments: ClientPayment[]
  projects: ClientProjectSummary[]
  portalAccount: PortalAccount | null
}

export const clientStatuses: { value: ClientStatus; label: string }[] = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'past', label: 'Past' },
]

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapContact(c: any): ClientContact {
  return {
    id: String(c.id),
    name: c.name,
    role: c.role ?? '',
    email: c.email ?? '',
    phone: c.phone ?? '',
    isPrimary: c.is_primary,
  }
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapAttachment(a: any): ClientDocument {
  return { id: String(a.id), name: a.name, size: a.size ?? 0, type: a.mime_type ?? undefined, url: a.url }
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapNote(n: any): ClientNote {
  return { id: String(n.id), text: n.body, createdAt: n.created_at }
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapActivity(a: any): ClientActivityEntry {
  return { id: String(a.id), text: a.description, createdAt: a.created_at }
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapPayment(p: any): ClientPayment {
  return {
    id: String(p.id),
    description: p.method ? `Payment via ${p.method}` : 'Payment',
    amount: Number(p.amount),
    date: p.paid_at ?? p.created_at,
    status: p.status,
  }
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapPortalAccount(a: any): PortalAccount | null {
  if (!a) return null
  return {
    email: a.email,
    portalEnabled: a.portal_enabled,
    mustChangePassword: a.must_change_password,
    lastLoginAt: a.last_login_at,
  }
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapClient(c: any): Client {
  return {
    id: String(c.id),
    name: c.name,
    industry: c.industry ?? '',
    city: c.city ?? '',
    status: c.status,
    lastInteraction: c.last_interaction_at ?? c.created_at,
    activeProjectsCount: c.active_projects_count ?? 0,
    revenue: c.revenue ?? 0,
    contacts: (c.contacts ?? []).map(mapContact),
    documents: (c.attachments ?? []).map(mapAttachment),
    notes: (c.notes ?? []).map(mapNote),
    activity: (c.activities ?? []).map(mapActivity),
    payments: (c.payments ?? []).map(mapPayment),
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    projects: (c.projects ?? []).map((p: any) => ({
      id: String(p.id),
      name: p.name,
      status: p.status,
      progress: p.progress,
    })),
    portalAccount: mapPortalAccount(c.portal_account),
  }
}

const DEFAULT_WITH = 'contacts,notes,activities,payments,projects,attachments,portalUser'

export const useClientsStore = defineStore('clients', {
  state: () => ({
    clients: [] as Client[],
    loading: false,
  }),
  actions: {
    async fetchClients(params: Record<string, unknown> = {}) {
      this.loading = true
      try {
        const { data } = await api.get('/clients', {
          params: { per_page: 100, with: DEFAULT_WITH, ...params },
        })
        this.clients = data.data.map(mapClient)
        return this.clients
      } finally {
        this.loading = false
      }
    },
    async fetchClient(id: string) {
      const { data } = await api.get(`/clients/${id}`, { params: { with: DEFAULT_WITH } })
      const client = mapClient(data.data)
      this.replaceClient(client)
      return client
    },
    async addNote(id: string, text: string) {
      await api.post(`/clients/${id}/notes`, { body: text })
      await this.fetchClient(id)
    },
    async updateNote(id: string, noteId: string, text: string) {
      await api.put(`/clients/${id}/notes/${noteId}`, { body: text })
      await this.fetchClient(id)
    },
    async deleteNote(id: string, noteId: string) {
      await api.delete(`/clients/${id}/notes/${noteId}`)
      await this.fetchClient(id)
    },
    async addContact(id: string, contact: { name: string; role: string; email: string; phone: string }) {
      await api.post(`/clients/${id}/contacts`, contact)
      await this.fetchClient(id)
    },
    async addAttachments(id: string, files: UploadedFileMeta[]) {
      for (const file of files) {
        await api.post(`/clients/${id}/attachments`, file)
      }
      await this.fetchClient(id)
    },
    async removeAttachment(id: string, attachmentId: string) {
      await api.delete(`/clients/${id}/attachments/${attachmentId}`)
      await this.fetchClient(id)
    },
    async enablePortal(id: string): Promise<PortalActionResult> {
      const { data } = await api.post(`/clients/${id}/portal/enable`)
      await this.fetchClient(id)
      return { temporaryPassword: data.data.temporary_password, emailSent: data.data.email_sent }
    },
    async disablePortal(id: string) {
      await api.post(`/clients/${id}/portal/disable`)
      await this.fetchClient(id)
    },
    async resetPortalPassword(id: string): Promise<PortalActionResult> {
      const { data } = await api.post(`/clients/${id}/portal/reset-password`)
      await this.fetchClient(id)
      return { temporaryPassword: data.data.temporary_password, emailSent: data.data.email_sent }
    },
    async regeneratePortalPassword(id: string): Promise<PortalActionResult> {
      const { data } = await api.post(`/clients/${id}/portal/regenerate-password`)
      await this.fetchClient(id)
      return { temporaryPassword: data.data.temporary_password, emailSent: data.data.email_sent }
    },
    async resendWelcomeEmail(id: string): Promise<PortalActionResult> {
      const { data } = await api.post(`/clients/${id}/portal/resend-welcome`)
      await this.fetchClient(id)
      return { temporaryPassword: data.data.temporary_password, emailSent: data.data.email_sent }
    },
    replaceClient(client: Client) {
      const i = this.clients.findIndex((c) => c.id === client.id)
      if (i >= 0) this.clients[i] = client
      else this.clients.push(client)
    },
  },
  getters: {
    getClient: (state) => (id: string) => state.clients.find((c) => c.id === id),
    totalRevenue: () => (client: Client) => client.revenue,
  },
})
