import { defineStore } from 'pinia'
import api from '@/services/api'
import type { Meeting } from '@/features/meetings/types'

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapMeeting(m: any): Meeting {
  return {
    id: String(m.id),
    title: m.title,
    description: m.description ?? '',
    type: m.type,
    type_label: m.type_label,
    status: m.status,
    status_label: m.status_label,
    starts_at: m.starts_at,
    ends_at: m.ends_at,
    duration_minutes: m.duration_minutes,
    location: m.location ?? '',
    meetingUrl: m.meeting_url,
    client_response: m.client_response,
    client_response_label: m.client_response_label,
    client_response_note: m.client_response_note,
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    attendees: (m.attendees ?? []).map((a: any) => ({
      id: String(a.id),
      name: a.name,
      userId: a.user_id,
    })),
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    notes: (m.notes ?? []).map((n: any) => ({ id: String(n.id), text: n.body, createdAt: n.created_at })),
    client: m.client ? { id: String(m.client.id), name: m.client.name } : null,
    project: m.project ? { id: String(m.project.id), name: m.project.name } : null,
    lead: m.lead ? { id: String(m.lead.id), name: m.lead.full_name } : null,
    createdAt: m.created_at,
  }
}

const DEFAULT_WITH = 'attendees,client,project,lead,notes'

export const useMeetingsStore = defineStore('meetings', {
  state: () => ({
    meetings: [] as Meeting[],
    loading: false,
  }),
  actions: {
    async fetchMeetings(params: Record<string, unknown> = {}) {
      this.loading = true
      try {
        const { data } = await api.get('/meetings', {
          params: { per_page: 100, with: DEFAULT_WITH, ...params },
        })
        this.meetings = data.data.map(mapMeeting)
        return this.meetings
      } finally {
        this.loading = false
      }
    },
    async fetchMeeting(id: string) {
      const { data } = await api.get(`/meetings/${id}`, { params: { with: DEFAULT_WITH } })
      const meeting = mapMeeting(data.data)
      this.replaceMeeting(meeting)
      return meeting
    },
    async addMeeting(input: {
      title: string
      startsAt: string
      durationMinutes: number
      location: string
      clientId?: string
      projectId?: string
      leadId?: string
    }) {
      const endsAt = new Date(new Date(input.startsAt).getTime() + input.durationMinutes * 60_000)
      const { data } = await api.post('/meetings', {
        title: input.title,
        starts_at: input.startsAt,
        ends_at: endsAt.toISOString(),
        location: input.location,
        client_id: input.clientId ? Number(input.clientId) : undefined,
        project_id: input.projectId ? Number(input.projectId) : undefined,
        lead_id: input.leadId ? Number(input.leadId) : undefined,
      })
      const meeting = mapMeeting(data.data)
      this.meetings.push(meeting)
      return meeting
    },
    async updateStatus(id: string, status: Meeting['status']) {
      const { data } = await api.put(`/meetings/${id}`, { status })
      this.replaceMeeting(mapMeeting(data.data))
    },
    async addAttendee(id: string, userId: number) {
      await api.post(`/meetings/${id}/attendees`, { user_id: userId })
    },
    async addNote(id: string, text: string) {
      await api.post(`/meetings/${id}/notes`, { body: text })
      await this.fetchMeeting(id)
    },
    async updateNote(id: string, noteId: string, text: string) {
      await api.put(`/meetings/${id}/notes/${noteId}`, { body: text })
      await this.fetchMeeting(id)
    },
    async deleteNote(id: string, noteId: string) {
      await api.delete(`/meetings/${id}/notes/${noteId}`)
      await this.fetchMeeting(id)
    },
    replaceMeeting(meeting: Meeting) {
      const i = this.meetings.findIndex((m) => m.id === meeting.id)
      if (i >= 0) this.meetings[i] = meeting
      else this.meetings.push(meeting)
    },
  },
  getters: {
    getMeeting: (state) => (id: string) => state.meetings.find((m) => m.id === id),
    upcoming: (state) =>
      [...state.meetings]
        .filter((m) => m.status === 'upcoming' && new Date(m.starts_at) >= new Date())
        .sort((a, b) => a.starts_at.localeCompare(b.starts_at)),
    past: (state) =>
      [...state.meetings]
        .filter((m) => m.status !== 'upcoming' || new Date(m.starts_at) < new Date())
        .sort((a, b) => b.starts_at.localeCompare(a.starts_at)),
    forClient: (state) => (clientId: string) =>
      state.meetings.filter((m) => m.client?.id === clientId),
    forProject: (state) => (projectId: string) =>
      state.meetings.filter((m) => m.project?.id === projectId),
    forLead: (state) => (leadId: string) => state.meetings.filter((m) => m.lead?.id === leadId),
  },
})
