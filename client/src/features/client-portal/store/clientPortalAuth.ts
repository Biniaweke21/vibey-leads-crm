import { defineStore } from 'pinia'
import api from '@/services/api'
import { setToken, clearToken } from '@/services/tokenStorage'

const STORAGE_KEY = 'vibey-client-portal-user'

export interface ClientPortalUser {
  id: number
  name: string
  email: string
  mustChangePassword: boolean
}

function loadUser(): ClientPortalUser | null {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEY) ?? 'null')
  } catch {
    return null
  }
}

export const useClientPortalAuthStore = defineStore('clientPortalAuth', {
  state: () => ({
    user: loadUser() as ClientPortalUser | null,
  }),
  getters: {
    isAuthenticated: (state) => state.user !== null,
  },
  actions: {
    /** Throws on failure (bad credentials, or a non-client account) — callers should catch and show apiErrorMessage(err). */
    async login(email: string, password: string) {
      const { data } = await api.post('/login', { email, password })
      const u = data.data.user

      if (u.role !== 'client') {
        await api.post('/logout').catch(() => {})
        throw new Error('This account is not a client portal account.')
      }

      this.user = { id: u.id, name: u.name, email: u.email, mustChangePassword: u.must_change_password }
      setToken(data.data.token)
      localStorage.setItem(STORAGE_KEY, JSON.stringify(this.user))
    },
    async logout() {
      try {
        await api.post('/logout')
      } catch {
        // Token may already be invalid/expired server-side — clear local state regardless.
      } finally {
        this.user = null
        clearToken()
        localStorage.removeItem(STORAGE_KEY)
      }
    },
    async changePassword(payload: {
      current_password: string
      password: string
      password_confirmation: string
    }) {
      await api.put('/user/password', payload)
      if (this.user) {
        this.user.mustChangePassword = false
        localStorage.setItem(STORAGE_KEY, JSON.stringify(this.user))
      }
    },
  },
})
