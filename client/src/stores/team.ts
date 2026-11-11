import { defineStore } from 'pinia'
import api from '@/services/api'

export interface TeamMember {
  id: number
  name: string
  email: string
  role: string
  roleLabel: string
}

export const useTeamStore = defineStore('team', {
  state: () => ({
    members: [] as TeamMember[],
    loaded: false,
  }),
  actions: {
    /** Cached — the team roster changes rarely, no need to refetch on every page visit. */
    async ensureLoaded() {
      if (this.loaded) return this.members
      const { data } = await api.get('/team')
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      this.members = data.data.map((u: any) => ({
        id: u.id,
        name: u.name,
        email: u.email,
        role: u.role,
        roleLabel: u.role_label,
      }))
      this.loaded = true
      return this.members
    },
  },
})
