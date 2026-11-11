import { defineStore } from 'pinia'
import api from '@/services/api'
import type { Attachment, UploadedFileMeta } from '@/utils/attachment'

export type ProjectStatus = 'planning' | 'in-progress' | 'on-hold' | 'completed' | 'maintenance'

export interface Milestone {
  id: string
  title: string
  dueDate: string
  done: boolean
}

export interface ProjectTask {
  id: string
  title: string
  assignedTo: string
  assignedToId: number | null
  status: 'todo' | 'in-progress' | 'done'
}

export type ProjectAttachment = Attachment

export type DesignStatus = 'pending' | 'approved' | 'rejected'

/** A design deliverable the team submits for the client to approve or reject. */
export interface DesignSubmission {
  id: string
  name: string
  size: number
  type?: string
  url?: string
  status: DesignStatus
  submittedAt: string
  decidedAt?: string
  note?: string
}

export interface ProjectActivityEntry {
  id: string
  text: string
  createdAt: string
}

export interface Project {
  id: string
  name: string
  description: string
  clientId: string
  /** The Lead this project was converted from — null for manually created projects. Links back to its AI Requirement Analysis. */
  leadId: string | null
  team: string[]
  status: ProjectStatus
  progress: number
  budget: number
  startDate: string | null
  endDate: string | null
  milestones: Milestone[]
  tasks: ProjectTask[]
  attachments: ProjectAttachment[]
  designs: DesignSubmission[]
  activity: ProjectActivityEntry[]
}

export const projectStatuses: { value: ProjectStatus; label: string }[] = [
  { value: 'planning', label: 'Planning' },
  { value: 'in-progress', label: 'In Progress' },
  { value: 'on-hold', label: 'On Hold' },
  { value: 'completed', label: 'Completed' },
  { value: 'maintenance', label: 'Maintenance' },
]

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapMilestone(m: any): Milestone {
  return { id: String(m.id), title: m.title, dueDate: m.due_date, done: m.is_done }
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapTask(t: any): ProjectTask {
  return {
    id: String(t.id),
    title: t.title,
    assignedTo: t.assignee?.name ?? 'Unassigned',
    assignedToId: t.assigned_to ?? null,
    // Backend uses 'in-progress'/'done'/'todo' — matches the mock's own union already.
    status: t.status,
  }
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapAttachment(a: any): ProjectAttachment {
  return { id: String(a.id), name: a.name, size: a.size ?? 0, type: a.mime_type ?? undefined, url: a.url }
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapDesign(d: any): DesignSubmission {
  return {
    id: String(d.id),
    name: d.name,
    size: d.size ?? 0,
    type: d.mime_type ?? undefined,
    url: d.url ?? undefined,
    status: d.status,
    submittedAt: d.submitted_at ?? d.created_at,
    decidedAt: d.decided_at ?? undefined,
    note: d.note ?? undefined,
  }
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapActivity(a: any): ProjectActivityEntry {
  return { id: String(a.id), text: a.description, createdAt: a.created_at }
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapProject(p: any): Project {
  return {
    id: String(p.id),
    name: p.name,
    description: p.description ?? '',
    clientId: String(p.client_id),
    leadId: p.lead_id != null ? String(p.lead_id) : null,
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    team: (p.members ?? []).map((m: any) => m.name),
    status: p.status,
    progress: p.progress,
    budget: Number(p.budget),
    startDate: p.start_date,
    endDate: p.end_date,
    milestones: (p.milestones ?? []).map(mapMilestone),
    tasks: (p.tasks ?? []).map(mapTask),
    attachments: (p.attachments ?? []).map(mapAttachment),
    designs: (p.design_submissions ?? []).map(mapDesign),
    activity: (p.activities ?? []).map(mapActivity),
  }
}

const DEFAULT_WITH = 'members,milestones,tasks,designSubmissions,activities,attachments'

export const useProjectsStore = defineStore('projects', {
  state: () => ({
    projects: [] as Project[],
    loading: false,
  }),
  actions: {
    async createProject(input: {
      clientId: string
      name: string
      description?: string
      status?: ProjectStatus
      budget?: number
      startDate?: string
      endDate?: string
    }) {
      const { data } = await api.post('/projects', {
        client_id: input.clientId,
        name: input.name,
        description: input.description?.trim() || undefined,
        status: input.status,
        budget: input.budget,
        start_date: input.startDate || undefined,
        end_date: input.endDate || undefined,
      })
      const project = mapProject(data.data)
      this.replaceProject(project)
      return project
    },
    async fetchProjects(params: Record<string, unknown> = {}) {
      this.loading = true
      try {
        const { data } = await api.get('/projects', {
          params: { per_page: 100, with: DEFAULT_WITH, ...params },
        })
        this.projects = data.data.map(mapProject)
        return this.projects
      } finally {
        this.loading = false
      }
    },
    async fetchProject(id: string) {
      const { data } = await api.get(`/projects/${id}`, { params: { with: DEFAULT_WITH } })
      const project = mapProject(data.data)
      this.replaceProject(project)
      return project
    },
    async updateStatus(id: string, status: ProjectStatus) {
      const { data } = await api.put(`/projects/${id}`, { status })
      await this.fetchProject(String(data.data.id))
    },
    async addMilestone(id: string, title: string, dueDate: string) {
      await api.post(`/projects/${id}/milestones`, { title, due_date: dueDate })
      await this.fetchProject(id)
    },
    async toggleMilestone(id: string, milestoneId: string) {
      const project = this.projects.find((p) => p.id === id)
      const milestone = project?.milestones.find((m) => m.id === milestoneId)
      if (!project || !milestone) return
      await api.put(`/projects/${id}/milestones/${milestoneId}`, { is_done: !milestone.done })
      await this.fetchProject(id)
    },
    async addTask(id: string, title: string, assignedToId: number | null) {
      await api.post(`/projects/${id}/tasks`, { title, assigned_to: assignedToId })
      await this.fetchProject(id)
    },
    async updateTaskStatus(id: string, taskId: string, status: ProjectTask['status']) {
      await api.put(`/projects/${id}/tasks/${taskId}`, { status })
      await this.fetchProject(id)
    },
    async addMember(id: string, userId: number) {
      await api.post(`/projects/${id}/members`, { user_id: userId })
      await this.fetchProject(id)
    },
    async removeMember(id: string, userId: number) {
      await api.delete(`/projects/${id}/members/${userId}`)
      await this.fetchProject(id)
    },
    async reviewDesign(id: string, designId: string, decision: 'approved' | 'rejected', note?: string) {
      await api.put(`/projects/${id}/design-submissions/${designId}`, {
        status: decision,
        note: note?.trim() || undefined,
      })
      await this.fetchProject(id)
    },
    async submitDesign(id: string, file: UploadedFileMeta) {
      await api.post(`/projects/${id}/design-submissions`, file)
      await this.fetchProject(id)
    },
    async addAttachments(id: string, files: UploadedFileMeta[]) {
      for (const file of files) {
        await api.post(`/projects/${id}/attachments`, file)
      }
      await this.fetchProject(id)
    },
    async removeAttachment(id: string, attachmentId: string) {
      await api.delete(`/projects/${id}/attachments/${attachmentId}`)
      await this.fetchProject(id)
    },
    replaceProject(project: Project) {
      const i = this.projects.findIndex((p) => p.id === project.id)
      if (i >= 0) this.projects[i] = project
      else this.projects.push(project)
    },
  },
  getters: {
    getProject: (state) => (id: string) => state.projects.find((p) => p.id === id),
    forClient: (state) => (clientId: string) =>
      state.projects.filter((p) => p.clientId === clientId),
    activeForClient: (state) => (clientId: string) =>
      state.projects.filter((p) => p.clientId === clientId && p.status !== 'completed').length,
  },
})
