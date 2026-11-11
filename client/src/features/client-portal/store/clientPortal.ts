import { defineStore } from 'pinia'
import api from '@/services/api'
import type {
  PortalDashboard,
  PortalProject,
  PortalDocument,
  PortalMeeting,
  PortalTimelineEntry,
  PortalProfile,
  PortalNotificationPreferences,
  PortalDesignSubmission,
  PortalPayment,
} from '@/features/client-portal/types'
import type { UploadedFileMeta } from '@/utils/attachment'

export const useClientPortalStore = defineStore('clientPortal', {
  state: () => ({
    dashboard: null as PortalDashboard | null,
    projects: [] as PortalProject[],
    documents: [] as PortalDocument[],
    meetings: [] as PortalMeeting[],
    timeline: [] as PortalTimelineEntry[],
    profile: null as PortalProfile | null,
  }),
  actions: {
    async fetchDashboard() {
      const { data } = await api.get('/portal/dashboard')
      this.dashboard = data.data
      return this.dashboard!
    },
    async fetchProjects(params: Record<string, unknown> = {}) {
      const { data } = await api.get('/portal/projects', { params })
      this.projects = data.data
      return this.projects
    },
    async fetchProject(id: number | string): Promise<PortalProject> {
      const { data } = await api.get(`/portal/projects/${id}`)
      return data.data
    },
    async addProjectAttachment(projectId: number | string, file: UploadedFileMeta) {
      const { data } = await api.post(`/portal/projects/${projectId}/attachments`, file)
      return data.data
    },
    async reviewDesign(
      projectId: number | string,
      designId: number | string,
      status: 'approved' | 'rejected',
      note?: string,
    ): Promise<PortalDesignSubmission> {
      const { data } = await api.put(
        `/portal/projects/${projectId}/design-submissions/${designId}/review`,
        { status, note },
      )
      return data.data
    },
    async fetchDocuments(params: Record<string, unknown> = {}) {
      const { data } = await api.get('/portal/documents', { params })
      this.documents = data.data
      return this.documents
    },
    async fetchDocument(id: number | string): Promise<PortalDocument> {
      const { data } = await api.get(`/portal/documents/${id}`)
      return data.data
    },
    async approveDocument(id: number | string): Promise<PortalDocument> {
      const { data } = await api.post(`/portal/documents/${id}/approve`)
      const doc = data.data as PortalDocument
      const i = this.documents.findIndex((d) => d.id === doc.id)
      if (i >= 0) this.documents[i] = doc
      return doc
    },
    async rejectDocument(id: number | string): Promise<PortalDocument> {
      const { data } = await api.post(`/portal/documents/${id}/reject`)
      const doc = data.data as PortalDocument
      const i = this.documents.findIndex((d) => d.id === doc.id)
      if (i >= 0) this.documents[i] = doc
      return doc
    },
    async downloadDocumentPdf(id: number | string) {
      const response = await api.get(`/portal/documents/${id}/pdf`, { responseType: 'blob' })
      const match = /filename="?([^";]+)"?/.exec(response.headers['content-disposition'] ?? '')
      const filename = match?.[1] ?? `document-${id}.pdf`
      const url = URL.createObjectURL(response.data as Blob)
      const a = document.createElement('a')
      a.href = url
      a.download = filename
      a.click()
      URL.revokeObjectURL(url)
    },
    /** Opens the PDF in a new tab instead of saving it — same endpoint, no download attribute. */
    async previewDocumentPdf(id: number | string) {
      const response = await api.get(`/portal/documents/${id}/pdf`, { responseType: 'blob' })
      const url = URL.createObjectURL(response.data as Blob)
      window.open(url, '_blank', 'noopener')
    },
    async submitPaymentProof(
      documentId: number | string,
      payload: {
        amount: number
        stage: 'deposit' | 'mid_project' | 'final_delivery'
        name: string
        mime_type?: string | null
        size?: number
        url?: string
        secure_url?: string
        cloudinary_public_id?: string
      },
    ): Promise<PortalPayment> {
      const { data } = await api.post(`/portal/documents/${documentId}/payments`, payload)
      return data.data
    },
    async fetchMeetings(params: Record<string, unknown> = {}) {
      const { data } = await api.get('/portal/meetings', { params })
      this.meetings = data.data
      return this.meetings
    },
    async requestMeeting(payload: {
      title: string
      description?: string
      starts_at: string
      ends_at?: string
      location?: string
      project_id?: number
    }): Promise<PortalMeeting> {
      const { data } = await api.post('/portal/meetings', payload)
      return data.data
    },
    async fetchMeeting(id: number | string): Promise<PortalMeeting> {
      const { data } = await api.get(`/portal/meetings/${id}`)
      return data.data
    },
    async respondToMeeting(
      id: number | string,
      response: 'available' | 'unavailable',
      note?: string,
    ): Promise<PortalMeeting> {
      const { data } = await api.post(`/portal/meetings/${id}/respond`, { response, note })
      return data.data
    },
    async fetchTimeline(params: Record<string, unknown> = {}) {
      const { data } = await api.get('/portal/timeline', { params })
      this.timeline = data.data
      return this.timeline
    },
    async fetchProfile() {
      const { data } = await api.get('/portal/profile')
      this.profile = data.data
      return this.profile!
    },
    async updateProfile(payload: {
      name?: string
      email?: string
      notification_preferences?: Partial<PortalNotificationPreferences>
    }) {
      const { data } = await api.put('/portal/profile', payload)
      this.profile = data.data
      return this.profile!
    },
  },
})
