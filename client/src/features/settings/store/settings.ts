import { defineStore } from 'pinia'
import api from '@/services/api'

export interface TeamMemberProfile {
  id: number
  name: string
  email: string
  role: 'admin' | 'team_member'
  roleLabel: string
}

export const teamRoleOptions: { value: TeamMemberProfile['role']; label: string }[] = [
  { value: 'admin', label: 'Admin' },
  { value: 'team_member', label: 'Team Member' },
]

export interface CompanyInfo {
  name: string
  industry: string
  website: string
  address: string
  phone: string
  email: string
  logo_url: string
  primary_color: string
  stamp_url: string
}

export interface NotificationPrefs {
  newLeads: boolean
  invoicePaid: boolean
  meetingReminders: boolean
  weeklyDigest: boolean
  productUpdates: boolean
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function mapMember(u: any): TeamMemberProfile {
  return { id: u.id, name: u.name, email: u.email, role: u.role, roleLabel: u.role_label }
}

export const useSettingsStore = defineStore('settings', {
  state: () => ({
    company: {
      name: '',
      industry: '',
      website: '',
      address: '',
      phone: '',
      email: '',
      logo_url: '',
      primary_color: '#5ba7ff',
      stamp_url: '',
    } as CompanyInfo,
    team: [] as TeamMemberProfile[],
    notifications: {
      newLeads: true,
      invoicePaid: true,
      meetingReminders: true,
      weeklyDigest: true,
      productUpdates: false,
    } as NotificationPrefs,
    googleCalendar: { connected: false, email: null as string | null },
  }),
  actions: {
    async fetchCompany() {
      const { data } = await api.get('/settings/company')
      if (data.data && Object.keys(data.data).length > 0) this.company = data.data
    },
    async updateCompany(company: CompanyInfo) {
      const { data } = await api.put('/settings/company', company)
      this.company = data.data
    },
    async fetchTeam() {
      const { data } = await api.get('/team')
      this.team = data.data.map(mapMember)
    },
    async addTeamMember(input: { name: string; email: string; role: TeamMemberProfile['role'] }) {
      const { data } = await api.post('/team', input)
      this.team.push(mapMember(data.data))
    },
    async removeTeamMember(id: number) {
      await api.delete(`/team/${id}`)
      this.team = this.team.filter((m) => m.id !== id)
    },
    async updateTeamMemberRole(id: number, role: TeamMemberProfile['role']) {
      const { data } = await api.put(`/team/${id}`, { role })
      const member = this.team.find((m) => m.id === id)
      if (member) Object.assign(member, mapMember(data.data))
    },
    async fetchNotifications() {
      const { data } = await api.get('/settings/notifications')
      if (data.data && Object.keys(data.data).length > 0) this.notifications = data.data
    },
    async updateNotifications(notifications: NotificationPrefs) {
      const { data } = await api.put('/settings/notifications', notifications)
      this.notifications = data.data
    },
    async fetchGoogleStatus() {
      const { data } = await api.get('/settings/google/status')
      this.googleCalendar = data.data
    },
    /** Returns the Google consent URL — caller navigates the browser to it. */
    async connectGoogle(): Promise<string> {
      const { data } = await api.get('/settings/google/connect')
      return data.data.url
    },
    async disconnectGoogle() {
      await api.post('/settings/google/disconnect')
      this.googleCalendar = { connected: false, email: null }
    },
  },
})
