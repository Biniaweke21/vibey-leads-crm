import axios from 'axios'
import { getToken, clearToken } from './tokenStorage'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000/api',
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use((config) => {
  const token = getToken()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      const isClient = window.location.pathname.startsWith('/client')
      clearToken()
      // Key must match the matching store's own storage key — cleared
      // directly here (not via the store) to avoid a circular import. Only
      // the current context's session is cleared — team and client portal
      // are independent sessions (see tokenStorage.ts) and a 401 in one
      // must not log the other out.
      localStorage.removeItem(isClient ? 'vibey-client-portal-user' : 'vibey-auth-user')

      const loginPath = isClient ? '/client/login' : '/login'
      if (window.location.pathname !== loginPath) {
        window.location.href = loginPath
      }
    }
    return Promise.reject(error)
  },
)

export default api
