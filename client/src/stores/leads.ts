import { defineStore } from 'pinia'
import api from '@/services/api'
import type { Attachment, UploadedFileMeta } from '@/utils/attachment'

export type FileMeta = Attachment

export type LeadStatus =
  | 'new'
  | 'contacted'
  | 'discovery-call'
  | 'proposal-sent'
  | 'negotiation'
  | 'won'
  | 'lost'
  | 'in-development'
  | 'delivered'
  | 'maintenance'

export type LeadPriority = 'hot' | 'warm' | 'cold'
export type AiAnalysisStatus = 'pending' | 'ready'

export interface AiTechItem {
  choice: string
  why: string
}

/** Kept snake_case (matches the API 1:1) — this blob is edited/saved as a whole unit, not merged field-by-field. */
export interface AiAnalysis {
  project_summary: string
  tech_stack: {
    frontend: AiTechItem
    backend: AiTechItem
    database: AiTechItem
    hosting: AiTechItem
    authentication: AiTechItem
    integrations: AiTechItem
  }
  complexity: { level: 'Low' | 'Medium' | 'High' | 'Enterprise'; explanation: string }
  timeline: { total_duration: string; phases: { phase: string; duration: string }[] }
  missing_requirements: string[]
  questions: string[]
  risks: { title: string; explanation: string }[]
  quotation: { currency: string; range_low: number; range_high: number; reasoning: string[] }
}

export interface Note {
  id: string
  text: string
  createdAt: string
}

export interface ActivityEntry {
  id: string
  text: string
  createdAt: string
}

export interface Lead {
  id: string
  createdAt: string

  fullName: string
  companyName: string
  phone: string
  whatsapp: string
  email: string
  city: string
  preferredContact: string

  services: string[]
  otherService: string

  businessName: string
  industry: string
  websiteUrl: string
  socialLinks: string
  businessDescription: string

  projectIdea: string
  mainGoal: string
  targetUsers: string
  problems: string
  coreFeatures: string

  features: Record<string, string[]>
  otherFeature: string

  designStyle: string
  referenceLinks: string[]
  designFiles: FileMeta[]

  budget: string
  timeline: string

  generalFiles: FileMeta[]

  additionalNotes: string

  status: LeadStatus
  stageEnteredAt: string
  assignedTo: string
  assignedToId: number | null
  dueDate: string
  priority: LeadPriority
  aiAnalysisStatus: AiAnalysisStatus
  aiAnalysis: AiAnalysis | null
  notes: Note[]
  activity: ActivityEntry[]
  convertedClientId: string | null
  /** The project this lead became once converted/won. */
  projectId: string | null
  /** Set when this lead's email already belongs to an onboarded client (e.g. submitted via their portal's "Start New Project" form) — converting will add a Project to that client instead of creating a new one. */
  existingClientMatch: { id: string; name: string } | null
}

export interface ConvertLeadOptions {
  createPortalAccount: boolean
  sendWelcomeEmail: boolean
  generateTemporaryPassword: boolean
}

export interface ConvertLeadResult {
  clientId: string
  projectId: string
  /** True when this lead's email matched an already-onboarded client — no new Client/account was created, just a Project. */
  existingClient: boolean
  temporaryPassword: string | null
  emailSent: boolean | null
}

export type NewLead = Omit<
  Lead,
  | 'id'
  | 'createdAt'
  | 'status'
  | 'stageEnteredAt'
  | 'assignedTo'
  | 'assignedToId'
  | 'dueDate'
  | 'priority'
  | 'aiAnalysisStatus'
  | 'aiAnalysis'
  | 'notes'
  | 'activity'
  | 'convertedClientId'
  | 'projectId'
  | 'existingClientMatch'
  | 'designFiles'
  | 'generalFiles'
> & {
  /** Already uploaded to Cloudinary (see uploadFiles in utils/attachment) — this is the real, downloadable metadata, not just a filename. */
  designFiles: UploadedFileMeta[]
  generalFiles: UploadedFileMeta[]
}

/** Shape used while the wizard is being filled in — file fields hold raw Files, not metadata yet. */
export interface RequestFormState extends Omit<NewLead, 'designFiles' | 'generalFiles'> {
  designFiles: File[]
  generalFiles: File[]
}

export const leadStatuses: { value: LeadStatus; label: string }[] = [
  { value: 'new', label: 'New Lead' },
  { value: 'contacted', label: 'Contacted' },
  { value: 'discovery-call', label: 'Discovery Call Scheduled' },
  { value: 'proposal-sent', label: 'Proposal Sent' },
  { value: 'negotiation', label: 'Negotiation' },
  { value: 'won', label: 'Won ✓' },
  { value: 'lost', label: 'Lost ✗' },
  { value: 'in-development', label: 'In Development' },
  { value: 'delivered', label: 'Delivered' },
  { value: 'maintenance', label: 'Maintenance / Retainer' },
]

/** Stages that count as "won" for reporting, including everything after the win. */
export const wonStatuses: LeadStatus[] = ['won', 'in-development', 'delivered', 'maintenance']

export const leadPriorities: { value: LeadPriority; label: string }[] = [
  { value: 'hot', label: 'Hot' },
  { value: 'warm', label: 'Warm' },
  { value: 'cold', label: 'Cold' },
]

/** Mirrors Lead::SERVICE_OPTIONS on the backend. */
export const serviceOptions = [
  'Website',
  'Mobile App',
  'ERP System',
  'AI Solution',
  'Branding',
  'UI/UX Design',
  'E-commerce',
  'SEO',
  'Social Media Management',
  'Custom Software',
]

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapAttachment(a: any): FileMeta {
  return { id: String(a.id), name: a.name, size: a.size ?? 0, type: a.mime_type ?? undefined, url: a.url }
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapNote(n: any): Note {
  return { id: String(n.id), text: n.body, createdAt: n.created_at }
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapActivity(a: any): ActivityEntry {
  return { id: String(a.id), text: a.description, createdAt: a.created_at }
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapLead(l: any): Lead {
  return {
    id: String(l.id),
    createdAt: l.created_at,
    fullName: l.full_name,
    companyName: l.company_name ?? '',
    phone: l.phone ?? '',
    whatsapp: l.whatsapp ?? '',
    email: l.email,
    city: l.city ?? '',
    preferredContact: l.preferred_contact ?? '',
    services: l.services ?? [],
    otherService: l.other_service ?? '',
    businessName: l.business_name,
    industry: l.industry ?? '',
    websiteUrl: l.website_url ?? '',
    socialLinks: l.social_links ?? '',
    businessDescription: l.business_description ?? '',
    projectIdea: l.project_idea ?? '',
    mainGoal: l.main_goal ?? '',
    targetUsers: l.target_users ?? '',
    problems: l.problems ?? '',
    coreFeatures: l.core_features ?? '',
    features: l.features ?? {},
    otherFeature: l.other_feature ?? '',
    designStyle: l.design_style ?? '',
    referenceLinks: l.reference_links ?? [],
    designFiles: [],
    budget: l.budget ?? '',
    timeline: l.timeline ?? '',
    generalFiles: (l.attachments ?? []).map(mapAttachment),
    additionalNotes: l.additional_notes ?? '',
    status: l.status,
    stageEnteredAt: l.stage_entered_at ?? l.created_at,
    assignedTo: l.assignee?.name ?? 'Unassigned',
    assignedToId: l.assigned_to ?? null,
    dueDate: l.due_date ?? '',
    priority: l.priority,
    aiAnalysisStatus: l.ai_analysis_status,
    aiAnalysis: l.ai_analysis ?? null,
    notes: (l.notes ?? []).map(mapNote),
    activity: (l.activities ?? []).map(mapActivity),
    convertedClientId: l.converted_client_id != null ? String(l.converted_client_id) : null,
    projectId: l.project_id != null ? String(l.project_id) : null,
    existingClientMatch: l.existing_client_match
      ? { id: String(l.existing_client_match.id), name: l.existing_client_match.name }
      : null,
  }
}

/** Reverse of mapLead's field renames, for create/update payloads — undefined keys are omitted by JSON.stringify. */
function toApiPayload(lead: Partial<NewLead> & { assignedToId?: number | null }) {
  return {
    full_name: lead.fullName,
    company_name: lead.companyName,
    phone: lead.phone,
    whatsapp: lead.whatsapp,
    email: lead.email,
    city: lead.city,
    preferred_contact: lead.preferredContact,
    services: lead.services,
    other_service: lead.otherService,
    business_name: lead.businessName,
    industry: lead.industry,
    website_url: lead.websiteUrl,
    social_links: lead.socialLinks,
    business_description: lead.businessDescription,
    project_idea: lead.projectIdea,
    main_goal: lead.mainGoal,
    target_users: lead.targetUsers,
    problems: lead.problems,
    core_features: lead.coreFeatures,
    features: lead.features,
    other_feature: lead.otherFeature,
    design_style: lead.designStyle,
    reference_links: lead.referenceLinks,
    budget: lead.budget,
    timeline: lead.timeline,
    additional_notes: lead.additionalNotes,
    assigned_to: lead.assignedToId,
    design_files: lead.designFiles,
    general_files: lead.generalFiles,
  }
}

export const useLeadsStore = defineStore('leads', {
  state: () => ({
    leads: [] as Lead[],
    loading: false,
  }),
  actions: {
    async fetchLeads(params: Record<string, unknown> = {}) {
      this.loading = true
      try {
        const { data } = await api.get('/leads', {
          params: { per_page: 100, with: 'assignee,notes,activities,attachments', ...params },
        })
        this.leads = data.data.map(mapLead)
        return this.leads
      } finally {
        this.loading = false
      }
    },
    async fetchLead(id: string) {
      const { data } = await api.get(`/leads/${id}`, {
        params: { with: 'assignee,notes,activities,attachments' },
      })
      const lead = mapLead(data.data)
      const i = this.leads.findIndex((l) => l.id === id)
      if (i >= 0) this.leads[i] = lead
      else this.leads.push(lead)
      return lead
    },
    /** Public request-form submission — no auth required, matches the backend's public lead intake. */
    async addLead(lead: NewLead) {
      const { data } = await api.post('/leads', toApiPayload(lead))
      return mapLead(data.data)
    },
    async updateStatus(id: string, status: LeadStatus) {
      const { data } = await api.put(`/leads/${id}`, { status })
      const lead = mapLead({ ...data.data, notes: data.data.notes, activities: data.data.activities })
      this.replaceLead(lead)
    },
    async updatePriority(id: string, priority: LeadPriority) {
      const { data } = await api.put(`/leads/${id}`, { priority })
      this.replaceLead(mapLead(data.data))
    },
    async updateAssignee(id: string, userId: number | null) {
      const { data } = await api.put(`/leads/${id}`, { assigned_to: userId })
      this.replaceLead(mapLead(data.data))
    },
    async runAiAnalysis(id: string) {
      await api.post(`/leads/${id}/ai/analyze`)
      await this.fetchLead(id)
    },
    async updateAiAnalysis(id: string, analysis: AiAnalysis) {
      const { data } = await api.put(`/leads/${id}/ai/analysis`, analysis)
      this.replaceLead(mapLead(data.data))
    },
    /** Returns the id of the newly created Proposal Document. */
    async generateProposal(id: string): Promise<string> {
      const { data } = await api.post(`/leads/${id}/ai/proposal`)
      return String(data.data.id)
    },
    async addNote(id: string, text: string) {
      const { data } = await api.post(`/leads/${id}/notes`, { body: text })
      const lead = this.leads.find((l) => l.id === id)
      if (lead) lead.notes.unshift(mapNote(data.data))
      await this.fetchLead(id) // refresh activity log too
    },
    async updateNote(id: string, noteId: string, text: string) {
      await api.put(`/leads/${id}/notes/${noteId}`, { body: text })
      await this.fetchLead(id)
    },
    async addAttachments(id: string, files: UploadedFileMeta[]) {
      for (const file of files) {
        await api.post(`/leads/${id}/attachments`, file)
      }
      await this.fetchLead(id)
    },
    async removeAttachment(id: string, attachmentId: string) {
      await api.delete(`/leads/${id}/attachments/${attachmentId}`)
      await this.fetchLead(id)
    },
    async deleteNote(id: string, noteId: string) {
      await api.delete(`/leads/${id}/notes/${noteId}`)
      await this.fetchLead(id)
    },
    async deleteLead(id: string) {
      await api.delete(`/leads/${id}`)
      this.leads = this.leads.filter((l) => l.id !== id)
    },
    async convertToClient(id: string, options: ConvertLeadOptions): Promise<ConvertLeadResult> {
      const { data } = await api.post(`/leads/${id}/convert`, {
        create_portal_account: options.createPortalAccount,
        send_welcome_email: options.sendWelcomeEmail,
        generate_temporary_password: options.generateTemporaryPassword,
      })
      await this.fetchLead(id)
      return {
        clientId: String(data.data.client.id),
        projectId: String(data.data.project_id),
        existingClient: data.data.existing_client,
        temporaryPassword: data.data.temporary_password,
        emailSent: data.data.email_sent,
      }
    },
    replaceLead(lead: Lead) {
      const i = this.leads.findIndex((l) => l.id === lead.id)
      if (i >= 0) this.leads[i] = lead
      else this.leads.push(lead)
    },
  },
  getters: {
    getLead: (state) => (id: string) => state.leads.find((l) => l.id === id),
  },
})
