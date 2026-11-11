import { defineStore } from 'pinia'
import api from '@/services/api'
import { setToken, clearToken } from '@/services/tokenStorage'

const STORAGE_KEY = 'vibey-auth-user'

export interface AuthUser {
  id: number
  name: string
  email: string
  role: string
  roleLabel: string
  signatureUrl: string | null
}

function loadUser(): AuthUser | null {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEY) ?? 'null')
  } catch {
    return null
  }
}

function mapUser(u: {
  id: number
  name: string
  email: string
  role: string
  role_label: string
  signature_url?: string | null
}): AuthUser {
  return {
    id: u.id,
    name: u.name,
    email: u.email,
    role: u.role,
    roleLabel: u.role_label,
    signatureUrl: u.signature_url ?? null,
  }
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: loadUser() as AuthUser | null,
  }),
  getters: {
    isAuthenticated: (state) => state.user !== null,
    isAdmin: (state) => state.user?.role === 'admin',
  },
  actions: {
    /** Throws on failure — callers should catch and show apiErrorMessage(err). */
    async login(email: string, password: string) {
      const { data } = await api.post('/login', { email, password })
      const u = data.data.user

      if (u.role === 'client') {
        await api.post('/logout').catch(() => {})
        throw new Error('This is a client account — use the client portal login.')
      }

      this.user = mapUser(u)
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
    async fetchCurrentUser() {
      const { data } = await api.get('/user')
      this.user = mapUser(data.data)
      localStorage.setItem(STORAGE_KEY, JSON.stringify(this.user))
    },
    async forgotPassword(email: string) {
      await api.post('/forgot-password', { email })
    },
    async resetPassword(payload: {
      token: string
      email: string
      password: string
      password_confirmation: string
    }) {
      await api.post('/reset-password', payload)
    },
    async changePassword(payload: {
      current_password: string
      password: string
      password_confirmation: string
    }) {
      await api.put('/user/password', payload)
    },
    async updateSignature(signatureUrl: string | null) {
      const { data } = await api.put('/user/signature', { signature_url: signatureUrl })
      this.user = mapUser(data.data)
      localStorage.setItem(STORAGE_KEY, JSON.stringify(this.user))
    },
  },
})
